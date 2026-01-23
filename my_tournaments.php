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

$entries = mysqli_query(
    $con,
    "SELECT t.*, ute.result, ute.winnings_amount, ute.entry_fee, ute.created_at AS joined_at
     FROM user_tournament_entries ute
     JOIN tournaments t ON t.id = ute.tournament_id
     WHERE ute.user_id={$user['id']}
     ORDER BY t.start_time DESC"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Tournaments</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .entry-card {
            background: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">My Tournaments</h2>
                    <?php if ($entries && mysqli_num_rows($entries) > 0) { ?>
                        <?php while ($entry = mysqli_fetch_assoc($entries)) { ?>
                            <div class="entry-card">
                                <h3><?= htmlspecialchars($entry['name']) ?></h3>
                                <p>Map: <?= htmlspecialchars($entry['map_name']) ?></p>
                                <p>Entry Fee: ₹<?= number_format((float) $entry['entry_fee'], 2) ?></p>
                                <p>Prize Pool: ₹<?= number_format((float) $entry['prize_pool'], 2) ?></p>
                                <p>Start: <?= date('d M Y, h:i A', strtotime($entry['start_time'])) ?></p>
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
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
