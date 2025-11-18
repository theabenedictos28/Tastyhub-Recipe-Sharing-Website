<?php
require 'db.php';

$livestream_id = $_GET['livestream_id'] ?? null;

if ($livestream_id) {
    $result = $conn->query("SELECT viewer_count FROM livestreams WHERE id = $livestream_id");
    $row = $result->fetch_assoc();
    echo $row['viewer_count'];
}
?>
