        <?php
        session_start();
        require 'db.php'; // Database connection
    require 'badge_utils.php';

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
$badge_sql = "SELECT user_id, badge_name, badge_icon FROM user_badges WHERE user_id = ?";
$badge_stmt = $conn->prepare($badge_sql);
$badge_stmt->bind_param("i", $recipe_author_id); // Use recipe author's ID
$badge_stmt->execute();
$badge_result = $badge_stmt->get_result();

$badges = [];
while ($row = $badge_result->fetch_assoc()) {
        update_user_badge($conn, $recipe_author_id);
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

        <!-- Favicon -->
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
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            #backToTopBtn {
                        display: none;
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 99;
                           background-color: #FEA116; /* your desired orange */
                }                  


            body.modal-open {
                overflow: hidden;
            }
            .modal-backdrop.show {
              background-color: rgba(255, 255, 255, 0.6) !important; /* white with 80% opacity */
              backdrop-filter: blur(2px); /* blur effect */
            }

        

           .modal-dialog {
            max-height: 90vh; /* Limit modal height to 90% of viewport */
            display: flex;
            align-items: center;
        }
        
        
        .modal-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        
        .modal-body {
            flex: 1;
            overflow-y: auto; /* Add scroll if content is too tall */
            max-height: calc(85vh - 120px); /* Account for header and footer */
        }
        
        .modal-footer {
            flex-shrink: 0; /* Prevent footer from shrinking */
            border-top: 1px solid #dee2e6;
        }
        
        .modal-header {
            flex-shrink: 0; /* Prevent header from shrinking */
            border-bottom: 1px solid #dee2e6;
        }
</style>
        </head>

    <body class="bg-white-100 h-[200vh]">


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
<span class="flex items-center gap-1">
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
    <!-- Share -->
    <button class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition btn-share" onclick="openShareModal('<?php echo $recipe_id; ?>')">
      <span class="material-icons text-gray-800 text-base md:text-xl">share</span>
    </button>

    <!-- Print -->
    <button 
      onclick="window.open('printable_recipe.php?recipe_id=<?= $recipe_id ?>', '_blank')" 
      class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition"
    >
      <span class="material-icons text-gray-800 text-base md:text-xl">print</span>
    </button>

    <!-- Edit -->
    <button onclick="openEditModal(<?php echo $recipe_id; ?>)" 
      class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition">
      <span class="material-icons text-gray-800 text-base md:text-xl">edit</span>
    </button>

    <!-- Delete -->
    <button onclick="openDeleteModal(<?php echo $recipe_id; ?>)" 
      class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition">
      <span class="material-icons text-gray-800 text-base md:text-xl">delete</span>
    </button>

  <?php else: ?>
    <!-- Print -->
    <button 
      onclick="window.open('printable_recipe.php?recipe_id=<?= $recipe_id ?>', '_blank')" 
      class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition"
    >
      <span class="material-icons text-gray-800 text-base md:text-xl">print</span>
    </button>

    <!-- Share -->
    <button class="bg-white/80 backdrop-blur-sm p-1.5 md:p-2 rounded-full shadow-md hover:bg-white transition btn-share" onclick="openShareModal('<?php echo $recipe_id; ?>')">
      <span class="material-icons text-gray-800 text-base md:text-xl">share</span>
    </button>
  <?php endif; ?>
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 px-4">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full max-w-sm relative">
        <!-- Close Button -->
        <button onclick="closeShareModal()" 
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl leading-none">
            &times;
        </button>
        
        <!-- Title -->
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Share Modal</h3>
        
        <!-- Share via text -->
        <p class="text-sm text-gray-600 mb-3">Share this link via</p>
        
        <!-- Social Share Buttons -->
        <div class="flex justify-center gap-3 mb-6">
            <!-- Facebook -->
            <a href="#" id="facebookShare" target="_blank" 
               class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition">
                <i class="fab fa-facebook-f"></i>
            </a>
            <!-- Twitter -->
            <a href="#" id="twitterShare" target="_blank" 
               class="w-12 h-12 rounded-full bg-sky-400 text-white flex items-center justify-center hover:bg-sky-500 transition">
                <i class="fab fa-twitter"></i>
            </a>
            <!-- Telegram -->
            <a href="#" id="telegramShare" target="_blank"
               class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 transition">
                <i class="fab fa-telegram-plane"></i>
            </a>
        </div>
        
        <!-- Or copy link text -->
        <p class="text-sm text-gray-600 mb-2">Or copy link</p>
        
                <!-- Share Link Input -->
        <div class="flex items-center gap-2 relative">
          <input type="text" id="shareLink" readonly value="example.com/share-link"
                 class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 bg-gray-50">
          <button onclick="copyShareLink()" 
                  class="flex-shrink-0 bg-orange-600 text-white transition px-4 py-2 rounded-lg text-sm font-medium shadow hover:bg-orange-700">
            Copy
          </button>

          <!-- Toast -->
          <div id="toast" 
               class="absolute right-0 bottom-[-40px] bg-gray-900 text-white text-xs font-medium py-1 px-3 rounded-md opacity-0 transition-opacity duration-300 pointer-events-none">
            Copied!
          </div>
        </div>
    </div>
