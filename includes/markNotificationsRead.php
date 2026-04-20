<?php
session_start();
if (empty($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit();
}
require_once 'conn.php';
$userId = (int)$_SESSION['userid'];
mysqli_query($link, "UPDATE notifications SET is_read = 1 WHERE recipient_id = $userId");
header('Content-Type: application/json');
echo json_encode(['success' => true]);
