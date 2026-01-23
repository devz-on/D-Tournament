<?php
include '../assets/php/config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("location:login.php");
    exit;
}

$response = "";

if (isset($_POST['create_tournament'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mapName = mysqli_real_escape_string($con, $_POST['map_name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $maxSeats = (int) $_POST['max_seats'];
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $startTime = mysqli_real_escape_string($con, $_POST['start_time']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $roomId = mysqli_real_escape_string($con, $_POST['room_id']);
    $roomPassword = mysqli_real_escape_string($con, $_POST['room_password']);
    $roomOpenAt = $_POST['room_open_at'] ? "'" . mysqli_real_escape_string($con, $_POST['room_open_at']) . "'" : "NULL";

    $roomIdValue = $roomId !== '' ? "'$roomId'" : "NULL";
    $roomPasswordValue = $roomPassword !== '' ? "'$roomPassword'" : "NULL";

    $query = "INSERT INTO tournaments (name, map_name, description, entry_fee, prize_pool, max_seats, start_time, status, room_id, room_password, room_open_at) 
              VALUES ('$name', '$mapName', '$description', $entryFee, $prizePool, $maxSeats, '$startTime', '$status', $roomIdValue, $roomPasswordValue, $roomOpenAt)";
    $query = "INSERT INTO tournaments (name, map_name, entry_fee, prize_pool, start_time, status, room_id, room_password, room_open_at) 
              VALUES ('$name', '$mapName', $entryFee, $prizePool, '$startTime', '$status', $roomIdValue, $roomPasswordValue, $roomOpenAt)";

    if (mysqli_query($con, $query)) {
        $response = "Tournament created.";
    } else {
        $response = "Failed to create tournament.";
    }
}

if (isset($_POST['update_tournament'])) {
    $tournamentId = (int) $_POST['tournament_id'];
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mapName = mysqli_real_escape_string($con, $_POST['map_name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $maxSeats = (int) $_POST['max_seats'];
    $entryFee = number_format((float) $_POST['entry_fee'], 2, '.', '');
    $prizePool = number_format((float) $_POST['prize_pool'], 2, '.', '');
    $startTime = mysqli_real_escape_string($con, $_POST['start_time']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $roomId = mysqli_real_escape_string($con, $_POST['room_id']);
    $roomPassword = mysqli_real_escape_string($con, $_POST['room_password']);
    $roomOpenAt = $_POST['room_open_at'] ? "'" . mysqli_real_escape_string($con, $_POST['room_open_at']) . "'" : "NULL";

    $roomIdValue = $roomId !== '' ? "'$roomId'" : "NULL";
    $roomPasswordValue = $roomPassword !== '' ? "'$roomPassword'" : "NULL";

    $query = "UPDATE tournaments SET 
                name='$name', 
                map_name='$mapName', 
                description='$description',
                entry_fee=$entryFee, 
                prize_pool=$prizePool, 
                max_seats=$maxSeats,
                entry_fee=$entryFee, 
                prize_pool=$prizePool, 
                start_time='$startTime', 
                status='$status', 
                room_id=$roomIdValue, 
                room_password=$roomPasswordValue, 
                room_open_at=$roomOpenAt
              WHERE id=$tournamentId";

    if (mysqli_query($con, $query)) {
        $response = "Tournament updated.";
    } else {
        $response = "Failed to update tournament.";
    }
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
                                            <label>Tournament Name</label>
                                            <input type="text" name="name" class="form-control" required value="<?= $editingTournament ? htmlspecialchars($editingTournament['name']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Map Name</label>
                                            <input type="text" name="map_name" class="form-control" required value="<?= $editingTournament ? htmlspecialchars($editingTournament['map_name']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?= $editingTournament ? htmlspecialchars($editingTournament['description']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
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
                                            <label>Max Seats</label>
                                            <input type="number" name="max_seats" min="0" step="1" class="form-control" required value="<?= $editingTournament ? htmlspecialchars($editingTournament['max_seats']) : '' ?>">
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
                                            <label>Status</label>
                                            <select name="status" class="form-control" required>
                                                <?php
                                                $statuses = ['draft', 'published', 'completed', 'archived'];
                                                foreach ($statuses as $status) {
                                                    $selected = ($editingTournament && $editingTournament['status'] === $status) ? 'selected' : '';
                                                    echo "<option value=\"$status\" $selected>" . ucfirst($status) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
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
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Room Visible At</label>
                                            <input type="datetime-local" name="room_open_at" class="form-control" value="<?= $editingTournament && $editingTournament['room_open_at'] ? date('Y-m-d\TH:i', strtotime($editingTournament['room_open_at'])) : '' ?>">
                                            <small class="text-muted">Set to 5 minutes before match for fair play.</small>
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
                                        <th>Name</th>
                                        <th>Map</th>
                                        <th>Seats</th>
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
                                            <tr>
                                                <td><?= htmlspecialchars($tournament['name']) ?></td>
                                                <td><?= htmlspecialchars($tournament['map_name']) ?></td>
                                                <td><?= $tournament['seats_filled'] ?> / <?= $tournament['max_seats'] ?></td>
                                                <td>₹<?= number_format((float) $tournament['entry_fee'], 2) ?></td>
                                                <td>₹<?= number_format((float) $tournament['prize_pool'], 2) ?></td>
                                                <td><?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></td>
                                                <td><?= ucfirst($tournament['status']) ?></td>
                                                <td>
                                                    <?php if (!empty($tournament['room_id'])) { ?>
                                                        ID: <?= htmlspecialchars($tournament['room_id']) ?><br>
                                                        Pass: <?= htmlspecialchars($tournament['room_password']) ?><br>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Pending</span>
                                                    <?php } ?>
                                                    <?php if (!empty($tournament['room_open_at'])) { ?>
                                                        <br><small>Visible at <?= date('d M Y, h:i A', strtotime($tournament['room_open_at'])) ?></small>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="tournaments.php?edit=<?= $tournament['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="8">No tournaments created yet.</td>
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
</body>

</html>