</div>

<!-- Edit Confirmation Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-4 w-full max-w-sm mx-4 relative">
        <!-- Title -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Edit Recipe</h2>
        <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to edit this recipe? Unsaved changes may be lost.
        </p>

        <!-- Buttons -->
        <div class="flex justify-end gap-2">
            <button onclick="closeEditModal()" 
                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm">
                Cancel
            </button>
            <a id="confirmEditBtn" href="#" 
               class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm">
                Edit
            </a>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-4 w-full max-w-sm mx-4 relative">
        <!-- Title -->
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Delete Recipe</h2>
        <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to delete this recipe? This action cannot be undone.
        </p>

        <!-- Buttons -->
        <div class="flex justify-end gap-2">
            <button onclick="closeDeleteModal()" 
                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm">
                Cancel
            </button>
            <a id="confirmDeleteBtn" href="#" 
               class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm">
                Delete
            </a>
        </div>
    </div>
</div>


<div class="absolute left-4 top-4 flex flex-col items-center space-y-3 z-100">
  <!-- Like Button -->
  <button class="p-1.5 md:p-2 rounded-full backdrop-blur-sm transition flex flex-col items-center like-action 
      <?php echo $is_like 
              ? 'bg-white/80 text-[var(--primary-orange)] hover:bg-white' 
              : 'bg-black/30 text-white hover:bg-black/50'; ?>"
      data-recipe-id="<?php echo $recipe_id; ?>">
      <span class="material-icons text-lg md:text-2xl lg:text-3xl">
          <?php echo $is_like ? 'favorite' : 'favorite_border'; ?>
      </span>
      <span class="text-[10px] md:text-xs lg:text-sm font-semibold like-count"><?php echo $like_count; ?></span>
  </button>

  <!-- Favorite Button -->
  <button class="p-1.5 md:p-2 rounded-full backdrop-blur-sm transition flex flex-col items-center favorite-action 
      <?php echo $is_favorite 
              ? 'bg-white/80 text-[var(--primary-orange)] hover:bg-white' 
              : 'bg-black/30 text-white hover:bg-black/50'; ?>"
      data-recipe-id="<?php echo $recipe_id; ?>">
      <span class="material-icons text-lg md:text-2xl lg:text-3xl">
          <?php echo $is_favorite ? 'bookmark' : 'bookmark_border'; ?>
      </span>
      <span class="text-[10px] md:text-xs lg:text-sm font-semibold fav-count"><?php echo $fav_count; ?></span>
  </button>

  <!-- Repost Button -->
  <button onclick="openRepostModal(<?php echo $recipe_id; ?>)" 
          class="text-white bg-black/30 backdrop-blur-sm p-1.5 md:p-2 rounded-full hover:bg-black/50 transition flex flex-col items-center">
      <span class="material-icons text-lg md:text-2xl lg:text-3xl">repeat</span>
      <span class="repost-count text-[10px] md:text-xs lg:text-sm font-semibold"><?php echo $repost_count; ?></span>
  </button>
</div>

            

