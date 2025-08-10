<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../login.php");
    exit;
}

// Get student class from DB
$stmt = $conn->prepare("SELECT class FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($student_class);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Test - School Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../../assets/font/bootstrap-icons.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .test-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #dee2e6;
        }
        .test-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left-color: #0d6efd;
        }
        .question-card {
            border-left: 4px solid #0d6efd;
            background: #f8f9fa;
        }
        .option-label {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        .option-label:hover {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        .option-label input[type="radio"]:checked + span {
            background: #0d6efd;
            color: white;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
        }
        .progress-bar-custom {
            height: 8px;
        }
        .loading-spinner {
            display: none;
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
                        <h1 class="mb-2">
                            <i class="bi bi-pencil-square me-2"></i>Take Test
                        </h1>
                        <h4 class="mb-0 opacity-90">
                            <i class="bi bi-mortarboard me-2"></i>Class: <?php echo htmlspecialchars($student_class); ?>
                        </h4>
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

    <!-- Available Tests Section -->
    <div id="testsSection" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Available Tests
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Loading Spinner -->
                    <div id="loadingTests" class="text-center py-5 loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading tests...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading available tests...</p>
                    </div>

                    <!-- Tests List -->
                    <div id="testList" class="row g-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Taking Section -->
    <div id="testArea" style="display:none;">
        <!-- Test Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-clipboard-check me-2"></i>
                                <span id="testTitle"></span>
                            </h5>
                            <button id="backToTests" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>Back to Tests
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <label class="form-label">Progress</label>
                            <div class="progress progress-bar-custom">
                                <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">
                                Question <span id="currentQuestion">0</span> of <span id="totalQuestions">0</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Form -->
        <form id="testForm">
            <div id="questionsContainer"></div>
            
            <!-- Submit Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="bi bi-check-circle me-2"></i>Submit Test
                            </button>
                            <p class="text-muted mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Make sure all questions are answered before submitting
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Result Area -->
        <div id="resultArea" class="mt-4"></div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="../../assets/js/bootstrap.bundle.min"></script>

<script>
// Load tests for student's class via AJAX
async function loadTests() {
    const loadingElement = document.getElementById('loadingTests');
    const testListElement = document.getElementById('testList');
    
    loadingElement.style.display = 'block';
    
    try {
        const res = await fetch('fetch_tests.php?class=<?php echo urlencode($student_class); ?>');
        const tests = await res.json();

        loadingElement.style.display = 'none';
        testListElement.innerHTML = '';

        if (tests.length === 0) {
            testListElement.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No Tests Available</h4>
                        <p class="text-muted">There are no tests available for your class at the moment.</p>
                    </div>
                </div>
            `;
            return;
        }

        for (const t of tests) {
            const testCard = `
                <div class="col-md-6 col-lg-4">
                    <div class="card test-card h-100" onclick="loadTestQuestions(${t.id}, '${escapeHtml(t.test_name)}')">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="bi bi-journal-text me-2"></i>${escapeHtml(t.test_name)}
                            </h6>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>Date: ${t.test_date}<br>
                                    <i class="bi bi-book me-1"></i>Subject: ${escapeHtml(t.subject)}
                                </small>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <button class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-play-circle me-1"></i>Start Test
                            </button>
                        </div>
                    </div>
                </div>
            `;
            testListElement.innerHTML += testCard;
        }
    } catch (error) {
        loadingElement.style.display = 'none';
        testListElement.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error loading tests: ${error.message}
                </div>
            </div>
        `;
    }
}

// Load questions for a test
async function loadTestQuestions(testId, testName) {
    try {
        const res = await fetch('fetch_questions.php?test_id=' + testId);
        const questions = await res.json();

        if (!questions.length) {
            alert('No questions found for this test.');
            return;
        }

        document.getElementById('testTitle').textContent = testName;
        document.getElementById('totalQuestions').textContent = questions.length;
        document.getElementById('currentQuestion').textContent = questions.length;
        
        const container = document.getElementById('questionsContainer');
        container.innerHTML = '';

        questions.forEach((q, i) => {
            const questionCard = `
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card question-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <span class="badge bg-primary me-2">${i + 1}</span>
                                    ${escapeHtml(q.question_text)}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="option-label d-block">
                                            <input type="radio" name="q${q.id}" value="A" required onchange="updateProgress()">
                                            <span class="d-block">A. ${escapeHtml(q.option_a)}</span>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="option-label d-block">
                                            <input type="radio" name="q${q.id}" value="B" required onchange="updateProgress()">
                                            <span class="d-block">B. ${escapeHtml(q.option_b)}</span>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="option-label d-block">
                                            <input type="radio" name="q${q.id}" value="C" required onchange="updateProgress()">
                                            <span class="d-block">C. ${escapeHtml(q.option_c)}</span>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="option-label d-block">
                                            <input type="radio" name="q${q.id}" value="D" required onchange="updateProgress()">
                                            <span class="d-block">D. ${escapeHtml(q.option_d)}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += questionCard;
        });

        document.getElementById('testsSection').style.display = 'none';
        document.getElementById('testArea').style.display = 'block';

        // Save test ID for submission
        document.getElementById('testForm').dataset.testId = testId;
        
        // Scroll to top
        window.scrollTo(0, 0);
    } catch (error) {
        alert('Error loading questions: ' + error.message);
    }
}

// Update progress bar
function updateProgress() {
    const form = document.getElementById('testForm');
    const totalQuestions = parseInt(document.getElementById('totalQuestions').textContent);
    const answeredQuestions = form.querySelectorAll('input[type="radio"]:checked').length;
    
    const percentage = (answeredQuestions / totalQuestions) * 100;
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('currentQuestion').textContent = answeredQuestions;
}

// Back to tests button
document.getElementById('backToTests').addEventListener('click', function() {
    document.getElementById('testArea').style.display = 'none';
    document.getElementById('testsSection').style.display = 'block';
    document.getElementById('resultArea').innerHTML = '';
});

// Submit test
document.getElementById('testForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const testId = form.dataset.testId;
    const formData = new FormData(form);

    // Check if all questions are answered
    const totalQuestions = parseInt(document.getElementById('totalQuestions').textContent);
    const answeredQuestions = form.querySelectorAll('input[type="radio"]:checked').length;
    
    if (answeredQuestions < totalQuestions) {
        alert(`Please answer all questions. You have answered ${answeredQuestions} out of ${totalQuestions} questions.`);
        return;
    }

    // Prepare answers as JSON
    let answers = {};
    for (const pair of formData.entries()) {
        answers[pair[0]] = pair[1];
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Submitting...';
    submitBtn.disabled = true;

    try {
        // Send answers to submit_test.php
        const res = await fetch('submit_test.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ test_id: testId, answers: answers })
        });

        const result = await res.json();

        if (result.success) {
            const percentage = Math.round((result.score / result.total) * 100);
            const badgeClass = percentage >= 80 ? 'success' : percentage >= 60 ? 'warning' : 'danger';
            
            document.getElementById('resultArea').innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <div class="card border-${badgeClass}">
                            <div class="card-header bg-${badgeClass} text-white text-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-check-circle-fill me-2"></i>Test Completed Successfully!
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="display-4 text-${badgeClass} mb-2">${percentage}%</div>
                                    <h4>Your Score: ${result.score} / ${result.total}</h4>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="../results/view_results.php" class="btn btn-primary">
                                        <i class="bi bi-eye me-1"></i>View All Results
                                    </a>
                                    <button onclick="location.reload()" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Take Another Test
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            form.style.display = 'none';
        } else {
            throw new Error(result.error || 'Unknown error occurred');
        }
    } catch (error) {
        document.getElementById('resultArea').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Error: ${error.message}
            </div>
        `;
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

function escapeHtml(text) {
    const map = {
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Load tests on page load
loadTests();
</script>

</body>
</html>
