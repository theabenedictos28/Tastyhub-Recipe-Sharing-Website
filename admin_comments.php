<?php
session_start();
require 'db.php'; // Include your database connection

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

$sql_reports = "
  SELECT r.*, 
         reporter.username AS reporter_name,
         reported.username AS reported_name,
         c.comment,
         recipe_name AS recipe_title
  FROM comments_reports r
  JOIN users reporter ON r.reporting_user_id = reporter.id
  JOIN users reported ON r.reported_user_id = reported.id
  JOIN comments c ON r.comment_id = c.id
  JOIN recipe ON r.recipe_id = recipe.id
  WHERE r.status != 'Dismissed'
  ORDER BY r.created_at DESC
";
// 🔹 Pagination setup
$perPage = 5; // how many rows per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Count total active reports
$countReports = $conn->query("SELECT COUNT(*) as total FROM comments_reports WHERE status != 'Dismissed'");
$totalReports = $countReports ? $countReports->fetch_assoc()['total'] : 0;
$totalPages = max(1, ceil($totalReports / $perPage));

// Count total dismissed reports
$countDismissed = $conn->query("SELECT COUNT(*) as total FROM comments_reports WHERE status = 'Dismissed'");
$totalDismissed = $countDismissed ? $countDismissed->fetch_assoc()['total'] : 0;
$totalDismissedPages = max(1, ceil($totalDismissed / $perPage));

// 🔹 Active Reports query with LIMIT
$sql_reports = "
  SELECT r.*, 
         reporter.username AS reporter_name,
         reported.username AS reported_name,
         c.comment,
         recipe_name AS recipe_title
  FROM comments_reports r
  JOIN users reporter ON r.reporting_user_id = reporter.id
  JOIN users reported ON r.reported_user_id = reported.id
  JOIN comments c ON r.comment_id = c.id
  JOIN recipe ON r.recipe_id = recipe.id
  WHERE r.status != 'Dismissed'
  ORDER BY r.created_at DESC
  LIMIT $perPage OFFSET $offset
";

// 🔹 Dismissed Reports query with LIMIT
$sql_dismissed = "
  SELECT r.*, 
         reporter.username AS reporter_name,
         reported.username AS reported_name,
         c.comment,
         recipe_name AS recipe_title
  FROM comments_reports r
  JOIN users reporter ON r.reporting_user_id = reporter.id
  JOIN users reported ON r.reported_user_id = reported.id
  JOIN comments c ON r.comment_id = c.id
  JOIN recipe ON r.recipe_id = recipe.id
  WHERE r.status = 'Dismissed'
  ORDER BY r.created_at DESC
  LIMIT $perPage OFFSET $offset
";
$sql_dismissed = "
  SELECT r.*, 
         reporter.username AS reporter_name,
         reported.username AS reported_name,
         c.comment,
         recipe_name AS recipe_title
  FROM comments_reports r
  JOIN users reporter ON r.reporting_user_id = reporter.id
  JOIN users reported ON r.reported_user_id = reported.id
  JOIN comments c ON r.comment_id = c.id
  JOIN recipe ON r.recipe_id = recipe.id
  WHERE r.status = 'Dismissed'
  ORDER BY r.created_at DESC
";



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
<a class="flex items-center p-3 rounded-lg active-link" href="admin_comments.php">
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
<h2 class="text-3xl font-bold text-gray-800">Reported Comments</h2>
<div class="flex items-center">
<span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
</div>
</header>