<!-- Repost Modal -->
<div id="repostModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" onclick="handleOutsideClick(event)">
  <div class="bg-white rounded-xl shadow-md p-3 w-full max-w-xs relative animate-fadeIn" onclick="event.stopPropagation()">
        <!-- Close Button -->
        <button onclick="closeRepostModal()" 
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold">
            &times;
        </button>

        <!-- Title -->
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Repost Recipe</h3>
        <p class="text-sm text-gray-500 mb-3">Add a caption before sharing.</p>

        <!-- Form -->
        <form id="repostForm" action="repost_action.php" method="POST" class="space-y-3">
            <input type="hidden" name="recipe_id" value="">
            
            <textarea name="caption" rows="2" 
                      placeholder="Write your caption..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-[var(--primary-orange)] focus:outline-none resize-none"></textarea>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeRepostModal()" 
                        class="px-3 py-1.5 rounded-md border border-gray-300 text-gray-600 text-sm hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-1.5 rounded-md bg-[var(--primary-orange)] text-white text-sm hover:bg-orange-600 transition">
                    Repost
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Animation -->
<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.25s ease-out;
}
</style>
<script>
const modal = document.getElementById("repostModal");
const form = document.getElementById("repostForm");
let scrollY = 0;

function openRepostModal(id) {
  form.recipe_id.value = id;
  scrollY = window.scrollY;
  modal.classList.replace("hidden", "flex");
  document.body.style.cssText = `position:fixed;top:-${scrollY}px;width:100%`;
}

function closeRepostModal() {
  modal.classList.replace("flex", "hidden");
  document.body.style.cssText = "";
  window.scrollTo(0, scrollY);
}

modal.addEventListener("click", e => {
  if (e.target === modal) closeRepostModal();
});
</script>


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
                       font-size: 0.85rem;',
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


<!-- Report Comment Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true" style="z-index: 9999;">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border shadow rounded-4">
      <form class="reportForm" method="POST" action="submit_commentreport.php">
        <!-- Header -->
        <div class="modal-header border-0 border-bottom">
          <h6 class="modal-title fw-bold text-dark" id="reportModalLabel">Report Comment</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body -->
        <div class="modal-body" id="reportModalBody">
          <input type="hidden" name="comment_id" id="report-comment-id">
          <input type="hidden" name="reported_user_id" id="report-reported-user">
          <input type="hidden" name="recipe_id" id="report-recipe-id">

          <!-- Reported Comment -->
          <div class="mb-3 p-3 rounded-3 border bg-light">
            <p class="text-muted mb-1 small">Reported Comment</p>
            <p id="report-comment-text" class="fw-semibold text-dark mb-0"></p>
          </div>

          <!-- Reason -->
          <div class="mb-3">
            <label for="report-reason" class="form-label fw-semibold small text-secondary">Reason</label>
            <select name="reason" id="report-reason" required class="form-select form-select-sm border rounded-3 shadow-sm">
              <option value="">Select a reason</option>
              <option value="Spam">Spam</option>
              <option value="Offensive">Offensive</option>
              <option value="Harassment">Harassment</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <!-- Other Reason -->
          <div id="other-reason-container" class="d-none mt-3">
            <label for="other-reason" class="form-label fw-semibold small text-secondary">Please specify</label>
            <textarea name="other_reason" id="other-reason" rows="3" 
              class="form-control form-control-sm border rounded-3 shadow-sm"
              placeholder="Enter your reason..."></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer border-0 border-top" id="reportModalFooter">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger rounded-3 px-3 shadow-sm">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>



<script>
  // Show/hide "Other" reason field
  document.addEventListener('DOMContentLoaded', () => {
    const reasonSelect = document.getElementById('report-reason');
    const otherReasonContainer = document.getElementById('other-reason-container');
    const otherReasonInput = document.getElementById('other-reason');

    reasonSelect.addEventListener('change', function () {
      if (this.value === "Other") {
        otherReasonContainer.classList.remove("d-none");
        otherReasonInput.required = true;
      } else {
        otherReasonContainer.classList.add("d-none");
        otherReasonInput.required = false;
        otherReasonInput.value = "";
      }
    });
  });
</script>


