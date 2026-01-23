<?php
 include "assets/php/config.php";
 session_start();
 ?>
 <!DOCTYPE html>
 <html lang="en">
 
 <head>
    <title>Aimgod eSports - Tournaments</title>
     <?php include "assets/pages/header.php"; ?>
     <style>
         .main {
             display: flex;
             flex-wrap: wrap;
             justify-content: space-around;
             padding: 20px;
         }
 
         .tournament-card {
             background-color: hsl(231, 12%, 12%);
             border-radius: 10px;
             box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
             margin-bottom: 20px;
             width: 45%;
             color: #fff;
         }
 
         .card-heading {
             background-color: hsl(231, 12%, 12%);
             color: #fff;
             border-top-left-radius: 10px;
             border-top-right-radius: 10px;
             padding: 10px;
             text-align: center;
             border-bottom: 1px solid white;
         }
 
         .card-details {
             padding: 20px;
         }
 
         .card-actions {
             padding: 20px;
             text-align: center;
         }
 
         .btn-register {
             background-color: hsl(31, 100%, 51%);
             border: none;
             border-radius: 5px;
             color: #fff;
             cursor: pointer;
             padding: 10px 20px;
             text-decoration: none;
             transition: background-color 0.3s;
         }
 
         .btn-register:hover {
             background-color: hsl(25, 100%, 51%);
         }
 
         .highlighter {
             color: hsl(31, 100%, 51%);
         }
     </style>
 </head>
 
 <body id="top">
     <main>
         <article>
             <?php include "assets/pages/navbar.php"; ?>
             <br><br>
             <section class="team section-wrapper" id="team">
                 <div class="container ">
                    <h2 class="h2 section-title">Available Tournaments</h2>
                     <div class="main">
                        <?php
                        $tournaments = mysqli_query($con, "SELECT * FROM tournaments WHERE status='published' ORDER BY start_time ASC");
                        if ($tournaments && mysqli_num_rows($tournaments) > 0) {
                            while ($tournament = mysqli_fetch_assoc($tournaments)) {
                        ?>
                                <section class="tournament-card">
                                    <div class="card-heading">
                                        <h2><?= htmlspecialchars($tournament['name']) ?></h2>
                                    </div>
                                    <div class="card-details">
                                        <p><strong class="highlighter">Map:</strong> <?= htmlspecialchars($tournament['map_name']) ?></p>
                                        <p><strong class="highlighter">Match Date:</strong> <?= date('d M Y, h:i A', strtotime($tournament['start_time'])) ?></p>
                                        <p><strong class="highlighter">Entry Fee:</strong> ₹<?= number_format((float) $tournament['entry_fee'], 2) ?></p>
                                        <p><strong class="highlighter">Prize Pool:</strong> ₹<?= number_format((float) $tournament['prize_pool'], 2) ?></p>
                                        <?php if ((int) $tournament['max_seats'] > 0) { ?>
                                            <p><strong class="highlighter">Seats:</strong> <?= $tournament['seats_filled'] ?> / <?= $tournament['max_seats'] ?></p>
                                        <?php } else { ?>
                                            <p><strong class="highlighter">Seats:</strong> Unlimited</p>
                                        <?php } ?>
                                        <p><strong class="highlighter">Description:</strong> <?= htmlspecialchars($tournament['description']) ?></p>
                                    </div>
                                    <div class="card-actions">
                                        <a href="tournaments.php" class="btn-register">View & Register</a>
                                    </div>
                                </section>
                        <?php
                            }
                        } else {
                        ?>
                            <section class="tournament-card">
                                <div class="card-heading">
                                    <h2>No tournaments published yet</h2>
                                </div>
                                <div class="card-details">
                                    <p>Check back soon for new events and announcements.</p>
                                </div>
                            </section>
                        <?php } ?>
                     </div>
                 </div>
             </section>
         </article>
     </main>
 
     <?php include "assets/pages/footer.php"; ?>
 </body>
 
</html>