<!-- 🔹 Active Reports -->
<div class="card p-6 mb-10">
    <h3 class="text-xl font-semibold mb-4">Reports Table</h3>

    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reporter</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reported User</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Recipe</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Comment</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reason</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Date</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          <?php
          $result = $conn->query($sql_reports);
          if ($result && $result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($row['reporter_name']) ?></td>
              <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($row['reported_name']) ?></td>
              <td class="px-4 py-2 text-gray-800 text-xs"><?= htmlspecialchars($row['recipe_title']) ?></td>
                <td class="px-4 py-2 text-gray-600 italic">
                  <!-- Truncated Comment (clickable) -->
                  <span class="inline-block max-w-[150px] truncate cursor-pointer hover:text-blue-600 hover:underline"
                        onclick="openCommentModal('<?= htmlspecialchars(addslashes($row['comment'])) ?>')">
                    “<?= htmlspecialchars($row['comment']) ?>”
                  </span>
                </td>
<td class="px-4 py-2 text-gray-800">
  <?php if ($row['reason'] === 'Other' && !empty($row['custom_reason'])): ?>
    <span class="text-black-600 cursor-pointer hover:text-blue-600 hover:underline"
          onclick="openReasonModal('<?= htmlspecialchars(addslashes($row['custom_reason'])) ?>')">
      Other (view)
    </span>
  <?php else: ?>
    <?= htmlspecialchars($row['reason']) ?>
  <?php endif; ?>
</td>
              <td class="px-4 py-2 text-gray-500 text-xs"><?= date("m-d-Y", strtotime($row['created_at'])) ?></td>
<td class="px-4 py-2 flex gap-2">
  <!-- Delete Button -->
  <button type="button" 
          onclick="openConfirmModal('delete_commentreport.php', {comment_id: '<?= $row['comment_id'] ?>'}, 'Delete Comment', 'Are you sure you want to delete this comment? This action cannot be undone.', 'Delete', 'bg-red-500 hover:bg-red-600')"
          class="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 text-xs">
    Delete
  </button>

  <!-- Dismiss Button -->
  <button type="button" 
          onclick="openConfirmModal('dismiss_commentreport.php', {report_id: '<?= $row['id'] ?>'}, 'Dismiss Report', 'Are you sure you want to dismiss this report?', 'Dismiss', 'bg-gray-500 hover:bg-gray-600')"
          class="px-3 py-1 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-xs">
    Dismiss
  </button>
</td>


            </tr>
          <?php endwhile; else: ?>
            <tr>
              <td colspan="7" class="px-4 py-4 text-center text-gray-500">No reports found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- 🔹 Pagination for Active -->
    <div class="p-4 border-t flex justify-between items-center">
        <span class="text-sm text-gray-600">
        Showing <?= min($offset + $perPage, $totalReports) ?> of <?= $totalReports ?> reports
        </span>
        <div class="inline-flex items-center space-x-2">
            <a href="?page=<?= max(1, $page - 1) ?>&status=active"
               class="p-2 rounded-md hover:bg-gray-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-icons text-gray-600">chevron_left</span>
            </a>
            <span class="text-sm font-medium text-gray-700">Page <?= $page ?> of <?= $totalPages ?></span>
            <a href="?page=<?= min($totalPages, $page + 1) ?>&status=active"
               class="p-2 rounded-md hover:bg-gray-100 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-icons text-gray-600">chevron_right</span>
            </a>
        </div>
    </div>
</div>
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

<!-- 🔹 Dismissed Reports -->
<div class="card p-6">
    <h3 class="text-xl font-semibold mb-4">Dismissed Reports</h3>

    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reporter</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reported User</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Recipe</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Comment</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Reason</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Date</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          <?php
          $result2 = $conn->query($sql_dismissed);
          if ($result2 && $result2->num_rows > 0):
              while ($row = $result2->fetch_assoc()):
          ?>
            <tr>
              <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($row['reporter_name']) ?></td>
              <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($row['reported_name']) ?></td>
              <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($row['recipe_title']) ?></td>
                <td class="px-4 py-2 text-gray-600 italic">
                  <!-- Truncated Comment (clickable) -->
                  <span class="inline-block max-w-[150px] italic truncate cursor-pointer hover:text-blue-600 hover:underline"
                        onclick="openCommentModal('<?= htmlspecialchars(addslashes($row['comment'])) ?>')">
                    “<?= htmlspecialchars($row['comment']) ?>”
                  </span>
                </td>             
<td class="px-4 py-2 text-gray-800">
  <?php if ($row['reason'] === 'Other' && !empty($row['custom_reason'])): ?>
    <span class="text-black-600 cursor-pointer hover:text-blue-600 hover:underline"
          onclick="openReasonModal('<?= htmlspecialchars(addslashes($row['custom_reason'])) ?>')">
      Other (view)
    </span>
  <?php else: ?>
    <?= htmlspecialchars($row['reason']) ?>
  <?php endif; ?>
</td>
              <td class="px-4 py-2 text-gray-500"><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
              <td class="px-4 py-2">
                <span class="status-rejected">Dismissed</span>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr>
              <td colspan="7" class="px-4 py-4 text-center text-gray-500">No dismissed reports</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- 🔹 Pagination for Dismissed -->
    <div class="p-4 border-t flex justify-between items-center">
        <span class="text-sm text-gray-600">
        Showing <?= min($offset + $perPage, $totalDismissed) ?> of <?= $totalDismissed ?> reports
        </span>
        <div class="inline-flex items-center space-x-2">
            <a href="?page=<?= max(1, $page - 1) ?>&status=dismissed"
               class="p-2 rounded-md hover:bg-gray-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-icons text-gray-600">chevron_left</span>
            </a>
            <span class="text-sm font-medium text-gray-700">Page <?= $page ?> of <?= $totalDismissedPages ?></span>
            <a href="?page=<?= min($totalDismissedPages, $page + 1) ?>&status=dismissed"
               class="p-2 rounded-md hover:bg-gray-100 <?= $page >= $totalDismissedPages ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-icons text-gray-600">chevron_right</span>
            </a>
        </div>
    </div>
</div>


    </div>
  </div>
</main>

</tbody>
</table>
</div>

<!-- Custom Reason Modal -->
<div id="reasonModal" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
     onclick="overlayReasonClick(event)">
  <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-lg p-4" id="reasonModalBox">
    <div class="flex justify-between items-center border-b pb-2">
      <h2 class="text-lg font-semibold">Reported Reason</h2>
      <button onclick="closeReasonModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <div id="reasonContent" class="p-4 text-gray-700"></div>
    <div class="flex justify-end mt-4">
      <button onclick="closeReasonModal()" class="bg-gray-600 text-white px-3 py-2 rounded hover:bg-gray-700">
        Close
      </button>
    </div>
  </div>
</div>


<!-- Comment Modal -->
<div id="commentModal" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
     onclick="overlayClick(event)">
  <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-lg" id="modalBox">
    
    <!-- Header -->
    <div class="flex justify-between items-center px-4 py-2">
      <h2 class="text-lg font-semibold">Comment</h2>
      <button onclick="closeCommentModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>

    <!-- Body -->
    <div class="p-4 text-gray-700 max-h-80 overflow-y-auto" id="commentContent"></div>

    <!-- Footer -->
    <div class="flex justify-end px-4 py-2">
      <button onclick="closeCommentModal()" class="bg-gray-600 text-white px-3 py-2 rounded hover:bg-gray-700">
        Close
      </button>
    </div>
  </div>
</div>


</main>

<script>
    function openReasonModal(reason) {
  document.getElementById('reasonContent').innerText = reason;
  document.getElementById('reasonModal').classList.remove('hidden');
  document.getElementById('reasonModal').classList.add('flex');
}

function closeReasonModal() {
  document.getElementById('reasonModal').classList.add('hidden');
  document.getElementById('reasonModal').classList.remove('flex');
}

function overlayReasonClick(e) {
  const modalBox = document.getElementById('reasonModalBox');
  if (!modalBox.contains(e.target)) {
    closeReasonModal();
  }
}

</script>

<script>
function openConfirmModal(action, data, title, message, buttonText, buttonClass) {
  // Update modal content
  document.getElementById('confirmTitle').innerText = title;
  document.getElementById('confirmMessage').innerText = message;

  // Update form
  const form = document.getElementById('confirmForm');
  form.action = action;
  form.innerHTML = ''; // clear previous inputs + buttons

  // Add hidden inputs from data object
  for (const key in data) {
    if (data.hasOwnProperty(key)) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = data[key];
      form.appendChild(input);
    }
  }

  // Add Cancel button
  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.innerText = 'Cancel';
  cancelBtn.className = "px-4 py-2 rounded-lg bg-gray-300 text-gray-800 hover:bg-gray-400";
  cancelBtn.onclick = closeConfirmModal;
  form.appendChild(cancelBtn);

  // Add Confirm button
  const confirmBtn = document.createElement('button');
  confirmBtn.type = 'submit';
  confirmBtn.innerText = buttonText;
  confirmBtn.className = `px-4 py-2 rounded-lg text-white ${buttonClass}`;
  form.appendChild(confirmBtn);

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
  if (e.target === this) {
    closeConfirmModal();
  }
});

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === "Escape") closeConfirmModal();
});
</script>

<script>
function openCommentModal(comment) {
  document.getElementById('commentContent').innerText = comment;
  document.getElementById('commentModal').classList.remove('hidden');
  document.getElementById('commentModal').classList.add('flex');
}

function closeCommentModal() {
  document.getElementById('commentModal').classList.add('hidden');
  document.getElementById('commentModal').classList.remove('flex');
}

// Close when clicking outside modal box
function overlayClick(e) {
  const modalBox = document.getElementById('modalBox');
  if (!modalBox.contains(e.target)) {
    closeCommentModal();
  }
}

</script>

</body>
</html>
