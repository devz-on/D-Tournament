<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/user_helpers.php";

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$amount = isset($payload['amount']) ? (float) $payload['amount'] : 0;
$paymentType = $payload['payment_type'] ?? 'topup';

if ($amount <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

$razorpayPath = __DIR__ . "/../razorpay/Razorpay.php";
if (!file_exists($razorpayPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Razorpay SDK missing. Place Razorpay.php in assets/razorpay.']);
    exit;
}

require_once $razorpayPath;

use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
$order = $api->order->create([
    'receipt' => uniqid('user_rcpt_'),
    'amount' => (int) round($amount * 100),
    'currency' => 'INR'
]);

$userId = (int) $_SESSION['user_id'];
$amountSafe = number_format($amount, 2, '.', '');
$paymentTypeSafe = $paymentType === 'registration' ? 'registration' : 'topup';

mysqli_query(
    $con,
    "INSERT INTO user_payments (user_id, amount, payment_type, razorpay_order_id, status) 
     VALUES ($userId, $amountSafe, '$paymentTypeSafe', '{$order['id']}', 'created')"
);

echo json_encode([
    'order_id' => $order['id'],
    'amount' => $amount,
    'key' => RAZORPAY_KEY_ID
]);
