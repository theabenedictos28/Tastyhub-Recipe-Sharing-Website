<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$recipe_id = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;

if (!$recipe_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid recipe ID']);
    exit;
}

// Get recipe owner
$owner_sql = "SELECT user_id FROM recipe WHERE id = ? AND status = 'approved'";
$owner_stmt = $conn->prepare($owner_sql);
$owner_stmt->bind_param("i", $recipe_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();

if ($owner_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Recipe not found']);
    exit;
}

$recipe_owner_id = $owner_result->fetch_assoc()['user_id'];

// Check if already liked
$check_sql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove like
    $delete_sql = "DELETE FROM likes WHERE user_id = ? AND recipe_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();
    $status = 'removed';
    
    // Update recipe owner points (-1 point)
    updateUserPoints($conn, $recipe_owner_id);
} else {
    // Add like
    $insert_sql = "INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();
    $status = 'added';
    
    // Update recipe owner points (+1 point)
    updateUserPoints($conn, $recipe_owner_id);
}

// Get updated like count (total)
$count_sql = "SELECT COUNT(*) as count FROM likes WHERE recipe_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $recipe_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$like_count = $count_result->fetch_assoc()['count'];

// Get count EXCLUDING recipe owner's like
$count_excluding_owner_sql = "SELECT COUNT(*) as count FROM likes WHERE recipe_id = ? AND user_id != ?";
$count_excluding_stmt = $conn->prepare($count_excluding_owner_sql);
$count_excluding_stmt->bind_param("ii", $recipe_id, $recipe_owner_id);
$count_excluding_stmt->execute();
$count_excluding_result = $count_excluding_stmt->get_result();
$count_excluding_owner = $count_excluding_result->fetch_assoc()['count'];

echo json_encode([
    'status' => $status, 
    'count' => $like_count,
    'count_excluding_owner' => $count_excluding_owner
]);

// Function to recalculate and update user points & badge
function updateUserPoints($conn, $user_id) {
    // Get like count (only approved recipes, EXCLUDING self-likes)
    $like_sql = "SELECT COUNT(*) AS like_count 
                 FROM likes l
                 JOIN recipe r ON l.recipe_id = r.id
                 WHERE r.user_id = ? AND l.user_id != ? AND r.status = 'approved'";
    $stmt = $conn->prepare($like_sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $like_count = $stmt->get_result()->fetch_assoc()['like_count'];
    
    // Get favorite count (only approved recipes, EXCLUDING self-favorites)
    $fav_sql = "SELECT COUNT(*) AS fav_count 
                FROM favorites f
                JOIN recipe r ON f.recipe_id = r.id
                WHERE r.user_id = ? AND f.user_id != ? AND r.status = 'approved'";
    $stmt = $conn->prepare($fav_sql);
    $stmt->bind_param("ii", $user_id, $user_id);
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