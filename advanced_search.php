    <?php
session_start();
require 'db.php'; // Include database connection
require 'badge_utils.php';

// Fetch user details from session
$username = htmlspecialchars($_SESSION['username']);
$email = htmlspecialchars($_SESSION['email']);
$user_id = $_SESSION['user_id'];

// Initialize search variables from GET parameters
$keyword = isset($_GET['keyword']) ? urldecode(trim($_GET['keyword'])) : '';
$categoryFilter = isset($_GET['category']) ? urldecode(trim($_GET['category'])) : '';
$difficulty = isset($_GET['difficulty']) ? urldecode(trim($_GET['difficulty'])) : '';
$preparationType = isset($_GET['preparation_type']) ? urldecode(trim($_GET['preparation_type'])) : '';
$cookingTime = isset($_GET['cooking_time']) ? urldecode(trim($_GET['cooking_time'])) : '';
$servings = isset($_GET['servings']) ? (int)$_GET['servings'] : null;
$budgetLevel = isset($_GET['budget']) ? urldecode(trim($_GET['budget'])) : '';

// Nutritional information with ranges
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

// Excluded ingredients
$excludedIngredients = isset($_GET['ingredient_excluded']) ? urldecode(trim($_GET['ingredient_excluded'])) : '';
$excludedIngredientsArray = [];
if (!empty($excludedIngredients)) {
    $excludedIngredientsArray = array_map('trim', explode(',', $excludedIngredients));
}

// Check for sorting parameter
$sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Initialize parameters and types for prepared statement
$params = [];
$types = '';

// Build the main SQL query
$sql = "SELECT recipe.id, recipe.user_id, recipe.recipe_name, recipe.image, recipe.category, 
               recipe.difficulty, recipe.preparation, recipe.budget, recipe.servings, recipe.cooktime, 
               recipe.recipe_description, recipe.tags,
               users.username, users.profile_picture,
               COUNT(DISTINCT favorites.user_id) AS favorite_count, 
               COUNT(DISTINCT likes.user_id) AS like_count,
               ub.badge_name, ub.badge_icon,
               nutritional_info.calories, nutritional_info.fat, nutritional_info.carbohydrates,
               nutritional_info.fiber, nutritional_info.cholesterol, nutritional_info.sodium,
               nutritional_info.protein, nutritional_info.sugar
        FROM recipe 
        JOIN users ON recipe.user_id = users.id
        LEFT JOIN favorites ON recipe.id = favorites.recipe_id
        LEFT JOIN likes ON recipe.id = likes.recipe_id
        LEFT JOIN ingredients ON recipe.id = ingredients.recipe_id
        LEFT JOIN equipments ON recipe.id = equipments.recipe_id
        LEFT JOIN nutritional_info ON recipe.id = nutritional_info.recipe_id
        LEFT JOIN instructions ON recipe.id = instructions.recipe_id
        LEFT JOIN (
            SELECT user_id, badge_name, badge_icon
            FROM user_badges ub1
            WHERE updated_at = (
                SELECT MAX(updated_at) 
                FROM user_badges ub2 
                WHERE ub1.user_id = ub2.user_id
            )
        ) ub ON ub.user_id = users.id
        WHERE recipe.status = 'approved'";

// Build conditions array
$conditions = [];

// Keyword search
if (!empty($keyword)) {
    $conditions[] = "(recipe.recipe_name LIKE ? OR recipe.recipe_description LIKE ? OR recipe.category LIKE ? 
                     OR recipe.difficulty LIKE ? OR recipe.preparation LIKE ? OR recipe.cooktime LIKE ? 
                     OR recipe.budget LIKE ? OR recipe.servings LIKE ? OR instructions.instruction_name LIKE ? 
                     OR ingredients.ingredient_name LIKE ? OR equipments.equipment_name LIKE ? OR recipe.tags LIKE ? OR users.username LIKE ?)";
    for ($i = 0; $i < 13; $i++) {
        $params[] = '%' . $keyword . '%';
        $types .= 's';
    }
}

