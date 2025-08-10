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
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$user_class = $_POST['class'] ?? '';

if ($username === '' || $password === '' || $role === '') {
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

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username already exists']);
    exit;
}
$stmt->close();

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (username, password, role, class) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hash, $role, $user_class);
if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $new_id,
            'username' => $username,
            'role' => $role,
            'class' => $user_class
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
$conn->close();
