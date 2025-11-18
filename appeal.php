<?php
session_start();
include 'db.php'; // Your database connection

// 🚫 If permanently banned
if (isset($_SESSION['banned_user'])) {
    $username = $_SESSION['banned_user'];
    $user_id = $_SESSION['banned_user_id'];
    $isPermanentBan = true;
}
// ⏳ If suspended
elseif (isset($_SESSION['suspended_user'])) {
    $username = $_SESSION['suspended_user'];
    $remaining = $_SESSION['suspended_days'];
    $user_id = $_SESSION['suspended_user_id'];
    $isPermanentBan = false;
}
// ❌ If neither → go back
else {
    header("Location: signin.php");
    exit;
}

// --- Only check reports + appeals if suspended ---
$report_reason = '';
$report_comment = '';
$report_date = '';
$report_id = 0;
$alreadySubmitted = false;
$appealStatus = '';

if (!$isPermanentBan) {
    // Fetch the last report reason for this user
    $report_query = $conn->prepare("SELECT id, reason, custom_reason, created_at 
                                    FROM reports 
                                    WHERE reported_user_id = ? 
                                    ORDER BY created_at DESC LIMIT 1");
    $report_query->bind_param("i", $user_id);
    $report_query->execute();
    $report_result = $report_query->get_result();
    if ($report_result->num_rows > 0) {
        $row = $report_result->fetch_assoc();
        $report_id = $row['id'];
        $report_reason = $row['reason'];
        $report_comment = $row['custom_reason'];
        $report_date = $row['created_at'];
    }
    $report_query->close();

    // Check if the user already submitted an appeal
    if ($report_id > 0) {
        $checkStmt = $conn->prepare("SELECT id, status FROM appeals WHERE user_id = ? AND report_id = ? ORDER BY appeal_date DESC LIMIT 1");
        $checkStmt->bind_param("ii", $user_id, $report_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $appealRow = $checkResult->fetch_assoc();
            $alreadySubmitted = true;
            $appealStatus = $appealRow['status'];
        }
        $checkStmt->close();
    }

    // Handle appeal submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadySubmitted) {
        $appeal_message = trim($_POST['appeal_message']);
        $appeal_date = date('Y-m-d H:i:s');

        // Handle optional proof upload
        $appeal_proof_path = null;
        if (isset($_FILES['appeal_proof']) && $_FILES['appeal_proof']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/appeals/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileTmpPath = $_FILES['appeal_proof']['tmp_name'];
            $fileName = basename($_FILES['appeal_proof']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = uniqid('appeal_', true) . '.' . $fileExt;

            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $appeal_proof_path = $destPath;
            }
        }

        if (!empty($appeal_message)) {
            $stmt = $conn->prepare("INSERT INTO appeals (user_id, report_id, appeal_message, appeal_date, status, appeal_proof) 
                                    VALUES (?, ?, ?, ?, 'Pending', ?)");
            $stmt->bind_param("iisss", $user_id, $report_id, $appeal_message, $appeal_date, $appeal_proof_path);
            $stmt->execute();
            $stmt->close();

            $alreadySubmitted = true;
            $appealStatus = 'Pending';
        } else {
            $error = "Please enter a message for your appeal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Status</title>
        <link href="img/favicon.png" rel="icon">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Prevent scrolling on mobile */
        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background-color: #f9fafb;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .status-card {
            width: 100%;
            max-width: 420px;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
        }

        @media (max-height: 650px) {
            .status-card {
                transform: scale(0.92);
            }
        }

        textarea {
            resize: none;
        }
    </style>
</head>

<body>
    <div class="status-card">

        <?php if ($isPermanentBan): ?>
            <!-- 🚫 Permanent Ban -->
            <h2 class="text-2xl font-bold mb-3 text-gray-800">Hello, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="text-red-600 font-semibold mb-3">🚫 Your account has been permanently banned.</p>
            <p class="text-gray-600 mb-6">This ban is permanent and cannot be appealed.</p>
            <a href="signin.php" class="text-red-600 font-medium hover:underline">Back to Sign In</a>

        <?php else: ?>
            <!-- ⏳ Suspension -->
            <h2 class="text-2xl font-bold mb-3 text-gray-800">Hello, <?php echo htmlspecialchars($username); ?>!</h2>

            <?php if ($appealStatus === 'Approved'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg mb-4 text-sm">
                    ✅ Your appeal has been approved. You can now access your account again.
                </div>
                <a href="signin.php" class="text-red-600 font-medium hover:underline">Back to Sign In</a>

            <?php else: ?>
                <p class="text-gray-600 text-sm mb-1">Your account is currently suspended.</p>
                <p class="text-gray-600 text-sm mb-3">Days remaining: 
                    <span class="font-semibold"><?php echo $remaining; ?></span>
                </p>

                <!-- 🚫 Show suspension reason only if appeal is NOT pending or rejected -->
                <?php if(!empty($report_reason) && $appealStatus !== 'Pending' && $appealStatus !== 'Rejected'): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                        <?php 
                        if ($report_reason === 'Other') {
                            echo "Your account has been suspended because you violated our community guidelines.";
                        } else {
                            echo "Your account has been suspended due to " . htmlspecialchars($report_reason) . ".";
                            if (!empty($report_comment)) echo " Details: " . htmlspecialchars($report_comment) . ".";
                        }
                        ?>
                        <br>
                        <small class="text-gray-500">Reported on: <?php echo date('M d, Y', strtotime($report_date)); ?></small>
                    </div>
                <?php endif; ?>

                <!-- Appeal Status Messages -->
                <?php if ($appealStatus === 'Rejected'): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                        ❌ Your appeal has been rejected. Please serve the full suspension period.
                    </div>
                <?php elseif ($appealStatus === 'Pending'): ?>
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm p-3 rounded-lg mb-4">
                        ⏳ Your appeal is under review. Please wait for a decision.
                    </div>
                <?php endif; ?>

                <?php if (!$alreadySubmitted): ?>
                    <form method="POST" enctype="multipart/form-data" class="space-y-3 text-left text-sm">
                        <div>
                            <label for="appeal_message" class="block font-medium text-gray-700 mb-1">
                                Submit an Appeal:
                            </label>
                            <textarea name="appeal_message" id="appeal_message" rows="4"
                                class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-red-200 focus:border-red-400 text-sm"
                                placeholder="Explain why your account should be reinstated"></textarea>
                        </div>

                        <div>
                            <label for="appeal_proof" class="block font-medium text-gray-700 mb-1">
                                Upload Proof (optional):
                            </label>
                            <input type="file" name="appeal_proof" id="appeal_proof" accept="image/*"
                                   class="w-full text-gray-700 text-sm">
                        </div>

                        <button type="submit"
                            class="w-full bg-red-600 text-white font-semibold py-2 rounded-lg hover:bg-red-700 transition text-sm">
                            Submit Appeal
                        </button>
                    </form>
                <?php endif; ?>

                <a href="signin.php" class="block mt-3 text-red-600 font-medium hover:underline text-sm text-center">
                    Back to Sign In
                </a>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
