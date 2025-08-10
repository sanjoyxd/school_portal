<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(403);
    exit;
}

$test_id = intval($_GET['test_id'] ?? 0);
if (!$test_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$res = $stmt->get_result();

$questions = $res->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($questions);
