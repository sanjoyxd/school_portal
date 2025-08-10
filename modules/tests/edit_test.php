<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$test_name = trim($_POST['test_name'] ?? '');
$class = trim($_POST['class'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$test_date = $_POST['test_date'] ?? '';

if (!$id || !$test_name || !$class || !$subject || !$test_date) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $test_date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

$stmt = $conn->prepare("UPDATE tests SET test_name=?, class=?, subject=?, test_date=? WHERE id=?");
$stmt->bind_param('ssssi', $test_name, $class, $subject, $test_date, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'test' => [
            'id' => $id,
            'test_name' => $test_name,
            'class' => $class,
            'subject' => $subject,
            'test_date' => $test_date
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$stmt->close();
?>
