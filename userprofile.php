<?php
session_start();
require 'db.php'; // Include database connection

// Redirect to sign-in page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Check if a username is provided in the URL
if (!isset($_GET['username'])) {
    header("Location: dashboard.php");
    exit;
}

$username = urldecode($_GET['username']); // Get the clicked username

// Fetch user details from the database
$sql = "SELECT id, email FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['id']; // Get user ID
$email = $user['email']; // Get user email

// Check if the username matches the logged-in user's username
$loggedInUsername = $_SESSION['username']; // Assuming this is set during login
if ($username === $loggedInUsername) {
    // Redirect to the profile page
    header("Location: profile.php");
    exit();
}

// Fetch total likes for all recipes uploaded by the user
$total_likes_sql = "
    SELECT COUNT(likes.id) AS total_likes 
    FROM likes 
    JOIN recipe ON likes.recipe_id = recipe.id 
    WHERE recipe.user_id = ?";
$total_likes_stmt = $conn->prepare($total_likes_sql);
$total_likes_stmt->bind_param("i", $user_id);
$total_likes_stmt->execute();
$total_likes_result = $total_likes_stmt->get_result();
$total_likes = $total_likes_result->fetch_assoc()['total_likes'];

// Fetch total users who have favorited the recipes uploaded by the user
$total_favorites_sql = "
    SELECT COUNT(*) AS total_favorites 
    FROM favorites 
    JOIN recipe ON favorites.recipe_id = recipe.id 
    WHERE recipe.user_id = ?";
$total_favorites_stmt = $conn->prepare($total_favorites_sql);
$total_favorites_stmt->bind_param("i", $user_id);
$total_favorites_stmt->execute();
$total_favorites_result = $total_favorites_stmt->get_result();
$total_favorites = $total_favorites_result->fetch_assoc()['total_favorites'];

// Fetch total uploaded recipes for the user (only approved)
$total_uploaded_recipes_sql = "SELECT COUNT(*) AS total_uploaded_recipes FROM recipe WHERE user_id = ? AND status = 'approved'";
$total_uploaded_recipes_stmt = $conn->prepare($total_uploaded_recipes_sql);
$total_uploaded_recipes_stmt->bind_param("i", $user_id);
$total_uploaded_recipes_stmt->execute();
$total_uploaded_recipes_result = $total_uploaded_recipes_stmt->get_result();
$total_uploaded_recipes = $total_uploaded_recipes_result->fetch_assoc()['total_uploaded_recipes'];

$check_fav_sql = "SELECT * FROM favorites WHERE user_id = ? AND recipe_id = ?";
$check_stmt = $conn->prepare($check_fav_sql);

$check_layk_sql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";

// Fetch reposted recipes
$reposts_sql = "SELECT reposts.id AS repost_id, reposts.caption, recipe.id AS recipe_id, recipe.recipe_name ,recipe.image, reposts.created_at 
                 FROM reposts 
                 JOIN recipe ON reposts.recipe_id = recipe.id 
                 WHERE reposts.user_id = ? 
                 ORDER BY reposts.created_at DESC";
$reposts_stmt = $conn->prepare($reposts_sql);
$reposts_stmt->bind_param("i", $user_id);
// Execute the statement and check for errors
if ($reposts_stmt->execute()) {
    $reposts_result = $reposts_stmt->get_result();
} else {
    // Handle the error
    echo "Error executing query: " . $reposts_stmt->error;
    $reposts_result = null; // Set to null to avoid undefined variable warning
}

// Fetch user biography from the database
$biography_sql = "SELECT biography FROM users WHERE id = ?";
$biography_stmt = $conn->prepare($biography_sql);
$biography_stmt->bind_param("i", $user_id);
$biography_stmt->execute();
$biography_result = $biography_stmt->get_result();
$biography = $biography_result->fetch_assoc()['biography'] ?? ''; 

    // Fetch user profile picture
    $profile_pic_sql = "SELECT profile_picture FROM users WHERE id = ?";
    $profile_pic_stmt = $conn->prepare($profile_pic_sql);
    $profile_pic_stmt->bind_param("i", $user_id);
    $profile_pic_stmt->execute();
    $profile_pic_result = $profile_pic_stmt->get_result();
    $user_data = $profile_pic_result->fetch_assoc();
    $profile_picture = $user_data['profile_picture'] ?? null;

    // Set default profile picture if none exists
    $profile_picture_url = $profile_picture ? 'uploads/profile_pics/' . $profile_picture : 'img/no_profile.png';


// Get total likes received on user's recipes
$sql_likes_count = "SELECT COUNT(*) AS like_count 
                    FROM likes l
                    JOIN recipe r ON l.recipe_id = r.id
                    WHERE r.user_id = ?";
$stmt_likes = $conn->prepare($sql_likes_count);
$stmt_likes->bind_param("i", $user_id);
$stmt_likes->execute();
$result_likes = $stmt_likes->get_result();
$like_count = $result_likes->fetch_assoc()['like_count'];

// Get total favorites received on user's recipes
$sql_fav_count = "SELECT COUNT(*) AS fav_count 
                  FROM favorites f
                  JOIN recipe r ON f.recipe_id = r.id
                  WHERE r.user_id = ?";
$stmt_fav = $conn->prepare($sql_fav_count);
$stmt_fav->bind_param("i", $user_id);
$stmt_fav->execute();
$result_fav = $stmt_fav->get_result();
$fav_count = $result_fav->fetch_assoc()['fav_count'];

// Get approved recipe count
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
$badge_icon = '';
$next_level_points = $levels[0]['points'];
$next_badge_name = $levels[0]['name'];

// Find current badge & next badge
foreach ($levels as $i => $level) {
    if ($total_points >= $level['points']) {
        $badge = $level['name'];
        $badge_icon = $level['icon'];
        if (isset($levels[$i + 1])) {
            $next_level_points = $levels[$i + 1]['points'];
            $next_badge_name = $levels[$i + 1]['name'];
        } else {
            // Already at max badge
            $next_level_points = $level['points'];
            $next_badge_name = 'Max Badge Achieved!';
        }
    }
}

