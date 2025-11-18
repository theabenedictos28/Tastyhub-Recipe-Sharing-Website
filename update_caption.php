<?php
session_start();
require 'db.php'; // Include database connection file

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read the input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'], $input['caption'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$id = intval($input['id']);
$caption = trim($input['caption']);

// Validate caption length (optional)
if (strlen($caption) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Caption is too long']);
    exit;
}

// Verify ownership: ensure the repost entry belongs to the logged-in user
$stmt = $conn->prepare("SELECT user_id FROM reposts WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed']);
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Repost not found']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();

if ($row['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You are not authorized to edit this caption']);
    $stmt->close();
    exit;
}
$stmt->close();

// Update the caption
$update_stmt = $conn->prepare("UPDATE reposts SET caption = ? WHERE id = ?");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed']);
    exit;
}
$update_stmt->bind_param("si", $caption, $id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Caption updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update caption']);
}

$update_stmt->close();
?>

