<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

requireUserSession();
$user = getUserById($con, $_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($user['status'] === 'banned') {
    echo "<p>Your account is banned. Contact support.</p>";
    exit;
}

if ($user['email_verified'] !== 'verified') {
    header("Location: verify_email.php");
    exit;
}

if ($user['status'] === 'pending') {
    header("Location: register_payment.php");
    exit;
}

ensureUserWallet($con, $user['id']);
$wallet = getUserWallet($con, $user['id']);
$response = "";

if (isset($_POST['redeem_code'])) {
    $code = mysqli_real_escape_string($con, trim($_POST['code']));
    $codeResult = mysqli_query($con, "SELECT * FROM redeem_codes WHERE code='$code' AND status='active'");
    $redeemCode = $codeResult ? mysqli_fetch_assoc($codeResult) : null;

    if (!$redeemCode) {
        $response = "Invalid or inactive redeem code.";
    } else {
        $alreadyUsed = mysqli_query($con, "SELECT id FROM redeem_redemptions WHERE redeem_code_id={$redeemCode['id']} AND user_id={$user['id']}");
        if ($alreadyUsed && mysqli_num_rows($alreadyUsed) > 0) {
            $response = "You already used this redeem code.";
        } elseif ($redeemCode['uses_count'] >= $redeemCode['max_uses']) {
            $response = "Redeem code limit reached.";
        } else {
            $amount = number_format((float) $redeemCode['amount'], 2, '.', '');
            mysqli_begin_transaction($con);
            $insertRedeem = mysqli_query($con, "INSERT INTO redeem_redemptions (redeem_code_id, user_id) VALUES ({$redeemCode['id']}, {$user['id']})");
            $updateCode = mysqli_query($con, "UPDATE redeem_codes SET uses_count = uses_count + 1 WHERE id={$redeemCode['id']}");
            $updateWallet = mysqli_query($con, "UPDATE user_wallets SET balance = balance + $amount WHERE user_id={$user['id']}");
            $logTransaction = mysqli_query($con, "INSERT INTO user_transactions (user_id, type, amount, source, note) VALUES ({$user['id']}, 'credit', $amount, 'redeem', 'Redeem code')");

            if ($insertRedeem && $updateCode && $updateWallet && $logTransaction) {
                mysqli_commit($con);
                $wallet = getUserWallet($con, $user['id']);
                $response = "Redeem code applied. Balance updated.";
            } else {
                mysqli_rollback($con);
                $response = "Failed to apply redeem code.";
            }
        }
    }
}

if (isset($_POST['update_profile'])) {
    $newUsername = mysqli_real_escape_string($con, $_POST['new_username']);
    $newEmail = mysqli_real_escape_string($con, $_POST['new_email']);
    $newPassword = $_POST['new_password'];
    $updates = [];

    if ($newUsername && $newUsername !== $user['username']) {
        if (!preg_match('/^[A-Za-z0-9_]{4,}$/', $newUsername)) {
            $response = "Username must be at least 4 characters and contain only letters, numbers, or underscore.";
        } else {
            $lastUpdated = $user['username_updated_at'] ? strtotime($user['username_updated_at']) : 0;
            if ($lastUpdated && $lastUpdated > strtotime('-3 months')) {
                $response = "Username can only be changed once every 3 months.";
            } else {
                $check = mysqli_query($con, "SELECT id FROM users WHERE username='$newUsername' AND id!={$user['id']}");
                if ($check && mysqli_num_rows($check) > 0) {
                    $response = "Username already taken.";
                } else {
                    $updates[] = "username='$newUsername'";
                    $updates[] = "username_updated_at=NOW()";
                }
            }
        }
    }

    if ($newEmail && $newEmail !== $user['email']) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $response = "Invalid email address.";
        } else {
            $check = mysqli_query($con, "SELECT id FROM users WHERE email='$newEmail' AND id!={$user['id']}");
            if ($check && mysqli_num_rows($check) > 0) {
                $response = "Email already in use.";
            } else {
                $updates[] = "email='$newEmail'";
            }
        }
    }

    if ($newPassword) {
        if (strlen($newPassword) < 6) {
            $response = "Password must be at least 6 characters.";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updates[] = "password_hash='$hash'";
        }
    }

    if (!$response && !empty($updates)) {
        $updateQuery = "UPDATE users SET " . implode(', ', $updates) . " WHERE id={$user['id']}";
        if (mysqli_query($con, $updateQuery)) {
            $response = "Profile updated.";
            $user = getUserById($con, $user['id']);
        } else {
            $response = "Failed to update profile.";
        }
    }
}

$upcoming = mysqli_query($con, "SELECT * FROM tournaments WHERE status!='ended' AND start_time >= NOW() ORDER BY start_time ASC");
$myTournaments = mysqli_query(
    $con,
    "SELECT t.*, ute.result, ute.winnings_amount FROM user_tournament_entries ute JOIN tournaments t ON t.id = ute.tournament_id WHERE ute.user_id={$user['id']} ORDER BY t.start_time DESC"
);

