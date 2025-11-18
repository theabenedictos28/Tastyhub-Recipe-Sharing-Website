    <?php
    session_start();
    require 'db.php'; // Include database connection


    // Redirect to sign-in page if the user is not logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: signin.php");
        exit;
    }

    // Fetch user details from session
    $username = $_SESSION['username'];
    $email = $_SESSION['email'];
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stream_id'], $_POST['edit_caption'])) {
    $edit_id = (int)$_POST['edit_stream_id'];
    $new_caption = trim($_POST['edit_caption']);

    $stmt = $conn->prepare("UPDATE livestreams SET caption = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_caption, $edit_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


    // Handle delete stream history
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stream_history'])) {
    $stream_id = (int)$_POST['delete_stream_history'];
    
    $delete_sql = "DELETE FROM livestreams WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $stream_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = "Livestream deleted!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

    // Fetch total likes for all recipes uploaded by the user
    $total_likes_sql = "
        SELECT COUNT(likes.id) AS total_likes 
        FROM likes 
        JOIN recipe ON likes.recipe_id = recipe.id 
    WHERE recipe.user_id = ? AND likes.user_id != ? AND status = 'approved'";
    $total_likes_stmt = $conn->prepare($total_likes_sql);
$total_likes_stmt->bind_param("ii", $user_id, $user_id);
    $total_likes_stmt->execute();
    $total_likes_result = $total_likes_stmt->get_result();
    $total_likes = $total_likes_result->fetch_assoc()['total_likes'];

    // Fetch total users who have favorited the recipes uploaded by the user
    $total_favorites_sql = "
        SELECT COUNT(*) AS total_favorites 
        FROM favorites 
        JOIN recipe ON favorites.recipe_id = recipe.id 
    WHERE recipe.user_id = ? AND favorites.user_id != ? AND status = 'approved'";
    $total_favorites_stmt = $conn->prepare($total_favorites_sql);
$total_favorites_stmt->bind_param("ii", $user_id, $user_id);
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
    $check_stmt->bind_param("ii", $user_id, $recipe_id);
    $check_stmt->execute();
    $is_favorite = $check_stmt->get_result()->num_rows > 0;

    $check_layk_sql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
    $check_stmt = $conn->prepare($check_layk_sql);
    $check_stmt->bind_param("ii", $user_id, $recipe_id);
    $check_stmt->execute();
    $is_like = $check_stmt->get_result()->num_rows > 0;

    // Fetch the recipes uploaded by the user
    $sql = "SELECT recipe.id, recipe.recipe_name, recipe.image, recipe.category, recipe.difficulty, 
                   recipe.preparation, recipe.cooktime, recipe.status, recipe.rejection_reason,
                    COUNT(DISTINCT favorites.user_id) AS favorite_count, COUNT(DISTINCT likes.user_id) AS like_count

            FROM recipe 
            LEFT JOIN favorites ON recipe.id = favorites.recipe_id
            LEFT JOIN likes ON recipe.id = likes.recipe_id
            WHERE recipe.user_id = ?
            GROUP by recipe.id
            ORDER BY recipe.created_at DESC"; // Specify the table name here


    $sql_recipe_count = "SELECT COUNT(*) AS recipe_count 
                        FROM recipe 
                        WHERE user_id = ? AND status = 'approved'";
    $stmt_count = $conn->prepare($sql_recipe_count);
    $stmt_count->bind_param("i", $user_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $recipe_count = $result_count->fetch_assoc()['recipe_count'];


    // Get total likes received on user's recipes
    $sql_likes_count = "SELECT COUNT(*) AS like_count 
                        FROM likes l
                        JOIN recipe r ON l.recipe_id = r.id
                    WHERE r.user_id = ? AND l.user_id != ? AND r.status = 'approved'";
    $stmt_likes = $conn->prepare($sql_likes_count);
$stmt_likes->bind_param("ii", $user_id, $user_id);
    $stmt_likes->execute();
    $result_likes = $stmt_likes->get_result();
    $like_count = $result_likes->fetch_assoc()['like_count'];

    // Get total favorites received on user's recipes
    $sql_fav_count = "SELECT COUNT(*) AS fav_count 
                      FROM favorites f
                      JOIN recipe r ON f.recipe_id = r.id
                  WHERE r.user_id = ? AND f.user_id != ? AND r.status = 'approved'";
    $stmt_fav = $conn->prepare($sql_fav_count);
$stmt_fav->bind_param("ii", $user_id, $user_id);
    $stmt_fav->execute();
    $result_fav = $stmt_fav->get_result();
    $fav_count = $result_fav->fetch_assoc()['fav_count'];

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
$badge_icon = ' ';
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

// Continuous progress calculation
$progress_percent = ($total_points / $next_level_points) * 100;
if ($progress_percent > 100) {
    $progress_percent = 100;
}

// Progress bar color
if ($progress_percent <= 0) {
    $progress_color = 'bg-gray-300';
} elseif ($progress_percent < 30) {
    $progress_color = 'bg-red-500';
} elseif ($progress_percent < 70) {
    $progress_color = 'bg-yellow-500';
} else {
    $progress_color = 'bg-green-500';
}

// Text display
$progress_text = $total_points . ' / ' . $next_level_points;

// Save badge to database
// Save badge AND points to database
$save_badge_sql = "INSERT INTO user_badges (user_id, badge_name, badge_icon, total_points) VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE badge_name = ?, badge_icon = ?, total_points = ?";
$save_badge_stmt = $conn->prepare($save_badge_sql);
$save_badge_stmt->bind_param("ississi", $user_id, $badge, $badge_icon, $total_points, $badge, $badge_icon, $total_points);
$save_badge_stmt->execute();


    // Fetch reposted recipes
    $reposts_sql = "SELECT reposts.id AS repost_id, reposts.caption, recipe.id AS recipe_id, recipe.recipe_name, recipe.image, reposts.created_at 
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

    // Fetch recipes by status
// For uploaded recipes
$sql_uploaded = "SELECT recipe.*, users.username, users.profile_picture, 
                        user_badges.badge_name, user_badges.badge_icon
                 FROM recipe 
                 LEFT JOIN users ON recipe.user_id = users.id
                 LEFT JOIN user_badges ON users.id = user_badges.user_id
                 WHERE recipe.user_id = ? AND recipe.status = 'approved' 
                 ORDER BY recipe.created_at DESC";

// Same pattern for pending and declined
$sql_pending = "SELECT recipe.*, users.username, users.profile_picture, 
                       user_badges.badge_name, user_badges.badge_icon
                FROM recipe 
                LEFT JOIN users ON recipe.user_id = users.id
                LEFT JOIN user_badges ON users.id = user_badges.user_id
                WHERE recipe.user_id = ? AND recipe.status = 'pending' 
                ORDER BY recipe.created_at DESC";


$sql_declined = "SELECT recipe.*, users.username, users.profile_picture, 
                        user_badges.badge_name, user_badges.badge_icon
                 FROM recipe 
                 LEFT JOIN users ON recipe.user_id = users.id
                 LEFT JOIN user_badges ON users.id = user_badges.user_id
                 WHERE recipe.user_id = ? AND recipe.status = 'rejected' 
                 ORDER BY recipe.created_at DESC";

    $stmt_uploaded = $conn->prepare($sql_uploaded);
    $stmt_uploaded->bind_param("i", $user_id);
    $stmt_uploaded->execute();
    $result_uploaded = $stmt_uploaded->get_result();

    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param("i", $user_id);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();

    $stmt_declined = $conn->prepare($sql_declined);
    $stmt_declined->bind_param("i", $user_id);
    $stmt_declined->execute();
    $result_declined = $stmt_declined->get_result();

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
        <!-- Favicon -->
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
        <div class="mx-auto px-3 lg:px-5">
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
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="list_archive.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">archive</span> Archived
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
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                                href="list_archive.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">archive</span>
                                <span class="text-sm">Archived</span>
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

<main class="mx-auto px-4 lg:px-6 py-6">
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
<aside class="lg:col-span-3">
  <div class="bg-white p-4 rounded-xl shadow-md text-center relative">
    <!-- Report History Button -->
    <button class="absolute top-3 right-3 text-gray-400 hover:text-orange-500"
            onclick="toggleModal('reportHistoryModal')"
            title="Report History">
      <span class="material-icons text-lg">notifications</span>
      <?php
      // Count unread notifications
      $unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
      $unread_stmt = $conn->prepare($unread_sql);
      $unread_stmt->bind_param("i", $user_id);
      $unread_stmt->execute();
      $unread_result = $unread_stmt->get_result();
      $unread_count = $unread_result->fetch_assoc()['unread_count'];
      
      if ($unread_count > 0):
      ?>
        <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
          <?= $unread_count > 9 ? '9+' : $unread_count ?>
        </span>
      <?php endif; ?>
    </button>


<!-- Report History Modal -->
<div id="reportHistoryModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
     onclick="if(event.target === this) toggleModal('reportHistoryModal')">
  <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto"
       onclick="event.stopPropagation()">
       
    <!-- Header -->
    <div class="flex justify-between items-center border-b px-4 py-3">
      <h5 class="font-semibold text-gray-800">Report Updates</h5>
      <button onclick="toggleModal('reportHistoryModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    
   <div class="p-4 text-left">
  <?php
  $userId = $_SESSION['user_id'];
  $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
      while ($notif = $result->fetch_assoc()) {
          $message = $notif['message'];
          $createdAt = date('M d, Y • h:i A', strtotime($notif['created_at']));

          // Detect status from message content
            if (stripos($message, 'resolved') !== false || stripos($message, 'reinstated') !== false) {
              // ✅ Green for resolved
              $bg = 'bg-green-50 border-green-200 text-green-800';
              $icon = 'check_circle';
              $iconColor = 'text-green-500';
              $timeColor = 'text-green-600';
        } elseif (stripos($message, 'dismissed') !== false || stripos($message, 'rejected') !== false) {
              // 🔴 Red for rejected
              $bg = 'bg-red-50 border-red-200 text-red-800';
              $icon = 'cancel';
              $iconColor = 'text-red-500';
              $timeColor = 'text-red-600';
          } else {
              // 🟡 Yellow for others
              $bg = 'bg-yellow-50 border-yellow-200 text-yellow-800';
              $icon = 'warning';
              $iconColor = 'text-yellow-500';
              $timeColor = 'text-yellow-600';
          }

          echo "
          <div class='flex items-start gap-3 p-3 mb-2 rounded-lg border $bg shadow-sm hover:shadow-md transition'>
            <span class='material-icons $iconColor text-xl mt-0.5'>$icon</span>
            <div class='flex-1'>
                <p class='text-sm font-medium leading-snug'>" . strip_tags($message) . "</p>
              <p class='text-xs $timeColor mt-1'>$createdAt</p>
            </div>
          </div>";
      }
  } else {
      echo "
      <div class='text-center py-6 text-gray-500'>
        <span class='material-icons text-5xl mb-2 text-yellow-400 opacity-50'>notifications_off</span>
        <p>No report updates yet.</p>
      </div>";
  }
  ?>
</div>
  </div>
</div>


    <!-- Profile Picture Section -->
    <div class="relative inline-block mb-3">
      <img alt="Profile picture" 
           id="profilePicPreview"
           class="w-24 h-24 rounded-full mx-auto object-cover shadow-md" 
           src="<?php echo htmlspecialchars($profile_picture_url); ?>"/>
      
      <!-- Camera Button -->
      <button class="absolute bottom-0 right-1 bg-orange-400 hover:bg-orange-500 py-0.5 px-2 rounded-full shadow-md cursor-pointer"
              onclick="document.getElementById('profilePicInput').click()"
              title="Change Profile Picture">
        <span class="material-icons text-white text-base">photo_camera</span>
      </button>
      
      <!-- Hidden File Input -->
      <input type="file" 
             id="profilePicInput" 
             accept="image/*" 
             class="hidden" 
             onchange="previewAndUploadProfilePic(this)">
    </div>

    <!-- Username Section -->
    <div class="flex items-center justify-center">
      <h1 class="text-lg font-bold text-gray-800">
        <span id="usernameDisplay"><?php echo htmlspecialchars($username); ?></span>
      </h1>
      <button class="text-gray-400 ml-1 hover:text-orange-500"
              onclick="toggleModal('usernameModal')"
              title="Edit Username">
        <span class="material-icons text-sm">edit</span>
      </button>
    </div>

<!-- Username Edit Modal -->
<div id="usernameModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 px-4"
     onclick="closeOnOutsideClick(event, 'usernameModal')">
  <div class="bg-white rounded-xl shadow-lg p-4 w-full max-w-sm relative"
       onclick="event.stopPropagation();"> <!-- Prevent closing when clicking inside -->

    <!-- Header -->
    <div class="flex justify-between items-center border-b pb-2 mb-3">
      <h5 class="font-semibold text-gray-800 flex items-center text-lg">
        <i class="fas fa-user-edit text-orange-400 mr-2"></i> Change Username
      </h5>
      <button onclick="toggleModal('usernameModal')" 
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
    </div>

    <!-- Form -->
    <form id="usernameForm" class="space-y-4">
      <!-- Input -->
      <div>
        <label for="newUsername" class="block text-sm font-medium text-gray-700 mb-1">
          New Username
        </label>
        <div class="flex items-center border rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-400">
          <span class="px-3 text-gray-500">
            <i class="fas fa-user"></i>
          </span>
          <input type="text" 
                 id="newUsername" 
                 name="username" 
                 class="flex-1 px-2 py-2 focus:outline-none text-gray-800 focus:ring-orange-400"
                 minlength="3" maxlength="20"
                 required 
                 placeholder="Enter new username"
                 style="border:none;">
        </div>
        <small id="usernameMessage" class="block mt-1 text-sm text-gray-500"></small>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-2">
        <button type="button" onclick="toggleModal('usernameModal')" 
                class="px-3 py-2 rounded-md border text-gray-600 hover:bg-gray-100 text-sm">
          Cancel
        </button>
        <button type="submit" id="saveUsernameBtn" disabled
                class="px-3 py-2 rounded-md bg-orange-400 text-white hover:bg-orange-500 text-sm disabled:opacity-50">
          Save
        </button>
      </div>
    </form>
  </div>
</div>
    <!-- Email -->
    <p class="text-gray-500 text-xs mb-5"><?php echo htmlspecialchars($email); ?></p>



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
    <!-- Achievements Modal -->
    <div id="achievementsModal"
         class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
         onclick="if(event.target === this) toggleModal('achievementsModal')">
      <div class="bg-white rounded-xl shadow-lg w-11/12 md:w-3/4 lg:w-2/3 max-h-[85vh] overflow-y-auto p-4"
      onclick="event.stopPropagation()">
        <div class="flex justify-between items-center border-b pb-2">
          <h5 class="font-semibold text-gray-800">Your Achievements</h5>
          <button onclick="toggleModal('achievementsModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="mt-3">
          <!-- Badge Progress Card -->
          <div class="bg-white rounded-lg shadow-md p-3 mb-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <span class="material-icons text-yellow-400 text-3xl">emoji_events</span>
                <div class="ml-2">
                  <p class="text-sm text-gray-500">You are a</p>
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
            <?php if ($next_level_points): ?>
              <div class="mt-2 bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="<?php echo $progress_color; ?> h-4 rounded-full transition-all duration-500"
                     style="width: <?php echo $progress_percent; ?>%;"></div>
              </div>
            <?php endif; ?>
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
                                <p class="text-xs text-red-600 mt-1 <?php echo ($total_points >= 500) ? '' : 'opacity-50'; ?>">(Unlock new features)</p> <!-- New line added -->

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
                                <p class="text-xs text-red-600 mt-1 <?php echo ($total_points >= 500) ? '' : 'opacity-50'; ?>">(Unlock new features)</p> <!-- New line added -->

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
    
<?php
session_start();

$total_points = ($like_count * 1) + ($fav_count * 2) + ($recipe_count * 5);

// Badge thresholds
$badgeThresholds = [
    20   => 'Freshly Baked',
    75   => 'Kitchen Star',
    150  => 'Flavor Favorite',
    300  => 'Gourmet Guru',
    500  => 'Culinary Star',
    1000 => 'Culinary Legend'
];

$badgeBorderColors = [
    'Freshly Baked'   => '#ff8f00',
    'Kitchen Star'    => '#28a745',
    'Flavor Favorite' => '#28a745',
    'Gourmet Guru'    => '#8a2be2',
    'Culinary Star'   => '#007bff',
    'Culinary Legend' => '#ff0000',
];

$badgeIcons = [
    'Freshly Baked'   => 'img/freshly_baked.png',
    'Kitchen Star'    => 'img/kitchen_star.png',
    'Flavor Favorite' => 'img/flavor_favorite.png',
    'Gourmet Guru'    => 'img/gourmet_guru.png',
    'Culinary Star'   => 'img/culinary_star.png',
    'Culinary Legend' => 'img/culinary_legend.png',
];

// Determine current badge based on points
$currentBadge = 'No Badge Yet';
foreach ($badgeThresholds as $points => $name) {
    if ($total_points >= $points) {
        $currentBadge = $name;
    } else {
        break;
    }
}

// Check if user already has this badge marked as "seen" in database
$check_badge_sql = "SELECT badge_name, has_seen_animation FROM user_badge_tracking 
                    WHERE user_id = ? AND badge_name = ?";
$check_badge_stmt = $conn->prepare($check_badge_sql);
$check_badge_stmt->bind_param("is", $user_id, $currentBadge);
$check_badge_stmt->execute();
$badge_result = $check_badge_stmt->get_result();
$badge_data = $badge_result->fetch_assoc();

// Determine if animation should show
$showAnimation = false;

if ($currentBadge !== 'No Badge Yet') {
    if (!$badge_data) {
        // New badge - insert and show animation
        $insert_badge_sql = "INSERT INTO user_badge_tracking (user_id, badge_name, has_seen_animation) 
                            VALUES (?, ?, 1)";
        $insert_stmt = $conn->prepare($insert_badge_sql);
        $insert_stmt->bind_param("is", $user_id, $currentBadge);
        $insert_stmt->execute();
        $showAnimation = true;
    } elseif ($badge_data['has_seen_animation'] == 0) {
        // Badge was lost and earned back - show animation and mark as seen
        $update_badge_sql = "UPDATE user_badge_tracking SET has_seen_animation = 1 
                            WHERE user_id = ? AND badge_name = ?";
        $update_stmt = $conn->prepare($update_badge_sql);
        $update_stmt->bind_param("is", $user_id, $currentBadge);
        $update_stmt->execute();
        $showAnimation = true;
    }
    // If has_seen_animation == 1, don't show animation (already seen this badge)
}

// Mark lost badges (points dropped below threshold)
foreach ($badgeThresholds as $points => $name) {
    if ($total_points < $points) {
        // User lost this badge - mark as not seen for future re-earning
        $mark_lost_sql = "UPDATE user_badge_tracking SET has_seen_animation = 0 
                         WHERE user_id = ? AND badge_name = ?";
        $mark_lost_stmt = $conn->prepare($mark_lost_sql);
        $mark_lost_stmt->bind_param("is", $user_id, $name);
        $mark_lost_stmt->execute();
    }
}

$badgeImage = $badgeIcons[$currentBadge] ?? '';
$badgeBorderColor = $badgeBorderColors[$currentBadge] ?? '#ff8f00';
?>

<div id="badgeOverlay" 
     class="fixed inset-0 hidden items-start justify-center z-50 pt-20" 
     style="backdrop-filter: blur(7px); background: rgba(255, 255, 255, 0.45);">

  <div class="badgeContent relative w-[350px] max-w-full p-8 flex flex-col items-center justify-center text-center mx-auto">
    
    <!-- Badge Container -->
    <div class="badge-wrapper relative flex items-center justify-center">
      
      <!-- Soft glowing background -->
      <div class="badge-glow absolute w-[280px] h-[280px] rounded-full opacity-0 blur-[50px]"
           style="
             background: radial-gradient(circle, <?= $badgeBorderColor ?>55 0%, <?= $badgeBorderColor ?>22 40%, transparent 80%);
             z-index: 0;
             filter: brightness(1.2) saturate(1.2);
           ">
      </div>

      <!-- Badge -->
      <div class="badge animate-bounceIn opacity-0 scale-50 relative z-10"
           style="border: 5px solid <?= $badgeBorderColor ?>; box-shadow: 0 0 25px <?= $badgeBorderColor ?>55;">
        <img src="<?= htmlspecialchars($badgeImage) ?>" 
             alt="Badge" 
             class="w-[180px] h-[180px] object-contain rounded-full">
      </div>
    </div>

    <!-- Texts -->
    <h1 class="mt-6 text-2xl font-extrabold drop-shadow-lg animate-fadeInUp"
        style="color: <?= $badgeBorderColor ?>;">
      <i class="fas fa-medal"></i> Congratulations! <i class="fas fa-medal"></i>
    </h1>

    <p class="text-base mt-2 animate-fadeInUp delay-200"
       style="color: <?= $badgeBorderColor ?>cc;">
      Achievement Unlocked
    </p>

    <p class="text-sm mt-2 animate-fadeInUp delay-400"
       style="color: <?= $badgeBorderColor ?>bb;">
      You've earned the <strong style="color: <?= $badgeBorderColor ?>;">"<?= htmlspecialchars($currentBadge) ?>"</strong> badge!
    </p>

    <button onclick="hideBadge()" 
            class="mt-6 px-6 py-2 text-white rounded-xl shadow-lg animate-fadeInUp delay-600 transition duration-300"
            style="
              background-color: <?= $badgeBorderColor ?>;
              box-shadow: 0 0 20px <?= $badgeBorderColor ?>66;
            "
            onmouseover="this.style.backgroundColor='<?= $badgeBorderColor ?>dd'"
            onmouseout="this.style.backgroundColor='<?= $badgeBorderColor ?>'">
      Close
    </button>

  </div>
</div>

<script>
const badgeOverlay = document.getElementById('badgeOverlay');
const badgeEl = badgeOverlay.querySelector('.badge');
const glow = badgeOverlay.querySelector('.badge-glow');

function showBadge() {
  badgeOverlay.classList.remove('hidden');
  document.body.classList.add('overflow-hidden');

  badgeOverlay.style.opacity = 0;
  badgeOverlay.style.transition = 'opacity 0.5s';
  requestAnimationFrame(() => badgeOverlay.style.opacity = 1);

  // Animate badge
  setTimeout(() => {
    badgeEl.classList.add('show');
    glow.classList.add('show');
  }, 300);
}

function hideBadge() {
  badgeOverlay.style.opacity = 0;
  document.body.classList.remove('overflow-hidden');
  setTimeout(() => badgeOverlay.classList.add('hidden'), 500);
}

<?php if ($showAnimation): ?>
document.addEventListener('DOMContentLoaded', showBadge);
<?php endif; ?>
</script>

<style>
.badge {
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 0 20px rgba(255,255,255,0.4), 0 0 50px rgba(255,255,255,0.2);
  transition: transform 0.8s ease, opacity 0.8s ease;
}

.badge.show {
  opacity: 1;
  transform: scale(1.05);
  animation: pulse 2s infinite ease-in-out;
}

.badgeContent {
  width: 100%;
  text-align: center;
  align-items: center;
  justify-content: center;
}

.badgeContent h1,
.badgeContent p {
  width: 100%;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
}

.badge-glow {
  animation: glowPulse 2s infinite ease-in-out;
  transition: opacity 1s ease;
}

.badge-glow.show {
  opacity: 1;
}

@keyframes pulse {
  0%,100% { transform: scale(1.05); }
  50% { transform: scale(1.15); }
}

@keyframes glowPulse {
  0%,100% { opacity: 0.7; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
}

@keyframes bounceIn {
  0% { opacity: 0; transform: scale(0.3); }
  60% { opacity: 1; transform: scale(1.1); }
  100% { transform: scale(1); }
}

.animate-bounceIn { animation: bounceIn 1s forwards; }
.animate-fadeInUp { opacity: 0; transform: translateY(20px); animation: fadeInUp 0.8s forwards; }
@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
.delay-200 { animation-delay: 0.2s; }
.delay-400 { animation-delay: 0.4s; }
.delay-600 { animation-delay: 0.6s; }
</style>

<!-- Biography Section -->
<div class="mt-4">
  <?php if (empty($biography)): ?>
    <p id="biographyDisplay" class="text-gray-600 text-sm cursor-pointer hover:text-orange-500"
       onclick="toggleModal('biographyModal')">
      Write something about yourself...
    </p>
  <?php else: ?>
    <p id="biographyDisplay" class="text-gray-600 text-sm cursor-pointer hover:text-orange-500"
       onclick="toggleModal('biographyModal')">
      <?php echo htmlspecialchars($biography); ?>
    </p>
  <?php endif; ?>
</div>


<!-- Biography Modal -->
<div id="biographyModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
     onclick="if(event.target === this) toggleModal('biographyModal')">

  <div class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/2 p-4"
       onclick="event.stopPropagation();">

    <form method="POST" id="biographyForm">
      <div class="flex justify-between items-center border-b pb-2">
<h5 class="font-semibold">
  <i class="fas fa-edit text-orange-400 me-2"></i> Edit Biography
</h5>
        <button type="button" onclick="toggleModal('biographyModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
      </div>

      <div class="mt-3">
        <textarea id="biography" name="biography" rows="4"
          class="w-full border rounded-lg p-2 focus:outline-none focus:ring focus:ring-orange-400 focus:border-none"
          placeholder="Write something about yourself..."><?php echo htmlspecialchars($biography ?? ''); ?></textarea>
        <p id="charCount" class="mt-1 text-xs text-gray-500">0/300 characters</p>
      </div>

      <div class="flex justify-end space-x-2 mt-3">
        <button type="button" onclick="toggleModal('biographyModal')" class="px-4 py-1.5 rounded-lg border text-gray-600 hover:bg-gray-100">Cancel</button>
        <button id="saveButton" type="submit" class="px-4 py-1.5 rounded-lg bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50">Save Changes</button>
      </div>
    </form>
  </div>
</div>



  </div>
</aside>



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

document.addEventListener("DOMContentLoaded", () => {
  const bioTextarea = document.getElementById("biography");
  const charCount = document.getElementById("charCount");
  const saveButton = document.getElementById("saveButton");
  const form = document.getElementById("biographyForm");
  const maxChars = 300;

  function updateCharacterCount() {
    let text = bioTextarea.value.replace(/\r\n/g, "\n").trim();
    const currentLength = text.length;

    charCount.textContent = `${currentLength}/${maxChars} characters`;

    if (currentLength > maxChars) {
      charCount.classList.remove("text-gray-500");
      charCount.classList.add("text-red-500");
      saveButton.disabled = true;
    } else {
      charCount.classList.remove("text-red-500");
      charCount.classList.add("text-gray-500");
      saveButton.disabled = false;
    }
  }

  if (bioTextarea) {
    bioTextarea.addEventListener("input", updateCharacterCount);
    updateCharacterCount();
  }

  // Handle form submit
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const biography = bioTextarea.value.trim();

    fetch("update_biography.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "biography=" + encodeURIComponent(biography),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Update bio display on page
          document.getElementById("biographyDisplay").textContent = biography;
          toggleModal("biographyModal");
        } else {
          alert(data.message || "Failed to update biography.");
        }
      })
      .catch(() => alert("Error saving biography."));
  });
});

