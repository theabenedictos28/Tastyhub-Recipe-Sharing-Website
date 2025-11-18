<?php
session_start();
require 'db.php'; // include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Check if recipe ID is provided
if (isset($_GET['id'])) {
    $recipeId = intval($_GET['id']);
    $userId = $_SESSION['user_id'];

    // Update recipe: set as archived, declined, and store the archived date
$sql = "UPDATE recipe 
        SET archived = 1, status = 'archived', archived_at = NOW()
        WHERE id = ? AND user_id = ?";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recipeId, $userId);

    if ($stmt->execute()) {
        header("Location: profile.php?message=Recipe moved to archive successfully");
        exit;
    } else {
        echo "Error archiving recipe: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "No recipe ID provided.";
}

$conn->close();
?>