$categoryGames = mysqli_query($con, "SELECT DISTINCT name FROM tournaments WHERE status!='ended' AND start_time >= NOW() ORDER BY name ASC");
$categoryMaps = mysqli_query($con, "SELECT DISTINCT map_name, name FROM tournaments WHERE status!='ended' AND start_time >= NOW() ORDER BY map_name ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>User Dashboard</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .dashboard-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .dashboard-tab {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            padding: 10px 16px;
            border-radius: 20px;
            cursor: pointer;
        }

        .dashboard-tab.active {
            background: hsl(31, 100%, 51%);
            color: #fff;
        }

        .dashboard-section {
            display: none;
        }

        .dashboard-section.active {
            display: block;
        }

        .card-dark {
            background: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }

        .profile-menu {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .profile-menu button {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }

        .profile-panel {
            display: none;
        }

        .profile-panel.active {
            display: block;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn-primary {
            background-color: hsl(31, 100%, 51%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <div class="profile-menu">
                        <button type="button" onclick="toggleProfile()">
                            <ion-icon name="person-circle-outline"></ion-icon>
                        </button>
                    </div>
                    <h2 class="h2 section-title">Welcome, <?= htmlspecialchars($user['username']) ?></h2>

                    <?php if ($response) { ?>
                        <div class="card-dark"><?= htmlspecialchars($response) ?></div>
                    <?php } ?>

                    <div class="dashboard-tabs">
                        <div class="dashboard-tab active" data-tab="tournaments">Tournaments</div>
                        <div class="dashboard-tab" data-tab="mytournaments">My Tournaments</div>
                        <div class="dashboard-tab" data-tab="category">Category</div>
                        <div class="dashboard-tab" data-tab="wallet">Add Money</div>
                    </div>

                    <div class="profile-panel card-dark" id="profilePanel">
                        <h3>Profile</h3>
                        <form method="post">
                            <label>Username (change once per 3 months)</label>
                            <input type="text" name="new_username" class="form-input" value="<?= htmlspecialchars($user['username']) ?>">
                            <label>Email</label>
                            <input type="email" name="new_email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-input" placeholder="Leave blank to keep current">
                            <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                        </form>
                        <div style="margin-top:16px;">
                            <a href="who-we-are.php">About</a> | <a href="rules.php">Terms & Conditions</a>
                        </div>
                        <div style="margin-top:16px;">
                            <a href="logout.php" class="btn-primary">Logout</a>
                        </div>
                    </div>

                    <div class="dashboard-section active" id="tab-tournaments">
                        <div class="grid">
                            <?php if ($upcoming && mysqli_num_rows($upcoming) > 0) { ?>
                                <?php while ($tournament = mysqli_fetch_assoc($upcoming)) { ?>
                                    <div class="card-dark">
                                        <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                                        <p>Map: <?= htmlspecialchars($tournament['map_name']) ?></p>
                                        <p><?= nl2br(htmlspecialchars($tournament['description'])) ?></p>
                                        <p>Entry Fee: ₹<?= number_format((float) $tournament['entry_fee'], 2) ?></p>
                                        <p>Prize Pool: ₹<?= number_format((float) $tournament['prize_pool'], 2) ?></p>
                                        <p>Start: <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></p>
                                        <a href="tournaments.php" class="btn-primary">Register</a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <p>No upcoming tournaments.</p>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="dashboard-section" id="tab-mytournaments">
                        <?php if ($myTournaments && mysqli_num_rows($myTournaments) > 0) { ?>
                            <?php while ($entry = mysqli_fetch_assoc($myTournaments)) { ?>
                                <div class="card-dark">
                                    <h3><?= htmlspecialchars($entry['name']) ?></h3>
                                    <p>Map: <?= htmlspecialchars($entry['map_name']) ?></p>
                                    <p>Status: <?= ucfirst($entry['result']) ?></p>
                                    <p>Winnings: ₹<?= number_format((float) $entry['winnings_amount'], 2) ?></p>
                                    <?php
                                    $roomOpenAt = $entry['room_open_at'];
                                    $now = new DateTime();
                                    if ($roomOpenAt && $now >= new DateTime($roomOpenAt)) {
                                        if (!empty($entry['room_id']) && !empty($entry['room_password'])) {
                                            echo "<p>Room ID: <strong>" . htmlspecialchars($entry['room_id']) . "</strong></p>";
                                            echo "<p>Room Password: <strong>" . htmlspecialchars($entry['room_password']) . "</strong></p>";
                                        } else {
                                            echo "<p>Room details will be shared shortly.</p>";
                                        }
                                    } elseif ($roomOpenAt) {
                                        echo "<p>Room details available at " . date('d M Y, h:i A', strtotime($roomOpenAt)) . ".</p>";
                                    } else {
                                        echo "<p>Room details pending. Admin will share them before the match.</p>";
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p>You have not registered for any tournaments yet.</p>
                        <?php } ?>
                    </div>

                    <div class="dashboard-section" id="tab-category">
                        <div class="card-dark">
                            <h3>Filter Tournaments</h3>
                            <label>Game</label>
                            <select id="gameFilter" class="form-input">
                                <option value="">All Games</option>
                                <?php if ($categoryGames) { while ($row = mysqli_fetch_assoc($categoryGames)) { ?>
                                    <option value="<?= htmlspecialchars($row['name']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php }} ?>
                            </select>
                            <label>Map</label>
                            <select id="mapFilter" class="form-input">
                                <option value="">All Maps</option>
                                <?php if ($categoryMaps) { while ($row = mysqli_fetch_assoc($categoryMaps)) { ?>
                                    <option value="<?= htmlspecialchars($row['map_name']) ?>" data-game="<?= htmlspecialchars($row['name']) ?>"><?= htmlspecialchars($row['map_name']) ?></option>
                                <?php }} ?>
                            </select>
                        </div>
                        <div class="grid" id="categoryResults">
                            <?php if ($upcoming && mysqli_num_rows($upcoming) > 0) { mysqli_data_seek($upcoming, 0); ?>
                                <?php while ($tournament = mysqli_fetch_assoc($upcoming)) { ?>
                                    <div class="card-dark category-item" data-game="<?= htmlspecialchars($tournament['name']) ?>" data-map="<?= htmlspecialchars($tournament['map_name']) ?>">
                                        <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                                        <p>Map: <?= htmlspecialchars($tournament['map_name']) ?></p>
                                        <p>Entry Fee: ₹<?= number_format((float) $tournament['entry_fee'], 2) ?></p>
                                        <p>Start: <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></p>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="dashboard-section" id="tab-wallet">
                        <div class="card-dark">
                            <h3>Add Money</h3>
                            <p>Available balance: ₹<?= number_format((float) $wallet['balance'], 2) ?></p>
                            <p><small>Added balance is not refundable. Only winnings can be withdrawn.</small></p>
                            <div>
                                <button type="button" class="btn-primary" onclick="startPayment(10)">₹10</button>
                                <button type="button" class="btn-primary" onclick="startPayment(20)">₹20</button>
                                <button type="button" class="btn-primary" onclick="startPayment(50)">₹50</button>
                                <button type="button" class="btn-primary" onclick="startPayment(100)">₹100</button>
                                <button type="button" class="btn-primary" onclick="startPayment(200)">₹200</button>
                                <button type="button" class="btn-primary" onclick="startPayment(500)">₹500</button>
                            </div>
                            <label>Custom Amount</label>
                            <input type="number" id="customAmount" class="form-input" min="1" placeholder="Enter amount">
                            <button type="button" class="btn-primary" onclick="startPayment(getCustomAmount())">Pay Custom Amount</button>
                        </div>
                        <div class="card-dark">
                            <h3>Redeem Code</h3>
                            <form method="post">
                                <input type="text" name="code" class="form-input" placeholder="Enter redeem code" required>
                                <button type="submit" name="redeem_code" class="btn-primary">Apply</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const tabs = document.querySelectorAll('.dashboard-tab');
        const sections = document.querySelectorAll('.dashboard-section');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
            });
        });

        function toggleProfile() {
            document.getElementById('profilePanel').classList.toggle('active');
        }

        function getCustomAmount() {
            const value = document.getElementById('customAmount').value;
            return parseFloat(value || 0);
        }

        async function startPayment(amount) {
            if (!amount || amount <= 0) {
                alert('Enter a valid amount.');
                return;
            }
            const response = await fetch('assets/php/user_payment_create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount, payment_type: 'topup' })
            });
            const data = await response.json();
            if (data.error) {
                alert(data.error);
                return;
            }

            const options = {
                key: data.key,
                amount: data.amount * 100,
                currency: 'INR',
                name: 'Wallet Top Up',
                order_id: data.order_id,
                handler: async function (response) {
                    const verifyResponse = await fetch('assets/php/user_payment_verify.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    });
                    const verifyPayload = await verifyResponse.json();
                    if (verifyPayload.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Payment verification failed.');
                    }
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        }

        const gameFilter = document.getElementById('gameFilter');
        const mapFilter = document.getElementById('mapFilter');
        const categoryItems = document.querySelectorAll('.category-item');

        function filterCategory() {
            const game = gameFilter.value;
            const map = mapFilter.value;
            categoryItems.forEach(item => {
                const matchesGame = !game || item.dataset.game === game;
                const matchesMap = !map || item.dataset.map === map;
                item.style.display = (matchesGame && matchesMap) ? 'block' : 'none';
            });
        }

        gameFilter.addEventListener('change', () => {
            const selectedGame = gameFilter.value;
            Array.from(mapFilter.options).forEach(option => {
                if (!option.dataset.game || option.dataset.game === selectedGame || selectedGame === '') {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            mapFilter.value = '';
            filterCategory();
        });
        mapFilter.addEventListener('change', filterCategory);
    </script>
</body>

</html>
