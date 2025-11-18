<?php
session_start();
require 'db.php'; // Include database connection
require 'badge_utils.php';

// Redirect to sign-in page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Fetch user details from session
$username = htmlspecialchars($_SESSION['username']);
$email = htmlspecialchars($_SESSION['email']);
$user_id = $_SESSION['user_id'];



// Check user points for story feature
$user_points_query = $conn->prepare("SELECT total_points FROM user_badges WHERE user_id = ?");
$user_points_query->bind_param("i", $user_id);
$user_points_query->execute();
$user_points_result = $user_points_query->get_result();
$user_points_data = $user_points_result->fetch_assoc();
$user_total_points = $user_points_data ? $user_points_data['total_points'] : 0;
$can_add_story = $user_total_points >= 500;
$can_go_live = $user_total_points >= 1000; 
// Handle new live stream  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['youtube_link'], $_POST['title'])) {
    if ($can_go_live) {

        // ✅ Check if user already has an active livestream
        $active_check_sql = "SELECT id FROM livestreams WHERE user_id = ? AND ended_at IS NULL";
        $active_check_stmt = $conn->prepare($active_check_sql);
        $active_check_stmt->bind_param("i", $user_id);
        $active_check_stmt->execute();
        $active_check_result = $active_check_stmt->get_result();

        if ($active_check_result->num_rows > 0) {
            echo "<script>alert('You already have an active livestream. Please end it before starting a new one.');</script>";
        } else {
            $youtube_link = trim($_POST['youtube_link']);
            $title = trim($_POST['title']);

            if ($youtube_link && $title) {
                // ✅ Validate all YouTube link formats
                if (
                    strpos($youtube_link, 'youtube.com/embed/') !== false ||
                    strpos($youtube_link, 'youtu.be/') !== false ||
                    strpos($youtube_link, 'youtube.com/watch?v=') !== false
                ) {
                    // Convert to embed if needed
                    if (strpos($youtube_link, 'youtube.com/watch?v=') !== false) {
                        $video_id = substr($youtube_link, strpos($youtube_link, 'v=') + 2);
                        $youtube_link = 'https://www.youtube.com/embed/' . $video_id;
                    } elseif (strpos($youtube_link, 'youtu.be/') !== false) {
                        $video_id = substr($youtube_link, strrpos($youtube_link, '/') + 1);
                        $youtube_link = 'https://www.youtube.com/embed/' . $video_id;
                    }

                    // ✅ Insert new livestream
                    $stmt = $conn->prepare("
                        INSERT INTO livestreams (user_id, username, title, youtube_link, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("isss", $user_id, $username, $title, $youtube_link);
                    $stmt->execute();
                    $stmt->close();

                    // Refresh page
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    echo "<script>alert('Invalid YouTube link. Please provide a valid YouTube URL.');</script>";
                }
            }
        }
    }
}


// Handle ending a livestream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_stream_id'])) {
    $end_id = (int)$_POST['end_stream_id'];
    $caption = trim($_POST['end_caption'] ?? '');

    $stmt = $conn->prepare("
        UPDATE livestreams 
        SET is_active = 0, 
            ended_at = NOW(),
            caption = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sii", $caption, $end_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Refresh page
    header("Location: profile.php");
    exit;
}


// Fetch all active livestreams
$streams = [];
$streams_query = "SELECT l.*, u.username, u.profile_picture, ub.badge_name, ub.badge_icon 
                  FROM livestreams l 
                  JOIN users u ON l.user_id = u.id 
                  LEFT JOIN user_badges ub ON l.user_id = ub.user_id 
                  WHERE l.is_active = 1 
                  ORDER BY l.created_at DESC";

$streams_result = $conn->query($streams_query);
if ($streams_result && $streams_result->num_rows > 0) {
    while ($row = $streams_result->fetch_assoc()) {
        $streams[] = $row;
    }
}

// Initialize search variable for category filter
$categoryFilter = '';

// Check if a category filter is set in the GET request
if (isset($_GET['category'])) {
    $categoryFilter = urldecode(trim($_GET['category'])); // Decode and trim the category
}

// Check for sorting parameter, default to 'latest'
$sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'latest';


// Prepare SQL statements to check if a recipe is in favorites or likes
$check_fav_sql = "SELECT * FROM favorites WHERE user_id = ? AND recipe_id = ?";
$check_stmt = $conn->prepare($check_fav_sql);
$check_stmt->bind_param("ii", $user_id, $recipe_id);
$check_stmt->execute();
$is_favorite = $check_stmt->get_result()->num_rows > 0;

$check_layk_sql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
$check_stmt = $conn->prepare($check_layk_sql);
$check_stmt->bind_param("ii", $user_id, $recipe_id);
$check_stmt->execute();
$is_like = $check_stmt->get_result()->num_rows > 0;

// Initialize search variables from GET request
$keyword = isset($_GET['keyword']) ? urldecode(trim($_GET['keyword'])) : '';
$difficulty = isset($_GET['difficulty']) ? urldecode(trim($_GET['difficulty'])) : '';
$preparationType = isset($_GET['preparation_type']) ? urldecode(trim($_GET['preparation_type'])) : '';
$cookingTime = isset($_GET['cooking_time']) ? urldecode(trim($_GET['cooking_time'])) : '';
$servings = isset($_GET['servings']) ? (int)$_GET['servings'] : null;
$budgetLevel = isset($_GET['budget']) ? urldecode(trim($_GET['budget'])) : '';
$calories = isset($_GET['calories']) ? (int)$_GET['calories'] : null;
$fat = isset($_GET['fat']) ? (int)$_GET['fat'] : null;
$carbohydrates = isset($_GET['carbohydrates']) ? (int)$_GET['carbohydrates'] : null;
$fiber = isset($_GET['fiber']) ? (int)$_GET['fiber'] : null;
$cholesterol = isset($_GET['cholesterol']) ? (int)$_GET['cholesterol'] : null;
$sodium = isset($_GET['sodium']) ? (int)$_GET['sodium'] : null;
$protein = isset($_GET['protein']) ? (int)$_GET['protein'] : null;
$sugar = isset($_GET['sugar']) ? (int)$_GET['sugar'] : null;


// Initialize parameters and types
$params = [];
$types = ''; // Initialize $types to an empty string

// Initialize excluded ingredients
$excludedIngredients = isset($_GET['ingredient_excluded']) ? urldecode(trim($_GET['ingredient_excluded'])) : '';
$excludedIngredientsArray = [];

// Split the excluded ingredients by comma and trim whitespace
if (!empty($excludedIngredients)) {
    $excludedIngredientsArray = array_map('trim', explode(',', $excludedIngredients));
}

// Start building the SQL query to fetch recipes
$sql = "SELECT recipe.id,recipe.user_id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, 
               recipe.difficulty, recipe.preparation, recipe.budget, 
               recipe.servings, recipe.cooktime, 
               users.username,
               MAX(user_badges.badge_name) AS badge_name,
               MAX(user_badges.badge_icon) AS badge_icon,
               COUNT(DISTINCT favorites.user_id) AS favorite_count, 
               COUNT(DISTINCT likes.user_id) AS like_count
        FROM recipe 
        JOIN users ON recipe.user_id = users.id
        LEFT JOIN user_badges ON users.id = user_badges.user_id
        LEFT JOIN favorites ON recipe.id = favorites.recipe_id
        LEFT JOIN likes ON recipe.id = likes.recipe_id
        LEFT JOIN ingredients ON recipe.id = ingredients.recipe_id
        LEFT JOIN equipments ON recipe.id = equipments.recipe_id
        LEFT JOIN nutritional_info ON recipe.id = nutritional_info.recipe_id
        WHERE recipe.status = 'approved' AND recipe.archived = 0
"; 


// Check if user has any preferences
$user_has_preferences_sql = "
    SELECT COUNT(*) as total_preferences FROM (
        SELECT user_id FROM user_ingredients WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_category WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_tags WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_difflevel WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_preparation WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_cooktime WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_budgetlevel WHERE user_id = ?
        UNION ALL
        SELECT user_id FROM user_equipment WHERE user_id = ?
    ) as preferences
";

$preferences_stmt = $conn->prepare($user_has_preferences_sql);
$preferences_stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$preferences_stmt->execute();
$preferences_result = $preferences_stmt->get_result();
$user_has_preferences = $preferences_result->fetch_assoc()['total_preferences'] > 0;

if ($user_has_preferences) {
$suggested_sql = "
SELECT DISTINCT r.*,
 u.username,
  b.badge_name, 
  b.badge_icon
FROM recipe r
JOIN users u ON r.user_id = u.id
LEFT JOIN user_badges b ON u.id = b.user_id
WHERE r.status = 'approved' AND (
    r.id IN (
        SELECT recipe_id
        FROM ingredients i
        WHERE EXISTS (
            SELECT 1
            FROM user_ingredients ui
            WHERE ui.user_id = ?
            AND LOWER(i.ingredient_name) LIKE CONCAT('%', LOWER(ui.ingredient), '%')
        )
    )
    OR r.category IN (
        SELECT category FROM user_category WHERE user_id = ?
    )
    OR EXISTS (
        SELECT 1 FROM user_tags ut
        WHERE ut.user_id = ?
        AND FIND_IN_SET(LOWER(ut.tag), REPLACE(LOWER(r.tags), ' ', '')) > 0
    )
    OR r.difficulty IN (
        SELECT diff_level FROM user_difflevel WHERE user_id = ?
    )
    OR r.preparation IN (
        SELECT preparation_type FROM user_preparation WHERE user_id = ?
    )
    OR r.cooktime IN (
        SELECT cook_time FROM user_cooktime WHERE user_id = ?
    )
    OR r.budget IN (
        SELECT budget_level FROM user_budgetlevel WHERE user_id = ?
    )
    OR r.id IN (
    SELECT recipe_id
    FROM equipments e
    WHERE EXISTS (
        SELECT 1
        FROM user_equipment ue
        WHERE ue.user_id = ?
        AND LOWER(e.equipment_name) LIKE CONCAT('%', LOWER(ue.equipment_name), '%')
    )
)

)
GROUP BY r.id
ORDER BY r.created_at DESC
";

// Prepare and execute the suggested recipes statement
$suggested_stmt = $conn->prepare($suggested_sql);
$suggested_stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
} else {
    // Default suggestions: Any 5 random recipes
$suggested_sql = "
    SELECT DISTINCT r.*, 
           u.username,
           b.badge_name,
           b.badge_icon,
           COUNT(DISTINCT l.user_id) as like_count
    FROM recipe r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN likes l ON r.id = l.recipe_id
    LEFT JOIN user_badges b ON u.id = b.user_id
    WHERE r.status = 'approved'
    GROUP BY r.id
    ORDER BY RAND()
";
    
$suggested_stmt = $conn->prepare($suggested_sql);
}
$suggested_stmt->execute();
$suggested_result = $suggested_stmt->get_result();

// Define badge gradients
$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet' => 'linear-gradient(90deg, #504F4F, #555)',
];

// 🎯 Attach gradient to each suggested recipe
$suggested_with_gradients = [];
while ($row = $suggested_result->fetch_assoc()) {
    if (!empty($row['user_id'])) {
        update_user_badge($conn, $row['user_id']);
    }
    $gradient = isset($badgeGradients[$row['badge_name']])
        ? $badgeGradients[$row['badge_name']]
        : 'linear-gradient(90deg, #333, #555)'; // fallback

    $row['badge_gradient'] = $gradient;
    $suggested_with_gradients[] = $row;
}


// Initialize an array to count tags from approved recipes
$tag_counts = [];

// Initialize an array to count tags from approved recipes
$result = $conn->query("SELECT tags FROM recipe WHERE tags IS NOT NULL AND tags != '' AND status = 'approved'");

while ($row = $result->fetch_assoc()) {
    $tags = explode(',', $row['tags']); // Split tags by comma
    foreach ($tags as $tag) {
        $clean_tag = strtolower(trim($tag)); // Normalize: lowercase & trim
        if (!empty($clean_tag)) {
            if (!isset($tag_counts[$clean_tag])) {
                $tag_counts[$clean_tag] = 0;
            }
            $tag_counts[$clean_tag]++; // Increment tag count
        }
    }
}

// Sort tags by count in descending order
arsort($tag_counts);

// Get top 20 tags
$top_tags = array_slice($tag_counts, 0, 20, true);

// Fetch the latest 5=4 recipes
$latest_sql = "SELECT recipe.id,recipe.user_id,  recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, 
                      recipe.difficulty, recipe.preparation, recipe.budget, 
                      recipe.servings, recipe.cooktime, 
                      users.username, users.profile_picture,
                      MAX(user_badges.badge_name) AS badge_name,
                      MAX(user_badges.badge_icon) AS badge_icon,
                      COUNT(DISTINCT favorites.user_id) AS favorite_count, 
                      COUNT(DISTINCT likes.user_id) AS like_count
               FROM recipe 
               JOIN users ON recipe.user_id = users.id
               LEFT JOIN user_badges ON users.id = user_badges.user_id
               LEFT JOIN favorites ON recipe.id = favorites.recipe_id
               LEFT JOIN likes ON recipe.id = likes.recipe_id
               WHERE recipe.status = 'approved'
               GROUP BY recipe.id, users.username
               ORDER BY recipe.created_at DESC, recipe.id DESC
               LIMIT 10";
$latest_stmt = $conn->prepare($latest_sql);
$latest_stmt->execute();
$latest_result = $latest_stmt->get_result();


// Fetch the most popular 4 recipes
$popular_sql = "SELECT recipe.id,recipe.user_id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, 
                       recipe.difficulty, recipe.preparation, recipe.budget, 
                       recipe.servings, recipe.cooktime, 
                       users.username, users.profile_picture,
                       MAX(user_badges.badge_name) AS badge_name,
                       MAX(user_badges.badge_icon) AS badge_icon,
                       COUNT(DISTINCT favorites.user_id) AS favorite_count, 
                       COUNT(DISTINCT likes.user_id) AS like_count
                FROM recipe 
                JOIN users ON recipe.user_id = users.id
                LEFT JOIN user_badges ON users.id = user_badges.user_id
                LEFT JOIN favorites ON recipe.id = favorites.recipe_id
                LEFT JOIN likes ON recipe.id = likes.recipe_id
                WHERE recipe.status = 'approved'
                GROUP BY recipe.id, users.username
                ORDER BY like_count DESC, recipe.id DESC
                LIMIT 10";
$popular_stmt = $conn->prepare($popular_sql);
$popular_stmt->execute();
$popular_result = $popular_stmt->get_result();

// Fetch the most favorite 4 recipes
$favorite_sql = "SELECT recipe.id,recipe.user_id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, 
                        recipe.difficulty, recipe.preparation, recipe.budget, 
                        recipe.servings, recipe.cooktime, 
                        users.username, users.profile_picture,
                        MAX(user_badges.badge_name) AS badge_name,
                        MAX(user_badges.badge_icon) AS badge_icon,
                        COUNT(DISTINCT favorites.user_id) AS favorite_count, 
                        COUNT(DISTINCT likes.user_id) AS like_count
                 FROM recipe 
                 JOIN users ON recipe.user_id = users.id
                 LEFT JOIN user_badges ON users.id = user_badges.user_id
                 LEFT JOIN favorites ON recipe.id = favorites.recipe_id
                 LEFT JOIN likes ON recipe.id = likes.recipe_id
                 WHERE recipe.status = 'approved'
                 GROUP BY recipe.id, users.username
                 ORDER BY favorite_count DESC, recipe.id DESC
                 LIMIT 10";
$favorite_stmt = $conn->prepare($favorite_sql);
$favorite_stmt->execute();
$favorite_result = $favorite_stmt->get_result();


// Initialize search variables
$calories = isset($_GET['calories']) ? (int)$_GET['calories'] : null;
$caloriesRange = isset($_GET['calories_range']) ? urldecode(trim($_GET['calories_range'])) : 'lower';

$fat = isset($_GET['fat']) ? (int)$_GET['fat'] : null;
$fatRange = isset($_GET['fat_range']) ? urldecode(trim($_GET['fat_range'])) : 'lower';

$carbohydrates = isset($_GET['carbohydrates']) ? (int)$_GET['carbohydrates'] : null;
$carbohydratesRange = isset($_GET['carbohydrates_range']) ? urldecode(trim($_GET['carbohydrates_range'])) : 'lower';

$fiber = isset($_GET['fiber']) ? (int)$_GET['fiber'] : null;
$fiberRange = isset($_GET['fiber_range']) ? urldecode(trim($_GET['fiber_range'])) : 'lower';

$cholesterol = isset($_GET['cholesterol']) ? (int)$_GET['cholesterol'] : null;
$cholesterolRange = isset($_GET['cholesterol_range']) ? urldecode(trim($_GET['cholesterol_range'])) : 'lower';

$sodium = isset($_GET['sodium']) ? (int)$_GET['sodium'] : null;
$sodiumRange = isset($_GET['sodium_range']) ? urldecode(trim($_GET['sodium_range'])) : 'lower';

$protein = isset($_GET['protein']) ? (int)$_GET['protein'] : null;
$proteinRange = isset($_GET['protein_range']) ? urldecode(trim($_GET['protein_range'])) : 'lower';

$sugar = isset($_GET['sugar']) ? (int)$_GET['sugar'] : null;
$sugarRange = isset($_GET['sugar_range']) ? urldecode(trim($_GET['sugar_range'])) : 'lower';


// Add conditions based on the search criteria
$conditions = [];
if (!empty($keyword)) {
    $conditions[] = "(recipe.recipe_name LIKE ? OR ingredients.ingredient_name LIKE ? OR equipments.equipment_name LIKE ?)";
}
if (!empty($categoryFilter)) {
    $conditions[] = "recipe.category = ?";
}
if (!empty($difficulty)) {
    $conditions[] = "recipe.difficulty = ?";
}
if (!empty($preparationType)) {
    $conditions[] = "recipe.preparation = ?";
}
if (!empty($cookingTime)) {
    $conditions[] = "recipe.cooktime = ?";
}
if (!empty($servings)) {
    $conditions[] = "recipe.servings <= ?"; 
}
if (!empty($budgetLevel)) {
    $conditions[] = "recipe.budget = ?";
}
if (!empty($calories)) {
    if ($caloriesRange === 'lower') {
        $conditions[] = "nutritional_info.calories <= ?";
    } elseif ($caloriesRange === 'higher') {
        $conditions[] = "nutritional_info.calories >= ?";
    }
}
if (!empty($fat)) {
    if ($fatRange === 'lower') {
        $conditions[] = "nutritional_info.fat <= ?";
    } elseif ($fatRange === 'higher') {
        $conditions[] = "nutritional_info.fat >= ?";
    }
}
if (!empty($carbohydrates)) {
    if ($carbohydratesRange === 'lower') {
        $conditions[] = "nutritional_info.carbohydrates <= ?";
    } elseif ($carbohydratesRange === 'higher') {
        $conditions[] = "nutritional_info.carbohydrates >= ?";
    }
}
if (!empty($fiber)) {
    if ($fiberRange === 'lower') {
        $conditions[] = "nutritional_info.fiber <= ?";
    } elseif ($fiberRange === 'higher') {
        $conditions[] = "nutritional_info.fiber >= ?";
    }
}
if (!empty($cholesterol)) {
    if ($cholesterolRange === 'lower') {
        $conditions[] = "nutritional_info.cholesterol <= ?";
    } elseif ($cholesterolRange === 'higher') {
        $conditions[] = "nutritional_info.cholesterol >= ?";
    }
}
if (!empty($sodium)) {
    if ($sodiumRange === 'lower') {
        $conditions[] = "nutritional_info.sodium <= ?";
    } elseif ($sodiumRange === 'higher') {
        $conditions[] = "nutritional_info.sodium >= ?";
    }
}
if (!empty($protein)) {
    if ($proteinRange === 'lower') {
        $conditions[] = "nutritional_info.protein <= ?";
    } elseif ($proteinRange === 'higher') {
        $conditions[] = "nutritional_info.protein >= ?";
    }
}
if (!empty($sugar)) {
    if ($sugarRange === 'lower') {
        $conditions[] = "nutritional_info.sugar <= ?";
    } elseif ($sugarRange === 'higher') {
        $conditions[] = "nutritional_info.sugar >= ?";
    }
}
// Append conditions to the SQL query if any exist
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Exclude ingredients
if (!empty($excludedIngredientsArray)) {
    $excludedConditions = [];
    foreach ($excludedIngredientsArray as $ingredient) {
        // Use LIKE for the condition with wildcards
        $excludedConditions[] = "ingredients.ingredient_name LIKE ?";
    }
        // Combine into a subquery excluding those recipes
    $sql .= " AND recipe.id NOT IN (SELECT recipe_id FROM ingredients WHERE " . implode(" OR ", $excludedConditions) . ")";
}

// Prepare the parameters for excluded ingredients
foreach ($excludedIngredientsArray as $ingredient) {
    // Use wildcards to exclude any ingredient that contains the excluded term
    $params[] = '%' . $ingredient . '%'; 
    $types .= 's'; 
}
// Append GROUP BY and sorting
$sql .= " GROUP BY recipe.id";


// Apply sorting conditions
if ($sortParam === 'most_popular') {
    $sql .= " ORDER BY like_count DESC, recipe.created_at DESC"; // Sort by like count descending (Most Popular)
} elseif ($sortParam === 'most_favorite') {
    $sql .= " ORDER BY favorite_count DESC, recipe.created_at DESC"; // Sort by favorite count descending (Most Favorite)
} else {
    $sql .= " ORDER BY recipe.created_at DESC"; // Default sort by creation date
}

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

// Bind parameters dynamically based on the search criteria
if (!empty($keyword)) {
    $params[] = '%' . $keyword . '%'; // For recipe name
    $params[] = '%' . $keyword . '%'; // For ingredient name
    $params[] = '%' . $keyword . '%'; // For equipment name
    $types .= 'sss'; // 3 string parameters for keyword
}
if (!empty($categoryFilter)) {
    $params[] = $categoryFilter;
    $types .= 's';
}
if (!empty($difficulty)) {
    $params[] = $difficulty;
    $types .= 's';
}
if (!empty($preparationType)) {
    $params[] = $preparationType;
    $types .= 's';
}
if (!empty($cookingTime)) {
    $params[] = $cookingTime;
    $types .= 's';
}
if (!empty($servings)) {
    $params[] = $servings;
    $types .= 'i';
}
if (!empty($budgetLevel)) {
    $params[] = $budgetLevel; // Ensure this line is included
    $types .= 's'; // Adjust the type accordingly
}
if (!empty($calories)) {
    $params[] = $calories;
    $types .= 'i';
}
if (!empty($fat)) {
    $params[] = $fat;
    $types .= 'i';
}
if (!empty($carbohydrates)) {
    $params[] = $carbohydrates;
    $types .= 'i';
}
if (!empty($fiber)) {
    $params[] = $fiber;
    $types .= 'i';
}
if (!empty($cholesterol)) {
    $params[] = $cholesterol;
    $types .= 'i';
}
if (!empty($sodium)) {
    $params[] = $sodium;
    $types .= 'i';
}
if (!empty($protein)) {
    $params[] = $protein;
    $types .= 'i';
}
if (!empty($sugar)) {
    $params[] = $sugar;
    $types .= 'i';
}


// Bind parameters to the statement
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tasty Hub</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
            <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="css/style.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .font-nunito { font-family: 'Nunito', sans-serif; }
            [x-cloak] { display: none !important; }

             #backToTopBtn {
                    display: none;
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 99;
                           background-color: #FEA116; /* your desired orange */

                }
            /* for title latest etc and see more mobile*/