// Save badge to database
$save_badge_sql = "INSERT INTO user_badges (user_id, badge_name, badge_icon) VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE badge_name = ?, badge_icon = ?";
$save_badge_stmt = $conn->prepare($save_badge_sql);
$save_badge_stmt->bind_param("issss", $user_id, $badge, $badge_icon, $badge, $badge_icon);
$save_badge_stmt->execute();

// Fetch only the recipes uploaded by this user, including the username
$sql = "SELECT recipe.id, recipe.recipe_name, recipe.image, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime, users.username, user_badges.badge_name, user_badges.badge_icon
        FROM recipe 
        JOIN users ON recipe.user_id = users.id 
        LEFT JOIN user_badges ON users.id = user_badges.user_id
        WHERE recipe.user_id = ? AND status = 'approved' 
        ORDER BY recipe.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Tasty Hub - Profile</title>
        <link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
<style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FFF7F0;
        }
        .badge-name {
  font-family: "DM Serif Text", serif;
  font-weight: 500;
  font-style: normal;
}
        .font-nunito { font-family: 'Nunito', sans-serif; }
        [x-cloak] { display: none !important; }

        #profile-tabs a {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>
<body>
<div class="min-h-screen bg-[#FFF7F0]">
    <header :class="{ '-translate-y-full': !showNav, 'translate-y-0': showNav }" 
            @scroll.window="
                if (window.scrollY > lastScrollY) {
                    showNav = false;
                } else {
                    showNav = true;
                }
                lastScrollY = window.scrollY;
            " 
            class="bg-orange-50 shadow-md fixed top-0 sticky left-0 right-0 z-50 transition-transform duration-300" 
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
        <div class="container mx-auto px-3">
            <div class="flex items-center justify-between py-3 gap-3">
                
                <a href="dashboard.php"  class="flex items-center text-decoration-none">
                <!-- Logo -->
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
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="search_ingredients.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">restaurant_menu</span> Search by Ingredients
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
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
                                <div class="grid grid-cols-3 gap-2 p-4">
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
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">new_releases</span> Latest
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php?sort=most_popular" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">trending_up</span> Most Popular
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="latest.php?sort=most_favorite" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">favorite</span> Most Favorite
                                    </a>
                                </div>
                            </div>
                        </div>

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
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="profile.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">account_circle</span> Profile
                                    </a>
                                    <a class="change-password-btn flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 cursor-pointer"
                                       role="menuitem">
                                      <span class="material-icons mr-3 text-orange-400">vpn_key</span> Change Password
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="dashboard.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">dashboard</span> Dashboard
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="submit_recipe.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">post_add</span> Submit a Recipe
                                    </a>
                                    <div class="border-t border-gray-100"></div>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
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
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6 change-password-btn"> 
                                <span class="material-icons mr-3 text-orange-400 text-sm">vpn_key</span>
                                <span class="text-sm">Change Password</span>
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

