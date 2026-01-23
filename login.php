<?php
include "assets/php/config.php";
include "assets/php/user_helpers.php";
session_start();

$response = "";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['login_user'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = $_POST['password'];
    $user = getUserByEmail($con, $email);
    if (!$user || !$user['password_hash']) {
        $response = "Invalid login details.";
    } elseif (!password_verify($password, $user['password_hash'])) {
        $response = "Invalid login details.";
    } elseif ($user['status'] === 'banned') {
        $response = "Your account is banned. Contact support.";
    } else {
        $_SESSION['user_id'] = $user['id'];
        if ($user['status'] === 'pending') {
            header("Location: register_payment.php");
            exit;
        }
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>User Login</title>
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

        .auth-card .hint {
            font-size: 14px;
            color: #bbb;
        }
    </style>
</head>

<body id="top">
    <main>
        <article>
            <?php include "assets/pages/navbar.php"; ?>
            <section class="team section-wrapper" id="login">
                <div class="container">
                    <h2 class="h2 section-title">Login</h2>
                    <div class="auth-card">
                        <?php if ($response) { ?>
                            <p><?= htmlspecialchars($response) ?></p>
                        <?php } ?>
                        <form method="post">
                            <label>Email</label>
                            <input type="email" name="email" required>
                            <label>Password</label>
                            <input type="password" name="password" required>
                            <button type="submit" name="login_user">Login</button>
                        </form>
                        <p class="hint">No account yet? <a href="register.php">Register</a></p>
                        <hr>
                        <p class="hint">Or login using Google:</p>
                        <button type="button" id="googleLogin">Continue with Google</button>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include "assets/pages/footer.php"; ?>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script>
        const firebaseConfig = {
            apiKey: "YOUR_FIREBASE_API_KEY",
            authDomain: "YOUR_FIREBASE_AUTH_DOMAIN",
            projectId: "YOUR_FIREBASE_PROJECT_ID",
        };

        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        document.getElementById('googleLogin').addEventListener('click', async () => {
            const provider = new firebase.auth.GoogleAuthProvider();
            const result = await auth.signInWithPopup(provider);
            const user = result.user;
            const response = await fetch('assets/php/firebase_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ uid: user.uid, email: user.email, username: user.displayName || user.email })
            });
            const payload = await response.json();
            if (payload.redirect) {
                window.location.href = payload.redirect;
            }
        });
    </script>
</body>

</html>