// Category filter
if (!empty($categoryFilter)) {
    $conditions[] = "recipe.category = ?";
    $params[] = $categoryFilter;
    $types .= 's';
}

// Difficulty filter
if (!empty($difficulty)) {
    $conditions[] = "recipe.difficulty = ?";
    $params[] = $difficulty;
    $types .= 's';
}

// Preparation type filter
if (!empty($preparationType)) {
    $conditions[] = "recipe.preparation = ?";
    $params[] = $preparationType;
    $types .= 's';
}

// Cooking time filter
if (!empty($cookingTime)) {
    $conditions[] = "recipe.cooktime = ?";
    $params[] = $cookingTime;
    $types .= 's';
}

// Servings filter
if (!empty($servings)) {
    $conditions[] = "recipe.servings <= ?";
    $params[] = $servings;
    $types .= 'i';
}

// Budget filter
if (!empty($budgetLevel)) {
    $conditions[] = "recipe.budget = ?";
    $params[] = $budgetLevel;
    $types .= 's';
}

// Nutritional filters
$nutritionFields = [
    'calories' => $calories,
    'fat' => $fat,
    'carbohydrates' => $carbohydrates,
    'fiber' => $fiber,
    'cholesterol' => $cholesterol,
    'sodium' => $sodium,
    'protein' => $protein,
    'sugar' => $sugar
];

$nutritionRanges = [
    'calories' => $caloriesRange,
    'fat' => $fatRange,
    'carbohydrates' => $carbohydratesRange,
    'fiber' => $fiberRange,
    'cholesterol' => $cholesterolRange,
    'sodium' => $sodiumRange,
    'protein' => $proteinRange,
    'sugar' => $sugarRange
];

foreach ($nutritionFields as $field => $value) {
    if (!empty($value)) {
        $range = $nutritionRanges[$field];
        if ($range === 'lower' || $range === 'up') {
            $conditions[] = "nutritional_info.$field <= ?";
        } elseif ($range === 'higher' || $range === 'down') {
            $conditions[] = "nutritional_info.$field >= ?";
        }
        $params[] = $value;
        $types .= 'i';
    }
}

// Add conditions to SQL
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Exclude ingredients
if (!empty($excludedIngredientsArray)) {
    $excludedConditions = [];
    foreach ($excludedIngredientsArray as $ingredient) {
        $excludedConditions[] = "ingredients.ingredient_name LIKE ?";
        $params[] = '%' . $ingredient . '%';
        $types .= 's';
    }
    $sql .= " AND recipe.id NOT IN (SELECT recipe_id FROM ingredients WHERE " . implode(" OR ", $excludedConditions) . ")";
}

// Group by and sorting
$sql .= " GROUP BY recipe.id";

if ($sortParam === 'most_popular') {
    $sql .= " ORDER BY like_count DESC, recipe.created_at DESC";
} elseif ($sortParam === 'most_favorite') {
    $sql .= " ORDER BY favorite_count DESC, recipe.created_at DESC";
} else {
    $sql .= " ORDER BY recipe.created_at DESC";
}

// Execute query
$recipes = [];
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
}

// Check if search was performed
$searchPerformed = !empty($_GET['keyword']) || !empty($_GET['category']) || !empty($_GET['difficulty']) || 
                   !empty($_GET['preparation_type']) || !empty($_GET['cooking_time']) || !empty($_GET['servings']) || 
                   !empty($_GET['budget']) || !empty($_GET['calories']) || !empty($_GET['fat']) || 
                   !empty($_GET['carbohydrates']) || !empty($_GET['fiber']) || !empty($_GET['cholesterol']) || 
                   !empty($_GET['sodium']) || !empty($_GET['protein']) || !empty($_GET['sugar']) || 
                   !empty($_GET['ingredient_excluded']);

