<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['experiment_id'])) {
    header("Location: manage_experiments.php");
    exit;
}

$experiment_id = intval($_GET['experiment_id']);

// Get experiment details
$stmt = $conn->prepare("SELECT * FROM experiments WHERE id = ?");
$stmt->bind_param("i", $experiment_id);
$stmt->execute();
$experiment = $stmt->get_result()->fetch_assoc();

if (!$experiment) {
    header("Location: manage_experiments.php");
    exit;
}

// Get parameters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'ASC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for search
$search_condition = '';
$search_param = '';
if (!empty($search)) {
    $search_condition = "AND u.username LIKE ?";
    $search_param = '%' . $search . '%';
}

// Build ORDER BY clause
$order_by = '';
switch ($sort_by) {
    case 'name':
        $order_by = "ORDER BY u.username " . ($sort_order === 'DESC' ? 'DESC' : 'ASC');
        break;
    case 'submission_time':
        $order_by = "ORDER BY es.submitted_at " . ($sort_order === 'DESC' ? 'DESC' : 'ASC') . ", u.username ASC";
        break;
    default:
        $order_by = "ORDER BY u.username ASC";
}

// Count total students for pagination
$count_sql = "SELECT COUNT(*) as total FROM users u WHERE u.class = ? AND u.role = 'student' $search_condition";
if ($search_param) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $experiment['class'], $search_param);
} else {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $experiment['class']);
}
$count_stmt->execute();
$total_students = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_students / $per_page);

// Get students with submissions (with pagination)
$sql = "
    SELECT u.id, u.username, 
           es.output_file, es.submitted_at, es.updated_at, es.total_files
    FROM users u
    LEFT JOIN experiment_submissions es ON u.id = es.student_id AND es.experiment_id = ?
    WHERE u.class = ? AND u.role = 'student' $search_condition
    $order_by
    LIMIT ? OFFSET ?
";

if ($search_param) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issii", $experiment_id, $experiment['class'], $search_param, $per_page, $offset);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $experiment_id, $experiment['class'], $per_page, $offset);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get overall stats (not just current page)
$stats_sql = "
    SELECT 
        COUNT(*) as total_students,
        COUNT(es.id) as total_submitted,
        COALESCE(SUM(es.total_files), 0) as total_files
    FROM users u
    LEFT JOIN experiment_submissions es ON u.id = es.student_id AND es.experiment_id = ?
    WHERE u.class = ? AND u.role = 'student' $search_condition
";

