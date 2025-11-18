<?php
session_start();
require 'db.php'; // Database connection

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = intval($_POST['comment_id']);
    $recipe_id = intval($_POST['recipe_id']);
    $user_id = $_SESSION['user_id'];

    // Check if the comment belongs to the user
    $check_sql = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $comment = $result->fetch_assoc();
        if ($comment['user_id'] == $user_id) {
            // Delete the comment
            $delete_sql = "DELETE FROM comments WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
        }
    }

    // Redirect back to the recipe page
    header("Location: recipe_details.php?id=" . $recipe_id ."#comments");
    exit;
}
?>