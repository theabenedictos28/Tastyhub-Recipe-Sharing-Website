<?php
function update_user_badge($conn, $user_id) {
    // Fetch total likes for all recipes uploaded by the user
    $sql_likes_count = "SELECT COUNT(*) AS like_count 
                        FROM likes l
                        JOIN recipe r ON l.recipe_id = r.id
                        WHERE r.user_id = ?";
    $stmt_likes = $conn->prepare($sql_likes_count);
    $stmt_likes->bind_param("i", $user_id);
    $stmt_likes->execute();
    $result_likes = $stmt_likes->get_result();
    $like_count = $result_likes->fetch_assoc()['like_count'];

    // Fetch total favorites received on user's recipes
    $sql_fav_count = "SELECT COUNT(*) AS fav_count 
                      FROM favorites f
                      JOIN recipe r ON f.recipe_id = r.id
                      WHERE r.user_id = ?";
    $stmt_fav = $conn->prepare($sql_fav_count);
    $stmt_fav->bind_param("i", $user_id);
    $stmt_fav->execute();
    $result_fav = $stmt_fav->get_result();
    $fav_count = $result_fav->fetch_assoc()['fav_count'];

    // Get total uploaded approved recipes for the user
    $sql_recipe_count = "SELECT COUNT(*) AS recipe_count 
                        FROM recipe 
                        WHERE user_id = ? AND status = 'approved'";
    $stmt_count = $conn->prepare($sql_recipe_count);
    $stmt_count->bind_param("i", $user_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $recipe_count = $result_count->fetch_assoc()['recipe_count'];

    // Calculate total points
    $total_points = ($like_count * 1) + ($fav_count * 2) + ($recipe_count * 5);

    // Badge levels (lowest → highest)
    $levels = [
        ['points' => 20,   'name' => 'Freshly Baked',    'icon' => 'img/freshly_baked.png'],
        ['points' => 75,   'name' => 'Kitchen Star',     'icon' => 'img/kitchen_star.png'],
        ['points' => 150,  'name' => 'Flavor Favorite',  'icon' => 'img/flavor_favorite.png'],
        ['points' => 300,  'name' => 'Gourmet Guru',     'icon' => 'img/gourmet_guru.png'],
        ['points' => 500,  'name' => 'Culinary Star',    'icon' => 'img/culinary_star.png'],
        ['points' => 1000, 'name' => 'Culinary Legend',  'icon' => 'img/culinary_legend.png']
    ];

    // Defaults
    $badge = 'No Badge Yet';
    $badge_icon = 'img/nobadge.png';

    // Find current badge
    foreach ($levels as $i => $level) {
        if ($total_points >= $level['points']) {
            $badge = $level['name'];
            $badge_icon = $level['icon'];
        }
    }

    // Save badge to database
    $save_badge_sql = "INSERT INTO user_badges (user_id, badge_name, badge_icon) VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE badge_name = ?, badge_icon = ?";
    $save_badge_stmt = $conn->prepare($save_badge_sql);
    $save_badge_stmt->bind_param("issss", $user_id, $badge, $badge_icon, $badge, $badge_icon);
    $save_badge_stmt->execute();
}
?>