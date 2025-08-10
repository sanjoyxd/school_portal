<?php
session_start();
require_once '../../config/db.php'; // Make sure this file defines $conn (mysqli)

// Return JSON always
header('Content-Type: application/json');

// ✅ Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ✅ Get JSON data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$test_id = intval($data['test_id'] ?? 0);
$answers = $data['answers'] ?? [];

// ✅ Basic validation
if ($test_id <= 0 || !is_array($answers) || empty($answers)) {
    echo json_encode(['success' => false, 'error' => 'Invalid or missing data']);
    exit;
}

// ✅ Fetch correct answers from DB
$stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$res = $stmt->get_result();

$correct_answers = [];
while ($row = $res->fetch_assoc()) {
    $correct_answers[$row['id']] = $row['correct_option'];
}
$stmt->close();

$total_questions = count($correct_answers);
$score = 0;

// ✅ Calculate score
foreach ($correct_answers as $qid => $correct) {
    $ansKey = "q" . $qid; // HTML input names are like q12, q13 etc
    if (isset($answers[$ansKey]) && strtoupper($answers[$ansKey]) === strtoupper($correct)) {
        $score++;
    }
}

// ✅ Save result into `results` table
$stmt = $conn->prepare("INSERT INTO results (user_id, test_id, score, total_questions) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiii", $_SESSION['user_id'], $test_id, $score, $total_questions);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Database insert failed: ' . $stmt->error]);
    exit;
}
$result_id = $stmt->insert_id;
$stmt->close();

// ✅ Save detailed answers into `result_answers` table
$stmt = $conn->prepare("INSERT INTO result_answers (result_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
foreach ($correct_answers as $qid => $correct) {
    $ansKey = "q" . $qid;
    $selected = isset($answers[$ansKey]) ? strtoupper($answers[$ansKey]) : '';
    $is_correct = ($selected === strtoupper($correct)) ? 1 : 0;
    $stmt->bind_param("iisi", $result_id, $qid, $selected, $is_correct);
    $stmt->execute();
}
$stmt->close();

echo json_encode([
    'success' => true,
    'score' => $score,
    'total' => $total_questions
]);
