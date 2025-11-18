        <?php
        session_start();
        require 'db.php'; // Database connection

        if (!isset($_SESSION['user_id'])) {
            header("Location: signin.php");
            exit;
        }
        $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);


        // Fetch user details from session
        $username = htmlspecialchars($_SESSION['username']);
        $email = htmlspecialchars($_SESSION['email']);
        $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

        // Initialize search variable
        $categoryFilter = '';


        // Initialize search variable
        $searchQuery = '';
        if (isset($_GET['q'])) {
            $searchQuery = trim($_GET['q']); // Trim whitespace
        }

        // Modify the SQL query to include search functionality
        $sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime, recipe.servings, recipe.budget, recipe.image, recipe.video_path, users.username, recipe.user_id AS recipe_user_id 
                FROM recipe 
                JOIN users ON recipe.user_id = users.id 
                WHERE recipe.recipe_name LIKE ? 
                ORDER BY recipe.created_at DESC";

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        $searchTerm = "%" . $searchQuery . "%"; // Use wildcards for partial matching
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();


        // Check if a recipe ID is provided
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo "<h3 class='text-center text-danger'>No recipe found</h3>";
            exit;
        }

        // Fetch recipe details
        $recipe_id = intval($_GET['id']);
        $sql = "SELECT recipe.recipe_name, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime, recipe.servings, recipe.budget, recipe.image, recipe.video_path, recipe.tags, 
               users.username, users.profile_picture, 
               recipe.user_id AS recipe_user_id, recipe.created_at
        FROM recipe 
        JOIN users ON recipe.user_id = users.id 
        WHERE recipe.id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo "<h3 class='text-center text-danger'>No recipe found</h3>";
            exit;
        }
        // Fetch ingredients for the recipe
    $ingredients_sql = "SELECT ingredient_name FROM ingredients WHERE recipe_id = ?";
    $stmt = $conn->prepare($ingredients_sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $ingredients_result = $stmt->get_result();

        
        // Fetch equipments for the recipe
    $equipments_sql = "SELECT equipment_name FROM equipments WHERE recipe_id = ?";
    $stmt = $conn->prepare($equipments_sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $equipments_result = $stmt->get_result();

    // Fetch instructions for the recipe
    $instructions_sql = "SELECT instruction_name FROM instructions WHERE recipe_id = ?";
    $stmt = $conn->prepare($instructions_sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $instructions_result = $stmt->get_result();
        

    // Fetch instructions for the recipe
    $instructions_sql = "SELECT instruction_name FROM instructions WHERE recipe_id = ?";
    $stmt = $conn->prepare($instructions_sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $instructions_result = $stmt->get_result();
        
    // Fetch nutritional information for the recipe
    $nutritional_sql = "SELECT calories, fat, protein, carbohydrates, fiber, sugar, cholesterol, sodium FROM nutritional_info WHERE recipe_id = ?";
    $stmt = $conn->prepare($nutritional_sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $nutritional_result = $stmt->get_result();
    $nutritional_info = $nutritional_result->fetch_assoc();

    $fav_count_sql = "SELECT COUNT(*) AS fav_count FROM favorites WHERE recipe_id = ?";
    $fav_stmt = $conn->prepare($fav_count_sql);
    $fav_stmt->bind_param("i", $recipe_id);
    $fav_stmt->execute();
    $fav_count_result = $fav_stmt->get_result();
    $fav_count = $fav_count_result->fetch_assoc()['fav_count'] ?? 0;

    $like_count_sql = "SELECT COUNT(*) AS like_count FROM likes WHERE recipe_id = ?";
    $like_stmt = $conn->prepare($like_count_sql);
    $like_stmt->bind_param("i", $recipe_id);
    $like_stmt->execute();
    $like_count_result = $like_stmt->get_result();
    $like_count = $like_count_result->fetch_assoc()['like_count'] ?? 0;

    $check_fav_sql = "SELECT * FROM favorites WHERE user_id = ? AND recipe_id = ?";
    $check_fav_stmt = $conn->prepare($check_fav_sql);
    $check_fav_stmt->bind_param("ii", $user_id, $recipe_id);
    $check_fav_stmt->execute();
    $is_favorite = $check_fav_stmt->get_result()->num_rows > 0;
    // Check if the recipe is liked by the user
    $check_like_sql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $user_id, $recipe_id);
    $check_like_stmt->execute();
    $is_like = $check_like_stmt->get_result()->num_rows > 0;

    // Get repost count for this recipe
    $repost_count_sql = "SELECT COUNT(*) AS repost_count FROM reposts WHERE recipe_id = ?";
    $repost_stmt = $conn->prepare($repost_count_sql);
    $repost_stmt->bind_param("i", $recipe_id);
    $repost_stmt->execute();
    $repost_count_result = $repost_stmt->get_result();
    $repost_count = $repost_count_result->fetch_assoc()['repost_count'] ?? 0;

    // Check if current user has reposted this recipe
    $check_repost_sql = "SELECT * FROM reposts WHERE user_id = ? AND recipe_id = ?";
    $check_repost_stmt = $conn->prepare($check_repost_sql);
    $check_repost_stmt->bind_param("ii", $user_id, $recipe_id);
    $check_repost_stmt->execute();
    $is_reposted = $check_repost_stmt->get_result()->num_rows > 0;

// Fetch comments for the recipe with user profile pictures
$comments_sql = "SELECT comments.id, comments.comment, comments.created_at, 
                        users.username, users.id AS user_id, users.profile_picture, 
                        comments.parent_comment_id
                 FROM comments 
                 JOIN users ON comments.user_id = users.id 
                 WHERE comments.recipe_id = ? 
                 ORDER BY comments.created_at ASC";


// After fetching the recipe details and before the badges query
$recipe = $result->fetch_assoc();

// Get the recipe author's user ID
$recipe_author_id = $recipe['recipe_user_id'];

// Fetch all badges for the RECIPE AUTHOR (not the logged-in user)
$badge_sql = "SELECT badge_name, badge_icon FROM user_badges WHERE user_id = ?";
$badge_stmt = $conn->prepare($badge_sql);
$badge_stmt->bind_param("i", $recipe_author_id); // Use recipe author's ID
$badge_stmt->execute();
$badge_result = $badge_stmt->get_result();

$badges = [];
while ($row = $badge_result->fetch_assoc()) {
    $badges[] = $row;
}

// Badge gradients map
$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet' => 'linear-gradient(90deg, #504F4F, #555)',
];

// Style: gradient based on the **first badge**, else fallback orange
if (!empty($badges) && isset($badgeGradients[$badges[0]['badge_name']])) {
    $usernameStyle = 'background: ' . $badgeGradients[$badges[0]['badge_name']] . ';
                      -webkit-background-clip: text;
                      -webkit-text-fill-color: transparent;
                      background-clip: text;
                      font-weight: bold;
                      font-size: 0.85rem;';
} else {
    $usernameStyle = 'color: var(--primary-orange); font-weight: bold;';
}

// Continue with comments query...
$stmt = $conn->prepare($comments_sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$comments_result = $stmt->get_result();

?>

        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>Tasty Hub | Recipe Details</title>
            <meta content="width=device-width, initial-scale=1.0" name="viewport">

        <link href="img/favicon.png" rel="icon">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>

            <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

            <link href="lib/animate/animate.min.css" rel="stylesheet">
            <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
            <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

            <link href="css/bootstrap.min.css" rel="stylesheet">
            <link href="css/style.css" rel="stylesheet">
            <link href="css/recipe_details.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .font-nunito { font-family: 'Nunito', sans-serif; }
            [x-cloak] { display: none !important; }
        </style>
        </head>

    <body class="bg-white-100 h-[200vh]">
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
            class="bg-yellow-50 shadow-md fixed top-0 left-0 right-0 z-50 transition-transform duration-300" 
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
            <div class="flex justify-between items-center py-3">
                <!-- Logo -->
                <a href="dashboard.php" style="text-decoration:none;">
                <div class="flex items-center">
                    <img alt="Tasty Hub logo" class="h-12 w-12 mr-2" src="img/logo_new.png"/>
                    <span class="text-2xl md:text-3xl font-extrabold text-orange-400 font-nunito">Tasty Hub</span>
                </div>
            </a>
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="md:hidden p-2 rounded-md text-orange-400 hover:bg-orange-100 focus:outline-none">
                    <span class="material-icons text-2xl">
                        <span x-show="!mobileMenuOpen">menu</span>
                        <span x-show="mobileMenuOpen" x-cloak>close</span>
                    </span>
                </button>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <!-- Search Bar -->
                    <div class="w-80">
                        <div class="relative">
                            <form action="latest.php" method="GET">
                                <input name="keyword" 
                                       class="w-full bg-white border border-gray-200 rounded-full py-2 px-6 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent" 
                                       placeholder="Search for recipes..." 
                                       type="text"/>
                            </form>
                        </div>
                    </div>

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
                                        <span class="material-icons mr-3 text-orange-400">restaurant_menu</span> Search for Ingredients
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
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="profile.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">account_circle</span> Profile
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="submit_recipe.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">post_add</span> Submit a Recipe
                                    </a>
                                    <a class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                       href="about.php" role="menuitem">
                                        <span class="material-icons mr-3 text-orange-400">info</span> About
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
            <div class="md:hidden" 
                 x-show="mobileMenuOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                
                <!-- Mobile Search Bar -->
                <div class="px-4 py-3 border-t border-orange-200">
                    <form action="latest.php" method="GET">
                        <input name="keyword" 
                               class="w-full bg-white border border-gray-200 rounded-full py-2 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent" 
                               placeholder="Search for recipes..." 
                               type="text"/>
                    </form>
                </div>

                <!-- Mobile Navigation Links -->
                <div class="px-4 pb-4 space-y-2">
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
                                <span class="text-sm">Search for Ingredients</span>
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
                            <a class="flex items-center py-2 px-6 text-gray-700 hover:bg-orange-100 rounded-lg transition-colors ml-6" 
                               href="about.php">
                                <span class="material-icons mr-3 text-orange-400 text-sm">info</span>
                                <span class="text-sm">About</span>
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

<main class="mx-3 py-4 mt-24 shadow-md">
<div class="mx-3">
<div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
<div class="lg:col-span-3">
<div class="mb-4">
<h2 class="recipe_name text-gray-900"><?php echo htmlspecialchars($recipe['recipe_name']); ?></h2>
<?php 
// Fallback to noprofile.png if no profile picture
$profile_picture = !empty($recipe['profile_picture']) 
    ? 'uploads/profile_pics/' . $recipe['profile_picture'] 
    : 'img/no_profile.png';
?>
<div class="flex items-center text-[var(--neutral-text-light)] mt-2">
    <!-- Profile Picture -->
    <img src="<?= htmlspecialchars($profile_picture) ?>" 
         alt="<?= htmlspecialchars($recipe['username']) ?>" 
         class="w-6 h-6 lg:w-9 lg:h-9 md:w-9 md:h-9 rounded-full object-cover mr-1">

    <!-- Username + Badges -->
    <span class="flex items-center">
        <a href="userprofile.php?username=<?= urlencode($recipe['username']); ?>" 
           class="hover:underline"
           style="<?= $usernameStyle ?>">
            @<?= htmlspecialchars($recipe['username']); ?>
        </a>
    <?php if (!empty($badges)): ?>
        <?php foreach ($badges as $badge): ?>
            <?php if ($badge['badge_name'] !== "No Badge Yet"): ?>
                <img src="<?= htmlspecialchars($badge['badge_icon']); ?>" 
                     alt="<?= htmlspecialchars($badge['badge_name']); ?>" 
                     title="<?= htmlspecialchars($badge['badge_name']); ?>"
                     class="w-4 h-4 lg:w-6 lg:h-6 lg:w-6 lg:h-6 object-contain">
            <?php else: ?>
                <!-- leave space if badge = No Badge Yet -->
                <span></span>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- leave space if no badge array -->
        <span></span>
    <?php endif; ?>
    </span>

    <span class="mx-2">•</span>
    <span class="date">Posted on: <?= date('F j, Y', strtotime($recipe['created_at'])); ?></span>
</div>

<style>
        .date {
             font-size: 0.7rem;
}
    @media (max-width: 768px) {
        .date {
            font-size: 10px;
        }
    }  
</style>


<div class="mt-2 flex flex-wrap gap-2">
    <?php if (!empty($recipe['tags'])): ?>
        <?php
        $tags = explode(',', $recipe['tags']);

        // Define an array of pastel background/text color combinations
        $tagColors = [
            ['bg' => 'bg-orange-100', 'text' => 'text-[var(--primary-orange)]'],
            ['bg' => 'bg-teal-100',   'text' => 'text-[var(--secondary-teal)]'],
            ['bg' => 'bg-gray-100',   'text' => 'text-gray-700'],
            ['bg' => 'bg-pink-100',   'text' => 'text-pink-700'],
            ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
            ['bg' => 'bg-green-100',  'text' => 'text-green-700'],
            ['bg' => 'bg-blue-100',   'text' => 'text-blue-700'],
            ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
        ];

        $colorCount = count($tagColors);

        foreach ($tags as $index => $tag):
            $cleanTag = htmlspecialchars(trim($tag));
            $encodedTag = urlencode(trim($tag));
            $color = $tagColors[$index % $colorCount];
        ?>
            <a href="latest.php?tag=<?php echo $encodedTag; ?>" 
               class="<?php echo $color['bg'] . ' ' . $color['text']; ?> text-xs lg:text-sm md:text-sm font-medium px-3 py-1 rounded-full hover:opacity-80 transition">
                #<?php echo $cleanTag; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


</div>
<div class="relative mb-8">
<img 
  alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>" 
  class="w-full aspect-[4/3] sm:aspect-video rounded-2xl shadow-lg object-cover object-center" 
  src="<?php echo !empty($recipe['image']) ? htmlspecialchars($recipe['image']) : 'uploads/default-placeholder.png'; ?>" 
/>

<div class="absolute bottom-4 right-4 flex items-center space-x-2">
    <?php if ($recipe['recipe_user_id'] == $user_id): ?>
        <!-- Edit -->
        <a href="edit_recipe.php?id=<?php echo $recipe_id; ?>" class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition">
            <span class="material-icons text-gray-800 text-base md:text-xl">edit</span>
        </a>

<!-- Delete Button (Triggers Modal) -->
<button onclick="openDeleteModal(<?php echo $recipe_id; ?>)" 
        class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition">
    <span class="material-icons text-gray-800 text-base md:text-xl">delete</span>
</button>

<script>
function openDeleteModal(recipeId) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('confirmDeleteBtn').href = 'delete_recipe.php?id=' + recipeId;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

    <?php else: ?>
    <?php endif; ?>
</div>



<div class="absolute left-4 top-4 flex flex-col items-center space-y-4 z-50">

</div>
            
</div>


<p class="text-[var(--neutral-text)] lg:text-lg md:text-lg text-md text-justify leading-relaxed mb-8"><?php echo nl2br(htmlspecialchars($recipe['recipe_description'])); ?></p>


<?php if (!empty($recipe['video_path'])): ?>
<?php
    $videoPath = $recipe['video_path'];
    $embedHTML = '';

    // YouTube
   if (strpos($videoPath, 'youtube.com') !== false || strpos($videoPath, 'youtu.be') !== false) {
        if (strpos($videoPath, 'watch?v=') !== false) {
            $videoId = explode('watch?v=', $videoPath)[1];
            $videoId = explode('&', $videoId)[0];
        } else {
            $parts = explode('youtu.be/', $videoPath);
            if (isset($parts[1])) {
                $videoId = explode('?', $parts[1])[0];
            }
        }
        if (!empty($videoId)) {
            $embedHTML = '<iframe src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" frameborder="0" allowfullscreen class="w-full h-full"></iframe>';
        }
    }
    // Google Drive
    elseif (strpos($videoPath, 'drive.google.com') !== false) {
        $fileId = '';
        if (strpos($videoPath, '/file/d/') !== false) {
            $parts = explode('/file/d/', $videoPath);
            if (isset($parts[1])) {
                $fileId = explode('/', $parts[1])[0];
            }
        } elseif (strpos($videoPath, 'id=') !== false) {
            $parts = explode('id=', $videoPath);
            if (isset($parts[1])) {
                $fileId = explode('&', $parts[1])[0];
            }
        }
        if (!empty($fileId)) {
            $embedHTML = '<iframe src="https://drive.google.com/file/d/' . htmlspecialchars($fileId) . '/preview" allow="autoplay" allowfullscreen class="w-full h-full"></iframe>';
        }
    }
    elseif (strpos($videoPath, 'uploads/videos/') !== false || file_exists($videoPath)) {
        $embedHTML = '<video controls class="w-full h-full rounded-lg">
                        <source src="' . htmlspecialchars($videoPath) . '" type="video/mp4">
                        <source src="' . htmlspecialchars($videoPath) . '" type="video/mov">
                        <source src="' . htmlspecialchars($videoPath) . '" type="video/avi">
                        Your browser does not support the video tag.
                      </video>';
    }
?>
<div class="mb-12">
    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b-2 border-[var(--primary-orange)] pb-2 inline-block">Video Tutorial</h2>

    <!-- Embed Video -->
    <div class="relative w-full max-w-4xl mx-auto rounded-2xl shadow-lg overflow-hidden aspect-video">
        <?php echo $embedHTML; ?>
    </div>
</div>
<?php endif; ?>

<h2 class="text-2xl font-bold text-gray-900 mb-4 border-b-2 border-[var(--primary-orange)] pb-2 inline-block">Instructions</h2>
<ol class="list-decimal list-inside space-y-4 text-[var(--neutral-text)]">

                    <?php while ($instruction = $instructions_result->fetch_assoc()) : ?>
                        <li><?php echo htmlspecialchars($instruction['instruction_name']); ?></li>
                    <?php endwhile; ?>

</ol>
</div>
<div class="lg:col-span-2 space-y-8 lg:mt-[10rem]">
<div class="bg-white p-6 rounded-2xl shadow-md border border-[var(--neutral-border)]">
<h3 class="text-xl font-bold text-gray-900 mb-4">Recipe Details</h3>
<div class="grid grid-cols-2 gap-6">
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Category</h4>
<p class="text-md lg:text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['category']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Preparation</h4>
<p class="text-md lg:text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['preparation']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Cooking Time</h4>
<p class="text-md lg:text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['cooktime']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Difficulty</h4>
<p class="text-md lg:text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['difficulty']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Servings</h4>
<p class="text-md lg:text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['servings']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Budget</h4>
<p class="text-xs lg:text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['budget']); ?></p>
</div>
</div>
</div>

<div class="bg-white p-6 rounded-2xl shadow-md border border-[var(--neutral-border)]">
<h2 class="text-xl font-bold text-gray-900 mb-4">Ingredients &amp; Equipment</h2>
<div class="space-y-4">
<div>
<h3 class="text-lg font-semibold text-[var(--neutral-text)] mb-2">Ingredients</h3>
<div class="space-y-3">
                
                    <?php while ($ingredient = $ingredients_result->fetch_assoc()) : ?>
                        <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer text-sm lg:text-md md:text-md">
                        <li style="list-style-type: none;"><input class="h-5 w-5 rounded border-gray-300 text-[var(--primary-orange)] focus:ring-[var(--primary-orange)] mr-3" type="checkbox"><span> <?php echo htmlspecialchars($ingredient['ingredient_name']); ?> </span></li>
                    </label>
                    <?php endwhile; ?>
                
</div>
</div>
<div class="border-t border-[var(--neutral-border)] my-4"></div>
<div>
<h3 class="text-lg font-semibold text-[var(--neutral-text)] mb-2">Equipment</h3>
<div class="space-y-3">
                        <?php while ($equipment = $equipments_result->fetch_assoc()) : ?>
                    <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer text-sm lg:text-md md:text-md">
                        <li style="list-style-type: none;"><input class="h-5 w-5 rounded border-gray-300 text-[var(--primary-orange)] focus:ring-[var(--primary-orange)] mr-3" type="checkbox"> <span> <?php echo htmlspecialchars($equipment['equipment_name']); ?></span></li> </label>
                    <?php endwhile; ?>
</div>
</div>
</div>
</div>

<div class="bg-white p-6 rounded-2xl shadow-md border border-[var(--neutral-border)]">
<h2 class="text-xl font-bold text-gray-900 mb-4">Nutritional Information</h2>
<p class="text-sm text-[var(--neutral-text-light)] mb-4">Values per serving</p>
<div class="space-y-3">
<div class="flex items-center justify-between">
<span class="font-medium text-[var(--neutral-text)]">Calories</span>
<span class="font-bold text-lg text-[var(--primary-orange)]"><?php echo isset($nutritional_info['calories']) ? htmlspecialchars($nutritional_info['calories']) : 'N/A'; ?> kcal</span>
</div>
<div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Fat</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['fat']) ? htmlspecialchars($nutritional_info['fat']) : 'N/A'; ?> g</span>
</div>
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Carbs</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['carbohydrates']) ? htmlspecialchars($nutritional_info['carbohydrates']) : 'N/A'; ?> g</span>
</div>
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Protein</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['protein']) ? htmlspecialchars($nutritional_info['protein']) : 'N/A'; ?> g</span>
</div>
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Sugar</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['sugar']) ? htmlspecialchars($nutritional_info['sugar']) : 'N/A'; ?> g</span>
</div>
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Fiber</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['fiber']) ? htmlspecialchars($nutritional_info['fiber']) : 'N/A'; ?> g</span>
</div>
<div class="flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Sodium</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['sodium']) ? htmlspecialchars($nutritional_info['sodium']) : 'N/A'; ?> mg</span>
</div>
<div class="col-span-2 flex justify-between border-b border-dashed border-gray-200 py-1">
<span class="text-[var(--neutral-text-light)]">Cholesterol</span>
<span class="font-semibold text-gray-800"><?php echo isset($nutritional_info['cholesterol']) ? htmlspecialchars($nutritional_info['cholesterol']) : 'N/A'; ?> mg</span>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-80 p-6">
        <h2 class="text-md font-semibold text-gray-800 mb-2">Delete Recipe</h2>
        <p class="text-sm text-gray-600 mb-2 leading-relaxed">
            Are you sure you want to delete this recipe? This action cannot be undone.
        </p>

        <!-- Buttons -->
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" 
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm">
                Cancel
            </button>
            <a id="confirmDeleteBtn" href="#" 
               class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm">
                Delete
            </a>
        </div>
    </div>
