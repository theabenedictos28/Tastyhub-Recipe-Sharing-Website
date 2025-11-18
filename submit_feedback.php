<?php
session_start();
require 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Insert feedback into the database
    $sql = "INSERT INTO feedback (name, email, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $message);
    $stmt->execute();

    // Redirect back to the about page with a success message
    $_SESSION['feedback_success'] = "Thank you for your feedback!";
    header("Location: about.php");
    exit;
}
?>