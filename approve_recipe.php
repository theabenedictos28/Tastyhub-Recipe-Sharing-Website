<?php
session_start();
require 'db.php';

header('Content-Type: application/json'); // JSON response

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['action'])) {
    $recipe_id = intval($_POST['id']);
    $action = $_POST['action'];
    $message = '';
    
    // Get recipe owner ID BEFORE updating status
    $owner_sql = "SELECT user_id FROM recipe WHERE id = ?";
    $owner_stmt = $conn->prepare($owner_sql);
    $owner_stmt->bind_param("i", $recipe_id);
    $owner_stmt->execute();
    $owner_result = $owner_stmt->get_result();
    
    if ($owner_result->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Recipe not found']);
        exit;
    }
    
    $recipe_owner_id = $owner_result->fetch_assoc()['user_id'];
    
    if ($action === 'approved' || $action === 'pending') {
        $stmt = $conn->prepare("UPDATE recipe SET status = ?, rejection_reason = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $action, $recipe_id);
            $stmt->execute();
            
            // Update points when status changes
            updateUserPoints($conn, $recipe_owner_id);
            
            $message = ucfirst($action) . " successfully.";
        } else {
            echo json_encode(['status'=>'error','message'=>$conn->error]);
            exit;
        }
    } elseif ($action === 'reject') {
        if (isset($_POST['rejection_reason']) && !empty($_POST['rejection_reason'])) {
            $rejection_reason = $_POST['rejection_reason'];
            if ($rejection_reason === 'Other' && !empty($_POST['custom_reason'])) {
                $rejection_reason = $_POST['custom_reason'];
            }
            $stmt = $conn->prepare("UPDATE recipe SET status='rejected', rejection_reason=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("si", $rejection_reason, $recipe_id);
                $stmt->execute();
                
                // Update points when rejected (removes the 5 points if previously approved)
                updateUserPoints($conn, $recipe_owner_id);
                
                $message = "Recipe rejected.";
            } else {
                echo json_encode(['status'=>'error','message'=>$conn->error]);
                exit;
            }
        } else {
            echo json_encode(['status'=>'error','message'=>'Rejection reason is required']);
            exit;
        }
    } else {
        echo json_encode(['status'=>'error','message'=>'Invalid action']);
        exit;
    }
    
    echo json_encode(['status'=>'success','message'=>$message]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Invalid request']);
$conn->close();

// Function to recalculate and update user points & badge
function updateUserPoints($conn, $user_id) {
    // Get like count (only approved recipes)
    $like_sql = "SELECT COUNT(*) AS like_count 
                 FROM likes l
                 JOIN recipe r ON l.recipe_id = r.id
                 WHERE r.user_id = ? AND r.status = 'approved'";
    $stmt = $conn->prepare($like_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $like_count = $stmt->get_result()->fetch_assoc()['like_count'];
    
    // Get favorite count (only approved recipes)
    $fav_sql = "SELECT COUNT(*) AS fav_count 
                FROM favorites f
                JOIN recipe r ON f.recipe_id = r.id
                WHERE r.user_id = ? AND r.status = 'approved'";
    $stmt = $conn->prepare($fav_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fav_count = $stmt->get_result()->fetch_assoc()['fav_count'];
    
    // Get approved recipe count
    $recipe_sql = "SELECT COUNT(*) AS recipe_count 
                   FROM recipe 
                   WHERE user_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($recipe_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recipe_count = $stmt->get_result()->fetch_assoc()['recipe_count'];
    
    // Calculate total points
    $total_points = ($like_count * 1) + ($fav_count * 2) + ($recipe_count * 5);
    
    // Determine badge (check from highest to lowest)
    $badge = 'No Badge Yet';
    $badge_icon = '';
    
    $levels = [
        ['points' => 1000, 'name' => 'Culinary Legend', 'icon' => 'img/culinary_legend.png'],
        ['points' => 500,  'name' => 'Culinary Star',   'icon' => 'img/culinary_star.png'],
        ['points' => 300,  'name' => 'Gourmet Guru',    'icon' => 'img/gourmet_guru.png'],
        ['points' => 150,  'name' => 'Flavor Favorite', 'icon' => 'img/flavor_favorite.png'],
        ['points' => 75,   'name' => 'Kitchen Star',    'icon' => 'img/kitchen_star.png'],
        ['points' => 20,   'name' => 'Freshly Baked',   'icon' => 'img/freshly_baked.png']
    ];
    
    foreach ($levels as $level) {
        if ($total_points >= $level['points']) {
            $badge = $level['name'];
            $badge_icon = $level['icon'];
            break;
        }
    }
    
    // Update user_badges table with points and badge
    $update_sql = "INSERT INTO user_badges (user_id, badge_name, badge_icon, total_points) 
                   VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   badge_name = VALUES(badge_name), 
                   badge_icon = VALUES(badge_icon), 
                   total_points = VALUES(total_points)";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("issi", $user_id, $badge, $badge_icon, $total_points);
    $stmt->execute();
}
?>