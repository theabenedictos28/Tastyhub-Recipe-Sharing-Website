<?php
require 'db.php';

// Get filter parameters
$filter_status = isset($_GET['filter_status']) && $_GET['filter_status'] != 'All' ? $_GET['filter_status'] : '';
$filter_reason = isset($_GET['filter_reason']) && $_GET['filter_reason'] != 'All' ? $_GET['filter_reason'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Date (Newest)';

// Pagination settings
$limit = 5; // Reports per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($filter_status) {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter_status;
}

if ($filter_reason) {
    $where_conditions[] = "r.reason = ?";
    $params[] = $filter_reason;
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Build ORDER BY clause
$order_by = "ORDER BY r.created_at DESC";
if ($sort_by === 'Date (Oldest)') {
    $order_by = "ORDER BY r.created_at ASC";
}

// Get total count of reports with filters
$count_sql = "
    SELECT COUNT(*) as total 
    FROM reports r
    JOIN users u1 ON r.reporting_user_id = u1.id
    JOIN users u2 ON r.reported_user_id = u2.id
    $where_clause
";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_sql);
}

$total_reports = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reports / $limit);

// Fetch reports with filters and pagination
$sql = "
    SELECT r.*, 
           u1.username AS reporter_name, u1.email AS reporter_email,
           u2.username AS reported_name, u2.email AS reported_email
    FROM reports r
    JOIN users u1 ON r.reporting_user_id = u1.id
    JOIN users u2 ON r.reported_user_id = u2.id
    $where_clause
    $order_by
    LIMIT $limit OFFSET $offset
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Calculate display range
$start_item = $total_reports > 0 ? $offset + 1 : 0;
$end_item = min($offset + $limit, $total_reports);

