<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = intval($_POST['report_id']);
    $status   = $_POST['status'];

   $stmt = $conn->prepare("
    SELECT r.reporting_user_id, r.reported_user_id, r.reason, r.custom_reason, r.proof_path, r.created_at,
           u.username, u.total_violations
    FROM reports r
    JOIN users u ON r.reported_user_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $reportId);
$stmt->execute();
$stmt->bind_result(
    $reportingUserId, $reportedUserId, $reason, $customReason, $proofPath, $reportDate,
    $reportedUsername, $currentViolations
);
$stmt->fetch();
$stmt->close();


    // Update report status
    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $reportId);
    $stmt->execute();
    $stmt->close();

    // If resolved → update reported user account
    if ($status === "Resolved") {
        $newViolations = $currentViolations + 1;

        if ($newViolations == 1) {
            $accStatus = "1st Warning";
            $userMessage = "You have received your 1st warning due to a report. Please review our community guidelines.";
        } elseif ($newViolations == 2) {
            $accStatus = "2nd Warning";
            $userMessage = "You have received your 2nd warning. Continued violations may result in suspension.";
        } elseif ($newViolations == 3) {
            $accStatus = "Suspended (7 days)";
            $userMessage = "Your account has been suspended for 7 days due to repeated violations.";
        } elseif ($newViolations == 4) {
            $accStatus = "Suspended (30 days)";
            $userMessage = "Your account has been suspended for 30 days due to repeated violations.";
        } elseif ($newViolations >= 5) {
            $accStatus = "Banned";
            $userMessage = "Your account has been permanently banned due to repeated violations.";
        } else {
            $accStatus = "Active";
            $userMessage = "";
        }

        // Update user account
        $stmt = $conn->prepare("UPDATE users SET total_violations = ?, accstatus = ?, status_updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("isi", $newViolations, $accStatus, $reportedUserId);
        $stmt->execute();
        $stmt->close();

        // Notify reported user if needed
        if (!empty($userMessage)) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
            $stmt->bind_param("is", $reportedUserId, $userMessage);
            $stmt->execute();
            $stmt->close();
        }
    }
// ✅ Always notify the reporter (Resolved OR Rejected)
$reporterMessage = "📢 Thank you for your report regarding $reportedUsername. After review, we have $status your report. " . 
                   ($status === 'Resolved' 
                       ? "We found that this user’s actions violated our community guidelines." 
                       : "We found that this user’s actions did not violate our community guidelines.");



$stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) 
                        VALUES (?, ?, NOW(), 0)");
$stmt->bind_param("is", $reportingUserId, $reporterMessage);
$stmt->execute();
$stmt->close();


    header("Location: admin_reports.php");
    exit();
}
?>
