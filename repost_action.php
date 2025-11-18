   <?php
   session_start();
   require 'db.php'; // Database connection

   if (!isset($_SESSION['user_id'])) {
       header("Location: signin.php");
       exit;
   }

   // Debugging output
   var_dump($_POST); // This will show you the contents of the $_POST array

   $user_id = $_SESSION['user_id'];
   $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : null;
   $caption = isset($_POST['caption']) ? htmlspecialchars(trim($_POST['caption'])) : null;

   // Check if recipe_id and caption are set
   if ($recipe_id === null || $caption === null) {
       echo "Recipe ID or caption is missing.";
       exit;
   }

   // Insert the repost into the database
   $sql = "INSERT INTO reposts (user_id, recipe_id, caption) VALUES (?, ?, ?)";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("iis", $user_id, $recipe_id, $caption);
   $stmt->execute();

   if ($stmt->affected_rows > 0) {
       // Redirect back to the recipe details page or wherever you want
       header("Location: profile.php");
   } else {
       // Handle error
       echo "Error reposting recipe.";
   }
   ?>
   