<main class="container mx-auto px-4 lg:px-2 py-6">
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
<aside class="lg:col-span-3">
  <div class="bg-white p-4 rounded-xl shadow-md flex flex-col items-center relative">

    <?php
    $reportingUserId = $_SESSION['user_id'];
    $reportedUserId = $user_id;

    // Check if this user has a pending report
    $checkReport = $conn->prepare("
        SELECT id FROM reports 
        WHERE reporting_user_id = ? 
          AND reported_user_id = ? 
          AND status = 'Pending'
        LIMIT 1
    ");
    $checkReport->bind_param("ii", $reportingUserId, $reportedUserId);
    $checkReport->execute();
    $checkReportResult = $checkReport->get_result();
    $hasPending = $checkReportResult->num_rows > 0;
    ?>

    <!-- Report Button -->
    <button type="button"
      class="absolute top-4 right-4 flex items-center justify-center w-8 h-8 rounded-full transition-all duration-300 z-10 hover:bg-gray-100"
      id="report-button" 
      title="Report User">
      <img src="img/flag.png" alt="Report" class="w-5 h-5">
    </button>

    <!-- Profile Picture -->
    <div class="relative inline-block mb-3">
      <div class="w-24 h-24 rounded-full overflow-hidden mx-auto border-2 border-white shadow-md">
        <img 
          id="profilePicPreview" 
          src="<?php echo htmlspecialchars($profile_picture_url); ?>" 
          alt="Profile Picture" 
          class="w-full h-full object-cover"
        >
      </div>
    </div>

    <!-- Username -->
    <div class="flex items-center justify-center mb-3">
      <h1 class="text-lg font-bold text-gray-800">
        <?php echo htmlspecialchars($username); ?>
      </h1>
    </div>

<!-- Badge Section -->
<div class="mb-3 flex justify-center">
  <?php if ($badge && $badge !== 'No Badge Yet'): ?>
    <?php
      $badgeColors = [
        'Freshly Baked'   => 'orange', // orange-500
        'Kitchen Star'    => 'green', // green-500
        'Flavor Favorite' => 'darkgreen', // green-800
        'Gourmet Guru'    => 'darkviolet', // violet-600
        'Culinary Star'   => 'blue', // blue-500
        'Culinary Legend' => 'red', // red-500
      ];
      $badgeColor = $badgeColors[$badge] ?? '#9ca3af'; // default gray
    ?>
    <button 
      onclick="toggleModal('achievementsModal')"
      class="flex items-center gap-2 px-6 py-2 rounded-full bg-white transition-all duration-300"
      style="border: 2px solid <?= $badgeColor ?>;
             box-shadow: 0 0 12px <?= $badgeColor ?>;
             color: <?= $badgeColor ?>;"
      onmouseover="this.style.background='<?= $badgeColor ?>11'; this.style.boxShadow='0 0 18px <?= $badgeColor ?>';"
      onmouseout="this.style.background='white'; this.style.boxShadow='0 0 12px <?= $badgeColor ?>';">
      <!-- Badge Icon -->
      <img src="<?= htmlspecialchars($badge_icon); ?>" 
           alt="<?= htmlspecialchars($badge); ?> badge" 
           class="w-10 h-10 object-contain"/>
      <!-- Badge Name -->
      <span class="badge-name font-semibold tracking-wide text-lg">
        <?= htmlspecialchars($badge); ?>
      </span>
    </button>
  <?php else: ?>
    <!-- Default (No Badge Yet) -->
    <button 
      onclick="toggleModal('achievementsModal')"
      class="flex items-center gap-2 px-4 py-2 rounded-full bg-white transition-all duration-300 text-gray-600"
      style="border: 2px solid #9CA3AF;
             box-shadow: 0 0 8px rgba(156,163,175,0.3);"
      onmouseover="this.style.background='#F9FAFB'; this.style.boxShadow='0 0 12px rgba(156,163,175,0.5)';"
      onmouseout="this.style.background='white'; this.style.boxShadow='0 0 8px rgba(156,163,175,0.3)';">
      <span class="font-semibold tracking-wide px-8 py-2  text-sm">Achievements</span>
    </button>
  <?php endif; ?>
</div>

<!-- Biography -->
    <div class="mt-2 text-center">
      <?php if (empty($biography)): ?>
        <p class="text-gray-500 italic">No biography available</p>
      <?php else: ?>
        <p class="whitespace-pre-line text-sm text-gray-700"><?php echo htmlspecialchars($biography); ?></p>
      <?php endif; ?>
    </div>

  
    <!-- Achievements Modal -->
    <div id="achievementsModal"
         class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
         onclick="if(event.target === this) toggleModal('achievementsModal')">
      <div class="bg-white rounded-xl shadow-lg w-11/12 md:w-3/4 lg:w-2/3 max-h-[85vh] overflow-y-auto p-4"
      onclick="event.stopPropagation()">
        <div class="flex justify-between items-center border-b pb-2">
       <h5 class="font-semibold text-gray-800">
          <?php echo htmlspecialchars($username); ?>'s Achievements
        </h5>
          <button onclick="toggleModal('achievementsModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="mt-3">
          <!-- Badge Progress Card -->
          <div class="bg-white rounded-lg shadow-md p-3 mb-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <span class="material-icons text-yellow-400 text-3xl">emoji_events</span>
                <div class="ml-2">
                  <p class="text-sm text-gray-500"><?php echo htmlspecialchars($username); ?></p>
                  <p class="font-bold text-xl text-gray-800">
                    <?php echo htmlspecialchars($badge); ?>
                  </p>
                </div>
              </div>
              <div class="text-right">
                <?php if ($next_level_points): ?>
                  <p class="text-sm text-gray-500">
                    Next Level: <?php echo htmlspecialchars($next_badge_name); ?>
                  </p>
                  <p class="font-bold text-lg text-gray-800">
                    <?php echo number_format($total_points); ?> / <?php echo number_format($next_level_points); ?> pts
                  </p>
                <?php else: ?>
                  <p class="text-sm text-green-600">Max badge achieved!</p>
                  <p class="font-bold text-lg text-gray-800">
                    <?php echo number_format($total_points); ?> pts
                  </p>
                <?php endif; ?>
              </div>
            </div>
                    <!-- Dynamic Progress Bar -->
                    <div class="mt-0">
                        <?php if ($next_level_points && $badge !== 'Culinary Legend'): ?>
                            <?php 
                            $progress_percent = ($total_points / $next_level_points) * 100;
                            if ($progress_percent > 100) $progress_percent = 100;
                            
                            if ($progress_percent <= 0) {
                                $progress_color = 'bg-gray-300';
                            } elseif ($progress_percent < 30) {
                                $progress_color = 'bg-red-500';
                            } elseif ($progress_percent < 70) {
                                $progress_color = 'bg-yellow-500';
                            } else {
                                $progress_color = 'bg-green-500';
                            }
                            ?>
                            <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div 
                                    class="<?php echo $progress_color; ?> h-4 rounded-full transition-all duration-500"
                                    style="width: <?php echo $progress_percent; ?>%;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
          </div>
                        <!-- Levels Section -->
                <div class="level-section bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Levels</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <!-- Freshly Baked - 20 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 20) ? 'bg-yellow-50 border-2 border-yellow-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 20) ? 'bg-yellow-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/freshly_baked.png" 
                                    alt="Freshly Baked Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >
                            </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 20) ? 'text-gray-700' : 'text-gray-400'; ?>">Freshly Baked</p>
                            <p class="text-sm <?php echo ($total_points >= 20) ? 'text-gray-500' : 'text-gray-400'; ?>">20 pts</p>
                            <?php if ($total_points >= 20): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>

                        <!-- Kitchen Star - 75 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 75) ? 'bg-yellow-50 border-2 border-yellow-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 75) ? 'bg-yellow-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/kitchen_star.png" 
                                    alt="Kitchen Star Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >                            </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 75) ? 'text-gray-700' : 'text-gray-400'; ?>">Kitchen Star</p>
                            <p class="text-sm <?php echo ($total_points >= 75) ? 'text-gray-500' : 'text-gray-400'; ?>">75 pts</p>
                            <?php if ($total_points >= 75): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>

                        <!-- Flavor Favorite - 150 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 150) ? 'bg-red-50 border-2 border-red-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 150) ? 'bg-red-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/flavor_favorite.png" 
                                    alt="Flavor Favorite Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >                              </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 150) ? 'text-gray-700' : 'text-gray-400'; ?>">Flavor Favorite</p>
                            <p class="text-sm <?php echo ($total_points >= 150) ? 'text-gray-500' : 'text-gray-400'; ?>">150 pts</p>
                            <?php if ($total_points >= 150): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>

                        <!-- Gourmet Guru - 300 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 300) ? 'bg-green-50 border-2 border-green-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 300) ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/gourmet_guru.png" 
                                    alt="Gourmet Guru Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >                              </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 300) ? 'text-gray-700' : 'text-gray-400'; ?>">Gourmet Guru</p>
                            <p class="text-sm <?php echo ($total_points >= 300) ? 'text-gray-500' : 'text-gray-400'; ?>">300 pts</p>
                            <?php if ($total_points >= 300): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>

                        <!-- Culinary Star - 500 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 500) ? 'bg-blue-50 border-2 border-blue-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 500) ? 'bg-blue-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/culinary_star.png" 
                                    alt="Culinary Star Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >                              </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 500) ? 'text-gray-700' : 'text-gray-400'; ?>">Culinary Star</p>
                            <p class="text-sm <?php echo ($total_points >= 500) ? 'text-gray-500' : 'text-gray-400'; ?>">500 pts</p>
                            <?php if ($total_points >= 500): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>

                        <!-- Culinary Legend - 1000 pts -->
                        <div class="text-center flex flex-col items-center p-3 rounded-lg transition-all duration-300 <?php echo ($total_points >= 1000) ? 'bg-purple-50 border-2 border-purple-200' : 'bg-gray-50 opacity-50'; ?>">
                            <div class="w-20 h-20 rounded-full <?php echo ($total_points >= 1000) ? 'bg-purple-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                <img 
                                    src="img/culinary_legend.png" 
                                    alt="Culinary Legend Badge" 
                                    class="w-15 h-15 <?php echo ($total_points >= 20) ? '' : 'grayscale opacity-50'; ?>"
                                >                              </div>
                            <p class="badge-name font-semibold <?php echo ($total_points >= 1000) ? 'text-gray-700' : 'text-gray-400'; ?>">Culinary Legend</p>
                            <p class="text-sm <?php echo ($total_points >= 1000) ? 'text-gray-500' : 'text-gray-400'; ?>">1000 pts</p>
                            <?php if ($total_points >= 1000): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">✓ Achieved</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
              <!-- Points Breakdown Section -->
          <div class="bg-gray-50 rounded-lg p-4 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Points Breakdown</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
              <div class="bg-white rounded-lg p-3">
                <div class="text-2xl font-bold text-red-600"><?php echo $like_count; ?></div>
                <div class="text-sm text-gray-600">Likes (1 pt each)</div>
                <div class="text-xs text-gray-500"><?php echo ($like_count * 1); ?> total points</div>
              </div>
              <div class="bg-white rounded-lg p-3">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $total_favorites; ?></div>
                <div class="text-sm text-gray-600">Favorites (2 pts each)</div>
                <div class="text-xs text-gray-500"><?php echo ($total_favorites * 2); ?> total points</div>
              </div>
              <div class="bg-white rounded-lg p-3">
                <div class="text-2xl font-bold text-blue-600"><?php echo $recipe_count; ?></div>
                <div class="text-sm text-gray-600">Uploaded (5 pts each)</div>
                <div class="text-xs text-gray-500"><?php echo ($recipe_count * 5); ?> total points</div>
              </div>
            </div>
            <div class="text-center mt-4 pt-3 border-t">
              <div class="text-xl font-bold text-gray-800">Total: <?php echo number_format($total_points); ?> Points</div>
            </div>
          </div>
        </div>
      </div>
    </div>


  </div>
