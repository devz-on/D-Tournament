<?php
include "../../assets/php/config.php"; // Include your database configuration file

header('Content-Type: application/json');

if (!isset($_GET['image-exists'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing image-exists flag']);
    exit;
}

$requestData = json_decode(file_get_contents('php://input'), true);
$imageName = $requestData['imageName'] ?? ($_POST['imageName'] ?? ($_GET['imageName'] ?? ''));

if ($imageName === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing imageName']);
    exit;
}

$query = "SELECT COUNT(*) AS count FROM teams WHERE profile = ?";
$stmt = mysqli_prepare($con, $query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare query']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $imageName);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to execute query']);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
echo json_encode(['exists' => ((int) $row['count']) > 0]);
?>
