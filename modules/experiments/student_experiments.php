<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = $_GET['success'] ?? '';

// Get student's class
$stmt = $conn->prepare("SELECT class FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_class = $stmt->get_result()->fetch_assoc()['class'];

// Get experiments for student's class with proper submission check
$stmt = $conn->prepare("
    SELECT e.*, 
           es.submitted_at, es.updated_at, es.total_files,
           (SELECT COUNT(*) FROM experiment_output_files eof WHERE eof.submission_id = es.id) as file_count
    FROM experiments e 
    LEFT JOIN experiment_submissions es ON e.id = es.experiment_id AND es.student_id = ?
    WHERE e.class = ?
    ORDER BY e.experiment_number ASC, e.date_added DESC
");
$stmt->bind_param("is", $user_id, $student_class);
$stmt->execute();
$experiments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Science Experiments - School Portal</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
            color: white;
        }
        .experiment-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        .experiment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left-color: #007bff;
        }
        .submission-status {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .experiment-number {
            font-size: 0.95rem;
            font-weight: bold;
            background: linear-gradient(135deg, #007bff, #6f42c1);
            color: white;
            border-radius: 50px;
            padding: 0.3rem 0.7rem;
            display: inline-block;
            margin-bottom: 0.4rem;
        }
        .task-list {
            counter-reset: task-counter;
        }
        .task-item {
            counter-increment: task-counter;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        .task-item:before {
            content: "Task " counter(task-counter) ": ";
            font-weight: bold;
            color: #0d6efd;
            font-size: 0.85rem;
        }
        .figure-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
        }
        .figure-item {
            text-align: center;
        }
        .figure-container img {
            max-height: 100px;
            max-width: 130px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        .updated-badge {
            font-size: 0.65rem;
            margin-left: 0.4rem;
        }
        .card-title {
            font-size: 1.1rem;
        }
        .card-text {
            font-size: 0.9rem;
        }
        h6 {
            font-size: 0.95rem;
        }
        .experiment-info p {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .alert-success.submission-alert {
            font-size: 0.9rem;
        }
        
        /* Success Animation */
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-animation {
            animation: successPulse 0.6s ease-in-out, fadeInUp 0.5s ease-out;
        }
        .success-badge-animation {
            animation: successPulse 1s ease-in-out 3;
        }
        .success-confetti {
            position: relative;
            overflow: hidden;
        }
        .success-confetti::before {
            content: '🎉';
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 1.5rem;
            animation: confettiFall 2s ease-out;
        }
        @keyframes confettiFall {
            0% { transform: translateY(-20px) rotate(0deg); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(20px) rotate(360deg); opacity: 0; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="gradient-header rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-flask me-2"></i>Science Experiments
                        </h1>
                        <p class="mb-0 opacity-75">Class: <?php echo htmlspecialchars($student_class); ?></p>
                        <small class="opacity-75">
                            Total Experiments: <?php echo count($experiments); ?>
                        </small>
                    </div>
                    <div>
                        <a href="../../dashboard.php" class="btn btn-light btn-lg">
                            <i class="bi bi-house-door me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message with Animation -->
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show success-animation" id="successAlert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-3 fs-4 text-success success-confetti"></i>
            <div>
                <h5 class="alert-heading mb-1">🎉 Submission Successful!</h5>
                <p class="mb-0"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Experiments Grid -->
    <div class="row g-4">
        <?php if (empty($experiments)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-flask display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No Experiments Assigned</h4>
                <p class="text-muted">No experiments have been assigned to your class yet.</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($experiments as $exp): ?>
        <div class="col-lg-6">
            <div class="card experiment-card h-100 position-relative">
                <!-- Submission Status Badge -->
                <div class="submission-status">
                    <?php if ($exp['file_count'] > 0 || ($exp['total_files'] && $exp['total_files'] > 0)): ?>
                        <span class="badge bg-success success-badge-animation">
                            <i class="bi bi-check-circle me-1"></i>Submitted
                            <?php if ($exp['updated_at'] && $exp['updated_at'] != $exp['submitted_at']): ?>
                                <span class="updated-badge badge bg-info">Updated</span>
                            <?php endif; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning">
                            <i class="bi bi-clock me-1"></i>Pending
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Experiment Figures -->
                <?php if ($exp['figure_path']): ?>
                <div class="p-3 bg-light">
                    <div class="figure-container">
                        <?php
                        $figure_paths = array_filter(explode(',', $exp['figure_path']));
                        foreach ($figure_paths as $fig_index => $path) {
                            $path = trim($path);
                            if ($path) {
                                echo '<div class="figure-item">';
                                echo '<small class="text-muted d-block" style="font-size: 0.75rem;">Fig ' . (isset($exp['experiment_number']) ? $exp['experiment_number'] : $exp['id']) . '.' . ($fig_index + 1) . '</small>';
                                echo '<img src="../../' . htmlspecialchars($path) . '" alt="Experiment Figure" class="img-fluid rounded" data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage(this.src, \'Fig ' . (isset($exp['experiment_number']) ? $exp['experiment_number'] : $exp['id']) . '.' . ($fig_index + 1) . '\')">';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card-body">
                    <!-- Experiment Number and Title -->
                    <div class="mb-3">
                        <?php if (isset($exp['experiment_number'])): ?>
                        <span class="experiment-number">
                            Experiment <?php echo $exp['experiment_number']; ?>
                        </span>
                        <?php endif; ?>
                        <h5 class="card-title text-primary">
                            <i class="bi bi-journal-text me-2"></i>
                            <?php echo htmlspecialchars($exp['title']); ?>
                        </h5>
                    </div>
                    
                    <?php if ($exp['description']): ?>
                    <p class="card-text text-muted mb-3">
                        <?php echo htmlspecialchars($exp['description']); ?>
                    </p>
                    <?php endif; ?>

                    <div class="experiment-info">
                        <!-- Aim -->
                        <div class="mb-3">
                            <h6 class="text-success">
                                <i class="bi bi-bullseye me-1"></i>Aim:
                            </h6>
                            <p><?php echo nl2br(htmlspecialchars($exp['aim'])); ?></p>
                        </div>

                        <!-- Tasks -->
                        <div class="mb-3">
                            <h6 class="text-info">
                                <i class="bi bi-list-task me-1"></i>Tasks:
                            </h6>
                            <div class="task-list">
                                <?php
                                $tasks = explode("\n", $exp['task']);
                                foreach ($tasks as $task) {
                                    $task = trim($task);
                                    if (!empty($task)) {
                                        echo '<div class="task-item">' . htmlspecialchars($task) . '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Hint -->
                        <?php if ($exp['hint']): ?>
                        <div class="mb-3">
                            <h6 class="text-warning">
                                <i class="bi bi-lightbulb me-1"></i>Hint:
                            </h6>
                            <p class="fst-italic"><?php echo nl2br(htmlspecialchars($exp['hint'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Submission Info -->
                        <?php if ($exp['file_count'] > 0 || ($exp['total_files'] && $exp['total_files'] > 0)): ?>
                        <div class="alert alert-success submission-alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Submitted:</strong> <?php echo date('M j, Y - g:i A', strtotime($exp['submitted_at'])); ?>
                            <?php if ($exp['updated_at'] && $exp['updated_at'] != $exp['submitted_at']): ?>
                                <br><small class="text-info">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Updated: <?php echo date('M j, Y - g:i A', strtotime($exp['updated_at'])); ?>
                                </small>
                            <?php endif; ?>
                            <br><small class="text-muted">
                                <?php 
                                $file_count = $exp['file_count'] ?: $exp['total_files']; 
                                echo $file_count . ' file(s) uploaded';
                                ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <div class="d-grid">
                        <a href="submit_experiment.php?id=<?php echo $exp['id']; ?>" 
                           class="btn btn-<?php echo ($exp['file_count'] > 0 || ($exp['total_files'] && $exp['total_files'] > 0)) ? 'outline-primary' : 'primary'; ?> btn-lg">
                            <i class="bi bi-<?php echo ($exp['file_count'] > 0 || ($exp['total_files'] && $exp['total_files'] > 0)) ? 'pencil' : 'upload'; ?> me-2"></i>
                            <?php echo ($exp['file_count'] > 0 || ($exp['total_files'] && $exp['total_files'] > 0)) ? 'Update Submission' : 'Submit Output'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalTitle">Figure Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>

<script>
function showImage(src, title) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModalTitle').textContent = title;
}

// Auto-dismiss success alert after 8 seconds with fade animation
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            const alert = new bootstrap.Alert(successAlert);
            alert.close();
        }, 8000);
    }
});
</script>

</body>
</html>
