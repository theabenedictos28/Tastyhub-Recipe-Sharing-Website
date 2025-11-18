<?php
session_start();
require 'db.php';
require 'badge_utils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$email = htmlspecialchars($_SESSION['email']);
$user_id = $_SESSION['user_id'];

$ingredientsArray = [];
if (isset($_GET['ingredients'])) {
$ingredientsArray = !empty($_GET['ingredients']) ? explode(',', $_GET['ingredients']) : [];
}

$excludeArray = [];
if (isset($_GET['exclude'])) {
    $excludeArray = !empty($_GET['exclude']) ? explode(',', $_GET['exclude']) : [];  // Changed $excludedArray to $excludeArray
}

$sortParam = $_GET['sort'] ?? 'matching';

if (!empty($ingredientsArray)) {
    $likeClauses = [];
    foreach ($ingredientsArray as $ingredient) {
        $likeClauses[] = "ingredients.ingredient_name LIKE ?";
    }

    $whereClause = "recipe.status = 'approved' AND (" . implode(' OR ', $likeClauses) . ")";

    // Excluded ingredients
    if (!empty($excludeArray)) {
        $excludeConditions = [];
        foreach ($excludeArray as $ex) {
            $excludeConditions[] = "recipe.id NOT IN (
                SELECT recipe_id FROM ingredients WHERE ingredient_name LIKE ?
            )";
        }
        $whereClause .= " AND " . implode(' AND ', $excludeConditions);
    }

    $sql = "SELECT recipe.id, recipe.user_id, recipe.recipe_name, recipe.image, 
                   users.username, users.profile_picture, 
                   user_badges.badge_name, user_badges.badge_icon,
                   COUNT(DISTINCT favorites.recipe_id) AS favorite_count, 
                   COUNT(DISTINCT likes.recipe_id) AS like_count,
                   GROUP_CONCAT(DISTINCT ingredients.ingredient_name SEPARATOR '|') AS matching_ingredients,
                   COUNT(DISTINCT CASE WHEN " . implode(' OR ', $likeClauses) . " THEN ingredients.ingredient_name END) AS matching_count
            FROM recipe
            JOIN users ON recipe.user_id = users.id
            LEFT JOIN user_badges ON users.id = user_badges.user_id
            LEFT JOIN favorites ON recipe.id = favorites.recipe_id
            LEFT JOIN likes ON recipe.id = likes.recipe_id
            LEFT JOIN ingredients ON recipe.id = ingredients.recipe_id
            WHERE $whereClause
            GROUP BY recipe.id";

    // Sorting
    if ($sortParam === 'most_favorite') $sql .= " ORDER BY favorite_count DESC";
    elseif ($sortParam === 'most_popular') $sql .= " ORDER BY like_count DESC";
    else $sql .= " ORDER BY matching_count DESC, recipe.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) die("SQL error: " . $conn->error);

    // Bind parameters: included first, then excluded
    $bindParams = [];
    foreach ($ingredientsArray as $ingredient) $bindParams[] = '%' . $ingredient . '%'; // CASE WHEN
    foreach ($ingredientsArray as $ingredient) $bindParams[] = '%' . $ingredient . '%'; // WHERE
    foreach ($excludeArray as $ex) $bindParams[] = '%' . $ex . '%'; // Excluded

    $stmt->bind_param(str_repeat('s', count($bindParams)), ...$bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Search by Ingredients</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
            <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .font-nunito { font-family: 'Nunito', sans-serif; }
            [x-cloak] { display: none !important; }
                /* Hide button by default */
    #backToTopBtn {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 99;
    }
            .btn-primary {
                margin-top: 1rem;
                width: 100%;
            }
            
        </style>    
        <style type="text/tailwindcss">
        body {
            font-family: 'Poppins', sans-serif;
        }
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
        #loading-overlay,
        #recipe-results {
            display: none;
            transition: opacity 0.3s ease-in-out;
        }
        .recipe-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .matching-ingredient {
            background-color: #10B981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin: 2px;
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
    
<style>
     @media (max-width: 768px) {
        .container-fluid{
            padding-top: 40px;
        }
     }
</style>





<div class="container-fluid mx-auto p-4 lg:p-10">
      <div class="flex justify-center">
  <!-- Ingredient Search Section -->
  <div class="w-full max-w-2xl bg-white rounded-2xl shadow-lg transition-opacity duration-300 mx-auto p-6 min-h-[350px]" id="ingredient-section">
    
    <div class="flex flex-col lg:flex-row gap-6">
      
      <!-- Add Ingredients (Left) -->
      <div class="flex-1">
        <h2 class="text-base font-semibold text-gray-900 mb-3">Add Your Ingredients</h2>
        
        <div class="relative mb-3">
          <input class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-700 text-sm placeholder-gray-400" 
                 id="ingredient-input" 
                 placeholder="chicken" 
                 type="text"/>
          <button class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-500 hover:bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center transition-colors" 
                  id="add-ingredient-btn-top">
            <span class="material-icons text-sm">add</span>
          </button>
        </div>

        <div class="flex justify-start mb-3">
          <button class="flex items-center gap-1.5 text-xs text-gray-600 hover:text-gray-800 transition-colors" 
                  id="clear-all-btn">
            <span class="material-icons text-xs">delete_sweep</span>
            <span>Clear All</span>
          </button>
        </div>

        <div class="space-y-1 min-h-[80px]" id="ingredient-list">
          <?php foreach ($ingredientsArray as $ingredient): ?>
            <div class="flex items-center justify-between bg-gray-50 hover:bg-gray-100 px-4 py-2 rounded-lg border border-gray-200 shadow-sm transition-all">
              <span class="text-gray-700 text-sm ingredient-name"><?= htmlspecialchars(trim($ingredient)) ?></span>
              <button class="remove-ingredient-btn text-gray-400 hover:text-red-500 transition-colors">
                <span class="material-icons text-xs">close</span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Divider -->
      <div class="hidden lg:block border-l border-gray-200"></div>
      <div class="lg:hidden border-t border-gray-200"></div>

      <!-- Exclude Ingredients (Right) -->
      <div class="flex-1">
        <h2 class="text-base font-semibold text-gray-900 mb-3">Exclude Ingredients</h2>
        
        <div class="relative mb-3">
          <input class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-700 text-sm placeholder-gray-400" 
                 id="exclude-input" 
                 placeholder="nuts" 
                 type="text"/>
          <button class="absolute right-2 top-1/2 -translate-y-1/2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center transition-colors" 
                  id="add-exclude-btn-top">
            <span class="material-icons text-sm">remove</span>
          </button>
        </div>

        <div class="flex justify-start mb-3">
          <button class="flex items-center gap-1.5 text-xs text-gray-600 hover:text-gray-800 transition-colors" 
                  id="clear-exclude-btn">
            <span class="material-icons text-xs">delete_sweep</span>
            <span>Clear All</span>
          </button>
        </div>

        <div class="space-y-1 min-h-[80px]" id="exclude-list">
    <?php foreach ($excludeArray as $exclude): ?>
            <div class="flex items-center justify-between bg-red-50 hover:bg-red-100 px-4 py-2 rounded-lg border border-red-200 transition-colors">
                <span class="text-gray-700 text-sm exclude-name"><?= htmlspecialchars(trim($exclude)) ?></span>
                <button class="remove-exclude-btn text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-icons text-xs">close</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

      </div>

    </div>

    <!-- Search Button -->
    <div class="pt-6 mt-4 border-t border-gray-200">
      <button class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-5 rounded-lg flex items-center justify-center gap-2 transition-all duration-200 text-sm shadow hover:shadow-md transform hover:-translate-y-0.5" 
              id="search-recipe-btn">
        <span class="material-icons text-base">search</span>
        <span>SEARCH RECIPE!</span>
      </button>
    </div>

  </div>
</div>

            <!-- Loading Overlay -->
            <div class="absolute inset-0  backdrop-blur-sm flex items-center justify-center rounded-2xl z-50" id="loading-overlay">
                <div class="text-center">
                    <img src="img/fryingpan.gif" alt="Loading..." class="w-68 h-7S0 mx-auto mb-4">
                    <p class="text-gray-700 font-semibold">Searching for delicious recipes...</p>
                </div>
            </div>
            
            <!-- Recipe Results -->
            <div class="w-full" id="recipe-results">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-800">Recipe Results</h2>
                    <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg flex items-center gap-2 transition duration-200" 
                            id="back-to-search-btn">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back To Search
                    </button>
                </div>
                
<!-- Recipe Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 lg:gap-6 md:gap-6" id="recipe-grid">
     <?php if (!empty($ingredientsArray) && isset($result) && $result && $result->num_rows > 0): ?>
        <?php 
        $totalIngredients = count($ingredientsArray);
        $badgeGradients = [
            'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
            'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
            'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
            'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
            'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
            'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
            'No Badge Yet'    => 'linear-gradient(90deg, #504F4F, #555)',
        ];

        while ($row = $result->fetch_assoc()):
            // Favorite check
            $check_fav_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
            $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
            $check_fav_stmt->execute();
            $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;

            // Like check
            $check_like_stmt = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND recipe_id = ?");
            $check_like_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
            $check_like_stmt->execute();
            $is_like = $check_like_stmt->get_result()->num_rows > 0;

            $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
            $matchingIngredients = !empty($row['matching_ingredients']) ? explode('|', $row['matching_ingredients']) : [];
            $gradient = isset($badgeGradients[$row['badge_name']]) ? $badgeGradients[$row['badge_name']] : $badgeGradients['No Badge Yet'];
        ?>
        
<div class="bg-white lg:rounded-lg md:rounded-lg rounded-sm shadow-lg overflow-hidden transform md:hover:scale-105 transition-transform duration-300 flex md:flex-col recipe-card cursor-pointer"
     data-recipe-id="<?= $row['id'] ?>"
     onclick="window.location.href='recipe_details.php?id=<?= $row['id'] ?>'">

            
            <!-- Image -->
            <img alt="Recipe Image" 
                 class="w-1/3 md:w-full h-auto md:h-48 object-cover" 
                 src="<?= $imagePath ?>"/>

            <!-- Card Content -->
            <div class="p-4 flex flex-col justify-between w-2/3 md:w-full">
                <div>
                <!-- Title -->
                <h3 class="text-sm lg:text-lg font-semibold text-gray-800 mb-1 truncate"><?= htmlspecialchars($row["recipe_name"]) ?></h3>

                <!-- Username + Badge (moved under recipe name) -->
                    <!-- Author Profile -->
                    <div class="flex items-center gap-1">
                    <!-- Profile Image (if available) -->
                    <?php if (!empty($row["profile_picture"])): ?>
                        <img alt="Author's profile picture" 
                             class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                             src="<?= htmlspecialchars('uploads/profile_pics/' . $row["profile_picture"]) ?>"/>
                    <?php else: ?>
                        <div class="w-7 h-7 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                    <?php endif; ?>
                        <div class="flex items-center gap-1">
                            <!-- Username with Badge Gradient -->
                            <a href="userprofile.php?username=<?= urlencode($row["username"]) ?>" 
                               class="lg:text-sm md:text-sm text-xs font-medium"
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
                <!-- Matching Ingredients -->
                <?php
                $matchRatio = $row['matching_count'] / max(1, $totalIngredients);
                $matchColor = 'bg-red-100 text-red-700';
                if ($matchRatio >= 0.75) $matchColor = 'bg-green-100 text-green-700';
                elseif ($matchRatio >= 0.4) $matchColor = 'bg-yellow-100 text-yellow-700';
                ?>
                <div class="flex items-center gap-1 <?= $matchColor ?> font-semibold px-2 py-1 rounded-full mb-2 text-xs">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span><?= $row['matching_count'] ?> of <?= $totalIngredients ?> matched</span>
                </div>
                <div class="mb-3 h-10 lg:h-12 md:h-12 overflow-hidden">
                    <div class="flex flex-wrap gap-1">
                        <?php 
                        $displayIngredients = array_slice($matchingIngredients, 0, 3); 
                        foreach ($displayIngredients as $ingredient): 
                        ?>
                            <span class="px-2 py-0.5 bg-gray-100 rounded-full text-xs text-gray-700 max-w-[110px] truncate inline-block">
                                <?= htmlspecialchars(trim($ingredient)) ?>
                            </span>
                        <?php endforeach; ?>

                        <?php if (count($matchingIngredients) > 3): ?>
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs inline-block">
                                +<?= count($matchingIngredients) - 3 ?> more
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                </div>



                <!-- Action Buttons -->
                <div class="flex justify-between items-center text-gray-500 mt-auto">
                    <!-- Like Button -->
                    <button class="like-action flex items-center space-x-1 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?> transition-colors"
                            data-recipe-id="<?= $row['id'] ?>"
                            <?php if (!isset($_SESSION['id'])): ?>
                                data-bs-toggle="modal" 
                                data-bs-target="#loginModal"
                            <?php endif; ?>
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="text-sm like-count"><?= $row['like_count'] ?></span>
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
                    <a href="recipe_details.php?id=<?= $row['id'] ?>#comments"
                       class="flex items-center space-x-1 hover:text-blue-500 transition-colors"
                       onclick="event.stopPropagation();">
                        <span class="material-icons text-m">comment</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
<?php else: ?>
    <div class="col-span-full text-center py-12">
        <div class="bg-orange-50 rounded-lg p-8">
            <i class="fas fa-search text-6xl text-orange-300 mb-4"></i>
            <p class="text-gray-600 font-bold text-xl mb-2">No recipes found</p>
            <p class="text-gray-500">We couldn't find any recipes matching your ingredients.</p>
            <p class="text-gray-400 text-sm mt-2">Try searching with fewer or different ingredients!</p>
        </div>
    </div>
<?php endif; ?>
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



<template id="ingredient-template">
    <div class="flex items-center justify-between bg-gray-50 hover:bg-gray-100 px-4 py-2 rounded-lg border border-gray-200 transition-colors">
        <span class="text-gray-700 text-sm ingredient-name"></span>
        <button class="remove-ingredient-btn text-gray-400 hover:text-red-500 transition-colors">
            <span class="material-icons text-lg">close</span>
        </button>
    </div>
</template>

<template id="exclude-template">
    <div class="flex items-center justify-between bg-red-50 hover:bg-red-100 px-4 py-2 rounded-lg border border-red-200 transition-colors">
        <span class="text-gray-700 text-sm exclude-name"></span>
        <button class="remove-exclude-btn text-gray-400 hover:text-red-500 transition-colors">
            <span class="material-icons text-lg">close</span>
        </button>
    </div>
</template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
             const ingredientInput = document.getElementById('ingredient-input');
            const addIngredientBtnTop = document.getElementById('add-ingredient-btn-top');
            const ingredientList = document.getElementById('ingredient-list');
            const ingredientTemplate = document.getElementById('ingredient-template');
            const clearAllBtn = document.getElementById('clear-all-btn');
            
            const excludeInput = document.getElementById('exclude-input');
            const addExcludeBtnTop = document.getElementById('add-exclude-btn-top');
            const excludeList = document.getElementById('exclude-list');
            const excludeTemplate = document.getElementById('exclude-template');
            const clearExcludeBtn = document.getElementById('clear-exclude-btn');
            
            const searchRecipeBtn = document.getElementById('search-recipe-btn');
            const ingredientSection = document.getElementById('ingredient-section');
            const loadingOverlay = document.getElementById('loading-overlay');
            const recipeResults = document.getElementById('recipe-results');
            const backToSearchBtn = document.getElementById('back-to-search-btn');

            <?php if (!empty($ingredientsArray)): ?>
        ingredientSection.style.display = 'none';
        recipeResults.style.display = 'block';
        recipeResults.style.opacity = '1';
    <?php endif; ?>
            
            const addIngredient = () => {
        const ingredientText = ingredientInput.value.trim();
        if (ingredientText) {
            const templateClone = ingredientTemplate.content.cloneNode(true);
            templateClone.querySelector('.ingredient-name').textContent = ingredientText;
            const removeBtn = templateClone.querySelector('.remove-ingredient-btn');
            removeBtn.addEventListener('click', (e) => {
                e.target.closest('.flex').remove();
            });
            ingredientList.appendChild(templateClone);
            ingredientInput.value = '';
            ingredientInput.focus();
        }
    };
    
    addIngredientBtnTop.addEventListener('click', addIngredient);
    ingredientInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addIngredient();
        }
    });
    const addExclude = () => {
        const excludeText = excludeInput.value.trim();
        if (excludeText) {
            const templateClone = excludeTemplate.content.cloneNode(true);
            templateClone.querySelector('.exclude-name').textContent = excludeText;
            const removeBtn = templateClone.querySelector('.remove-exclude-btn');
            removeBtn.addEventListener('click', (e) => {
                e.target.closest('.flex').remove();
            });
            excludeList.appendChild(templateClone);
            excludeInput.value = '';
            excludeInput.focus();
        }
    };
    
    addExcludeBtnTop.addEventListener('click', addExclude);
    excludeInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addExclude();
        }
    });

            // Attach remove listeners to existing exclude items (from PHP)