<!-- HTML COMMENT SECTION -->
<div id="comments" class="comments-section mt-12 lg:px-4">
    <h2 class="text-3xl font-bold text-gray-900 mb-6">Comments</h2>
    <div class="space-y-8 max-w-4xl">
        <div class="flex items-start space-x-4 bg-white shadow-sm rounded-xl p-3 border border-gray-100">
            <div class="flex-1 px-3">
                <form action="submit_comment.php" method="POST" class="space-y-2">
                    <input type="hidden" name="recipe_id" value="<?= htmlspecialchars($recipe_id) ?>">
                    <textarea name="comment" rows="3" required class="w-full p-3 border border-[var(--neutral-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--primary-orange)]" placeholder="Add a comment..."></textarea>
                    <div class="flex justify-end mt-2">
                        <button type="submit" class="bg-[var(--primary-orange)] text-white px-6 py-2 rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--primary-orange)] transition">Post Comment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="comments-section space-y-4 lg:px-4">
            <?php
            // Display parent comments (comments with no parent_comment_id or parent_comment_id = NULL)
            foreach ($all_comments as $comment) {
                if ($comment['parent_comment_id'] == null) {
                    // Get badge info for this user
                    $userBadgeInfo = getUserBadgeStyle($comment['user_id'], $conn, $badgeGradients);
                    ?>
                    
                    <!-- Parent Comment -->
                    <div class="bg-white shadow-sm rounded-xl p-3 space-y-3 border border-gray-100">
                        <div class="flex items-start space-x-2">
                            <img src="<?= htmlspecialchars($comment['profile_picture'] 
                                    ? 'uploads/profile_pics/' . $comment['profile_picture'] 
                                    : 'img/noprofile.png') ?>" 
                                 alt="<?= htmlspecialchars($comment['username']) ?>"
                                 class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover">

                            <div class="flex-1">
                                   <!-- Eto -->
                                <!-- Username & Date with Badge -->
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between md:flex-row md:items-center md:justify-between">
                                    <!-- Username + Badge -->
                                    <div class="flex items-center gap-1">
                                        <a href="userprofile.php?username=<?= urlencode($comment['username']) ?>" 
                                           class="hover:underline"
                                           style="<?= $userBadgeInfo['style'] ?>">
                                            @<?= htmlspecialchars($comment['username']) ?>
                                        </a>
                                        
                                        <?php if ($userBadgeInfo['badge'] && strtolower($userBadgeInfo['badge']['badge_name']) !== 'no badge yet'): ?>
                                            <img src="<?= htmlspecialchars($userBadgeInfo['badge']['badge_icon']) ?>" 
                                                 alt="<?= htmlspecialchars($userBadgeInfo['badge']['badge_name']) ?>" 
                                                 title="<?= htmlspecialchars($userBadgeInfo['badge']['badge_name']) ?>"
                                                 class="w-6 h-6 object-contain">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Date inline (large screens) -->
                                    <small class="hidden lg:inline text-gray-500 text-2xs lg:text-xs md:text-xs">
                                        <?= date("g:i a d-m-Y", strtotime($comment['created_at'])) ?>
                                    </small>
                                </div>

                                <!-- Date below username (mobile/small screens) -->
                                <small class="lg:hidden block text-gray-500 text-2xs mt-1">
                                    <?= date("g:i a d-m-Y", strtotime($comment['created_at'])) ?>
                                </small>


                                <!-- Comment Text -->
                                <p class="text-gray-700 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                </p>

                                <!-- Actions -->
                                <div class="flex items-center gap-4 mt-2 text-2xs lg:text-xs md:text-xs">
                                    <?php if ($comment['user_id'] == $user_id): ?>
                                        <form action="delete_comment.php" method="POST" class="inline">
                                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                            <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                                            <button type="submit" 
                                                    class="text-red-500 hover:text-red-600 transition">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button type="button" 
                                            onclick="document.getElementById('reply-form-<?= $comment['id'] ?>').classList.toggle('hidden');"
                                            class="text-gray-700 hover:text-gray-900 transition">
                                        Reply
                                    </button>

                                    <?php if (isset($user_id) && $comment['user_id'] != $user_id): ?>
                                    <button type="button"
                                            class="text-red-500 hover:text-red-600 transition text-2xs lg:text-xs md:text-xs"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reportModal"
                                            data-comment-id="<?= $comment['id'] ?>"
                                            data-reported-user="<?= $comment['user_id'] ?>"
                                            data-recipe-id="<?= $recipe_id ?>"
                                            data-comment-text="<?= htmlspecialchars($comment['comment']) ?>">
                                        Report
                                    </button>
                                <?php endif; ?>

                                </div>

                                <!-- Reply Form -->
                                <div id="reply-form-<?= $comment['id'] ?>" class="hidden mt-3">
                                    <form action="submit_comment.php" method="POST" class="space-y-2">
                                        <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                                        <input type="hidden" name="parent_comment_id" value="<?= $comment['id'] ?>">
                                        <textarea name="comment" rows="2" required placeholder="Write a reply..." 
                                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"></textarea>
                                        <button type="submit" 
                                                class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-2xs lg:text-xs md:text-xs">
                                            Post Reply
                                        </button>
                                    </form>
                                </div>

                                <?php
                                // Check for replies to this comment
                                $replies = array_filter($all_comments, function($c) use ($comment) {
                                    return $c['parent_comment_id'] == $comment['id'];
                                });

                                if (!empty($replies)): ?>
                                    <!-- See More Replies Button -->
                                    <button 
                                        onclick="toggleReplies('replies-<?= $comment['id'] ?>', this)" 
                                        class="mt-3 text-2xs lg:text-xs md:text-xs text-gray-700 hover:text-gray-900 transition">
                                        See replies (<?= count($replies) ?>)
                                    </button>

                                    <!-- Nested Replies Container -->
                                    <div id="replies-<?= $comment['id'] ?>" class="hidden mt-3 space-y-3">
                                        <?php foreach ($replies as $reply): 
                                            $replyBadgeInfo = getUserBadgeStyle($reply['user_id'], $conn, $badgeGradients);
                                            ?>
                                            
                                            <div class="border-l-2 border-gray-200 pl-2 space-y-2">
                                                <div class="flex items-start space-x-3">
                                                    <img src="<?= htmlspecialchars($reply['profile_picture'] 
                                                            ? 'uploads/profile_pics/' . $reply['profile_picture'] 
                                                            : 'img/noprofile.png') ?>" 
                                                         alt="<?= htmlspecialchars($reply['username']) ?>"
                                                         class="w-8 h-8 lg:w-10 lg:h-10 md:w-10 md:h-10 rounded-full object-cover">

                                                <div class="flex-1">
                                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                                        <!-- Username + badge -->
                                                        <div class="flex items-center gap-1">
                                                            <a href="userprofile.php?username=<?= urlencode($reply['username']) ?>" 
                                                               class="hover:underline text-sm"
                                                               style="<?= $replyBadgeInfo['style'] ?>">
                                                                @<?= htmlspecialchars($reply['username']) ?>
                                                            </a>
                                                            
                                                            <?php if ($replyBadgeInfo['badge'] && strtolower($replyBadgeInfo['badge']['badge_name']) !== 'no badge yet'): ?>
                                                                <img src="<?= htmlspecialchars($replyBadgeInfo['badge']['badge_icon']) ?>" 
                                                                     alt="<?= htmlspecialchars($replyBadgeInfo['badge']['badge_name']) ?>" 
                                                                     title="<?= htmlspecialchars($replyBadgeInfo['badge']['badge_name']) ?>"
                                                                     class="w-6 h-6 object-contain">
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Date (visible inline only on lg screens) -->
                                                        <small class="hidden lg:inline text-gray-500 text-2xs lg:text-xs md:text-xs">
                                                            <?= date("g:i a d-m-Y", strtotime($reply['created_at'])) ?>
                                                        </small>
                                                    </div>

                                                    <!-- Date (shown under username on mobile only) -->
                                                    <small class="lg:hidden block text-gray-500 text-2xs mt-1">
                                                        <?= date("g:i a d-m-Y", strtotime($reply['created_at'])) ?>
                                                    </small>

                                                    <!-- Reply text -->
                                                    <p class="text-gray-600 text-2xs lg:text-xs md:text-xs leading-relaxed">
                                                        <?= nl2br(htmlspecialchars($reply['comment'])) ?>
                                                    </p>

                                                    <!-- Actions -->
                                                    <?php if ($reply['user_id'] == $user_id): ?>
                                                        <form action="delete_comment.php" method="POST" class="inline">
                                                            <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                            <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                                                            <button type="submit" class="text-red-500 hover:text-red-600 transition text-xs">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if (isset($user_id) && $reply['user_id'] != $user_id): ?>
                                                        <button type="button"
                                                                class="text-red-500 hover:text-red-600 transition text-2xs lg:text-xs md:text-xs"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#reportModal"
                                                                data-comment-id="<?= $reply['id'] ?>"
                                                                data-reported-user="<?= $reply['user_id'] ?>"
                                                                data-recipe-id="<?= $recipe_id ?>"
                                                                data-comment-text="<?= htmlspecialchars($reply['comment']) ?>">
                                                            Report
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>












