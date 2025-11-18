<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($_POST['story_id'])) {
        // Mark story as viewed
        $story_id = intval($_POST['story_id']);
        
        // Check if story exists and is still active
        $check_story = $conn->prepare("SELECT user_id FROM stories WHERE id = ? AND is_active = 1 AND expires_at > NOW()");
        $check_story->bind_param("i", $story_id);
        $check_story->execute();
        $story_result = $check_story->get_result();


        if ($story_result->num_rows > 0) {
        $story_data = $story_result->fetch_assoc();
        
        if ($story_data['user_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Owner view not counted']);
            exit;
        }

            $record_view = $conn->prepare("INSERT IGNORE INTO story_views (story_id, user_id) VALUES (?, ?)");
            $record_view->bind_param("ii", $story_id, $user_id);
            $record_view->execute();
            
            echo json_encode(['success' => true, 'message' => 'View recorded']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Story not found or expired']);
        }
        
    } elseif (isset($_GET['get_stories'])) {
        // Get all active stories
        $stories_query = "
            SELECT 
                s.id,
                s.user_id,
                s.image,
                s.caption,
                s.recipe_link,
                s.created_at,
                u.username,
                u.profile_picture,
                (SELECT badge_name FROM user_badges WHERE user_id = s.user_id LIMIT 1) as badge_name,
                (SELECT badge_icon FROM user_badges WHERE user_id = s.user_id LIMIT 1) as badge_icon,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND user_id = ?) as viewed_by_me
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE s.is_active = 1 
            AND s.expires_at > NOW()
            ORDER BY s.created_at DESC
        ";
        
        $stmt = $conn->prepare($stories_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stories_by_user = [];
        while ($row = $result->fetch_assoc()) {
            $uid = $row['user_id'];
            if (!isset($stories_by_user[$uid])) {
                // Handle profile picture path
                $profile_pic = $row['profile_picture'];
                
                // If profile picture exists but doesn't have a path separator, add uploads/profile_pics/
                if (!empty($profile_pic) && strpos($profile_pic, '/') === false) {
                    $profile_pic = 'uploads/profile_pics/' . $profile_pic;
                }
                
                // Check if file exists, if not use default
                if (empty($profile_pic) || !file_exists($profile_pic)) {
                    $profile_pic = 'uploads/no_profile.png';
                }
                
                $stories_by_user[$uid] = [
                    'user_id' => $row['user_id'],
                    'username' => $row['username'],
                    'profile_picture' => $profile_pic,
                    'badge_name' => $row['badge_name'],
                    'badge_icon' => $row['badge_icon'],
                    'has_unviewed' => false,
                    'stories' => []
                ];
            }
            
            if (!$row['viewed_by_me']) {
                $stories_by_user[$uid]['has_unviewed'] = true;
            }
            
            $stories_by_user[$uid]['stories'][] = [
                'id' => $row['id'],
                'image' => $row['image'],
                'caption' => $row['caption'],
                'recipe_link' => $row['recipe_link'],
                'created_at' => $row['created_at'],
                'view_count' => $row['view_count'],
                'viewed_by_me' => $row['viewed_by_me']
            ];
        }
        
        $stmt->close();
        echo json_encode(['success' => true, 'stories' => array_values($stories_by_user)]);
        
    } elseif (isset($_GET['get_story_views']) && isset($_GET['story_id'])) {
        // Get list of users who viewed a specific story (only for story owner)
        $story_id = intval($_GET['story_id']);
        
        // Verify ownership
        $check_owner = $conn->prepare("SELECT user_id FROM stories WHERE id = ?");
        $check_owner->bind_param("i", $story_id);
        $check_owner->execute();
        $owner_result = $check_owner->get_result();
        $owner_data = $owner_result->fetch_assoc();
        
        if ($owner_data && $owner_data['user_id'] == $user_id) {
            $views_query = "
                SELECT 
                    u.id,
                    u.username,
                    u.profile_picture,
                    sv.viewed_at
                FROM story_views sv
                JOIN users u ON sv.user_id = u.id
                WHERE sv.story_id = ?
                ORDER BY sv.viewed_at DESC
            ";
            
            $stmt = $conn->prepare($views_query);
            $stmt->bind_param("i", $story_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $views = [];
            while ($row = $result->fetch_assoc()) {
                $views[] = $row;
            }
            
            echo json_encode(['success' => true, 'views' => $views]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>