/* for card latest etc mpbile */

@media (max-width: 768px) {
  .container-fluid {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
  }

  #recipe-grid,
  #popular-recipe-grid,
  #favorite-recipe-grid {
    margin-left: 0 !important;
    margin-right: 0 !important;
    gap: 0.5rem; /* reduce spacing between cards */
  }
}

/* for tags mpbile */

    @media (max-width: 768px) {
  /* Smaller heading for tags */
  .tag-container h3 {
    font-size: 1rem !important;  /* smaller than before */
  }

  .tag-container .tags {
    font-size: 0.7rem !important;  /* smaller text */
    padding: 5px !important;   /* reduce padding */
    margin-right: 3px;              /* slight spacing between tags */
    margin-bottom: 3px;             /* space between rows */
    display: inline-block;          /* ensures wrapping */
  }
}


        </style>
        <style type="text/tailwindcss">
        body {
            font-family: 'Poppins', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        .recipe-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border:none
        }
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
        </head>

    <body class="bg-orange-50 mt-8 py-8">
<!-- Spinner Start -->
<div id="spinner" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-white opacity-0 pointer-events-none transition-opacity duration-500">
  <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
  <span class="sr-only">Loading...</span>
</div>
<!-- Spinner End -->

<script>
  // Show spinner
  function showSpinner() {
    const spinner = document.getElementById("spinner");
    spinner.classList.remove("opacity-0", "pointer-events-none");
    spinner.classList.add("opacity-100");
  }

  // Hide spinner
  function hideSpinner() {
    const spinner = document.getElementById("spinner");
    spinner.classList.remove("opacity-100");
    spinner.classList.add("opacity-0", "pointer-events-none");
  }

  // Example: auto-hide after 2s
  window.addEventListener("load", () => {
    setTimeout(() => {
      hideSpinner();
    }, 2000);
  });
