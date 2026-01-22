<?php
include "assets/php/send_code.php";

$result = sendOtp(
    "bhoothihu@gmail.com",
    "Mail Test - Aimgod",
    "TEST123",
    "Test User",
    date("M Y"),
    "send-otp.html"
);

echo $result ? "MAIL SENT OK" : "MAIL FAILED";
