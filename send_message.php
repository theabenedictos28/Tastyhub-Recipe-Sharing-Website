<?php
session_start();
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message']);
$username = $_SESSION['username'];
$reply_to_id = isset($data['reply_to_id']) ? (int)$data['reply_to_id'] : null;

if ($message !== '') {
    $stmt = $conn->prepare("INSERT INTO messages (username, message, reply_to_id, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $username, $message, $reply_to_id);
    $stmt->execute();
}
?>
