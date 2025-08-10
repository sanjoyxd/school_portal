<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: student_experiments.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$experiment_id = intval($_GET['id']);

// Get experiment details
$stmt = $conn->prepare("SELECT * FROM experiments WHERE id = ?");
$stmt->bind_param("i", $experiment_id);
$stmt->execute();
$experiment = $stmt->get_result()->fetch_assoc();

if (!$experiment) {
    header("Location: student_experiments.php");
    exit;
}

// Function to get upload error message
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "File is too large";
        case UPLOAD_ERR_FORM_SIZE:
            return "File exceeds form size limit";
        case UPLOAD_ERR_PARTIAL:
            return "File was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary upload directory";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension";
        default:
            return "Unknown upload error";
    }
}

// Function to format file size
function formatFileSize($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Helper function to convert PHP ini values to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Check if student already submitted
$stmt = $conn->prepare("SELECT * FROM experiment_submissions WHERE experiment_id = ? AND student_id = ?");
$stmt->bind_param("ii", $experiment_id, $user_id);
$stmt->execute();
$existing_submission = $stmt->get_result()->fetch_assoc();

// Get existing output files if any
$existing_files = [];
if ($existing_submission) {
    $stmt = $conn->prepare("SELECT * FROM experiment_output_files WHERE submission_id = ? ORDER BY uploaded_at ASC");
    $stmt->bind_param("i", $existing_submission['id']);
    $stmt->execute();
    $existing_files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_files':
                if (isset($_FILES['output_files']) && is_array($_FILES['output_files']['name'])) {
                    $upload_dir = '../../uploads/experiment_outputs/';
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $error = "Failed to create upload directory. Please contact administrator.";
                            break;
                        }
                    }
                    
                    $uploaded_files = [];
                    $upload_errors = [];
                    $submission_id = null;
                    
                    // Create or get submission record
                    if ($existing_submission) {
                        $submission_id = $existing_submission['id'];
                        // Clear old files if resubmitting
                        $stmt = $conn->prepare("SELECT * FROM experiment_output_files WHERE submission_id = ?");
                        $stmt->bind_param("i", $submission_id);
                        $stmt->execute();
                        $old_files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        // Delete old physical files
                        foreach ($old_files as $old_file) {
                            if (file_exists('../../' . $old_file['file_path'])) {
                                unlink('../../' . $old_file['file_path']);
                            }
                        }
                        
                        // Delete old database records
                        $stmt = $conn->prepare("DELETE FROM experiment_output_files WHERE submission_id = ?");
                        $stmt->bind_param("i", $submission_id);
                        $stmt->execute();
                    } else {
                        // Create new submission record
                        $stmt = $conn->prepare("INSERT INTO experiment_submissions (experiment_id, student_id, output_file) VALUES (?, ?, 'multiple_files')");
                        $stmt->bind_param("ii", $experiment_id, $user_id);
                        
                        if (!$stmt->execute()) {
                            $error = "Database error: " . $conn->error;
                            break;
                        }
                        $submission_id = $conn->insert_id;
                    }
                    
                    // Get max file size in bytes for comparison
                    $max_filesize_bytes = return_bytes(ini_get('upload_max_filesize'));
                    
                    // Process each uploaded file
                    for ($i = 0; $i < count($_FILES['output_files']['name']); $i++) {
                        $file_name = $_FILES['output_files']['name'][$i];
                        $file_error = $_FILES['output_files']['error'][$i];
                        $file_size = $_FILES['output_files']['size'][$i];
                        
                        if (empty($file_name)) continue; // Skip empty file slots
                        
                        // Check for upload errors
                        if ($file_error !== UPLOAD_ERR_OK) {
                            $upload_errors[] = $file_name . ': ' . getUploadErrorMessage($file_error);
                            continue;
                        }
                        
                        // Additional size check
                        if ($file_size > $max_filesize_bytes) {
                            $upload_errors[] = $file_name . ': File too large (' . formatFileSize($file_size) . ')';
                            continue;
                        }
                        
                        // Check file type
                        $file_type = $_FILES['output_files']['type'][$i];
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                        if (!in_array($file_type, $allowed_types)) {
                            $upload_errors[] = $file_name . ': File type not allowed';
                            continue;
                        }
                        
                        // Move file
                        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_filename = $user_id . '_' . $experiment_id . '_' . time() . '_' . ($i + 1) . '.' . $file_ext;
                        $file_path = 'uploads/experiment_outputs/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['output_files']['tmp_name'][$i], '../../' . $file_path)) {
                            // Save file info to database
                            $stmt = $conn->prepare("INSERT INTO experiment_output_files (submission_id, file_path, file_name) VALUES (?, ?, ?)");
                            $stmt->bind_param("iss", $submission_id, $file_path, $file_name);
                            
                            if ($stmt->execute()) {
                                $uploaded_files[] = $file_name;
                            } else {
                                $upload_errors[] = $file_name . ': Database error';
                            }
                        } else {
                            $upload_errors[] = $file_name . ': Failed to upload';
                        }
                    }
                    
                    // Update submission with total file count
                    if (!empty($uploaded_files)) {
                        $total_files = count($uploaded_files);
                        
                        $stmt = $conn->prepare("UPDATE experiment_submissions SET total_files = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("ii", $total_files, $submission_id);
                        $stmt->execute();
                        
                        $success_message = "🎉 Successfully submitted " . $total_files . " file(s) for Experiment " . (isset($experiment['experiment_number']) ? $experiment['experiment_number'] : $experiment_id) . "!";
                        
                        // Redirect back to student experiments with success message
                        header("Location: student_experiments.php?success=" . urlencode($success_message));
                        exit;
                    }
                    
                    // Set error messages
                    if (!empty($upload_errors)) {
                        $error = "Upload failed:\n" . implode("\n", $upload_errors);
                    } else {
                        $error = "No files were uploaded successfully.";
                    }
                    
                } else {
                    $error = "Please select files to upload.";
                }
                break;
                
            case 'delete_file':
                if (isset($_POST['file_id']) && $existing_submission) {
                    $file_id = intval($_POST['file_id']);
                    
                    $stmt = $conn->prepare("SELECT * FROM experiment_output_files WHERE id = ? AND submission_id = ?");
                    $stmt->bind_param("ii", $file_id, $existing_submission['id']);
                    $stmt->execute();
                    $file_info = $stmt->get_result()->fetch_assoc();
                    
                    if ($file_info) {
                        if (file_exists('../../' . $file_info['file_path'])) {
                            unlink('../../' . $file_info['file_path']);
                        }
                        
                        $stmt = $conn->prepare("DELETE FROM experiment_output_files WHERE id = ?");
                        $stmt->bind_param("i", $file_id);
                        $stmt->execute();
                        
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM experiment_output_files WHERE submission_id = ?");
                        $stmt->bind_param("i", $existing_submission['id']);
                        $stmt->execute();
                        $total_files = $stmt->get_result()->fetch_assoc()['total'];
                        
                        if ($total_files > 0) {
                            $stmt = $conn->prepare("UPDATE experiment_submissions SET total_files = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->bind_param("ii", $total_files, $existing_submission['id']);
                            $stmt->execute();
                            
                            // Refresh existing files
                            $stmt = $conn->prepare("SELECT * FROM experiment_output_files WHERE submission_id = ? ORDER BY uploaded_at ASC");
                            $stmt->bind_param("i", $existing_submission['id']);
                            $stmt->execute();
                            $existing_files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        } else {
                            // Delete submission if no files left
                            $stmt = $conn->prepare("DELETE FROM experiment_submissions WHERE id = ?");
                            $stmt->bind_param("i", $existing_submission['id']);
                            $stmt->execute();
                            $existing_submission = null;
                            $existing_files = [];
                        }
                        
                        $success = "File deleted successfully.";
                    } else {
                        $error = "File not found or access denied.";
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Experiment Output</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e3f2fd;
        }
        .file-item {
            transition: all 0.3s ease;
        }
        .file-item:hover {
            background-color: #f8f9fa;
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
        .card-title {
            font-size: 1rem;
        }
        h5, h6 {
            font-size: 0.95rem;
        }
        p, .card-text {
            font-size: 0.9rem;
        }
        small {
            font-size: 0.8rem;
        }
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
                        <h1 class="mb-1" style="font-size: 1.8rem;">
                            <i class="bi bi-upload me-2"></i>Submit Experiment Output
                        </h1>
                        <h4 class="mb-0 opacity-90" style="font-size: 1.2rem;">
                            <?php if (isset($experiment['experiment_number'])): ?>
                                Experiment <?php echo $experiment['experiment_number']; ?>: 
                            <?php endif; ?>
                            <?php echo htmlspecialchars($experiment['title']); ?>
                        </h4>
                    </div>
                    <div>
                        <a href="student_experiments.php" class="btn btn-light btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo nl2br(htmlspecialchars($success)); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Upload Failed:</strong><br>
        <?php echo nl2br(htmlspecialchars($error)); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Experiment Details -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2"></i>Experiment Details
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($experiment['figure_path']): ?>
                    <div class="text-center mb-3">
                        <?php
                        $figure_paths = array_filter(explode(',', $experiment['figure_path']));
                        foreach ($figure_paths as $fig_index => $path) {
                            $path = trim($path);
                            if ($path) {
                                echo '<div class="mb-2">';
                                echo '<small class="text-muted d-block">Fig ' . (isset($experiment['experiment_number']) ? $experiment['experiment_number'] : $experiment['id']) . '.' . ($fig_index + 1) . '</small>';
                                echo '<img src="../../' . htmlspecialchars($path) . '" alt="Experiment Figure" class="img-fluid rounded mb-2" style="max-height: 130px;">';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <h6 class="text-success">
                            <i class="bi bi-bullseye me-1"></i>Aim:
                        </h6>
                        <p><?php echo nl2br(htmlspecialchars($experiment['aim'])); ?></p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-info">
                            <i class="bi bi-list-task me-1"></i>Tasks:
                        </h6>
                        <div class="task-list">
                            <?php
                            $tasks = explode("\n", $experiment['task']);
                            foreach ($tasks as $task) {
                                $task = trim($task);
                                if (!empty($task)) {
                                    echo '<div class="task-item">' . htmlspecialchars($task) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <?php if ($experiment['hint']): ?>
                    <div class="mb-3">
                        <h6 class="text-warning">
                            <i class="bi bi-lightbulb me-1"></i>Hint:
                        </h6>
                        <p class="fst-italic"><?php echo nl2br(htmlspecialchars($experiment['hint'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="col-md-6">
            <?php if (!empty($existing_files)): ?>
            <!-- Current Submissions -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>Current Submission (<?php echo count($existing_files); ?> files)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($existing_files as $index => $file): ?>
                    <div class="file-item p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-image text-primary me-2 fs-4"></i>
                                    <div>
                                        <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                        <br><small class="text-muted">
                                            <?php echo date('M j, Y - g:i A', strtotime($file['uploaded_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="../../<?php echo $file['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="../../<?php echo $file['file_path']; ?>" download class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Are you sure you want to delete this file?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="alert alert-success mb-0" style="font-size: 0.85rem;">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Submitted!</strong> You can update your submission by uploading new files below.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="card">
                <div class="card-header <?php echo !empty($existing_files) ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
                    <h5 class="mb-0">
                        <i class="bi bi-cloud-upload me-2"></i>
                        <?php echo !empty($existing_files) ? 'Update Submission' : 'Submit Your Output'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="action" value="upload_files">
                        
                        <?php if (!empty($existing_files)): ?>
                        <div class="alert alert-warning" style="font-size: 0.85rem;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> Uploading new files will replace your current submission.
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <div class="upload-area" id="uploadArea">
                                <i class="bi bi-cloud-upload display-4 text-muted"></i>
                                <h5 class="mt-3" style="font-size: 1.1rem;">Select Your Output Files</h5>
                                <p class="text-muted" style="font-size: 0.9rem;">
                                    Upload one or more files (screenshots, images, documents)
                                </p>
                                <input type="file" class="form-control" name="output_files[]" 
                                       id="fileInput" accept="image/*,.pdf" multiple required style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Choose Files
                                </button>
                            </div>
                            
                            <div id="filePreview" class="mt-3" style="display: none;"></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn <?php echo !empty($existing_files) ? 'btn-warning' : 'btn-success'; ?> btn-lg">
                                <i class="bi bi-<?php echo !empty($existing_files) ? 'arrow-clockwise' : 'upload'; ?> me-2"></i>
                                <?php echo !empty($existing_files) ? 'Update Submission' : 'Submit Files'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
const fileInput = document.getElementById('fileInput');
const uploadArea = document.getElementById('uploadArea');
const filePreview = document.getElementById('filePreview');
const maxFileSize = <?php echo return_bytes(ini_get('upload_max_filesize')); ?>;

// File input change event
fileInput.addEventListener('change', function(e) {
    const files = e.target.files;
    displayFilePreview(files);
});

// Form submit validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const files = fileInput.files;
    
    if (files.length === 0) {
        e.preventDefault();
        alert('Please select at least one file to upload.');
        return false;
    }
    
    let hasOversizedFile = false;
    let oversizedFiles = [];
    
    for (let i = 0; i < files.length; i++) {
        if (files[i].size > maxFileSize) {
            hasOversizedFile = true;
            oversizedFiles.push(files[i].name);
        }
    }
    
    if (hasOversizedFile) {
        e.preventDefault();
        alert('Some files are too large:\n\n' + oversizedFiles.join('\n') + '\n\nPlease choose smaller files.');
        return false;
    }
    
    // Confirmation for resubmission
    <?php if (!empty($existing_files)): ?>
    if (!confirm('This will replace your current submission. Are you sure you want to continue?')) {
        e.preventDefault();
        return false;
    }
    <?php endif; ?>
});

// Drag and drop events
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        displayFilePreview(files);
    }
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function displayFilePreview(files) {
    if (files.length === 0) return;
    
    filePreview.innerHTML = '';
    filePreview.style.display = 'block';
    
    const previewContainer = document.createElement('div');
    previewContainer.className = 'row g-2';
    
    let totalSize = 0;
    let oversizedFiles = [];
    
    for (let i = 0; i < Math.min(files.length, 6); i++) {
        const file = files[i];
        totalSize += file.size;
        
        const fileDiv = document.createElement('div');
        fileDiv.className = 'col-6 col-md-4';
        
        const isOversized = file.size > maxFileSize;
        if (isOversized) {
            oversizedFiles.push(file.name);
        }
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                fileDiv.innerHTML = `
                    <div class="text-center ${isOversized ? 'border border-danger' : ''}">
                        <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 70px;">
                        <small class="d-block text-truncate ${isOversized ? 'text-danger' : ''}" style="font-size: 0.75rem;">${file.name}</small>
                        <small class="text-muted" style="font-size: 0.7rem;">${formatFileSize(file.size)}</small>
                        ${isOversized ? '<small class="text-danger d-block" style="font-size: 0.65rem;">Too large!</small>' : ''}
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            fileDiv.innerHTML = `
                <div class="text-center ${isOversized ? 'border border-danger' : ''}">
                    <i class="bi bi-file-earmark display-6 text-muted"></i>
                    <small class="d-block text-truncate ${isOversized ? 'text-danger' : ''}" style="font-size: 0.75rem;">${file.name}</small>
                    <small class="text-muted" style="font-size: 0.7rem;">${formatFileSize(file.size)}</small>
                    ${isOversized ? '<small class="text-danger d-block" style="font-size: 0.65rem;">Too large!</small>' : ''}
                </div>
            `;
        }
        
        previewContainer.appendChild(fileDiv);
    }
    
    if (files.length > 6) {
        const moreDiv = document.createElement('div');
        moreDiv.className = 'col-6 col-md-4 text-center';
        moreDiv.innerHTML = `
            <div class="text-center text-muted">
                <i class="bi bi-three-dots display-6"></i>
                <small class="d-block" style="font-size: 0.75rem;">+${files.length - 6} more</small>
            </div>
        `;
        previewContainer.appendChild(moreDiv);
    }
    
    const selectedInfo = document.createElement('div');
    selectedInfo.className = 'mt-2';
    selectedInfo.innerHTML = `
        <p class="text-info mb-1" style="font-size: 0.85rem;">${files.length} file(s) selected | Total: ${formatFileSize(totalSize)}</p>
        ${oversizedFiles.length > 0 ? `<p class="text-danger mb-0" style="font-size: 0.8rem;">⚠️ ${oversizedFiles.length} file(s) too large</p>` : ''}
    `;
    
    filePreview.appendChild(selectedInfo);
    filePreview.appendChild(previewContainer);
}
</script>

</body>
</html>
