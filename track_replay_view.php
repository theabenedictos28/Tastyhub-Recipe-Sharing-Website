<?php
session_start();
require_once 'db.php';

// Set JSON header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$stream_id = isset($data['stream_id']) ? (int)$data['stream_id'] : 0;

if ($stream_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid stream ID']);
    exit;
}

// Update view count
$sql = "UPDATE livestreams SET total_views = total_views + 1 WHERE id = ? AND is_active = 0";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $stream_id);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    echo json_encode([
        'success' => true, 
        'message' => 'View counted',
        'affected_rows' => $affected,
        'stream_id' => $stream_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>