<script>
    document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    const modalBody = document.getElementById('reportModalBody');
    const modalFooter = document.getElementById('reportModalFooter');

    const originalBody = modalBody.innerHTML;
    const originalFooter = modalFooter.innerHTML;

    // Function to show modal and prefill data
    window.openReportModal = function(commentId, reportedUserId, recipeId, commentText) {
        document.getElementById('report-comment-id').value = commentId;
        document.getElementById('report-reported-user').value = reportedUserId;
        document.getElementById('report-recipe-id').value = recipeId;
        document.getElementById('report-comment-text').textContent = commentText;

        // Reset form
        modalBody.innerHTML = originalBody;
        modalFooter.innerHTML = originalFooter;
        modalFooter.style.display = 'flex';

        initOtherReasonToggle();
        modal.show();
    };

    // "Other reason" toggle
    function initOtherReasonToggle() {
        const reasonSelect = document.getElementById('report-reason');
        const otherContainer = document.getElementById('other-reason-container');
        const otherInput = document.getElementById('other-reason');

        if (!reasonSelect) return;

        reasonSelect.addEventListener('change', function () {
            if (this.value === 'Other') {
                otherContainer.classList.remove('d-none');
                otherInput.required = true;
            } else {
                otherContainer.classList.add('d-none');
                otherInput.required = false;
                otherInput.value = '';
            }
        });
    }

    // Submit handler (event delegation)
    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.reportForm');
        if (!form) return;
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        const formData = new FormData(form);

        fetch(form.action, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            modalFooter.style.display = 'none'; // hide buttons

            if (data.status === 'already_reported') {
                modalBody.innerHTML = `
                    <div class="text-center text-warning">
                        <h5 class="mb-3">⚠️ You already submitted a report for this comment.</h5>
                        <p>Please wait until the admin reviews it.</p>
                    </div>
                `;
            } else if (data.status === 'success') {
                modalBody.innerHTML = `
                    <div class="text-center text-success">
                        <h5 class="mb-3">✅ Thank you for submitting a report!</h5>
                        <p>Your report has been received and will be reviewed by the admin.</p>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="text-center text-danger">
                        <p>⚠️ Something went wrong. Please try again later.</p>
                    </div>
                `;
                modalFooter.style.display = 'flex';
            }
        })
        .catch(() => {
            modalFooter.style.display = 'none';
            modalBody.innerHTML = `
                <div class="text-center text-danger">
                    <p>⚠️ Something went wrong. Please try again later.</p>
                </div>
            `;
        })
        .finally(() => submitBtn.disabled = false);
    });

    // Reset modal when closed
    document.getElementById('reportModal').addEventListener('hidden.bs.modal', function () {
        modalBody.innerHTML = originalBody;
        modalFooter.innerHTML = originalFooter;
        modalFooter.style.display = 'flex';
        initOtherReasonToggle();
    });
});

