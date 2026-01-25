<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/user_helpers.php";

session_start();
header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$uid = mysqli_real_escape_string($con, $payload['uid'] ?? '');
$email = mysqli_real_escape_string($con, $payload['email'] ?? '');
$username = mysqli_real_escape_string($con, $payload['username'] ?? '');
$displayName = mysqli_real_escape_string($con, $payload['displayName'] ?? '');

if ($uid === '' || $email === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing Firebase data']);
    exit;
}

$result = mysqli_query($con, "SELECT * FROM users WHERE firebase_uid='$uid' OR email='$email' LIMIT 1");
$user = $result ? mysqli_fetch_assoc($result) : null;

if (!$user) {
    if ($username === '') {
        $username = $displayName !== '' ? $displayName : strstr($email, '@', true);
    }
    if (!preg_match('/^[A-Za-z0-9_]{4,}$/', $username)) {
        echo json_encode(['error' => 'Username must be at least 4 characters and contain only letters, numbers, or underscore.']);
        exit;
    }
    $checkUsername = mysqli_query($con, "SELECT id FROM users WHERE username='$username'");
    if ($checkUsername && mysqli_num_rows($checkUsername) > 0) {
        echo json_encode(['error' => 'Username is already taken.']);
        exit;
    }
    mysqli_query($con, "INSERT INTO users (username, email, firebase_uid, email_verified, status) VALUES ('$username', '$email', '$uid', 'verified', 'pending')");
    $userId = mysqli_insert_id($con);
    ensureUserWallet($con, $userId);
    mysqli_query($con, "INSERT INTO user_payments (user_id, amount, payment_type, status) VALUES ($userId, 50, 'registration', 'created')");
    $_SESSION['user_id'] = $userId;
    echo json_encode(['redirect' => 'register_payment.php']);
    exit;
}

if ($user['status'] === 'banned') {
    echo json_encode(['error' => 'Account banned']);
    exit;
}

if ($user['firebase_uid'] === null || $user['firebase_uid'] === '') {
    mysqli_query($con, "UPDATE users SET firebase_uid='$uid', email_verified='verified' WHERE id={$user['id']}");
}

$_SESSION['user_id'] = $user['id'];
if ($user['status'] === 'pending') {
    echo json_encode(['redirect' => 'register_payment.php']);
} else {
    echo json_encode(['redirect' => 'dashboard.php']);
}
