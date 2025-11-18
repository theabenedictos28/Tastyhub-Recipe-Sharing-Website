<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$reporting_user_id = $_SESSION['user_id'];
$comment_id = $_POST['comment_id'];
$reported_user_id = $_POST['reported_user_id'];
$recipe_id = $_POST['recipe_id'];
$reason = $_POST['reason'];
$custom_reason = null;

if ($reason === "Other" && !empty($_POST['other_reason'])) {
    $custom_reason = $_POST['other_reason'];
}

// ✅ Check if same user already reported the same comment (still pending)
$check = $conn->prepare("
    SELECT id 
    FROM comments_reports 
    WHERE reporting_user_id = ? 
      AND comment_id = ? 
      AND status = 'Pending'
");
$check->bind_param("ii", $reporting_user_id, $comment_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Already reported this comment, still pending
    echo json_encode(["status" => "already_reported"]);
} else {
    // Insert new report
    $stmt = $conn->prepare("
        INSERT INTO comments_reports 
        (reporting_user_id, reported_user_id, recipe_id, comment_id, reason, custom_reason, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->bind_param("iiiiss", $reporting_user_id, $reported_user_id, $recipe_id, $comment_id, $reason, $custom_reason
);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB insert failed"]);
    }
}
