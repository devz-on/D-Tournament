<?php
include '../assets/php/config.php';
include '../assets/php/function.php';

session_start();

if (!isset($_SESSION['admin_auth'])) {
  header("location:login.php");
  exit;
}
if (isset($_GET['notification'])) {
  if ($_GET['notification'] == "readall") {
    seenNotification();
    header("location:index.php");
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

  <title>Aimgod eSports| Dashboard</title>
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
              <h1 class="m-0">
                Dashboard
              </h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
              </ol>
            </div>
          </div>
        </div>
      </div>


      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Small boxes (Stat box) -->


          <div class="row">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <?php
                  $select_users = "SELECT COUNT(*) AS totalusers FROM users";
                  $run_select_users = mysqli_query($con, $select_users);
                  $totalusers = 0;
                  if ($run_select_users) {
                    $row = mysqli_fetch_assoc($run_select_users);
                    $totalusers = $row['totalusers'];
                  }
                  ?>
                  <h3>
                    <?= $totalusers ?>
                  </h3>

                  <p>Total Users</p>
                </div>
                <div class="icon">
                  <i class="ion ion-stats-bars"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-danger">
                <div class="inner">
                  <?php
                  $select_revenue = "SELECT COALESCE(SUM(amount),0) AS total_revenue FROM user_payments WHERE status='paid'";
                  $run_select_revenue = mysqli_query($con, $select_revenue);
                  $totalRevenue = 0;
                  if ($run_select_revenue) {
                    $row = mysqli_fetch_assoc($run_select_revenue);
                    $totalRevenue = $row['total_revenue'];
                  }
                  ?>
                  <h3>
                    <?= number_format((float) $totalRevenue, 2) . '₹' ?>
                  </h3>
                  <p>Total Revenue</p>
                </div>

                <div class="icon">

                </div>

              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-warning">
                <div class="inner">
                  <?php
                  $select_active = "SELECT COUNT(*) AS active_users FROM users WHERE status='active'";
                  $run_select_active = mysqli_query($con, $select_active);
                  $activeUsers = 0;
                  if ($run_select_active) {
                    $row = mysqli_fetch_assoc($run_select_active);
                    $activeUsers = $row['active_users'];
                  }
                  ?>
                  <h3>
                    <?= $activeUsers ?>
                  </h3>
                  <p>Verified Users</p>
                </div>
                <div class="icon">
                  <i class="ion ion-person-add"></i>
                </div>

              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-success">
                <div class="inner">
                  <?php
                  $select_banned = "SELECT COUNT(*) AS banned_users FROM users WHERE status='banned'";
                  $run_banned = mysqli_query($con, $select_banned);
                  $bannedUsers = 0;
                  if ($run_banned) {
                    $row = mysqli_fetch_assoc($run_banned);
                    $bannedUsers = $row['banned_users'];
                  }
                  ?>
                  <h3>
                    <?= $bannedUsers ?>
                  </h3>
                  <p>Banned Users</p>
                </div>
                <div class="icon">
                  <i class="ion ion-pie-graph"></i>
                </div>
              </div>
            </div>
          </div>


          <div class="row">
            <div class="card w-100 " style="overflow: scroll;">
              <div class="card-header">
                <h3 class="card-title">Recent Users</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>User</th>
                      <th>Status</th>
                      <th>Wallet Balance</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $query = "SELECT u.*, w.balance FROM users u LEFT JOIN user_wallets w ON w.user_id = u.id ORDER BY u.created_at DESC LIMIT 20";
                    $result = mysqli_query($con, $query);
                    if (mysqli_num_rows($result) == 0) {
                      echo '<tr><td colspan="4">No Users Found</td></tr>';
                    } else {
                      $count = 1;
                      while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                          <td>
                            <?= $count++ . "." ?>
                          </td>
                          <td>
                            <strong><?= htmlspecialchars($row['username']) ?></strong><br>
                            <span class="text-muted"><?= htmlspecialchars($row['email']) ?></span>
                          </td>
                          <td><?= htmlspecialchars($row['status']) ?></td>
                          <td>₹<?= number_format((float) ($row['balance'] ?? 0), 2) ?></td>
                        </tr>
                        <?php
                      }
                    }
                    ?>
                  </tbody>
                </table>
              </div>

            </div>
      </section>
    </div>
    <?php include 'pages/footer.php' ?>

</body>

</html>
