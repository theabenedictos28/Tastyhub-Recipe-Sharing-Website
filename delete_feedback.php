<?php
session_start();
require 'db.php'; // Include database connection

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = intval($_POST['feedback_id']);

    // Prepare and execute the delete statement
    $sql = "DELETE FROM feedback WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();

    // Redirect back to the admin dashboard
    header("Location: admin_feedback.php"); // Adjust the redirect as necessary
    exit;
}
?>