<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
$question_text = trim($_POST['question_text'] ?? '');
$option_a = trim($_POST['option_a'] ?? '');
$option_b = trim($_POST['option_b'] ?? '');
$option_c = trim($_POST['option_c'] ?? '');
$option_d = trim($_POST['option_d'] ?? '');
$correct_option = trim($_POST['correct_option'] ?? '');

// Validate
if (!$test_id || !$question_text || !$option_a || !$option_b || !$option_c || !$option_d || !in_array($correct_option, ['A','B','C','D'])) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $test_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'question' => [
        'id' => $stmt->insert_id,
        'test_id' => $test_id,
        'question_text' => $question_text,
        'option_a' => $option_a,
        'option_b' => $option_b,
        'option_c' => $option_c,
        'option_d' => $option_d,
        'correct_option' => $correct_option
    ]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
