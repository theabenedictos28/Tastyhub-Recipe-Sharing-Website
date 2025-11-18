<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

// Get filter values from GET
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// --- Pagination Setup ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5; // feedback per page
$offset = ($page - 1) * $perPage;

// Count total feedback for pagination (with filters applied)
$countSql = "SELECT COUNT(*) as total FROM feedback WHERE 1";

// Apply filters to count query
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $countSql .= " AND (name LIKE '%$search_safe%' OR email LIKE '%$search_safe%' OR message LIKE '%$search_safe%')";
}
if (!empty($date_from)) {
    $countSql .= " AND created_at >= '$date_from'";
}
if (!empty($date_to)) {
    $countSql .= " AND created_at <= '$date_to'";
}

$countResult = $conn->query($countSql);
$totalFeedback = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalFeedback / $perPage);

// Base query
$sql_feedback = "SELECT * FROM feedback WHERE 1";

// Add filters
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql_feedback .= " AND (name LIKE '%$search_safe%' OR email LIKE '%$search_safe%' OR message LIKE '%$search_safe%')";
}
if (!empty($date_from)) {
    $sql_feedback .= " AND created_at >= '$date_from'";
}
if (!empty($date_to)) {
    $sql_feedback .= " AND created_at <= '$date_to'";
}

// Order and pagination
$sql_feedback .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$result_feedback = $conn->query($sql_feedback);

// Calculate range for "Showing X to Y of Z"
$start = $offset + 1;
$end = min($offset + $perPage, $totalFeedback);
?>




<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Dashboard - Feedback</title>
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
        .btn-danger {
            background-color: #fef2f2;
            color: #ef4444;
        }
        .btn-danger:hover {
            background-color: #fee2e2;
        }
        .modal {
            display: none;
        }
        .modal.open {
            display: flex;
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
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin.php">
<span class="material-icons mr-3">dashboard</span>
<span>Dashboard</span>
</a>
</li>
<li class="mb-4">
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
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_comments.php">
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
<a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_archived.php">
<span class="material-icons mr-3">archive</span>
<span>Archived</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg active-link" href="admin_feedback.php">
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
<main class="main-content flex-1 p-8 overflow-y-auto bg-gray-100">
  <header class="flex justify-between items-center mb-8">
    <div>
      <h2 class="text-3xl font-bold text-gray-800">User Feedback</h2>
      <p class="text-gray-500">View and manage feedback submitted by users.</p>
    </div>
    <div class="flex items-center">
      <span class="mr-4 text-gray-600">Admin User</span>
      <img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
    </div>
  </header>

<div class="container mx-auto max-w-9xl px-3">
    <div class="card p-6">
      <!-- Filter Form -->
     <form method="GET" class="p-4 border-b flex flex-wrap gap-4 items-end">
  <div class="flex-1 min-w-[200px]">
      <label class="block text-sm font-medium text-gray-700">Search</label>
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
             placeholder="Name, Email, Message"
             class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
    </div>
    <div class="flex-1 min-w-[150px]">
      <label class="block text-sm font-medium text-gray-700">From</label>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
             class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
          </div>
          <div class="flex-1 min-w-[150px]">
              <label class="block text-sm font-medium text-gray-700">To</label>
              <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                     class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
          </div>
          <div>
              <button type="submit" class="py-2 px-4 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium">Apply Filters</button>
          </div>
        </form>
      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-4 font-semibold text-gray-600">User</th>
              <th class="p-4 font-semibold text-gray-600">Message</th>
              <th class="p-4 font-semibold text-gray-600">Submitted On</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if($result_feedback->num_rows > 0): ?>
              <?php while($feedback = $result_feedback->fetch_assoc()): ?>
                <tr>
                  <td class="p-4 align-top">
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($feedback['name']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($feedback['email']); ?></p>
                  </td>
                  <td class="p-4 align-top max-w-md">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                  </td>
                    <td class="p-4 align-top text-sm text-gray-500">
                      <?php echo date('F d Y', strtotime($feedback['created_at'])); ?>
                    </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="p-4 text-center text-gray-500">No feedback submitted yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="p-4 border-t flex justify-between items-center">
   <span class="text-sm text-gray-600">
    <?php if ($totalFeedback > 0): ?>
        Showing <?= $end ?> of <?= $totalFeedback ?> feedback
    <?php else: ?>
        No feedback found
    <?php endif; ?>
</span>


    <div class="inline-flex items-center space-x-2">
        <!-- Prev -->
        <a href="?page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
           class="p-2 rounded-md hover:bg-gray-100 <?= ($page <= 1) ? 'opacity-50 pointer-events-none' : '' ?>">
            <span class="material-icons text-gray-600">chevron_left</span>
        </a>

        <span class="text-sm font-medium text-gray-700">Page <?= $page ?> of <?= $totalPages ?></span>

        <!-- Next -->
        <a href="?page=<?= min($totalPages, $page + 1) ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
           class="p-2 rounded-md hover:bg-gray-100 <?= ($page >= $totalPages) ? 'opacity-50 pointer-events-none' : '' ?>">
            <span class="material-icons text-gray-600">chevron_right</span>
        </a>
    </div>
</div>

      </div>
    </div>
  </div>
</main>
  <!-- Confirmation Modal -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4" id="confirm-delete-modal">
  <div class="card p-8 rounded-lg max-w-md w-full mx-auto text-center relative">
    <button class="close-modal-btn absolute top-3 right-3 p-1 rounded-full hover:bg-gray-200">
      <span class="material-icons">close</span>
    </button>
    <div class="w-16 h-16 mx-auto flex items-center justify-center rounded-full mb-4 bg-red-500">
      <span class="material-icons text-4xl text-white">delete</span>
    </div>
    <h4 class="text-xl font-bold text-gray-800 mb-2">Delete Feedback</h4>
    <p class="text-gray-600 mb-6">Are you sure you want to delete this feedback? This action cannot be undone.</p>
    <div class="flex justify-center space-x-4">
      <button type="button" class="btn-secondary py-2 px-6 rounded-md cancel-delete-btn">Cancel</button>
      <button type="button" class="py-2 px-6 rounded-md bg-red-500 text-white hover:bg-red-600" id="confirm-delete-btn">Delete</button>
    </div>
  </div>
</div>

</body></html>