</script>

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
<p class="text-gray-500 text-sm">My Recipes</p>
</div>
</div>

<!-- Tailwind Tabs -->
<div class="w-full ">
  <div class="border-b border-gray-200">
    <nav aria-label="Tabs" class="-mb-px flex justify-evenly space-x-6" id="profile-tabs">
      <a class="tab-link whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-orange-500 border-orange-500 active" href="#timeline">
        Timeline
      </a>
      <a class="tab-link whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-gray-500 border-transparent hover:text-orange-500 hover:border-orange-500" href="#uploaded">
        My Recipes
      </a>
      <a class="tab-link whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-gray-500 border-transparent hover:text-orange-500 hover:border-orange-500" href="#pending">
        Pending
      </a>
      <a class="tab-link whitespace-nowrap py-3 px-1 border-b-2 font-medium text-xs text-gray-500 border-transparent hover:text-orange-500 hover:border-orange-500" href="#declined">
        Declined
      </a>
    </nav>
  </div>

  <!-- Tab Content -->
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
                    
                    <!-- Ellipsis menu (Edit & Delete) -->
                    <?php if ($item['user_id'] == $user_id): ?>
                    <div class="relative ellipsis-container">
                      <button class="text-gray-500 hover:text-gray-700"
                              onclick="event.stopPropagation(); toggleDropdown(this)">
                        <span class="material-icons text-lg">more_horiz</span>
                      </button>
                      
                      <div class="ellipsis-menu hidden absolute right-0 w-32 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <!-- Edit Caption -->
                        <button 
                            onclick="openEditCaptionModal(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['caption'] ?? '')) ?>')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                          Edit
                        </button>
                        <!-- Delete Stream -->
                        <button 
                            onclick="openDeleteStreamModal(<?= $item['id'] ?>)"
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-100">
                          Delete
                        </button>
                      </div>
                    </div>
                    <?php endif; ?>
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
                    
                    <!-- Ellipsis menu -->
                    <div class="relative ellipsis-container">
                      <button class="text-gray-500 hover:text-gray-700"
                              onclick="event.stopPropagation(); toggleDropdown(this)">
                        <span class="material-icons text-lg">more_horiz</span>
                      </button>
                      <div class="ellipsis-menu hidden absolute right-0 w-32 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <button onclick="showEditForm(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['caption'])) ?>')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                          Edit
                        </button>
                        <button onclick="openDeleteModal(<?= $item['id'] ?>)"
                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-100">
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Caption -->
                 <div class="mt-3">
  <!-- Caption -->
  <?php if (!empty($item["caption"])): ?>
    <p id="caption-<?= $item['id'] ?>" class="text-gray-700 text-sm mb-3">
      <?= htmlspecialchars($item["caption"]) ?>
    </p>
  <?php else: ?>
    <p id="caption-<?= $item['id'] ?>" class="text-gray-700 text-sm mb-3 hidden"></p>
  <?php endif; ?>

  <!-- Edit form (always rendered, hidden by default) -->
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
            <p class="text-gray-400 text-sm mt-2">Your timeline will show your livestreams and reposts</p>
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

