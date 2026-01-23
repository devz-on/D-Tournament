<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

$leaders = mysqli_query($con, "SELECT u.username, w.winnings_balance FROM user_wallets w JOIN users u ON u.id = w.user_id WHERE u.status='active' ORDER BY w.winnings_balance DESC LIMIT 50");
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = getUserById($con, $_SESSION['user_id']);
}

$userRank = null;
if ($currentUser) {
    $rankResult = mysqli_query($con, "SELECT COUNT(*) AS rank FROM user_wallets w JOIN users u ON u.id = w.user_id WHERE w.winnings_balance > (SELECT winnings_balance FROM user_wallets WHERE user_id={$currentUser['id']})");
    $rankRow = $rankResult ? mysqli_fetch_assoc($rankResult) : null;
    $userRank = $rankRow ? ((int) $rankRow['rank'] + 1) : null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Leaderboard</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .leader-card {
            background: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">Leaderboard</h2>
                    <?php if ($currentUser && $userRank) { ?>
                        <div class="leader-card">
                            Your current rank: <strong>#<?= $userRank ?></strong>
                        </div>
                    <?php } ?>
                    <div class="leader-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Username</th>
                                    <th>Winning Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                if ($leaders && mysqli_num_rows($leaders) > 0) {
                                    while ($row = mysqli_fetch_assoc($leaders)) {
                                        echo "<tr>";
                                        echo "<td>#" . $rank++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>₹" . number_format((float) $row['winnings_balance'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo '<tr><td colspan="3">No ranking data yet.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
