<?php
include '../../assets/php/config.php';

$username = $_POST['team_name'];

// SQL query to fetch autocomplete suggestions
$sql = "SELECT DISTINCT `username` FROM `users` WHERE `username` LIKE '$username%'";
$result = $con->query($sql);

// Process and display the autocomplete suggestions
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<a class='dropdown-item' href='users.php?q=".$row['username']."'>".$row['username']."</a>";
    }
} else {
    echo "No suggestions found.";
}

?>
