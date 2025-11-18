<?php
include 'db.php'; // DB connection

// Get filter parameters
$filter_status = isset($_GET['filter_status']) && $_GET['filter_status'] != 'All' ? $_GET['filter_status'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Date (Newest)';

// Pagination settings
$limit = 5; // Appeals per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($filter_status) {
    $where_conditions[] = "a.status = ?";
    $params[] = $filter_status;
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Build ORDER BY clause
$order_by = "ORDER BY a.appeal_date DESC";
if ($sort_by === 'Date (Oldest)') {
    $order_by = "ORDER BY a.appeal_date ASC";
}

// Get total count of appeals with filters
$count_sql = "
    SELECT COUNT(*) as total 
    FROM appeals a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN reports r ON a.report_id = r.id
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

$total_appeals = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_appeals / $limit);

// Fetch appeals with filters and pagination
$sql = "
    SELECT 
        a.id AS appeal_id, 
        a.appeal_message, 
        a.appeal_date, 
        a.status AS appeal_status,
        a.appeal_proof,
        u.username, 
        u.email,
        r.reason AS report_reason, 
        r.custom_reason AS report_comment, 
        r.status AS report_status,
        r.proof_path AS report_proof,
        r.created_at AS report_date
    FROM appeals a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN reports r ON a.report_id = r.id
    $where_clause
    $order_by
    LIMIT $limit OFFSET $offset
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $appeals_result = $stmt->get_result();
} else {
    $appeals_result = $conn->query($sql);
}

// Calculate display range
$start_item = $total_appeals > 0 ? $offset + 1 : 0;
$end_item = min($offset + $limit, $total_appeals);