</script>


    <header :class="{ '-translate-y-full': !showNav, 'translate-y-0': showNav }" 
            @scroll.window="
                if (window.scrollY > lastScrollY) {
                    showNav = false;
                } else {
                    showNav = true;
                }
                lastScrollY = window.scrollY;
            " 
            class="bg-orange-50 shadow-md fixed top-0 left-0 right-0 z-50 transition-transform duration-300" 
            x-data="{ 
                openDropdown: '', 
                showNav: true, 
                lastScrollY: window.scrollY,
                mobileMenuOpen: false,
                mobileSearchOpen: false,
                mobileCategoryOpen: false,
                mobileSortOpen: false,
                mobileProfileOpen: false
            }">
        <div class="mx-auto px-3 lg:px-5">
            <div class="flex items-center justify-between py-3 gap-3">
                <!-- Logo -->
                <a href="dashboard.php" style="text-decoration:none;">
                <div class="flex items-center">
                    <img alt="Tasty Hub logo" class="h-12 w-12 mr-2" src="img/logo_new.png"/>
                    <span class="hidden sm:inline lg:text-3xl text-xl font-extrabold text-orange-400 font-nunito">Tasty Hub</span>
                </div>
            </a>

            <!-- Search Bar (always visible, adapts width) -->
            <div class="flex-1 max-w-md">
                <form action="latest.php" method="GET">
                    <input name="keyword" 
                           class="w-full bg-white border border-gray-200 rounded-full py-2 px-4 
                                  text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent
                                  text-sm md:text-base"
                           placeholder="Search for recipes..." 
                           type="text"/>
                </form>
            </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="lg:hidden p-2 rounded-md text-orange-400 hover:bg-orange-100 focus:outline-none">
                    <span class="material-icons text-2xl">
                        <span x-show="!mobileMenuOpen">menu</span>
                        <span x-show="mobileMenuOpen" x-cloak>close</span>
                    </span>
                </button>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-6">

                    <!-- Navigation Items -->
                    <nav class="flex items-center space-x-6">
                        <!-- Home -->
                        <a class="flex items-center text-orange-400 hover:text-orange-500 transition-colors" href="dashboard.php">
                            <span class="material-icons">home</span>
                            <span class="ml-2">Home</span>
                        </a>

                        <!-- Search Dropdown -->
                        <div class="relative" x-on:click.away="if (openDropdown === 'search') openDropdown = ''">
                            <button @click="openDropdown = openDropdown === 'search' ? '' : 'search'" 
                                    class="flex items-center text-orange-400 hover:text-orange-500 transition-colors">
                                <span class="material-icons">search</span>
                                <span class="ml-2">Search</span>
                                <span :class="{ 'rotate-180': openDropdown === 'search' }" 
                                      class="material-icons transition-transform duration-200">arrow_drop_down</span>
                            </button>
                            <div class="absolute right-0 z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" 
                                 x-cloak x-show="openDropdown === 'search'" 
                                 x-transition:enter="transition ease-out duration-100" 
                                 x-transition:enter-start="transform opacity-0 scale-95" 
                                 x-transition:enter-end="transform opacity-100 scale-100" 
                                 x-transition:leave="transition ease-in duration-75" 
                                 x-transition:leave-start="transform opacity-100 scale-100" 
                                 x-transition:leave-end="transform opacity-0 scale-95">
                                <div aria-labelledby="options-menu" aria-orientation="vertical" class="py-1" role="menu">
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="search_ingredients.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">restaurant_menu</span> Search by Ingredients
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="advanced_search.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">manage_search</span> Advanced Search
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Category Dropdown -->
                        <div class="relative" x-on:click.away="if (openDropdown === 'category') openDropdown = ''">
                            <button @click="openDropdown = openDropdown === 'category' ? '' : 'category'" 
                                    class="flex items-center text-orange-400 hover:text-orange-500 transition-colors">
                                <span class="material-icons">widgets</span>
                                <span class="ml-2">Category</span>
                                <span :class="{ 'rotate-180': openDropdown === 'category' }" 
                                      class="material-icons transition-transform duration-200">arrow_drop_down</span>
                            </button>
                            <div class="absolute right-0 z-10 mt-2 w-[40rem] rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" 
                                 x-cloak x-show="openDropdown === 'category'" 
                                 x-transition:enter="transition ease-out duration-100" 
                                 x-transition:enter-start="transform opacity-0 scale-95" 
                                 x-transition:enter-end="transform opacity-100 scale-100" 
                                 x-transition:leave="transition ease-in duration-75" 
                                 x-transition:leave-start="transform opacity-100 scale-100" 
                                 x-transition:leave-end="transform opacity-0 scale-95">
                                <div class="grid grid-cols-3 gap-2 p-3">
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Main%20Dish&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">restaurant</span>
                                        <span class="mt-1 text-center">Main Dish</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Appetizers%20%26%20Snacks&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">tapas</span>
                                        <span class="mt-1 text-center">Appetizers & Snacks</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Soups%20%26%20Stews&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">ramen_dining</span>
                                        <span class="mt-1 text-center">Soups & Stews</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Salads%20%26%20Sides&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">dinner_dining</span>
                                        <span class="mt-1 text-center">Salads & Sides</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Brunch&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">brunch_dining</span>
                                        <span class="mt-1 text-center">Brunch</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Desserts%20%26%20Sweets&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">icecream</span>
                                        <span class="mt-1 text-center">Desserts & Sweets</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Drinks%20%26%20Beverages&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">local_bar</span>
                                        <span class="mt-1 text-center">Drinks & Beverages</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Vegetables&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">grass</span>
                                        <span class="mt-1 text-center">Vegetables</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Occasional&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">cake</span>
                                        <span class="mt-1 text-center">Occasional</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="latest.php?category=Healthy%20%26%20Special%20Diets&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">health_and_safety</span>
                                        <span class="mt-1 text-center">Healthy & Special Diets</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="relative" x-on:click.away="if (openDropdown === 'sort') openDropdown = ''">
                            <button @click="openDropdown = openDropdown === 'sort' ? '' : 'sort'" 
                                    class="flex items-center text-orange-400 hover:text-orange-500 transition-colors">
                                <span class="material-icons">sort</span>
                                <span class="ml-2">Sort By</span>
                                <span :class="{ 'rotate-180': openDropdown === 'sort' }" 
                                      class="material-icons transition-transform duration-200">arrow_drop_down</span>
                            </button>
                            <div class="absolute right-0 z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" 
                                 x-cloak x-show="openDropdown === 'sort'" 
                                 x-transition:enter="transition ease-out duration-100" 
                                 x-transition:enter-start="transform opacity-0 scale-95" 
                                 x-transition:enter-end="transform opacity-100 scale-100" 
                                 x-transition:leave="transition ease-in duration-75" 
                                 x-transition:leave-start="transform opacity-100 scale-100" 
                                 x-transition:leave-end="transform opacity-0 scale-95">
                                <div aria-labelledby="options-menu" aria-orientation="vertical" class="py-1" role="menu">
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">new_releases</span> Latest
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php?sort=most_popular" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">trending_up</span> Most Popular
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php?sort=most_favorite" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">favorite</span> Most Favorite
                                    </a>
                                </div>
                            </div>
                        </div>
                                            <!-- Favorites -->
                    <a class="flex items-center text-orange-400 transition-colors" 
                       href="favorites.php">
                        <span class="material-icons mr-3 text-orange-400 hover:text-orange-500">bookmark</span>
                    </a>

                        <!-- Profile Dropdown -->
                        <div class="relative" x-on:click.away="if (openDropdown === 'profile') openDropdown = ''">
                            <button @click="openDropdown = openDropdown === 'profile' ? '' : 'profile'" 
                                    class="text-orange-400 hover:text-orange-700-orange-500 transition-colors">
                                <span class="material-icons text-orange-400 hover:text-orange-500 text-3xl">account_circle</span>
                            </button>
                            <div class="absolute right-0 z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" 
                                 x-cloak x-show="openDropdown === 'profile'" 
                                 x-transition:enter="transition ease-out duration-100" 
                                 x-transition:enter-start="transform opacity-0 scale-95" 
                                 x-transition:enter-end="transform opacity-100 scale-100" 
                                 x-transition:leave="transition ease-in duration-75" 
                                 x-transition:leave-start="transform opacity-100 scale-100" 
                                 x-transition:leave-end="transform opacity-0 scale-95">
                                <div aria-labelledby="options-menu" aria-orientation="vertical" class="py-1" role="menu">
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="profile.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">account_circle</span> Profile
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="dashboard.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">dashboard</span> Dashboard
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="submit_recipe.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">post_add</span> Submit a Recipe
                                    </a>
                                    <div class="border-t border-gray-100"></div>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="logout.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">logout</span> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="lg:hidden" 
                 x-show="mobileMenuOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">

                <!-- Mobile Navigation Links -->
                <div class="px-4 py-3 border-t border-orange-200">
                    <!-- Home -->
                    <a class="flex items-center py-3 px-3 text-orange-400 hover:bg-orange-100 rounded-lg transition-colors" 
                       href="dashboard.php">
                        <span class="material-icons mr-3">home</span>
                        <span>Home</span>
                    </a>

                    <!-- Search Options Dropdown -->
                    <div class="space-y-1">
                        <button @click="mobileSearchOpen = !mobileSearchOpen" 
                                class="w-full flex items-center justify-between py-3 px-3 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <span class="material-icons mr-3 text-orange-400">search</span>
                                <span class="text-orange-400">Search</span>
                            </div>
                            <span :class="{ 'rotate-180': mobileSearchOpen }" 
                                  class="material-icons transition-transform duration-200 text-orange-400">arrow_drop_down</span>
                        </button>
                        <div x-show="mobileSearchOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="search_ingredients.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">restaurant_menu</span>
                                <span class="text-sm">Search by Ingredients</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="advanced_search.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">manage_search</span>
                                <span class="text-sm">Advanced Search</span>
                            </a>
                        </div>
                    </div>

                    <!-- Categories Dropdown -->
                    <div class="space-y-1">
                        <button @click="mobileCategoryOpen = !mobileCategoryOpen" 
                                class="w-full flex items-center justify-between py-3 px-3 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <span class="material-icons mr-3 text-orange-400">widgets</span>
                                <span class="text-orange-400">Categories</span>
                            </div>
                            <span :class="{ 'rotate-180': mobileCategoryOpen }" 
                                  class="material-icons transition-transform duration-200 text-orange-400">arrow_drop_down</span>
                        </button>
                        <div x-show="mobileCategoryOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <div class="grid grid-cols-2 gap-2 ml-6 mt-2">
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Main%20Dish&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">restaurant</span>
                                    <span class="text-xs">Main Dish</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Appetizers%20%26%20Snacks&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">tapas</span>
                                    <span class="text-xs">Appetizers</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Soups%20%26%20Stews&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">ramen_dining</span>
                                    <span class="text-xs">Soups</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Salads%20%26%20Sides&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">dinner_dining</span>
                                    <span class="text-xs">Salads</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Brunch&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">brunch_dining</span>
                                    <span class="text-xs">Brunch</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Desserts%20%26%20Sweets&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">icecream</span>
                                    <span class="text-xs">Desserts</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Drinks%20%26%20Beverages&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">local_bar</span>
                                    <span class="text-xs">Drinks</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Vegetables&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">grass</span>
                                    <span class="text-xs">Vegetables</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Occasional&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">cake</span>
                                    <span class="text-xs">Occasional</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="latest.php?category=Healthy%20%26%20Special%20Diets&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">health_and_safety</span>
                                    <span class="text-xs">Healthy</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sort Options Dropdown -->
                    <div class="space-y-1">
                        <button @click="mobileSortOpen = !mobileSortOpen" 
                                class="w-full flex items-center justify-between py-3 px-3 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <span class="material-icons mr-3 text-orange-400">sort</span>
                                <span class="text-orange-400">Sort By</span>
                            </div>
                            <span :class="{ 'rotate-180': mobileSortOpen }" 
                                  class="material-icons transition-transform duration-200 text-orange-400">arrow_drop_down</span>
                        </button>
                        <div x-show="mobileSortOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="latest.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">new_releases</span>
                                <span class="text-sm">Latest</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="latest.php?sort=most_popular">
                                <span class="material-icons mr-3 text-orange-400 text-sm">trending_up</span>
                                <span class="text-sm">Most Popular</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="latest.php?sort=most_favorite">
                                <span class="material-icons mr-3 text-orange-400 text-sm">favorite</span>
                                <span class="text-sm">Most Favorite</span>
                            </a>
                        </div>
                    </div>
                                        <!-- Favorites -->
                    <a class="flex items-center px-3 py-3 text-orange-400 transition-colors" 
                       href="favorites.php">
                        <span class="material-icons mr-3 text-orange-400 hover:text-orange-500">bookmark</span>
                        <span>Favorites</span>  
                    </a>

                    <!-- Profile Options Dropdown -->
                    <div class="space-y-1">
                        <button @click="mobileProfileOpen = !mobileProfileOpen" 
                                class="w-full flex items-center justify-between py-3 px-3 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <span class="material-icons mr-3 text-orange-400">person</span>
                                <span class="text-orange-400">Profile</span>
                            </div>
                            <span :class="{ 'rotate-180': mobileProfileOpen }" 
                                  class="material-icons transition-transform duration-200 text-orange-400">arrow_drop_down</span>
                        </button>
                        <div x-show="mobileProfileOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="profile.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">account_circle</span>
                                <span class="text-sm">Profile</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="dashboard.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">dashboard</span>
                                <span class="text-sm">Dashboard</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="submit_recipe.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">post_add</span>
                                <span class="text-sm">Submit a Recipe</span>
                            </a>
                            <div class="border-t border-gray-100 ml-6 my-2"></div>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="logout.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">logout</span>
                                <span class="text-sm">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>


















<!-- Enhanced Stories Section HTML -->
<div class="stories-container mt-8 mb-2 px-10 lg:px-18" x-data="storiesApp()">
    <div class="flex items-center justify-between">
<h1 class="text-md lg:text-2xl md:text-lg font-bold flex items-center gap-2">
  <span class="material-icons text-red-600 text-xl lg:text-3xl">restaurant_menu</span>
  Today's Taste
</h1>
        <?php if ($can_add_story): ?>
        <button @click="openAddStoryModal" 
                class="bg-orange-400 hover:bg-orange-500 text-white px-3 py-2 rounded-full text-xs font-medium transition-colors flex items-center gap-2 shadow-md">
            <span class="material-icons text-lg">add_circle</span>
            <span class="hidden sm:inline">Add Taste</span>
        </button>
        <?php else: ?>
        <div class="text-xs sm:text-sm text-gray-600 bg-gray-100 px-3 py-2 rounded-full flex items-center gap-1">
            <span class="material-icons text-base">lock</span>
            <span class="hidden sm:inline">Need 500 points to unlock (<?php echo $user_total_points; ?>/500)</span>
            <span class="sm:hidden"><?php echo $user_total_points; ?>/500</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stories Carousel -->
    <div class="relative">
       

        <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide scroll-smooth" 
             x-ref="storiesCarousel"
             @scroll="updateScrollButtons">
            <!-- Loading state -->
            <template x-if="loading">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-full animate-pulse"></div>
                    <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-full animate-pulse"></div>
                    <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-full animate-pulse"></div>
                </div>
            </template>

            <!-- Empty state -->
            <template x-if="!loading && stories.length === 0">
                <div class="w-full text-center py-6 text-gray-500">
                    <p class="text-sm">No taste available yet</p>
                    <?php if ($can_add_story): ?>
                    <p class="text-xs mt-1">Be the first to share a taste!</p>
                    <?php endif; ?>
                </div>
            </template>

            <!-- Stories -->
<template x-for="userStory in stories" :key="userStory.user_id">
    <div class="flex-shrink-0 text-center w-24">
        <div class="relative cursor-pointer group mx-auto mt-2" @click="viewUserStories(userStory)">
            <!-- Ring for unviewed stories -->
            <div 
                :class="userStory.has_unviewed ? 'ring-4 ring-orange-400' : 'ring-2 ring-gray-300'" 
                class="relative w-20 h-20 rounded-full overflow-hidden bg-white p-[2px] transition-transform group-hover:scale-105 shadow-sm flex items-center justify-center mx-auto">
                
                <img 
                    :src="userStory.profile_picture || 'img/no_profile.png'" 
                    :alt="userStory.username"
                    @error="$event.target.src='img/no_profile.png'"
                    class="w-full h-full object-cover rounded-full">
            </div>
        </div>
