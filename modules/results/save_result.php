<?php
// Assume $conn is your MySQLi connection
// Assume $user_id, $test_id, $answers are provided 
// $answers should be like [question_id => 'A']

$total_questions = count($answers);
$correct_count = 0;

// Get all correct answers
$placeholders = implode(',', array_fill(0, $total_questions, '?'));
$sql = "SELECT id, correct_option FROM questions WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', $total_questions), ...array_keys($answers));
$stmt->execute();
$result = $stmt->get_result();

$correct_map = [];
while ($row = $result->fetch_assoc()) {
    $correct_map[$row['id']] = $row['correct_option'];
}

// Count score
foreach ($answers as $qid => $selected) {
    if (isset($correct_map[$qid]) && $correct_map[$qid] === $selected) {
        $correct_count++;
    }
}

// Insert into results table (only once!)
$stmt = $conn->prepare("INSERT INTO results (user_id, test_id, score, total_questions) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iiii', $user_id, $test_id, $correct_count, $total_questions);
$stmt->execute();
$result_id = $stmt->insert_id;

// Insert into result_answers
$stmt = $conn->prepare("INSERT INTO result_answers (result_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
foreach ($answers as $qid => $selected) {
    $is_correct = (isset($correct_map[$qid]) && $correct_map[$qid] === $selected) ? 1 : 0;
    $stmt->bind_param('iisi', $result_id, $qid, $selected, $is_correct);
    $stmt->execute();
}

echo json_encode(['success' => true, 'score' => $correct_count, 'total' => $total_questions]);
