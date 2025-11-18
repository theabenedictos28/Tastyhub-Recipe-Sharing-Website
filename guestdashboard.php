
<?php
session_start();
require 'db.php'; // Include database connection


// Initialize search variable
$categoryFilter = '';


if (isset($_GET['category'])) {
    $categoryFilter = urldecode(trim($_GET['category']));
}

// Check for sorting parameter
$sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'latest';


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
SELECT DISTINCT r.*, u.username
FROM recipe r
JOIN users u ON r.user_id = u.id
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
    SELECT r.id, r.recipe_name, r.image, r.category, r.difficulty, r.preparation, r.budget, r.servings, r.cooktime, 
           u.username,
           MAX(b.badge_name) AS badge_name, 
           MAX(b.badge_icon) AS badge_icon,
           r.recipe_description
    FROM recipe r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN user_badges b ON u.id = b.user_id
    WHERE r.status = 'approved'
    GROUP BY r.id, u.username
    ORDER BY RAND()
";

    
  $suggested_stmt = $conn->prepare($suggested_sql);
}
$suggested_stmt->execute();

$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet' => 'linear-gradient(90deg, #504F4F, #555)',
];

$suggested_result = [];
$suggested_query = $suggested_stmt->get_result();
while ($row = $suggested_query->fetch_assoc()) {
    $gradient = isset($badgeGradients[$row['badge_name']])
        ? $badgeGradients[$row['badge_name']]
        : 'linear-gradient(90deg, #333, #555)';
    $row['badge_gradient'] = $gradient;
    $suggested_result[] = $row;
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

// Initialize search variables
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

// Start building the SQL query
$sql = "SELECT recipe.id, recipe.recipe_name, recipe.image, recipe.category, recipe.difficulty, recipe.preparation, recipe.budget, recipe.servings, recipe.cooktime, users.username, 
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
        WHERE recipe.status = 'approved'"; // Only fetch approved recipes

// Fetch the latest 5 recipes
$latest_sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, recipe.difficulty, recipe.preparation, recipe.budget, recipe.servings, recipe.cooktime, users.username, users.profile_picture,
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
        GROUP BY recipe.id
        ORDER BY recipe.created_at DESC, recipe.id DESC
        LIMIT 10";
$latest_stmt = $conn->prepare($latest_sql);
$latest_stmt->execute();
$latest_result = $latest_stmt->get_result();


// Fetch the most popular 5 recipes
$popular_sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, recipe.difficulty, recipe.preparation, recipe.budget, recipe.servings, recipe.cooktime, users.username, users.profile_picture,
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
        GROUP BY recipe.id
        ORDER BY like_count DESC, recipe.created_at DESC, recipe.id DESC
        LIMIT 10";
$popular_stmt = $conn->prepare($popular_sql);
$popular_stmt->execute();
$popular_result = $popular_stmt->get_result();

// Fetch the most favorite 5 recipes
$favorite_sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.image, recipe.category, recipe.difficulty, recipe.preparation, recipe.budget, recipe.servings, recipe.cooktime, users.username, users.profile_picture,
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
        GROUP BY recipe.id
        ORDER BY favorite_count DESC, recipe.created_at DESC, recipe.id DESC
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
    $conditions[] = "(recipe.recipe_name LIKE ? OR ingredients.ingredient_name LIKE ? OR equipments.equipment_name LIKE ? OR recipe.tags LIKE ?)";
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
    $params[] = '%' . $keyword . '%'; // For tags
    $types .= 'ssss'; // 3 string parameters for keyword
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

if (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $selectedTag = trim($_GET['tag']);
    $sql = "SELECT recipe.*, users.username, user_badges.badge_name, user_badges.badge_icon
            FROM recipe 
            JOIN users ON recipe.user_id = users.id 
            LEFT JOIN user_badges ON users.id = user_badges.user_id
            WHERE recipe.status = 'approved'
              AND FIND_IN_SET(TRIM(?), REPLACE(recipe.tags, ' ', ''))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedTag);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Load all or recent approved recipes
    $result = $conn->query("SELECT recipe.*, users.username, user_badges.badge_name, user_badges.badge_icon
                            FROM recipe 
                            JOIN users ON recipe.user_id = users.id 
                            LEFT JOIN user_badges ON users.id = user_badges.user_id
                            WHERE recipe.status = 'approved'
                            ORDER BY recipe.created_at DESC");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

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

/* for card latest etc mobile */

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
        }
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
        </head>
    <body class="bg-orange-50 mt-8 py-8">
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
        <!-- Top Row: Logo, Hamburger, Search -->
        <div class="flex items-center justify-between py-3 gap-3">
            
            <!-- Logo -->
            <a href="index.php" class="flex items-center text-decoration-none">
                <img alt="Tasty Hub logo" class="h-12 w-12 mr-2" src="img/logo_new.png"/>
                <span class="hidden sm:inline text-3xl font-extrabold text-orange-400 font-nunito">Tasty Hub</span>
            </a>

            <!-- Search Bar (always visible, adapts width) -->
            <div class="flex-1 max-w-md">
                <form action="guestlatest.php" method="GET">
                    <input name="keyword" 
                           class="w-full bg-white border border-gray-200 rounded-full py-2 px-4 
                                  text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent
                                  text-sm md:text-base"
                           placeholder="Search for recipes..." 
                           type="text"/>
                </form>
            </div>

                    <!-- Hamburger (phones & tablets only) -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="lg:hidden p-2 rounded-md text-orange-400 hover:bg-orange-100 focus:outline-none">
                <span class="material-icons text-2xl">
                    <span x-show="!mobileMenuOpen">menu</span>
                    <span x-show="mobileMenuOpen" x-cloak>close</span>
                </span>
            </button>

            <!-- Desktop Navigation (≥1024px only) -->
            <div class="hidden lg:flex items-center space-x-6">
                <!-- Home -->
                <a class="flex items-center text-orange-400 hover:text-orange-500 transition-colors" href="guestdashboard.php">
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
                                       href="#" onclick="openModal()" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">restaurant_menu</span> Search by Ingredients
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="#" onclick="openModal()"  role="menuitem">
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
                                       href="guestlatest.php?category=Main%20Dish&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">restaurant</span>
                                        <span class="mt-1 text-center">Main Dish</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Appetizers%20%26%20Snacks&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">tapas</span>
                                        <span class="mt-1 text-center">Appetizers & Snacks</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Soups%20%26%20Stews&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">ramen_dining</span>
                                        <span class="mt-1 text-center">Soups & Stews</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Salads%20%26%20Sides&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">dinner_dining</span>
                                        <span class="mt-1 text-center">Salads & Sides</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Brunch&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">brunch_dining</span>
                                        <span class="mt-1 text-center">Brunch</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Desserts%20%26%20Sweets&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">icecream</span>
                                        <span class="mt-1 text-center">Desserts & Sweets</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Drinks%20%26%20Beverages&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">local_bar</span>
                                        <span class="mt-1 text-center">Drinks & Beverages</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Vegetables&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">grass</span>
                                        <span class="mt-1 text-center">Vegetables</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Occasional&sort=latest">
                                        <span class="material-icons text-orange-400 text-3xl">cake</span>
                                        <span class="mt-1 text-center">Occasional</span>
                                    </a>
                                    <a class="flex flex-col items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md" 
                                       href="guestlatest.php?category=Healthy%20%26%20Special%20Diets&sort=latest">
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
                                       href="guestlatest.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">new_releases</span> Latest
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="guestlatest.php?sort=most_popular" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">trending_up</span> Most Popular
                                    </a>
                                    <a class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="guestlatest.php?sort=most_favorite" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">favorite</span> Most Favorite
                                    </a>
                                </div>
                            </div>
                        </div>                <!-- Favorites -->
                <a class="flex items-center text-orange-400 transition-colors" 
                   href="#" onclick="openModal()">
                    <span class="material-icons text-orange-400 hover:text-orange-500">bookmark</span>
                </a>

                <!-- Sign In -->
                <a href="signin.php">
                    <button class="py-2 px-3 rounded bg-orange-400 text-white hover:bg-orange-500">
                        SIGN IN
                    </button>
                </a>
            </div>
        </div>

        <!-- Mobile Navigation (phones + tablets) -->
        <div class="lg:hidden" 
             x-show="mobileMenuOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
            
            <!-- Mobile Nav Links -->
            <div class="px-4 py-3 border-t border-orange-200">
                <!-- Home -->
                <a class="flex items-center py-3 px-3 text-orange-400 hover:bg-orange-100 rounded-lg transition-colors" 
                   href="guestdashboard.php">
                    <span class="material-icons mr-3">home</span>
                    <span>Home</span>
                </a>

                <!-- Search Dropdown -->
                <div class="space-y-1">
                    <button @click="mobileSearchOpen = !mobileSearchOpen" 
                            class="w-full flex items-center justify-between py-3 px-3 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons mr-3 text-orange-400">search</span>
                            <span class="text-orange-400">Search Options</span>
                        </div>
                        <span :class="{ 'rotate-180': mobileSearchOpen }" 
                              class="material-icons transition-transform duration-200 text-orange-400">arrow_drop_down</span>
                    </button>
                    <div x-show="mobileSearchOpen" x-cloak class="overflow-hidden">
                    <a href="#" onclick="openModal()" 
                       class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6">
                      <span class="material-icons mr-3 text-orange-400 text-sm">restaurant_menu</span>
                      <span class="text-sm">Search by Ingredients</span>
                    </a>
                    <a href="#" onclick="openModal()" 
                       class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6">
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
                                   href="guestlatest.php?category=Main%20Dish&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">restaurant</span>
                                    <span class="text-xs">Main Dish</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Appetizers%20%26%20Snacks&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">tapas</span>
                                    <span class="text-xs">Appetizers</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Soups%20%26%20Stews&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">ramen_dining</span>
                                    <span class="text-xs">Soups</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Salads%20%26%20Sides&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">dinner_dining</span>
                                    <span class="text-xs">Salads</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Brunch&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">brunch_dining</span>
                                    <span class="text-xs">Brunch</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Desserts%20%26%20Sweets&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">icecream</span>
                                    <span class="text-xs">Desserts</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Drinks%20%26%20Beverages&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">local_bar</span>
                                    <span class="text-xs">Drinks</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Vegetables&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">grass</span>
                                    <span class="text-xs">Vegetables</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Occasional&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">cake</span>
                                    <span class="text-xs">Occasional</span>
                                </a>
                                <a class="flex items-center py-2 px-2 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors" 
                                   href="guestlatest.php?category=Healthy%20%26%20Special%20Diets&sort=latest">
                                    <span class="material-icons mr-2 text-orange-400 text-lg">health_and_safety</span>
                                    <span class="text-xs">Healthy</span>
                                </a>
                            </div>
                        </div>
                    </div>

                <!-- Sort Dropdown -->
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
                               href="guestlatest.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">new_releases</span>
                                <span class="text-sm">Latest</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="guestlatest.php?sort=most_popular">
                                <span class="material-icons mr-3 text-orange-400 text-sm">trending_up</span>
                                <span class="text-sm">Most Popular</span>
                            </a>
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="guestlatest.php?sort=most_favorite">
                                <span class="material-icons mr-3 text-orange-400 text-sm">favorite</span>
                                <span class="text-sm">Most Favorite</span>
                            </a>
                        </div>
                    </div>

                <a href="#" onclick="openModal()" 
                   class="flex items-center px-3 py-3 text-orange-400 transition-colors">
                  <span class="material-icons mr-3 text-orange-400 hover:text-orange-500">bookmark</span>
                  <span>Favorites</span>
                </a>

                <!-- Sign In -->
                <a href="signin.php">
                    <button class="py-2 px-3 w-full rounded bg-orange-400 text-white hover:bg-orange-500">
                        SIGN IN
                    </button>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Login Required Modal -->
<div id="loginModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur-sm">
  <div class="absolute bottom-0 left-0 right-0 w-full max-w-md mx-auto bg-white/95 shadow-xl rounded-t-2xl p-6 transform translate-y-full transition-transform duration-300" id="modalContent">
    
    <!-- Header -->
    <div class="flex justify-between items-start pb-3">
      <h5 class="text-lg font-bold text-gray-900">
        You must log in first
      </h5>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">✕</button>
    </div>

    <!-- Body -->
    <div class="text-gray-600 mb-6">
      To like, save to favorites, repost, and unlock all features, please sign in or create an account.
    </div>

    <!-- Footer -->
    <div class="flex justify-end gap-2">
      <button onclick="closeModal()" 
        class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-medium hover:-translate-y-0.5 transition">
        Cancel
      </button>
      <a href="signin.php" 
        class="px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 hover:-translate-y-0.5 transition">
        Sign In
      </a>
      <a href="signin.php?signup=true" 
        class="px-4 py-2 rounded-lg bg-orange-500 text-white font-medium hover:bg-orange-700 hover:-translate-y-0.5 transition">
        Sign Up
      </a>
    </div>
  </div>
</div>

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

                <div 
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
                </div>
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

<div class="tag-container mb-2">
    <h1 class="text-md lg:text-2xl md:text-lg font-bold mb-3">Popular Tags For You</h1>
    <div class="flex flex-wrap gap-1.5 sm:gap-2">
        <?php 
        // Array of Tailwind color classes matching the original design
        $gradientColors = [
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
        
        // Get array keys for random selection
        $colorKeys = array_keys($gradientColors);
        
        // Loop through top tags (assuming $top_tags is available from your PHP logic)
        foreach ($top_tags as $tag => $count) {
            $encodedTag = urlencode($tag);
            // Randomly select a color scheme
            $randomColorKey = $colorKeys[array_rand($colorKeys)];
            $colorClass = $gradientColors[$randomColorKey];
            
            echo '<a href="guestlatest.php?tag=' . $encodedTag . '" 
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
  const recipes = <?= json_encode($suggested_result) ?>;

  function shuffleArray(array) {
    let shuffled = array.slice();
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  function renderSuggestions() {
    const shuffled = shuffleArray(recipes);
    const container = document.getElementById('suggestions-container');
    container.innerHTML = '';

    // Main (big) card
    const main = shuffled[0];
    container.innerHTML += `
      <div class="lg:col-span-2 bg-white dark:bg-card-dark rounded-lg overflow-hidden flex flex-col md:flex-row shadow-sm border border-border-light dark:border-border-dark group cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
           onclick="location.href='guestrecipe_details.php?id=${main.id}'">
        <div class="md:w-1/2">
          <img src="${main.image || 'uploads/default-placeholder.png'}" alt="${main.recipe_name}" 
               class="w-full h-48 lg:h-96 md:h-72 sm:h-60 object-cover rounded-md mx-auto md:mx-0"/>
        </div>
        <div class="p-4 md:p-6 md:w-1/2 flex flex-col justify-center">
          <h3 class="text-xl font-bold mb-1">${main.recipe_name}</h3>

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

          <p class="text-subtext-light dark:text-subtext-dark mb-4 text-sm line-clamp-2">${main.recipe_description || ''}</p>
          <button class="w-full bg-gradient-to-r from-orange-400 to-orange-600 text-white font-bold py-2 px-4 rounded-md shadow-md hover:shadow-lg transition-all duration-300 group-hover:scale-105 text-sm">
            Try Now
          </button>
        </div>
      </div>
    `;

    // Side small cards
    const smallCards = shuffled.slice(1, 4);
    let smallHTML = `<div class="lg:col-span-1 space-y-4">`;
    smallCards.forEach(row => {
      smallHTML += `
        <div class="bg-white dark:bg-card-dark p-3 rounded-lg flex items-center gap-3 shadow-sm border border-border-light dark:border-border-dark cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
             onclick="location.href='guestrecipe_details.php?id=${row.id}'">
          <img src="${row.image || 'uploads/default-placeholder.png'}" alt="${row.recipe_name}" 
               class="w-20 h-20 rounded-md object-cover"/>
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm line-clamp-2">${row.recipe_name}</h4>
                        <div class="flex items-center gap-1 mb-0.5">
                            <p class="text-xs text-primary-dark dark:text-primary-light" 
                               style="background: ${row.badge_gradient}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">
                               @${row.username}
                            </p>
                            ${(row.badge_name && row.badge_name !== "No Badge Yet") 
                                ? `<img src="${row.badge_icon}" alt="${row.badge_name}" class="w-4 h-4 object-contain">` 
                                : `<span class="w-4 h-4 inline-block"></span>`}
                        </div>

                        <p class="text-xs text-subtext-light dark:text-subtext-dark">
                            ${row.recipe_description.substring(0, 50)}...
                        </p>
                    </div>
        </div>
      `;
    });
    smallHTML += `</div>`;
    container.innerHTML += smallHTML;
  }

  // First render
  renderSuggestions();

  // Auto refresh every 3s (like before)
  setInterval(renderSuggestions, 3000);
</script>



<!-- Latest Recipes listing -->
<div class="container-fluid mt-7 relative  relative recipe-scroll-container">
    <p class="text-md lg:text-2xl md:text-lg"  
    style="text-align: left; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
    <span>Latest Recipes</span>
<a href="guestlatest.php" style="text-decoration: none; color: black; font-size: 12px;">See More...</a>
</h3>

    
    <!-- Scrollable wrapper for mobile -->
    <div class="overflow-x-auto w-full custom-scroll">
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
                $recipeUrl = 'guestrecipe_details.php?id=' . $row["id"];

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
        
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[385px]"
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
                <p class="text-sm md:text-lg lg:text-lg h-10 lg:h-12 md:h-12 font-bold text-gray-800 mb-1">
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
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
                        <a href="#" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation(); openModal();">
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
                <div class="flex items-center justify-between text-sm md:text-lg lg:text-lg mt-3 lg:mt-2 md:mt-2">

                    <!-- Like -->
                     <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                    data-recipe-id="<?= $row['id'] ?>"
                    onclick="event.stopPropagation(); openModal();">
                <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                <span class="like-count"><?= $layk_count ?></span>
            </button>

            <!-- Bookmark button -->
            <button class="flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                    data-recipe-id="<?= $row['id'] ?>"
                    onclick="event.stopPropagation(); openModal();">
                <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
            </button>

                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700">
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
        <a href="guestlatest.php?category=<?= urlencode($categoryFilter ?? '') ?>&sort=most_popular" style="text-decoration: none; font-size: 12px; color: black;">See More...</a>
    </h3>

    <!-- Scrollable wrapper for mobile -->
    <div class="overflow-x-auto w-full custom-scroll">
        <!-- Recipe Grid -->
    <div class="grid grid-flow-col auto-cols-max gap-6 p-2" id="popular-recipe-grid">
            <?php
            if ($popular_result->num_rows > 0) {
                while ($row = $popular_result->fetch_assoc()) {
                    $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                    $recipeUrl = 'guestrecipe_details.php?id=' . $row["id"];

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
        
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[385px] "
                     data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">

            <div class="relative">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row["recipe_name"]) ?>"
                     class="w-full h-36 md:h-48 lg:h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>
            </div>

            <div class="py-2 px-3">
                <!-- Title -->
                <p class="text-sm md:text-lg lg:text-lg h-10 lg:h-12 md:h-12 font-bold text-gray-800 mb-1"> 
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
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
                        <a href="#" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation(); openModal();">
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
                <div class="flex items-center justify-between text-sm md:text-lg lg:text-lg mt-3 lg:mt-2 md:mt-2">
                    <!-- Like -->
                    <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                    data-recipe-id="<?= $row['id'] ?>"
                    onclick="event.stopPropagation(); openModal();">
                <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                <span class="like-count"><?= $layk_count ?></span>
            </button>

            <!-- Bookmark button -->
            <button class="flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                    data-recipe-id="<?= $row['id'] ?>"
                    onclick="event.stopPropagation(); openModal();">
                <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
            </button>


                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700">
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
        <a href="guestlatest.php?category=<?= urlencode($categoryFilter ?? '') ?>&sort=most_favorite" style="text-decoration: none; font-size: 12px; color: black;">See More...</a>
    </h3>
    
    <!-- Scrollable wrapper for mobile -->
    <div class="overflow-x-auto w-full custom-scroll">
        <!-- Recipe Grid -->
    <div class="grid grid-flow-col auto-cols-max gap-6 p-2" id="favorite-recipe-grid">
            <?php
            if ($favorite_result->num_rows > 0) {
                while ($row = $favorite_result->fetch_assoc()) {
                    $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                    $recipeUrl = 'guestrecipe_details.php?id=' . $row["id"];

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
    
            <div class="bg-white rounded-lg shadow-md overflow-hidden group recipe-card cursor-pointer h-[320px] sm:h-[300px] md:w-72 md:h-[390px] lg:w-72 lg:h-[385px]"
             data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">

            <div class="relative">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row["recipe_name"]) ?>"
                     class="w-full h-36 md:h-48 lg:h-48 object-cover group-hover:scale-105 transition-transform duration-300"/>
            </div>

            <div class="py-2 px-3">
                <!-- Title -->
                <p class="text-sm md:text-lg lg:text-lg h-10 lg:h-12 md:h-12 font-bold text-gray-800 mb-1"> 
                    <?= htmlspecialchars(mb_strimwidth($row["recipe_name"], 0, 40, "...")) ?><p>

                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 text-xs lg:text-md md:text-sm md:text-sm mb-2 sm:block sm:text-xs h-10 sm:h-6 md:h-8 lg:h-8">
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
                        <a href="#" 
                           class="font-bold text-xs md:text-sm lg:text-sm"
                           style="background: <?= $gradient ?>;
                                  -webkit-background-clip: text;
                                  -webkit-text-fill-color: transparent;"
                           onclick="event.stopPropagation(); openModal();">
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
                <div class="flex items-center justify-between text-sm md:text-lg lg:text-lg mt-3 lg:mt-2 md:mt-2">
                    <!-- Like -->
                    <button class="like-action flex items-center gap-1 text-gray-500 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?>"
                            data-recipe-id="<?= $row['id'] ?>"
                            onclick="event.stopPropagation(); openModal();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="like-count"><?= $layk_count ?></span>
                    </button>

                <!-- Bookmark button -->
                <button class=" flex items-center space-x-1 text-gray-500 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                        data-recipe-id="<?= $row['id'] ?>"
                        onclick="event.stopPropagation(); openModal();">
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                     <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
                </button>
                    <!-- Comment icon -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center text-gray-500 hover:text-gray-700">
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


<!-- Back to Top Button -->
<a href="#" class="btn btn-lg bg-orange-500 text-white btn-lg-square back-to-top px-4 py-3 rounded-sm" id="backToTopBtn">
    <i class="bi bi-arrow-up"></i>
</a>

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

 <script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".like-icon").forEach(icon => {
        icon.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("i");

            fetch("like_action.php", {
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
        });
    });
});

</script>

 <script>

document.addEventListener("DOMContentLoaded", function() {
    const searchDropdown = document.querySelector('.search-dropdown');
    const dropdownToggle = searchDropdown.querySelector('.dropdown-toggle');

    // Show dropdown on click
    dropdownToggle.addEventListener('click', function(event) {
        searchDropdown.classList.toggle('active'); // Toggle active class
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchDropdown.contains(event.target)) {
            searchDropdown.classList.remove('active'); // Remove active class
        }
    });
});
</script>


<script>  

 function openLoginModal() {
    let modal = document.getElementById("loginModal");
    modal.classList.remove("hidden");
    setTimeout(() => {
        modal.classList.add("show");
    }, 10);
}

function closeLoginModal() {
    let modal = document.getElementById("loginModal");
    modal.classList.remove("show");
    setTimeout(() => {
        modal.classList.add("hidden");
    }, 300);
}

// Optional: close when clicking the black overlay
document.getElementById("loginModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});
   

</script>

<script>
    let lastScrollTop = 0;
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop < lastScrollTop) {
            // Scrolling up
            navbar.classList.add('show');
        } else {
            // Scrolling down
            navbar.classList.remove('show');
        }

        lastScrollTop = scrollTop;
    });
</script>
<script>
function openModal() {
    const modal = document.getElementById("loginModal");
    const content = document.getElementById("modalContent");
    const backToTop = document.getElementById("backToTopBtn");

    // Show modal
    modal.classList.remove("hidden");

    // Prevent background scroll
    document.body.style.overflow = "hidden";

    // Hide back-to-top button
    if (backToTop) backToTop.style.display = "none";

    setTimeout(() => {
        content.classList.remove("translate-y-full");
    }, 10);
}

function closeModal() {
    const modal = document.getElementById("loginModal");
    const content = document.getElementById("modalContent");
    const backToTop = document.getElementById("backToTopBtn");

    // Slide modal down
    content.classList.add("translate-y-full");

    setTimeout(() => {
        // Hide modal
        modal.classList.add("hidden");

        // Restore background scroll
        document.body.style.overflow = "";

        // Show back-to-top button again
        if (backToTop) backToTop.style.display = "block";
    }, 300);
}

// Close modal when clicking outside content
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("loginModal");
    const content = document.getElementById("modalContent");

    modal.addEventListener("click", (e) => {
        if (!content.contains(e.target)) {
            closeModal();
        }
    });
});
</script>

        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/main.js"></script>
    </div>
</body>
</html>