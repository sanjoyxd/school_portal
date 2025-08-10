<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$question_text = trim($_POST['question_text'] ?? '');
$option_a = trim($_POST['option_a'] ?? '');
$option_b = trim($_POST['option_b'] ?? '');
$option_c = trim($_POST['option_c'] ?? '');
$option_d = trim($_POST['option_d'] ?? '');
$correct_option = trim($_POST['correct_option'] ?? '');

if (!$id || !$question_text || !$option_a || !$option_b || !$option_c || !$option_d || !in_array($correct_option, ['A','B','C','D'])) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

$stmt = $conn->prepare("UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ?");
$stmt->bind_param("ssssssi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
