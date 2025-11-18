<?php
session_start();
require 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'Date (Newest)';

// Base query (only archived)
$sql_recipes = "SELECT r.*, u.username AS submitted_by 
                FROM recipe r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.archived = 1";

// Search filter
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql_recipes .= " AND (r.recipe_name LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%')";
}

// Sorting
switch ($sort_by) {
    case 'Date (Oldest)':
        $sql_recipes .= " ORDER BY r.created_at ASC";
        break;
    case 'Name (A-Z)':
        $sql_recipes .= " ORDER BY r.recipe_name ASC";
        break;
    case 'Name (Z-A)':
        $sql_recipes .= " ORDER BY r.recipe_name DESC";
        break;
    default:
        $sql_recipes .= " ORDER BY r.created_at DESC";
        break;
}

$result_all = $conn->query($sql_recipes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Archived Recipes - Admin</title>
<link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f7f3f0; }
.sidebar { background-color: #ffffff; }
.main-content { background-color: #f7f3f0; }
.active-link { background-color: #ff6f61; color: white; }
.inactive-link { color: #4a4a4a; }
.card { background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
</style>
</head>
<body class="flex h-screen">

<!-- Sidebar -->
<aside class="sidebar w-64 flex-shrink-0 p-6 flex flex-col justify-between">
<div>
<div class="flex items-center mb-10">
<img src="img/logo_new.png" alt="Logo" class="w-12 h-12 mr-2">
<h1 class="text-2xl font-bold text-gray-800">Admin</h1>
</div>
<nav>
<ul>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin.php">
<span class="material-icons mr-3">dashboard</span>
<span>Dashboard</span>
</a>
</li>
<li class="mb-4 sidebar-item">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_recipe.php">
<span class="material-icons mr-3">receipt_long</span>
<span>Recipes</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_users.php">
<span class="material-icons mr-3">people</span>
<span>Users</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link" href="admin_comments.php">
<span class="material-icons mr-3">comment</span>
<span>Comments</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_reports.php">
<span class="material-icons mr-3">flag</span>
<span>Reports</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_appeals.php">
<span class="material-icons mr-3">gavel</span>
<span>Appeals</span>
</a>
</li>
<li class="mb-4 sidebar-item">
<a class="flex items-center p-3 rounded-lg active-link" href="admin_archived.php">
<span class="material-icons mr-3">archive</span>
<span>Archived</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_feedback.php">
<span class="material-icons mr-3">feedback</span>
<span>Feedback</span>
</a>
</li>
</ul>
</nav>
</div>
<div>
<a class="flex items-center p-3 rounded-lg text-red-500 hover:bg-red-50" href="logout.php">
<span class="material-icons mr-3">logout</span>
<span>Logout</span>
</a>
</div>
</aside>

<main class="main-content flex-1 p-8 overflow-y-auto">
<header class="flex justify-between items-center mb-8">
<h2 class="text-3xl font-bold text-gray-800">Archived Recipes</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<!-- Filters -->
<form method="GET" class="card p-6 mb-8">
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <label for="search" class="block text-sm font-medium text-gray-700">Search Recipe</label>
        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or submitter"
               class="mt-1 block w-full pl-3 pr-3 py-2 border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500 sm:text-sm"/>
    </div>

    <div>
        <label for="sort" class="block text-sm font-medium text-gray-700">Sort by</label>
        <select name="sort" id="sort" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
            <?php 
            $options = ['Date (Newest)','Date (Oldest)','Name (A-Z)','Name (Z-A)'];
            foreach($options as $option){
                $selected = ($sort_by == $option) ? 'selected' : '';
                echo "<option value='$option' $selected>$option</option>";
            }
            ?>
        </select>
    </div>

    <div class="flex items-end">
        <button type="submit" class="w-full py-2 px-4 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Apply Filters</button>
    </div>
</div>
</form>

<!-- Archived Recipes Table -->
<div class="card overflow-hidden">
<table class="w-full">
<thead class="bg-gray-50">
<tr>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Recipe Name</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Submitted By</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Date Archived</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200">
<?php if ($result_all->num_rows > 0): ?>
    <?php while($row = $result_all->fetch_assoc()): ?>
    <tr>
        <td class="p-4 whitespace-nowrap font-medium text-sm text-gray-800"><?php echo htmlspecialchars($row['recipe_name']); ?></td>
        <td class="p-4 whitespace-nowrap text-xs text-gray-600"><?php echo htmlspecialchars($row['submitted_by']); ?></td>
        <td class="p-4 whitespace-nowrap text-xs text-gray-600"><?php echo date("M d, Y", strtotime($row['archived_at'])); ?></td>
        <td class="p-4 whitespace-nowrap">
            <a href="adminrecipe_details.php?id=<?php echo $row['id']; ?>" class="py-2 px-3 rounded-lg bg-blue-500 text-white font-medium hover:bg-blue-600 text-sm">Details</a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="4" class="p-6 text-center text-gray-500">No archived recipes found.</td>
    </tr>
<?php endif; ?>
</tbody>
</table>
</div>

</main>
</body>
</html>
