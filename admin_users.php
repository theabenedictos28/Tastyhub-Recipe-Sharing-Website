<?php
require 'db.php';

$perPage = 10; // number of users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Count total users
$countSql = "SELECT COUNT(*) as total FROM users";
if ($search) {
    $countSql .= " WHERE username LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($countSql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $totalUsers = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $totalUsers = $conn->query($countSql)->fetch_assoc()['total'];
}

$totalPages = ceil($totalUsers / $perPage);

$sql = "SELECT id, username, email, total_violations, accstatus, created_at 
        FROM users 
        WHERE username != 'admin'";

if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";


$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bind_param("ssii", $like, $like, $perPage, $offset);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Dashboard - Users</title>
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
        .status-active {
            background-color: #ecfdf5;
            color: #10b981;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-suspended {
            background-color: #fffbeb;
            color: #f59e0b;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-banned {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-warning {
            background-color: #fef3c7;
            color: #d97706;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .sidebar-submenu {
            display: none;
            padding-left: 1rem;
        }
        .sidebar-item.open .sidebar-submenu {
            display: block;
        }
        .sidebar-item > a {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        input[type="radio"]:checked+label {
            color: #ff6f61;
            font-weight: 500;
        }
        .search-container {
            position: relative;
        }
        .search-results-info {
            background-color: #e0f2fe;
            border: 1px solid #81d4fa;
            color: #01579b;
        }
        .clear-search {
            color: #01579b;
            text-decoration: underline;
            cursor: pointer;
        }
        .clear-search:hover {
            color: #0277bd;
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
<a class="flex items-center p-3 rounded-lg active-link" href="admin_users.php">
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
<h2 class="text-3xl font-bold text-gray-800">Users</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<!-- Search Section -->
<div class="mb-8">
<form method="GET" action="" class="search-container">
<div class="relative">
<span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-2xl">search</span>
<input class="w-full pl-14 pr-24 py-3 text-lg border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent shadow-sm" 
       id="search-user-main" 
       name="search" 
       value="<?= htmlspecialchars($search) ?>"
       placeholder="Search for users by name, email..." 
       type="text"/>
<div class="absolute right-2 top-1/2 -translate-y-1/2 flex space-x-2">
<?php if ($search): ?>
    <a href="?" class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100" title="Clear search">
        <span class="material-icons text-xl">clear</span>
    </a>
<?php endif; ?>
<button type="submit" class="btn-primary py-2 px-4 rounded-lg font-medium">
    Search
</button>
</div>
</div>
</form>

<!-- Search Results Info -->
<?php if ($search): ?>
<div class="mt-4 p-3 search-results-info rounded-lg">
<div class="flex items-center justify-between">
<div class="flex items-center space-x-2">
<span class="material-icons text-sm">search</span>
<span class="text-sm font-medium">
    Search results for: "<strong><?= htmlspecialchars($search) ?></strong>"
</span>
<span class="text-sm">
    (<?= $totalUsers ?> result<?= $totalUsers != 1 ? 's' : '' ?> found)
</span>
</div>
<a href="?" class="text-sm clear-search">Clear search</a>
</div>
</div>
<?php endif; ?>
</div>

<div class="card overflow-hidden">
<table class="w-full">
<thead class="bg-gray-50">
<tr>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">User</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Email</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Violations</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Status</th>
<th class="p-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-200">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td class="p-4 whitespace-nowrap">
            <div class="flex items-center">
              <div>
                <div class="font-medium text-gray-800"><?= htmlspecialchars($row['username']) ?></div>
                <div class="text-sm text-gray-500">Joined <?= date("M d, Y", strtotime($row['created_at'])) ?></div>
              </div>
            </div>
          </td>
          <td class="p-4 whitespace-nowrap text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
          <td class="p-4 whitespace-nowrap">
            <div class="flex items-center text-red-500">
              <span class="material-icons text-base mr-1">report</span>
              <span class="font-semibold"><?= $row['total_violations'] ?></span>
            </div>
          </td>
          <td class="p-4 whitespace-nowrap">
            <?php
              $status = strtolower($row['accstatus']);
              $class = "status-active";
              if (strpos($status, "warning") !== false) $class = "status-warning";
              elseif (strpos($status, "suspend") !== false) $class = "status-suspended";
              elseif ($status === "banned") $class = "status-banned";
            ?>
            <span class="<?= $class ?>"><?= htmlspecialchars($row['accstatus']) ?></span>
          </td>
          <td class="p-4 whitespace-nowrap">
            <a href="admin_userdetails.php?id=<?= $row['id'] ?>" class="py-2 px-4 rounded-lg bg-orange-500 text-white font-medium hover:bg-orange-600 text-sm">Details</a>
          </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="5" class="p-6 text-center text-gray-500">
        <?php if ($search): ?>
            No users found matching "<?= htmlspecialchars($search) ?>". 
            <a href="?" class="text-orange-500 hover:underline">Show all users</a>
        <?php else: ?>
            No users found.
        <?php endif; ?>
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>

<div class="p-4 border-t flex justify-between items-center">
  <span class="text-sm text-gray-600">
    <?php if ($totalUsers > 0): ?>
        Showing <?= ($offset+1) ?> 
        to <?= min($offset+$perPage, $totalUsers-1) ?> 
        of <?= $totalUsers-1 ?> users
        <?= $search ? ' (filtered)' : '' ?>
    <?php else: ?>
        No users to display
    <?php endif; ?>
  </span>

  <?php if ($totalPages > 1): ?>
  <div class="inline-flex items-center space-x-2">
    <!-- First Page -->
    <?php if ($page > 1): ?>
        <a href="?page=1&search=<?= urlencode($search) ?>"
           class="p-2 rounded-md hover:bg-gray-100" title="First page">
            <span class="material-icons text-gray-600">first_page</span>
        </a>
    <?php endif; ?>

    <!-- Prev button -->
    <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>"
       class="p-2 rounded-md hover:bg-gray-100 <?= ($page <= 1 ? 'opacity-50 cursor-not-allowed pointer-events-none' : '') ?>">
      <span class="material-icons text-gray-600">chevron_left</span>
    </a>

    <!-- Page Numbers -->
    <?php
    $start_page = max(1, $page - 2);
    $end_page = min($totalPages, $page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
           class="px-3 py-2 rounded-md text-sm font-medium <?= $i == $page ? 'bg-orange-500 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <!-- Next button -->
    <a href="?page=<?= min($totalPages, $page+1) ?>&search=<?= urlencode($search) ?>"
       class="p-2 rounded-md hover:bg-gray-100 <?= ($page >= $totalPages ? 'opacity-50 cursor-not-allowed pointer-events-none' : '') ?>">
      <span class="material-icons text-gray-600">chevron_right</span>
    </a>

    <!-- Last Page -->
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>"
           class="p-2 rounded-md hover:bg-gray-100" title="Last page">
            <span class="material-icons text-gray-600">last_page</span>
        </a>
    <?php endif; ?>
  </div>
  
  <div class="text-sm text-gray-600">
    Page <?= $page ?> of <?= $totalPages ?>
  </div>
  <?php endif; ?>
</div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
        const link = item.querySelector('a');
        const submenu = item.querySelector('.sidebar-submenu');
        const icon = item.querySelector('a > span.material-icons:last-child');
        if (submenu) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                item.classList.toggle('open');
                if (icon) {
                    icon.classList.toggle('rotate-180');
                }
            });
        }
    });

    // Search functionality
    const searchInput = document.getElementById('search-user-main');
    const searchForm = searchInput.closest('form');
    
    // Submit search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchForm.submit();
        }
    });
    
    // Auto-focus search input if there's a search term
    if (searchInput.value.trim() !== '') {
        searchInput.focus();
        // Move cursor to end
        searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
    }
    
    // Highlight search terms in results
    const searchTerm = '<?= addslashes($search) ?>';
    if (searchTerm) {
        highlightSearchTerm(searchTerm);
    }
});

function highlightSearchTerm(term) {
    if (!term) return;
    
    const regex = new RegExp(`(${term})`, 'gi');
    const cells = document.querySelectorAll('tbody td');
    
    cells.forEach(cell => {
        if (cell.querySelector('a')) return; // Skip cells with links
        
        const text = cell.textContent;
        if (regex.test(text)) {
            cell.innerHTML = text.replace(regex, '<mark class="bg-yellow-200 rounded">$1</mark>');
        }
    });
}
</script>

</body></html>