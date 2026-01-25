<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/user_helpers.php";

session_start();
header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);

$razorpayPath = __DIR__ . "/../razorpay/Razorpay.php";
if (!file_exists($razorpayPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'failed', 'error' => 'Razorpay SDK missing.']);
    exit;
}

require_once $razorpayPath;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

try {
    $attributes = [
        'razorpay_order_id' => $payload['razorpay_order_id'] ?? '',
        'razorpay_payment_id' => $payload['razorpay_payment_id'] ?? '',
        'razorpay_signature' => $payload['razorpay_signature'] ?? ''
    ];

    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $api->utility->verifyPaymentSignature($attributes);

    $orderId = mysqli_real_escape_string($con, $attributes['razorpay_order_id']);
    $paymentResult = mysqli_query($con, "SELECT * FROM user_payments WHERE razorpay_order_id='$orderId' LIMIT 1");
    $payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
    if (!$payment) {
        echo json_encode(['status' => 'failed', 'error' => 'Payment not found.']);
        exit;
    }
    if ($payment['status'] === 'paid') {
        echo json_encode(['status' => 'success']);
        exit;
    }

    $userId = (int) $payment['user_id'];
    $amount = number_format((float) $payment['amount'], 2, '.', '');

    mysqli_begin_transaction($con);
    $updatePayment = mysqli_query(
        $con,
        "UPDATE user_payments SET razorpay_payment_id='{$attributes['razorpay_payment_id']}', razorpay_signature='{$attributes['razorpay_signature']}', status='paid' WHERE id={$payment['id']}"
    );

    ensureUserWallet($con, $userId);
    $updateWallet = mysqli_query($con, "UPDATE user_wallets SET balance = balance + $amount WHERE user_id=$userId");
    $logTransaction = mysqli_query($con, "INSERT INTO user_transactions (user_id, type, amount, source, note) VALUES ($userId, 'credit', $amount, 'razorpay', '{$payment['payment_type']} payment')");

    if ($payment['payment_type'] === 'registration') {
        $activateUser = mysqli_query($con, "UPDATE users SET status=IF(email_verified='verified','active','pending') WHERE id=$userId");
    } else {
        $activateUser = true;
    }

    if ($updatePayment && $updateWallet && $logTransaction && $activateUser) {
        mysqli_commit($con);
        echo json_encode(['status' => 'success']);
    } else {
        mysqli_rollback($con);
        echo json_encode(['status' => 'failed', 'error' => 'Failed to update payment.']);
    }
} catch (SignatureVerificationError $e) {
    echo json_encode(['status' => 'failed', 'error' => 'Signature verification failed.']);
}
