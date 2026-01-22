<?php
include "assets/php/config.php";
include "assets/php/function.php";
session_start();

$response = "";

function getTeamByLabel($con, $teamLabel)
{
    $teamLabelSafe = mysqli_real_escape_string($con, $teamLabel);
    $result = mysqli_query($con, "SELECT * FROM teams WHERE team_label='$teamLabelSafe'");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function ensureWallet($con, $teamId)
{
    $check = mysqli_query($con, "SELECT id FROM team_wallets WHERE team_id=$teamId");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($con, "INSERT INTO team_wallets (team_id, balance, winnings_balance) VALUES ($teamId, 0, 0)");
    }
}

function getWallet($con, $teamId)
{
    $result = mysqli_query($con, "SELECT * FROM team_wallets WHERE team_id=$teamId");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return ['balance' => 0, 'winnings_balance' => 0];
}

if (isset($_POST['login_team'])) {
    $teamCode = mysqli_real_escape_string($con, $_POST['team_code']);
    $team = getTeamByLabel($con, $teamCode);
    if ($team) {
        setcookie("team-code", $teamCode, time() + (86400 * 30), "/");
        header("Location: dashboard.php");
        exit;
    }
    $response = "Invalid team code.";
}

if (isset($_GET['logout'])) {
    setcookie("team-code", "", time() - 3600, "/");
    header("Location: dashboard.php");
    exit;
}

$team = null;
$wallet = null;
if (isset($_COOKIE['team-code'])) {
    $team = getTeamByLabel($con, $_COOKIE['team-code']);
    if ($team) {
        ensureWallet($con, (int) $team['id']);
        $wallet = getWallet($con, (int) $team['id']);
    }
}

if ($team && isset($_POST['add_funds'])) {
    $amount = (float) $_POST['amount'];
    if ($amount <= 0) {
        $response = "Amount must be greater than zero.";
    } else {
        $teamId = (int) $team['id'];
        $amountSafe = number_format($amount, 2, '.', '');
        mysqli_begin_transaction($con);
        $updateBalance = mysqli_query($con, "UPDATE team_wallets SET balance = balance + $amountSafe WHERE team_id=$teamId");
        $logTransaction = mysqli_query($con, "INSERT INTO wallet_transactions (team_id, type, amount, source, note) VALUES ($teamId, 'credit', $amountSafe, 'wallet', 'User added funds')");
        if ($updateBalance && $logTransaction) {
            mysqli_commit($con);
            $wallet = getWallet($con, $teamId);
            $response = "Balance updated.";
        } else {
            mysqli_rollback($con);
            $response = "Failed to update balance.";
        }
    }
}

if ($team && isset($_POST['join_tournament'])) {
    $tournamentId = (int) $_POST['tournament_id'];
    $teamId = (int) $team['id'];
    $tournamentResult = mysqli_query($con, "SELECT * FROM tournaments WHERE id=$tournamentId AND status='published'");
    $tournament = $tournamentResult ? mysqli_fetch_assoc($tournamentResult) : null;
    if (!$tournament) {
        $response = "Tournament not available.";
    } else {
        $alreadyJoined = mysqli_query($con, "SELECT id FROM tournament_entries WHERE tournament_id=$tournamentId AND team_id=$teamId");
        if ($alreadyJoined && mysqli_num_rows($alreadyJoined) > 0) {
            $response = "You already joined this tournament.";
        } else {
            $entryFee = (float) $tournament['entry_fee'];
            if ($wallet['balance'] < $entryFee) {
                $response = "Insufficient balance to join.";
            } else {
                $feeSafe = number_format($entryFee, 2, '.', '');
                mysqli_begin_transaction($con);
                $insertEntry = mysqli_query($con, "INSERT INTO tournament_entries (tournament_id, team_id, entry_fee) VALUES ($tournamentId, $teamId, $feeSafe)");
                $updateBalance = mysqli_query($con, "UPDATE team_wallets SET balance = balance - $feeSafe WHERE team_id=$teamId");
                $logTransaction = mysqli_query($con, "INSERT INTO wallet_transactions (team_id, type, amount, source, note) VALUES ($teamId, 'debit', $feeSafe, 'tournament', 'Entry fee')");
                if ($insertEntry && $updateBalance && $logTransaction) {
                    mysqli_commit($con);
                    $wallet = getWallet($con, $teamId);
                    $response = "Joined tournament successfully.";
                } else {
                    mysqli_rollback($con);
                    $response = "Failed to join tournament.";
                }
            }
        }
    }
}

$upcomingTournaments = mysqli_query($con, "SELECT * FROM tournaments WHERE status='published' ORDER BY start_time ASC");

