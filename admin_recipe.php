<?php
session_start();
require 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

// Get filters from GET
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'Date (Newest)';

// Base query
$sql_recipes = "SELECT r.*, u.username AS submitted_by 
                FROM recipe r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE 1 AND r.status != 'archived'";

// Add search condition
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql_recipes .= " AND (r.recipe_name LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%')";
}

// Add status filter
if (!empty($status_filter) && strtolower($status_filter) !== 'all') {
    $status_safe = $conn->real_escape_string(strtolower($status_filter));
    $sql_recipes .= " AND r.status='$status_safe'";
}

// Add sorting
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
<title>Admin Dashboard - Recipes</title>
        <!-- Favicon -->
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
.status-pending { background-color: #fffbeb; color: #f59e0b; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
.status-approved { background-color: #ecfdf5; color: #10b981; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
.status-rejected { background-color: #fef2f2; color: #ef4444; padding: 2px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; }
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
<a class="flex items-center p-3 rounded-lg active-link" href="admin_recipe.php">
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
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_archived.php">
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
<h2 class="text-3xl font-bold text-gray-800">Recipes</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<!-- Filters -->
<form method="GET" class="card p-6 mb-8">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div>
        <label for="search" class="block text-sm font-medium text-gray-700">Search Recipe</label>
        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or submitter"
               class="mt-1 block w-full pl-3 pr-3 py-2 border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500 sm:text-sm"/>
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Filter by Status</label>
        <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
            <?php 
            $statuses = ['All','Approved','Pending','Rejected'];
            foreach($statuses as $status){
                $selected = ($status_filter == strtolower($status)) ? 'selected' : '';
                echo "<option value='$status' $selected>$status</option>";
            }
            ?>
        </select>
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

<!-- Recipes Table -->
<div class="card overflow-hidden">
<table class="w-full">
<thead class="bg-gray-50">
<tr>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Recipe Name</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Submitted By</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Status</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200">
<?php while($row = $result_all->fetch_assoc()): ?>
<tr>
<td class="p-4 whitespace-nowrap font-medium text-sm text-gray-800"><?php echo htmlspecialchars($row['recipe_name']); ?></td>
<td class="p-4 whitespace-nowrap font-medium text-xs text-gray-600"><?php echo htmlspecialchars($row['submitted_by']); ?></td>
<td class="p-4 whitespace-nowrap">
<?php 
$statusClass = 'status-pending';
if($row['status'] == 'approved') $statusClass = 'status-approved';
if($row['status'] == 'rejected') $statusClass = 'status-rejected';
?>
<span class="<?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span>
</td>
<td class="p-4 whitespace-nowrap space-x-2">
    <!-- Details button -->
    <a href="adminrecipe_details.php?id=<?php echo $row['id']; ?>" class="py-2 px-3 rounded-lg bg-blue-500 text-white font-medium hover:bg-blue-600 text-sm">Details</a>

    <!-- Status dropdown -->
    <select class="py-2 px-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 status-select" 
        data-recipe-id="<?php echo $row['id']; ?>">
    <option value="pending" <?php echo ($row['status']=='pending')?'selected':''; ?>>Pending</option>
    <option value="approved" <?php echo ($row['status']=='approved')?'selected':''; ?>>Approved</option>
    <option value="rejected" <?php echo ($row['status']=='rejected')?'selected':''; ?>>Rejected</option>
</select>

</td>

</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- Reject Reason Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-lg p-6 w-96">
    <h3 class="text-lg font-bold mb-4">Reject Recipe</h3>
    <div id="rejectMessage" class="mb-4 p-2 rounded hidden"></div>

    <form id="rejectForm" method="POST" action="approve_recipe.php">
        <input type="hidden" name="id" id="modalRecipeId">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="redirect" value="admin_recipe.php">

        <label class="block text-sm font-medium mb-1">Select Reason</label>
        <select name="rejection_reason" id="rejection_reason" class="w-full border-gray-300 rounded-md mb-4">
            <option value="">-- Select Reason --</option>
             <option value="Inappropriate Content">Inappropriate Content</option>
                <option value="Duplicate Recipe">Duplicate Recipe</option>
                <option value="Incomplete Information">Incomplete Information</option>
                <option value="Safety Concerns">Safety Concerns</option>
                <option value="Copyright Violation">Copyright Violation</option>
            <option value="Other">Other</option>
        </select>

        <div id="customReasonDiv" class="hidden mb-4">
            <label class="block text-sm font-medium mb-1">Custom Reason</label>
            <input type="text" name="custom_reason" id="custom_reason" class="w-full border-gray-300 rounded-md" required>
        </div>

        <div class="flex justify-end space-x-2">
            <button type="button" id="cancelBtn" class="py-2 px-4 rounded-lg border border-gray-300">Cancel</button>
            <button type="submit" class="py-2 px-4 rounded-lg bg-red-500 text-white hover:bg-red-600">Reject</button>
        </div>
    </form>
  </div>
</div>

<script>
const rejectModal = document.getElementById('rejectModal');
const modalRecipeId = document.getElementById('modalRecipeId');
const rejectionReason = document.getElementById('rejection_reason');
const customReasonDiv = document.getElementById('customReasonDiv');
const customReasonInput = document.getElementById('custom_reason');
const cancelBtn = document.getElementById('cancelBtn');
const rejectMessage = document.getElementById('rejectMessage');

// Handle status change
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const recipeId = this.dataset.recipeId;
        const value = this.value;

        if (value === 'approved' || value === 'pending') {
            // Update status via AJAX
            fetch('approve_recipe.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `id=${recipeId}&action=${value}`
            })
            .then(res => res.json())
            .then(data => {
                rejectMessage.textContent = data.message;
                rejectMessage.className = `mb-4 p-2 rounded ${data.status==='error'?'bg-red-100 text-red-700':'bg-green-100 text-green-700'}`;
                rejectMessage.classList.remove('hidden');
                setTimeout(() => location.reload(), 1000);
            });
        } else if (value === 'rejected') {
            // Show modal for rejection
            modalRecipeId.value = recipeId;
            rejectionReason.value = '';
            customReasonDiv.classList.add('hidden');
            customReasonInput.value = '';
            rejectMessage.classList.add('hidden'); // clear previous message
            rejectModal.classList.remove('hidden');
        }
    });
});