</div>

<?php
// Add this RIGHT AFTER your existing comments query and BEFORE the HTML output

// Badge gradients map (define it globally)
$badgeGradients = [
    'Freshly Baked'   => 'linear-gradient(190deg, yellow, brown)',
    'Kitchen Star'    => 'linear-gradient(50deg, yellow, green)',
    'Flavor Favorite' => 'linear-gradient(180deg, darkgreen, yellow)',
    'Gourmet Guru'    => 'linear-gradient(90deg, darkviolet, gold)',
    'Culinary Star'   => 'linear-gradient(180deg, red, blue)',
    'Culinary Legend' => 'linear-gradient(180deg, red, gold)',
    'No Badge Yet' => 'linear-gradient(90deg, #504F4F, #555)',

];


// Function to get user badge style
function getUserBadgeStyle($userId, $conn, $badgeGradients) {
    // Fetch user badges
    $badge_sql = "SELECT badge_name, badge_icon FROM user_badges WHERE user_id = ? LIMIT 1";
    $badge_stmt = $conn->prepare($badge_sql);
    $badge_stmt->bind_param("i", $userId);
    $badge_stmt->execute();
    $badge_result = $badge_stmt->get_result();
    $badge = $badge_result->fetch_assoc();

    if ($badge && isset($badgeGradients[$badge['badge_name']])) {
        return [
            'style' => 'background: ' . $badgeGradients[$badge['badge_name']] . ';
                       -webkit-background-clip: text;
                       -webkit-text-fill-color: transparent;
                       background-clip: text;
                       font-weight: bold;
                       font-size: 1rem;',
            'badge' => $badge
        ];
    } else {
        return [
            'style' => 'color: var(--primary-orange); font-weight: bold;',
            'badge' => null
        ];
    }
}