<p class="text-xs text-center mt-2 truncate w-24 font-medium" 
   x-text="userStory.user_id === <?php echo $user_id; ?> ? 'Your Taste' : userStory.username">
</p>
    </div>
</template>

        </div>
    </div>

    <!-- Add Story Modal -->
    <div x-show="showAddStoryModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
         @click.self="closeAddStoryModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl" 
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Add Taste</h2>
                    <button @click="closeAddStoryModal" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitStory" enctype="multipart/form-data">
                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image *</label>
                        <div class="relative">
                            <input type="file" 
                                    accept=".jpg,.jpeg,.png"
                                   @change="handleImageSelect"
                                   class="hidden" 
                                   x-ref="imageInput"
                                   >
                            <button type="button" 
                                    @click="$refs.imageInput.click()"
                                    class="w-full border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-orange-400 transition-colors bg-gray-50">
                                <template x-if="!imagePreview">
                                    <div>
                                        <span class="material-icons text-4xl text-gray-400 mb-2 block">add_photo_alternate</span>
                                        <p class="text-gray-600 font-medium">Click to upload image</p>
                                        <p class="text-xs text-gray-500 mt-1">(JPG, PNG, GIF)</p>
                                    </div>
                                </template>
                                <template x-if="imagePreview">
                                    <div class="relative">
                                        <img :src="imagePreview" class="max-h-48 mx-auto rounded-lg">
                                        <div class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md">
                                            <span class="material-icons text-orange-400">check_circle</span>
                                        </div>
                                    </div>
                                </template>
                            </button>
                            <!-- ✅ ADD THIS ERROR MESSAGE -->
                        <template x-if="imageError">
                            <p class="mt-2 text-sm text-red-600" x-text="imageError"></p>
                        </template>
                        </div>
                    </div>

                    <!-- Caption -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Caption (optional)</label>
                        <textarea x-model="storyCaption"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-400 focus:border-transparent resize-none"
                                  rows="3"
                                  maxlength="200"
                                  placeholder="Share what's on your mind..."></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-500">Add a caption to your taste</p>
                            <p class="text-xs text-gray-500" x-text="`${storyCaption.length}/200`"></p>
                        </div>
                    </div>

                                       <!-- Link to Recipe (optional) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Link to Recipe (optional)</label>
                        <input type="url" 
                               x-model="storyRecipeLink"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                               placeholder="https://tastyhub.free.nf/recipe_details.php?id=3">
                        <p class="text-xs text-gray-500 mt-1">Link your taste to one of your recipes</p>
                    </div>


                    <!-- Submit Button -->
                    <button type="submit" 
                            :disabled="uploading"
                            :class="uploading ? 'bg-gray-400 cursor-not-allowed' : 'bg-orange-400 hover:bg-orange-500'"
                            class="w-full text-white font-medium py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-md">
                        <template x-if="!uploading">
                            <div class="flex items-center gap-2">
                                <span class="material-icons">send</span>
                                <span>Post</span>
                            </div>
                        </template>
                        <template x-if="uploading">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Uploading...</span>
                            </div>
                        </template>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- View Stories Modal -->
    <div x-show="showViewStoryModal" 
         x-cloak
         class="fixed inset-0 bg-black z-50 flex items-center justify-center"
         @click.self="closeViewStoryModal"
         @keydown.escape.window="closeViewStoryModal"
         @keydown.arrow-left.window="previousStory"
         @keydown.arrow-right.window="nextStory"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="relative w-full h-full max-w-lg mx-auto flex items-center justify-center" @click.stop>
            <!-- Close button -->
            <button @click="closeViewStoryModal" 
                    class="absolute top-4 right-4 z-20 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full p-2 transition-all">
                <span class="material-icons text-3xl">close</span>
            </button>

            <!-- Previous button -->
            <button @click="previousStory" 
                    x-show="currentStoryIndex > 0"
                    class="absolute left-4 z-20 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full p-3 transition-all hover:scale-110">
                <span class="material-icons text-2xl">chevron_left</span>
            </button>

            <!-- Next button -->
            <button @click="nextStory" 
                    x-show="currentUserStories && currentStoryIndex < currentUserStories.stories.length - 1"
                    class="absolute right-4 z-20 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full p-3 transition-all hover:scale-110">
                <span class="material-icons text-2xl">chevron_right</span>
            </button>

            <!-- Story Content -->
            <template x-if="currentUserStories && currentUserStories.stories[currentStoryIndex]">
                <div class="w-full h-full flex flex-col bg-black">
                    <!-- Progress bars -->
                    <div class="flex gap-1 px-2 pt-2 absolute top-0 left-0 right-0 z-10">
                        <template x-for="(story, index) in currentUserStories.stories" :key="story.id">
                            <div class="flex-1 h-1 bg-gray-600 rounded-full overflow-hidden">
                                <div class="h-full bg-white transition-all duration-100"
                                 :style="index === currentStoryIndex ? `width: ${progressWidth}%` : (index < currentStoryIndex ? 'width: 100%' : 'width: 0%')">
                            </div>

                            </div>
                        </template>
                    </div>

                    <!-- User info header -->
                    <div class="absolute top-6 left-0 right-0 z-10 px-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img :src="currentUserStories.profile_picture || 'img/no_profile.png'" 
                                 @error="$event.target.src='img/no_profile.png'"
                                 class="w-10 h-10 rounded-full border-2 border-white object-cover">
                            <div>
                                <div class="flex items-center gap-1 mb-1">
                                    <a 
                                        :href="'userprofile.php?username=' + encodeURIComponent(currentUserStories.username)"
                                        class="font-bold text-sm hover:opacity-80 transition duration-150 inline-block"
                                        :style="`background: ${currentUserStories.badge_gradient || 'linear-gradient(90deg, #fff, #fff)'}; -webkit-background-clip: text; -webkit-text-fill-color: transparent;`"
                                        x-text="'@' + currentUserStories.username">
                                    </a>
                                    <template x-if="currentUserStories.badge_icon && currentUserStories.badge_name !== 'No Badge Yet'">
                                        <img :src="currentUserStories.badge_icon" 
                                             alt="Badge" 
                                             class="w-6 h-5 inline-block">
                                    </template>
                                </div>
                                <p class="text-white text-xs opacity-75" 
                                   x-text="formatTimeAgo(currentUserStories.stories[currentStoryIndex].created_at)"></p>
                            </div>
                        </div>
                    </div>

                   <div class="flex-1 flex items-center justify-center px-4"
                         @pointerdown="stopStoryTimer()"
                         @pointerup="startStoryTimer()"
                         @pointercancel="startStoryTimer()"
                         @touchstart.prevent="stopStoryTimer()"
                         @touchend.prevent="startStoryTimer()"
                         @touchcancel.prevent="startStoryTimer()">


                        <img :src="currentUserStories.stories[currentStoryIndex].image" 
                             class="max-w-full max-h-full object-contain rounded-lg">
                    </div>


                    <!-- Bottom info -->
                    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black via-black/50 to-transparent">
                        <!-- Caption -->
                        <template x-if="currentUserStories.stories[currentStoryIndex].caption">
                            <p class="text-white text-sm mb-3" x-text="currentUserStories.stories[currentStoryIndex].caption"></p>
                        </template>
                        
                      <!-- Views + Delete (aligned right) -->
                    <template x-if="currentUserStories.user_id === <?php echo $user_id; ?>">
                        <div class="flex items-center justify-between text-white text-sm mt-1">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-lg">visibility</span>
                                <span x-text="currentUserStories.stories[currentStoryIndex].view_count + ' views'"></span>
                            </div>
                            <button @click="deleteCurrentStory"
                                    class="flex items-center gap-1 hover:text-red-400 bg-black/50 px-3 py-1.5 rounded-full transition-all">
                                <span class="material-icons text-base">delete</span>
                                <span>Delete</span>
                            </button>
                        </div>
                    </template>

                        
                       <!-- Recipe link -->
                    <template x-if="currentUserStories.stories[currentStoryIndex].recipe_link">
                        <a :href="currentUserStories.stories[currentStoryIndex].recipe_link"
                           class="mt-2 inline-flex items-center gap-2 bg-orange-400 hover:bg-orange-500 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors">
                            <span class="material-icons text-lg">restaurant</span>
                            <span>View Recipe</span>
                        </a>
                    </template>

                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script>
function storiesApp() {
    return {
        stories: [],
        loading: false,
        showAddStoryModal: false,
        showViewStoryModal: false,
        imagePreview: null,
        storyCaption: '',
        storyRecipeLink: '',
        uploading: false,
        imageError: '', 
        currentUserStories: null,
        currentStoryIndex: 0,
        canScrollLeft: false,
        canScrollRight: false,
        storyTimer: null,
        storyDuration: 5000, // 5 seconds per story
        progressWidth: 0,
         badgeGradients: {
            'Freshly Baked': 'linear-gradient(190deg, yellow, brown)',
            'Kitchen Star': 'linear-gradient(50deg, yellow, green)',
            'Flavor Favorite': 'linear-gradient(180deg, darkgreen, yellow)',
            'Gourmet Guru': 'linear-gradient(90deg, darkviolet, gold)',
            'Culinary Star': 'linear-gradient(180deg, red, blue)',
            'Culinary Legend': 'linear-gradient(180deg, red, gold)',
            'No Badge Yet': 'linear-gradient(90deg, #7a7a7a, #9c9c9c)'
        },

        init() {
            this.loadStories();
            this.$nextTick(() => {
                this.updateScrollButtons();
            });
        },

        // Helper function to toggle chat button visibility
        toggleChatButton(show) {
            const chatButton = document.getElementById('chatButton');
            if (chatButton) {
                chatButton.style.display = show ? 'block' : 'none';
            }
        },

       async loadStories() {
            this.loading = true;
            try {
                const response = await fetch('view_story.php?get_stories=1');
                const data = await response.json();
                if (data.success) {
                   const currentUserId = <?php echo $user_id; ?>; // ✅ your logged-in user's ID

                    // Map with badge gradients
                    let allStories = data.stories.map(story => {
                        const badgeName = story.badge_name || 'No Badge Yet';
                        const gradient = this.badgeGradients[badgeName] || this.badgeGradients['No Badge Yet'];
                        return {
                            ...story,
                            badge_gradient: gradient
                        };
                    });

                    // ✅ Move your own story to the top
                    const userStory = allStories.find(s => s.user_id === currentUserId);
                    if (userStory) {
                        allStories = [userStory, ...allStories.filter(s => s.user_id !== currentUserId)];
                    }

                    this.stories = allStories;

                    
                    this.$nextTick(() => {
                        this.updateScrollButtons();
                    });
                }
            } catch (error) {
                console.error('Error loading stories:', error);
            } finally {
                this.loading = false;
            }
        },

        updateScrollButtons() {
            const carousel = this.$refs.storiesCarousel;
            if (carousel) {
                this.canScrollLeft = carousel.scrollLeft > 0;
                this.canScrollRight = carousel.scrollLeft < (carousel.scrollWidth - carousel.clientWidth - 10);
            }
        },

        scrollLeft() {
            const carousel = this.$refs.storiesCarousel;
            if (carousel) {
                carousel.scrollBy({ left: -300, behavior: 'smooth' });
                setTimeout(() => this.updateScrollButtons(), 300);
            }
        },

        scrollRight() {
            const carousel = this.$refs.storiesCarousel;
            if (carousel) {
                carousel.scrollBy({ left: 300, behavior: 'smooth' });
                setTimeout(() => this.updateScrollButtons(), 300);
            }
        },

        openAddStoryModal() {
            this.showAddStoryModal = true;
            this.imagePreview = null;
            this.storyCaption = '';
            this.storyRecipeLink = '';

                document.body.style.overflow = 'hidden';
                            this.toggleChatButton(false); // Hide chat button


        },

        closeAddStoryModal() {
            this.showAddStoryModal = false;
                document.body.style.overflow = '';


        },

         imageError: '',

        // Simple image handler
        handleImageSelect(event) {
            const file = event.target.files[0];
            this.imageError = '';
            
            if (!file) {
                this.imagePreview = null;
                return;
            }

            // Basic validation
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type.toLowerCase())) {
                this.imageError = 'Invalid file type. Only JPG, JPEG, and PNG are allowed.';
                this.imagePreview = null;
                event.target.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                this.imageError = 'Image size must be less than 10MB.';
                this.imagePreview = null;
                event.target.value = '';
                return;
            }

            // ✅ Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result; // Alpine.js reactive property
            };
            reader.readAsDataURL(file);
        },


        async submitStory(event) {
            this.imageError = '';
            const imageFile = this.$refs.imageInput.files[0];
            
            // Simple required check
            if (!imageFile) {
                this.imageError = 'Image is required.';
                return;
            }

           const formData = new FormData();
            formData.append('story_image', imageFile);
            formData.append('caption', this.storyCaption.trim());

             if (this.storyRecipeLink.trim()) {
                formData.append('recipe_link', this.storyRecipeLink.trim());
            }


            this.uploading = true;

            try {
                const response = await fetch('add_story.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.closeAddStoryModal();
                    await this.loadStories();
                    
                    // Show success notification
                    this.showNotification('Story added successfully! 🎉', 'success');
                } else {
                    alert(data.message || 'Failed to add story');
                }
            } catch (error) {
                console.error('Error submitting story:', error);
                alert('An error occurred while uploading');
            } finally {
                this.uploading = false;
            }
            },

             // Clear errors when modal closes
        closeAddStoryModal() {
            this.showAddStoryModal = false;
            this.imagePreview = null;
            this.storyCaption = '';
            this.storyRecipeLink = '';
            this.imageError = '';
            document.body.style.overflow = '';
                        this.toggleChatButton(true); // Show chat button

        },

               viewUserStories(userStory) {
            this.currentUserStories = userStory;
            this.currentStoryIndex = 0;
            this.showViewStoryModal = true;
            document.body.style.overflow = 'hidden'; // Disable scrolling
            this.markStoryAsViewed(userStory.stories[0].id);
            this.startStoryTimer(); // ⬅ start the timer
                                        this.toggleChatButton(false); // Hide chat button


        },

               closeViewStoryModal() {
            this.stopStoryTimer();
            this.showViewStoryModal = false;
            document.body.style.overflow = ''; // Re-enable scrolling
            this.currentUserStories = null;
            this.currentStoryIndex = 0;
            this.loadStories();
                                        this.toggleChatButton(true); // Hide chat button

        },


         nextStory() {
                this.stopStoryTimer();

                if (this.currentUserStories && this.currentStoryIndex < this.currentUserStories.stories.length - 1) {
                    this.currentStoryIndex++;
                    this.progressWidth = 0; // Reset progress immediately
                    this.$nextTick(() => {
                        this.waitForImageLoad().then(() => {
                            this.markStoryAsViewed(this.currentUserStories.stories[this.currentStoryIndex].id);
                            this.startStoryTimer();
                        });
                    });
                } else {
                    const currentUserIndex = this.stories.findIndex(s => s.user_id === this.currentUserStories.user_id);
                    if (currentUserIndex < this.stories.length - 1) {
                        const nextUser = this.stories[currentUserIndex + 1];
                        this.currentUserStories = nextUser;
                        this.currentStoryIndex = 0;
                        this.progressWidth = 0;

                        this.$nextTick(() => {
                            this.waitForImageLoad().then(() => {
                                this.markStoryAsViewed(nextUser.stories[0].id);
                                this.startStoryTimer();
                            });
                        });
                    } else {
                        this.closeViewStoryModal();
                    }
                }
            },


            previousStory() {
                    this.stopStoryTimer();

                    if (this.currentStoryIndex > 0) {
                        this.currentStoryIndex--;
                        this.progressWidth = 0; // Reset progress immediately
                        this.$nextTick(() => {
                            this.waitForImageLoad().then(() => {
                                this.markStoryAsViewed(this.currentUserStories.stories[this.currentStoryIndex].id);
                                this.startStoryTimer();
                            });
                        });
                    } else {
                        const currentUserIndex = this.stories.findIndex(s => s.user_id === this.currentUserStories.user_id);
                        if (currentUserIndex > 0) {
                            const prevUser = this.stories[currentUserIndex - 1];
                            this.currentUserStories = prevUser;

                            this.$nextTick(() => {
                                this.currentStoryIndex = prevUser.stories.length - 1;
                                this.progressWidth = 0;
                                this.waitForImageLoad().then(() => {
                                    this.markStoryAsViewed(prevUser.stories[this.currentStoryIndex].id);
                                    this.startStoryTimer();
                                });
                            });
                        }
                    }
                },


