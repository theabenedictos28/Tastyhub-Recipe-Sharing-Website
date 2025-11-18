<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Recipe Feed Personalization</title>
        <link href="img/favicon.png" rel="icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .tag {
            transition: all 0.2s ease-in-out;
        }
        .tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .tag.selected {
            background-color: #FEA116;
            color: white;
            border-color: #FEA116;
        }
        input[type="radio"],
        input[type="checkbox"] {
            display: none;
        }
        input:checked + label {
            background-color: #FEA116;
            color: white;
            border-color: #FEA116;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="container mx-auto px-4 py-8">
    <header class="flex justify-between items-center mb-8">
        <div></div>
        <button type="button" onclick="window.location.href='dashboard.php'" class="text-green-600 font-semibold hover:text-green-800 transition-colors">Skip</button>
    </header>
    
    <main class="max-w-5xl mx-auto">
        <h1 class="text-4xl font-bold text-center mb-10 text-gray-900">Personalize Your Recipe Feed</h1>
        
        <form method="POST" action="save_preference.php" class="space-y-12">
            <!-- Category -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Category</h2>
                <div class="flex flex-wrap gap-3">
                    <input type="checkbox" id="main" name="category[]" value="Main Dish">
                    <label for="main" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Main Dish</label>
                    
                    <input type="checkbox" id="appetizers" name="category[]" value="Appetizers & Snacks">
                    <label for="appetizers" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Appetizers & Snacks</label>
                    
                    <input type="checkbox" id="soups" name="category[]" value="Soups & Stews">
                    <label for="soups" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Soups & Stews</label>
                    
                    <input type="checkbox" id="salads" name="category[]" value="Salads & Sides">
                    <label for="salads" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Salads & Sides</label>
                    
                    <input type="checkbox" id="brunch" name="category[]" value="Brunch">
                    <label for="brunch" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Brunch</label>
                    
                    <input type="checkbox" id="desserts" name="category[]" value="Desserts & Sweets">
                    <label for="desserts" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Desserts & Sweets</label>
                    
                    <input type="checkbox" id="drinks" name="category[]" value="Drinks & Beverages">
                    <label for="drinks" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Drinks & Beverages</label>
                    
                    <input type="checkbox" id="vegetables" name="category[]" value="Vegetables">
                    <label for="vegetables" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Vegetables</label>
                    
                    <input type="checkbox" id="occasional" name="category[]" value="Occasional">
                    <label for="occasional" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Occasional</label>
                    
                    <input type="checkbox" id="special" name="category[]" value="Healthy & Special Diets">
                    <label for="special" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Healthy & Special Diets</label>
                </div>
            </div>

            <!-- Ingredients -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Ingredients</h2>
                <div class="flex flex-wrap gap-3">
                    <input type="checkbox" id="chicken" name="ingredients[]" value="Chicken">
                    <label for="chicken" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Chicken</label>
                    
                    <input type="checkbox" id="beef" name="ingredients[]" value="Beef">
                    <label for="beef" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Beef</label>
                    
                    <input type="checkbox" id="pork" name="ingredients[]" value="Pork">
                    <label for="pork" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pork</label>
                    
                    <input type="checkbox" id="fish" name="ingredients[]" value="Fish">
                    <label for="fish" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Fish</label>
                    
                    <input type="checkbox" id="shrimp" name="ingredients[]" value="Shrimp">
                    <label for="shrimp" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Shrimp</label>
                    
                    <input type="checkbox" id="egg" name="ingredients[]" value="Egg">
                    <label for="egg" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Egg</label>
                    
                    <input type="checkbox" id="cheese" name="ingredients[]" value="Cheese">
                    <label for="cheese" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Cheese</label>
                    
                    <input type="checkbox" id="milk" name="ingredients[]" value="Milk">
                    <label for="milk" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Milk</label>
                    
                    <input type="checkbox" id="tofu" name="ingredients[]" value="Tofu">
                    <label for="tofu" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Tofu</label>
                    
                    <input type="checkbox" id="mushroom" name="ingredients[]" value="">
                    <label for="mushroom" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Mushroom</label>
                    
                    <input type="checkbox" id="rice" name="ingredients[]" value="Rice">
                    <label for="rice" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Rice</label>
                    
                    <input type="checkbox" id="pasta" name="ingredients[]" value="Pasta">
                    <label for="pasta" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pasta</label>
                    
                    <input type="checkbox" id="beans" name="ingredients[]" value="Beans">
                    <label for="beans" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Beans</label>
                    
                    <input type="checkbox" id="potato" name="ingredients[]" value="Potato">
                    <label for="potato" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Potato</label>
                    
                    <input type="checkbox" id="tomato" name="ingredients[]" value="Tomato">
                    <label for="tomato" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Tomato</label>
                    
                    <input type="checkbox" id="onion" name="ingredients[]" value="Onion">
                    <label for="onion" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Onion</label>
                    
                    <input type="checkbox" id="garlic" name="ingredients[]" value="Garlic">
                    <label for="garlic" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Garlic</label>
                    
                    <input type="checkbox" id="salt" name="ingredients[]" value="Salt">
                    <label for="salt" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Salt</label>
                    
                    <input type="checkbox" id="pepper" name="ingredients[]" value="Pepper">
                    <label for="pepper" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pepper</label>
                    
                    <input type="checkbox" id="oliveoil" name="ingredients[]" value="Olive Oil">
                    <label for="oliveoil" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Olive Oil</label>
                    
                    <input type="checkbox" id="butter" name="ingredients[]" value="Butter">
                    <label for="butter" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Butter</label>
                    
                    <input type="checkbox" id="sugar" name="ingredients[]" value="Sugar">
                    <label for="sugar" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Sugar</label>
                    
                    <input type="checkbox" id="flour" name="ingredients[]" value="Flour">
                    <label for="flour" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">All-purpose flour</label>
                </div>
            </div>

            <!-- Preparation Type -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Preparation Type</h2>
                <div class="flex flex-wrap gap-3">
                    <input type="checkbox" id="raw" name="preparation_type[]" value="Raw">
                    <label for="raw" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Raw</label>
                    
                    <input type="checkbox" id="boiling" name="preparation_type[]" value="Boiling">
                    <label for="boiling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Boiling</label>
                    
                    <input type="checkbox" id="steaming" name="preparation_type[]" value="Steaming">
                    <label for="steaming" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Steaming</label>
                    
                    <input type="checkbox" id="blanching" name="preparation_type[]" value="Blanching">
                    <label for="blanching" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Blanching</label>
                    
                    <input type="checkbox" id="simmering" name="preparation_type[]" value="Simmering">
                    <label for="simmering" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Simmering</label>
                    
                    <input type="checkbox" id="sauteing" name="preparation_type[]" value="Sauteing">
                    <label for="sauteing" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Sauteing</label>
                    
                    <input type="checkbox" id="frying" name="preparation_type[]" value="Frying">
                    <label for="frying" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Frying</label>
                    
                    <input type="checkbox" id="grilling" name="preparation_type[]" value="Grilling">
                    <label for="grilling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Grilling</label>
                    
                    <input type="checkbox" id="roasting" name="preparation_type[]" value="Roasting">
                    <label for="roasting" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Roasting</label>
                    
                    <input type="checkbox" id="baking" name="preparation_type[]" value="Baking">
                    <label for="baking" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Baking</label>
                    
                    <input type="checkbox" id="broiling" name="preparation_type[]" value="Broiling">
                    <label for="broiling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Broiling</label>
                    
                    <input type="checkbox" id="poaching" name="preparation_type[]" value="Poaching">
                    <label for="poaching" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Poaching</label>
                    
                    <input type="checkbox" id="braising" name="preparation_type[]" value="Braising">
                    <label for="braising" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Braising</label>
                    
                    <input type="checkbox" id="stewing" name="preparation_type[]" value="Stewing">
                    <label for="stewing" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Stewing</label>
                    
                    <input type="checkbox" id="smoking" name="preparation_type[]" value="Smoking">
                    <label for="smoking" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Smoking</label>
                    
                    <input type="checkbox" id="fermenting" name="preparation_type[]" value="Fermenting">
                    <label for="fermenting" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Fermenting</label>
                    
                    <input type="checkbox" id="pickling" name="preparation_type[]" value="Pickling">
                    <label for="pickling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pickling</label>
                    
                    <input type="checkbox" id="marinating" name="preparation_type[]" value="Marinating">
                    <label for="marinating" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Marinating</label>
                    
                    <input type="checkbox" id="blending" name="preparation_type[]" value="Blending">
                    <label for="blending" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Blending</label>
                    
                    <input type="checkbox" id="shaking" name="preparation_type[]" value="Shaking">
                    <label for="shaking" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Shaking</label>
                    
                    <input type="checkbox" id="stirring" name="preparation_type[]" value="Stirring">
                    <label for="stirring" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Stirring</label>
                    
                    <input type="checkbox" id="juicing" name="preparation_type[]" value="Juicing">
                    <label for="juicing" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Juicing</label>
                    
                    <input type="checkbox" id="brewing" name="preparation_type[]" value="Brewing">
                    <label for="brewing" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Brewing</label>
                    
                    <input type="checkbox" id="infusing" name="preparation_type[]" value="Infusing">
                    <label for="infusing" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Infusing</label>
                    
                    <input type="checkbox" id="chilling" name="preparation_type[]" value="Chilling">
                    <label for="chilling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Chilling</label>
                    
                    <input type="checkbox" id="carbonating" name="preparation_type[]" value="Carbonating">
                    <label for="carbonating" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Carbonating</label>
                    
                    <input type="checkbox" id="muddling" name="preparation_type[]" value="Muddling">
                    <label for="muddling" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Muddling</label>
                    
                    <input type="checkbox" id="pouringice" name="preparation_type[]" value="Pouring Over Ice">
                    <label for="pouringice" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pouring Over ice</label>
                </div>
            </div>

            <!-- Difficulty Level -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Difficulty Level</h2>
                <div class="flex flex-wrap gap-3">
                    <input type="radio" id="diff_easy" name="difflevel" value="Easy">
                    <label for="diff_easy" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Easy</label>
                    
                    <input type="radio" id="diff_medium" name="difflevel" value="Medium">
                    <label for="diff_medium" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Medium</label>
                    
                    <input type="radio" id="diff_hard" name="difflevel" value="Hard">
                    <label for="diff_hard" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Hard</label>
                    
                    <input type="radio" id="diff_extreme" name="difflevel" value="Extreme">
                    <label for="diff_extreme" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Extreme</label>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Tags</h2>
                <div class="flex flex-wrap gap-3">
                    <input type="checkbox" id="vegan" name="tags[]" value="Vegan">
                    <label for="vegan" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Vegan</label>
                    
                    <input type="checkbox" id="vegetarian" name="tags[]" value="Vegetarian">
                    <label for="vegetarian" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Vegetarian</label>
                    
                    <input type="checkbox" id="glutenfree" name="tags[]" value="GlutenFree">
                    <label for="glutenfree" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#GlutenFree</label>
                    
                    <input type="checkbox" id="dairyfree" name="tags[]" value="DairyFree">
                    <label for="dairyfree" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#DairyFree</label>
                    
                    <input type="checkbox" id="lowcarb" name="tags[]" value="LowCarb">
                    <label for="lowcarb" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#LowCarb</label>
                    
                    <input type="checkbox" id="keto" name="tags[]" value="Keto">
                    <label for="keto" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Keto</label>
                    
                    <input type="checkbox" id="paleo" name="tags[]" value="Paleo">
                    <label for="paleo" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Paleo</label>
                    
                    <input type="checkbox" id="halal" name="tags[]" value="Halal">
                    <label for="halal" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Halal</label>
                    
                    <input type="checkbox" id="kosher" name="tags[]" value="Kosher">
                    <label for="kosher" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Kosher</label>
                    
                    <input type="checkbox" id="easy" name="tags[]" value="Easy">
                    <label for="easy" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Easy</label>
                    
                    <input type="checkbox" id="beginner" name="tags[]" value="Beginner">
                    <label for="beginner" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#BeginnerFriendly</label>
                    
                    <input type="checkbox" id="sweet" name="tags[]" value="Sweet">
                    <label for="sweet" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Sweet</label>
                    
                    <input type="checkbox" id="budgetfriendly" name="tags[]" value="BudgetFriendly">
                    <label for="budgetfriendly" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#BudgetFriendly</label>
                    
                    <input type="checkbox" id="kidfriendly" name="tags[]" value="KidFriendly">
                    <label for="kidfriendly" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#KidFriendly</label>
                    
                    <input type="checkbox" id="weightloss" name="tags[]" value="WeightLoss">
                    <label for="weightloss" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#WeightLoss</label>
                    
                    <input type="checkbox" id="comfortfood" name="tags[]" value="Comfort Food">
                    <label for="comfortfood" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#ComfortFood</label>
                    
                    <input type="checkbox" id="homemade" name="tags[]" value="Homemade">
                    <label for="homemade" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Homemade</label>
                    
                    <input type="checkbox" id="trending" name="tags[]" value="Trending">
                    <label for="trending" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Trending</label>
                    
                    <input type="checkbox" id="viral" name="tags[]" value="Viral">
                    <label for="viral" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#Viral</label>
                    
                    <input type="checkbox" id="tiktok" name="tags[]" value="TikTok Recipes">
                    <label for="tiktok" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">#TikTokRecipes</label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <!-- Equipment -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Equipments</h2>
                    <div class="flex flex-wrap gap-3">
                        <input type="checkbox" id="knife" name="equipment[]" value="Knife">
                        <label for="knife" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Knife</label>
                        
                        <input type="checkbox" id="cutting-board" name="equipment[]" value="Cutting Board">
                        <label for="cutting-board" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Cutting Board</label>
                        
                        <input type="checkbox" id="pan" name="equipment[]" value="Pan">
                        <label for="pan" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pan</label>
                        
                        <input type="checkbox" id="pot" name="equipment[]" value="Pot">
                        <label for="pot" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Pot</label>
                        
                        <input type="checkbox" id="blender" name="equipment[]" value="Blender">
                        <label for="blender" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Blender</label>
                        
                        <input type="checkbox" id="oven" name="equipment[]" value="Oven">
                        <label for="oven" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Oven</label>
                        
                        <input type="checkbox" id="microwave" name="equipment[]" value="Microwave">
                        <label for="microwave" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Microwave</label>
                        
                        <input type="checkbox" id="grill" name="equipment[]" value="Grill">
                        <label for="grill" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Grill</label>
                        
                        <input type="checkbox" id="mixer" name="equipment[]" value="Mixer">
                        <label for="mixer" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Mixer</label>
                        
                        <input type="checkbox" id="spatula" name="equipment[]" value="Spatula">
                        <label for="spatula" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Spatula</label>
                        
                        <input type="checkbox" id="strainer" name="equipment[]" value="Strainer">
                        <label for="strainer" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Strainer</label>
                        
                        <input type="checkbox" id="tong" name="equipment[]" value="Tong">
                        <label for="tong" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Tong</label>
                        
                        <input type="checkbox" id="whisk" name="equipment[]" value="Whisk">
                        <label for="whisk" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Whisk</label>
                    </div>
                </div>

                <!-- Budget Level -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Budget Level</h2>
                    <div class="flex flex-wrap gap-3">
                        <input type="radio" id="budget_ultra_low" name="budget_level" value="Ultra Low Budget (₱0 - ₱100)">
                        <label for="budget_ultra_low" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Ultra Low Budget (₱0 - ₱100)</label>
                        
                        <input type="radio" id="budget_low" name="budget_level" value="Low Budget (₱101 - ₱250)">
                        <label for="budget_low" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Low Budget (₱101 - ₱250)</label>
                        
                        <input type="radio" id="budget_mid" name="budget_level" value="Mid Budget (₱251 - ₱500)">
                        <label for="budget_mid" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Mid Budget (₱251 - ₱500)</label>
                        
                        <input type="radio" id="budget_high" name="budget_level" value="High Budget (₱501 - ₱1,000)">
                        <label for="budget_high" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">High Budget (₱501 - ₱1,000)</label>
                        
                        <input type="radio" id="budget_luxury" name="budget_level" value="Luxury Budget (₱1,001 above)">
                        <label for="budget_luxury" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Luxury Budget (₱1,001 above)</label>
                    </div>
                </div>

                <!-- Cooking Time -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Cooking Time</h2>
                    <div class="flex flex-wrap gap-3">
                        <input type="radio" id="cook_1_5" name="recipe_cooktime" value="1 to 5 minutes">
                        <label for="cook_1_5" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">1-5 minutes</label>
                        
                        <input type="radio" id="cook_5_15" name="recipe_cooktime" value="5 to 15 minutes">
                        <label for="cook_5_15" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">5-15 minutes</label>
                        
                        <input type="radio" id="cook_15_30" name="recipe_cooktime" value="15 to 30 minutes">
                        <label for="cook_15_30" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">15-30 minutes</label>
                        
                        <input type="radio" id="cook_30_60" name="recipe_cooktime" value="30 to 60 minutes">
                        <label for="cook_30_60" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">30-60 minutes</label>
                        
                        <input type="radio" id="cook_1_3h" name="recipe_cooktime" value="1 to 3 hours">
                        <label for="cook_1_3h" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">1-3 hours</label>
                        
                        <input type="radio" id="cook_above_3h" name="recipe_cooktime" value="Above 3 hours">
                        <label for="cook_above_3h" class="tag bg-white border border-gray-300 text-gray-700 rounded-full px-4 py-2 text-sm font-medium cursor-pointer">Above 3 hours</label>
                    </div>
                </div>
            </div>

            <div class="mt-16 text-center">
                <button type="submit" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-full hover:bg-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Preferences
                </button>
            </div>
        </form>
    </main>
</div>

<script>
    // Add visual feedback for tag selection without interfering with form functionality
    const labels = document.querySelectorAll('label.tag');
    labels.forEach(label => {
        const input = document.getElementById(label.getAttribute('for'));
        
        // Set initial state
        if (input.checked) {
            label.classList.add('selected');
        }
        
        // Add click handler for visual feedback
        label.addEventListener('click', () => {
            setTimeout(() => {
                if (input.checked) {
                    label.classList.add('selected');
                } else {
                    label.classList.remove('selected');
                }
                
                // For radio buttons, remove selected class from siblings
                if (input.type === 'radio') {
                    const siblings = document.querySelectorAll(`input[name="${input.name}"]`);
                    siblings.forEach(sibling => {
                        if (sibling !== input) {
                            const siblingLabel = document.querySelector(`label[for="${sibling.id}"]`);
                            if (siblingLabel) {
                                siblingLabel.classList.remove('selected');
                            }
                        }
                    });
                }
            }, 10);
        });
    });
</script>

</body>
</html>