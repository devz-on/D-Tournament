<?php 
function sendNotification($team_name , $message, $category = 'normal', $context = null) {
    global $con;
    $contextValue = $context ? "'" . mysqli_real_escape_string($con, $context) . "'" : "NULL";
    $query = "INSERT INTO `notification`(`team_name`, `message`, `category`, `context`) VALUES ('$team_name','$message','$category',$contextValue)";
    mysqli_query($con, $query);
}
function sendSystemNotification($message, $context = null) {
    sendNotification('System', $message, 'system', $context);
}
function seenNotification() {
    global $con;
    $query = "UPDATE `notification` SET `status`='read' WHERE 1";
    mysqli_query($con, $query);
}
function teamname($code) {
    global $con;
    $query = "SELECT * FROM `teams` WHERE `team_label`= '$code' ";
    $result = mysqli_query($con, $query);
    // Check if team exists
    if(mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result); // Return team details if found
    } else {
        //Return some value if team is not found

        return null; // Return null if team is not found
    }
}


?>
