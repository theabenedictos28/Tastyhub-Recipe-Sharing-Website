<?php
session_start();
require 'db.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Get the recipe ID from the URL
if (isset($_GET['id'])) {
    $recipeId = intval($_GET['id']); // Ensure it's an integer

    // Start a transaction
    $conn->begin_transaction();

    try {
        // First, delete all comments associated with the recipe
        $deleteCommentsSql = "DELETE FROM comments WHERE recipe_id = ?";
        $stmt = $conn->prepare($deleteCommentsSql);
        $stmt->bind_param("i", $recipeId);
        $stmt->execute();

        // Now, delete the recipe
        $deleteRecipeSql = "DELETE FROM recipe WHERE id = ?";
        $stmt = $conn->prepare($deleteRecipeSql);
        $stmt->bind_param("i", $recipeId);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Redirect to the dashboard or another page after deletion
        header("Location: profile.php?message=Recipe deleted successfully");
        exit;
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollback();
        echo "Error deleting recipe: " . $e->getMessage();
    }
} else {
    echo "No recipe ID provided.";
}
?>