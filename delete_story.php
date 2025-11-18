<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['story_id'])) {
    $story_id = intval($_POST['story_id']);
    
    // Verify the story belongs to the current user
    $check_owner = $conn->prepare("SELECT image FROM stories WHERE id = ? AND user_id = ?");
    $check_owner->bind_param("ii", $story_id, $user_id);
    $check_owner->execute();
    $result = $check_owner->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Story not found or unauthorized']);
        exit;
    }
    
    $story_data = $result->fetch_assoc();
    $image_path = $story_data['image'];
    
    // Delete story views first
    $delete_views = $conn->prepare("DELETE FROM story_views WHERE story_id = ?");
    $delete_views->bind_param("i", $story_id);
    $delete_views->execute();
    
    // Delete the story record
    $delete_story = $conn->prepare("DELETE FROM stories WHERE id = ?");
    $delete_story->bind_param("i", $story_id);
    
    if ($delete_story->execute()) {
        // Delete the image file
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        echo json_encode(['success' => true, 'message' => 'Story deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>