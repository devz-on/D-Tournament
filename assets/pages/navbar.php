<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Registration Control (SAFE VERSION)
|--------------------------------------------------------------------------
*/

$registion_url = "register.php"; // default fallback

$select_settings = "SELECT data1, data2 FROM settings WHERE id = 2";
$result_settings = mysqli_query($con, $select_settings);

if ($result_settings) {
    if (mysqli_num_rows($result_settings) > 0) {
        $row = mysqli_fetch_assoc($result_settings);

        $status = isset($row['data1']) ? stripslashes($row['data1']) : '';

        if ($status === "start" && !empty($row['data2'])) {
            $token = stripslashes($row['data2']);
            $_SESSION['register-token'] = urlencode($token);
            $registion_url = "register.php?active=yes&t=" . urlencode($token);
        }
    }
} else {
    // Optional: uncomment for debugging
    // die("SQL Error: " . mysqli_error($con));
}
?>

<header class="header">

  <!-- overlay -->
  <div class="overlay" data-overlay></div>

  <div class="container">

    <a href="index.php" class="logo">
      <img src="./assets/images/logo.png" alt="GameX logo">
    </a>

    <button class="nav-open-btn" data-nav-open-btn>
      <ion-icon name="menu-outline"></ion-icon>
    </button>

    <nav class="navbar" data-nav>

      <div class="navbar-top">

        <a href="index.php" class="logo">
          <img src="./assets/images/logo.svg" alt="GameX logo">
        </a>

        <button class="nav-close-btn" data-nav-close-btn>
          <ion-icon name="close-outline"></ion-icon>
        </button>

      </div>

      <ul class="navbar-list">

        <li><a href="index.php#hero" class="navbar-link">Home</a></li>
        <li><a href="index.php#about" class="navbar-link">About</a></li>
        <li><a href="tornament.php" class="navbar-link">Tournaments</a></li>
        <li><a href="tournaments.php" class="navbar-link">Upcoming</a></li>
        <li><a href="my_tournaments.php" class="navbar-link">My Tournaments</a></li>
        <li><a href="leaderboard.php" class="navbar-link">Leaderboard</a></li>
        <li><a href="dashboard.php" class="navbar-link">Dashboard</a></li>
        <li><a href="contact.php" class="navbar-link">Contact</a></li>

      </ul>

      <ul class="nav-social-list">

        <li>
          <a href="https://chat.whatsapp.com/GTAcQ60U1OsC6sN9uFdGYT" class="social-link">
            <ion-icon name="logo-whatsapp"></ion-icon>
          </a>
        </li>

        <li>
          <a href="https://www.instagram.com/org_aimgod" class="social-link">
            <ion-icon name="logo-instagram"></ion-icon>
          </a>
        </li>

        <li>
          <a href="https://youtube.com/@AimgodE-Sports" class="social-link">
            <ion-icon name="logo-youtube"></ion-icon>
          </a>
        </li>

        <li>
          <a href="https://discord.gg/FckbdBgD7g" class="social-link">
            <ion-icon name="logo-discord"></ion-icon>
          </a>
        </li>

      </ul>

    </nav>

    <div class="header-actions">

      <a href="dashboard.php">
        <button class="search">
          <ion-icon name="game-controller-outline"></ion-icon>
        </button>
      </a>

      <?php if (isset($_SESSION['user_id'])) { ?>
        <a href="add_funds.php">
          <button class="btn-sign_in">
            <div class="icon-box">
              <ion-icon name="wallet-outline"></ion-icon>
            </div>
            <span>Add Money</span>
          </button>
        </a>
      <?php } else { ?>
        <a href="login.php">
          <button class="btn-sign_in">
            <div class="icon-box">
              <ion-icon name="log-in-outline"></ion-icon>
            </div>
            <span>Login</span>
          </button>
        </a>
        <a href="register.php">
          <button class="btn-sign_in">
            <div class="icon-box">
              <ion-icon name="person-add-outline"></ion-icon>
            </div>
            <span>Register</span>
          </button>
        </a>
      <?php } ?>

    </div>

  </div>

</header>
