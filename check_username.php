<?php
require 'db.php';
$username = trim($_POST['username']);
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode(['exists' => $result->num_rows > 0]);
?>