// Add this new helper function
waitForImageLoad() {
    return new Promise((resolve) => {
        // Correct selector for the story image
        const img = document.querySelector('.flex-1 img');
        if (!img) {
            resolve();
            return;
        }
        
        if (img.complete) {
            resolve();
        } else {
            img.onload = () => resolve();
            img.onerror = () => resolve(); // Resolve even on error to prevent hanging
            // Timeout fallback in case image never loads
            setTimeout(() => resolve(), 2000);
        }
    });
},

            startStoryTimer() {
        this.stopStoryTimer(); // clear any previous timer
        this.progressWidth = 0;

        this.storyTimer = setInterval(() => {
            this.progressWidth += 100 / (this.storyDuration / 100);
            if (this.progressWidth >= 100) {
                this.nextStory();
            }
        }, 100);
    },

        stopStoryTimer() {
            if (this.storyTimer) {
                clearInterval(this.storyTimer);
                this.storyTimer = null;
            }
        },


        async markStoryAsViewed(storyId) {
            try {
                const formData = new FormData();
                formData.append('story_id', storyId);
                
                await fetch('view_story.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error marking story as viewed:', error);
            }
        },

        async deleteCurrentStory() {
            if (!confirm('Are you sure you want to delete this story?')) {
                return;
            }

            const storyId = this.currentUserStories.stories[this.currentStoryIndex].id;

            try {
                const formData = new FormData();
                formData.append('story_id', storyId);

                const response = await fetch('delete_story.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Remove the story from the current array
                    this.currentUserStories.stories.splice(this.currentStoryIndex, 1);
                    
                    // If no more stories for this user, close modal
                    if (this.currentUserStories.stories.length === 0) {
                        this.closeViewStoryModal();
                    } else if (this.currentStoryIndex >= this.currentUserStories.stories.length) {
                        // If we deleted the last story, go to previous
                        this.currentStoryIndex = this.currentUserStories.stories.length - 1;
                    }
                    
                    this.showNotification('Story deleted successfully', 'success');
                } else {
                    alert(data.message || 'Failed to delete story');
                }
            } catch (error) {
                console.error('Error deleting story:', error);
                alert('An error occurred while deleting');
            }
        },

        formatTimeAgo(timestamp) {
            const now = new Date();
            const storyTime = new Date(timestamp);
            const diff = Math.floor((now - storyTime) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        },

        showNotification(message, type = 'info') {
            // Simple notification - you can enhance this with a better notification system
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 'bg-blue-500'
            } transition-all duration-300`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    };
}


</script>

<!-- Popular Users Section -->
<div class="mt-8 mb-2 px-10 lg:px-18">
    <div class="mb-4">
        <h2 class="text-md lg:text-2xl md:text-lg font-bold flex items-center gap-2">
            <span class="material-icons text-yellow-500 text-xl lg:text-3xl">emoji_events</span>
            Top Creators
        </h2>
    </div>

    <?php
    // Fetch top 10 users by total points (without badges table)
    $top_users_query = "
        SELECT 
            u.id,
            u.username,
            u.profile_picture,
            ub.total_points,
            ub.badge_name
        FROM users u
        INNER JOIN user_badges ub ON u.id = ub.user_id
        WHERE ub.total_points > 0
        ORDER BY ub.total_points DESC
        LIMIT 10
    ";
    
    $top_users_result = mysqli_query($conn, $top_users_query);
    $top_users = [];
    
    if ($top_users_result) {
        while ($row = mysqli_fetch_assoc($top_users_result)) {
            $top_users[] = $row;
        }
    }
    ?>

    <?php if (empty($top_users)): ?>
        <div class="text-center py-8">
            <span class="material-icons text-gray-300 text-6xl mb-4">person_off</span>
            <p class="text-gray-500 text-lg font-semibold">No top chefs yet</p>
            <p class="text-gray-400 text-sm mt-2">Be the first to earn points!</p>
        </div>
    <?php else: ?>
        <div class="horizontal-scroll-container flex overflow-x-auto gap-5 pb-4">
            <?php foreach ($top_users as $index => $user): ?>
                <?php
                    // Handle profile picture path
                    if (!empty($user['profile_picture'])) {
                        $profilePicPath = $user['profile_picture'];
                        if (strpos($profilePicPath, 'uploads/profile_pics/') === false) {
                            $profilePicPath = 'uploads/profile_pics/' . ltrim($profilePicPath, '/');
                        }
                        $profilePicPath = htmlspecialchars($profilePicPath);
                    } else {
                        $profilePicPath = 'img/no_profile.png';
                    }

                    // Determine rank styling
                    $rank = $index + 1;
                    $rankIcon = '';
                    $ringGradient = '';
                    $badgeBg = '';
                    $glowColor = '';
                    
                    switch ($rank) {
                        case 1:
                            $rankIcon = '👑';
                            $ringGradient = 'from-yellow-400 via-amber-300 to-yellow-500';
                            $badgeBg = 'from-yellow-400 to-amber-500';
                            $glowColor = 'shadow-yellow-400/50';
                            break;
                        case 2:
                            $rankIcon = '🥈';
                            $ringGradient = 'from-gray-300 via-slate-300 to-gray-400';
                            $badgeBg = 'from-gray-300 to-slate-400';
                            $glowColor = 'shadow-gray-400/50';
                            break;
                        case 3:
                            $rankIcon = '🥉';
                            $ringGradient = 'from-orange-400 via-amber-400 to-orange-500';
                            $badgeBg = 'from-orange-400 to-amber-500';
                            $glowColor = 'shadow-orange-400/50';
                            break;
                        default:
                            $rankIcon = '#' . $rank;
                            $ringGradient = 'from-orange-300 via-orange-400 to-amber-400';
                            $badgeBg = 'from-orange-400 to-orange-500';
                            $glowColor = 'shadow-orange-300/40';
                    }
                ?>

                <a href="userprofile.php?username=<?= urlencode($user['username']) ?>" 
                   class="creator-circle flex-none flex flex-col items-center group w-[108px]">
                    
                    <!-- Profile Picture Circle with Gradient Ring -->
                    <div class="relative mb-2">
                        <!-- Rank Badge - Top Right -->
                        <div class="absolute -top-1 -right-1 z-20">
                            <div class="bg-gradient-to-br <?= $badgeBg ?> text-white 
                                        text-xs font-bold w-7 h-7 rounded-full shadow-md 
                                        flex items-center justify-center border-2 border-white
                                        group-hover:scale-110 group-hover:rotate-12 transition-all duration-300">
                                <span><?= $rankIcon ?></span>
                            </div>
                        </div>

                        <!-- Gradient Ring -->
                        <div class="relative p-0.5 bg-gradient-to-br <?= $ringGradient ?> rounded-full shadow-md <?= $glowColor ?> 
                                    group-hover:shadow-lg group-hover:scale-105 transition-all duration-300">
                            <!-- White inner ring -->
                            <div class="p-0.5 bg-white rounded-full">
                                <!-- Profile Image -->
                                <img src="<?= $profilePicPath ?>" 
                                     alt="<?= htmlspecialchars($user['username']) ?>"
                                     class="w-20 h-20 rounded-full object-cover"
                                     onerror="this.src='img/no_profile.png'">
                            </div>
                        </div>

                        <!-- Animated glow effect -->
                        <div class="absolute inset-0 bg-gradient-to-br <?= $ringGradient ?> rounded-full blur-md opacity-0 
                                    group-hover:opacity-40 transition-opacity duration-300 -z-10"></div>
                    </div>

                    <!-- User Info -->
                    <div class="text-center w-full">
                        <h4 class="font-semibold text-xs text-gray-800 truncate mb-1.5
                                   group-hover:text-orange-600 transition-colors"
                            title="<?= htmlspecialchars($user['username']) ?>">
                            <?= htmlspecialchars($user['username']) ?>
                        </h4>
                        
                        <!-- Points Circle Badge -->
                        <div class="inline-flex items-center gap-1 bg-gradient-to-r from-orange-500 to-amber-500 
                                    text-white px-2.5 py-1 rounded-full shadow-sm">
                            <span class="material-icons text-xs">stars</span>
                            <p class="text-xs font-bold">
                                <?= number_format($user['total_points']) ?>
                            </p>
                        </div>

                        <!-- Badge Name in Circle -->
                        <?php if (!empty($user['badge_name']) && $user['badge_name'] !== 'No Badge Yet'): ?>
                            <div class="bg-gray-100 rounded-full px-2 py-1 mt-1.5">
                                <p class="text-[10px] text-gray-600 font-medium truncate" 
                                   title="<?= htmlspecialchars($user['badge_name']) ?>">
                                    <?= htmlspecialchars($user['badge_name']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.horizontal-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #FEA116 #f3f4f6;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.horizontal-scroll-container::-webkit-scrollbar {
    height: 6px;
}

.horizontal-scroll-container::-webkit-scrollbar-track {
    background: linear-gradient(to right, #fef3e2, #fff5e8);
    border-radius: 10px;
}

.horizontal-scroll-container::-webkit-scrollbar-thumb {
    background: linear-gradient(to right, #FEA116, #ff8c00);
    border-radius: 10px;
}

.horizontal-scroll-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to right, #e89005, #e67700);
}

.creator-circle {
    transition: transform 0.3s ease;
}

.creator-circle:hover {
    transform: translateY(-3px);
}
</style>


 <!-- Livestream Section -->
    <div class="mt-2 mb-2 px-10 lg:px-18">
        <div class="flex items-center justify-between mb-4">
  <h2 class="text-md lg:text-2xl md:text-lg font-bold flex items-center gap-2">
    <span class="material-icons text-red-600">sensors</span>
    Live Streams
  </h2>

  <?php if ($can_go_live): ?>
    <button onclick="openModal('go-live-modal')" 
            class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-1.5 rounded-md shadow-sm transition-all flex items-center gap-1 text-sm">
      <span class="material-icons text-base">videocam</span>
      <span class="hidden sm:inline">Go Live</span>
    </button>
  <?php else: ?>
    <div class="text-xs sm:text-sm text-gray-600 bg-gray-100 px-3 py-2 rounded-full flex items-center gap-1">
            <span class="material-icons text-base">lock</span>
            <span class="hidden sm:inline">Need 1000 points to go live (<?php echo $user_total_points; ?>/1000)</span>
            <span class="sm:hidden"><?php echo $user_total_points; ?>/1000</span>
        </div>

  <?php endif; ?>
</div>



        <!-- Live Streams Display -->
        <?php if (empty($streams)): ?>
            <div class="text-center">
                <span class="material-icons text-gray-300 text-6xl mb-4">tv_off</span>
                <p class="text-gray-500 text-lg">No live streams at the moment</p>
                <p class="text-gray-400 text-sm mt-2">Check back later or be the first to go live!</p>
            </div>
        <?php else: ?>
                       <div class="horizontal-scroll-container flex overflow-x-auto gap-3 pb-2">
                <?php foreach ($streams as $stream): ?>
                    <div class="flex-none w-[220px]">
                    <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-300 cursor-pointer group"
                             onclick="openLiveModal(
                '<?= htmlspecialchars($stream['youtube_link']) ?>',
                '<?= htmlspecialchars($stream['title']) ?>',
                '<?= htmlspecialchars($stream['username']) ?>',
                '<?= htmlspecialchars($stream['id']) ?>'
            )">

                <div class="relative aspect-video bg-gray-900">
                     <iframe
                    src="<?= htmlspecialchars($stream['youtube_link']) ?>?autoplay=0&mute=1&controls=0"
                    class="w-full h-full rounded-t-lg pointer-events-none"
                    frameborder="0"
                    allowfullscreen>
                </iframe>

                    <!-- Transparent clickable overlay -->
                    <div
                        class="absolute inset-0 z-10 cursor-pointer"
                        onclick="openLiveModal(
                            '<?= htmlspecialchars($stream['youtube_link']) ?>',
                            '<?= htmlspecialchars($stream['title']) ?>',
                            '<?= htmlspecialchars($stream['username']) ?>',
                            '<?= htmlspecialchars($stream['id']) ?>'
                        )">
                    </div>
                    <div class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-[2px] rounded-full flex items-center gap-1 live-pulse">
                        <span class="w-1.5 h-1.5 bg-white rounded-full"></span>
                        LIVE
                    </div>
                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-60 text-white text-[10px] px-2 py-[2px] rounded flex items-center gap-1">
                        <span class="material-icons text-[12px]">visibility</span>
                        <span id="viewer-count-<?= $stream['id'] ?>"><?= htmlspecialchars($stream['viewer_count'] ?? 0) ?></span>
                    </div>
                </div>

                <div class="p-2">
                    <!-- Title + End Stream in one row -->
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-semibold text-sm truncate group-hover:text-orange-400 transition-colors"
                            title="<?= htmlspecialchars($stream['title']) ?>">
                            <?= htmlspecialchars($stream['title']) ?>
                        </h4>

                        <?php if ($stream['user_id'] == $user_id): ?>
                            <button 
                                type="button" 
                                onclick="event.stopPropagation(); openEndLiveModal(<?= $stream['id'] ?>, '<?= htmlspecialchars(addslashes($stream['title'])) ?>')" 
                                class="bg-red-600 hover:bg-gray-200 hover:text-black text-white text-[10px] px-2 py-[2px] rounded transition-all">
                                End
                            </button>
                            <?php endif; ?>

                    </div>

                     <!-- End Live Confirmation Modal -->
        <div id="end-live-modal" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" onclick="event.stopPropagation();">
            <!-- Body -->
            <div class="p-6 text-center">
              <p class="text-gray-700 text-sm mb-3">
                Are you sure you want to <span class="font-semibold text-red-600">end this livestream</span>?
              </p>

              <form id="end-live-form" method="POST">
                <input type="hidden" name="end_stream_id" id="end_stream_id">

                <!-- Caption input -->
                <div class="mb-5">
                  <label for="end_caption" class="block text-sm font-medium text-gray-700 mb-2">
                    Add caption or message (optional)
                  </label>
                  <textarea id="end_caption" name="end_caption" rows="3"
                            class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            placeholder="e.g., Thanks everyone for watching!"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex justify-center gap-3">
                  <button type="button" onclick="closeModal('end-live-modal')"
                          class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-lg transition-all">
                    Cancel
                  </button>
                  <button type="submit"
                          class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-all">
                    End Live
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>


                <script>
                    function openEndLiveModal(streamId, title) {
                document.getElementById('end_stream_id').value = streamId;
                openModal('end-live-modal');
            }
                </script>

                    <?php
                        if (!empty($stream['profile_picture'])) {
                            $profilePicPath = $stream['profile_picture'];
                            if (strpos($profilePicPath, 'uploads/profile_pics/') === false) {
                                $profilePicPath = 'uploads/profile_pics/' . ltrim($profilePicPath, '/');
                            }
                            $profilePicPath = htmlspecialchars($profilePicPath);
                        } else {
                            $profilePicPath = 'img/no_profile.png';
                        }
                    ?>

                    <!-- Profile info -->
                    <div class="flex items-center gap-1.5">
                        <img src="<?= $profilePicPath ?>" alt="Profile"
                             class="w-5 h-5 rounded-full object-cover border border-gray-200"
                             onerror="this.src='uploads/profile_pics/default.png'">
                        <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($stream['username']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

        <?php endif; ?>
    </div>


<!-- Go Live Modal -->
<div id="go-live-modal" class="modal fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <span class="material-icons">videocam</span>
                    Start Live Stream
                </h3>
                <button onclick="closeModal('go-live-modal')" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-all">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <p class="text-red-100 text-sm mt-2">Share your cooking journey with the community!</p>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Stream Title *</label>
                <input type="text" name="title" placeholder="e.g., Making Pasta from Scratch" 
                       class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" 
                       required maxlength="100">
                <p class="text-xs text-gray-500 mt-1">Give your stream an exciting title</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">YouTube Embed Link *</label>
                <input type="url" name="youtube_link" placeholder="https://www.youtube.com/embed/VIDEO_ID" 
                       class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" 
                       required>
                <p class="text-xs text-gray-500 mt-1">
                    <span class="material-icons text-xs align-middle">info</span>
                    Paste your YouTube embed link here
                </p>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-xs text-blue-800 font-medium mb-1">💡 How to get YouTube Embed Link:</p>
                <ol class="text-xs text-blue-700 space-y-1 ml-4 list-decimal">
                    <li>Go to your YouTube video</li>
                    <li>Click "Share" → "Embed"</li>
                    <li>Copy the URL from the iframe src</li>
                </ol>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('go-live-modal')" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition-all">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg shadow-md transition-all flex items-center justify-center gap-2">
                    <span class="material-icons">sensors</span>
                    Go Live
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Live Viewer Modal with YouTube Chat -->
<div id="viewer-modal" class="modal fixed inset-0 bg-black bg-opacity-90 z-50 hidden items-center justify-center p-4">
    <div class="modal-content w-full max-w-7xl h-[90vh]">
        <div class="relative bg-black rounded-2xl overflow-hidden shadow-2xl h-full flex flex-col">
            
            <!-- Close Button -->
            <button onclick="closeModal('viewer-modal')" 
                    class="absolute top-4 right-4 z-50 hover:bg-opacity-70 text-white rounded-full p-2 transition-all">
                <span class="material-icons">close</span>
            </button>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col md:flex-row gap-0 h-full overflow-hidden">
                
                <!-- Video Container -->
                <div class="flex-1 bg-black flex items-center justify-center">
                    <div class="relative w-full h-full">
                        <iframe 
                            id="live-video" 
                            class="absolute inset-0 w-full h-full" 
                            src="" 
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
                
               <div class="hidden md:flex w-full md:w-96 bg-gray-900 flex-col h-full">
                  <!-- Chat Header -->
                  <div class="bg-gray-800 p-3 border-b border-gray-700">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-gray-400 text-sm">chat</span>
                      <h4 class="text-white text-sm font-semibold">Live Chat</h4>
                    </div>
                  </div>

                  <!-- YouTube Chat Iframe -->
                  <div class="flex-1 relative">
                    <iframe id="live-chat" class="absolute inset-0 w-full h-full" src="" frameborder="0"></iframe>
                  </div>
                </div>

            </div>

            <!-- Stream Info Overlay (Bottom Left) -->
            <div class="absolute bottom-0 left-0 p-4 md:p-6 pointer-events-none">
                <div class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full inline-flex items-center gap-1 mb-2 live-pulse">
                    <span class="w-2 h-2 bg-white rounded-full"></span>
                    LIVE
                </div>
                <h3 id="stream-title" class="text-white text-lg md:text-xl font-bold mb-1"></h3>
                <p id="stream-username" class="text-gray-300 text-sm"></p>
            </div>
        </div>
    </div>
</div>

<script>
let currentLivestreamId = null;
let viewerInterval = null;

function openLiveModal(link, title, username, livestreamId) {
    const videoIframe = document.getElementById("live-video");
    const chatIframe = document.getElementById("live-chat");
    const streamTitle = document.getElementById("stream-title");
    const streamUsername = document.getElementById("stream-username");

    // Extract video ID from the embed link
    let videoId = '';
    const embedMatch = link.match(/embed\/([a-zA-Z0-9_-]+)/);
    if (embedMatch) {
        videoId = embedMatch[1];
    }

    // Construct video URL
    const videoSrc = videoId 
        ? `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&playsinline=1&controls=1&rel=0&modestbranding=1`
        : link + "?autoplay=1&mute=0&playsinline=1&controls=1";

    // Construct chat URL (YouTube live chat)
    const chatSrc = videoId 
        ? `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${window.location.hostname}`
        : '';

    // Clear iframes first
    videoIframe.src = '';
    chatIframe.src = '';

    streamTitle.textContent = title;
    streamUsername.textContent = username;

    openModal("viewer-modal");

    // Set sources after modal opens
    setTimeout(() => {
        videoIframe.src = videoSrc;
        if (chatSrc) {
            chatIframe.src = chatSrc;
        }
    }, 100);

    currentLivestreamId = livestreamId;

    // Track viewer
    fetch('track_viewer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'livestream_id=' + encodeURIComponent(livestreamId)
    });

    // Update viewer count
    if (viewerInterval) clearInterval(viewerInterval);
    viewerInterval = setInterval(() => {
        fetch('get_view_count.php?livestream_id=' + livestreamId)
            .then(res => res.text())
            .then(count => {
                const counter = document.querySelector(`#viewer-count-${livestreamId}`);
                if (counter) counter.innerText = count;
            })
            .catch(err => console.error('Error fetching view count:', err));
    }, 5000);
}

