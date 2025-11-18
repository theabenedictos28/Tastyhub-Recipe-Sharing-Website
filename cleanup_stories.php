<?php
/**
 * Cleanup Expired Stories
 * Run this script via cron job every hour:
 * 0 * * * * /usr/bin/php /path/to/your/project/cleanup_stories.php
 */

require 'db.php';

// Get all expired stories
$expired_stories = $conn->query("
    SELECT id, image 
    FROM stories 
    WHERE expires_at <= NOW() OR is_active = 0
");

$deleted_count = 0;
$failed_count = 0;

while ($story = $expired_stories->fetch_assoc()) {
    // Delete the image file
    if (file_exists($story['image'])) {
        if (unlink($story['image'])) {
            echo "Deleted image: {$story['image']}\n";
        } else {
            echo "Failed to delete image: {$story['image']}\n";
            $failed_count++;
        }
    }
    
    // Delete story views first (foreign key constraint)
    $delete_views = $conn->prepare("DELETE FROM story_views WHERE story_id = ?");
    $delete_views->bind_param("i", $story['id']);
    $delete_views->execute();
    
    // Delete the story record
    $delete_story = $conn->prepare("DELETE FROM stories WHERE id = ?");
    $delete_story->bind_param("i", $story['id']);
    
    if ($delete_story->execute()) {
        $deleted_count++;
        echo "Deleted story ID: {$story['id']}\n";
    } else {
        $failed_count++;
        echo "Failed to delete story ID: {$story['id']}\n";
    }
}

echo "\nCleanup completed:\n";
echo "- Stories deleted: $deleted_count\n";
echo "- Failed deletions: $failed_count\n";
echo "- Timestamp: " . date('Y-m-d H:i:s') . "\n";

$conn->close();
?>