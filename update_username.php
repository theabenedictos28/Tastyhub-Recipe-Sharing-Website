<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];
$new_username = trim($_POST['username']);

// 1. Validate length
if (strlen($new_username) < 3 || strlen($new_username) > 20) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3–20 characters.']);
    exit;
}

// 2. Check duplicates
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->bind_param("si", $new_username, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken.']);
    exit;
}

// 3. Check cooldown
$stmt = $conn->prepare("SELECT last_username_change FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$last_change = $stmt->get_result()->fetch_assoc()['last_username_change'];

if ($last_change && (strtotime($last_change) > strtotime('-30 days'))) {
    $seconds_left = (strtotime($last_change) + (30 * 24 * 60 * 60)) - time();
    $days_left = max(1, floor($seconds_left / (60 * 60 * 24))); // ✅ FIXED: no more "31 days"
    
    echo json_encode([
        'success' => false,
        'message' => "You can change your username again in $days_left day" . ($days_left > 1 ? "s" : "") . "."
    ]);
    exit;
}

// 4. Update username
$stmt = $conn->prepare("UPDATE users SET username = ?, last_username_change = NOW() WHERE id = ?");
$stmt->bind_param("si", $new_username, $user_id);
if ($stmt->execute()) {
    $_SESSION['username'] = $new_username;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
