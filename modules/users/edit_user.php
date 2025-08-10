<?php
session_start();
include '../../config/db.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data and sanitize
$id = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$user_class = $_POST['class'] ?? '';

if ($id <= 0 || $username === '' || $role === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$allowed_roles = ['admin', 'teacher', 'student'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Invalid role']);
    exit;
}

if ($role === 'student') {
    $allowed_classes = ['8A','8B', '9A', '9B', '10A', '10B'];
    if (!in_array($user_class, $allowed_classes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid class for student']);
        exit;
    }
} else {
    $user_class = null; // clear class if not student
}

// Check if username already exists for different user
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->bind_param("si", $username, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username already taken']);
    exit;
}
$stmt->close();

// Prepare update query
if ($password !== '') {
    // Hash new password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, class = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $username, $role, $user_class, $hash, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, class = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $role, $user_class, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
