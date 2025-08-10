<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
if (!$test_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid test_id']);
    exit;
}

$stmt = $conn->prepare("SELECT id, test_id, question_text, option_a, option_b, option_c, option_d, correct_option FROM questions WHERE test_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $test_id);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

echo json_encode(['success' => true, 'questions' => $questions]);