function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.style.overflow = "hidden";
    setTimeout(() => modal.classList.add("is-open"), 10);
    // Hide chat button when livestream modals open
    const chatButton = document.getElementById('chatButton');
    if (chatButton && (id === 'go-live-modal' || id === 'viewer-modal' || id === 'end-live-modal')) {
        chatButton.style.display = 'none';
    }

}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove("is-open");
    document.body.style.overflow = "";

    setTimeout(() => {
        modal.classList.add("hidden");

        if (id === 'viewer-modal') {
            document.getElementById("live-video").src = '';
            document.getElementById("live-chat").src = '';

            if (currentLivestreamId) {
                fetch('remove_viewer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'livestream_id=' + encodeURIComponent(currentLivestreamId)
                });
            }

            clearInterval(viewerInterval);
            viewerInterval = null;
            currentLivestreamId = null;
        }
        // Show chat button when livestream modals close
        const chatButton = document.getElementById('chatButton');
        if (chatButton && (id === 'go-live-modal' || id === 'viewer-modal' || id === 'end-live-modal')) {
            chatButton.style.display = 'block';
        }
    }, 300);
}

// Close modal on backdrop click
document.querySelectorAll(".modal").forEach(modal => {
    modal.addEventListener("click", e => {
        if (e.target === modal) {
            closeModal(modal.id);
        }
    });
});

// Escape key closes modals
document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        closeModal("viewer-modal");
    }
});

// Handle tab close
window.addEventListener('beforeunload', () => {
    if (currentLivestreamId) {
        const data = new FormData();
        data.append('livestream_id', currentLivestreamId);
        navigator.sendBeacon('remove_viewer.php', data);
    }
});
</script>

<style>
.live-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #stream-title {
        font-size: 1rem;
    }
    
    #stream-username {
        font-size: 0.75rem;
    }
}
</style>

</body>
</html>





<div class="tag-container mb-2">
  <h1 class="text-md lg:text-2xl md:text-lg font-bold mb-3">
    Popular Tags For You
  </h1>
  <div class="flex flex-wrap gap-1.5 sm:gap-2">
    <?php 
    $tailwindColors = [
        'purple' => 'bg-purple-100 text-purple-600 hover:bg-purple-200',
        'rose' => 'bg-rose-100 text-rose-600 hover:bg-rose-200',
        'red' => 'bg-red-100 text-red-600 hover:bg-red-200',
        'pink' => 'bg-pink-100 text-pink-600 hover:bg-pink-200',
        'indigo' => 'bg-indigo-100 text-indigo-600 hover:bg-indigo-200',
        'cyan' => 'bg-cyan-100 text-cyan-600 hover:bg-cyan-200',
        'teal' => 'bg-teal-100 text-teal-600 hover:bg-teal-200',
        'green' => 'bg-green-100 text-green-600 hover:bg-green-200',
        'orange' => 'bg-orange-100 text-orange-600 hover:bg-orange-200',
        'yellow' => 'bg-yellow-100 text-yellow-600 hover:bg-yellow-200',
        'amber' => 'bg-amber-100 text-amber-600 hover:bg-amber-200',
        'lime' => 'bg-lime-100 text-lime-600 hover:bg-lime-200',
        'blue' => 'bg-blue-100 text-blue-600 hover:bg-blue-200',
        'violet' => 'bg-violet-100 text-violet-600 hover:bg-violet-200',
        'fuchsia' => 'bg-fuchsia-100 text-fuchsia-600 hover:bg-fuchsia-200',
        'sky' => 'bg-sky-100 text-sky-600 hover:bg-sky-200'
    ];

    $colorKeys = array_keys($tailwindColors);

    foreach ($top_tags as $tag => $count) {
        $encodedTag = urlencode($tag);
        $randomColorKey = $colorKeys[array_rand($colorKeys)];
        $colorClass = $tailwindColors[$randomColorKey];

        echo '<a href="latest.php?tag=' . $encodedTag . '" 
                 class="' . $colorClass . ' text-[10px] sm:text-xs md:text-sm font-medium px-2 py-[3px] sm:px-2.5 sm:py-1 rounded-full cursor-pointer transition-all duration-300 hover:scale-105 inline-block">
                 #' . htmlspecialchars($tag) . ' (' . intval($count) . ')
              </a>';
    }
    ?>
  </div>
</div>




<!-- Suggested For You Section -->
<div class="bg-card-light ml-5 me-5 p-4 sm:p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-md lg:text-2xl md:text-lg font-bold">Suggested For You</h2>
        <button class="text-subtext-light transition-transform duration-300 hover:text-orange-400 hover:scale-110"
                onclick="window.location.href='preference.php'">
            <span class="material-icons">tune</span>
        </button>
    </div>
    
    <!-- Dynamic Content Container -->
    <div id="suggestions-container" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Content will be dynamically generated here -->
    </div>
    <!-- No suggestions state -->
    <div id="no-suggestions" class="text-center py-8 hidden">
        <p class="text-subtext-light ">No personalized suggestions found.</p>
    </div>
</div>


