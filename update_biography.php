<?php
session_start();
require 'db.php'; // Include database connection

header('Content-Type: application/json');

// Redirect to sign-in page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $biography = trim($_POST['biography']);

    $update_biography_sql = "UPDATE users SET biography = ? WHERE id = ?";
    $stmt = $conn->prepare($update_biography_sql);
    $stmt->bind_param("si", $biography, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Biography updated."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating biography: " . $stmt->error]);
    }
    exit;
}



echo json_encode(["success" => false, "message" => "Invalid request."]);
exit;
?>
