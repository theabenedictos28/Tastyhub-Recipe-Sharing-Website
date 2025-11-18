<?php
session_start();
require 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);

    // Update report status to Dismissed
    $stmt = $conn->prepare("UPDATE comments_reports SET status = 'Dismissed' WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin_comments.php");
exit;
?>
