        <?php
        session_start();
        require 'db.php'; // Database connection

        if (!isset($_SESSION['user_id'])) {
            header("Location: signin.php");
            exit;
        }


        // Fetch user details from session
        $username = htmlspecialchars($_SESSION['username']);
        $email = htmlspecialchars($_SESSION['email']);
        $user_id = $_SESSION['user_id']; // Get the logged-in user's ID


        // Initialize search variable
        $searchQuery = ''; 
        if (isset($_GET['q'])) {
            $searchQuery = trim($_GET['q']); // Trim whitespace
        }

        // Modify the SQL query to include search functionality
        $sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime,recipe.servings, recipe.budget, recipe.image, recipe.video_path, users.username, recipe.user_id AS recipe_user_id 
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
        $sql = "SELECT recipe.recipe_name, recipe.recipe_description, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime, recipe.servings, recipe.budget, recipe.image, recipe.video_path, recipe.tags, users.username, users.profile_picture, recipe.user_id AS recipe_user_id, recipe.created_at
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


        $recipe = $result->fetch_assoc();
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

            <link href="css/recipe_details.css" rel="stylesheet">

        <style>
            .font-nunito { font-family: 'Nunito', sans-serif; }
            [x-cloak] { display: none !important; }
        </style>
        </head>

    <body class="bg-orange-50 h-[200vh]">

 <div class="fixed top-4 right-4 z-50">
    <button onclick="window.history.back();" 
            class="flex items-center gap-1 text-white px-4 py-2 bg-gray-600 rounded-md shadow-md hover:bg-gray-700">
        <span class="material-icons">arrow_left</span>
        Back to Admin
    </button>
</div>

<main class="container mx-auto py-6 px-6 border-2 m-5 shadow-md bg-gray-50">
<div class="max-w-7xl mx-auto">
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
         class="w-9 h-9 rounded-full object-cover mr-1">

<!-- Username + Badges -->
<span class="flex items-center gap-1">
    <span style="<?= $usernameStyle ?>">
        @<?= htmlspecialchars($recipe['username']); ?>
    </span>
</span>


    <?php if (!empty($badges)): ?>
        <?php foreach ($badges as $badge): ?>
            <?php if ($badge['badge_name'] !== "No Badge Yet"): ?>
                <img src="<?= htmlspecialchars($badge['badge_icon']); ?>" 
                     alt="<?= htmlspecialchars($badge['badge_name']); ?>" 
                     title="<?= htmlspecialchars($badge['badge_name']); ?>"
                     class="w-6 h-6 object-contain">
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
    @media (max-width: 768px) {
        .date {
             font-size: 12px;
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
            <a href="#" 
               class="<?php echo $color['bg'] . ' ' . $color['text']; ?> text-sm font-medium px-3 py-1 rounded-full hover:opacity-80 transition">
                #<?php echo $cleanTag; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


</div>
<div class="relative mb-8">
<img alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>" class="w-full h-[450px] rounded-2xl shadow-lg object-cover" src="<?php echo !empty($recipe['image']) ? htmlspecialchars($recipe['image']) : 'uploads/default-placeholder.png'; ?>"/>


</div>


<p class="text-[var(--neutral-text)] text-lg leading-relaxed mb-8"><?php echo nl2br(htmlspecialchars($recipe['recipe_description'])); ?></p>

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
<p class="text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['category']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Preparation</h4>
<p class="text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['preparation']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Cooking Time</h4>
<p class="text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['cooktime']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Difficulty</h4>
<p class="text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['difficulty']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Servings</h4>
<p class="text-lg font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['servings']); ?></p>
</div>
<div class="text-center">
<h4 class="text-sm font-semibold text-[var(--neutral-text-light)] uppercase tracking-wider">Budget</h4>
<p class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($recipe['budget']); ?></p>
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
                        <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer">
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
                    <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer">
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


    </div>
    </main>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="js/main.js"></script>
        </div>

        </body>

        </html>