$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet'    => 'linear-gradient(90deg, #504F4F, #555)',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Advanced Recipe Search</title>
            <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <script defer="" src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .input-group:focus-within .input-label,
        .input-group .form-input:not(:placeholder-shown)+.input-label {
            transform: translateY(-1.5rem) scale(0.8);
            color: #f59e0b;
        }
        .input-label {
            transition: all 0.2s ease-out;
            transform-origin: top left;
        }
        .form-input:focus {
            border-color: #f59e0b;
        }
        .input-error .form-input {
            border-color: #ef4444 !important;
        }
        .input-error .input-label {
            color: #ef4444;
        }
        .recipe-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .recipe-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .loading {
            animation: pulse 2s infinite;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
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
</head>
<body class="bg-orange-50 h-[200vh]">
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

<!--- BODY --->
    <div class="mx-4 py-10 px-3">
        <!-- Search Form -->
        <div id="search-form" class="bg-white p-8 md:p-12 rounded-2xl shadow-lg <?php echo $searchPerformed ? 'mb-8' : ''; ?>">
            <form id="recipeForm" method="GET" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Recipe Details</h2>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="keyword">Search any recipe, ingredient, and equipment.</label>
                                <div class="relative" id="keyword-group">
                                    <span class="material-icons absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">search</span>
                                    <input class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3 form-input" 
                                           id="keyword" name="keyword" placeholder="Search any keyword" type="text" 
                                           value="<?php echo htmlspecialchars($keyword); ?>"/>
                                    <p class="mt-1 text-xs text-red-600 hidden" id="keyword-error"></p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="ingredient_excluded">Ingredient search (excluded)</label>
                                <div class="relative">
                                    <span class="material-icons absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">do_not_disturb_on</span>
                                    <input class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" 
                                           id="ingredient_excluded" name="ingredient_excluded" placeholder="Enter ingredient (excluded)" type="text"
                                           value="<?php echo htmlspecialchars($excludedIngredients); ?>"/>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="category">Category</label>
                                    <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="category" name="category">
                                        <option value="">Category</option>
                                        <?php 
                                        $categories = ["Main Dish", "Appetizers & Snacks", "Soups & Stews", "Salads & Sides", "Brunch", 
                                                      "Desserts & Sweets", "Drinks & Beverages", "Vegetables", "Occasional", "Healthy & Special Diets"];
                                        foreach ($categories as $cat) {
                                            $selected = ($categoryFilter === $cat) ? 'selected' : '';
                                            echo "<option value=\"$cat\" $selected>$cat</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="difficulty">Difficulty Level</label>
                                    <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="difficulty" name="difficulty">
                                        <option value="">Difficulty Level</option>
                                        <?php 
                                        $difficulties = ["Easy", "Medium", "Hard", "Extreme"];
                                        foreach ($difficulties as $diff) {
                                            $selected = ($difficulty === $diff) ? 'selected' : '';
                                            echo "<option value=\"$diff\" $selected>$diff</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="preparation_type">Preparation Type</label>
                                    <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="preparation_type" name="preparation_type">
                                        <option value="">Preparation Type</option>
                                        <?php 
                                        $prepTypes = ["Raw", "Boiling", "Steaming", "Blanching", "Simmering", "Sauteing", "Frying", "Grilling", 
                                                     "Roasting", "Baking", "Broiling", "Poaching", "Braising", "Stewing", "Smoking", "Fermenting", 
                                                     "Pickling", "Marinating", "Blending", "Shaking", "Stirring", "Juicing", "Brewing", "Infusing", 
                                                     "Chilling", "Carbonating", "Muddling", "Pouring Over Ice"];
                                        foreach ($prepTypes as $prep) {
                                            $selected = ($preparationType === $prep) ? 'selected' : '';
                                            echo "<option value=\"$prep\" $selected>$prep</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="cooking_time">Cooking Time</label>
                                    <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="cooking_time" name="cooking_time">
                                        <option value="">Cooking Time</option>
                                        <?php 
                                        $cookTimes = [
                                            "1 to 5 minutes" => "1-5 minutes",
                                            "5 to 15 minutes" => "5-15 minutes", 
                                            "15 to 30 minutes" => "15-30 minutes",
                                            "30 to 60 minutes" => "30-60 minutes",
                                            "1 to 3 hours" => "1-3 hours",
                                            "Above 3 hours" => "Above 3 hours"
                                        ];
                                        foreach ($cookTimes as $value => $display) {
                                            $selected = ($cookingTime === $value) ? 'selected' : '';
                                            echo "<option value=\"$value\" $selected>$display</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div id="servings-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="servings">Servings</label>
                                    <input class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" 
                                           id="servings" name="servings" placeholder="Enter servings" type="number" min="1"
                                           value="<?php echo $servings ? $servings : ''; ?>"/>
                                    <p class="mt-1 text-xs text-red-600 hidden" id="servings-error"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="budget">Budget Level</label>
                                    <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 :text-sm py-2 lg:py-3" id="budget" name="budget">
                                        <option value="">Budget Level</option>
                                        <?php 
                                        $budgets = [
                                            "Ultra Low Budget (₱0 - ₱100)", "Low Budget (₱101 - ₱250)", 
                                            "Mid Budget (₱251 - ₱500)", "High Budget (₱501 - ₱1,000)", 
                                            "Luxury Budget (₱1,001 above )"
                                        ];
                                        foreach ($budgets as $budget) {
                                            $selected = ($budgetLevel === $budget) ? 'selected' : '';
                                            echo "<option value=\"$budget\" $selected>$budget</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold text-gray-800">Nutritional Information</h2>
                            <button class="flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-700" type="button" id="suggest-values">
                                <span class="material-symbols-outlined mr-1 text-base">auto_awesome</span>
                                Suggest Values
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Nutritional fields with PHP values -->
                            <div>
                                <div class="flex items-start gap-2">
                                    <div class="relative flex-grow input-group" id="calories-group">
                                        <input class="form-input block w-full border-gray-300 rounded-lg shadow-sm sm:text-sm py-2 lg:py-3 pt-4 focus:ring-orange-400 focus:border-orange-400 peer" 
                                               id="calories" name="calories" placeholder=" " type="text" 
                                               value="<?php echo $calories ? $calories : ''; ?>"/>
                                        <label class="input-label absolute top-3 left-3 text-gray-500 text-sm pointer-events-none" for="calories">Calories (kcal)</label>
                                        <p class="mt-1 text-xs text-red-600 hidden" id="calories-error"></p>
                                    </div>
                                    <div class="relative">
                                        <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="calories_range" name="calories_range">
                                            <option value="lower" <?php echo ($caloriesRange === 'lower') ? 'selected' : ''; ?>>≤ (Max)</option>
                                            <option value="higher" <?php echo ($caloriesRange === 'higher') ? 'selected' : ''; ?>>≥ (Min)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Repeat for other nutritional fields -->
                            <?php 
                            $nutritionInputs = [
                                'fat' => ['Fat (g)', $fat, $fatRange],
                                'carbohydrates' => ['Carbohydrates (g)', $carbohydrates, $carbohydratesRange],
                                'fiber' => ['Fiber (g)', $fiber, $fiberRange],
                                'protein' => ['Protein (g)', $protein, $proteinRange],
                                'sodium' => ['Sodium (mg)', $sodium, $sodiumRange],
                                'cholesterol' => ['Cholesterol (mg)', $cholesterol, $cholesterolRange],
                                'sugar' => ['Sugar (g)', $sugar, $sugarRange]
                            ];

                            foreach ($nutritionInputs as $field => $data): 
                                list($label, $value, $range) = $data;
                            ?>
                            <div>
                                <div class="flex items-start gap-2">
                                    <div class="relative flex-grow input-group" id="<?php echo $field; ?>-group">
                                        <input class="form-input block w-full border-gray-300 rounded-lg shadow-sm sm:text-sm py-2 lg:py-3 pt-4 focus:ring-orange-400 focus:border-orange-400 peer" 
                                               id="<?php echo $field; ?>" name="<?php echo $field; ?>" placeholder=" " type="text" 
                                               value="<?php echo $value ? $value : ''; ?>"/>
                                        <label class="input-label absolute top-3 left-3 text-gray-500 text-sm pointer-events-none" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                        <p class="mt-1 text-xs text-red-600 hidden" id="<?php echo $field; ?>-error"></p>
                                    </div>
                                    <div class="relative">
                                        <select class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-400 focus:border-orange-400 sm:text-sm py-2 lg:py-3" id="<?php echo $field; ?>_range" name="<?php echo $field; ?>_range">
                                            <option value="lower" <?php echo ($range === 'lower') ? 'selected' : ''; ?>>≤ (Max)</option>
                                            <option value="higher" <?php echo ($range === 'higher') ? 'selected' : ''; ?>>≥ (Min)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-col md:flex-row items-center justify-center gap-4">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="w-full md:w-auto inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-full shadow-sm text-gray-600 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-300">
                        Reset Filters
                    </a>
                    <button class="w-full md:w-auto inline-flex items-center justify-center px-20 py-4 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-orange-400 hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-400 transition-colors duration-300" type="submit">
                        Search Recipes
                    </button>
                </div>
            </form>
        </div>

        <?php if ($searchPerformed): ?>
        <!-- Search Results -->
        <div id="search-results" class="w-full">
            <div class="text-center mb-4">  
                <h2 class="text-3xl font-bold text-gray-900">Search Results</h2>

                <?php if (count($recipes) > 0): ?>
                    <p class="text-gray-600 mt-2">
                        Found <?php echo count($recipes); ?> 
                        <?php echo count($recipes) === 1 ? 'recipe' : 'recipes'; ?>
                    </p>
                <?php endif; ?>
            </div>
            <!-- Sort Options-->
            <?php if (count($recipes) > 0): ?>
            <div class="flex justify-center mb-6">
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                    <a href="<?php 
                        $currentParams = $_GET;
                        $currentParams['sort'] = 'latest';
                        echo $_SERVER['PHP_SELF'] . '?' . http_build_query($currentParams);
                    ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors <?php echo $sortParam === 'latest' ? 'bg-orange-400 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        Latest
                    </a>
                    <a href="<?php 
                        $currentParams = $_GET;
                        $currentParams['sort'] = 'most_popular';
                        echo $_SERVER['PHP_SELF'] . '?' . http_build_query($currentParams);
                    ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors <?php echo $sortParam === 'most_popular' ? 'bg-orange-400 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        Most Popular
                    </a>
                    <a href="<?php 
                        $currentParams = $_GET;
                        $currentParams['sort'] = 'most_favorite';
                        echo $_SERVER['PHP_SELF'] . '?' . http_build_query($currentParams);
                    ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-colors <?php echo $sortParam === 'most_favorite' ? 'bg-orange-400 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        Most Favorited
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($recipes) > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 px-1 sm:px-2 md:px-0">
                <?php foreach ($recipes as $recipe): ?>
        <div class="recipe-card w-full sm:w-[95%] mx-auto bg-white rounded-xl shadow-md overflow-hidden fade-in">
                    <img src="<?php echo htmlspecialchars($recipe['image'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>" 
                         alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>"                  class="w-full h-60 sm:h-64 md:h-48 lg:h-48 object-cover">
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 h-12"><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                        <?php 
                        $description = $recipe['recipe_description'] ?: 'No description available';
                        $truncatedDescription = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                        ?>
                        <p class="text-gray-600 text-sm mb-4 h-10 overflow-hidden"><?php echo htmlspecialchars($truncatedDescription); ?></p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="flex items-center">
                                <span class="material-icons text-base mr-1">schedule</span>
                                <?php echo htmlspecialchars($recipe['cooktime']); ?>
                            </span>
                            <span class="flex items-center">
                                <span class="material-icons text-base mr-1">person</span>
                                <?php echo htmlspecialchars($recipe['servings']); ?> servings
                            </span>
                            <span class="px-2 py-1 bg-gray-100 rounded-full text-xs font-medium capitalize">
                                <?php echo htmlspecialchars($recipe['difficulty']); ?>
                            </span>
                        </div>

                        <!-- User info with profile picture, gradient username, and badge -->
                        <div class="flex items-center mb-4 text-sm">
                            <?php if (!empty($recipe['profile_picture'])): ?>
                                <img src="uploads/profile_pics/<?php echo htmlspecialchars($recipe['profile_picture']); ?>" 
                                     alt="Profile" 
                                     class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full mr-2 object-cover">
                            <?php else: ?>
                                <div class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full mr-2 bg-gray-300 flex items-center justify-center">
                                    <span class="material-icons text-gray-600 text-sm lg:text-xl md:text-lg">person</span>
                                </div>
                            <?php endif; ?>

                            <?php 
                                $badgeName = $recipe['badge_name'] ?: 'No Badge Yet';
                                $gradient = $badgeGradients[$badgeName] ?? $badgeGradients['No Badge Yet'];
                            ?>
                            
                            <span class="font-semibold bg-clip-text text-transparent" 
                                  style="background: <?php echo $gradient; ?>;
                                        -webkit-background-clip: text;
                                        -webkit-text-fill-color: transparent;">
                                @<?php echo htmlspecialchars($recipe['username']); ?>
                            </span>

                            <?php if ($recipe['badge_icon'] && $badgeName !== 'No Badge Yet'): ?>
                                <span class="ml-2" title="<?php echo htmlspecialchars($badgeName); ?>">
                                    <img class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4" src="<?php echo $recipe['badge_icon']; ?>"> 
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Nutritional info if available -->
                        <?php if ($recipe['calories'] || $recipe['protein']): ?>
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <?php if ($recipe['calories']): ?>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="font-semibold text-gray-900"><?php echo $recipe['calories']; ?></div>
                                <div class="text-gray-600">Calories</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($recipe['protein']): ?>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="font-semibold text-gray-900"><?php echo $recipe['protein']; ?>g</div>
                                <div class="text-gray-600">Protein</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Engagement stats -->
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="flex items-center">
                                <span class="material-icons text-base mr-1">favorite</span>
                                <?php echo $recipe['like_count']; ?> likes
                            </span>                            
                            <span class="flex items-center">
                                <span class="material-icons text-base mr-1">bookmark</span>
                                <?php echo $recipe['favorite_count']; ?> favorites
                            </span>
                        </div>

                        <a href="recipe_details.php?id=<?php echo $recipe['id']; ?>" 
                           class="w-full block text-center bg-orange-400 hover:bg-orange-500 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                            View Recipe
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- No Results -->
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <span class="material-icons text-6xl text-gray-400 mb-4">search_off</span>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Recipes Found</h3>
                    <p class="text-gray-600 mb-6">Try adjusting your search criteria or exploring different ingredients.</p>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" 
                       class="inline-flex items-center px-6 py-2 lg:py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-orange-500 hover:bg-orange-600">
                        <span class="material-icons mr-2">refresh</span>
                        Try Again
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Suggest nutritional values based on category
            document.getElementById('suggest-values').addEventListener('click', function() {
                const category = document.getElementById('category').value;
                const suggestions = {
                    "Main Dish": { calories: 500, protein: 30, fat: 18, carbohydrates: 55 },
                    "Appetizers & Snacks": { calories: 200, protein: 6, fat: 8, carbohydrates: 25 },
                    "Soups & Stews": { calories: 250, protein: 12, fat: 10, carbohydrates: 30 },
                    "Salads & Sides": { calories: 180, protein: 5, fat: 7, carbohydrates: 20 },
                    "Brunch": { calories: 400, protein: 18, fat: 14, carbohydrates: 40 },
                    "Desserts & Sweets": { calories: 280, protein: 4, fat: 12, carbohydrates: 45 },
                    "Drinks & Beverages": { calories: 120, protein: 2, fat: 2, carbohydrates: 25 },
                    "Vegetables": { calories: 100, protein: 4, fat: 3, carbohydrates: 15 },
                    "Occasional": { calories: 600, protein: 25, fat: 25, carbohydrates: 70 },
                    "Healthy & Special Diets": { calories: 350, protein: 20, fat: 10, carbohydrates: 35 }
                };

                const defaultSuggestion = { calories: 400, protein: 20, fat: 15, carbohydrates: 50 };
                const suggestion = suggestions[category] || defaultSuggestion;

                // Fill in suggested values
                Object.keys(suggestion).forEach(key => {
                    const input = document.getElementById(key);
                    if (input && !input.value) {
                        input.value = suggestion[key];
                    }
                });

                // Show success message
                const button = this;
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="material-symbols-outlined mr-1 text-base">check_circle</span>Values Suggested!';
                button.classList.add('text-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('text-green-600');
                }, 2000);
            });

            // Form validation
            const form = document.getElementById('recipeForm');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
                document.querySelectorAll('.text-red-600').forEach(el => el.classList.add('hidden'));

                // Validate keyword length
                const keyword = document.getElementById('keyword').value.trim();
                if (keyword && keyword.length < 2) {
                    showError('keyword', 'Search keyword must be at least 2 characters long.');
                    isValid = false;
                }

                // Validate servings
                const servings = document.getElementById('servings').value;
                if (servings && (isNaN(servings) || parseInt(servings) < 1)) {
                    showError('servings', 'Please enter a number greater than or equal to 1 for servings.');
                    isValid = false;
                }

                // Validate nutritional values
                const nutritionFields = ['calories', 'fat', 'carbohydrates', 'fiber', 'protein', 'sodium', 'cholesterol', 'sugar'];
                nutritionFields.forEach(field => {
                    const value = document.getElementById(field).value;
                    if (value && (isNaN(value) || parseFloat(value) < 0)) {
                        showError(field, 'Please enter a valid number.');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Real-time validation
            document.getElementById('keyword').addEventListener('input', function() {
                const value = this.value.trim();
                clearError('keyword');
                
                if (value.length > 0 && value.length < 2) {
                    showError('keyword', 'Search keyword must be at least 2 characters long.');
                }           
            });

            document.getElementById('servings').addEventListener('input', function() {
                const value = this.value;
                clearError('servings');

                if (value && (isNaN(value) || parseInt(value) < 1)) {
                    showError('servings', 'Please enter a number greater than or equal to 1 for servings.');
                }
            });

            // Nutritional field validation
            const nutritionFields = ['calories', 'fat', 'carbohydrates', 'fiber', 'protein', 'sodium', 'cholesterol', 'sugar'];
            nutritionFields.forEach(field => {
                document.getElementById(field).addEventListener('input', function() {
                    const value = this.value;
                    clearError(field);
                    
                    if (value && (isNaN(value) || parseFloat(value) < 0)) {
                        showError(field, 'Please enter a valid number.');
                    }
                });
            });

            function showError(fieldId, message) {
                const group = document.getElementById(fieldId + '-group');
                const error = document.getElementById(fieldId + '-error');
                if (group && error) {
                    group.classList.add('input-error');
                    error.textContent = message;
                    error.classList.remove('hidden');
                }
            }

            function clearError(fieldId) {
                const group = document.getElementById(fieldId + '-group');
                const error = document.getElementById(fieldId + '-error');
                if (group && error) {
                    group.classList.remove('input-error');
                    error.classList.add('hidden');
                }
            }
        });
    </script>



        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="js/main.js"></script>
</body>
</html>