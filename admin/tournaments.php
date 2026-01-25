<?php
include '../assets/php/config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("location:login.php");
    exit;
}

$response = "";

$gameOptions = [
    'BGMI' => ['maps' => ['Erangel (Event)', 'Erangel (Classic)', 'Livik (Event)', 'Livik (Classic)']],
    'Valorant' => ['maps' => []],
    'Free Fire Max' => ['maps' => ['Bermuda']]
];

function getTournamentDescription($game, $map, $entryFee)
{
    $entryFeeText = number_format((float) $entryFee, 2);
    $baseRules = "Rules: No hacks, no third-party tools, and fair play required. Any cheating results in disqualification.";

    if ($game === 'BGMI') {
        return "BGMI tournament on $map. Entry fee ₹$entryFeeText. Teams compete for top 3 prizes. $baseRules";
    }
    if ($game === 'Valorant') {
        return "Valorant tournament. Map will be selected randomly before match start. Entry fee ₹$entryFeeText. $baseRules";
    }
    return "Free Fire Max tournament on $map. Entry fee ₹$entryFeeText. $baseRules";
}

function calculateMaxSeats($game, $mode)
{
    if ($game === 'Valorant') {
        return 10;
    }
    if ($mode === 'duo') {
        return 50;
    }
    if ($mode === 'squad') {
        return 25;
    }
    return 100;
}

if (isset($_POST['create_tournament'])) {
    $gameName = mysqli_real_escape_string($con, $_POST['game_name']);
    $mapName = mysqli_real_escape_string($con, $_POST['map_name']);
    $mode = mysqli_real_escape_string($con, $_POST['mode']);
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $startTime = mysqli_real_escape_string($con, $_POST['start_time']);
    $roomId = mysqli_real_escape_string($con, $_POST['room_id']);
    $roomPassword = mysqli_real_escape_string($con, $_POST['room_password']);

    $description = mysqli_real_escape_string($con, getTournamentDescription($gameName, $mapName, $entryFee));
    $maxSeats = calculateMaxSeats($gameName, $mode);
    $roomOpenAt = date('Y-m-d H:i:s', strtotime($startTime . ' -10 minutes'));
    $roomIdValue = $roomId !== '' ? "'$roomId'" : "NULL";
    $roomPasswordValue = $roomPassword !== '' ? "'$roomPassword'" : "NULL";

    $query = "INSERT INTO tournaments (name, map_name, description, mode, entry_fee, prize_pool, max_seats, start_time, status, room_id, room_password, room_open_at)
              VALUES ('$gameName', '$mapName', '$description', '$mode', $entryFee, $prizePool, $maxSeats, '$startTime', 'published', $roomIdValue, $roomPasswordValue, '$roomOpenAt')";

    if (mysqli_query($con, $query)) {
        $response = "Tournament created.";
    } else {
        $response = "Failed to create tournament.";
    }
}

if (isset($_POST['update_tournament'])) {
    $tournamentId = (int) $_POST['tournament_id'];
    $gameName = mysqli_real_escape_string($con, $_POST['game_name']);
    $mapName = mysqli_real_escape_string($con, $_POST['map_name']);
    $mode = mysqli_real_escape_string($con, $_POST['mode']);
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $startTime = mysqli_real_escape_string($con, $_POST['start_time']);
    $roomId = mysqli_real_escape_string($con, $_POST['room_id']);
    $roomPassword = mysqli_real_escape_string($con, $_POST['room_password']);

    $description = mysqli_real_escape_string($con, getTournamentDescription($gameName, $mapName, $entryFee));
    $maxSeats = calculateMaxSeats($gameName, $mode);
    $roomOpenAt = date('Y-m-d H:i:s', strtotime($startTime . ' -10 minutes'));
    $roomIdValue = $roomId !== '' ? "'$roomId'" : "NULL";
    $roomPasswordValue = $roomPassword !== '' ? "'$roomPassword'" : "NULL";

    $query = "UPDATE tournaments SET 
                name='$gameName', 
                map_name='$mapName', 
                description='$description',
                mode='$mode',
                entry_fee=$entryFee, 
                prize_pool=$prizePool, 
                max_seats=$maxSeats,
                start_time='$startTime', 
                room_id=$roomIdValue, 
                room_password=$roomPasswordValue, 
                room_open_at='$roomOpenAt'
              WHERE id=$tournamentId";

    if (mysqli_query($con, $query)) {
        $response = "Tournament updated.";
    } else {
        $response = "Failed to update tournament.";
    }
}

