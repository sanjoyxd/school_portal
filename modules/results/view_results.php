<?php
session_start();
require_once '../../config/db.php'; // Adjust path to your db.php

if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to view results.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get filter parameters
$filter_class = $_GET['filter_class'] ?? '';
$filter_test = $_GET['filter_test'] ?? '';

// Build WHERE conditions for admin/teacher filters
$where_conditions = [];
$params = [];
$param_types = '';

// If student → only their results, else → all results with optional filters
if ($role === 'student') {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $user_id;
    $param_types .= 'i';
} else {
    // For admin/teacher, add filters if provided
    if ($filter_class !== '') {
        $where_conditions[] = "u.class = ?";
        $params[] = $filter_class;
        $param_types .= 's';
    }
    
    if ($filter_test !== '') {
        $where_conditions[] = "t.id = ?";
        $params[] = $filter_test;
        $param_types .= 'i';
    }
}

// Build WHERE clause
$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Prepare the query
if ($role === 'student') {
    $sql = "
        SELECT r.id AS result_id, t.test_name, r.score, r.total_questions, r.date_taken
        FROM results r
        JOIN tests t ON r.test_id = t.id
        $where_sql
        ORDER BY r.date_taken DESC
    ";
} else {
    $sql = "
        SELECT r.id AS result_id, u.username, u.class, t.test_name, r.score, r.total_questions, r.date_taken
        FROM results r
        JOIN users u ON r.user_id = u.id
        JOIN tests t ON r.test_id = t.id
        $where_sql
        ORDER BY r.date_taken DESC
    ";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique classes for filter dropdown (only for admin/teacher)
$classes = [];
$tests = [];
if ($role !== 'student') {
    // Get classes
    $class_stmt = $conn->prepare("SELECT DISTINCT class FROM users WHERE class IS NOT NULL AND class != '' ORDER BY class");
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    while ($row = $class_result->fetch_assoc()) {
        $classes[] = $row['class'];
    }
    
    // Get tests
    $test_stmt = $conn->prepare("SELECT id, test_name FROM tests ORDER BY test_name");
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    while ($row = $test_result->fetch_assoc()) {
        $tests[] = $row;
    }
}

// Count total results
$total_results = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results - School Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .score-badge {
            font-size: 0.9rem;
        }
        .filter-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="gradient-bg rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-center flex-grow-1">
                        <h1 class="mb-1"><i class="bi bi-clipboard-data me-2"></i>Test Results</h1>
                        <p class="mb-0 opacity-75">
                            <?php if ($role === 'student'): ?>
                                View your test performance and detailed answers
                            <?php else: ?>
                                Monitor student performance and analyze test results
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="../../dashboard.php" class="btn btn-light btn-lg">
                            <i class="bi bi-house-door me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role !== 'student'): ?>
    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-funnel me-2 text-primary"></i>Filter Results
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="filter_class" class="form-label">
                                <i class="bi bi-people me-1"></i>Class
                            </label>
                            <select name="filter_class" id="filter_class" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class); ?>" 
                                            <?php echo ($filter_class === $class) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="filter_test" class="form-label">
                                <i class="bi bi-journal-text me-1"></i>Exam
                            </label>
                            <select name="filter_test" id="filter_test" class="form-select">
                                <option value="">All Exams</option>
                                <?php foreach ($tests as $test): ?>
                                    <option value="<?php echo $test['id']; ?>" 
                                            <?php echo ($filter_test == $test['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Apply Filter
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Results Summary -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2 text-primary"></i>
                    Results Found: <span class="badge bg-primary"><?php echo $total_results; ?></span>
                </h5>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <?php if ($total_results > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <?php if ($role !== 'student'): ?>
                                        <th><i class="bi bi-person me-1"></i>Student</th>
                                        <th><i class="bi bi-mortarboard me-1"></i>Class</th>
                                    <?php endif; ?>
                                    <th><i class="bi bi-journal-text me-1"></i>Test Name</th>
                                    <th><i class="bi bi-trophy me-1"></i>Score</th>
                                    <th><i class="bi bi-calendar me-1"></i>Date Taken</th>
                                    <th class="text-center"><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php 
                                    $percentage = round(($row['score'] / $row['total_questions']) * 100);
                                    $badge_class = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <?php if ($role !== 'student'): ?>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 32px; height: 32px; font-size: 14px;">
                                                        <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($row['username']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($row['class']); ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['test_name']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php echo $badge_class; ?> score-badge me-2">
                                                    <?php echo $row['score'] . "/" . $row['total_questions']; ?>
                                                </span>
                                                <small class="text-muted">(<?php echo $percentage; ?>%)</small>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('M j, Y - g:i A', strtotime($row['date_taken'])); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="view_answers.php?result_id=<?php echo $row['result_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>View Answers
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No Results Found</h4>
                        <p class="text-muted">
                            <?php if ($role === 'student'): ?>
                                You haven't taken any tests yet.
                            <?php else: ?>
                                No test results match your current filters.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="../../assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