<script>

    // Pass PHP array to JS
    const recipes = <?= json_encode($suggested_with_gradients) ?>;

function shuffleArray(array) {
    let shuffled = array.slice();
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

function renderSuggestions() {
    const container = document.getElementById('suggestions-container');
    const noSuggestionsState = document.getElementById('no-suggestions');
    
        
        if (recipes.length === 0) {
            noSuggestionsState.classList.remove('hidden');
            return;
        }
        
        noSuggestionsState.classList.add('hidden');
        container.classList.remove('hidden');
        
        const shuffled = shuffleArray(recipes);
        container.innerHTML = '';
        
        // Main featured card (large, left side)
        const main = shuffled[0];
        const mainCardHtml = `
            <div class="lg:col-span-2 bg-white dark:bg-card-dark rounded-lg overflow-hidden flex flex-col md:flex-row shadow-sm border border-border-light dark:border-border-dark group cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                 onclick="window.location.href='recipe_details.php?id=${main.id}'">
                <div class="md:w-1/2">
                <img alt="${main.recipe_name}" 
                     class="w-full h-48 lg:h-96 md:h-72 sm:h-60 object-cover rounded-md mx-auto md:mx-0" 
                     src="${main.image || 'uploads/default-placeholder.png'}"/>
                                </div>
                                <!-- Main Card -->
                <div class="p-4 md:p-6 md:w-1/2 flex flex-col justify-center">
                    <!-- Recipe name (truncate after 1 line) -->
                    <h3 class="lg:text-xl text-md font-bold mb-1" title="${main.recipe_name}">
                        ${main.recipe_name}
                    </h3>

                    <!-- Username + badge -->
                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-primary-dark text-sm"
                           style="background: ${main.badge_gradient}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">
                           @${main.username}
                        </p>
                        ${main.badge_name && main.badge_name !== "No Badge Yet" 
                            ? `<img src="${main.badge_icon}" alt="${main.badge_name}" class="w-6 h-6 object-contain">` 
                            : `<span class="w-6 h-6 inline-block"></span>`}
                    </div>

                    <!-- Description (max 2 lines) -->
                    <p class="text-subtext-light dark:text-subtext-dark mb-4 text-sm line-clamp-2">
                        ${main.recipe_description}
                    </p>

                    <button class="w-full bg-gradient-to-r from-orange-400 to-orange-600 text-white font-bold py-2 px-4 rounded-md shadow-md hover:shadow-lg transition-all duration-300 group-hover:scale-105 text-sm">
                        Try Now
                    </button>
                </div>
            </div>
        `;
        
        // Small cards (right side)
        const smallCards = shuffled.slice(1, 4);
        let smallCardsHtml = `<div class="lg:col-span-1 space-y-4">`;
        
   smallCards.forEach(recipe => {
    smallCardsHtml += `
        <div class="bg-white p-3 rounded-lg flex items-center gap-3 shadow-md border border-border-light dark:border-border-dark cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
             onclick="window.location.href='recipe_details.php?id=${recipe.id}'">
            <img alt="${recipe.recipe_name}" 
                 class="w-20 h-20 rounded-md object-cover flex-shrink-0" 
                 src="${recipe.image || 'uploads/default-placeholder.png'}"/>
            <div class="flex-1">
                <h4 class="font-semibold text-sm line-clamp-2">${recipe.recipe_name}</h4>
                <div class="flex items-center gap-1 mb-0.5">
                    <p class="text-xs text-primary-dark dark:text-primary-light" 
                       style="background: ${recipe.badge_gradient}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">
                       @${recipe.username}
                    </p>
                    ${(recipe.badge_name && recipe.badge_name !== "No Badge Yet") 
                        ? `<img src="${recipe.badge_icon}" alt="${recipe.badge_name}" class="w-4 h-4 object-contain">` 
                        : `<span class="w-4 h-4 inline-block"></span>`}
                </div>

                <p class="text-xs text-subtext-light dark:text-subtext-dark">
                    ${recipe.recipe_description.substring(0, 50)}...
                </p>
            </div>
        </div>
    `;
});
        
        smallCardsHtml += `</div>`;
        
        // Combine main card and small cards
        container.innerHTML = mainCardHtml + smallCardsHtml;
        
        // Add hover effects
        document.querySelectorAll('[onclick*="recipe_details.php"]').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (this.classList.contains('lg:col-span-2')) {
                    this.style.transform = 'translateY(-8px)';
                } else {
                    this.style.transform = 'translateY(-4px)';
                }
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
        
    
}

// Initial render
renderSuggestions();

// Auto refresh every 3 seconds (matching your original interval)
setInterval(renderSuggestions, 3000);

// Optional: Add refresh button functionality
function manualRefresh() {
    renderSuggestions();
}

</script>

<!-- Latest Recipes listing -->
<div class="container-fluid mt-7 relative  relative recipe-scroll-container">
    <p class="text-md lg:text-2xl md:text-lg"  
    style="text-align: left; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
    <span>Latest Recipes</span>
<a href="latest.php" style="text-decoration: none; color: black; font-size: 12px;">See More...</a>
</p>
  <!-- Floating Arrows 
<div class="scroll-arrow left" onclick="scrollGrid('latest-scroll', -300)">
  <i class="fas fa-chevron-left"></i>
</div>
<div class="scroll-arrow right" onclick="scrollGrid('latest-scroll', 300)">
  <i class="fas fa-chevron-right"></i>
</div>
-->
    
    <!-- Scrollable wrapper -->
    <div class="overflow-x-auto w-full custom-scroll" id="latest-scroll">
        <!-- Recipe Grid -->
    <div class="grid grid-flow-col auto-cols-max gap-6 p-2" id="recipe-grid">
        <?php
        if ($latest_result->num_rows > 0) {
            $badgeGradients = [
                'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
                'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
                'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
                'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
                'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
                'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
                'No Badge Yet'    => 'linear-gradient(90deg, #504F4F, #555)',
            ];

            while ($row = $latest_result->fetch_assoc()) {
                $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                $recipeUrl = 'recipe_details.php?id=' . $row["id"];

                // Favorites check
                $check_fav_stmt = $conn->prepare($check_fav_sql ?? "SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
                if (isset($_SESSION['user_id'])) {
                    $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                    $check_fav_stmt->execute();
                    $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;
                } else {
                    $is_favorite = false;
                }

                // Likes check
                $check_layk_stmt = $conn->prepare($check_layk_sql ?? "SELECT 1 FROM likes WHERE user_id = ? AND recipe_id = ?");
                if (isset($_SESSION['user_id'])) {
                    $check_layk_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                    $check_layk_stmt->execute();
                    $is_like = $check_layk_stmt->get_result()->num_rows > 0;
                } else {
                    $is_like = false;
                }

                // Like count
                $layk_count_sql = "SELECT COUNT(*) AS layk_count FROM likes WHERE recipe_id = ?";
                $layk_count_stmt = $conn->prepare($layk_count_sql);
                $layk_count_stmt->bind_param("i", $row['id']);
                $layk_count_stmt->execute();
                $layk_count_result = $layk_count_stmt->get_result();
                $layk_count = $layk_count_result->fetch_assoc()['layk_count'] ?? 0;

                $gradient = isset($badgeGradients[$row['badge_name']]) ? $badgeGradients[$row['badge_name']] : $badgeGradients['No Badge Yet'];
        ?>
        
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[395px]"
            data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">

            <!-- Image -->
            <div class="relative">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row["recipe_name"]) ?>"
                     class="w-full h-36 md:h-48 lg:h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>
            </div>

            <!-- Card Content -->
            <div class="py-2 px-3">
                <!-- Title -->
                <p class="text-sm md:text-lg lg:text-lg lg:h-12 md:h-12 h-10 font-bold text-gray-800 mb-1"> 
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm lg:mb-4 md:mb-4 mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
                            <?php 
                            $description = htmlspecialchars($row["recipe_description"]);
                            echo strlen($description) > 55 ? substr($description, 0, 55) . '...' : $description; 
                            ?>
                        </p>
                    <?php endif; ?> 

                    <div class="flex items-center gap-1">
                        <!-- Profile Image (if available) -->
                        <?php if (!empty($row["profile_picture"])): ?>
                            <img alt="Author's profile picture" 
                                 class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                                 src="<?= htmlspecialchars('uploads/profile_pics/' . $row["profile_picture"]) ?>"/>
                        <?php else: ?>
                            <div class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 text-xs lg:text-lg md:text-lg"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Username -->
                        <a href="userprofile.php?username=<?= urlencode($row["username"]) ?>" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation();">
                            @<?= htmlspecialchars($row["username"]) ?>
                        </a>

                        <!-- Badge -->
                        <?php if (!empty($row['badge_name']) && $row['badge_name'] !== "No Badge Yet"): ?>
                            <img src="<?= htmlspecialchars($row['badge_icon']) ?>" 
                                 alt="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 title="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4">
                        <?php else: ?>
                            <!-- Leave space if no badge -->
                            <span class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4 inline-block"></span>
                        <?php endif; ?>
                    </div>

                <!-- Interactions -->
                <div class="flex items-center justify-between text-sm md:text-lg mt-3 lg:text-lg lg:mt-2 md:mt-2">
                    <!-- Like -->
                    <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                            data-recipe-id="<?= $row['id'] ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#loginModal"
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="like-count"><?= $layk_count ?></span>
                    </button>

                <!-- Bookmark button -->
                <button class=" flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                        data-recipe-id="<?= $row['id'] ?>"
                        data-bs-toggle="modal" 
                        data-bs-target="#loginModal"
                        onclick="event.stopPropagation();">
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                     <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
                </button>
                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700"
                       onclick="event.stopPropagation();">
                        <span class="material-icons text-md">comment</span>
                    </a>
                </div>
            </div>
        </div>

        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No latest recipes found.</p>
                  </div>';
        }
        ?>
        </div>
    </div>
</div>

<!-- Popular Recipes Section -->
<div class="container-fluid mt-7 relative recipe-scroll-container">
    <h3 class="text-md lg:text-2xl md:text-lg" 
    style="text-align: left; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
        <span><strong>Trending For You</strong> (Most Popular)</span>
        <a href="latest.php?category=<?= urlencode($categoryFilter ?? '') ?>&sort=most_popular" style="text-decoration: none; font-size: 12px; color: black;">See More...</a>
    </h3>
      <!-- Floating Arrows
<div class="scroll-arrow left" onclick="scrollGrid('popular-scroll', -300)">
  <i class="fas fa-chevron-left"></i>
</div>
<div class="scroll-arrow right" onclick="scrollGrid('popular-scroll', 300)">
  <i class="fas fa-chevron-right"></i>
</div>
-->
    <!-- Scrollable wrapper for mobile -->
    <div class="overflow-x-auto w-full custom-scroll" id="popular-scroll">
        <!-- Recipe Grid -->
    <div class="grid grid-flow-col auto-cols-max gap-6 p-2" id="popular-recipe-grid">
            <?php
            if ($popular_result->num_rows > 0) {
                while ($row = $popular_result->fetch_assoc()) {
                    $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                    $recipeUrl = 'recipe_details.php?id=' . $row["id"];

                    if (isset($_SESSION['user_id'])) {
                        $check_fav_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
                        $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                        $check_fav_stmt->execute();
                        $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;

                        $check_layk_stmt = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND recipe_id = ?");
                        $check_layk_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                        $check_layk_stmt->execute();
                        $is_like = $check_layk_stmt->get_result()->num_rows > 0;
                    } else {
                        $is_favorite = false;
                        $is_like = false;
                    }

                    $layk_count_sql = "SELECT COUNT(*) AS layk_count FROM likes WHERE recipe_id = ?";
                    $layk_count_stmt = $conn->prepare($layk_count_sql);
                    $layk_count_stmt->bind_param("i", $row['id']);
                    $layk_count_stmt->execute();
                    $layk_count_result = $layk_count_stmt->get_result();
                    $layk_count = $layk_count_result->fetch_assoc()['layk_count'] ?? 0;

                    $gradient = isset($badgeGradients[$row['badge_name']]) ? $badgeGradients[$row['badge_name']] : $badgeGradients['No Badge Yet'];
            ?>
        
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[395px] "
            data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">

            <!-- Image -->
            <div class="relative">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row["recipe_name"]) ?>"
                     class="w-full h-36 md:h-48 lg:h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>


            </div>

            <!-- Card Content -->
            <div class="py-2 px-3">
                <!-- Title -->
                <p class="text-sm md:text-lg lg:text-lg lg:h-12 md:h-12 h-10 font-bold text-gray-800 mb-1"> 
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm lg:mb-4 md:mb-4 mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
                            <?php 
                            $description = htmlspecialchars($row["recipe_description"]);
                            echo strlen($description) > 55 ? substr($description, 0, 55) . '...' : $description; 
                            ?>
                        </p>
                    <?php endif; ?> 

                    <div class="flex items-center gap-1">
                        <!-- Profile Image (if available) -->
                        <?php if (!empty($row["profile_picture"])): ?>
                            <img alt="Author's profile picture" 
                                 class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                                 src="<?= htmlspecialchars('uploads/profile_pics/' . $row["profile_picture"]) ?>"/>
                        <?php else: ?>
                            <div class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 text-xs lg:text-lg md:text-lg"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Username -->
                        <a href="userprofile.php?username=<?= urlencode($row["username"]) ?>" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation();">
                            @<?= htmlspecialchars($row["username"]) ?>
                        </a>

                        <!-- Badge -->
                        <?php if (!empty($row['badge_name']) && $row['badge_name'] !== "No Badge Yet"): ?>
                            <img src="<?= htmlspecialchars($row['badge_icon']) ?>" 
                                 alt="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 title="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4">
                        <?php else: ?>
                            <!-- Leave space if no badge -->
                            <span class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4 inline-block"></span>
                        <?php endif; ?>
                    </div>

                <!-- Interactions -->
                <div class="flex items-center justify-between text-sm md:text-lg mt-3 lg:text-lg lg:mt-2 md:mt-2">
                    <!-- Like -->
                    <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                            data-recipe-id="<?= $row['id'] ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#loginModal"
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="like-count"><?= $layk_count ?></span>
                    </button>


                <!-- Bookmark button -->
                <button class=" flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                        data-recipe-id="<?= $row['id'] ?>"
                        data-bs-toggle="modal" 
                        data-bs-target="#loginModal"
                        onclick="event.stopPropagation();">
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                     <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
                </button>
                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700"
                       onclick="event.stopPropagation();">
                        <span class="material-icons text-md">comment</span>
                    </a>
                </div>
            </div>
        </div>

        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No popular recipes found.</p>
                  </div>';
        }
        ?>
        </div>
    </div>
</div>

<!-- Favorite Recipes Section -->
<div class="container-fluid mt-7 relative recipe-scroll-container">
    <h3 class="text-md lg:text-2xl md:text-lg" 
    style="text-align: left; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
        <span><strong>Most Treasured</strong> (Most Favorite)</span>
        <a href="latest.php?category=<?= urlencode($categoryFilter ?? '') ?>&sort=most_favorite" style="text-decoration: none; font-size: 12px; color: black;">See More...</a>
    </h3>
      <!-- Floating Arrows 
<div class="scroll-arrow left" onclick="scrollGrid('favorite-scroll', -300)">
  <i class="fas fa-chevron-left"></i>
</div>
<div class="scroll-arrow right" onclick="scrollGrid('favorite-scroll', 300)">
  <i class="fas fa-chevron-right"></i>
</div>
-->
    <!-- Scrollable wrapper for mobile -->
    <div class="overflow-x-auto w-full custom-scroll" id="favorite-scroll" >
        <!-- Recipe Grid -->
    <div class="grid grid-flow-col auto-cols-max gap-6 p-2" id="favorite-recipe-grid">
            <?php
            if ($favorite_result->num_rows > 0) {
                while ($row = $favorite_result->fetch_assoc()) {
                    $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                    $recipeUrl = 'recipe_details.php?id=' . $row["id"];

                    if (isset($_SESSION['user_id'])) {
                        $check_fav_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
                        $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                        $check_fav_stmt->execute();
                        $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;

                        $check_layk_stmt = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND recipe_id = ?");
                        $check_layk_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                        $check_layk_stmt->execute();
                        $is_like = $check_layk_stmt->get_result()->num_rows > 0;
                    } else {
                        $is_favorite = false;
                        $is_like = false;
                    }

                    $layk_count_sql = "SELECT COUNT(*) AS layk_count FROM likes WHERE recipe_id = ?";
                    $layk_count_stmt = $conn->prepare($layk_count_sql);
                    $layk_count_stmt->bind_param("i", $row['id']);
                    $layk_count_stmt->execute();
                    $layk_count_result = $layk_count_stmt->get_result();
                    $layk_count = $layk_count_result->fetch_assoc()['layk_count'] ?? 0;

                    $gradient = isset($badgeGradients[$row['badge_name']]) ? $badgeGradients[$row['badge_name']] : $badgeGradients['No Badge Yet'];
            ?>
    
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[395px] "
            data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">

            <!-- Image -->
            <div class="relative">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row["recipe_name"]) ?>"
                     class="w-full h-36 md:h-48 lg:h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>


            </div>

            <!-- Card Content -->
            <div class="py-2 px-3">
                <!-- Title -->
                <p class="text-sm md:text-lg lg:text-lg lg:h-12 md:h-12 h-10 font-bold text-gray-800 mb-1"> 
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm lg:mb-4 md:mb-4 mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
                            <?php 
                            $description = htmlspecialchars($row["recipe_description"]);
                            echo strlen($description) > 55 ? substr($description, 0, 55) . '...' : $description; 
                            ?>
                        </p>
                    <?php endif; ?> 

                    <div class="flex items-center gap-1">
                        <!-- Profile Image (if available) -->
                        <?php if (!empty($row["profile_picture"])): ?>
                            <img alt="Author's profile picture" 
                                 class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                                 src="<?= htmlspecialchars('uploads/profile_pics/' . $row["profile_picture"]) ?>"/>
                        <?php else: ?>
                            <div class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 text-xs lg:text-lg md:text-lg"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Username -->
                        <a href="userprofile.php?username=<?= urlencode($row["username"]) ?>" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation();">
                            @<?= htmlspecialchars($row["username"]) ?>
                        </a>

                        <!-- Badge -->
                        <?php if (!empty($row['badge_name']) && $row['badge_name'] !== "No Badge Yet"): ?>
                            <img src="<?= htmlspecialchars($row['badge_icon']) ?>" 
                                 alt="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 title="<?= htmlspecialchars($row['badge_name']) ?>" 
                                 class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4">
                        <?php else: ?>
                            <!-- Leave space if no badge -->
                            <span class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4 inline-block"></span>
                        <?php endif; ?>
                    </div>

                <!-- Interactions -->
                <div class="flex items-center justify-between text-sm md:text-lg mt-3 lg:text-lg lg:mt-2 md:mt-2">
                    <!-- Like -->
                    <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                            data-recipe-id="<?= $row['id'] ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#loginModal"
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="like-count"><?= $layk_count ?></span>
                    </button>


                <!-- Bookmark button -->
                <button class=" flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                        data-recipe-id="<?= $row['id'] ?>"
                        data-bs-toggle="modal" 
                        data-bs-target="#loginModal"
                        onclick="event.stopPropagation();">
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                     <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
                </button>
                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700"
                       onclick="event.stopPropagation();">
                        <span class="material-icons text-md">comment</span>
                    </a>
                </div>
            </div>
        </div>

        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No favorite recipes found.</p>
                  </div>';
        }
        ?>
        </div>
    </div>
</div>

<!-- Back to Top Button (Right) -->
<a href="#" 
   class="btn btn-lg bg-orange-500 text-white btn-lg-square back-to-top px-4 py-3 rounded-sm" 
   id="backToTopBtn"
   style="position: fixed; bottom: 20px; right: 20px;">
    <i class="bi bi-arrow-up"></i>
</a>

<!-- Chat Button (Left) with Notification Message -->
<a href="chat.php" id="chatButton"
   style="
       position: fixed;
       bottom: 20px;
       left: 20px;
       background: #FEA116;
       color: white;
       padding: 15px 20px;
       border-radius: 50px;
       text-decoration: none;
       box-shadow: 0 4px 8px rgba(0,0,0,0.2);
       font-weight: bold;
       text-align: center;
       z-index: 1000;
   ">
    <i class="bi bi-chat-dots"></i>
    
    <!-- Red notification dot -->
    <span style="
        position: absolute;
        top: 5px;
        right: 5px;
        width: 12px;
        height: 12px;
        background: #dc3545;
        border-radius: 50%;
        border: 2px solid white;
        animation: pulse 2s infinite;
    "></span>
    
    <!-- Floating message notification -->
    <span style="
        position: absolute;
        bottom: 70px;
        left: 0;
        background: white;
        color: #333;
        padding: 10px 15px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        white-space: nowrap;
        font-size: 14px;
        animation: float 3s ease-in-out infinite;
    ">
        Check this out!
        <!-- Small arrow pointing down -->
        <span style="
            position: absolute;
            bottom: -8px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid white;
        "></span>
    </span>
</a>

<style>
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-5px);
    }
}
</style>