</script>

<!-- Updated JavaScript for better modal handling -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const reportModal = document.getElementById('reportModal');
  
  if (reportModal) {
    // Bootstrap 5 modal event listeners
    reportModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      
      if (button) {
        document.getElementById('report-comment-id').value = button.getAttribute('data-comment-id') || '';
        document.getElementById('report-reported-user').value = button.getAttribute('data-reported-user') || '';
        document.getElementById('report-recipe-id').value = button.getAttribute('data-recipe-id') || '';
        document.getElementById('report-comment-text').textContent = button.getAttribute('data-comment-text') || '';
      }
    });

    // Ensure modal is fully clickable when shown
    reportModal.addEventListener('shown.bs.modal', function() {
      // Focus on the select element to ensure it's interactive
      const reasonSelect = document.getElementById('report-reason');
      if (reasonSelect) {
        reasonSelect.focus();
      }
    });
  }

  // Fix for custom modals (share, video, repost)
  function fixCustomModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.addEventListener('click', function(e) {
        // Close modal when clicking on backdrop
        if (e.target === modal) {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
        }
      });
    }
  }

  // Apply fixes to custom modals
  fixCustomModal('shareModal');
  fixCustomModal('videoModal'); 
  fixCustomModal('repostModal');
});

// Alternative method to trigger report modal if data attributes aren't working
function showReportModal(commentId, reportedUserId, recipeId, commentText) {
  document.getElementById('report-comment-id').value = commentId;
  document.getElementById('report-reported-user').value = reportedUserId;
  document.getElementById('report-recipe-id').value = recipeId;
  document.getElementById('report-comment-text').textContent = commentText;
  
  const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
  reportModal.show();
}
</script>































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
<a href="#" class="btn btn-lg bg-orange-500 text-white btn-lg-square back-to-top p-2" id="backToTopBtn">
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
function openEditModal(recipeId) {
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    document.getElementById('confirmEditBtn').href = 'edit_recipe.php?id=' + recipeId;

    // Disable body scroll
    document.body.classList.add('modal-open');

    // Hide Back-to-Top button
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "none";
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    // Re-enable body scroll
    document.body.classList.remove('modal-open');

     // Show Back-to-Top button again
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "flex";
}