// Open Edit Caption Modal
function openEditCaptionModal(streamId, caption) {
    document.getElementById('edit_stream_id').value = streamId;
    document.getElementById('edit_caption_textarea').value = caption;
    openModal('edit-caption-modal');
}

// Open Delete Stream Modal
function openDeleteStreamModal(streamId) {
    document.getElementById('delete_stream_id').value = streamId;
    openModal('delete-stream-modal');
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

// Repost edit functions
function showEditForm(repostId, caption) {
    document.getElementById('caption-' + repostId).classList.add('hidden');
    document.getElementById('edit-form-container-' + repostId).classList.remove('hidden');
}

function cancelEdit(repostId) {
    document.getElementById('caption-' + repostId).classList.remove('hidden');
    document.getElementById('edit-form-container-' + repostId).classList.add('hidden');
}

function saveEdit(repostId) {
    const newCaption = document.getElementById('edit-caption-input-' + repostId).value;
    
    fetch('update_repost_caption.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ repost_id: repostId, caption: newCaption })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('caption-' + repostId).textContent = newCaption;
            cancelEdit(repostId);
        } else {
            alert('Failed to update caption');
        }
    })
    .catch(err => console.error('Error updating caption:', err));
}

// Repost delete functions
function openDeleteModal(repostId) {
    const modal = document.getElementById('deleteRepostModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    document.getElementById('confirmDeleteBtn').onclick = function() {
        deleteRepost(repostId);
    };
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteRepostModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function deleteRepost(repostId) {
    fetch('delete_repost.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ repost_id: repostId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete repost');
        }
    })
    .catch(err => console.error('Error deleting repost:', err));
}
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}



