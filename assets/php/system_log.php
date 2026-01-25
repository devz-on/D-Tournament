<?php
include "config.php";
include "function.php";

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$message = isset($payload['message']) ? trim($payload['message']) : '';
$context = isset($payload['context']) ? trim($payload['context']) : '';

if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing message']);
    exit;
}

sendSystemNotification($message, $context);

echo json_encode(['status' => 'ok']);
