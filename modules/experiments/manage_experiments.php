<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../../login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_experiment':
                $title = $_POST['title'];
                $experiment_number = intval($_POST['experiment_number']);
                $description = $_POST['description'];
                $aim = $_POST['aim'];
                $task = $_POST['task'];
                $hint = $_POST['hint'];
                $class = $_POST['class'];
                
                // Handle multiple figure uploads
                $figure_paths = [];
                if (isset($_FILES['figures']) && is_array($_FILES['figures']['name'])) {
                    $upload_dir = '../../uploads/experiments/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    for ($i = 0; $i < count($_FILES['figures']['name']); $i++) {
                        if ($_FILES['figures']['error'][$i] === 0) {
                            $file_ext = pathinfo($_FILES['figures']['name'][$i], PATHINFO_EXTENSION);
                            $figure_path = 'uploads/experiments/' . $experiment_number . '_' . ($i+1) . '_' . time() . '.' . $file_ext;
                            if (move_uploaded_file($_FILES['figures']['tmp_name'][$i], '../../' . $figure_path)) {
                                $figure_paths[] = $figure_path;
                            }
                        }
                    }
                }
                $figure_path_string = implode(',', $figure_paths);
                
                $stmt = $conn->prepare("INSERT INTO experiments (title, experiment_number, description, aim, task, hint, class, figure_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissssss", $title, $experiment_number, $description, $aim, $task, $hint, $class, $figure_path_string);
                $stmt->execute();
                
                $success = "Experiment added successfully!";
                break;
                
            case 'edit_experiment':
                $exp_id = intval($_POST['experiment_id']);
                $title = $_POST['title'];
                $experiment_number = intval($_POST['experiment_number']);
                $description = $_POST['description'];
                $aim = $_POST['aim'];
                $task = $_POST['task'];
                $hint = $_POST['hint'];
                $class = $_POST['class'];
                
                // Handle figure uploads for edit
                $figure_paths = [];
                $existing_figures = $_POST['existing_figures'] ?? '';
                
                // Keep existing figures
                if (!empty($existing_figures)) {
                    $existing_paths = array_filter(explode(',', $existing_figures));
                    $figure_paths = $existing_paths;
                }
                
                // Add new figures
                if (isset($_FILES['figures']) && is_array($_FILES['figures']['name'])) {
                    $upload_dir = '../../uploads/experiments/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    for ($i = 0; $i < count($_FILES['figures']['name']); $i++) {
                        if ($_FILES['figures']['error'][$i] === 0) {
                            $file_ext = pathinfo($_FILES['figures']['name'][$i], PATHINFO_EXTENSION);
                            $figure_path = 'uploads/experiments/' . $experiment_number . '_' . (count($figure_paths) + 1) . '_' . time() . '.' . $file_ext;
                            if (move_uploaded_file($_FILES['figures']['tmp_name'][$i], '../../' . $figure_path)) {
                                $figure_paths[] = $figure_path;
                            }
                        }
                    }
                }
                
                $figure_path_string = implode(',', array_filter($figure_paths));
                
                $stmt = $conn->prepare("UPDATE experiments SET title=?, experiment_number=?, description=?, aim=?, task=?, hint=?, class=?, figure_path=? WHERE id=?");
                $stmt->bind_param("sissssssi", $title, $experiment_number, $description, $aim, $task, $hint, $class, $figure_path_string, $exp_id);
                
                if ($stmt->execute()) {
                    $success = "Experiment updated successfully!";
                } else {
                    $error = "Failed to update experiment: " . $conn->error;
                }
                break;
                
            case 'delete_experiment':
                $exp_id = intval($_POST['experiment_id']);
                
                // Delete figure files if exist
                $stmt = $conn->prepare("SELECT figure_path FROM experiments WHERE id = ?");
                $stmt->bind_param("i", $exp_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                if ($result && $result['figure_path']) {
                    $figure_paths = explode(',', $result['figure_path']);
                    foreach ($figure_paths as $path) {
                        if (trim($path) && file_exists('../../' . trim($path))) {
                            unlink('../../' . trim($path));
                        }
                    }
                }
                
                // Delete experiment and submissions - Fixed prepared statements
                $stmt = $conn->prepare("DELETE FROM experiment_submissions WHERE experiment_id = ?");
                $stmt->bind_param("i", $exp_id);
                $stmt->execute();
                
                $stmt = $conn->prepare("DELETE FROM experiments WHERE id = ?");
                $stmt->bind_param("i", $exp_id);
                $stmt->execute();
                
                $success = "Experiment deleted successfully!";
                break;
        }
    }
}

// Get filter
$filter_class = $_GET['filter_class'] ?? '';

// Fetch experiments with proper prepared statement
if ($filter_class) {
    $stmt = $conn->prepare("SELECT * FROM experiments WHERE class = ? ORDER BY class, experiment_number, date_added DESC");
    $stmt->bind_param("s", $filter_class);
    $stmt->execute();
    $experiments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $experiments = $conn->query("SELECT * FROM experiments ORDER BY class, experiment_number, date_added DESC")->fetch_all(MYSQLI_ASSOC);
}

