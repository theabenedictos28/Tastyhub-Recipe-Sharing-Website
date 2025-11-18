<?php
require 'db.php';

$livestream_id = $_POST['livestream_id'] ?? null;
$user_ip = $_SERVER['REMOTE_ADDR'];

if ($livestream_id) {

    // 🔹 1. Permanent view logging (counted once per IP per livestream)
    $check = $conn->prepare("
        SELECT id FROM view_logs WHERE livestream_id = ? AND ip_address = ?
    ");
    $check->bind_param("is", $livestream_id, $user_ip);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        // Insert permanent record
        $insert = $conn->prepare("
            INSERT INTO view_logs (livestream_id, ip_address, viewed_at)
            VALUES (?, ?, NOW())
        ");
        $insert->bind_param("is", $livestream_id, $user_ip);
        $insert->execute();

        // Increment permanent total_views
        $conn->query("
            UPDATE livestreams 
            SET total_views = total_views + 1
            WHERE id = $livestream_id
        ");
    }

    // 🔹 2. Temporary viewer tracking (for live count)
    $stmt = $conn->prepare("
        INSERT IGNORE INTO livestream_viewers (livestream_id, user_ip)
        VALUES (?, ?)
    ");
    $stmt->bind_param("is", $livestream_id, $user_ip);
    $stmt->execute();

    // 🔹 3. Update live viewer count (real-time only)
    $conn->query("
        UPDATE livestreams 
        SET viewer_count = (SELECT COUNT(*) FROM livestream_viewers WHERE livestream_id = $livestream_id)
        WHERE id = $livestream_id
    ");
}
?>