function openDeleteModal(recipeId) {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    document.getElementById('confirmDeleteBtn').href = 'archive_recipe.php?id=' + recipeId;

    // Disable body scroll
    document.body.classList.add('modal-open');

     // Hide Back-to-Top button
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "none";
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    // Re-enable body scroll
    document.body.classList.remove('modal-open');

     // Show Back-to-Top button again
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "flex";
}

// ✅ Close modal when clicking outside
document.querySelectorAll('#editModal, #deleteModal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<script>
function openShareModal(recipeId) {
    var modal = document.getElementById("shareModal");
    var linkInput = document.getElementById("shareLink");

    // Generate the recipe link dynamically
    var baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    var recipeLink = baseUrl + "/recipe_details.php?id=" + recipeId;
    linkInput.value = recipeLink;

    // Encode URL and text for sharing
    var encodedLink = encodeURIComponent(recipeLink);   
    var shareText = encodeURIComponent("Check out this recipe!");

    // Update social share URLs
    document.getElementById("facebookShare").href = `https://www.facebook.com/sharer/sharer.php?u=${encodedLink}`;
    document.getElementById("twitterShare").href = `https://twitter.com/intent/tweet?url=${encodedLink}&text=Check out this recipe!`
    ;

 // Disable body scroll
    document.body.classList.add('modal-open');

    // Hide Back-to-Top button
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "none";


    // Show the modal
    modal.style.display = "flex";
}


function closeShareModal() {
    document.getElementById("shareModal").style.display = "none";

    // Re-enable body scroll
    document.body.classList.remove('modal-open');

     // Show Back-to-Top button again
    var backToTop = document.getElementById("backToTopBtn");
    if (backToTop) backToTop.style.display = "flex";
}

function copyShareLink() {
  const linkInput = document.getElementById("shareLink");
  const toast = document.getElementById("toast");

  linkInput.select();
  linkInput.setSelectionRange(0, 99999); // For mobile
  navigator.clipboard.writeText(linkInput.value).then(() => {
    // Show toast
    toast.classList.remove("opacity-0");
    toast.classList.add("opacity-100");

    // Hide after 2s
    setTimeout(() => {
      toast.classList.remove("opacity-100");
      toast.classList.add("opacity-0");
    }, 2000);
  });
}

// Close modals when clicking outside
document.querySelectorAll('#editModal, #deleteModal, #shareModal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) { // Only trigger if the click is on the overlay
            // Close modal
            if(modal.id === 'shareModal') {
                modal.style.display = 'none';
            } else {
                modal.classList.add('hidden');
            }
            // Re-enable scroll
            document.body.classList.remove('modal-open');
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
    function printRecipe() {
    window.print();
}
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

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="js/main.js"></script>
        </div>

        </body>

        </html>