// Build URL parameters for pagination links
function buildUrlParams($exclude = []) {
    global $filter_status, $filter_reason, $sort_by;
    $params = [];
    
    if ($filter_status && !in_array('filter_status', $exclude)) {
        $params[] = 'filter_status=' . urlencode($filter_status);
    }
    if ($filter_reason && !in_array('filter_reason', $exclude)) {
        $params[] = 'filter_reason=' . urlencode($filter_reason);
    }
    if ($sort_by && !in_array('sort_by', $exclude)) {
        $params[] = 'sort_by=' . urlencode($sort_by);
    }
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Dashboard - Reports</title>
        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-resolved {
            background-color: #ecfdf5;
            color: #10b981;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-dismissed {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .sidebar-submenu {
            display: none;
            padding-left: 1rem;
        }
        .sidebar-item.open .sidebar-submenu {
            display: block;
        }
        .sidebar-item>a {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        input[type="radio"]:checked+label {
            color: #ff6f61;
            font-weight: 500;
        }
        .details-row {
            display: none;
        }
        .details-row.open {
            display: table-row;
        }
        .modal {
            display: none;
        }
        .modal.open {
            display: flex;
        }
        .pagination-btn {
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background-color: white;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .pagination-btn:hover:not(.disabled):not(.active) {
            background-color: #f3f4f6;
            border-color: #9ca3af;
        }
        .pagination-btn.active {
            background-color: #ff6f61;
            border-color: #ff6f61;
            color: white;
        }
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
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
<a class="flex items-center p-3 rounded-lg active-link" href="admin_reports.php">
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
<h2 class="text-3xl font-bold text-gray-800">Reports</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<div class="card p-6 mb-8">
<form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<div>
<label class="block text-sm font-medium text-gray-700 mb-1" for="filter-status">Filter by Status</label>
<select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
        id="filter-status" name="filter_status">
<option value="All" <?= !$filter_status ? 'selected' : '' ?>>All</option>
<option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
<option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
<option value="Dismissed" <?= $filter_status === 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 mb-1" for="filter-type">Filter by Reason</label>
<select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
        id="filter-type" name="filter_reason">
<option value="All" <?= !$filter_reason ? 'selected' : '' ?>>All</option>
<option value="Inappropriate Content" <?= $filter_reason === 'Inappropriate Content' ? 'selected' : '' ?>>Inappropriate Content</option>
<option value="Spam" <?= $filter_reason === 'Spam' ? 'selected' : '' ?>>Spam</option>
<option value="Harassment" <?= $filter_reason === 'Harassment' ? 'selected' : '' ?>>Harassment</option>
<option value="Misinformation" <?= $filter_reason === 'Misinformation' ? 'selected' : '' ?>>Misinformation</option>
<option value="Other" <?= $filter_reason === 'Other' ? 'selected' : '' ?>>Other</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 mb-1" for="sort-by">Sort by</label>
<select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
        id="sort-by" name="sort_by">
<option value="Date (Newest)" <?= $sort_by === 'Date (Newest)' ? 'selected' : '' ?>>Date (Newest)</option>
<option value="Date (Oldest)" <?= $sort_by === 'Date (Oldest)' ? 'selected' : '' ?>>Date (Oldest)</option>
</select>
</div>
<div class="flex items-end space-x-2">
<button type="submit" class="flex-1 btn-primary py-2 px-4 rounded-md flex items-center text-sm justify-center">
<span class="material-icons mr-2 text-m">filter_list</span> Apply Filters
</button>
<button type="button" onclick="clearFilters()" class="btn-secondary py-2 px-3 rounded-md flex items-center justify-center">
<span class="material-icons mr-1">clear</span>
</button>
</div>
</form>

<?php if ($filter_status || $filter_reason || $sort_by !== 'Date (Newest)'): ?>
<div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
<div class="flex items-center justify-between">
<div class="flex items-center space-x-2">
<span class="material-icons text-blue-600 text-sm">filter_alt</span>
<span class="text-sm font-medium text-blue-800">Active Filters:</span>
<div class="flex flex-wrap gap-2">
<?php if ($filter_status): ?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
Status: <?= htmlspecialchars($filter_status) ?>
</span>
<?php endif; ?>
<?php if ($filter_reason): ?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
Reason: <?= htmlspecialchars($filter_reason) ?>
</span>
<?php endif; ?>
<?php if ($sort_by !== 'Date (Newest)'): ?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
Sort: <?= htmlspecialchars($sort_by) ?>
</span>
<?php endif; ?>
</div>
</div>
<a href="?" class="text-sm text-blue-600 hover:text-blue-800">Clear all</a>
</div>
</div>
<?php endif; ?>
</div>

<div class="card overflow-x-auto">
<table class="w-full whitespace-nowrap">
<thead class="bg-gray-50">
<tr>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reporter</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reported User</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
<div class="flex items-center"> Date <span class="material-icons text-sm ml-1 text-gray-400">arrow_downward</span>
</div>
</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Details</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
        // Format date
        $date = date("M d, Y", strtotime($row['created_at']));

        // Status badge
        $statusClass = "status-pending";
        if ($row['status'] === "Resolved") $statusClass = "status-resolved";
        if ($row['status'] === "Dismissed") $statusClass = "status-dismissed";
        ?>
<tr class="report-row">
    <td class="p-3">
        <div>
            <div class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($row['reporter_name']) ?></div>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($row['reporter_email']) ?></div>
        </div>
    </td>
    <td class="p-3">
        <div>
            <div class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($row['reported_name']) ?></div>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($row['reported_email']) ?></div>
        </div>
    </td>
    <td class="p-3 text-sm text-gray-500"><?= $date ?></td>
    <td class="p-3">
        <span class="<?= $statusClass ?>"><?= $row['status'] ?></span>
    </td>
    <td class="p-3">
        <button 
            class="view-details-btn py-1 px-3 rounded-lg bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 text-xs flex items-center" 
            data-modal-target="#report-modal-<?= $row['id'] ?>">
            <span class="material-icons text-sm mr-1">visibility</span> View
        </button>
    </td>
<td class="p-3 space-x-1">
    <?php if ($row['status'] === "Pending"): ?>
        <!-- Approve Button -->
        <button type="button"
            onclick="openConfirmModal(
                'update_report.php',
                { report_id: '<?= $row['id'] ?>', status: 'Resolved', page: '<?= $page ?>' },
                'Approve Report',
                'Are you sure you want to approve this report?',
                'Approve',
                'bg-green-500 hover:bg-green-600'
            )"
            class="py-1 px-2 rounded-lg bg-green-100 text-green-700 font-medium hover:bg-green-200 text-xs">
            Approve
        </button>

        <!-- Reject Button -->
        <button type="button"
            onclick="openConfirmModal(
                'update_report.php',
                { report_id: '<?= $row['id'] ?>', status: 'Dismissed', page: '<?= $page ?>' },
                'Reject Report',
                'Are you sure you want to reject this report?',
                'Reject',
                'bg-red-500 hover:bg-red-600'
            )"
            class="py-1 px-2 rounded-lg bg-red-100 text-red-700 font-medium hover:bg-red-200 text-xs">
            Reject
        </button>

    <?php elseif ($row['status'] === "Resolved"): ?>
        <span class="text-xs text-green-600 italic">Approved</span>
    <?php else: ?>
        <span class="text-xs text-red-600 italic">Rejected</span>
    <?php endif; ?>
</td>

</tr>

<!-- Confirmation Modal  -->
<div id="confirmModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-xl w-11/12 max-w-md p-6 text-center transform scale-95 opacity-0 transition-all duration-300" id="confirmModalBox">
    
    <!-- Title -->
    <h2 id="confirmTitle" class="text-lg font-semibold text-gray-800 mb-2">Confirm Action</h2>
    
    <!-- Message -->
    <p id="confirmMessage" class="text-gray-600 mb-6">Are you sure?</p>
    
    <!-- Buttons -->
    <form id="confirmForm" method="POST" class="flex justify-center gap-3">
      <!-- Hidden inputs will be injected here -->
      <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 rounded-lg bg-gray-300 text-gray-800 hover:bg-gray-400">
        Cancel
      </button>
      <button id="confirmSubmit" type="submit" class="px-4 py-2 rounded-lg text-white">
        Confirm
      </button>
    </form>
  </div>
</div>


<!-- Modal for details -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4" id="report-modal-<?= $row['id'] ?>">
    <div class="card p-8 rounded-lg max-w-2xl w-full mx-auto">
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Report Details</h3>
            <button class="close-modal-btn p-1 rounded-full hover:bg-gray-200">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div>
            <h4 class="font-semibold text-lg text-gray-800 mb-2">Reason for Report</h4>
            <p class="text-base text-gray-600 mb-6">
            <?php if ($row['reason'] === "Other" && !empty($row['custom_reason'])): ?>
                <?= htmlspecialchars($row['custom_reason']) ?>
            <?php else: ?>
                <?= htmlspecialchars($row['reason']) ?>
                <?php if (!empty($row['custom_reason'])): ?>
                    - <?= htmlspecialchars($row['custom_reason']) ?>
                <?php endif; ?>
            <?php endif; ?>
            </p>
            
            <h4 class="font-semibold text-lg text-gray-800 mb-2">Proof</h4>
            <?php if ($row['proof_path']): ?>
                <img src="<?= htmlspecialchars($row['proof_path']) ?>" class="w-full max-h-96 rounded-md object-contain">
            <?php else: ?>
                <div class="w-full h-64 rounded-md bg-gray-200 flex items-center justify-center">
                    <span class="text-lg text-gray-500">No Proof Provided</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="p-6 text-center text-gray-500">No reports found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<!-- Enhanced Pagination -->
<div class="p-4 border-t flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
    <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600">
            Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_reports ?> reports
        </span>
        <span class="text-xs text-gray-500">
            (<?= $limit ?> per page)
        </span>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center space-x-1">
        <!-- First Page -->
        <?php if ($page > 1): ?>
            <a href="?page=1<?= buildUrlParams() ?>" class="pagination-btn">
                <span class="material-icons text-sm">first_page</span>
            </a>
        <?php endif; ?>
        
        <!-- Previous Page -->
        <a href="<?= $page > 1 ? '?page=' . ($page - 1) . buildUrlParams() : '#' ?>" 
           class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">
            <span class="material-icons text-sm">chevron_left</span>
        </a>
        
        <!-- Page Numbers -->
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
            <a href="?page=1<?= buildUrlParams() ?>" class="pagination-btn">1</a>
            <?php if ($start_page > 2): ?>
                <span class="pagination-btn disabled">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?= $i ?><?= buildUrlParams() ?>" 
               class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
                <span class="pagination-btn disabled">...</span>
            <?php endif; ?>
            <a href="?page=<?= $total_pages ?><?= buildUrlParams() ?>" class="pagination-btn"><?= $total_pages ?></a>
        <?php endif; ?>
        
        <!-- Next Page -->
        <a href="<?= $page < $total_pages ? '?page=' . ($page + 1) . buildUrlParams() : '#' ?>" 
           class="pagination-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <span class="material-icons text-sm">chevron_right</span>
        </a>
        
        <!-- Last Page -->
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $total_pages ?><?= buildUrlParams() ?>" class="pagination-btn">
                <span class="material-icons text-sm">last_page</span>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Page Info -->
    <div class="text-sm text-gray-600">
        Page <?= $page ?> of <?= $total_pages ?>
    </div>
    <?php endif; ?>
</div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-details-btn');
    const closeButtons = document.querySelectorAll('.close-modal-btn');
    const modals = document.querySelectorAll('.modal');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalTarget;
            const modal = document.querySelector(modalId);
            if (modal) {
                modal.classList.add('open');
            }
        });
    });
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('open');
            }
        });
    });
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
            }
        });
    });
});