// Build URL parameters for pagination links
function buildUrlParams($exclude = []) {
    global $filter_status, $sort_by;
    $params = [];
    
    if ($filter_status && !in_array('filter_status', $exclude)) {
        $params[] = 'filter_status=' . urlencode($filter_status);
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
<title>Admin Dashboard - Appeals</title>
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
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-approved, .action-accepted {
            background-color: #ecfdf5;
            color: #10b981;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-rejected, .action-rejected {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 4px 10px;
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
        .dialog {
            display: none;
        }
        .dialog.open {
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
<a class="flex items-center p-3 rounded-lg inactive-link" href="admin_reports.php">
<span class="material-icons mr-3">flag</span>
<span>Reports</span>
</a>
</li>
<li class="mb-4">
<a class="flex items-center p-3 rounded-lg active-link" href="admin_appeals.php">
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
<h2 class="text-3xl font-bold text-gray-800">Appeals</h2>
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
<option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
<option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
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
<div class="flex items-end col-span-2 space-x-2">
<button type="submit" class="flex-1 md:flex-none btn-primary py-2 px-4 rounded-md flex items-center text-sm justify-center">
<span class="material-icons mr-2">filter_list</span> Apply Filters
</button>
<button type="button" onclick="clearFilters()" class="btn-secondary py-2 px-4 rounded-md flex text-sm items-center justify-center">
<span class="material-icons mr-1">clear</span> Clear
</button>
</div>
</form>

<?php if ($filter_status || $sort_by !== 'Date (Newest)'): ?>
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
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Appellant</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Appeal Date</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Details</th>
<th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-200">
<?php if ($appeals_result && $appeals_result->num_rows > 0): ?>
    <?php while($appeal = $appeals_result->fetch_assoc()): ?>
<tr class="report-row" data-id="<?php echo $appeal['appeal_id']; ?>">
    <td class="p-4">
        <div>
            <div class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($appeal['username']); ?></div>
            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($appeal['email']); ?></div>
        </div>
    </td>
    <td class="p-4 text-sm text-gray-500"><?php echo date('M d, Y', strtotime($appeal['appeal_date'])); ?></td>
    <td class="p-4">
        <?php
            $statusClass = match($appeal['appeal_status']) {
                'Pending' => 'status-pending',
                'Approved' => 'status-approved',
                'Rejected' => 'status-rejected',
                default => 'status-pending'
            };
        ?>
        <span class="<?php echo $statusClass; ?>"><?php echo $appeal['appeal_status']; ?></span>
    </td>
    <td class="p-4">
        <button class="py-1 px-3 rounded-lg bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 text-xs flex items-center view-details-btn" 
            data-appeal='<?php echo json_encode($appeal); ?>'>
            <span class="material-icons text-sm mr-1">visibility</span> View
        </button>
    </td>

    <td class="p-4 space-x-2 action-cell">
    <?php if ($appeal['appeal_status'] === 'Pending'): ?>
        <button class="py-1 px-3 rounded-lg bg-green-100 text-green-700 font-medium hover:bg-green-200 text-xs action-btn" data-action="accept">Accept</button>
        <button class="py-1 px-3 rounded-lg bg-red-100 text-red-700 font-medium hover:bg-red-200 text-xs action-btn" data-action="reject">Reject</button>
    <?php elseif ($appeal['appeal_status'] === 'Approved'): ?>
        <span class="text-green-600 text-xs italic">Accepted</span>
    <?php elseif ($appeal['appeal_status'] === 'Rejected'): ?>
        <span class="text-red-600 text-xs italic">Rejected</span>
    <?php endif; ?>
    </td>
</tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="5" class="p-6 text-center text-gray-500">No appeals found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<!-- Enhanced Pagination -->
<div class="p-4 border-t flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
    <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600">
            Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_appeals ?> appeals
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

<div class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4" id="appeal-modal">
<div class="card p-8 rounded-lg max-w-4xl w-full mx-auto max-h-[90vh] overflow-y-auto relative">
<div class="flex justify-between items-start mb-6">
<h3 class="text-2xl font-bold text-gray-800">Appeal Details</h3>
<button class="close-modal-btn p-1 rounded-full hover:bg-gray-200">
<span class="material-icons">close</span>
</button>
</div>
<div class="space-y-6">
<div class="bg-gray-50 p-6 rounded-lg">
<h4 class="font-semibold text-lg text-gray-800 mb-4 border-b pb-2">Original Report</h4>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<p class="text-sm font-medium text-gray-500">Report Reason</p>
<p class="text-base text-gray-700 font-semibold mb-4"></p>
</div>
<div>
<p class="text-sm font-medium text-gray-500 mb-2">Reporter's Proof</p>
<img id="report-proof-img" alt="Proof image" class="w-full h-48 object-cover rounded-md" src="placeholder.jpg"/>
</div>
</div>
</div>
<div class="p-6 rounded-lg border">
<h4 class="font-semibold text-lg text-gray-800 mb-4 border-b pb-2">User's Appeal</h4>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<p class="text-sm font-medium text-gray-500">Appeal Message</p>
<p class="text-base text-gray-600"></p>
</div>
<div>
<p class="text-sm font-medium text-gray-500 mb-2">Appellant's Proof</p>
<img id="appeal-proof-img" alt="Proof image" class="w-full h-48 object-cover rounded-md" src="placeholder.jpg"/>
</div>
</div>
</div>
</div>

<div class="dialog absolute inset-0 bg-white bg-opacity-90 items-center justify-center p-8 rounded-lg" id="confirmation-dialog">
<div class="text-center max-w-sm">
<div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4" id="dialog-icon">
<span class="material-icons text-4xl text-white"></span>
</div>
<h4 class="text-xl font-bold text-gray-800 mb-2" id="dialog-title"></h4>
<p class="text-gray-600 mb-6" id="dialog-message"></p>
<div class="flex justify-center space-x-4">
<button class="btn-secondary py-2 px-6 rounded-md cancel-btn">Cancel</button>
<button class="py-2 px-6 rounded-md text-white" id="confirm-action-btn">Confirm</button>
</div>
</div>
</div>
</div>
</div>

<!-- Confirmation Modal -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4" id="confirmation-modal">
  <div class="card p-8 rounded-lg max-w-md w-full mx-auto text-center relative">
    <button class="close-modal-btn absolute top-3 right-3 p-1 rounded-full hover:bg-gray-200">
      <span class="material-icons">close</span>
    </button>
    <div class="w-16 h-16 mx-auto flex items-center justify-center rounded-full mb-4" id="confirm-icon">
      <span class="material-icons text-4xl text-white">help</span>
    </div>
    <h4 class="text-xl font-bold text-gray-800 mb-2" id="confirm-title">Confirm Action</h4>
    <p class="text-gray-600 mb-6" id="confirm-message">Are you sure you want to proceed?</p>
    <div class="flex justify-center space-x-4">
      <button class="btn-secondary py-2 px-6 rounded-md cancel-confirm-btn">Cancel</button>
      <button class="py-2 px-6 rounded-md text-white bg-green-500 hover:bg-green-600" id="confirm-btn">Confirm</button>
    </div>
  </div>
</div>

<script>
    document.querySelectorAll('.view-details-btn').forEach(button => {
    button.addEventListener('click', function() {
        const appeal = JSON.parse(this.dataset.appeal);
        const modal = document.getElementById('appeal-modal');
        modal.classList.add('open');

        modal.querySelector('.card h3').textContent = `Appeal Details - ${appeal.username}`;
  // Display report reason, but hide "Other" and show only custom_reason if present
        let reportText = '';
        if (appeal.report_reason === 'Other' && appeal.report_comment && appeal.report_comment.trim() !== '') {
            reportText = appeal.report_comment;
        } else if (appeal.report_reason) {
            reportText = appeal.report_reason;
            if (appeal.report_comment && appeal.report_comment.trim() !== '') {
                reportText += ' - ' + appeal.report_comment;
            }
        } else {
            reportText = 'No report reason';
        }

        modal.querySelector('p.text-base.text-gray-700').textContent = reportText;

        // Update appeal message
        modal.querySelector('p.text-base.text-gray-600').textContent = appeal.appeal_message;

        // Update report proof
        const reportImg = document.getElementById('report-proof-img');
        if (appeal.report_proof && appeal.report_proof.trim() !== "") {
            reportImg.src = appeal.report_proof;
            reportImg.style.display = "block"; // show if exists
        } else {
            reportImg.style.display = "none";  // hide if no image
        }

        // Update appeal proof
        const appealImg = document.getElementById('appeal-proof-img');
        const appealLabel = appealImg.previousElementSibling; // assumes label is just above img
        if (appeal.appeal_proof && appeal.appeal_proof.trim() !== "") {
            appealImg.src = appeal.appeal_proof;
            appealImg.style.display = "block";
            appealLabel.style.display = "block";
        } else {
            appealImg.style.display = "none";
            appealLabel.style.display = "none";
        }
            });
        });

function setupCloseModalButtons() {
    // Select all buttons with the class 'close-modal-btn'
    const closeButtons = document.querySelectorAll('.close-modal-btn');

    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Find the closest parent modal and hide it
            const modal = button.closest('.modal');
            if (modal) {
                modal.classList.remove('open'); // or modal.style.display = 'none';
            }
        });
    });
}

