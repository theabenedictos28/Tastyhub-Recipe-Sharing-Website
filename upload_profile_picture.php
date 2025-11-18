<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
    exit;
}

// Validate file size (5MB max)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB.']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pics/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Get current profile picture to delete old one
$current_pic_sql = "SELECT profile_picture FROM users WHERE id = ?";
$current_pic_stmt = $conn->prepare($current_pic_sql);
$current_pic_stmt->bind_param("i", $user_id);
$current_pic_stmt->execute();
$current_pic_result = $current_pic_stmt->get_result();
$current_pic_data = $current_pic_result->fetch_assoc();
$old_picture = $current_pic_data['profile_picture'] ?? null;

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Update database
    $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_filename, $user_id);
    
    if ($update_stmt->execute()) {
        // Delete old profile picture if it exists and is not default
        if ($old_picture && $old_picture !== 'default-avatar.png') {
            $old_file_path = $upload_dir . $old_picture;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'image_url' => $upload_path
        ]);
    } else {
        // Delete uploaded file if database update fails
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}

$conn->close();
?>