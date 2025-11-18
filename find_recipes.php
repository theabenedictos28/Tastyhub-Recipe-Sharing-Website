<?php
session_start();
require 'db.php'; // Include database connection

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$ingredients = $data['ingredients'];

// Prepare the SQL query
$placeholders = implode(',', array_fill(0, count($ingredients), '?'));
$sql = "SELECT recipe.id, recipe.recipe_name, recipe.image, recipe.category, recipe.difficulty, recipe.preparation, recipe.cooktime, 
        COUNT(favorites.recipe_id) AS favorite_count, COUNT(likes.recipe_id) AS like_count
        FROM recipe 
        JOIN ingredients ON recipe.id = ingredients.recipe_id
        LEFT JOIN favorites ON recipe.id = favorites.recipe_id
        LEFT JOIN likes ON recipe.id = likes.recipe_id
        WHERE recipe.status = 'approved' AND ingredients.ingredient_name IN ($placeholders)
        GROUP BY recipe.id
        HAVING COUNT(DISTINCT ingredients.ingredient_name) = ?"; // Ensure all ingredients are matched

$stmt = $conn->prepare($sql);
$ingredientCount = count($ingredients);
$stmt->bind_param(str_repeat('s', $ingredientCount) . 'i', ...$ingredients, $ingredientCount);
$stmt->execute();
$result = $stmt->get_result();

$recipes = [];
while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
}

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode($recipes);
?>