/* Close dropdown when clicking outside */
.ellipsis-container {
    position: relative;
}

.ellipsis-menu {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 0.25rem;
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






    <!-- Uploaded -->
    <div id="uploaded" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2 lg:gap-6 md:gap-6">
        <?php
        if ($result_uploaded->num_rows > 0) {
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

            while ($row = $result_uploaded->fetch_assoc()) {
                $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                $recipeUrl = 'recipe_details.php?id=' . $row["id"];

                // Check if the current recipe is in favorites
                $check_fav_stmt = $conn->prepare($check_fav_sql);
                $check_fav_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                $check_fav_stmt->execute();
                $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;

                // Check if the current recipe is in LIKES
                $check_layk_stmt = $conn->prepare($check_layk_sql);
                $check_layk_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                $check_layk_stmt->execute();
                $is_like = $check_layk_stmt->get_result()->num_rows > 0;

                // Get the total count of users who LIKED this recipe
                $layk_count_sql = "SELECT COUNT(*) AS layk_count FROM likes WHERE recipe_id = ?";
                $layk_count_stmt = $conn->prepare($layk_count_sql);
                $layk_count_stmt->bind_param("i", $row['id']); 
                $layk_count_stmt->execute();
                $layk_count_result = $layk_count_stmt->get_result();
                $layk_count = $layk_count_result->fetch_assoc()['layk_count'] ?? 0;

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
            echo '<div class="col-span-full text-center">
                    <p class="text-gray-500">No uploaded recipes.</p>
                  </div>';
        }
        ?>
    </div>
</div>
    </div>

    <!-- Pending -->
    <div id="pending" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2 lg:gap-6 md:gap-6">
        <?php
        if ($result_pending->num_rows > 0) {
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

            while ($row = $result_pending->fetch_assoc()) {
                $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                $recipeUrl = 'pending_details.php?id=' . $row["id"];

                // Get the gradient for the user's badge
                $gradient = isset($badgeGradients[$badge ?? 'No Badge Yet']) ? $badgeGradients[$badge ?? 'No Badge Yet'] : $badgeGradients['No Badge Yet'];
        ?>
        
<div class="bg-white lg:rounded-lg md:rounded-lg rounded-sm shadow-lg overflow-hidden transform md:hover:scale-105 transition-transform duration-300 flex md:flex-col recipe-card cursor-pointer"
     data-recipe-id="<?= $row['id'] ?>"
     onclick="window.location.href='<?= $recipeUrl ?>'">

    <!-- Recipe Image with Pending Badge -->
    <div class="relative w-1/3 md:w-full">
        <img alt="Recipe Image" 
             class="w-full h-40 md:h-48 object-cover" 
             src="<?= $imagePath ?>"/>

        <!-- Pending Badge Top-Right -->
        <div class="absolute top-2 right-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 shadow-md">
                <i class="fas fa-clock mr-1"></i> Pending Review
            </span>
        </div>
    </div>

    <!-- Recipe Content -->
    <div class="p-4 flex flex-col justify-between w-2/3 md:w-full">
        <div>
            <!-- Recipe Title -->
            <h3 class="text-sm lg:text-lg font-semibold text-gray-800 mb-2 truncate"><?= htmlspecialchars($row["recipe_name"]) ?></h3>

            <!-- Recipe Description (if available) -->
            <?php if (!empty($row["recipe_description"])): ?>
                <p class="text-gray-600 lg:text-sm md:text-sm text-xs lg:mb-4 md:mb-4 mb-2 sm:block">
                    <?php 
                    $description = htmlspecialchars($row["recipe_description"]);
                    echo strlen($description) > 55 ? substr($description, 0, 55) . '...' : $description; 
                    ?>
                </p>
            <?php endif; ?>

            <!-- Author Profile -->
            <div class="flex items-center gap-1">
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
                            <span class="lg:w-7 lg:h-6 md:w-7 md:h-6 w-5 h-4 inline-block"></span>
                        <?php endif; ?> 
                </div>
            </div>
        </div>
    </div>
</div>

        
        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center">
                    <p class="text-gray-500">No pending recipes.</p>
                  </div>';
        }
        ?>
    </div>
</div>
    </div>

    <!-- Declined -->
    <div id="declined" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-2 lg:gap-6 md:gap-6">
        <?php
        if ($result_declined->num_rows > 0) {
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

            while ($row = $result_declined->fetch_assoc()) {
                $imagePath = !empty($row["image"]) ? htmlspecialchars($row["image"]) : 'uploads/default-placeholder.png';
                $recipeUrl = 'declined_details.php?id=' . $row["id"];

                // Get the gradient for the user's badge
                $gradient = isset($badgeGradients[$badge ?? 'No Badge Yet']) ? $badgeGradients[$badge ?? 'No Badge Yet'] : $badgeGradients['No Badge Yet'];
        ?>
        
<div class="bg-white lg:rounded-lg md:rounded-lg rounded-sm shadow-lg overflow-hidden transform md:hover:scale-105 transition-transform duration-300 flex md:flex-col recipe-card cursor-pointer"
     data-recipe-id="<?= $row['id'] ?>"
     onclick="window.location.href='<?= $recipeUrl ?>'">

    <!-- Recipe Image with Overlay -->
    <div class="relative w-1/3 md:w-full">
        <img alt="Recipe Image" 
             class="w-full h-40 md:h-48 object-cover opacity-75" 
             src="<?= $imagePath ?>"/>
        <div class="absolute inset-0 bg-red-500 bg-opacity-20"></div>

        <!-- Declined Badge Top-Right -->
        <div class="absolute top-2 right-2"
             onclick="event.stopPropagation(); showDeclineReason(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['rejection_reason'] ?? 'No reason provided')) ?>')">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 shadow-md">
                <i class="fas fa-times-circle mr-1"></i> Declined
            </span>
        </div>
    </div>

    <!-- Recipe Content -->
    <div class="p-4 flex flex-col justify-between w-2/3 md:w-full">
        <div>
            <!-- Recipe Title -->
            <h3 class="text-sm lg:text-lg font-semibold text-gray-800 mb-2 truncate"><?= htmlspecialchars($row["recipe_name"]) ?></h3>

            <!-- Recipe Description -->
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
    </div>
</div>

        
        <?php 
            }
        } else {
            echo '<div class="col-span-full text-center">
                    <p class="text-gray-500">No declined recipes.</p>
                  </div>';
        }
        ?>
    </div>
</div>
<!-- Decline Reason Modal -->
<div 
  id="declineReasonModal" 
  class="fixed inset-0 bg-black bg-opacity-40 hidden z-50 flex items-center justify-center p-4"
  onclick="handleBackdropClick(event, 'declineReasonModal')"
>
  <div class="bg-white rounded-lg shadow-lg w-full max-w-sm" onclick="event.stopPropagation()">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-2 bg-red-500 rounded-t-lg">
      <h5 class="text-white font-semibold text-sm flex items-center gap-1">
        <i class="fas fa-times-circle"></i> Recipe Declined
      </h5>
      <button onclick="closeModal('declineReasonModal')" 
              class="text-white hover:text-gray-200 text-lg font-bold">&times;</button>
    </div>

    <!-- Body -->
    <div class="p-4 flex items-start gap-3">
      <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mt-1"></i>
      <div>
        <h6 class="font-semibold text-sm mb-1">Reason:</h6>
        <p id="declineReasonText" class="text-gray-600 text-sm"></p>
      </div>
    </div>
  </div>
</div>


    </div>
  </div>
</div>

<!-- Change Password Modal (Tailwind) -->
<div id="changePasswordModal"
     class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 px-4 flex items-center justify-center"
     aria-hidden="true"
          onclick="if(event.target === this) closeModal('changePasswordModal')">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6 relative"
       role="dialog" aria-modal="true"
       onclick="event.stopPropagation()">
       
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-gray-800">Change Your Password</h2>
      <button type="button" aria-label="Close" 
              class="text-gray-400 hover:text-gray-600"
              onclick="closeModal('changePasswordModal')">✕</button>
    </div>

    <!-- Form -->
    <form id="changePasswordForm" novalidate class="space-y-4">
      <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
        <input type="password" id="current_password" name="current_password" required
               placeholder="Enter current password"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
      </div>

      <div>
        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
        <input type="password" id="new_password" name="new_password" required
               placeholder="Enter new password"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
      </div>

      <div>
        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required
               placeholder="Confirm new password"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
      </div>

      <!-- Message placeholder -->
      <p id="passwordMessage" class="text-red-500 text-sm text-center min-h-[1rem]"></p>

      <!-- Submit -->
      <button type="submit" id="changePasswordSubmit"
              class="w-full py-2 px-4 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 focus:outline-none">
        Update Password
      </button>
    </form>
  </div>
</div>


<!-- Delete Stream Modal -->
<div id="delete-stream-modal" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-md p-5 w-full max-w-xs text-center animate-fadeIn" onclick="event.stopPropagation();">
    <div class="text-center">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Delete Livestream</h3>
      <p class="text-gray-700 text-sm mb-6">
        Are you sure you want to <span class="font-semibold text-red-600">delete this livestream</span>? This cannot be undone.
      </p>
      <form id="delete-stream-form" method="POST">
        <input type="hidden" name="delete_stream_history" id="delete_stream_id">
        <div class="flex justify-center gap-3">
          <button type="button" onclick="closeModal('delete-stream-modal')"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-lg transition-all">
            Cancel
          </button>
          <button type="submit"
                  class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-all">
            Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Caption Modal -->
<div id="edit-caption-modal" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" onclick="event.stopPropagation();">
    <div class="text-black-600 px-6 py-3 flex justify-between items-center">
      <h3 class="text-lg font-bold flex items-center gap-2">
        <span class="material-icons">edit</span>
        Edit Caption
      </h3>
    </div>
    <form id="edit-caption-form" method="POST" class="p-6">
      <input type="hidden" name="edit_stream_id" id="edit_stream_id">
      <textarea id="edit_caption_textarea" name="edit_caption" rows="3"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                placeholder="Edit your caption..."></textarea>
      <div class="flex justify-center gap-3 mt-4">
        <button type="button" onclick="closeModal('edit-caption-modal')"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-lg transition-all">
          Cancel
        </button>
        <button type="submit"
                class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-all">
          Save
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Repost Modal -->
<div id="deleteRepostModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-md p-5 w-full max-w-xs text-center animate-fadeIn" onclick="event.stopPropagation()">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">Delete Repost</h3>
    <p class="text-sm text-gray-500 mb-5">Are you sure you want to delete this repost?</p>
    <div class="flex justify-center space-x-3">
      <button onclick="closeDeleteModal()" 
              class="px-3 py-1.5 rounded-md border border-gray-300 text-gray-600 text-sm hover:bg-gray-100">
        Cancel
      </button>
      <button id="confirmDeleteBtn" 
              class="px-4 py-1.5 rounded-md bg-red-500 text-white text-sm hover:bg-red-600 transition">
        Delete
      </button>
    </div>
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







<script>
// Utility to open/close modal (kept global for compatibility)
function openModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('hidden');
  el.classList.add('flex');
  el.setAttribute('aria-hidden', 'false');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('flex');
  el.classList.add('hidden');
  el.setAttribute('aria-hidden', 'true');
}

// Hook up trigger(s) after DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  // Attach click to any change-password triggers
  document.querySelectorAll('.change-password-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openModal('changePasswordModal');
    });
  });

  // Click overlay to close
  const modal = document.getElementById('changePasswordModal');
  if (modal) {
    modal.addEventListener('click', function (e) {
      // if clicked directly on the overlay (not the inner modal), close
      if (e.target === modal) closeModal('changePasswordModal');
    });
  }

  // Close on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal('changePasswordModal');
  });

  // FORM SUBMIT (tries jQuery first, falls back to fetch if jQuery isn't present)
  const form = document.getElementById('changePasswordForm');
  const messageEl = document.getElementById('passwordMessage');
  const submitBtn = document.getElementById('changePasswordSubmit');

  function displayMessage(msg, isError = true) {
    messageEl.textContent = msg || '';
    messageEl.classList.toggle('text-red-500', isError);
    messageEl.classList.toggle('text-green-600', !isError);
  }

  if (window.jQuery && typeof jQuery === 'function') {
    // jQuery path
    $(form).on('submit', function (ev) {
      ev.preventDefault();
      const currentPassword = $('#current_password').val();
      const newPassword = $('#new_password').val();
      const confirmPassword = $('#confirm_password').val();

     if (newPassword.length < 8) {
      displayMessage("Password must be at least 8 characters long.");
      return;
    }
    if (newPassword !== confirmPassword) {
      displayMessage("New passwords do not match.");
      return;
    }

      submitBtn.disabled = true;
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Updating...';

      $.ajax({
        url: 'change_password.php',
        type: 'POST',
        data: {
          current_password: currentPassword,
          new_password: newPassword
        },
        dataType: 'json', // preferred: expect JSON { success: true/false, message: "..." }
        success: function (resp) {
          // support both JSON object and plain string
          let data = resp;
          if (typeof resp === 'string') {
            try { data = JSON.parse(resp); } catch (e) { data = { success: resp.toLowerCase().includes('success'), message: resp }; }
          }

          if (data && data.success) {
            displayMessage(data.message || 'Password updated successfully.', false);
            setTimeout(function () {
              closeModal('changePasswordModal');
              form.reset();
              displayMessage('');
            }, 800);
          } else {
            displayMessage(data.message || 'Failed to update password.');
          }
        },
        error: function () {
          displayMessage('Server error. Please try again.');
        },
        complete: function () {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      });
    });
  } else {
    // Fetch fallback (vanilla JS)
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      const currentPassword = document.getElementById('current_password').value;
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (newPassword !== confirmPassword) {
        displayMessage('New passwords do not match.');
        return;
      }

      submitBtn.disabled = true;
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Updating...';

      fetch('change_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          current_password: currentPassword,
          new_password: newPassword
        })
      })
      .then(response => response.text())
      .then(text => {
        // try parse JSON, otherwise fallback to string
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          data = { success: text.toLowerCase().includes('success'), message: text };
        }

        if (data && data.success) {
          displayMessage(data.message || 'Password updated successfully.', false);
          setTimeout(() => {
            closeModal('changePasswordModal');
            form.reset();
            displayMessage('');
          }, 800);
        } else {
          displayMessage(data.message || 'Failed to update password.');
        }
      })
      .catch(() => displayMessage('Server error. Please try again.'))
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });
    });
  }

  // small debug log so you can verify handlers attached
  console.log('Change password modal script initialized. Change-password triggers:', document.querySelectorAll('.change-password-btn').length);
});
</script>


