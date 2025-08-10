<?php
session_start();
require_once '../../config/db.php'; // adjust path as needed

if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to view results.</p>";
    exit;
}

if (!isset($_GET['result_id']) || !intval($_GET['result_id'])) {
    echo "<p>Invalid request. No result selected.</p>";
    exit;
}

$result_id = intval($_GET['result_id']);

// ✅ Fetch result info
$stmt = $conn->prepare("
    SELECT r.*, t.test_name, u.username, u.class
    FROM results r
    JOIN tests t ON r.test_id = t.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param('i', $result_id);
$stmt->execute();
$res_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res_info) {
    echo "<p>Result not found.</p>";
    exit;
}

// ✅ Security: if student, must be their own result
if ($_SESSION['role'] === 'student' && $_SESSION['user_id'] != $res_info['user_id']) {
    echo "<p>You are not authorized to view this result.</p>";
    exit;
}

// ✅ Fetch per-question details
$stmt = $conn->prepare("
    SELECT q.id AS qid, q.question_text, 
           q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, 
           ra.selected_option, ra.is_correct
    FROM result_answers ra
    JOIN questions q ON ra.question_id = q.id
    WHERE ra.result_id = ?
    ORDER BY q.id ASC
");
$stmt->bind_param('i', $result_id);
$stmt->execute();
$q_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_questions = count($q_rows);
$correct_answers = array_sum(array_column($q_rows, 'is_correct'));
$percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Answer Sheet - <?php echo htmlspecialchars($res_info['test_name']); ?></title>
    <!-- Bootstrap 5 CSS (Local) -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .question-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        .question-card.correct {
            border-left-color: #198754;
        }
        .question-card.incorrect {
            border-left-color: #dc3545;
        }
        .option-correct {
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
        }
        .option-incorrect {
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
        }
        .option-unselected {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto;
        }
        .score-excellent { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .score-good { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
        .score-poor { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="gradient-header rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="bi bi-clipboard-check me-2"></i>
                            Answer Sheet
                        </h1>
                        <h3 class="mb-0 opacity-90"><?php echo htmlspecialchars($res_info['test_name']); ?></h3>
                    </div>
                    <div class="text-end">
                        <a href="view_results.php" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back to Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Info & Score -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge me-2"></i>Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="bi bi-person me-2 text-primary"></i>
                                <strong>Student:</strong> <?php echo htmlspecialchars($res_info['username']); ?>
                            </p>
                            <?php if (!empty($res_info['class'])): ?>
                            <p class="mb-2">
                                <i class="bi bi-mortarboard me-2 text-primary"></i>
                                <strong>Class:</strong> 
                                <span class="badge bg-info"><?php echo htmlspecialchars($res_info['class']); ?></span>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="bi bi-calendar3 me-2 text-primary"></i>
                                <strong>Date:</strong> <?php echo date('M j, Y - g:i A', strtotime($res_info['date_taken'])); ?>
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-question-circle me-2 text-primary"></i>
                                <strong>Total Questions:</strong> <?php echo $res_info['total_questions']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-trophy me-2"></i>Score Summary
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php 
                    $score_class = $percentage >= 80 ? 'score-excellent' : ($percentage >= 60 ? 'score-good' : 'score-poor');
                    ?>
                    <div class="<?php echo $score_class; ?> score-circle mb-3">
                        <?php echo $percentage; ?>%
                    </div>
                    <h4><?php echo $res_info['score']; ?>/<?php echo $res_info['total_questions']; ?></h4>
                    <p class="text-muted mb-0">
                        <?php echo $correct_answers; ?> correct, <?php echo ($total_questions - $correct_answers); ?> incorrect
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions and Answers -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i>Detailed Answer Review
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($q_rows)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                        <h4 class="text-muted mt-3">No answers found</h4>
                        <p class="text-muted">This result may not have been saved correctly.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($q_rows as $index => $q): ?>
                        <?php
                        $question_class = $q['is_correct'] ? 'correct' : 'incorrect';
                        $options = [
                            'A' => $q['option_a'],
                            'B' => $q['option_b'],
                            'C' => $q['option_c'],
                            'D' => $q['option_d'],
                        ];
                        ?>
                        <div class="question-card <?php echo $question_class; ?> p-4 <?php echo $index < count($q_rows) - 1 ? 'border-bottom' : ''; ?>">
                            <!-- Question Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="text-primary mb-0">
                                    <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </h6>
                                <div class="text-end">
                                    <?php if ($q['is_correct']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Correct
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Incorrect
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="row g-2">
                                <?php foreach ($options as $letter => $text): ?>
                                    <?php
                                    $isSelected = ($letter === $q['selected_option']);
                                    $isCorrect = ($letter === $q['correct_option']);
                                    
                                    if ($isCorrect) {
                                        $option_class = 'option-correct';
                                        $icon = '<i class="bi bi-check-circle text-success me-2"></i>';
                                    } elseif ($isSelected && !$isCorrect) {
                                        $option_class = 'option-incorrect';
                                        $icon = '<i class="bi bi-x-circle text-danger me-2"></i>';
                                    } else {
                                        $option_class = 'option-unselected';
                                        $icon = '<i class="bi bi-circle text-muted me-2"></i>';
                                    }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="<?php echo $option_class; ?> rounded p-3">
                                            <div class="d-flex align-items-start">
                                                <?php echo $icon; ?>
                                                <div class="flex-grow-1">
                                                    <strong><?php echo $letter; ?>.</strong> 
                                                    <?php echo htmlspecialchars($text); ?>
                                                    <?php if ($isSelected): ?>
                                                        <small class="d-block mt-1">
                                                            <i class="bi bi-arrow-right me-1"></i>Your answer
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($isCorrect): ?>
                                                        <small class="d-block mt-1 fw-bold">
                                                            <i class="bi bi-star me-1"></i>Correct answer
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="view_results.php" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-left me-2"></i>Back to All Results
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-lg ms-2">
                <i class="bi bi-printer me-2"></i>Print Answer Sheet
            </button>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS (Local) -->
<script src="../../assets/js/bootstrap.min.js"></script>

</body>
</html>
