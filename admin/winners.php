<?php
include '../assets/php/config.php';
include '../assets/php/user_helpers.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
  header("location:login.php");
  exit;
}

$response = "";

if (isset($_POST['update_entry'])) {
  $entryId = (int) $_POST['entry_id'];
  $result = mysqli_real_escape_string($con, $_POST['result']);
  $winnings = number_format((float) $_POST['winnings_amount'], 2, '.', '');

  $entryResult = mysqli_query($con, "SELECT * FROM user_tournament_entries WHERE id=$entryId");
  $entry = $entryResult ? mysqli_fetch_assoc($entryResult) : null;

  if ($entry) {
    $delta = (float) $winnings - (float) $entry['winnings_amount'];
    mysqli_begin_transaction($con);
    $updateEntry = mysqli_query($con, "UPDATE user_tournament_entries SET result='$result', winnings_amount=$winnings WHERE id=$entryId");
    $updateWallet = true;
    $logTransaction = true;
    if ($delta != 0) {
      $deltaSafe = number_format($delta, 2, '.', '');
      ensureUserWallet($con, $entry['user_id']);
      $updateWallet = mysqli_query($con, "UPDATE user_wallets SET winnings_balance = winnings_balance + $deltaSafe WHERE user_id={$entry['user_id']}");
      $logTransaction = mysqli_query($con, "INSERT INTO user_transactions (user_id, type, amount, source, note) VALUES ({$entry['user_id']}, 'winnings', $deltaSafe, 'admin', 'Tournament winnings update')");
    }

    if ($updateEntry && $updateWallet && $logTransaction) {
      mysqli_commit($con);
      $response = "Entry updated.";
    } else {
      mysqli_rollback($con);
      $response = "Failed to update entry.";
    }
  }
}

$tournaments = mysqli_query(
  $con,
  "SELECT * FROM tournaments WHERE status='ended' OR start_time <= DATE_SUB(NOW(), INTERVAL 45 MINUTE) ORDER BY start_time DESC"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Aimgod eSports| Winners</title>
  <?php include 'pages/header.php' ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__shake" src="../assets/favicon/safari-pinned-tab.svg" alt="AdminLTELogo" height="60"
        width="60">
    </div>

    <?php include 'pages/navbar.php' ?>


    <div class="content-wrapper">
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Winners</h1>
            </div>
          </div>
        </div>
      </div>

      <section class="content">
        <div class="container-fluid">
          <?php if ($response) { ?>
            <div class="alert alert-info"><?= $response ?></div>
          <?php } ?>

          <?php if ($tournaments && mysqli_num_rows($tournaments) > 0) { ?>
            <?php while ($tournament = mysqli_fetch_assoc($tournaments)) { ?>
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"><?= htmlspecialchars($tournament['name']) ?> - <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></h3>
                </div>
                <div class="card-body">
                  <?php
                  $totalPot = (float) $tournament['entry_fee'] * (int) $tournament['seats_filled'];
                  $firstPrize = $totalPot * 0.2;
                  $secondPrize = $totalPot * 0.1;
                  $thirdPrize = (float) $tournament['entry_fee'];
                  ?>
                  <p><strong>Suggested payouts:</strong> 1st ₹<?= number_format($firstPrize, 2) ?>, 2nd ₹<?= number_format($secondPrize, 2) ?>, 3rd ₹<?= number_format($thirdPrize, 2) ?></p>
                  <?php
                  $entries = mysqli_query(
                    $con,
                    "SELECT ute.*, u.username, u.email FROM user_tournament_entries ute JOIN users u ON u.id = ute.user_id WHERE ute.tournament_id={$tournament['id']} ORDER BY ute.winnings_amount DESC"
                  );
                  ?>
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>User</th>
                        <th>Result</th>
                        <th>Winnings</th>
                        <th>Update</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($entries && mysqli_num_rows($entries) > 0) { ?>
                        <?php while ($entry = mysqli_fetch_assoc($entries)) { ?>
                          <tr>
                            <td><?= htmlspecialchars($entry['username']) ?><br><small><?= htmlspecialchars($entry['email']) ?></small></td>
                            <td><?= ucfirst($entry['result']) ?></td>
                            <td>₹<?= number_format((float) $entry['winnings_amount'], 2) ?></td>
                            <td>
                              <form method="post" class="form-inline">
                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                <select name="result" class="form-control form-control-sm mr-2">
                                  <?php
                                  $results = ['pending', 'win', 'lose'];
                                  foreach ($results as $res) {
                                    $selected = $entry['result'] === $res ? 'selected' : '';
                                    echo "<option value=\"$res\" $selected>" . ucfirst($res) . "</option>";
                                  }
                                  ?>
                                </select>
                                <input type="number" step="1" min="0" name="winnings_amount" value="<?= htmlspecialchars($entry['winnings_amount']) ?>" class="form-control form-control-sm mr-2" style="width:120px;">
                                <button type="submit" name="update_entry" class="btn btn-sm btn-primary">Save</button>
                              </form>
                            </td>
                          </tr>
                        <?php } ?>
                      <?php } else { ?>
                        <tr>
                          <td colspan="4">No entries for this tournament.</td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php } ?>
          <?php } else { ?>
            <p>No completed tournaments yet.</p>
          <?php } ?>
        </div>
      </section>
    </div>
    <?php include 'pages/footer.php' ?>

</body>

</html>
