<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

requireUserSession();
$user = getUserById($con, $_SESSION['user_id']);
if (!$user || $user['email_verified'] !== 'verified') {
    header("Location: verify_email.php");
    exit;
}
if ($user['status'] !== 'active') {
    header("Location: register_payment.php");
    exit;
}

ensureUserWallet($con, $user['id']);
$wallet = getUserWallet($con, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Add Funds</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .wallet-card {
            background: hsl(231, 12%, 12%);
            padding: 30px;
            border-radius: 12px;
            color: #fff;
            max-width: 600px;
            margin: 0 auto;
        }

        .wallet-card button {
            background-color: hsl(31, 100%, 51%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 8px;
            margin-bottom: 10px;
        }

        .wallet-card input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper">
                <div class="container">
                    <h2 class="h2 section-title">Add Money</h2>
                    <div class="wallet-card">
                        <p>Available balance: ₹<?= number_format((float) $wallet['balance'], 2) ?></p>
                        <p><small>Added balance is not refundable. Only winnings can be withdrawn.</small></p>
                        <div>
                            <button type="button" onclick="startPayment(10)">₹10</button>
                            <button type="button" onclick="startPayment(20)">₹20</button>
                            <button type="button" onclick="startPayment(50)">₹50</button>
                            <button type="button" onclick="startPayment(100)">₹100</button>
                            <button type="button" onclick="startPayment(200)">₹200</button>
                            <button type="button" onclick="startPayment(500)">₹500</button>
                        </div>
                        <label>Custom Amount</label>
                        <input type="number" id="customAmount" min="1" placeholder="Enter amount">
                        <button type="button" onclick="startPayment(getCustomAmount())">Pay Custom Amount</button>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        function getCustomAmount() {
            const value = document.getElementById('customAmount').value;
            return parseFloat(value || 0);
        }

        async function startPayment(amount) {
            if (!amount || amount <= 0) {
                alert('Enter a valid amount.');
                return;
            }
            const response = await fetch('assets/php/user_payment_create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount, payment_type: 'topup' })
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
                name: 'Wallet Top Up',
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
                        window.location.reload();
                    } else {
                        alert('Payment verification failed.');
                    }
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        }
    </script>
</body>

</html>
