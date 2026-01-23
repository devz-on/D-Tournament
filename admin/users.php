<?php
include '../assets/php/config.php';
include '../assets/php/user_helpers.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("location:login.php");
    exit;
}

$response = "";

if (isset($_POST['ban_user'])) {
    $userId = (int) $_POST['user_id'];
    mysqli_query($con, "UPDATE users SET status='banned' WHERE id=$userId");
    $response = "User banned.";
}

if (isset($_POST['unban_user'])) {
    $userId = (int) $_POST['user_id'];
    mysqli_query($con, "UPDATE users SET status='active' WHERE id=$userId");
    $response = "User unbanned.";
}

if (isset($_POST['add_balance'])) {
    $userId = (int) $_POST['user_id'];
    $amount = (float) $_POST['amount'];
    if ($amount > 0) {
        $amountSafe = number_format($amount, 2, '.', '');
        ensureUserWallet($con, $userId);
        mysqli_begin_transaction($con);
        $updateWallet = mysqli_query($con, "UPDATE user_wallets SET balance = balance + $amountSafe WHERE user_id=$userId");
        $logTransaction = mysqli_query($con, "INSERT INTO user_transactions (user_id, type, amount, source, note) VALUES ($userId, 'credit', $amountSafe, 'admin', 'Admin added balance')");
        if ($updateWallet && $logTransaction) {
            mysqli_commit($con);
            $response = "Balance added.";
        } else {
            mysqli_rollback($con);
            $response = "Failed to add balance.";
        }
    } else {
        $response = "Enter a valid amount.";
    }
}

if (isset($_POST['create_redeem'])) {
    $code = strtoupper(mysqli_real_escape_string($con, $_POST['code']));
    $amount = (float) $_POST['amount'];
    $maxUses = (int) $_POST['max_uses'];
    if ($code && $amount > 0 && $maxUses > 0) {
        $amountSafe = number_format($amount, 2, '.', '');
        mysqli_query($con, "INSERT INTO redeem_codes (code, amount, max_uses, status) VALUES ('$code', $amountSafe, $maxUses, 'active')");
        $response = "Redeem code created.";
    } else {
        $response = "Provide code, amount, and max uses.";
    }
}

$users = mysqli_query($con, "SELECT u.*, w.balance, w.winnings_balance FROM users u LEFT JOIN user_wallets w ON w.user_id = u.id ORDER BY u.created_at DESC");
$redeemCodes = mysqli_query($con, "SELECT * FROM redeem_codes ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Aimgod eSports | Users</title>
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
                            <h1 class="m-0">User Management</h1>
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
                            <h3 class="card-title">Create Redeem Code</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" class="row">
                                <div class="col-md-3">
                                    <input type="text" name="code" class="form-control" placeholder="CODE123" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="amount" min="1" class="form-control" placeholder="Amount" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="max_uses" min="1" class="form-control" placeholder="Max Uses" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" name="create_redeem" class="btn btn-success">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Users</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Balance</th>
                                        <th>Winnings</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users && mysqli_num_rows($users) > 0) { ?>
                                        <?php while ($row = mysqli_fetch_assoc($users)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['username']) ?></td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td><?= htmlspecialchars($row['status']) ?></td>
                                                <td>₹<?= number_format((float) ($row['balance'] ?? 0), 2) ?></td>
                                                <td>₹<?= number_format((float) ($row['winnings_balance'] ?? 0), 2) ?></td>
                                                <td>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                        <?php if ($row['status'] !== 'banned') { ?>
                                                            <button type="submit" name="ban_user" class="btn btn-danger btn-sm">Ban</button>
                                                        <?php } else { ?>
                                                            <button type="submit" name="unban_user" class="btn btn-warning btn-sm">Unban</button>
                                                        <?php } ?>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                        <input type="number" name="amount" min="1" step="1" placeholder="Amount" style="width:90px;" required>
                                                        <button type="submit" name="add_balance" class="btn btn-info btn-sm">Add</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="6">No users found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Redeem Codes</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Amount</th>
                                        <th>Uses</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($redeemCodes && mysqli_num_rows($redeemCodes) > 0) { ?>
                                        <?php while ($code = mysqli_fetch_assoc($redeemCodes)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($code['code']) ?></td>
                                                <td>₹<?= number_format((float) $code['amount'], 2) ?></td>
                                                <td><?= $code['uses_count'] ?> / <?= $code['max_uses'] ?></td>
                                                <td><?= htmlspecialchars($code['status']) ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4">No redeem codes created.</td>
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
