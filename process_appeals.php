<?php
include 'db.php';
header('Content-Type: application/json');

if (!isset($_POST['appeal_id'], $_POST['action'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid request']);
    exit;
}

$appealId = intval($_POST['appeal_id']);
$action = $_POST['action'];

if (!in_array($action, ['accept','reject'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid action']);
    exit;
}

$conn->begin_transaction();

try {
    $appealStatus = $action === 'accept' ? 'Approved' : 'Rejected';
    
    $stmt = $conn->prepare("UPDATE appeals SET status=? WHERE id=?");
    $stmt->bind_param("si", $appealStatus, $appealId);
    $stmt->execute();
    $stmt->close();

    if ($action === 'accept') {
        $stmt = $conn->prepare("SELECT user_id FROM appeals WHERE id=?");
        $stmt->bind_param("i", $appealId);
        $stmt->execute();
        $stmt->bind_result($userId);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET accstatus='Active' WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

         // ✅ Add notification
        $message = "Your account has been reinstated after a successful appeal.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $message);
        $stmt->execute();
        $stmt->close();
        }
        

    $conn->commit();
    echo json_encode(['success'=>true, 'status'=>$appealStatus]);

} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

$conn->close();
?>