</aside>

<div class="lg:col-span-9">
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
<div class="bg-white p-4 rounded-xl shadow-md text-center">
<p class="text-2xl font-bold text-orange-500"><?php echo $total_likes; ?></p>
<p class="text-gray-500 text-sm">Likes</p>
</div>
<div class="bg-white p-4 rounded-xl shadow-md text-center">
<p class="text-2xl font-bold text-orange-500"><?php echo $total_favorites; ?></p>
<p class="text-gray-500 text-sm">Favorites</p>
</div>
<div class="bg-white p-4 rounded-xl shadow-md text-center">
<p class="text-2xl font-bold text-orange-500"><?php echo $total_uploaded_recipes; ?></p>
<p class="text-gray-500 text-sm"><?php echo htmlspecialchars($username); ?>'s Recipes
</p>

</div>
</div>

<div class="w-full">
  <!-- Tabs -->
  <div class="border-b border-gray-200">
    <nav aria-label="Tabs" class="-mb-px flex justify-evenly space-x-6" id="profile-tabs">
      <button 
        class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-orange-500 border-orange-500 active" 
        data-tab="timeline">
        Timeline
      </button>
      <button 
        class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-gray-500 border-transparent hover:text-orange-500 hover:border-orange-500" 
        data-tab="uploaded">
    <?php echo htmlspecialchars($username); ?>'s Recipes
      </button>
    </nav>
  </div>

  <!-- Tab Contents -->
  <div class="py-4 space-y-4">
   <!-- Timeline -->
<div id="timeline" class="tab-content active">
  <div class="space-y-6">
<?php
// Fetch livestreams
$livestream_sql = "
    SELECT 
        'livestream' as content_type,
        ls.id,
        ls.title,
        ls.caption,
        ls.youtube_link,
        ls.user_id,
        ls.created_at,
        ls.ended_at,
        ls.total_views,
        u.username,
        u.profile_picture
    FROM livestreams ls
    JOIN users u ON ls.user_id = u.id
    WHERE ls.user_id = ? AND ls.is_active = 0
";
$livestream_stmt = $conn->prepare($livestream_sql);
$livestream_stmt->bind_param("i", $user_id);
$livestream_stmt->execute();
$livestream_result = $livestream_stmt->get_result();

$timeline_items = [];

// Add livestreams to timeline
while ($row = $livestream_result->fetch_assoc()) {
    $row['content_type'] = 'livestream';
    $timeline_items[] = $row;
}

// Fetch reposts
$reposts_sql = "
    SELECT 
        'repost' as content_type,
        reposts.id,
        reposts.caption,
        reposts.user_id,
        reposts.created_at,
        u.username,
        u.profile_picture,
        recipe.id as recipe_id,
        recipe.recipe_name,
        recipe.image as recipe_image,
        recipe.status as recipe_status,
        (SELECT username FROM users WHERE id = recipe.user_id) as original_user
    FROM reposts
    JOIN recipe ON reposts.recipe_id = recipe.id
    JOIN users u ON reposts.user_id = u.id
    WHERE reposts.user_id = ?
