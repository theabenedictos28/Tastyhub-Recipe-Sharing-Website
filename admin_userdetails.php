<?php
require 'db.php';

if (!isset($_GET['id'])) {
    die("User ID is missing.");
}

$userId = intval($_GET['id']);



// ✅ Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    die("User not found.");
}
// count approved recipes
$stmt = $conn->prepare("SELECT COUNT(*) as recipes_count FROM recipe WHERE user_id = ? AND status = 'Approved'");
$stmt->bind_param("i", $userId); // make sure this matches your earlier $userId variable
$stmt->execute();
$user['recipes_count'] = $stmt->get_result()->fetch_assoc()['recipes_count'];


// count recipes
$stmt = $conn->prepare("SELECT COUNT(*) as comments_count FROM comments WHERE user_id = ?");
$stmt->bind_param("i", $userId); // make sure same variable as in user details
$stmt->execute();
$user['comments_count'] = $stmt->get_result()->fetch_assoc()['comments_count'];


// ✅ Fetch reports about this user
$stmt = $conn->prepare("
    SELECT r.*, ru.username AS reported_username, su.username AS reporting_username
    FROM reports r
    LEFT JOIN users ru ON r.reported_user_id = ru.id
    LEFT JOIN users su ON r.reporting_user_id = su.id
    WHERE r.reported_user_id = ?
    ORDER BY r.id DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Dashboard - User Details</title>
        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<style type="text/tailwindcss">
    body { font-family: 'Poppins', sans-serif; background-color: #f7f3f0; }
    .sidebar { background-color: #ffffff; }
    .main-content { background-color: #f7f3f0; }
    .active-link { background-color: #ff6f61; color: white; }
    .inactive-link { color: #4a4a4a; }
    .card { background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
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
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin.php"><span class="material-icons mr-3">dashboard</span>Dashboard</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_recipe.php"><span class="material-icons mr-3">receipt_long</span>Recipes</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg active-link" href="admin_users.php"><span class="material-icons mr-3">people</span>Users</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_comments.php"><span class="material-icons mr-3">comment</span>Comments</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_reports.php"><span class="material-icons mr-3">flag</span>Reports</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_appeals.php"><span class="material-icons mr-3">gavel</span>Appeals</a></li>
                <li class="mb-4"><a class="flex items-center p-3 rounded-lg inactive-link hover:bg-gray-100" href="admin_feedback.php"><span class="material-icons mr-3">feedback</span>Feedback</a></li>
            </ul>
        </nav>
    </div>
    <div>
        <a class="flex items-center p-3 rounded-lg text-red-500 hover:bg-red-50" href="logout.php"><span class="material-icons mr-3">logout</span>Logout</a>
    </div>
</aside>

<main class="main-content flex-1 p-8 overflow-y-auto">
<header class="flex justify-between items-center mb-8">
    <div>
        <a class="flex items-center text-gray-500 hover:text-gray-800 mb-2" href="admin_users.php">
            <span class="material-icons">arrow_back</span>
            <span class="ml-2">Back to Users</span>
        </a>
        <h2 class="text-3xl font-bold text-gray-800">User Details</h2>
    </div>
    <div class="flex items-center">
        <span class="mr-4 text-gray-600">Admin User</span>
<img alt="Admin avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA9Z-yBsYAaeaWkFBB6UcrHuFGwdd_GMeyqKk8UUwvgkXciEDhGc26LGm3V1PEhpM9G--3TUV46vMgDlJ_gbGgwwOotUFFFMH713MIWQui0zFZYqgBSoJ8edc4LnnHVVEN3g7KMwBeI-oSbtFUovjdQ8r2CLGeqzvb3KtbcKEDlDG6CnzbRsFdoDTr7dv-tfCRto2KRbKiK4RUvztUS47_onsq2T_b7qBQ22JrDL4EdLe1pMvi867ixtn6frrtmIHh0YfcLeyZzbdEU"/>
    </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: User Info -->
    <div class="lg:col-span-1">
        <div class="card p-6 text-center mb-2">
            <?php 
                $profilePic = !empty($user['profile_picture']) 
              ? 'uploads/profile_pics/'. $user['profile_picture'] 
              : 'img/no_profile.png';
            ?>
            <img alt="Profile Picture" class="w-24 h-24 rounded-full mx-auto mb-4" src="<?= htmlspecialchars($profilePic) ?>"/>
            <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($user['username']) ?></h3>
            <p class="text-gray-500 mb-2"><?= htmlspecialchars($user['email']) ?></p>
<?php
$status = strtolower($user['accstatus']);
$class = "status-active"; // default
if (strpos($status, "warning") !== false) {
    $class = "status-warning";
} elseif (strpos($status, "suspend") !== false) {
    $class = "status-suspended";
} elseif ($status === "banned") {
    $class = "status-banned";
}
?>
<span class="<?= $class ?>"><?= ucfirst(htmlspecialchars($user['accstatus'])) ?></span>
            <div class="mt-2">
                <p class="flex items-center mb-2">
    <span class="material-icons mr-2 text-gray-400">calendar_today</span>
    Joined <?= date("M d, Y", strtotime($user['created_at'])) ?>
</p>

<p class="flex items-center mb-2">
    <span class="material-icons mr-2 text-gray-400">receipt</span>
    <?= $user['recipes_count'] ?> Recipes Created
</p>

<p class="flex items-center mb-2">
    <span class="material-icons mr-2 text-gray-400">comment</span>
    <?= $user['comments_count'] ?> Comments Posted
</p>

            </div>
        </div>

    </div>


    <!-- Right: Reports -->
    <div class="lg:col-span-2">
<div class="card">
  <div class="p-6 border-b">
    <h4 class="text-xl font-semibold text-gray-800">
      Report History (<?= $reports->num_rows ?>)
    </h4>
  </div>

  <div class="divide-y divide-gray-200">
    <?php if ($reports->num_rows > 0): ?>
      <?php while ($report = $reports->fetch_assoc()): ?>
        <div class="p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-semibold text-gray-800">
    <?php if (strtolower($report['reason']) === 'other' && !empty($report['custom_reason'])): ?>
        <?= htmlspecialchars($report['custom_reason']) ?>
    <?php else: ?>
        <?= htmlspecialchars($report['reason']) ?>
    <?php endif; ?>
  </p>
            </div>
            <span class="text-sm text-gray-500">
              <?= date("M d, Y", strtotime($report['created_at'])) ?>
            </span>
          </div>

          <p class="mt-3 text-gray-600 bg-gray-50 p-3 rounded-lg">
            Status: <?= htmlspecialchars($report['status']) ?>
          </p>

          <div class="mt-3 text-sm">
            <span class="font-medium text-gray-700">Reported by:</span>
            <span class="text-gray-600"><?= htmlspecialchars($report['reporting_username']) ?></span>
          </div>

          <?php if (!empty($report['proof_path'])): ?>
            <div class="mt-3 text-sm">
              <span class="font-medium text-gray-700">Proof:</span>
              <a href="<?= htmlspecialchars($report['proof_path']) ?>" 
                 target="_blank" 
                 class="text-blue-500 underline">View Proof</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="p-6 text-gray-500">No reports found for this user.</p>
    <?php endif; ?>
  </div>

  <?php if ($reports->num_rows > 5): ?>
    <div class="p-4 border-t flex justify-center items-center">
      <button class="py-2 px-4 rounded-lg bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 text-sm">Load More</button>
    </div>
  <?php endif; ?>
</div>

        </div>
    </div>
</div>
</main>
</body>
</html>