// Call the function after the DOM is loaded
document.addEventListener('DOMContentLoaded', setupCloseModalButtons);

document.querySelectorAll('.action-btn').forEach(button => {
  button.addEventListener('click', function() {
    const row = button.closest('tr');
    const appealId = row.dataset.id;
    const action = button.dataset.action;

    const modal = document.getElementById('confirmation-modal');
    const confirmBtn = document.getElementById('confirm-btn');
    const title = document.getElementById('confirm-title');
    const message = document.getElementById('confirm-message');
    const icon = document.getElementById('confirm-icon').firstElementChild;

    // Set modal content
    title.textContent = action === "accept" ? "Accept Appeal" : "Reject Appeal";
    message.textContent = `Are you sure you want to ${action} this appeal?`;
    icon.textContent = action === "accept" ? "check_circle" : "cancel";
    icon.className = `material-icons text-4xl ${action === "accept" ? "text-green-500" : "text-red-500"}`;

    modal.classList.add("open");

    // Remove old click listeners to prevent stacking
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    // Confirm action
    newConfirmBtn.addEventListener("click", () => {
      fetch('process_appeals.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `appeal_id=${appealId}&action=${action}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const statusSpan = row.querySelector('td:nth-child(3) span');
          if (data.status === 'Approved') {
            statusSpan.textContent = 'Approved';
            statusSpan.className = 'status-approved';
            row.querySelector('.action-cell').innerHTML = '<span class="text-green-600 text-xs italic">Accepted</span>';
          } else if (data.status === 'Rejected') {
            statusSpan.textContent = 'Rejected';
            statusSpan.className = 'status-rejected';
            row.querySelector('.action-cell').innerHTML = '<span class="text-red-600 text-xs italic">Rejected</span>';
          }
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => console.error(err))
      .finally(() => modal.classList.remove("open"));
    });

    // Cancel/close
    modal.querySelector(".cancel-confirm-btn").onclick = () => modal.classList.remove("open");
    modal.querySelector(".close-modal-btn").onclick = () => modal.classList.remove("open");
  });
});


// Clear filters function
function clearFilters() {
    window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
}

// Auto-submit form when filters change (optional - for better UX)
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('#filter-status, #sort-by');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit form when filter changes
            // Uncomment the next line if you want instant filtering without clicking "Apply Filters"
            // this.form.submit();
        });
    });
});
</script>

</body>
</html>