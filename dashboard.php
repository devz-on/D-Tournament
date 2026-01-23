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

$upcoming = mysqli_query($con, "SELECT * FROM tournaments WHERE status='published' ORDER BY start_time ASC LIMIT 5");
$myTournaments = mysqli_query(
    $con,
    "SELECT t.*, ute.result, ute.winnings_amount FROM user_tournament_entries ute JOIN tournaments t ON t.id = ute.tournament_id WHERE ute.user_id={$user['id']} ORDER BY t.start_time DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>User Dashboard</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .dashboard-card {
            background: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }

        .dashboard-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-column {
            flex: 1 1 320px;
        }

        .dashboard-card button {
            background-color: hsl(31, 100%, 51%);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .dashboard-card input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">Welcome, <?= htmlspecialchars($user['username']) ?></h2>
                    <p><a href="logout.php">Logout</a></p>

                    <?php if ($response) { ?>
                        <div class="dashboard-card">
                            <?= htmlspecialchars($response) ?>
                        </div>
                    <?php } ?>

                    <div class="dashboard-row">
                        <div class="dashboard-column">
                            <div class="dashboard-card">
                                <h3>Wallet</h3>
                                <p>Available balance: ₹<?= number_format((float) $wallet['balance'], 2) ?></p>
                                <p>Winnings balance: ₹<?= number_format((float) $wallet['winnings_balance'], 2) ?></p>
                                <p><small>Added balance is non-refundable. Winnings can be withdrawn via admin.</small></p>
                                <a href="add_funds.php" class="btn-sign_in">Add Money</a>
                            </div>
                            <div class="dashboard-card">
                                <h3>Redeem Code</h3>
                                <form method="post">
                                    <input type="text" name="code" placeholder="Enter redeem code" required>
                                    <button type="submit" name="redeem_code">Apply</button>
                                </form>
                            </div>
                        </div>
                        <div class="dashboard-column">
                            <div class="dashboard-card">
                                <h3>Upcoming Tournaments</h3>
                                <?php if ($upcoming && mysqli_num_rows($upcoming) > 0) { ?>
                                    <ul>
                                        <?php while ($tournament = mysqli_fetch_assoc($upcoming)) { ?>
                                            <li><?= htmlspecialchars($tournament['name']) ?> - <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></li>
                                        <?php } ?>
                                    </ul>
                                    <a href="tournaments.php">View all tournaments</a>
                                <?php } else { ?>
                                    <p>No upcoming tournaments.</p>
                                <?php } ?>
                            </div>
                            <div class="dashboard-card">
                                <h3>My Latest Tournaments</h3>
                                <?php if ($myTournaments && mysqli_num_rows($myTournaments) > 0) { ?>
                                    <ul>
                                        <?php while ($entry = mysqli_fetch_assoc($myTournaments)) { ?>
                                            <li><?= htmlspecialchars($entry['name']) ?> - <?= ucfirst($entry['result']) ?> (₹<?= number_format((float) $entry['winnings_amount'], 2) ?>)</li>
                                        <?php } ?>
                                    </ul>
                                    <a href="my_tournaments.php">View all</a>
                                <?php } else { ?>
                                    <p>You have not joined any tournaments yet.</p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