// Group experiments by class
$experiments_by_class = [];
foreach ($experiments as $exp) {
    $experiments_by_class[$exp['class']][] = $exp;
}

// Get unique classes
$classes = $conn->query("SELECT DISTINCT class FROM users WHERE class IS NOT NULL AND class != '' ORDER BY class")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Experiments - School Portal</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .class-section {
            border-left: 4px solid #28a745;
            margin-bottom: 2rem;
        }
        .experiment-card {
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .experiment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .task-list {
            counter-reset: task-counter;
        }
        .task-item {
            counter-increment: task-counter;
            margin-bottom: 0.5rem;
        }
        .task-item:before {
            content: "Task " counter(task-counter) ": ";
            font-weight: bold;
            color: #0d6efd;
        }
        .figure-container img {
            max-height: 100px;
            margin: 0.25rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        .experiment-number {
            font-size: 1.2rem;
            font-weight: bold;
            background: linear-gradient(135deg, #007bff, #6f42c1);
            color: white;
            border-radius: 50px;
            padding: 0.5rem 1rem;
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
                            <i class="bi bi-flask me-2"></i>Manage Experiments
                        </h1>
                        <p class="mb-0 opacity-75">Create and manage IT/Computer experiments for students</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-light btn-lg me-2" data-bs-toggle="modal" data-bs-target="#addExperimentModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Experiment
                        </button>
                        <a href="../../dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-house-door me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Filter by Class</label>
                            <select name="filter_class" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class']; ?>" <?php echo ($filter_class === $class['class']) ? 'selected' : ''; ?>>
                                    <?php echo $class['class']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Experiments by Class -->
    <?php if (empty($experiments)): ?>
    <div class="text-center py-5">
        <i class="bi bi-flask display-1 text-muted"></i>
        <h4 class="text-muted mt-3">No Experiments Found</h4>
        <p class="text-muted">Click "Add Experiment" to create your first experiment</p>
    </div>
    <?php else: ?>
        <?php foreach ($experiments_by_class as $class_name => $class_experiments): ?>
        <div class="class-section">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-mortarboard me-2"></i>
                        Class: <?php echo htmlspecialchars($class_name); ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo count($class_experiments); ?> experiments</span>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($class_experiments as $index => $exp): ?>
                    <div class="experiment-card border-bottom p-4" 
                         data-id="<?php echo $exp['id']; ?>"
                         data-title="<?php echo htmlspecialchars($exp['title']); ?>"
                         data-experiment-number="<?php echo $exp['experiment_number']; ?>"
                         data-class="<?php echo htmlspecialchars($exp['class']); ?>"
                         data-description="<?php echo htmlspecialchars($exp['description']); ?>"
                         data-aim="<?php echo htmlspecialchars($exp['aim']); ?>"
                         data-task="<?php echo htmlspecialchars($exp['task']); ?>"
                         data-hint="<?php echo htmlspecialchars($exp['hint']); ?>"
                         data-figure-path="<?php echo htmlspecialchars($exp['figure_path']); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="experiment-number me-3">
                                            Experiment <?php echo $exp['experiment_number']; ?>
                                        </span>
                                        <h5 class="text-primary mb-0">
                                            <?php echo htmlspecialchars($exp['title']); ?>
                                        </h5>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-success me-1" onclick="viewSubmissions(<?php echo $exp['id']; ?>)">
                                            <i class="bi bi-eye-fill"></i> View Submissions
                                        </button>
                                        <button class="btn btn-sm btn-outline-info me-1" onclick="editExperiment(<?php echo $exp['id']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteExperiment(<?php echo $exp['id']; ?>)">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($exp['description']): ?>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($exp['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="text-success">
                                        <i class="bi bi-bullseye me-1"></i>Aim:
                                    </h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($exp['aim'])); ?></p>
                                </div>
                                
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
                                
                                <?php if ($exp['hint']): ?>
                                <div class="mb-3">
                                    <h6 class="text-warning">
                                        <i class="bi bi-lightbulb me-1"></i>Hint:
                                    </h6>
                                    <p class="mb-0 fst-italic"><?php echo nl2br(htmlspecialchars($exp['hint'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Added: <?php echo date('M j, Y - g:i A', strtotime($exp['date_added'])); ?>
                                </small>
                            </div>
                            
                            <div class="col-md-4">
                                <?php if ($exp['figure_path']): ?>
                                <div class="figure-container">
                                    <h6 class="text-secondary mb-2">
                                        <i class="bi bi-images me-1"></i>Figures:
                                    </h6>
                                    <?php
                                    $figure_paths = array_filter(explode(',', $exp['figure_path']));
                                    foreach ($figure_paths as $fig_index => $path) {
                                        $path = trim($path);
                                        if ($path) {
                                            echo '<div class="mb-2">';
                                            echo '<small class="text-muted d-block">Fig ' . $exp['experiment_number'] . '.' . ($fig_index + 1) . '</small>';
                                            echo '<img src="../../' . htmlspecialchars($path) . '" alt="Figure" class="img-thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage(this.src)">';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="bi bi-image display-6"></i>
                                    <p class="mb-0">No figures</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Experiment Modal -->
<div class="modal fade" id="addExperimentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_experiment">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Add New Experiment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Experiment Number *</label>
                            <input type="number" class="form-control" name="experiment_number" required min="1" placeholder="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class *</label>
                            <select class="form-select" name="class" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class']; ?>"><?php echo $class['class']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Aim *</label>
                            <textarea class="form-control" name="aim" rows="3" required placeholder="What is the purpose of this experiment?"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tasks * <small class="text-muted">(Write each task on a new line - they will be automatically numbered)</small></label>
                            <textarea class="form-control" name="task" rows="6" required placeholder="Prepare materials&#10;Set up the experiment&#10;Record observations&#10;Analyze results"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hint (Optional)</label>
                            <textarea class="form-control" name="hint" rows="2" placeholder="Any helpful tips for students?"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Figures/Diagrams (Optional - Multiple files allowed)</label>
                            <input type="file" class="form-control" name="figures[]" accept="image/*" multiple>
                            <small class="text-muted">Upload images, diagrams, or figures. They will be numbered as Fig [ExperimentNumber].1, Fig [ExperimentNumber].2, etc.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i>Add Experiment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Experiment Modal -->
<div class="modal fade" id="editExperimentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_experiment">
                <input type="hidden" name="experiment_id" id="edit_experiment_id">
                <input type="hidden" name="existing_figures" id="edit_existing_figures">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Edit Experiment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Experiment Number *</label>
                            <input type="number" class="form-control" name="experiment_number" id="edit_experiment_number" required min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class *</label>
                            <select class="form-select" name="class" id="edit_class" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class']; ?>"><?php echo $class['class']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Aim *</label>
                            <textarea class="form-control" name="aim" id="edit_aim" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tasks *</label>
                            <textarea class="form-control" name="task" id="edit_task" rows="6" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hint (Optional)</label>
                            <textarea class="form-control" name="hint" id="edit_hint" rows="2"></textarea>
                        </div>
                        <div class="col-12" id="existing_figures_display"></div>
                        <div class="col-12">
                            <label class="form-label">Add New Figures (Optional)</label>
                            <input type="file" class="form-control" name="figures[]" accept="image/*" multiple>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-circle me-1"></i>Update Experiment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Figure Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_experiment">
    <input type="hidden" name="experiment_id" id="deleteExpId">
</form>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>

<script>
function editExperiment(experimentId) {
    // Get the experiment card element
    const experimentCard = document.querySelector(`[data-id="${experimentId}"]`);
    
    if (!experimentCard) {
        alert('Experiment data not found');
        return;
    }
    
    // Extract data from data attributes
    const id = experimentCard.dataset.id;
    const title = experimentCard.dataset.title;
    const experimentNumber = experimentCard.dataset.experimentNumber;
    const classValue = experimentCard.dataset.class;
    const description = experimentCard.dataset.description;
    const aim = experimentCard.dataset.aim;
    const task = experimentCard.dataset.task;
    const hint = experimentCard.dataset.hint;
    const figurePath = experimentCard.dataset.figurePath;
    
    // Set form values
    document.getElementById('edit_experiment_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_experiment_number').value = experimentNumber;
    document.getElementById('edit_class').value = classValue;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_aim').value = aim;
    document.getElementById('edit_task').value = task;
    document.getElementById('edit_hint').value = hint;
    document.getElementById('edit_existing_figures').value = figurePath;
    
    // Display existing figures
    const figuresDiv = document.getElementById('existing_figures_display');
    if (figurePath) {
        const figures = figurePath.split(',');
        let figuresHtml = '<label class="form-label">Current Figures:</label><div class="d-flex flex-wrap gap-2">';
        figures.forEach((path, index) => {
            if (path.trim()) {
                figuresHtml += `<div class="position-relative">
                    <img src="../../${path.trim()}" alt="Fig ${experimentNumber}.${index + 1}" class="img-thumbnail" style="max-height: 80px;">
                    <small class="d-block text-center">Fig ${experimentNumber}.${index + 1}</small>
                </div>`;
            }
        });
        figuresHtml += '</div>';
        figuresDiv.innerHTML = figuresHtml;
    } else {
        figuresDiv.innerHTML = '';
    }
    
    // Show modal
    const editModal = new bootstrap.Modal(document.getElementById('editExperimentModal'));
    editModal.show();
}

function viewSubmissions(experimentId) {
    // Open submissions page in new tab
    window.open(`experiment_submissions.php?experiment_id=${experimentId}`, '_blank');
}

function showImage(src) {
    document.getElementById('modalImage').src = src;
}

function deleteExperiment(id) {
    if (confirm('Are you sure you want to delete this experiment? This will also delete all student submissions.')) {
        document.getElementById('deleteExpId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

</body>
</html>
