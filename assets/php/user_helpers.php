<?php
function getUserById($con, $userId)
{
    $userId = (int) $userId;
    $result = mysqli_query($con, "SELECT * FROM users WHERE id=$userId");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function getUserByEmail($con, $email)
{
    $emailSafe = mysqli_real_escape_string($con, $email);
    $result = mysqli_query($con, "SELECT * FROM users WHERE email='$emailSafe'");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function ensureUserWallet($con, $userId)
{
    $userId = (int) $userId;
    $check = mysqli_query($con, "SELECT id FROM user_wallets WHERE user_id=$userId");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($con, "INSERT INTO user_wallets (user_id, balance, winnings_balance) VALUES ($userId, 0, 0)");
    }
}

function getUserWallet($con, $userId)
{
    $userId = (int) $userId;
    $result = mysqli_query($con, "SELECT * FROM user_wallets WHERE user_id=$userId");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return ['balance' => 0, 'winnings_balance' => 0];
}

function requireUserSession()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
