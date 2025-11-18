<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

require 'db.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipe_name = trim($_POST['recipe_name']);
    $recipe_description = trim($_POST['recipe_description']); 
    $recipe_category = isset($_POST['recipe_category']) ? $_POST['recipe_category'] : '';  
    $recipe_difficulty = isset($_POST['recipe_difficulty']) ? $_POST['recipe_difficulty'] : '';  
    $recipe_preparation = isset($_POST['recipe_preparation']) ? $_POST['recipe_preparation'] : ''; 
    $recipe_cooktime = isset($_POST['recipe_cooktime']) ? $_POST['recipe_cooktime'] : '';
    $recipe_servings = isset($_POST['recipe_servings']) ? $_POST['recipe_servings'] : '';  
    $recipe_budget = isset($_POST['recipe_budget']) ? $_POST['recipe_budget'] : '';   
    $recipe_tags = isset($_POST['recipe_tags']) ? trim($_POST['recipe_tags']) : '';

    // Capture nutritional information
    $recipe_calories = isset($_POST['recipe_calories']) ? $_POST['recipe_calories'] : 0;
    $recipe_fat = isset($_POST['recipe_fat']) ? $_POST['recipe_fat'] : 0;
    $recipe_protein = isset($_POST['recipe_protein']) ? $_POST['recipe_protein'] : 0;
    $recipe_carbohydrates = isset($_POST['recipe_carbohydrates']) ? $_POST['recipe_carbohydrates'] : 0;
    $recipe_fiber = isset($_POST['recipe_fiber']) ? $_POST['recipe_fiber'] : 0;
    $recipe_sugar = isset($_POST['recipe_sugar']) ? $_POST['recipe_sugar'] : 0;
    $recipe_cholesterol = isset($_POST['recipe_cholesterol']) ? $_POST['recipe_cholesterol'] : 0;
    $recipe_sodium = isset($_POST['recipe_sodium']) ? $_POST['recipe_sodium'] : 0;
    
    $user_id = $_SESSION['user_id'];
    $image = "";
    $recipe_video = "";

    // Handle image upload
    if (!empty($_FILES["recipe_image"]["name"])) {
        $target_dir = "uploads/image/";
        $image_name = basename($_FILES["recipe_image"]["name"]);
        $target_file = $target_dir . uniqid() . "_" . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png"];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["recipe_image"]["tmp_name"], $target_file)) {
                $image = $target_file;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            $error = "Invalid image format. Only JPG, JPEG, and PNG are allowed.";
        }
    }

    // ✅ Handle video upload or link
    if (!empty($_FILES["recipe_video_file"]["name"])) {
        // Uploaded file
        $target_dir = "uploads/videos/";
        $video_name = basename($_FILES["recipe_video_file"]["name"]);
        $target_file = $target_dir . uniqid() . "_" . $video_name;
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_video = ["mp4", "mov", "avi"];

        if (in_array($videoFileType, $allowed_video)) {
            if (move_uploaded_file($_FILES["recipe_video_file"]["tmp_name"], $target_file)) {
                $recipe_video = $target_file;
            } else {
                $error = "Error uploading video.";
            }
        } else {
            $error = "Invalid video format. Only MP4, MOV, or AVI.";
        }
    } elseif (!empty($_POST['recipe_video_link'])) {
        // Video link
        $recipe_video = trim($_POST['recipe_video_link']);
        if (!filter_var($recipe_video, FILTER_VALIDATE_URL)) {
            $error = "Invalid video URL.";
            $recipe_video = '';
        }
    }


    // Only proceed if there’s no error
    if (!isset($error)) {
        // Insert into the recipe table
        $stmt = $conn->prepare("INSERT INTO recipe (user_id, recipe_name, recipe_description, image, category, difficulty, preparation, cooktime, servings, budget, video_path, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssssss", $user_id, $recipe_name, $recipe_description, $image, $recipe_category, $recipe_difficulty, $recipe_preparation, $recipe_cooktime, $recipe_servings, $recipe_budget, $recipe_video, $recipe_tags);

        if ($stmt->execute()) {
            $recipe_id = $stmt->insert_id;

            // Insert nutritional information
            $stmt_nutrition = $conn->prepare("INSERT INTO nutritional_info (recipe_id, calories, fat, protein, carbohydrates, fiber, sugar, cholesterol, sodium) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_nutrition->bind_param("iiiiiiiii", $recipe_id, $recipe_calories, $recipe_fat, $recipe_protein, $recipe_carbohydrates, $recipe_fiber, $recipe_sugar, $recipe_cholesterol, $recipe_sodium);
            $stmt_nutrition->execute();

            // Insert ingredients
            if (!empty($_POST['ingredients'])) {
                $stmt_ing = $conn->prepare("INSERT INTO ingredients (recipe_id, ingredient_name) VALUES (?, ?)");
                foreach ($_POST['ingredients'] as $ingredient) {
                    $ingredient = trim($ingredient);
                    if (!empty($ingredient)) {
                        $stmt_ing->bind_param("is", $recipe_id, $ingredient);
                        $stmt_ing->execute();
                    }
                }
            }

            // Insert equipments
            if (!empty($_POST['equipments'])) {
                $stmt_eq = $conn->prepare("INSERT INTO equipments (recipe_id, equipment_name) VALUES (?, ?)");
                foreach ($_POST['equipments'] as $equipment) {
                    $equipment = trim($equipment);
                    if (!empty($equipment)) {
                        $stmt_eq->bind_param("is", $recipe_id, $equipment);
                        $stmt_eq->execute();
                    }
                }
            }

            // Insert instructions
            if (!empty($_POST['instructions'])) {
                $stmt_inst = $conn->prepare("INSERT INTO instructions (recipe_id, instruction_name) VALUES (?, ?)");
                foreach ($_POST['instructions'] as $instruction) {
                    $instruction = trim($instruction);
                    if (!empty($instruction)) {
                        $stmt_inst->bind_param("is", $recipe_id, $instruction);
                        $stmt_inst->execute();
                    }
                }
            }

            $success = "Recipe added successfully! It is now pending approval.";
        } else {
            $error = "Error adding recipe. Try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Submit Your Recipe</title>
        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
<!-- Cropper.js CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">

<!-- Cropper.js JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

  <!-- Font Awesome (Bootstrap Icons Alternative) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
<style>

    .font-nunito { font-family: 'Nunito', sans-serif; }
    [x-cloak] { display: none !important; }
</style>

<style type="text/tailwindcss">
        :root {
            --bg-color: #FEF3C7;
            --primary-text: #4A5568;
            --accent-color: #F59E0B;
            --accent-hover: #D97706;
            --form-bg: #FFFFFF;
            --form-border: #E5E7EB;
            --form-focus: #F59E0B;
            --secondary-text: #6B7280;
            --danger-bg: #FEE2E2;
            --danger-text: #EF4444;
            --danger-hover: #FECACA;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--primary-text);
        }
        .container {
            max-width: 1200px;
            margin-top: 50px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
    </style>
</head>

<body class="bg-[{var--bg-color}] h-[200vh]">
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
        <div class="mx-auto px-3 lg:px-5">
            <div class="flex items-center justify-between py-3 gap-3">
                
                <a href="dashboard.php" style="text-decoration:none;"><!-- Logo -->
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
                    <!-- Favorites -->
                    <a class="flex items-center
                    text-orange-400 transition-colors" 
                       href="favorites.php">
                        <span class="material-icons mr-3 text-orange-400 hover:text-orange-500">bookmark</span>
                    </a>

                        <!-- Profile Dropdown -->
                        <div class="relative" x-on:click.away="if (openDropdown === 'profile') openDropdown = ''">
                            <button @click="openDropdown = openDropdown === 'profile' ? '' : 'profile'" 
                                    class="transition-colors">
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

<!-- JS for toggle -->
<script>
  const profileBtn = document.getElementById("profileMenuBtn");
  const profileMenu = document.getElementById("profileMenu");

  profileBtn.addEventListener("click", function(e) {
    e.stopPropagation(); // prevent window click from firing
    profileMenu.classList.toggle("hidden");
  });

  // Close dropdown if clicked outside
  window.addEventListener("click", function(e) {
    if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
      profileMenu.classList.add("hidden");
    }
  });
</script>

<div class="mx-auto px-4 py-20">
<div class="max-w-5xl mx-auto bg-[var(--form-bg)] p-8 rounded-2xl shadow-2xl">
<h1 class="text-4xl font-bold text-center mb-10 text-[var(--accent-color)]">Submit Your Recipe</h1>
    <div class="submit-recipe-container mt-5">
    <?php if (isset($success)) echo "<p class='bg-green-100 text-green-800 px-4 py-3 rounded mb-4'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='bg-red-100 text-red-800 px-4 py-3 rounded mb-4'>$error</p>"; ?>
</div>

<form action="" method="POST" enctype="multipart/form-data">
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
<div class="space-y-6">
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_name">Recipe Name</label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_name" name="recipe_name" placeholder="e.g., Delicious Chocolate Cake" required="" type="text"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_tags">Tags</label>
<div class="flex flex-wrap items-center gap-2 p-2 border border-[var(--form-border)] rounded-lg w-full max-w-full box-border" id="tags-container">
<input class="w-full sm:flex-grow sm:w-auto px-2 py-1 text-sm rounded focus:outline-none focus:ring-orange-400 focus:border-orange-400 box-border" id="tags-input" placeholder="e.g., vegan, spicy, gluten-free" type="text"/>
</div>
<input id="hidden-tags" name="recipe_tags" type="hidden"/>
<p class="text-xs text-[var(--secondary-text)] mt-1">Separate tags with a comma. Click tag to remove.</p>
</div>
<div>
  <label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_image">Recipe Image</label>
  <div class="flex items-center justify-center w-full">
    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-[var(--form-border)] border-dashed rounded-lg cursor-pointer bg-amber-50 hover:bg-amber-100" for="recipe_image">
      <div class="flex flex-col items-center justify-center pt-5 pb-6">
        <span class="material-icons text-[var(--accent-color)] mb-2">cloud_upload</span>
        <p class="mb-2 text-sm text-[var(--secondary-text)]"><span class="font-semibold text-[var(--accent-color)]">Click to upload</span></p>
        <p class="text-xs text-[var(--secondary-text)]">(JPG, JPEG, PNG)</p>
      </div>
      <input class="hidden" id="recipe_image" name="recipe_image" required type="file" accept=".jpg,.jpeg,.png" />
    </label>
  </div>

  <!-- Error Message -->
  <p id="image-error" class="text-red-500 text-xs mt-2 hidden">Invalid file type. Please upload an image (JPG, JPEG or PNG).</p>

  <!-- Image Cropper Modal -->
  <div id="cropper-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-4 rounded-xl shadow-xl w-[90%] max-w-lg">
      <h3 class="text-lg font-semibold mb-3">Adjust Image</h3>
      <div class="w-full h-64">
        <img id="cropper-image" class="max-h-64 mx-auto" />
      </div>
      <div class="flex justify-end gap-3 mt-4">
        <button id="cancel-crop" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button id="confirm-crop" class="px-4 py-2 bg-[var(--accent-color)] text-white rounded-lg hover:opacity-90">Crop & Save</button>
      </div>
    </div>
  </div>

  <!-- Cropped Image Preview -->
  <div id="image-preview-box" class="mt-4 hidden">
    <h3 class="text-sm font-semibold mb-2">Image Preview</h3>
    <img id="image-preview" class="w-full max-w-md rounded-lg border object-cover" />
  </div>
</div>

<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_description">Recipe Description</label>
<textarea class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_description" maxlength="500" minlength="60" name="recipe_description" placeholder="Share a little bit about your recipe..." required="" rows="4"></textarea>
</div>
<!-- Video Upload or Link -->
<div class="mt-4">
  <label class="block text-sm font-medium text-gray-700 mb-2">Video Tutorial (Optional)</label>

  <!-- Toggle Option -->
  <div class="flex gap-4 mb-3">
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="radio" name="video_type" value="link" checked class="cursor-pointer"> Link
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="radio" name="video_type" value="upload" class="cursor-pointer"> Upload
    </label>
  </div>

  <!-- Video Link Input -->
  <input id="recipe_video_link" name="recipe_video_link" type="url"
         class="video-input w-full border rounded-lg px-4 py-3 focus:ring-orange-400 focus:border-orange-400"
         placeholder="Paste YouTube or Google Drive link" />

  <!-- File Upload Input (hidden by default) -->
  <input id="recipe_video_file" name="recipe_video_file" type="file" 
         accept="video/mp4,video/mov,video/avi"
         class="video-input w-full border rounded-lg px-4 py-3 hidden" />

  <!-- Error Message -->
  <p id="video-error" class="text-red-500 text-xs mt-2 hidden"></p>

  <!-- Video Preview -->
  <div id="video-preview-box" class="mt-3 hidden">
    <h3 class="text-sm font-semibold mb-2">Video Preview</h3>
    <div id="video-preview-container" class="w-full rounded-lg border overflow-hidden"></div>
  </div>
</div>

<script>
const radioButtons = document.querySelectorAll('input[name="video_type"]');
const videoLink = document.getElementById('recipe_video_link');
const videoFile = document.getElementById('recipe_video_file');
const videoPreviewBox = document.getElementById('video-preview-box');
const videoPreviewContainer = document.getElementById('video-preview-container');
const videoError = document.getElementById('video-error');

// Function to clear inputs and preview
function clearVideoInputs() {
  videoLink.value = '';
  videoFile.value = '';
  videoPreviewContainer.innerHTML = '';
  videoPreviewBox.classList.add('hidden');
  videoError.classList.add('hidden');
}

// Toggle between link and upload
radioButtons.forEach(radio => {
  radio.addEventListener('change', () => {
    clearVideoInputs();
    if (radio.value === 'link') {
      videoLink.classList.remove('hidden');
      videoFile.classList.add('hidden');
      videoLink.removeAttribute('disabled');
      videoFile.setAttribute('disabled', 'disabled');
    } else {
      videoLink.classList.add('hidden');
      videoFile.classList.remove('hidden');
      videoFile.removeAttribute('disabled');
      videoLink.setAttribute('disabled', 'disabled');
    }
  });
});

// Handle video link preview
videoLink.addEventListener('input', function () {
  const url = this.value.trim();
  videoPreviewContainer.innerHTML = '';
  videoError.classList.add('hidden');

  if (!url) {
    videoPreviewBox.classList.add('hidden');
    return;
  }

  let embedHTML = '';
  
  // YouTube
  if (url.includes('youtube.com') || url.includes('youtu.be')) {
    const videoId = url.includes('watch?v=') 
      ? url.split('watch?v=')[1].split('&')[0] 
      : url.split('youtu.be/')[1]?.split('?')[0];
    
    if (videoId) {
      embedHTML = `<iframe width="100%" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
    }
  }
  // Google Drive
  else if (url.includes('drive.google.com')) {
    let fileId = '';
    if (url.includes('/file/d/')) {
      fileId = url.split('/file/d/')[1].split('/')[0];
    } else if (url.includes('id=')) {
      fileId = url.split('id=')[1].split('&')[0];
    }
    
    if (fileId) {
      embedHTML = `<iframe src="https://drive.google.com/file/d/${fileId}/preview" width="100%" height="315" allow="autoplay" allowfullscreen></iframe>`;
    }
  }

  if (embedHTML) {
    videoPreviewContainer.innerHTML = embedHTML;
    videoPreviewBox.classList.remove('hidden');
  } else {
    videoError.textContent = 'Invalid video URL. Please use YouTube or Google Drive links.';
    videoError.classList.remove('hidden');
    videoPreviewBox.classList.add('hidden');
  }
});

// Handle video file preview with validation
videoFile.addEventListener('change', function() {
    const file = this.files[0];
    videoPreviewContainer.innerHTML = '';
    videoError.classList.add('hidden');

    if (!file) {
        videoPreviewBox.classList.add('hidden');
        return;
    }

    // Validate file type
    const validTypes = ['video/mp4', 'video/mov', 'video/avi'];
    if (!validTypes.includes(file.type)) {
        videoError.textContent = 'Invalid video format. Only MP4, MOV, or AVI.';
        videoError.classList.remove('hidden');
        this.value = '';
        return;
    }

    // Validate file size (max 50MB)
    const maxSize = 50 * 1024 * 1024; // 100MB in bytes
    if (file.size > maxSize) {
        videoError.textContent = 'Video file is too large. Maximum size is 50MB.';
        videoError.classList.remove('hidden');
        this.value = '';
        return;
    }

    // Create preview
    const url = URL.createObjectURL(file);
    videoPreviewBox.classList.remove('hidden');

    const videoTag = document.createElement('video');
    videoTag.src = url;
    videoTag.controls = true;
    videoTag.classList.add('rounded-lg', 'w-full');
    videoTag.style.maxHeight = '315px';

    videoPreviewContainer.appendChild(videoTag);

    // Clean up the object URL when video is loaded
    videoTag.addEventListener('loadeddata', function() {
        URL.revokeObjectURL(url);
    });
});

// Initialize: ensure file input is disabled on page load
videoFile.setAttribute('disabled', 'disabled');
</script>
<div class="space-y-6">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_category">Recipe Category</label>
<select class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_category" name="recipe_category" required="">
<option value="">Category</option>
                                    <option value="Main Dish">Main Dish</option>
                                    <option value="Appetizers & Snacks">Appetizers & Snacks</option>
                                    <option value="Soups & Stews">Soups & Stews</option>
                                    <option value="Salads & Sides">Salads & Sides</option>
                                    <option value="Brunch">Brunch</option>
                                    <option value="Desserts & Sweets">Desserts & Sweets</option>
                                    <option value="Drinks & Beverages">Drinks & Beverages</option>
                                    <option value="Vegetables">Vegetables</option>
                                    <option value="Occasional">Occasional</option>
                                    <option value="Healthy & Special Diets">Healthy & Special Diets</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_preparation">Preparation Type</label>
<select class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_preparation" name="recipe_preparation" required="">
<option value="">Preparation Type</option>
                            <option value="Raw">Raw</option>
                            <option value="Boiling">Boiling</option>
                            <option value="Steaming">Steaming</option>
                            <option value="Blanching">Blanching</option>
                            <option value="Simmering">Simmering</option>
                            <option value="Sauteing">Sauteing</option>
                            <option value="Frying">Frying</option>
                            <option value="Grilling">Grilling</option>
                            <option value="Roasting">Roasting</option>
                            <option value="Baking">Baking</option>
                            <option value="Broiling">Broiling</option>
                            <option value="Poaching">Poaching</option>
                            <option value="Braising">Braising</option>
                            <option value="Stewing">Stewing</option>
                            <option value="Smoking">Smoking</option>
                            <option value="Fermenting">Fermenting</option>
                            <option value="Pickling">Pickling</option>
                            <option value="Marinating">Marinating</option>
                            <!-- Drink Prep Types -->
                            <option value="Blending">Blending</option>
                            <option value="Shaking">Shaking</option>
                            <option value="Stirring">Stirring</option>
                            <option value="Juicing">Juicing</option>
                            <option value="Brewing">Brewing</option>
                            <option value="Infusing">Infusing</option>
                            <option value="Chilling">Chilling</option>
                            <option value="Carbonating">Carbonating</option>
                            <option value="Muddling">Muddling</option>
                            <option value="Pouring Over Ice">Pouring Over Ice</option>
</select>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_cooktime">Cooking Time</label>
<select class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_cooktime" name="recipe_cooktime" required="">
<option value="">Select Cooking Time</option>
                            <option value="1 to 5 minutes">1-5 minutes</option>
                            <option value="5 to 15 minutes">5-15 minutes</option>
                            <option value="15 to 30 minutes">15-30 minutes</option>
                            <option value="30 to 60 minutes">30-60 minutes</option>
                            <option value="1 to 3 hours">1-3 hours</option>
                            <option value="Above 3 hours">Above 3 hours</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_difficulty">Difficulty Level</label>
<select class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_difficulty" name="recipe_difficulty" required="">
<option value="">Select Difficulty</option>
                            <option value="Easy">Easy</option>
                            <option value="Medium">Medium</option>
                            <option value="Hard">Hard</option>
                            <option value="Extreme">Extreme</option>
</select>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_servings">Servings</label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_servings" min="1" name="recipe_servings" placeholder="e.g., 4" required="" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_budget">Budget Level</label>
<select class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_budget" name="recipe_budget" required="">
<option value="">Select Budget</option>
                            <option value="Ultra Low Budget (₱0 - ₱100)">Ultra Low Budget (₱0 - ₱100)</option>
                            <option value="Low Budget (₱101 - ₱250)">Low Budget (₱101 - ₱250)</option>
                            <option value="Mid Budget (₱251 - ₱500)">Mid Budget (₱251 - ₱500)</option>
                            <option value="High Budget (₱501 - ₱1,000)">High Budget (₱501 - ₱1,000)</option>
                            <option value="Luxury Budget (₱1,001 above )">Luxury Budget (₱1,001 above)</option>
</select>
</div>
</div>
</div>
</div>
<div class="space-y-6">
<h3 class="text-lg font-semibold text-gray-900 mb-4">Nutritional Information</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_calories">Calories (kcal)</label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_calories" min="0" name="recipe_calories" placeholder="e.g., 250" type="number" required/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_fat">Fat (g) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_fat" min="0" name="recipe_fat" placeholder="e.g., 15" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_protein">Protein (g) <span class="text-xs text-[var(--secondary-text)]"></span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_protein" min="0" name="recipe_protein" placeholder="e.g., 10" type="number" required/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_carbohydrates">Carbohydrates (g) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_carbohydrates" min="0" name="recipe_carbohydrates" placeholder="e.g., 30" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_fiber">Fiber (g) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_fiber" min="0" name="recipe_fiber" placeholder="e.g., 5" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_sugar">Sugar (g) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_sugar" min="0" name="recipe_sugar" placeholder="e.g., 20" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_cholesterol">Cholesterol (mg) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_cholesterol" min="0" name="recipe_cholesterol" placeholder="e.g., 70" type="number"/>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-1" for="recipe_sodium">Sodium (mg) <span class="text-xs text-[var(--secondary-text)]">(optional)</span></label>
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" id="recipe_sodium" min="0" name="recipe_sodium" placeholder="e.g., 300" type="number"/>
</div>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-2">Ingredients</label>
<div class="space-y-3" id="ingredients-container">
<div class="flex items-center space-x-2">
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" name="ingredients[]" placeholder="e.g., 2 cups flour" required="" type="text"/>
<button class="p-2 bg-[var(--danger-bg)] text-[var(--danger-text)] rounded-full hover:bg-[var(--danger-hover)]" type="button">
<span class="material-icons">delete</span>
</button>
</div>
</div>
<button class="mt-3 flex items-center text-[var(--accent-color)] font-semibold hover:text-[var(--accent-hover)]" id="add-ingredient" type="button">
<span class="material-icons mr-1">add_circle_outline</span> Add Ingredient
</button>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-2">Equipments</label>
<div class="space-y-3" id="equipments-container">
<div class="flex items-center space-x-2">
<input class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" name="equipments[]" placeholder="e.g., Mixing bowl" required="" type="text"/>
<button class="p-2 bg-[var(--danger-bg)] text-[var(--danger-text)] rounded-full hover:bg-[var(--danger-hover)]" type="button">
<span class="material-icons">delete</span>
</button>
</div>
</div>
<button class="mt-3 flex items-center text-[var(--accent-color)] font-semibold hover:text-[var(--accent-hover)]" id="add-equipment" type="button">
<span class="material-icons mr-1">add_circle_outline</span> Add Equipment
</button>
</div>
<div>
<label class="block text-sm font-medium text-[var(--primary-text)] mb-2">Instructions</label>
<div class="space-y-3" id="instructions-container">
<div class="flex items-start space-x-2">
<span class="mt-3 text-[var(--secondary-text)] font-semibold">1.</span>
<textarea class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" maxlength="250" name="instructions[]" placeholder="e.g., Mix flour and sugar..." required="" rows="2"></textarea>
<button class="mt-2 p-2 bg-[var(--danger-bg)] text-[var(--danger-text)] rounded-full hover:bg-[var(--danger-hover)]" type="button">
<span class="material-icons">delete</span>
</button>
</div>
</div>
<button class="mt-3 flex items-center text-[var(--accent-color)] font-semibold hover:text-[var(--accent-hover)]" id="add-instruction" type="button">
<span class="material-icons mr-1">add_circle_outline</span> Add Step
</button>
</div>
</div>
</div>
<div class="mt-12 flex justify-end">
<button class="w-full md:w-auto bg-[var(--accent-color)] hover:bg-[var(--accent-hover)] text-white font-bold py-4 px-10 rounded-lg shadow-lg transition duration-300 ease-in-out transform hover:scale-105" type="submit">
                        Submit Recipe
                    </button>
</div>
</form>
</div>
</div>

<script>
  let cropper;

  const fileInput = document.getElementById('recipe_image');
  const cropperModal = document.getElementById('cropper-modal');
  const cropperImage = document.getElementById('cropper-image');
  const previewBox = document.getElementById('image-preview-box');
  const previewImage = document.getElementById('image-preview');
  const errorMessage = document.getElementById('image-error');
  const cancelCropBtn = document.getElementById('cancel-crop');
  const confirmCropBtn = document.getElementById('confirm-crop');

  fileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
      errorMessage.classList.remove('hidden');
      previewBox.classList.add('hidden');
      this.value = '';
      return;
    }

    errorMessage.classList.add('hidden');
    const reader = new FileReader();
    reader.onload = function (e) {
      cropperImage.src = e.target.result;
      cropperModal.classList.remove('hidden');

      if (cropper) cropper.destroy();

      cropper = new Cropper(cropperImage, {
        aspectRatio: 16 / 9, // ✅ Slightly taller ratio for better balance
        viewMode: 1,
        dragMode: 'move',
        background: false,
        autoCropArea: 1,
        minCropBoxWidth: 800,
        minCropBoxHeight: 450, // ✅ Increased height
      });
    };
    reader.readAsDataURL(file);
  });

  cancelCropBtn.addEventListener('click', () => {
    cropperModal.classList.add('hidden');
    fileInput.value = ''; 
    if (cropper) cropper.destroy();
  });

  confirmCropBtn.addEventListener('click', () => {
    const canvas = cropper.getCroppedCanvas({
      width: 1280,  // ✅ Wider crop output
      height: 720,  // ✅ Taller crop output
    });

    canvas.toBlob((blob) => {
      const newFile = new File([blob], "cropped_image.jpg", { type: "image/jpeg" });
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(newFile);
      fileInput.files = dataTransfer.files;

      // Show preview
      previewImage.src = canvas.toDataURL();
      previewBox.classList.remove('hidden');
      cropperModal.classList.add('hidden');
      cropper.destroy();
    }, "image/jpeg", 0.9);
  });
</script>

<script>
        document.addEventListener('DOMContentLoaded', function () {
            function addInput(containerId, placeholder, type = 'input', name) {
                const container = document.getElementById(containerId);
                const newRow = document.createElement('div');
                newRow.className = 'flex items-center space-x-2';
                if (type === 'textarea') {
                    newRow.className = 'flex items-start space-x-2';
                    const stepNumber = container.children.length + 1;
                    newRow.innerHTML = `
                        <span class="mt-3 text-[var(--secondary-text)] font-semibold">${stepNumber}.</span>
                        <textarea name="${name}" rows="2" class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" placeholder="${placeholder}" required maxlength="250"></textarea>
                        <button type="button" class="mt-2 p-2 bg-[var(--danger-bg)] text-[var(--danger-text)] rounded-full hover:bg-[var(--danger-hover)] remove-btn">
                            <span class="material-icons">delete</span>
                        </button>
                    `;
                } else {
                     newRow.innerHTML = `
                        <input type="text" name="${name}" class="w-full px-4 py-3 border border-[var(--form-border)] rounded-lg focus:ring-[var(--form-focus)] focus:border-[var(--form-focus)]" placeholder="${placeholder}" required>
                        <button type="button" class="p-2 bg-[var(--danger-bg)] text-[var(--danger-text)] rounded-full hover:bg-[var(--danger-hover)] remove-btn">
                            <span class="material-icons">delete</span>
                        </button>
                    `;
                }
                container.appendChild(newRow);
                newRow.querySelector('.remove-btn').addEventListener('click', () => {
                    newRow.remove();
                    if (type === 'textarea') {
                        updateStepNumbers();
                    }
                });
            }
            function updateStepNumbers() {
                const container = document.getElementById('instructions-container');
                const steps = container.children;
                for (let i = 0; i < steps.length; i++) {
                    steps[i].querySelector('span.font-semibold').textContent = `${i + 1}.`;
                }
            }
            document.getElementById('add-ingredient').addEventListener('click', () => addInput('ingredients-container', 'e.g., 1 tsp vanilla extract', 'input', 'ingredients[]'));
            document.getElementById('add-equipment').addEventListener('click', () => addInput('equipments-container', 'e.g., Whisk', 'input', 'equipments[]'));
            document.getElementById('add-instruction').addEventListener('click', () => addInput('instructions-container', 'Add another step...', 'textarea', 'instructions[]'));
            document.querySelectorAll('.remove-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const rowToRemove = e.currentTarget.closest('.flex');
                    const container = rowToRemove.parentElement;
                    rowToRemove.remove();
                    if (container.id === 'instructions-container') {
                        updateStepNumbers();
                    }
                });
            });
            const tagsInput = document.getElementById('tags-input');
            const tagsContainer = document.getElementById('tags-container');
            const hiddenTags = document.getElementById('hidden-tags');
            let recipe_tags = [];
            function updateHiddenTags() {
                hiddenTags.value = recipe_tags.join(',');
            }
            function createTag(label) {
                if (recipe_tags.includes(label)) return;
                recipe_tags.push(label);
                const div = document.createElement('div');
                div.setAttribute('class', 'flex items-center gap-1 bg-amber-200 text-amber-800 text-sm font-medium px-2 py-1 rounded-full');
                div.innerHTML = `
                    <span>${label}</span>
                    <span class="material-icons text-sm cursor-pointer">cancel</span>
                `;
                tagsContainer.insertBefore(div, tagsInput);
                div.querySelector('span:last-child').addEventListener('click', () => {
                    recipe_tags = recipe_tags.filter(t => t !== label);
                    div.remove();
                    updateHiddenTags();
                });
                updateHiddenTags();
            }
            function parseTags(input) {
                return input.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
            }
            tagsInput.addEventListener('input', function(e) {
                if (e.target.value.includes(',')) {
                    const newTags = parseTags(e.target.value);
                    newTags.forEach(tag => createTag(tag));
                    e.target.value = '';
                }
            });
            tagsInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.value.trim() !== '') {
                    e.preventDefault();
                    const newTags = parseTags(e.target.value);
                    newTags.forEach(tag => createTag(tag));
                    e.target.value = '';
                }
            });
             tagsInput.addEventListener('blur', function(e) {
                 if (e.target.value.trim() !== '') {
                    const newTags = parseTags(e.target.value);
                    newTags.forEach(tag => createTag(tag));
                    e.target.value = '';
                 }
            });
        });

    // Image preview functionality
    document.getElementById('recipe_image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const previewBox = document.getElementById('image-preview-box');
        const previewImg = document.getElementById('image-preview');

        if (file && file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewBox.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewBox.classList.add('hidden');
            previewImg.src = "";
        }
    });

    </script>
 <script>
    const menuBtn = document.getElementById('menu-toggle');
    const menu = document.getElementById('menu');
    const profileBtn = document.getElementById('profileMenuBtn');
    const profileMenu = document.getElementById('profileMenu');

    // Mobile menu toggle
    menuBtn.addEventListener('click', () => {
      menu.classList.toggle('hidden');
    });

    // Profile dropdown toggle
    profileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle('hidden');
    });

    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
      if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
        profileMenu.classList.add('hidden');
      }
    });
  </script>


<script>
document.addEventListener('DOMContentLoaded', function() {
  // Warn user before leaving or refreshing the page if form is not yet submitted
  let formChanged = false;
  let formSubmitted = false;

  // Get the form
  const recipeForm = document.querySelector('form[action=""][method="POST"]');
  
  if (recipeForm) {
    // Listen to input events (typing in text fields, textareas)
    recipeForm.addEventListener('input', function(e) {
      console.log('Input detected:', e.target); // Debug log
      formChanged = true;
    });
    
    // Listen to change events (select dropdowns, file inputs)
    recipeForm.addEventListener('change', function(e) {
      console.log('Change detected:', e.target); // Debug log
      formChanged = true;
    });

    // When the form is submitted, disable the warning
    recipeForm.addEventListener('submit', function() {
      console.log('Form submitted'); // Debug log
      formChanged = false;
      formSubmitted = true;
    });
  }

  // Ask confirmation when user tries to refresh or leave the page
  window.addEventListener('beforeunload', function (e) {
    console.log('Before unload - formChanged:', formChanged, 'formSubmitted:', formSubmitted); // Debug log
    if (formChanged && !formSubmitted) {
      const message = 'You have unsaved changes. Are you sure you want to leave?';
      e.preventDefault();
      e.returnValue = message; // For most browsers
      return message; // For some older browsers
    }
  });

  console.log('Form change detection initialized'); // Debug log
});
</script>

</body></html>