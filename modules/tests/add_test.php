<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$test_name = trim($_POST['test_name'] ?? '');
$class = trim($_POST['class'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$test_date = $_POST['test_date'] ?? '';

if (!$test_name || !$class || !$subject || !$test_date) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Simple validation for date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $test_date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

// Insert into DB
$stmt = $conn->prepare("INSERT INTO tests (test_name, class, subject, test_date) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $test_name, $class, $subject, $test_date);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'test' => [
            'id' => $newId,
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