if (isset($_POST['end_tournament'])) {
    $tournamentId = (int) $_POST['tournament_id'];
    mysqli_query($con, "UPDATE tournaments SET status='ended' WHERE id=$tournamentId");
    $response = "Tournament marked as ended.";
}

$editingTournament = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editResult = mysqli_query($con, "SELECT * FROM tournaments WHERE id=$editId");
    if ($editResult && mysqli_num_rows($editResult) > 0) {
        $editingTournament = mysqli_fetch_assoc($editResult);
    }
}

$tournaments = mysqli_query($con, "SELECT * FROM tournaments ORDER BY start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Aimgod eSports | Tournaments</title>
    <?php include 'pages/header.php' ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include 'pages/navbar.php' ?>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Tournament Manager</h1>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <?php if ($response) { ?>
                        <div class="alert alert-info">
                            <?= $response ?>
                        </div>
                    <?php } ?>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $editingTournament ? 'Edit Tournament' : 'Create Tournament'; ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Game</label>
                                            <select name="game_name" class="form-control" required>
                                                <?php foreach (array_keys($gameOptions) as $game) {
                                                    $selected = ($editingTournament && $editingTournament['name'] === $game) ? 'selected' : '';
                                                    echo "<option value=\"$game\" $selected>$game</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Map</label>
                                            <select name="map_name" class="form-control" required>
                                                <?php
                                                $selectedGame = $editingTournament['name'] ?? 'BGMI';
                                                $maps = $gameOptions[$selectedGame]['maps'];
                                                if (empty($maps)) {
                                                    echo '<option value="Random">Random</option>';
                                                } else {
                                                    foreach ($maps as $map) {
                                                        $selected = ($editingTournament && $editingTournament['map_name'] === $map) ? 'selected' : '';
                                                        echo "<option value=\"$map\" $selected>$map</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mode</label>
                                            <select name="mode" class="form-control" required>
                                                <?php
                                                $modes = ['solo', 'duo', 'squad', 'team'];
                                                foreach ($modes as $mode) {
                                                    $selected = ($editingTournament && $editingTournament['mode'] === $mode) ? 'selected' : '';
                                                    $label = $mode === 'team' ? 'Team (5v5)' : ucfirst($mode);
                                                    echo "<option value=\"$mode\" $selected>$label</option>";
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted">Valorant is fixed to 10 players.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Entry Fee (INR)</label>
                                            <input type="number" name="entry_fee" min="0" step="1" class="form-control" required value="<?= $editingTournament ? htmlspecialchars($editingTournament['entry_fee']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Prize Pool (INR)</label>
                                            <input type="number" name="prize_pool" min="0" step="1" class="form-control" required value="<?= $editingTournament ? htmlspecialchars($editingTournament['prize_pool']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Start Time</label>
                                            <input type="datetime-local" name="start_time" class="form-control" required value="<?= $editingTournament ? date('Y-m-d\TH:i', strtotime($editingTournament['start_time'])) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Room ID</label>
                                            <input type="text" name="room_id" class="form-control" value="<?= $editingTournament ? htmlspecialchars($editingTournament['room_id']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Room Password</label>
                                            <input type="text" name="room_password" class="form-control" value="<?= $editingTournament ? htmlspecialchars($editingTournament['room_password']) : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <?php if ($editingTournament) { ?>
                                    <input type="hidden" name="tournament_id" value="<?= $editingTournament['id'] ?>">
                                    <button type="submit" name="update_tournament" class="btn btn-primary">Update Tournament</button>
                                    <a href="tournaments.php" class="btn btn-secondary">Cancel</a>
                                <?php } else { ?>
                                    <button type="submit" name="create_tournament" class="btn btn-success">Create Tournament</button>
                                <?php } ?>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Tournaments</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Game</th>
                                        <th>Map</th>
                                        <th>Mode</th>
                                        <th>Entry Fee</th>
                                        <th>Prize Pool</th>
                                        <th>Start Time</th>
                                        <th>Status</th>
                                        <th>Room Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($tournaments && mysqli_num_rows($tournaments) > 0) { ?>
                                        <?php while ($tournament = mysqli_fetch_assoc($tournaments)) { ?>
                                            <?php
                                            $status = $tournament['status'] === 'ended' ? 'Ended' : 'Ongoing';
                                            $endTime = strtotime($tournament['start_time'] . ' +45 minutes');
                                            if ($tournament['status'] !== 'ended' && time() > $endTime) {
                                                $status = 'Ended';
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tournament['name']) ?></td>
                                                <td><?= htmlspecialchars($tournament['map_name']) ?></td>
                                                <td><?= ucfirst($tournament['mode']) ?></td>
                                                <td>₹<?= number_format((float) $tournament['entry_fee'], 2) ?></td>
                                                <td>₹<?= number_format((float) $tournament['prize_pool'], 2) ?></td>
                                                <td><?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></td>
                                                <td><?= $status ?></td>
                                                <td>
                                                    <?php if (!empty($tournament['room_id'])) { ?>
                                                        ID: <?= htmlspecialchars($tournament['room_id']) ?><br>
                                                        Pass: <?= htmlspecialchars($tournament['room_password']) ?><br>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Pending</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="tournaments.php?edit=<?= $tournament['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="tournament_id" value="<?= $tournament['id'] ?>">
                                                        <button type="submit" name="end_tournament" class="btn btn-sm btn-warning">End</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="9">No tournaments created yet.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include 'pages/footer.php' ?>
    </div>
    <script>
        const gameOptions = <?= json_encode($gameOptions) ?>;
        const gameSelect = document.querySelector('select[name=\"game_name\"]');
        const mapSelect = document.querySelector('select[name=\"map_name\"]');
        const modeSelect = document.querySelector('select[name=\"mode\"]');
        const currentMap = <?= json_encode($editingTournament['map_name'] ?? '') ?>;
        const currentMode = <?= json_encode($editingTournament['mode'] ?? '') ?>;

        function updateMapOptions() {
            const game = gameSelect.value;
            const maps = gameOptions[game]?.maps || [];
            mapSelect.innerHTML = '';
            if (maps.length === 0) {
                const option = document.createElement('option');
                option.value = 'Random';
                option.textContent = 'Random';
                mapSelect.appendChild(option);
            } else {
                maps.forEach(map => {
                    const option = document.createElement('option');
                    option.value = map;
                    option.textContent = map;
                    mapSelect.appendChild(option);
                });
            }
            if (currentMap) {
                mapSelect.value = currentMap;
            }
        }

        function updateModeOptions() {
            const game = gameSelect.value;
            const options = Array.from(modeSelect.options);
            options.forEach(option => option.disabled = false);
            if (game === 'Valorant') {
                options.forEach(option => option.disabled = option.value !== 'team');
                modeSelect.value = 'team';
            } else {
                options.forEach(option => option.disabled = option.value === 'team');
                if (currentMode && currentMode !== 'team') {
                    modeSelect.value = currentMode;
                } else if (modeSelect.value === 'team') {
                    modeSelect.value = 'solo';
                }
            }
        }

        if (gameSelect && mapSelect && modeSelect) {
            gameSelect.addEventListener('change', () => {
                updateMapOptions();
                updateModeOptions();
            });
            updateMapOptions();
            updateModeOptions();
        }
    </script>
</body>

</html>