<script>
  const tabs = document.querySelectorAll('#profile-tabs .tab-link');
  const contents = document.querySelectorAll('.tab-content');

  tabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();

      // Reset all tabs
      tabs.forEach(item => {
        item.classList.remove('text-orange-500', 'border-orange-500', 'active');
        item.classList.add('text-gray-500', 'border-transparent', 'hover:text-orange-500', 'hover:border-orange-500');
      });

      // Activate clicked tab
      tab.classList.add('text-orange-500', 'border-orange-500', 'active');
      tab.classList.remove('text-gray-500', 'border-transparent', 'hover:text-orange-500', 'hover:border-orange-500');

      // Show corresponding content
      contents.forEach(content => {
        content.classList.add('hidden');
      });
      const target = document.querySelector(tab.getAttribute('href'));
      if (target) {
        target.classList.remove('hidden');
      }
    });
  });
</script>
<script>
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    const allMenus = document.querySelectorAll('.ellipsis-dropdown');
    
    allMenus.forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });
    // Close all other menus first
    document.querySelectorAll('.ellipsis-menu').forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
}

// Close menu when clicking outside
document.addEventListener('click', (event) => {
    if (!event.target.closest('.ellipsis-container')) {
        document.querySelectorAll('.ellipsis-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});
// Ensure menu closes after clicking an option
document.addEventListener('click', (event) => {
    if (event.target.closest('.ellipsis-menu button')) {
        const menu = event.target.closest('.ellipsis-menu');
        menu.classList.add('hidden');
    }
});
function showEditForm(id, currentCaption) {
    event.stopPropagation();
    document.getElementById('caption-' + id).style.display = 'none';
    document.getElementById('edit-form-container-' + id).style.display = 'block';

    // Hide all dropdowns
    document.querySelectorAll('.ellipsis-dropdown').forEach(menu => menu.classList.add('hidden'));
}

function cancelEdit(id) {
    document.getElementById('edit-form-container-' + id).style.display = 'none';
    document.getElementById('caption-' + id).style.display = 'block';
}

function saveEdit(id) {
    const newCaption = document.getElementById('edit-caption-input-' + id).value.trim();

    if(newCaption.length === 0) {
        alert('Caption cannot be empty.');
        return;
    }

    // Optimistically update caption text
    document.getElementById('caption-' + id).innerText = newCaption;
    cancelEdit(id);

    // Send update request
    fetch('update_caption.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, caption: newCaption })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update caption: ' + data.message);
        }
    })
    .catch(() => alert('Error updating caption.'));
}


function handleCardClick(event, recipeUrl) {
    const isEditForm = event.target.closest('.edit-form-container');
    const isMenu = event.target.closest('.ellipsis-dropdown');
    
    if (!isEditForm && !isMenu) {
        window.location.href = recipeUrl;
    }
    menu.classList.toggle('hidden');
}

</script>

<script>
let deleteId = null;

function openDeleteModal(id) {
  deleteId = id;
  document.getElementById("deleteRepostModal").classList.replace("hidden", "flex");
  document.body.style.overflow = "hidden";
}

function closeDeleteModal() {
  document.getElementById("deleteRepostModal").classList.replace("flex", "hidden");
  document.body.style.overflow = "";
  deleteId = null;
}

document.getElementById("confirmDeleteBtn").addEventListener("click", () => {
  if (!deleteId) return;

  fetch('delete_repost.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ repost_id: deleteId })
  })
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          const card = document.querySelector(`.repost-card[data-id='${deleteId}']`);
          if (card) card.remove();
          closeDeleteModal();
          location.reload();
      } else {
          alert('Failed to delete repost: ' + data.message);
      }
  })
  .catch(() => alert('Error deleting repost.'));
});

