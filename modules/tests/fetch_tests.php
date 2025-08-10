<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(403);
    exit;
}

$class = $_GET['class'] ?? '';

$stmt = $conn->prepare("SELECT id, test_name, class, subject, test_date FROM tests WHERE class = ? ORDER BY test_date DESC");
$stmt->bind_param("s", $class);
$stmt->execute();
$res = $stmt->get_result();

$tests = $res->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($tests);