document.querySelectorAll('.remove-exclude-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.target.closest('.flex').remove();
    });
});

           document.querySelectorAll('.remove-ingredient-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.flex').remove();
        });
    });
    
    clearAllBtn.addEventListener('click', () => {
        ingredientList.innerHTML = '';
    });
    
    clearExcludeBtn.addEventListener('click', () => {
        excludeList.innerHTML = '';
    });


              
    searchRecipeBtn.addEventListener('click', () => {
        const ingredients = Array.from(ingredientList.querySelectorAll('.ingredient-name'))
            .map(span => span.textContent.trim());
        
        const excludes = Array.from(excludeList.querySelectorAll('.exclude-name'))
            .map(span => span.textContent.trim());
        
        if (ingredients.length === 0) {
            alert('Please add at least one ingredient before searching!');
            return;
        }
        
        ingredientSection.style.opacity = '0';
        loadingOverlay.style.display = 'flex';
        setTimeout(() => {
            loadingOverlay.style.opacity = '1';
        }, 10);
        const queryString = ingredients.join(',');
        let url = `search_ingredients.php?ingredients=${encodeURIComponent(queryString)}`;
        
        if (excludes.length > 0) {
            url += `&exclude=${encodeURIComponent(excludes.join(','))}`;
        }
                
               setTimeout(() => {
            window.location.href = url;
        }, 1500);
    });
            
            backToSearchBtn?.addEventListener('click', () => {
        recipeResults.style.opacity = '0';
        setTimeout(() => {
            recipeResults.style.display = 'none';
            ingredientSection.style.display = 'block';
            setTimeout(() => {
                ingredientSection.style.opacity = '1';
            }, 50);
        }, 300);
    });
            
document.querySelectorAll('.recipe-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.favorite-action') || e.target.closest('.like-action')) {
                return;
            }
            const recipeId = this.dataset.recipeId;
            window.location.href = `recipe_details.php?id=${recipeId}`;
        });
    });
            
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

        });
    </script>
</body>
</html>

