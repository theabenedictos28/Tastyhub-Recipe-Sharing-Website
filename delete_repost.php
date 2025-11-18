<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check user logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['repost_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$repost_id = intval($input['repost_id']);

// Verify ownership (only allow user to delete their own repost)
$stmt = $conn->prepare("SELECT user_id FROM reposts WHERE id = ?");
$stmt->bind_param("i", $repost_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Repost not found']);
    exit;
}

$row = $result->fetch_assoc();

if ($row['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this repost']);
    exit;
}

// Delete the repost
$del_stmt = $conn->prepare("DELETE FROM reposts WHERE id = ?");
$del_stmt->bind_param("i", $repost_id);

if ($del_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Repost deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete repost']);
}

$del_stmt->close();
$conn->close();
?>
