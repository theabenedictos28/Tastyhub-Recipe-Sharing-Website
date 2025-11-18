<?php
session_start();
require 'db.php';
require 'badge_utils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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
$sql = "SELECT recipe.id,recipe.user_id, recipe.recipe_name, recipe.image, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, 
               recipe.budget, recipe.servings, recipe.cooktime, users.username, users.profile_picture, user_badges.badge_name, user_badges.badge_icon,
               (SELECT COUNT(*) FROM favorites WHERE favorites.recipe_id = recipe.id) AS favorite_count,
               (SELECT COUNT(*) FROM likes WHERE likes.recipe_id = recipe.id) AS like_count
        FROM recipe 
        JOIN users ON recipe.user_id = users.id
        LEFT JOIN user_badges ON users.id = user_badges.user_id
        LEFT JOIN favorites ON recipe.id = favorites.recipe_id
        LEFT JOIN likes ON recipe.id = likes.recipe_id
        LEFT JOIN ingredients ON recipe.id = ingredients.recipe_id
        LEFT JOIN equipments ON recipe.id = equipments.recipe_id
        LEFT JOIN nutritional_info ON recipe.id = nutritional_info.recipe_id
        WHERE recipe.status = 'approved'";

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
$conditions[] = "favorites.user_id = $user_id"; // Ensure the user must own the favorites

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
    $conditions[] = "recipe.servings = ?";
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
    $sql .= " ORDER BY like_count DESC"; // Sort by like count descending (Most Popular)
} elseif ($sortParam === 'most_favorite') {
    $sql .= " ORDER BY favorite_count DESC"; // Sort by favorite count descending (Most Favorite)
} else {
    $sql .= " ORDER BY favorites.created_at DESC"; // Default sort by creation date
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
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
            <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

<script defer="" src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .font-nunito {
            font-family: 'Nunito', sans-serif;
        }
        [x-cloak] {
            display: none !important;
        }
    #backToTopBtn {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 99;
}
        </style>
        <style type="text/tailwindcss">
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        .spinner {
            width: 56px;
            height: 56px;
            border: 5px solid;
            border-color: #10B981 transparent;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
        </head>

    <body class="bg-orange-50">
               <!-- Spinner Start -->
            <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <!-- Spinner End -->

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

<main class="lg:pt-12 pt-10 px-2 mx-auto lg:px-4 mb-5">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 lg:gap-6 md:gap-6">
        <?php
        if ($result->num_rows > 0) {
            $badgeGradients = [
                'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
                'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
                'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
                'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
                'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
                'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
                'No Badge Yet'    => 'linear-gradient(90deg, #504F4F, #555)',
            ];

            while ($row = $result->fetch_assoc()) {
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
        
        <div class="bg-white lg:rounded-lg md:rounded-lg rounded-sm shadow-lg overflow-hidden transform md:hover:scale-105 transition-transform duration-300 flex md:flex-col cursor-pointer"
             data-recipe-id="<?= $row['id'] ?>"
             onclick="window.location.href='<?= $recipeUrl ?>'">
            
            <!-- Recipe Image -->
            <img alt="Recipe Image" 
                 class="w-1/3 md:w-full h-auto md:h-48 object-cover" 
                 src="<?= $imagePath ?>"/>
            
            <div class="p-4 flex flex-col justify-between w-2/3 md:w-full">
                <div>
                    <!-- Recipe Title -->
                    <h3 class="text-sm lg:text-lg font-semibold text-gray-800 mb-2 truncate"><?= htmlspecialchars($row["recipe_name"]) ?></h3>
                    
                    <!-- Recipe Description (if available) -->
                    <?php if (!empty($row["recipe_description"])): ?>
                        <p class="text-gray-600 lg:text-sm md:text-sm text-xs lg:mb-4 md:mb-4 mb-2 sm:block">
                            <?php 
                            $description = htmlspecialchars($row["recipe_description"]);
                            echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description; 
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Author Profile -->
                    <div class="flex items-center gap-1">
                    <!-- Profile Image (if available) -->
                    <?php if (!empty($row["profile_picture"])): ?>
                        <img alt="Author's profile picture" 
                             class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                             src="<?= htmlspecialchars('uploads/profile_pics/' . $row["profile_picture"]) ?>"/>
                    <?php else: ?>
                        <div class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                    <?php endif; ?>
                        <div class="flex items-center gap-1">
                            <!-- Username with Badge Gradient -->
                            <a href="userprofile.php?username=<?= urlencode($row["username"]) ?>" 
                               class="lg:text-sm md:text-sm text-xs font-bold"
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
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-between items-center text-gray-500 mt-3">
                    <!-- Like Button -->
                    <button class="like-action flex items-center space-x-1 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?> transition-colors"
                            data-recipe-id="<?= $row['id'] ?>"
                            <?php if (!isset($_SESSION['id'])): ?>
                                data-bs-toggle="modal" 
                                data-bs-target="#loginModal"
                            <?php endif; ?>
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="text-sm like-count"><?= $layk_count ?></span>
                    </button>
                    
                    <!-- Bookmark Button -->
                    <button class="bookmark-btn flex items-center space-x-1 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                            data-recipe-id="<?= $row['id'] ?>"
                            <?php if (!isset($_SESSION['id'])): ?>
                                data-bs-toggle="modal" 
                                data-bs-target="#loginModal"
                            <?php endif; ?>
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark"></i>
                        <span class="text-xs"><?= $is_favorite ? 'Added' : 'Add' ?></span>
                    </button>
                    
                    <!-- Comment Button -->
                    <a href="<?= $recipeUrl ?>#comments" 
                       class="flex items-center space-x-1 hover:text-blue-500 transition-colors"
                       onclick="event.stopPropagation();">
                        <span class="material-icons text-m">comment</span>
                    </a>
                </div>

            </div>
        </div>
        
        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No recipes found.</p>
                  </div>';
        }
        ?>
    </div>

</main>
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
// Updated JavaScript - replace all existing like/favorite scripts with this:
document.addEventListener("DOMContentLoaded", function() {
    // Handle favorite actions
    document.querySelectorAll(".favorite-action").forEach(btn => {
        btn.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("i");

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
                } else if (data.status === "removed") {
                    iconElement.classList.remove("fas");
                    iconElement.classList.add("far");
                    // Since this is favorites page, remove the card when unfavorited
                    this.closest('.col-lg-3, .col-md-4, .col-sm-6, .col-12').remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    // Handle like actions
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
                } else if (data.status === "removed") {
                    iconElement.classList.remove("fas");
                    iconElement.classList.add("far");
                }
                // Update the count
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
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>