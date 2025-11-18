<?php
require 'db.php';

$livestream_id = $_POST['livestream_id'] ?? null;
$user_ip = $_SERVER['REMOTE_ADDR'];

if ($livestream_id) {
    // Remove viewer from table
    $stmt = $conn->prepare("
        DELETE FROM livestream_viewers 
        WHERE livestream_id = ? AND user_ip = ?
    ");
    $stmt->bind_param("is", $livestream_id, $user_ip);
    $stmt->execute();

    // Update viewer count
    $conn->query("
        UPDATE livestreams 
        SET viewer_count = (SELECT COUNT(*) FROM livestream_viewers WHERE livestream_id = $livestream_id)
        WHERE id = $livestream_id
    ");
}
?>