// Show/hide custom reason input if "Other" selected
rejectionReason.addEventListener('change', function() {
    if (this.value === 'Other') {
        customReasonDiv.classList.remove('hidden');
        customReasonInput.setAttribute('required', 'required'); // make input required
    } else {
        customReasonDiv.classList.add('hidden');
        customReasonInput.removeAttribute('required'); // remove required
    }
});

// Submit rejection form via AJAX
document.getElementById('rejectForm').addEventListener('submit', function(e){
    e.preventDefault();

    const recipeId = modalRecipeId.value;
    let reason = rejectionReason.value.trim();

    if(!reason) {
        alert("Please select a rejection reason.");
        return;
    }

    if(reason === 'Other') {
        const custom = customReasonInput.value.trim();
        if(!custom) {
            alert("Please enter a custom rejection reason.");
            return;
        }
        reason = custom; // use custom reason
    }

    fetch('approve_recipe.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${recipeId}&action=reject&rejection_reason=${encodeURIComponent(reason)}`
    })
    .then(res => res.json())
    .then(data => {
        rejectMessage.textContent = data.message;
        rejectMessage.className = `mb-4 p-2 rounded ${data.status==='error'?'bg-red-100 text-red-700':'bg-green-100 text-green-700'}`;
        rejectMessage.classList.remove('hidden');
        if (data.status === 'success') {
            setTimeout(() => location.reload(), 1500);
        }
    });
});

// Close modal
cancelBtn.addEventListener('click', () => {
    rejectModal.classList.add('hidden');
});
</script>


</main>
</body>
</html>
