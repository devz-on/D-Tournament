<?php
require_once "assets/php/config.php";
require_once "assets/razorpay/Razorpay.php";

use Razorpay\Api\Api;

session_start();

if (!isset($_SESSION['team_label'])) {
    die("Invalid session");
}

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

$order = $api->order->create([
    'receipt' => uniqid('rcpt_'),
    'amount' => REGISTRATION_AMOUNT * 100, // paise
    'currency' => 'INR'
]);

$team_label = $_SESSION['team_label'];

mysqli_query($con, "
    INSERT INTO payments (team_label, razorpay_order_id, amount, status)
    VALUES ('$team_label', '{$order['id']}', '".REGISTRATION_AMOUNT."', 'created')
");

echo json_encode([
    'order_id' => $order['id'],
    'amount' => REGISTRATION_AMOUNT,
    'key' => RAZORPAY_KEY_ID
]);
