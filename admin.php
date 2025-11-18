<?php
session_start();
require 'db.php'; // Include your database connection

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch all recipes
$sql_all = "SELECT * FROM recipe";
$result_all = $conn->query($sql_all);

// Fetch only pending recipes
$sql_pending = "SELECT * FROM recipe WHERE status = 'pending'";
$result_pending = $conn->query($sql_pending);

// Count statuses for dashboard cards
$pending_count = $result_pending->num_rows;
$approved_count = $conn->query("SELECT * FROM recipe WHERE status='approved'")->num_rows;
$declined_count = $conn->query("SELECT * FROM recipe WHERE status='rejected'")->num_rows;
// Count pending user reports
$active_reports_count = $conn->query("SELECT * FROM reports WHERE status='Pending'")->num_rows;

// Count pending comment reports
$active_comment_reports_count = $conn->query("SELECT * FROM comments_reports WHERE status='Pending'")->num_rows;

// Combine both
$total_pending_reports = $active_reports_count + $active_comment_reports_count;
?>


<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Dashboard</title>
        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<style type="text/tailwindcss">
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f3f0;
        }
        .sidebar {
            background-color: #ffffff;
        }
        .main-content {
            background-color: #f7f3f0;
        }
        .active-link {
            background-color: #ff6f61;
            color: white;
        }
        .inactive-link {
            color: #4a4a4a;
        }
        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #ff6f61;
            color: white;
        }
        .btn-primary:hover {
            background-color: #e66054;
        }
        .btn-secondary {
            background-color: #f0f0f0;
            color: #4a4a4a;
        }
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        .status-pending {
            background-color: #fffbeb;
            color: #f59e0b;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
        .status-approved {
            background-color: #ecfdf5;
            color: #10b981;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
        .status-declined {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
        .status-rejected {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .sidebar-submenu {
            display: none;
        }
        .sidebar-item:hover .sidebar-submenu,
        .sidebar-item.open .sidebar-submenu {
            display: block;
        }
        input[type="radio"]:checked+label {
            color: #ff6f61;
            font-weight: 500;
        }
    </style>
</head>
<body class="flex h-screen">
<aside class="sidebar w-64 flex-shrink-0 p-6 flex flex-col justify-between">
<div>
<div class="flex items-center mb-10">
<img src="img/logo_new.png" alt="Logo" class="w-12 h-12 mr-2">
<h1 class="text-2xl font-bold text-gray-800">Admin</h1>
</div>
<nav>
<ul>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg active-link" href="admin.php">
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
<h2 class="text-3xl font-bold text-gray-800">Admin Dashboard</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card p-6 flex items-center">
        <div class="p-3 rounded-full bg-yellow-100 mr-4">
            <span class="material-icons text-2xl text-yellow-500">hourglass_top</span>
        </div>
        <div>
            <p class="text-gray-500">Pending Recipes</p>
            <p class="text-2xl font-bold"><?php echo $pending_count; ?></p>
        </div>
    </div>

    <div class="card p-6 flex items-center">
        <div class="p-3 rounded-full bg-green-100 mr-4">
            <span class="material-icons text-2xl text-green-500">check_circle</span>
        </div>
        <div>
            <p class="text-gray-500">Approved Recipes</p>
            <p class="text-2xl font-bold"><?php echo $approved_count; ?></p>
        </div>
    </div>

    <div class="card p-6 flex items-center">
        <div class="p-3 rounded-full bg-red-100 mr-4">
            <span class="material-icons text-2xl text-red-500">cancel</span>
        </div>
        <div>
            <p class="text-gray-500">Declined Recipes</p>
            <p class="text-2xl font-bold"><?php echo $declined_count; ?></p>
        </div>
    </div>

    <!-- ✅ New Card for Active Reports -->
    <div class="card p-6 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 mr-4">
            <span class="material-icons text-2xl text-purple-500">report_problem</span>
        </div>
        <div>
            <p class="text-gray-500">Active Reports</p>
            <p class="text-2xl font-bold"><?php echo $total_pending_reports; ?></p>
        </div>
    </div>
</div>


<!-- Pending Recipes Table -->
<div class="card overflow-hidden mt-8">
<div class="p-6 border-b">
<h3 class="text-xl font-semibold text-gray-800">Pending Approval</h3>
</div>
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
<?php while($row = $result_pending->fetch_assoc()): ?>
<tr>
<td class="p-4 whitespace-nowrap font-medium text-gray-800">
    <?php echo htmlspecialchars($row['recipe_name']); ?>
</td>
<td class="p-4 whitespace-nowrap text-gray-600">
    <?php
    $user_id = $row['user_id']; // assuming this exists
    $user_result = $conn->query("SELECT username FROM users WHERE id = $user_id");
    $user = $user_result->fetch_assoc();
    echo htmlspecialchars($user ? $user['username'] : 'Unknown');
    ?>
</td>
<td class="p-4 whitespace-nowrap"><span class="status-pending">Pending</span></td>
<td class="p-4 whitespace-nowrap space-x-2">
    <!-- Approve -->
    <form action="approve_recipe.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <input type="hidden" name="action" value="approved">
        <input type="hidden" name="redirect" value="admin.php">
        <button class="py-2 px-4 rounded-lg bg-green-500 text-white font-medium hover:bg-green-600">
            Approve
        </button>
    </form>
    <!-- Reject (opens modal) -->
    <button type="button" onclick="openRejectModal(<?php echo $row['id']; ?>)"
            class="py-2 px-4 rounded-lg bg-red-500 text-white font-medium hover:bg-red-600">
        Reject
    </button>
    <!-- Details -->
    <a href="adminrecipe_details.php?id=<?php echo $row['id']; ?>"
       class="py-2 px-4 rounded-lg bg-blue-500 text-white font-medium hover:bg-blue-600">
       Details
    </a>
</td>

<!-- Add this modal and script at the bottom of your admin.php page, before closing </body> tag -->

<!-- Reject Reason Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-96 shadow-xl">
        <h3 class="text-lg font-bold mb-4">Reject Recipe</h3>
        <form id="rejectForm" method="POST" action="approve_recipe.php">
            <input type="hidden" name="id" id="modalRecipeId">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="redirect" value="admin.php">
            
            <label class="block text-sm font-medium mb-1">Select Reason</label>
            <select name="rejection_reason" id="rejection_reason" class="w-full border border-gray-300 rounded-md p-2 mb-4 focus:outline-none focus:ring-2 focus:ring-red-500">
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
                <textarea name="custom_reason" id="custom_reason" rows="3" 
                         placeholder="Please specify the reason for rejection..."
                         class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancelBtn" 
                        class="py-2 px-4 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="py-2 px-4 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors">
                    Reject Recipe
                </button>
            </div>
        </form>
    </div>
</div>

</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
<script>
// Modal elements
const rejectModal = document.getElementById('rejectModal');
const modalRecipeId = document.getElementById('modalRecipeId');
const rejectionReason = document.getElementById('rejection_reason');
const customReasonDiv = document.getElementById('customReasonDiv');
const customReason = document.getElementById('custom_reason');
const cancelBtn = document.getElementById('cancelBtn');
const rejectForm = document.getElementById('rejectForm');

// Function to open reject modal
function openRejectModal(recipeId) {
    modalRecipeId.value = recipeId;
    rejectionReason.value = '';
    customReason.value = '';
    customReasonDiv.classList.add('hidden');
    rejectModal.classList.remove('hidden');
    
    // Focus on the select dropdown
    rejectionReason.focus();
}

// Show/hide custom reason input based on selection
rejectionReason.addEventListener('change', function() {
    if (this.value === 'Other') {
        customReasonDiv.classList.remove('hidden');
        customReason.focus();
        customReason.required = true;
    } else {
        customReasonDiv.classList.add('hidden');
        customReason.required = false;
        customReason.value = '';
    }
});

// Close modal when cancel button is clicked
cancelBtn.addEventListener('click', function() {
    closeRejectModal();
});

// Close modal when clicking outside
rejectModal.addEventListener('click', function(e) {
    if (e.target === rejectModal) {
        closeRejectModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !rejectModal.classList.contains('hidden')) {
        closeRejectModal();
    }
});

// Function to close modal
function closeRejectModal() {
    rejectModal.classList.add('hidden');
    rejectionReason.value = '';
    customReason.value = '';
    customReasonDiv.classList.add('hidden');
    customReason.required = false;
}

// Form validation before submit
rejectForm.addEventListener('submit', function(e) {
    const reason = rejectionReason.value;
    const customReasonValue = customReason.value.trim();
    
    if (!reason) {
        e.preventDefault();
        alert('Please select a reason for rejection.');
        rejectionReason.focus();
        return false;
    }
    
    if (reason === 'Other' && !customReasonValue) {
        e.preventDefault();
        alert('Please provide a custom reason for rejection.');
        customReason.focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Rejecting...';
    submitBtn.disabled = true;
    
    // Re-enable button after a delay in case of errors
    setTimeout(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>

<script>
document.querySelectorAll('form[action="approve_recipe.php"]').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch('approve_recipe.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      showToast(data.message, data.status === 'success' ? 'green' : 'red');
      if (data.status === 'success') {
        setTimeout(() => location.reload(), 1500); // slight delay for smooth reload
      }
    })
    .catch(err => console.error('Error:', err));
  });
});

// ✅ Toast function (replaces alert)
function showToast(message, color = 'green') {
  const toast = document.createElement('div');
  toast.textContent = message;
  toast.className = `fixed bottom-6 right-6 bg-${color}-500 text-white px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300 z-50`;
  document.body.appendChild(toast);

  // Fade in
  setTimeout(() => toast.style.opacity = '1', 50);

  // Fade out and remove
  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 2000);
}
</script>


</main>
</body>
</html>
