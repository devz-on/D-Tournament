<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

requireUserSession();
$user = getUserById($con, $_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($user['email_verified'] !== 'verified') {
    header("Location: verify_email.php");
    exit;
}

if ($user['status'] === 'active') {
    header("Location: dashboard.php");
    exit;
}

$payment = mysqli_query($con, "SELECT * FROM user_payments WHERE user_id={$user['id']} AND payment_type='registration' ORDER BY id DESC LIMIT 1");
$paymentRow = $payment ? mysqli_fetch_assoc($payment) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Complete Registration Payment</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .payment-card {
            background: hsl(231, 12%, 12%);
            padding: 30px;
            border-radius: 12px;
            color: #fff;
            max-width: 560px;
            margin: 0 auto;
            text-align: center;
        }

        .payment-card button {
            background-color: hsl(31, 100%, 51%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">Complete Registration</h2>
                    <div class="payment-card">
                        <p>Registration fee: <strong>₹50</strong></p>
                        <p>The registration fee is added to your wallet balance after payment.</p>
                        <button id="payNow">Pay ₹50</button>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('payNow').addEventListener('click', async () => {
            const response = await fetch('assets/php/user_payment_create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: 50, payment_type: 'registration' })
            });
            const data = await response.json();
            if (data.error) {
                alert(data.error);
                return;
            }

            const options = {
                key: data.key,
                amount: data.amount * 100,
                currency: 'INR',
                name: 'Tournament Registration',
                order_id: data.order_id,
                handler: async function (response) {
                    const verifyResponse = await fetch('assets/php/user_payment_verify.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    });
                    const verifyPayload = await verifyResponse.json();
                    if (verifyPayload.status === 'success') {
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('Payment verification failed.');
                    }
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        });
    </script>
</body>

</html>
