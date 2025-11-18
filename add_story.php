<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user has 500+ points
$points_check = $conn->prepare("SELECT total_points FROM user_badges WHERE user_id = ?");
$points_check->bind_param("i", $user_id);
$points_check->execute();
$points_result = $points_check->get_result();
$user_points = $points_result->fetch_assoc();

if (!$user_points || $user_points['total_points'] < 500) {
    echo json_encode(['success' => false, 'message' => 'You need 500 points to add stories']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
$recipe_link = isset($_POST['recipe_link']) && !empty($_POST['recipe_link']) ? trim($_POST['recipe_link']) : null;
    
    // Handle file upload
    if (!isset($_FILES['story_image']) || $_FILES['story_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please upload an image']);
        exit;
    }
    
    $file = $_FILES['story_image'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, and PNG images are allowed']);
        exit;
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 10MB']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'uploads/stories/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'story_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
    
    // Insert story into database
    $insert_story = $conn->prepare("INSERT INTO stories (user_id, recipe_link, image, caption) VALUES (?, ?, ?, ?)");
$insert_story->bind_param("isss", $user_id, $recipe_link, $filepath, $caption);

    
    if ($insert_story->execute()) {
        echo json_encode(['success' => true, 'message' => 'Story added successfully']);
    } else {
        // Delete uploaded file if database insert fails
        unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Failed to save story']);
    }
    
    $insert_story->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>