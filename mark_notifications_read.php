<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Debug: Check current unread count
$check = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();
$before = $result->fetch_assoc()['count'];
$check->close();

// Mark all notifications as read for this user
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    echo json_encode([
        'success' => true, 
        'before' => $before, 
        'affected_rows' => $affected,
        'user_id' => $user_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}

$stmt->close();
$conn->close();
?>