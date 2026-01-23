<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

requireUserSession();
$user = getUserById($con, $_SESSION['user_id']);
if (!$user || $user['status'] !== 'active') {
    header("Location: register_payment.php");
    exit;
}

ensureUserWallet($con, $user['id']);
$wallet = getUserWallet($con, $user['id']);
$response = "";

if (isset($_POST['join_tournament'])) {
    $tournamentId = (int) $_POST['tournament_id'];
    $tournamentResult = mysqli_query($con, "SELECT * FROM tournaments WHERE id=$tournamentId AND status='published'");
    $tournament = $tournamentResult ? mysqli_fetch_assoc($tournamentResult) : null;
    if (!$tournament) {
        $response = "Tournament not available.";
    } elseif ((int) $tournament['max_seats'] > 0 && $tournament['seats_filled'] >= $tournament['max_seats']) {
        $response = "Tournament is full.";
    } else {
        $exists = mysqli_query($con, "SELECT id FROM user_tournament_entries WHERE tournament_id=$tournamentId AND user_id={$user['id']}");
        if ($exists && mysqli_num_rows($exists) > 0) {
            $response = "You are already registered for this tournament.";
        } else {
            $entryFee = (float) $tournament['entry_fee'];
            if ($wallet['balance'] < $entryFee) {
                $response = "Insufficient balance.";
            } else {
                $feeSafe = number_format($entryFee, 2, '.', '');
                mysqli_begin_transaction($con);
                $insertEntry = mysqli_query($con, "INSERT INTO user_tournament_entries (tournament_id, user_id, entry_fee) VALUES ($tournamentId, {$user['id']}, $feeSafe)");
                $updateBalance = mysqli_query($con, "UPDATE user_wallets SET balance = balance - $feeSafe WHERE user_id={$user['id']}");
                $updateSeats = mysqli_query($con, "UPDATE tournaments SET seats_filled = seats_filled + 1 WHERE id=$tournamentId");
                $logTransaction = mysqli_query($con, "INSERT INTO user_transactions (user_id, type, amount, source, note) VALUES ({$user['id']}, 'debit', $feeSafe, 'tournament', 'Entry fee')");
                if ($insertEntry && $updateBalance && $updateSeats && $logTransaction) {
                    mysqli_commit($con);
                    $wallet = getUserWallet($con, $user['id']);
                    $response = "Tournament registration successful.";
                } else {
                    mysqli_rollback($con);
                    $response = "Failed to register. Try again.";
                }
            }
        }
    }
}

$tournaments = mysqli_query($con, "SELECT * FROM tournaments WHERE status='published' ORDER BY start_time ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Upcoming Tournaments</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .tournament-card {
            background: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }

        .tournament-card button {
            background-color: hsl(31, 100%, 51%);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .tournament-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">Upcoming Tournaments</h2>
                    <?php if ($response) { ?>
                        <div class="tournament-card"><?= htmlspecialchars($response) ?></div>
                    <?php } ?>
                    <div class="tournament-grid">
                        <?php if ($tournaments && mysqli_num_rows($tournaments) > 0) { ?>
                            <?php while ($tournament = mysqli_fetch_assoc($tournaments)) { ?>
                                <div class="tournament-card">
                                    <h3><?= htmlspecialchars($tournament['name']) ?></h3>
                                    <p>Map: <?= htmlspecialchars($tournament['map_name']) ?></p>
                                    <p><?= nl2br(htmlspecialchars($tournament['description'])) ?></p>
                                    <p>Entry Fee: ₹<?= number_format((float) $tournament['entry_fee'], 2) ?></p>
                                    <p>Prize Pool: ₹<?= number_format((float) $tournament['prize_pool'], 2) ?></p>
                                    <?php if ((int) $tournament['max_seats'] > 0) { ?>
                                        <p>Seats: <?= $tournament['seats_filled'] ?> / <?= $tournament['max_seats'] ?></p>
                                        <p>Seats Left: <?= max(0, (int) $tournament['max_seats'] - (int) $tournament['seats_filled']) ?></p>
                                    <?php } else { ?>
                                        <p>Seats: Unlimited</p>
                                    <?php } ?>
                                    <p>Start: <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></p>
                                    <form method="post">
                                        <input type="hidden" name="tournament_id" value="<?= $tournament['id'] ?>">
                                        <button type="submit" name="join_tournament">Register</button>
                                    </form>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p>No tournaments available.</p>
                        <?php } ?>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
