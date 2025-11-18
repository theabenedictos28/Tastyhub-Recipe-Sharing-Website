<?php
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "
    SELECT 
        m.id, 
        m.username, 
        m.message, 
        m.timestamp,
        u.profile_picture,
        r.message AS reply_to_message, 
        r.username AS reply_to_user
    FROM messages m
    LEFT JOIN users u 
        ON TRIM(LOWER(m.username)) COLLATE utf8mb4_general_ci = TRIM(LOWER(u.username)) COLLATE utf8mb4_general_ci
    LEFT JOIN messages r ON m.reply_to_id = r.id
    ORDER BY m.id ASC
";

$result = $conn->query($sql);

if (!$result) {
    die('Query Error: ' . $conn->error);
}

$messages = [];
while ($row = $result->fetch_assoc()) {
    // If profile picture is empty, use default
    if (empty($row['profile_picture'])) {
        $row['profile_picture'] = 'img/no_profile.png';
    } else {
        // Ensure correct path (use uploads/profile_pics/)
        if (!str_starts_with($row['profile_picture'], 'uploads/profile_pics/')) {
            $row['profile_picture'] = 'uploads/profile_pics/' . basename($row['profile_picture']);
        }
    }

    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages, JSON_PRETTY_PRINT);
?>