$joinedTournaments = null;
if ($team) {
    $teamId = (int) $team['id'];
    $joinedTournaments = mysqli_query(
        $con,
        "SELECT te.id AS entry_id, t.* FROM tournament_entries te JOIN tournaments t ON t.id = te.tournament_id WHERE te.team_id=$teamId ORDER BY t.start_time ASC"
    );
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Aimgod eSports - Dashboard</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .dashboard-container {
            margin-top: 40px;
        }

        .dashboard-card {
            background-color: hsl(231, 12%, 12%);
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            margin-bottom: 20px;
        }

        .dashboard-card h3 {
            margin-top: 0;
        }

        .dashboard-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-column {
            flex: 1 1 320px;
        }

        .dashboard-form input,
        .dashboard-form select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 12px;
        }

        .dashboard-form button {
            background-color: hsl(31, 100%, 51%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            cursor: pointer;
        }

        .tournament-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tournament-list li {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 0;
        }

        .room-details {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .alert-message {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper dashboard-container" id="dashboard">
                <div class="container">
                    <h2 class="h2 section-title">User Dashboard</h2>

                    <?php if ($response) { ?>
                        <div class="alert-message">
                            <?= $response ?>
                        </div>
                    <?php } ?>

                    <?php if (!$team) { ?>
                        <div class="dashboard-card">
                            <h3>Access your team dashboard</h3>
                            <form class="dashboard-form" method="post">
                                <input type="text" name="team_code" placeholder="Enter your team code" required>
                                <button type="submit" name="login_team">Open Dashboard</button>
                            </form>
                        </div>
                    <?php } else { ?>
                        <div class="dashboard-row">
                            <div class="dashboard-column">
                                <div class="dashboard-card">
                                    <h3>Welcome, <?= htmlspecialchars($team['team_name']) ?></h3>
                                    <p>Team Code: <strong><?= htmlspecialchars($team['team_label']) ?></strong></p>
                                    <p>Available Balance: <strong>₹<?= number_format((float) $wallet['balance'], 2) ?></strong></p>
                                    <p>Winnings Balance: <strong>₹<?= number_format((float) $wallet['winnings_balance'], 2) ?></strong></p>
                                    <p><small>Added balance is non-refundable. Winnings balance can be withdrawn by contacting admin.</small></p>
                                    <a href="dashboard.php?logout=true" class="btn-sign_in">Switch Team</a>
                                </div>

                                <div class="dashboard-card">
                                    <h3>Add Money</h3>
                                    <form class="dashboard-form" method="post">
                                        <input type="number" name="amount" min="1" step="1" placeholder="Amount in INR" required>
                                        <button type="submit" name="add_funds">Add Balance</button>
                                    </form>
                                </div>
                            </div>

                            <div class="dashboard-column">
                                <div class="dashboard-card">
                                    <h3>Upcoming Tournaments</h3>
                                    <ul class="tournament-list">
                                        <?php if ($upcomingTournaments && mysqli_num_rows($upcomingTournaments) > 0) { ?>
                                            <?php while ($tournament = mysqli_fetch_assoc($upcomingTournaments)) { ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($tournament['name']) ?></strong><br>
                                                    Map: <?= htmlspecialchars($tournament['map_name']) ?><br>
                                                    Entry Fee: ₹<?= number_format((float) $tournament['entry_fee'], 2) ?> | Prize Pool: ₹<?= number_format((float) $tournament['prize_pool'], 2) ?><br>
                                                    Start: <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?>
                                                    <form method="post" style="margin-top: 8px;">
                                                        <input type="hidden" name="tournament_id" value="<?= $tournament['id'] ?>">
                                                        <button type="submit" name="join_tournament">Join Tournament</button>
                                                    </form>
                                                </li>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <li>No tournaments published yet.</li>
                                        <?php } ?>
                                    </ul>
                                </div>

                                <div class="dashboard-card">
                                    <h3>Your Joined Tournaments</h3>
                                    <ul class="tournament-list">
                                        <?php if ($joinedTournaments && mysqli_num_rows($joinedTournaments) > 0) { ?>
                                            <?php while ($joined = mysqli_fetch_assoc($joinedTournaments)) { ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($joined['name']) ?></strong><br>
                                                    Start: <?= date('d M Y, h:i A', strtotime($joined['start_time'])) ?>
                                                    <div class="room-details">
                                                        <?php
                                                        $roomOpenAt = $joined['room_open_at'];
                                                        $now = new DateTime();
                                                        if ($roomOpenAt && $now >= new DateTime($roomOpenAt)) {
                                                            if (!empty($joined['room_id']) && !empty($joined['room_password'])) {
                                                                echo "Room ID: <strong>" . htmlspecialchars($joined['room_id']) . "</strong><br>";
                                                                echo "Room Password: <strong>" . htmlspecialchars($joined['room_password']) . "</strong>";
                                                            } else {
                                                                echo "Room details will be shared shortly.";
                                                            }
                                                        } elseif ($roomOpenAt) {
                                                            echo "Room details available at " . date('d M Y, h:i A', strtotime($roomOpenAt)) . ".";
                                                        } else {
                                                            echo "Room details pending. Admin will share them before the match.";
                                                        }
                                                        ?>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <li>You have not joined any tournaments yet.</li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