";
$reposts_stmt = $conn->prepare($reposts_sql);
$reposts_stmt->bind_param("i", $user_id);
$reposts_stmt->execute();
$reposts_result = $reposts_stmt->get_result();

// Add reposts to timeline
while ($row = $reposts_result->fetch_assoc()) {
    $row['content_type'] = 'repost';
    $timeline_items[] = $row;
}

// Sort all items by created_at in descending order
usort($timeline_items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 20 items
$timeline_items = array_slice($timeline_items, 0, 20);

// Badge gradients map
$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet'    => 'linear-gradient(90deg, #504F4F, #555)',
];

if (count($timeline_items) > 0) {
    foreach ($timeline_items as $item) {
        // Get user badge
        $badge_sql = "SELECT badge_name, badge_icon FROM user_badges WHERE user_id = ?";
        $badge_stmt = $conn->prepare($badge_sql);
        $badge_stmt->bind_param("i", $item['user_id']);
        $badge_stmt->execute();
        $badge_result = $badge_stmt->get_result();
        $badge_row = $badge_result->fetch_assoc();
        $badge_name_raw = $badge_row['badge_name'] ?? null;
        $badge_icon = $badge_row['badge_icon'] ?? null;
        $badge_key = $badge_name_raw ? ucwords(strtolower(trim($badge_name_raw))) : 'No Badge Yet';
        $gradient = $badgeGradients[$badge_key] ?? $badgeGradients['No Badge Yet'];
        
        // Profile picture
        if (!empty($item['profile_picture'])) {
            $profilePic = $item['profile_picture'];
            if (strpos($profilePic, 'uploads/profile_pics/') === false) {
                $profilePic = 'uploads/profile_pics/' . ltrim($profilePic, '/');
            }
        } else {
            $profilePic = 'img/no_profile.png';
        }
        
        if ($item['content_type'] == 'livestream') {
            // ========== LIVESTREAM CARD ==========
            // Extract video ID for thumbnail
            preg_match('/\/embed\/([a-zA-Z0-9_-]+)/', $item['youtube_link'], $matches);
            $video_id = $matches[1] ?? '';
            $thumbnail = $video_id ? "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg" : 'img/no_thumbnail.png';
            
            // Calculate duration
            $duration = '';
            if ($item['ended_at']) {
                $start = new DateTime($item['created_at']);
                $end = new DateTime($item['ended_at']);
                $diff = $start->diff($end);
                if ($diff->h > 0) {
                    $duration = $diff->h . 'h ' . $diff->i . 'm';
                } else {
                    $duration = $diff->i . 'm';
                }
            }
            ?>
            
            <!-- Livestream History Card -->
            <div class="bg-white p-4 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
              <div class="flex items-start space-x-3">
                <!-- Profile Picture -->
                <img src="<?= htmlspecialchars($profilePic) ?>"
                     alt="<?= htmlspecialchars($item['username']) ?>"
                     class="w-10 h-10 rounded-full object-cover"
                     onerror="this.src='img/no_profile.png'"/>
                <div class="flex-1">
                  <!-- Header -->
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1">
                      <!-- Username with Gradient -->
                      <span style="
                          display:inline-block;
                          background: <?= $gradient ?>;
                          -webkit-background-clip: text;
                          background-clip: text;
                          color: transparent;
                          -webkit-text-fill-color: transparent;
                          font-weight:600;
                          font-size: 0.875rem;
                      ">@<?= htmlspecialchars($item['username']) ?></span>
                      
                      <!-- Badge Icon -->
                      <?php if (!empty($badge_icon) && $badge_key !== "No Badge Yet"): ?>
                        <img src="<?= htmlspecialchars($badge_icon) ?>" 
                             alt="<?= htmlspecialchars($badge_key) ?>" 
                             class="inline-block" 
                             style="width:23px; height:20px; vertical-align:middle;">
                      <?php endif; ?>
                      
                      <span class="text-gray-500 text-sm font-normal">was live</span>
                    </div>
                  </div>
                  
                  <!-- Stream Date -->
                  <p class="text-xs text-gray-400 mb-3">
                    <?= date("F j, Y • g:i A", strtotime($item['ended_at'])) ?>
                  </p>
                  
                  <!-- Caption -->
                  <?php if (!empty($item['caption'])): ?>
                    <p class="text-gray-700 text-sm mb-3">
                      <?= htmlspecialchars($item['caption']) ?>
                    </p>
                  <?php endif; ?>
                  
                  <!-- Livestream Card -->
                  <div class="border border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:border-orange-400 transition-all"
                       onclick="openPastLiveModal('<?= htmlspecialchars($item['youtube_link']) ?>', '<?= htmlspecialchars($item['title']) ?>', '@<?= htmlspecialchars($item['username']) ?>', '<?= date("F j, Y", strtotime($item['created_at'])) ?>', <?= $item['id'] ?>)">
                    
                    <!-- Thumbnail with Play Button -->
                    <div class="relative group">
                      <img src="<?= htmlspecialchars($thumbnail) ?>" 
                           alt="<?= htmlspecialchars($item['title']) ?>"
                           class="w-full h-48 sm:h-64 object-cover group-hover:brightness-75 transition-all"
                           onerror="this.src='img/no_thumbnail.png'">
                      
                      <!-- Play Overlay -->
                      <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-all">
                        <div class="bg-white rounded-full p-4 opacity-0 group-hover:opacity-100 transform scale-75 group-hover:scale-100 transition-all">
                          <span class="material-icons text-red-600 text-4xl">play_arrow</span>
                        </div>
                      </div>    
                      
                      <!-- "PAST LIVE" Badge -->
                      <div class="absolute top-3 left-3 bg-gray-800 bg-opacity-90 text-white text-xs font-medium px-3 py-1 rounded-full flex items-center gap-1">
                        <span class="material-icons text-sm">history</span>
                        PAST LIVE
                      </div>
                    </div>
                    
                    <!-- Stream Title -->
                    <div class="p-3 bg-gray-50">
                      <h3 class="font-bold text-gray-800 text-base mb-1 line-clamp-2">
                        <?= htmlspecialchars($item['title']) ?>
                      </h3>
                      <div class="flex items-center justify-between text-xs text-gray-500">
                        <div class="flex items-center gap-1">
                          <span class="material-icons text-sm">visibility</span>
                          <?= number_format($item['total_views'] ?? 0) ?> views
                        </div>
                        <span class="text-orange-500 font-medium hover:text-orange-600">
                          Watch Replay →
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <?php
        } else {
            // ========== REPOST CARD ==========
            $recipeUrl = 'recipe_details.php?id=' . $item['recipe_id'];
            ?>
            
            <div class="bg-white p-4 rounded-xl shadow-md">
              <div class="flex items-start space-x-3">
                <!-- Profile picture -->
                <img src="<?= htmlspecialchars($profilePic) ?>"
                     alt="<?= htmlspecialchars($item['username']) ?>"
                     class="w-10 h-10 rounded-full object-cover"/>
                <div class="flex-1">
                  <!-- Header -->
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-semibold text-sm">
                        <?php
                          // Gradient username
                          echo '<span style="
                                    display:inline-block;
                                    background: ' . $gradient . ';
                                    -webkit-background-clip: text;
                                    background-clip: text;
                                    color: transparent;
                                    -webkit-text-fill-color: transparent;
                                    font-weight:600;
                                ">@' . htmlspecialchars($item['username']) . '</span>';
                            // Badge icon if exists and not "No Badge Yet"
                            if (!empty($badge_icon) && $badge_key !== "No Badge Yet") {
                                echo ' <img src="' . htmlspecialchars($badge_icon) . '" 
                                             alt="' . htmlspecialchars($badge_key) . '" 
                                             class="inline-block" 
                                             style="width:23px; height:20px; vertical-align:middle;">';
                            } else {
                                // Leave space if no badge
                                echo '<span></span>';
                            }
                        ?>
                        <span class="font-normal text-gray-500">reposted a recipe</span>
                      </p>
                      <p class="text-xs text-gray-400">
                        <?= date("F j, Y • g:i A", strtotime($item['created_at'])) ?>
                      </p>
                    </div>
                  </div>
                  
                  <!-- Caption -->
                  <?php if (!empty($item["caption"])): ?>
                    <div class="mt-3">
                      <!-- Static caption -->
                      <p id="caption-<?= $item['id'] ?>" class="text-gray-700 text-sm mb-3">
                        <?= htmlspecialchars($item["caption"]) ?>
                      </p>
                      <!-- Edit form (hidden by default) -->
                      <div id="edit-form-container-<?= $item['id'] ?>" class="hidden mb-3">
                        <textarea id="edit-caption-input-<?= $item['id'] ?>"
                                  class="w-full p-2 border focus:ring-orange-400 focus:border-none border-gray-300 rounded-md text-sm"
                                  rows="2"><?= htmlspecialchars($item["caption"]) ?></textarea>
                        <div class="flex space-x-2 mt-2">
                          <button onclick="saveEdit(<?= $item['id'] ?>)"
                                  class="px-3 py-1 bg-orange-400 text-white text-xs rounded-md hover:bg-orange-500">
                            Save
                          </button>
                          <button onclick="cancelEdit(<?= $item['id'] ?>)"
                                  class="px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300">
                            Cancel
                          </button>
                        </div>
                      </div>
                  <?php else: ?>
                    <div class="mt-3">
                  <?php endif; ?>
                  
                      <!-- Recipe card -->
                      <div class="border border-gray-200 rounded-lg p-3 flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-3">
                        <img src="<?= htmlspecialchars($item['recipe_image']) ?>"
                             alt="<?= htmlspecialchars($item['recipe_name']) ?>"
                             class="w-full sm:w-20 h-20 rounded-md object-cover"/>
                        <div class="flex-1">
                          <h3 class="text-base font-bold text-gray-800"><?= htmlspecialchars($item['recipe_name']) ?></h3>
                          <p class="text-xs text-gray-500 mb-2">by @<?= htmlspecialchars($item['original_user']) ?></p>
                          <?php if ($item['recipe_status'] === 'pending' || $item['recipe_status'] === 'rejected'): ?>
                            <p class="text-xs text-yellow-600 font-bold">
                              Recipe not available.
                            </p>
                          <?php else: ?>
                            <a href="<?= $recipeUrl ?>"
                               class="inline-flex items-center text-orange-500 font-semibold text-xs hover:text-orange-600">
                              View Recipe <span class="material-icons text-xs ml-1">arrow_forward</span>
                            </a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
            
            <?php
        }
    }
} else {
    echo '<div class="text-center py-16">
            <span class="material-icons text-gray-300 text-6xl mb-4">timeline</span>
            <p class="text-gray-500 text-lg">No activity yet</p>
            <p class="text-gray-400 text-sm mt-2">This user hasn’t shared any livestreams or reposts yet.</p>
          </div>';
}
?>
  </div>
</div>


<script>
// Toggle dropdown visibility
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    menu.classList.toggle('hidden');
}
// Open Past Live Modal - MOBILE REDIRECT VERSION
function openPastLiveModal(link, title, username, date, streamId) {
    // Extract YouTube video ID from embed link
    let videoId = '';
    const embedMatch = link.match(/embed\/([a-zA-Z0-9_-]+)/);
    if (embedMatch) videoId = embedMatch[1];

    // Always use in-site modal (no redirect)
    const videoIframe = document.getElementById("past-live-video");

    // Build video URL
    const videoUrl = videoId 
        ? `https://www.youtube.com/embed/${videoId}?autoplay=1&playsinline=1&controls=1&rel=0&modestbranding=1`
        : link + "?autoplay=1&playsinline=1";

    // Clear previous iframe source
    videoIframe.src = '';

    // Open modal
    const modal = document.getElementById('past-live-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Set video source
    setTimeout(() => {
        videoIframe.src = videoUrl;
    }, 100);

    // Track replay view after 30 seconds
    setTimeout(() => {
        if (!modal.classList.contains('hidden')) {
            fetch('track_replay_view.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ stream_id: streamId })
            }).catch(err => console.error('View tracking failed:', err));
        }
    }, 30000);
}
// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    document.body.style.overflow = '';

    if (modalId === 'past-live-modal') {
        document.getElementById('past-live-video').src = '';
    }
}