// Fetch all comments into array
$all_comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $all_comments[] = $row;
}
?>

<!-- HTML COMMENT SECTION -->


<script>
function toggleReplies(repliesId, button) {
    const repliesDiv = document.getElementById(repliesId);
    
    if (repliesDiv.classList.contains('hidden')) {
        repliesDiv.classList.remove('hidden');
        button.textContent = 'Hide replies';
    } else {
        repliesDiv.classList.add('hidden');
        const replyCount = repliesDiv.children.length;
        button.textContent = `See replies (${replyCount})`;
    }
}
</script>
<!-- Back to Top Button -->
<a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top p-2" id="backToTopBtn">
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
    </div>
    </main>
    <script>
function toggleReplies(id, btn) {
    const container = document.getElementById(id);
    const isHidden = container.classList.contains('hidden');
    container.classList.toggle('hidden');
    btn.textContent = isHidden ? 'Hide replies' : btn.textContent.replace('Hide replies', 'See more replies');
}
</script>

<script>

document.addEventListener("DOMContentLoaded", function() {
    const searchDropdown = document.querySelector('.search-dropdown');
    const dropdownToggle = searchDropdown.querySelector('.dropdown-toggle');

    // Show dropdown on click
    dropdownToggle.addEventListener('click', function(event) {
        searchDropdown.classList.toggle('active'); // Toggle active class
        const dropdownContent = searchDropdown.querySelector('.custom-search-dropdown-content');
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block'; // Toggle display
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchDropdown.contains(event.target) && !dropdownToggle.contains(event.target)) {
            searchDropdown.classList.remove('active'); // Remove active class
            searchDropdown.querySelector('.custom-search-dropdown-content').style.display = 'none'; // Hide dropdown
        }
    });
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
    // Handle favorite actions
    document.querySelectorAll(".favorite-action").forEach(btn => {
        btn.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();

            let recipeId = this.getAttribute("data-recipe-id");
            let iconElement = this.querySelector("span.material-icons");
            let countElement = this.querySelector(".fav-count"); // Now this will correctly select the count element

            fetch("favorite_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "recipe_id=" + recipeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "added") {
                    iconElement.textContent = 'bookmark'; // Change icon to filled
                    // Update button classes for favorited state
                    btn.classList.remove('bg-black/30', 'text-white', 'hover:bg-black/50');
                    btn.classList.add('bg-white/80', 'text-[var(--primary-orange)]', 'hover:bg-white');
                } else if (data.status === "removed") {
                    iconElement.textContent = 'bookmark_border'; // Change icon to outline
                    // Update button classes for unfavorited state
                    btn.classList.remove('bg-white/80', 'text-[var(--primary-orange)]', 'hover:bg-white');
                    btn.classList.add('bg-black/30', 'text-white', 'hover:bg-black/50');
                }
                // Update the count
                countElement.textContent = data.count; // Update the favorite count
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
            let iconElement = this.querySelector("span.material-icons");
            let countElement = this.querySelector(".like-count");

            fetch("like_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "recipe_id=" + recipeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "added") {
                    iconElement.textContent = 'favorite'; // Change icon to filled
                    // Update button classes for liked state
                    btn.classList.remove('bg-black/30', 'text-white', 'hover:bg-black/50');
                    btn.classList.add('bg-white/80', 'text-[var(--primary-orange)]', 'hover:bg-white');
                } else if (data.status === "removed") {
                    iconElement.textContent = 'favorite_border'; // Change icon to outline
                    // Update button classes for unliked state
                    btn.classList.remove('bg-white/80', 'text-[var(--primary-orange)]', 'hover:bg-white');
                    btn.classList.add('bg-black/30', 'text-white', 'hover:bg-black/50');
                }
                // Update the count
                countElement.textContent = data.count; // Update the like count
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
</script>


            <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="js/main.js"></script>
        </div>

        </body>

        </html>