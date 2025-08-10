<?php
session_start();
include '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['error'=>'Unauthorized']); exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Invalid ID']); exit; }
if ($id == $_SESSION['user_id']) { echo json_encode(['error'=>"Can't delete your own account"]); exit; }

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['error'=>'Delete failed: '.$conn->error]);
}
$stmt->close();
$conn->close();
