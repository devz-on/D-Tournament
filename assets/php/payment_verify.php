<?php
require_once "assets/php/config.php";
require_once "assets/razorpay/Razorpay.php";

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

try {
    $attributes = [
        'razorpay_order_id' => $_POST['razorpay_order_id'],
        'razorpay_payment_id' => $_POST['razorpay_payment_id'],
        'razorpay_signature' => $_POST['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes);

    mysqli_query($con, "
        UPDATE payments SET
        razorpay_payment_id = '{$attributes['razorpay_payment_id']}',
        razorpay_signature = '{$attributes['razorpay_signature']}',
        status = 'paid'
        WHERE razorpay_order_id = '{$attributes['razorpay_order_id']}'
    ");

    echo "PAYMENT_SUCCESS";

} catch (SignatureVerificationError $e) {
    echo "PAYMENT_FAILED";
}
