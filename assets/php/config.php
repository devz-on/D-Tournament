<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$username = "oravffoz_dev";
$password = "oravffoz_dev";
$server = 'localhost';
$db = "oravffoz_tournament";


// $username = "mypoetry_admin";
// $password = 'aX0m9~U&6)Fr';
// $server = 'localhost';
// $db = "mypoetry_esports";


$con  = mysqli_connect($server,$username,$password,$db);

if (!$con) {
    echo "Connection unsuccessful";
    die("Not Connected " . mysqli_connect_error());
} 

if (!isset($_COOKIE['viewed'])) {
    // Update database and set cookie
    $sql = "UPDATE settings SET `data1` = `data1` + 1 WHERE id = 1";
    $con->query($sql);

    setcookie('viewed', '1', time() + (5 * 24 * 60 * 60));
}


/* Razorpay Config */
define('RAZORPAY_KEY_ID', 'rzp_live_RapWYMXKAAQqH0');      // NEW key id
define('RAZORPAY_KEY_SECRET', 'tHQLfhEE3lT4U75GImgXCwWF'); // NEW secret
define('REGISTRATION_AMOUNT', 50); // INR
?>