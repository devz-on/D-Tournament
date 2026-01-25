<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
include "assets/php/send_code.php";
session_start();

requireUserSession();
$user = getUserById($con, $_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$response = "";

if ($user['email_verified'] === 'verified') {
    if ($user['status'] === 'pending') {
        header("Location: register_payment.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

if (isset($_POST['verify_email'])) {
    $otp = mysqli_real_escape_string($con, $_POST['otp']);
    if ($otp === $user['email_otp'] && $user['email_otp_expires'] && strtotime($user['email_otp_expires']) > time()) {
        mysqli_query($con, "UPDATE users SET email_verified='verified', email_otp=NULL, email_otp_expires=NULL WHERE id={$user['id']}");
        $user = getUserById($con, $user['id']);
        if ($user['status'] === 'pending') {
            header("Location: register_payment.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }
    $response = "Invalid or expired OTP.";
}

if (isset($_POST['resend_otp'])) {
    $otpCode = (string) random_int(100000, 999999);
    $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    mysqli_query($con, "UPDATE users SET email_otp='$otpCode', email_otp_expires='$otpExpires' WHERE id={$user['id']}");
    sendOtp(
        $user['email'],
        'Verify your email for Aimgod eSports',
        $otpCode,
        $user['username'],
        date('M Y'),
        'send-otp.html'
    );
    $response = "OTP resent to your email.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Email</title>
    <?php include "assets/pages/header.php"; ?>
    <style>
        .auth-card {
            background: hsl(231, 12%, 12%);
            padding: 30px;
            border-radius: 12px;
            color: #fff;
            max-width: 520px;
            margin: 0 auto;
        }

        .auth-card input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .auth-card button {
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
                    <h2 class="h2 section-title">Verify Your Email</h2>
                    <div class="auth-card">
                        <?php if ($response) { ?>
                            <p><?= htmlspecialchars($response) ?></p>
                        <?php } ?>
                        <p>We sent a 6-digit OTP to <?= htmlspecialchars($user['email']) ?>.</p>
                        <form method="post">
                            <input type="text" name="otp" placeholder="Enter OTP" required>
                            <button type="submit" name="verify_email">Verify Email</button>
                        </form>
                        <form method="post" style="margin-top:10px;">
                            <button type="submit" name="resend_otp">Resend OTP</button>
                        </form>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
</body>

</html>