// Open modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close modal on backdrop click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('past-live-modal')?.addEventListener('click', (e) => {
        if (e.target.id === 'past-live-modal' || e.target.classList.contains('modal-backdrop')) {
            closeModal('past-live-modal');
        }
    });
});
// Close on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal('past-live-modal');
        closeModal('edit-caption-modal');
        closeModal('delete-stream-modal');
        closeDeleteModal();
    }
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth animations */
.animate-fadeIn {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>



    <!-- Uploaded Tab -->
    <div id="uploaded" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2 lg:gap-6 md:gap-6">
                        <?php
                        if ($result->num_rows > 0) {

                            // Define badge gradients
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

                                // Check if the current recipe is in favorites
                                $check_fav_stmt = $conn->prepare($check_fav_sql);
                                $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']); // Using current recipe's id
                                $check_fav_stmt->execute();
                                $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;
                                
                                // Get the total count of users who favorited this recipe
                                $fav_count_sql = "SELECT COUNT(*) AS fav_count FROM favorites WHERE recipe_id = ?";
                                $fav_count_stmt = $conn->prepare($fav_count_sql);
                                $fav_count_stmt->bind_param("i", $row['id']);
                                $fav_count_stmt->execute();
                                $fav_count_result = $fav_count_stmt->get_result();
                                $fav_count = $fav_count_result->fetch_assoc()['fav_count'] ?? 0; //

                                // Check if the current recipe is in LIKES
                                $check_layk_stmt = $conn->prepare($check_layk_sql);
                                $check_layk_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']); // Using current recipe's id
                                $check_layk_stmt->execute();
                                $is_like = $check_layk_stmt->get_result()->num_rows > 0;

                                // Get the total count of users who LIKED this recipe
                                $layk_count_sql = "SELECT COUNT(*) AS layk_count FROM likes WHERE recipe_id = ?";
                                $layk_count_stmt = $conn->prepare($layk_count_sql);
                                $layk_count_stmt->bind_param("i", $row['id']);
                                $layk_count_stmt->execute();
                                $layk_count_result = $layk_count_stmt->get_result();
                                $layk_count = $layk_count_result->fetch_assoc()['layk_count'] ?? 0; //

                // Get the gradient for the user's badge
                $gradient = isset($badgeGradients[$badge ?? 'No Badge Yet']) ? $badgeGradients[$badge ?? 'No Badge Yet'] : $badgeGradients['No Badge Yet'];
        ?>
        
        <div class="bg-white lg:rounded-lg md:rounded-lg rounded-sm shadow-lg overflow-hidden transform md:hover:scale-105 transition-transform duration-300 flex md:flex-col recipe-card cursor-pointer"
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
                        <p class="text-gray-600 lg:text-sm md:text-sm text-xs mb-2 sm:block">
                            <?php 
                            $description = htmlspecialchars($row["recipe_description"]);
                            echo strlen($description) > 55 ? substr($description, 0, 55) . '...' : $description; 
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Author Profile -->
                    <div class="flex items-center gap-1">
                    <!-- Profile Image (if available) -->
                    <?php if (!empty($profile_picture)): ?>
                        <img alt="Author's profile picture" 
                             class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover" 
                             src="<?= htmlspecialchars('uploads/profile_pics/' . $profile_picture) ?>"/>
                    <?php else: ?>
                        <div class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                    <?php endif; ?>
                        <div class="flex items-center gap-1">
                            <!-- Username with Badge Gradient -->
                            <a href="userprofile.php?username=<?= urlencode($username) ?>" 
                               class="lg:text-sm md:text-sm text-xs font-bold"
                               style="background: <?= $gradient ?>;
                                      -webkit-background-clip: text;
                                      -webkit-text-fill-color: transparent;"
                               onclick="event.stopPropagation();">
                                @<?= htmlspecialchars($username) ?>
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
                <div class="flex justify-between items-center text-gray-500 mt-2">
                    <!-- Like Button -->
                    <button class="like-action flex items-center space-x-1 <?= $is_like ? 'text-red-500' : 'hover:text-red-500' ?> transition-colors"
                            data-recipe-id="<?= $row['id'] ?>"
                            onclick="event.stopPropagation();">
                        <i class="<?= $is_like ? 'fas' : 'far' ?> fa-heart"></i>
                        <span class="text-sm like-count"><?= $layk_count ?></span>
                    </button>
                    
                    <!-- Bookmark Button -->
                    <button class="bookmark-btn flex items-center space-x-1 <?= $is_favorite ? 'text-orange-500' : 'hover:text-orange-500' ?> transition-colors favorite-action"
                            data-recipe-id="<?= $row['id'] ?>"
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
                    <p class="text-gray-600 tect-extrabold">No recipes uploaded by this user.</p>
                  </div>';
        }
        ?>
    </div>
</div>
    </div>
    </div>
  </div>
</div>


</div>
</div>
</main>
</div>
<!-- Report Modal -->
<div id="report-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 relative">

    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-gray-800">REPORT USER</h2>
      <button class="text-gray-500 hover:text-gray-800" id="close-modal">
        <span class="material-icons text-lg">close</span>
      </button>
    </div>

    <!-- Error Message -->
    <div id="reportErrorMessage" 
         class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm">
      <span id="reportErrorText"></span>
    </div>

    <!-- Success Message -->
    <div id="reportSuccessMessage" 
         class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm text-center">
      ✅ Thank you for submitting a report! <br>
      Your report has been received and will be reviewed by the admin.
    </div>

    <!-- Pending Message -->
    <div id="reportPendingMessage" 
         class="hidden bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4 text-sm text-center">
      ⚠️ You already submitted a report for this user.  
      Please wait until the admin reviews it before submitting another.
    </div>

    <!-- Form -->
    <form id="reportForm" action="submit_report.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="reported_user_id" value="<?php echo $user_id; ?>">
      <input type="hidden" name="reporting_user_id" value="<?php echo $_SESSION['user_id']; ?>">

      <!-- Reason -->
      <div class="mb-4">
        <label for="reasonSelect" class="block text-gray-700 text-sm font-bold mb-2">Reason</label>
        <select name="reason" id="reasonSelect" required
          class="shadow border rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none">
          <option value="">Select a reason</option>
          <option value="Inappropriate Content">Inappropriate Content</option>
          <option value="Spam">Spam</option>
          <option value="Harassment">Harassment</option>
            <option value="Offensive Language">Offensive Language</option>
            <option value="Misleading Information">Misleading Information</option>
            <option value="Hate Speech">Hate Speech</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <!-- Custom Reason -->
      <div id="customReasonContainer" class="hidden mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2">Custom Reason</label>
        <textarea name="custom_reason" rows="3"
          class="shadow border rounded w-full py-2 px-3 text-gray-700 text-sm focus:outline-none"></textarea>
      </div>

       <!-- File Upload -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2">Attach Proof</label>
        <input type="file" name="proof" accept=".jpg,.jpeg,.png"  required
          class="shadow border rounded w-full py-2 px-3 text-sm focus:outline-none">
        <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, JPEG. Max size: 5MB</p>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-2">
        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm" id="close-modal-btn">
          Cancel
        </button>
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded text-sm">
          Submit Report
        </button>
      </div>
    </form>
  </div>
</div>



<!-- Past Live Viewer Modal - Centered and Compact -->
<div id="past-live-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden items-center justify-center px-3 sm:px-4">
  <div class="modal-content w-full max-w-3xl h-auto rounded-xl overflow-hidden bg-black shadow-2xl">
    <!-- Video Wrapper -->
    <div class="relative w-full" style="aspect-ratio: 16 / 9;">
      <iframe 
        id="past-live-video" 
        class="absolute inset-0 w-full h-full rounded-xl"
        src=""
        frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
        playsinline
        loading="lazy">
      </iframe>
    </div>
  </div>
</div>




<!-- Tab Switch Script -->
<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    // Reset all tabs
    document.querySelectorAll('.tab-btn').forEach(b => {
      b.classList.remove('text-orange-500', 'border-orange-500');
      b.classList.add('text-gray-500', 'border-transparent');
    });
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

    // Activate clicked tab
    btn.classList.add('text-orange-500', 'border-orange-500');
    btn.classList.remove('text-gray-500', 'border-transparent');
    document.getElementById(btn.dataset.tab).classList.remove('hidden');
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const reportModal = document.getElementById('report-modal');
  const reportButton = document.getElementById('report-button');
  const closeModal = document.getElementById('close-modal');
  const closeModalBtn = document.getElementById('close-modal-btn');
  const reportForm = document.getElementById('reportForm');
  const errorContainer = document.getElementById('reportErrorMessage');
  const errorTextSpan = document.getElementById('reportErrorText');
  const successContainer = document.getElementById('reportSuccessMessage');
  const pendingContainer = document.getElementById('reportPendingMessage');

function hideAllReportMessages() {
  errorContainer.classList.add('hidden');
  successContainer.classList.add('hidden');
  pendingContainer.classList.add('hidden');
  reportForm.classList.remove('hidden');
}

  // Function to open modal
  function openModal() {
    reportModal.classList.remove('hidden');
    reportModal.classList.add('flex');
    document.body.classList.add('overflow-hidden'); // Lock scroll
  }

  // Function to close modal
  function hideModal() {
    reportModal.classList.add('hidden');
    reportModal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden'); // Unlock scroll
  }

  // Open modal when report button clicked
  reportButton.addEventListener('click', openModal);

  // Close modal buttons
  closeModal.addEventListener('click', hideModal);
  closeModalBtn.addEventListener('click', hideModal);

  // Close when clicking outside modal content
  reportModal.addEventListener('click', (e) => {
    if (e.target === reportModal) hideModal();
  });

  // Prevent closing when clicking inside modal content
  reportModal.querySelector('div').addEventListener('click', e => e.stopPropagation());

  // Handle PHP session-based messages
  <?php if (isset($_SESSION['error'])): ?>
      hideAllReportMessages();
    errorTextSpan.textContent = <?php echo json_encode($_SESSION['error']); ?>;
    errorContainer.classList.remove('hidden');
    openModal();
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['report_success'])): ?>
      hideAllReportMessages();

    reportForm.classList.add('hidden');
    successContainer.classList.remove('hidden');
    openModal();
    <?php unset($_SESSION['report_success']); ?>
  <?php endif; ?>

  // Handle pending state
  const hasPending = <?php echo $hasPending ? 'true' : 'false'; ?>;
  if (hasPending) {
    reportButton.addEventListener('click', () => {
            hideAllReportMessages();

      reportForm.classList.add('hidden');
      pendingContainer.classList.remove('hidden');
    });
  }

  // Show/hide custom reason textarea
  const reasonSelect = document.getElementById('reasonSelect');
  const customReasonContainer = document.getElementById('customReasonContainer');
  if (reasonSelect) {
    reasonSelect.addEventListener('change', function () {
      if (this.value === 'Other') {
        customReasonContainer.classList.remove('hidden');
      } else {
        customReasonContainer.classList.add('hidden');
      }
    });
  }
});

</script>


<script>

function toggleModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  if (modal.classList.contains('hidden')) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    if (modalId === 'biographyModal') {
      updateCharacterCount();
      document.getElementById('biography').focus();
    }
  } else {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

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


</body></html>