<style>
/* Modern horizontal scrollbar */
.custom-scroll::-webkit-scrollbar {
  height: 10px;
}

.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scroll::-webkit-scrollbar-thumb {
  background: rgba(249, 115, 22, 0.8);
  border-radius: 20px;
  border: 2px solid transparent;
  background-clip: content-box;
  transition: background 0.3s ease;
}

.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(249, 115, 22, 1);
}

/* Firefox */
.custom-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(249, 115, 22, 0.8) transparent;
  position: relative;
}

/* Floating scroll arrows */
.scroll-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 20;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: rgba(249, 115, 22, 0.9);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  transition: background 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
  opacity: 0;
  visibility: hidden;
}

.scroll-arrow:hover {
  background: rgba(249, 115, 22, 1);
}

.scroll-arrow.left {
  left: 10px;
}

.scroll-arrow.right {
  right: 10px;
}

/* Show arrows only on hover */
.recipe-scroll-container:hover .scroll-arrow {
  opacity: 1;
  visibility: visible;
}

</style>

<script>
function scrollGrid(containerId, amount) {
  const grid = document.getElementById(containerId);
  if (grid) {
    grid.scrollBy({ left: amount, behavior: 'smooth' });
  }
}
</script>

<script>
    // Show/hide button on scroll
    window.onscroll = function () {
        let btn = document.getElementById("backToTopBtn");
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
            btn.style.display = "block";
        } else {
            btn.style.display = "none";
        }
    };

    // Smooth scroll to top when clicked
    document.getElementById("backToTopBtn").addEventListener("click", function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".favorite-icon").forEach(icon => {
        icon.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            // Check if the user is logged in
            let isLoggedIn = <?php echo json_encode(isset($_SESSION['user_id'])); ?>; // PHP variable to check login status
            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("i");
                // Proceed with the favorite action if logged in
                fetch("favorite_action.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "recipe_id=" + recipeId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "added") {
                        iconElement.classList.remove("far", "text-muted");
                        iconElement.classList.add("fas", "text-warning");
                    } else if (data.status === "removed") {
                        iconElement.classList.remove("fas", "text-warning");
                        iconElement.classList.add("far", "text-muted");
                    }
                });
            }
        });
    });

</script>
        </div>

     <script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle favorite actions (bookmark)
    document.querySelectorAll(".favorite-action").forEach(btn => {
        btn.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("i");
            let textElement = this.querySelector("span.text-xs");

            fetch("favorite_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "recipe_id=" + recipeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "added") {
                    iconElement.classList.remove("far");
                    iconElement.classList.add("fas");
                    this.classList.add("text-orange-500");
                    textElement.textContent = "Added";
                } else if (data.status === "removed") {
                    iconElement.classList.remove("fas");
                    iconElement.classList.add("far");
                    this.classList.remove("text-orange-500");
                    textElement.textContent = "Add";
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    // Handle like actions (heart)
    document.querySelectorAll(".like-action").forEach(btn => {
        btn.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("i");
            let countElement = this.querySelector(".like-count");

            fetch("like_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "recipe_id=" + recipeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "added") {
                    iconElement.classList.remove("far");
                    iconElement.classList.add("fas");
                    this.classList.add("text-red-500");
                } else if (data.status === "removed") {
                    iconElement.classList.remove("fas");
                    iconElement.classList.add("far");
                    this.classList.remove("text-red-500");
                }
                // Update the like count
                countElement.textContent = data.count;
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });


    // Search dropdown functionality
    const searchDropdown = document.querySelector('.search-dropdown');
    if (searchDropdown) {
        const dropdownToggle = searchDropdown.querySelector('.dropdown-toggle');

        dropdownToggle.addEventListener('click', function(event) {
            searchDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function(event) {
            if (!searchDropdown.contains(event.target)) {
                searchDropdown.classList.remove('active');
            }
        });
    }
});

// Navbar scroll behavior
let lastScrollTop = 0;
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', function () {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop < lastScrollTop) {
        navbar.classList.add('show');
    } else {
        navbar.classList.remove('show');
    }

    lastScrollTop = scrollTop;
});

// Back to top functionality
window.onscroll = function () {
    let btn = document.getElementById("backToTopBtn");
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
        btn.style.display = "block";
    } else {
        btn.style.display = "none";
    }
};

document.getElementById("backToTopBtn").addEventListener("click", function (e) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>



<script>
document.addEventListener("DOMContentLoaded", function() {
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 0) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
});
</script>

<script>
    const allSuggestions = <?= json_encode($suggested_result); ?>;
    let currentIndex = 0;

    function renderSuggestions() {
        const suggestionCount = Math.min(4, allSuggestions.length);
        const displayItems = [];

        for (let i = 0; i < suggestionCount; i++) {
            const index = (currentIndex + i) % allSuggestions.length;

            // Avoid wrapping if total results <= 4
            if (allSuggestions.length <= 4 && index < i) break;

            displayItems.push(allSuggestions[index]);
        }

        const main = displayItems[0];
        const others = displayItems.slice(1);

    let html = `
        <div class="row mb-5">
            <div class="col-lg-8">
                <h4><strong>Suggested for you</strong></h4>
                <div class="d-flex border p-3" style="cursor: pointer;" onclick="location.href='recipe_details.php?id=${main.id}'">
                    <img src="${main.image || 'uploads/default-placeholder.png'}" class="me-3" style="width: 220px; height: 180px; object-fit: cover; border: 1px solid #ccc;">
                    <div>
                        <h1 style="font-size:1.5rem;font-weight:800;><strong>${main.recipe_name}</strong></h1>
                        <p class="text-muted mb-1 small">@${main.username}</p>
                        <p style="font-size: 14px;">${main.recipe_description}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <h4><strong>Similar Recipes</strong></h4>`;
        others.forEach(recipe => {
            html += `
            <div class="d-flex align-items-start border p-2 mb-2" style="cursor: pointer;" onclick="location.href='recipe_details.php?id=${recipe.id}'">
                <img src="${recipe.image || 'uploads/default-placeholder.png'}" style="width: 70px; height: 70px; object-fit: cover; border: 1px solid #ccc;" class="me-2">
                <div>
                    <p class="mb-0"><strong>${recipe.recipe_name}</strong></p>
                    <p class="text-muted mb-1 small">@${recipe.username}</p>
                    <p class="mb-0 small">${recipe.recipe_description.slice(0, 50)}...</p>
                </div>
            </div>`;
        });

        html += '</div></div>';
        document.getElementById('suggestion-section').innerHTML = html;

        currentIndex = (currentIndex + 1) % allSuggestions.length;
    }

    // Shuffle initially if more than 4
    if (allSuggestions.length > 4) {
        shuffle(allSuggestions);
        renderSuggestions();
        setInterval(renderSuggestions, 3000);
    } else {
        renderSuggestions(); // Just show as is if <= 4
    }

    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }
</script>






        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/main.js"></script>

    </div>
</body>
</html>