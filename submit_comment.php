<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipe_id = intval($_POST['recipe_id']);
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);
    $parent_comment_id = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
        ? intval($_POST['parent_comment_id'])
        : null;

    if ($parent_comment_id === null) {
        $sql = "INSERT INTO comments (recipe_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $recipe_id, $user_id, $comment);
    } else {
        $sql = "INSERT INTO comments (recipe_id, user_id, comment, parent_comment_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $recipe_id, $user_id, $comment, $parent_comment_id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: recipe_details.php?id=" . $recipe_id ."#comments");
    exit;
}
?>