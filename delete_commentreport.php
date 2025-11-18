<?php
session_start();
require 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);

    // 1. Delete related reports first (to avoid foreign key error)
    $stmt1 = $conn->prepare("DELETE FROM comments_reports WHERE comment_id = ?");
    $stmt1->bind_param("i", $comment_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Now delete the comment
    $stmt2 = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt2->bind_param("i", $comment_id);
    $stmt2->execute();
    $stmt2->close();
}

header("Location: admin_comments.php");
exit;
?>