if ($search_param) {
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("iss", $experiment_id, $experiment['class'], $search_param);
} else {
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("is", $experiment_id, $experiment['class']);
}
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experiment Submissions - <?php echo htmlspecialchars($experiment['title']); ?></title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
            color: white;
        }
        .submission-card {
            transition: all 0.3s ease;
        }
        .submission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .output-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            cursor: pointer;
        }
        .file-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }
        .file-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid #dee2e6;
        }
        .file-thumbnail.error-img {
            background-color: #f8f9fa;
            border-color: #dc3545;
        }
        .more-files {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            color: #6c757d;
        }
        .loading-placeholder {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
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
                            <i class="bi bi-clipboard-data me-2"></i>Experiment Submissions
                        </h1>
                        <h4 class="mb-0 opacity-90">
                            <?php if (isset($experiment['experiment_number'])): ?>
                                Experiment <?php echo $experiment['experiment_number']; ?>: 
                            <?php endif; ?>
                            <?php echo htmlspecialchars($experiment['title']); ?>
                        </h4>
                        <p class="mb-0 opacity-75">Class: <?php echo htmlspecialchars($experiment['class']); ?></p>
                    </div>
                    <div>
                        <button onclick="window.close()" class="btn btn-light btn-lg me-2">
                            <i class="bi bi-x-circle me-2"></i>Close Tab
                        </button>
                        <a href="manage_experiments.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Back to Manage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $stats['total_students']; ?></h3>
                    <p class="card-text">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $stats['total_submitted']; ?></h3>
                    <p class="card-text">Submitted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $stats['total_students'] - $stats['total_submitted']; ?></h3>
                    <p class="card-text">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $stats['total_files']; ?></h3>
                    <p class="card-text">Total Files</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Sort Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="experiment_id" value="<?php echo $experiment_id; ?>">
                        
                        <div class="col-md-4">
                            <label class="form-label">Search Students</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Enter student name...">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort_by" class="form-select">
                                <option value="name" <?php echo ($sort_by === 'name') ? 'selected' : ''; ?>>Student Name</option>
                                <option value="submission_time" <?php echo ($sort_by === 'submission_time') ? 'selected' : ''; ?>>Submission Time</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <select name="sort_order" class="form-select">
                                <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                                <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Descending</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-funnel me-1"></i>Apply
                            </button>
                            <a href="?experiment_id=<?php echo $experiment_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>Student Submissions 
                            <?php if (!empty($search)): ?>
                                <small>(Search: "<?php echo htmlspecialchars($search); ?>")</small>
                            <?php endif; ?>
                        </h5>
                        <small>
                            Showing <?php echo min($per_page, count($students)); ?> of <?php echo $total_students; ?> students
                        </small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($students)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No Students Found</h4>
                        <p class="text-muted">
                            <?php if (!empty($search)): ?>
                                No students match your search criteria.
                            <?php else: ?>
                                No students are enrolled in this class.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Files</th>
                                    <th>Submitted At</th>
                                    <th>Output Preview</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr data-student-id="<?php echo $student['id']; ?>" data-student-name="<?php echo htmlspecialchars($student['username']); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 32px; height: 32px; font-size: 14px;">
                                                <?php echo strtoupper(substr($student['username'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($student['username']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($student['output_file'] || $student['total_files']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Submitted
                                            </span>
                                            <?php if ($student['updated_at'] && $student['updated_at'] != $student['submitted_at']): ?>
                                                <br><small class="text-info">Updated</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['total_files']): ?>
                                            <span class="badge bg-info">
                                                <?php echo $student['total_files']; ?> files
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">0 files</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['output_file'] || $student['total_files']): ?>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y - g:i A', strtotime($student['submitted_at'])); ?>
                                                <?php if ($student['updated_at'] && $student['updated_at'] != $student['submitted_at']): ?>
                                                    <br><span class="text-info">Updated: <?php echo date('M j - g:i A', strtotime($student['updated_at'])); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Not submitted</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['output_file'] || $student['total_files']): ?>
                                            <div id="preview-<?php echo $student['id']; ?>">
                                                <div class="file-grid">
                                                    <div class="loading-placeholder">
                                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No output</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['output_file'] || $student['total_files']): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="showMultipleOutputs(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['username']); ?>')"
                                                        data-bs-toggle="modal" data-bs-target="#outputModal">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-outline-success" 
                                                        onclick="downloadAllFiles(<?php echo $student['id']; ?>)">
                                                    <i class="bi bi-download"></i> All
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Students pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?experiment_id=<?php echo $experiment_id; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&page=<?php echo $page - 1; ?>">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?experiment_id=<?php echo $experiment_id; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Button -->
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?experiment_id=<?php echo $experiment_id; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&page=<?php echo $page + 1; ?>">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Page Info -->
            <div class="text-center text-muted">
                <small>
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                    (<?php echo $total_students; ?> total students)
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Output Preview Modal -->
<div class="modal fade" id="outputModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-images me-2"></i>Student Outputs - <span id="modalStudentName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalOutputBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading files...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="downloadAllCurrentFiles()">
                    <i class="bi bi-download me-1"></i>Download All Files
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>

<script>
let currentStudentFiles = [];
const experimentId = <?php echo $experiment_id; ?>;

// Load file previews for each student when page loads
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($students as $student): ?>
        <?php if ($student['output_file'] || $student['total_files']): ?>
            loadFilePreview(<?php echo $student['id']; ?>);
        <?php endif; ?>
    <?php endforeach; ?>
});

async function loadFilePreview(studentId) {
    const previewContainer = document.getElementById(`preview-${studentId}`);
    if (!previewContainer) return;
    
    try {
        const response = await fetch(`get_student_files.php?experiment_id=${experimentId}&student_id=${studentId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error('Server returned HTML instead of JSON. Check get_student_files.php exists and is accessible.');
        }
        
        const files = await response.json();
        
        if (files.error) {
            console.error('Server error:', files.error);
            previewContainer.innerHTML = '<span class="text-danger" title="' + files.error + '">Error</span>';
            return;
        }
        
        if (!Array.isArray(files) || files.length === 0) {
            previewContainer.innerHTML = '<span class="text-muted">No files</span>';
            return;
        }
        
        let html = '<div class="file-grid">';
        const maxPreview = 4;
        
        for (let i = 0; i < Math.min(files.length, maxPreview); i++) {
            const file = files[i];
            if (file.file_path && file.file_path.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
                html += `<img src="../../${file.file_path}" alt="Output ${i+1}" class="file-thumbnail" 
                         onclick="showMultipleOutputs(${studentId}, '${getStudentName(studentId)}')"
                         data-bs-toggle="modal" data-bs-target="#outputModal"
                         onerror="this.classList.add('error-img'); this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yNSAzMUMyNS41NTIzIDMxIDI2IDMwLjU1MjMgMjYgMzBDMjYgMjkuNDQ3NyAyNS41NTIzIDI5IDI1IDI5QzI0LjQ0NzcgMjkgMjQgMjkuNDQ3NyAyNCAzMEMyNCAzMC41NTIzIDI0LjQ0NzcgMzEgMjUgMzFaIiBmaWxsPSIjOTk5Ii8+CjxwYXRoIGQ9Ik0yNSAyN0MyNS41NTIzIDI3IDI2IDI2LjU1MjMgMjYgMjZWMjBDMjYgMTkuNDQ3NyAyNS41NTIzIDE5IDI1IDE5QzI0LjQ0NzcgMTkgMjQgMTkuNDQ3NyAyNCAyMFYyNkMyNCAyNi41NTIzIDI0LjQ0NzcgMjcgMjUgMjdaIiBmaWxsPSIjOTk5Ii8+Cjwvc3ZnPgo=';">`;
            } else if (file.file_path) {
                html += `<div class="file-thumbnail bg-light d-flex align-items-center justify-content-center border rounded"
                         onclick="showMultipleOutputs(${studentId}, '${getStudentName(studentId)}')"
                         data-bs-toggle="modal" data-bs-target="#outputModal">
                         <i class="bi bi-file-earmark text-muted"></i></div>`;
            }
        }
        
        if (files.length > maxPreview) {
            html += `<div class="more-files" onclick="showMultipleOutputs(${studentId}, '${getStudentName(studentId)}')" 
                     data-bs-toggle="modal" data-bs-target="#outputModal">
                     +${files.length - maxPreview}</div>`;
        }
        
        html += '</div>';
        html += `<small class="text-muted d-block mt-1">${files.length} file(s)</small>`;
        previewContainer.innerHTML = html;
        
    } catch (error) {
        console.error('Error loading preview for student', studentId, ':', error);
        previewContainer.innerHTML = `<span class="text-danger" title="${error.message}">Error</span>`;
    }
}

function getStudentName(studentId) {
    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
    if (row) {
        return row.getAttribute('data-student-name') || 'Unknown Student';
    }
    return 'Unknown Student';
}

async function showMultipleOutputs(studentId, studentName) {
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('modalOutputBody').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading files...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`get_student_files.php?experiment_id=${experimentId}&student_id=${studentId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned invalid response. Please check get_student_files.php');
        }
        
        const files = await response.json();
        
        if (files.error) {
            throw new Error(files.error);
        }
        
        currentStudentFiles = files;
        displayMultipleFiles(files);
        
    } catch (error) {
        console.error('Error fetching files:', error);
        document.getElementById('modalOutputBody').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Error loading files:</strong> ${error.message}
                <hr>
                <small class="text-muted">Possible solutions:
                <ul class="mb-0">
                    <li>Ensure get_student_files.php exists in the same directory</li>
                    <li>Check file permissions on uploaded files</li>
                    <li>Verify database connection</li>
                    <li>Check browser developer tools for more details</li>
                </ul></small>
            </div>
        `;
    }
}

function displayMultipleFiles(files) {
    const modalBody = document.getElementById('modalOutputBody');
    
    if (!Array.isArray(files) || files.length === 0) {
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-file-earmark display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No Files Found</h4>
                <p class="text-muted">This student hasn't uploaded any files yet.</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row g-3">';
    
    files.forEach((file, index) => {
        const isImage = file.file_path.match(/\.(jpg|jpeg|png|gif|webp)$/i);
        const fileUrl = `../../${file.file_path}`;
        
        html += `
            <div class="col-md-4 col-lg-3">
                <div class="card h-100">
                    ${isImage ? 
                        `<div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                            <img src="${fileUrl}" class="w-100 h-100" style="object-fit: cover;" 
                                 onerror="this.parentElement.innerHTML='<div class=\\'d-flex align-items-center justify-content-center h-100 bg-light\\'>  <i class=\\'bi bi-image text-muted display-4\\'></i><div class=\\'text-center\\'>  <small class=\\'text-muted\\'>Image not found</small></div></div>'">
                         </div>` :
                        `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-file-earmark display-4 text-muted"></i>
                         </div>`
                    }
                    <div class="card-body p-2">
                        <h6 class="card-title text-truncate" title="${file.file_name}">${file.file_name}</h6>
                        <small class="text-muted d-block">${new Date(file.uploaded_at).toLocaleDateString()}</small>
                    </div>
                    <div class="card-footer p-2">
                        <div class="btn-group btn-group-sm w-100">
                            <a href="${fileUrl}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="${fileUrl}" download="${file.file_name}" class="btn btn-outline-primary">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    modalBody.innerHTML = html;
}

async function downloadAllFiles(studentId) {
    try {
        const response = await fetch(`get_student_files.php?experiment_id=${experimentId}&student_id=${studentId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const files = await response.json();
        
        if (files.error) {
            alert('Error: ' + files.error);
            return;
        }
        
        if (!files.length) {
            alert('No files to download');
            return;
        }
        
        // Show download progress
        const studentName = getStudentName(studentId);
        const confirmed = confirm(`Download ${files.length} file(s) from ${studentName}?`);
        if (!confirmed) return;
        
        files.forEach((file, index) => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = `../../${file.file_path}`;
                link.download = file.file_name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, index * 200); // Stagger downloads
        });
        
    } catch (error) {
        console.error('Error downloading files:', error);
        alert('Error downloading files: ' + error.message);
    }
}

function downloadAllCurrentFiles() {
    if (!currentStudentFiles.length) {
        alert('No files to download');
        return;
    }
    
    const confirmed = confirm(`Download ${currentStudentFiles.length} file(s)?`);
    if (!confirmed) return;
    
    currentStudentFiles.forEach((file, index) => {
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = `../../${file.file_path}`;
            link.download = file.file_name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, index * 200);
    });
}
</script>

</body>
</html>