// Optional: close modal when clicking outside
document.getElementById("deleteRepostModal").addEventListener("click", e => {
  if (e.target.id === "deleteRepostModal") closeDeleteModal();
});
</script>

<script>
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

<script>
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden'; // Disable scroll
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.add('hidden');
  document.body.style.overflow = ''; // Enable scroll again
}

function handleBackdropClick(event, modalId) {
  // Close modal only if user clicks on the backdrop (not inside modal box)
  if (event.target.id === modalId) {
    closeModal(modalId);
  }
}

function showDeclineReason(recipeId, reason) {
  document.getElementById('declineReasonText').textContent =
    reason || 'No specific reason was provided.';
  openModal('declineReasonModal');
}
</script>


<script>
    // Add this JavaScript to your existing script section
function previewAndUploadProfilePic(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, JPG).');
            return;
        }
        
        // Validate file size (5MB max)
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if (file.size > maxSize) {
            alert('File size must be less than 10MB.');
            return;
        }
        
        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePicPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // Upload the file
        uploadProfilePicture(file);
    }
}

function uploadProfilePicture(file) {
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    // Show loading state
    const uploadButton = document.querySelector('[onclick*="profilePicInput"]');
    const originalHTML = uploadButton.innerHTML;
    uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
    uploadButton.disabled = true;
    
    fetch('upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the profile picture
            document.getElementById('profilePicPreview').src = data.image_url + '?t=' + new Date().getTime();
            
            // Show success message
            showNotification('Profile picture updated successfully!', 'success');
        } else {
            // Revert preview on error
            location.reload(); // Simple way to revert, or you could store original src
            showNotification(data.message || 'Failed to update profile picture.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        location.reload(); // Revert on error
        showNotification('An error occurred while uploading the image.', 'error');
    })
    .finally(() => {
        // Restore button state
        uploadButton.innerHTML = originalHTML;
        uploadButton.disabled = false;
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${type === 'success' ? 'alert-success' : 'alert-danger'} position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>

<script>
// Username input validation
$("#newUsername").on("input", function() {
  let username = $(this).val().trim();
  let messageEl = $("#usernameMessage");
  let saveBtn = $("#saveUsernameBtn");

  messageEl.removeClass("text-red-500 text-green-500 text-gray-500");
  saveBtn.prop("disabled", true);

  if (username.length < 3 || username.length > 20) {
    messageEl.text("Username must be 3–20 characters.")
             .addClass("text-red-500");
    return;
  }

  $.post("check_username.php", { username: username }, function(response) {
    messageEl.removeClass("text-red-500 text-green-500 text-gray-500");
    if (response.exists) {
      messageEl.text("❌ This username is already taken.")
               .addClass("text-red-500");
    } else {
      messageEl.text("✔ Username available!")
               .addClass("text-green-500");
      saveBtn.prop("disabled", false);
    }
  }, "json");
});

// Handle form submission
$("#usernameForm").submit(function(e) {
  e.preventDefault();
  let username = $("#newUsername").val().trim();
  let messageEl = $("#usernameMessage");

  $.post("update_username.php", { username: username }, function(response) {
    if (response.success) {
      $("#usernameDisplay").text(username);
      toggleModal("usernameModal"); // ✅ Close Tailwind modal
    } else {
      messageEl.text(response.message).addClass("text-red-500");
    }
  }, "json");
});

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    const isHidden = modal.classList.contains('hidden');

    if (isHidden) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden'); // Prevent scroll
        document.documentElement.classList.add('overflow-hidden'); // <html>

    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden'); // Re-enable scroll
        document.documentElement.classList.remove('overflow-hidden');

    }
}

function closeOnOutsideClick(event, modalId) {
  const modal = document.getElementById(modalId);
  if (event.target === modal) {
    toggleModal(modalId);
  }
}
</script>

<script>
function toggleModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  if (modal.classList.contains('hidden')) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';


    
    // Mark notifications as read when opening report history modal
    if (modalId === 'reportHistoryModal') {
      markNotificationsAsRead();
    }
  } else {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';

  }
}

function markNotificationsAsRead() {
  fetch('mark_notifications_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Hide the red badge completely
      const badge = document.getElementById('notificationBadge');
      if (badge) {
        badge.style.display = 'none';
      }
    }
  })
  .catch(error => {
    console.error('Error:', error);
  });
}

</script>


<script>
    function getUserPoints($conn, $user_id) {
    $sql = "SELECT total_points FROM user_badges WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['total_points'];
    }
    
    return 0; // Return 0 if no record exists
}
</script>

</body>
</html>