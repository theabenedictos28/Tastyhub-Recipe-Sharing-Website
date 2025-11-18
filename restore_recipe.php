<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$recipeId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// When restoring: archived = 0, status = 'pending'
$sql = "UPDATE recipe 
        SET archived = 0, status = 'pending'
        WHERE id = ? AND user_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $recipeId, $userId);

if ($stmt->execute()) {
    header("Location: archived_recipes.php?message=Recipe restored and sent for admin review");
    exit;
} else {
    echo "Error restoring recipe: " . $conn->error;
}
?>