// Clear filters function
function clearFilters() {
    window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
}

// Auto-submit form when filters change (optional - for better UX)
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('#filter-status, #filter-type, #sort-by');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit form when filter changes
            // Uncomment the next line if you want instant filtering without clicking "Apply Filters"
            // this.form.submit();
        });
    });
});
</script>
<script>
function openConfirmModal(action, data, title, message, buttonText, buttonClass) {
  // Update modal content
  document.getElementById('confirmTitle').innerText = title;
  document.getElementById('confirmMessage').innerText = message;

  // Update form
  const form = document.getElementById('confirmForm');
  form.action = action;
  form.innerHTML = ''; // clear previous

  // Add hidden inputs
  for (const key in data) {
    if (data.hasOwnProperty(key)) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = data[key];
      form.appendChild(input);
    }
  }


  // Confirm button
  const confirmBtn = document.getElementById('confirmSubmit');
  confirmBtn.innerText = buttonText;
  confirmBtn.className = `px-4 py-2 rounded-lg text-white ${buttonClass}`;

  // Show modal with animation
  const modal = document.getElementById('confirmModal');
  const modalBox = document.getElementById('confirmModalBox');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => {
    modalBox.classList.remove('scale-95', 'opacity-0');
    modalBox.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeConfirmModal() {
  const modal = document.getElementById('confirmModal');
  const modalBox = document.getElementById('confirmModalBox');
  modalBox.classList.add('scale-95', 'opacity-0');
  modalBox.classList.remove('scale-100', 'opacity-100');
  setTimeout(() => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }, 200);
}

// Close when clicking outside
document.getElementById('confirmModal').addEventListener('click', function(e) {
  if (e.target === this) closeConfirmModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === "Escape") closeConfirmModal();
});
</script>


</body>
</html>