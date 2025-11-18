<?php
session_start();
require 'db.php';

$reported_user_id = $_POST['reported_user_id'];
$reporting_user_id = $_POST['reporting_user_id'];
$reason = $_POST['reason'] ?? '';
$custom_reason = $_POST['custom_reason'] ?? '';
$proof_path = null;

// ✅ Get reported user's username for redirect
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $reported_user_id);
$stmt->execute();
$stmt->bind_result($reported_username);
$stmt->fetch();
$stmt->close();

// ✅ Check if user already has a pending report against this person
$pending_sql = "
    SELECT id FROM reports 
    WHERE reported_user_id = ? 
      AND reporting_user_id = ? 
      AND status = 'Pending'
    LIMIT 1
";
$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("ii", $reported_user_id, $reporting_user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$stmt->close();

if ($pending_result->num_rows > 0) {
    $_SESSION['error'] = "You already submitted a report for this user. Please wait until it is resolved or dismissed.";
    header("Location: userprofile.php?username=" . urlencode($reported_username));
    exit;
}

// ✅ Handle optional proof file
if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['proof']['tmp_name'];
    $fileName = $_FILES['proof']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg','jpeg','png','pdf'];

    if (in_array($fileExt, $allowedExtensions)) {
        $newFileName = uniqid('proof_', true) . '.' . $fileExt;
        $uploadDir = 'uploads/reports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $destPath = $uploadDir . $newFileName;
        move_uploaded_file($fileTmpPath, $destPath);
        $proof_path = $destPath;
    }
}

// ✅ Insert new report
$stmt = $conn->prepare("INSERT INTO reports (reported_user_id, reporting_user_id, reason, custom_reason, proof_path, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
$stmt->bind_param("iisss", $reported_user_id, $reporting_user_id, $reason, $custom_reason, $proof_path);
$stmt->execute();
$stmt->close();

// ✅ Redirect back with success
$_SESSION['report_success'] = true;
header("Location: userprofile.php?username=" . urlencode($reported_username));
exit;
?>
