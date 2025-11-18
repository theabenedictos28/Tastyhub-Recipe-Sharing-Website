<?php
session_start();
require 'db.php'; // Your database connection file

header('Content-Type: application/json'); // Ensure JSON response

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized request."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit;
}

// Fetch current password from DB
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

if (!$hashed_password) {
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit;
}

// Verify the current password
if (!password_verify($current_password, $hashed_password)) {
    echo json_encode([
        "success" => false,
        "message" => "Current password is incorrect."
    ]);
} else {
    // Hash and update new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_hashed_password, $user_id);

    if ($update_stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Password updated successfully!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Something went wrong. Please try again."
        ]);
    }

    $update_stmt->close();
}
?>
