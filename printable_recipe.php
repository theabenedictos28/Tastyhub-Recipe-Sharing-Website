<?php
session_start();
require 'db.php'; // Database connection

// ✅ Ensure recipe_id is provided
if (!isset($_GET['recipe_id']) || empty($_GET['recipe_id'])) {
    echo "<h3 class='text-center text-danger'>No recipe found</h3>";
    exit;
}

$recipe_id = intval($_GET['recipe_id']);

// ✅ Fetch main recipe details
$sql = "SELECT r.recipe_name, r.recipe_description, r.category, r.difficulty, 
               r.preparation, r.cooktime, r.servings, r.budget, r.image, 
               r.video_path, r.tags, u.username, r.created_at
        FROM recipe r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$recipe_result = $stmt->get_result();

if ($recipe_result->num_rows === 0) {
    echo "<h3 class='text-center text-danger'>No recipe found</h3>";
    exit;
}
$recipe = $recipe_result->fetch_assoc();

// ✅ Fetch ingredients
$ingredients_sql = "SELECT ingredient_name FROM ingredients WHERE recipe_id = ?";
$stmt = $conn->prepare($ingredients_sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$ingredients_result = $stmt->get_result();

// ✅ Fetch equipments
$equipments_sql = "SELECT equipment_name FROM equipments WHERE recipe_id = ?";
$stmt = $conn->prepare($equipments_sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$equipments_result = $stmt->get_result();

// ✅ Fetch instructions
$instructions_sql = "SELECT instruction_name FROM instructions WHERE recipe_id = ?";
$stmt = $conn->prepare($instructions_sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$instructions_result = $stmt->get_result();

// ✅ Fetch nutritional info
$nutritional_sql = "SELECT calories, fat, protein, carbohydrates, fiber, sugar, cholesterol, sodium 
                    FROM nutritional_info WHERE recipe_id = ?";
$stmt = $conn->prepare($nutritional_sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$nutritional_result = $stmt->get_result();
$nutritional_info = $nutritional_result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">

    <title><?php echo htmlspecialchars($recipe['recipe_name']); ?> - Printable Recipe</title>
    <style>
        @media print {
            body { margin: 0.5in; }
            .no-print { display: none; }
        }
        
        body {
            font-family: Poppins, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px auto;
            color: #333;
            max-width: 8.5in;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #FEA116;
            padding-bottom: 10px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .logo-icon {
            width: 24px;
            height: 24px;
            color: #FEA116;
        }
        
        .logo-text {
            font-family: Nunito, sans-serif;
            font-size: 25px;
            font-weight: 900;
            color: #FEA116;
        }
        
        .header h1 {
            font-size: 22px;
            margin: 10px 0 0;
            color: #181411;
        }
        
        .recipe-info {
            font-size: 11px;
            color: #666;
            margin: 0;
        }
        
        .section {
            margin-bottom: 18px;
        }
        
        .section h2 {
            font-size: 16px;
            margin: 0 0 8px 0;
            color: #181411;
            border-bottom: 1px solid #e5e0dc;
            padding-bottom: 3px;
        }
        
        .ingredient-list, .equipment-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }
        
        .equipment-list {
            grid-template-columns: 1fr;
        }
        
        .ingredient-list li, .equipment-list li {
            padding-left: 18px;
            position: relative;
            font-size: 11px;
        }
        
        .ingredient-list li::before, .equipment-list li::before {
            content: "☐";
            position: absolute;
            left: 0;
            color: #FEA116;
            font-weight: bold;
            font-size: 12px;
        }
        .instructions {
            margin-top: 12px;
        }
        
        .instruction-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .instruction-list li {
            margin-bottom: 6px;
            padding: 5px 8px;
            background: #fafafa;
            border-left: 3px solid #FEA116;
            border-radius: 0 3px 3px 0;
        }

                /* Compact mode for very long recipes */
        .compact .ingredient-list li,
        .compact .equipment-list li {
            font-size: 9px;
        }
        .compact .instruction-list li {
            padding: 4px 6px;
            margin-bottom: 4px;
        }
        
        .compact .step-text {
            font-size: 9px;
        }
        
        /* Ultra-compact for 20+ ingredients */
        .ultra-compact .ingredient-list {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1px;
        }
        
        .ultra-compact .ingredient-list li {
            font-size: 9px;
            line-height: 1.1;
            padding-left: 12px;
        }
        
        .ultra-compact .section {
            margin-bottom: 8px;
        }
        
        .ultra-compact .instruction-list li {
            padding: 3px 6px;
            margin-bottom: 3px;
        }

        .step-number {
            font-weight: bold;
            color: #FEA116;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .step-text {
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .equipment-section {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <svg class="logo-icon" viewBox="0 0 48 48" fill="none">
                <img src="img/logo_new.png" alt="Logo" style="width: 40px; height: 40px;"></i>
            </svg>
            <span class="logo-text">TastyHub</span>
        </div>
        <h1><?php echo htmlspecialchars($recipe['recipe_name']); ?></h1>
        <p class="recipe-info">By: <?php echo htmlspecialchars($recipe['username']); ?></p>
        <p class="recipe-info">Servings: <?php echo htmlspecialchars($recipe['servings']); ?> | Prep Type: <?php echo htmlspecialchars($recipe['preparation']); ?> | Cook: <?php echo htmlspecialchars($recipe['cooktime']); ?> | Budget: <?php echo htmlspecialchars($recipe['budget']); ?></p>
    </div>

    <div class="section">
        <h2>Ingredients</h2>
        <ul class="ingredient-list">
                    <?php while ($ingredient = $ingredients_result->fetch_assoc()) : ?>
                        <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer">
                        <li style="list-style-type: none;" class="h-5 w-5 rounded border-gray-300 text-[var(--primary-orange)] focus:ring-[var(--primary-orange)] mr-3" type="checkbox"><span> <?php echo htmlspecialchars($ingredient['ingredient_name']); ?> </span></li>
                    </label>
                    <?php endwhile; ?>
        </ul>
    </div>
    
    <div class="section equipment-section">
        <h2>Equipment</h2>
        <ul class="equipment-list">
                        <?php while ($equipment = $equipments_result->fetch_assoc()) : ?>
                    <label class="flex items-center text-[var(--neutral-text)] hover:text-black transition cursor-pointer">
                        <li style="list-style-type: none;" class="h-5 w-5 rounded border-gray-300 text-[var(--primary-orange)] focus:ring-[var(--primary-orange)] mr-3" type="checkbox"> <span> <?php echo htmlspecialchars($equipment['equipment_name']); ?></span></li> </label>
                    <?php endwhile; ?>
        </ul>
    </div>

<div class="section instructions">
    <h2>Instructions</h2>
    <ol class="instruction-list">
        <?php 
        $step_number = 1; 
        while ($instruction = $instructions_result->fetch_assoc()) : 
        ?>
            <li>
                <span class="step-number"><?php echo $step_number++; ?>.</span>
                <span class="step-text">
                    <?php echo htmlspecialchars($instruction['instruction_name']); ?>
                </span>
            </li>
        <?php endwhile; ?>
    </ol>
</div>

<div class="no-print" style="margin-top: 20px; text-align: center;">
    <button 
        onclick="window.print()" 
        style="background: #ea802a; color: white; border: none; padding: 8px 16px; 
               font-size: 12px; border-radius: 4px; cursor: pointer;">
        🖨️ Print Recipe
    </button>
    <p style="font-size: 10px; color: #666; margin-top: 8px;">
        This recipe is optimized for printing on standard letter-sized paper
    </p>
</div>

</body>
</html>