<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get current user info
$stmt = $conn->prepare("SELECT username, class FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics for admin/teacher
if ($role == 'admin' || $role == 'teacher') {
    // Initialize stats array
    $stats = [
        'users' => ['student' => 0, 'teacher' => 0, 'admin' => 0],
        'experiments' => ['total' => 0, 'submitted_students' => 0, 'total_submissions' => 0, 'recent_submissions' => 0],
        'active_users' => [],
        'class_performance' => []
    ];
    
    // Get total users by role
    $result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['users'][$row['role']] = $row['count'];
        }
    }
    
    // Get experiment statistics
    $result = $conn->query("SELECT COUNT(*) as total FROM experiments");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['experiments']['total'] = $row['total'] ?? 0;
    }
    
    $result = $conn->query("SELECT COUNT(DISTINCT student_id) as submitted_students FROM experiment_submissions");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['experiments']['submitted_students'] = $row['submitted_students'] ?? 0;
    }
    
    $result = $conn->query("SELECT COUNT(*) as total_submissions FROM experiment_submissions");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['experiments']['total_submissions'] = $row['total_submissions'] ?? 0;
    }
    
    // Get recent submissions (last 24 hours)
    $result = $conn->query("SELECT COUNT(*) as recent_submissions FROM experiment_submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['experiments']['recent_submissions'] = $row['recent_submissions'] ?? 0;
    }
    
    // Get active users (logged in within last hour)
    $result = $conn->query("
        SELECT DISTINCT u.username, u.role, es.updated_at
        FROM users u
        LEFT JOIN experiment_submissions es ON u.id = es.student_id
        WHERE es.updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY es.updated_at DESC
        LIMIT 10
    ");
    if ($result) {
        $stats['active_users'] = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get class-wise experiment completion
    $result = $conn->query("
        SELECT u.class, 
               COUNT(DISTINCT u.id) as total_students,
               COUNT(DISTINCT es.student_id) as active_students,
               ROUND((COUNT(DISTINCT es.student_id) / COUNT(DISTINCT u.id)) * 100, 1) as completion_rate
        FROM users u
        LEFT JOIN experiment_submissions es ON u.id = es.student_id
        WHERE u.role = 'student'
        GROUP BY u.class
        ORDER BY completion_rate DESC
    ");
    if ($result) {
        $stats['class_performance'] = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Helper function for time elapsed (fixed for PHP 8+ compatibility)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $units = [];
    
    if ($diff->y > 0) $units[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) $units[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    
    $weeks = floor($diff->d / 7);
    if ($weeks > 0) $units[] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    
    $days = $diff->d % 7;
    if ($days > 0) $units[] = $days . ' day' . ($days > 1 ? 's' : '');
    
    if ($diff->h > 0) $units[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) $units[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    if ($diff->s > 0) $units[] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');

    if (!$full && !empty($units)) {
        $units = array_slice($units, 0, 1);
    }
    
    return !empty($units) ? implode(', ', $units) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Bosco School Portal - Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: "Segoe UI", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .school-brand {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 0.8rem 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .school-logo-small {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: visible;
        }
        
        .logo-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-image {
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .logo-image:hover {
            transform: scale(1.05);
        }

        .developer-photo {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .developer-photo:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .navigation-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
            margin-bottom: 1rem;
        }
        
        .btn-nav {
            border-radius: 25px;
            font-weight: 500;
            padding: 6px 14px;
            margin: 2px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .main-content {
            flex: 1;
            padding-bottom: 1rem;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            padding: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: scale(1.03);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .instructions-card {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #2c3e50;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-right: 0.8rem;
            font-size: 0.85rem;
        }
        
        .role-badge {
            font-size: 0.7rem;
            border-radius: 10px;
            padding: 3px 6px;
        }
        
        .developer-footer {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto;
        }
        
        .developer-footer a {
            color: #84fab0;
            text-decoration: none;
            font-weight: 600;
        }
        
        .developer-footer a:hover {
            color: #8fd3f4;
            text-decoration: underline;
        }
        
        .progress-custom {
            height: 6px;
            border-radius: 10px;
        }
        
        .live-indicator {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .instruction-step {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        
        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #003366;
            border-radius: 20px;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            color: #003366;
            transform: scale(1.05);
        }
        
        /* Compact styles for students */
        .student-layout {
            max-height: calc(100vh - 200px);
            overflow: hidden;
        }
        
        .compact-instructions {
            padding: 1rem;
        }
        
        .compact-step {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid #667eea;
        }
        
        .compact-step h6 {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .compact-step p {
            font-size: 0.8rem;
            margin-bottom: 0;
        }
        
        .spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- School Brand Header -->
    <div class="school-brand">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="school-logo-small logo-container">
                            <img src="assets/images/school-logo.png" 
                                 alt="Don Bosco School Logo" 
                                 class="logo-image"
                                 id="dashboardLogo"
                                 style="width: 36px; height: 36px; object-fit: contain; border-radius: 50%; display: block;"
                                 onload="console.log('Dashboard logo loaded successfully'); this.style.display='block';"
                                 onerror="console.log('Dashboard logo failed, showing fallback'); this.style.display='none'; document.getElementById('logoFallback').style.display='flex';">
                            <!-- Clean fallback -->
                            <div id="logoFallback" style="display: none; width: 36px; height: 36px; background: white; border-radius: 50%; 
                                        color: #003366; font-weight: bold; font-size: 1.2rem;
                                        position: absolute; top: 2px; left: 2px;
                                        align-items: center; justify-content: center;">
                                DBS
                            </div>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size: 1.1rem;">Don Bosco School</h5>
                            <small class="opacity-75" style="font-size: 0.8rem;">Barpeta Road, Assam • School Management Portal</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex align-items-center justify-content-md-end justify-content-start mt-2 mt-md-0">
                        <div class="me-3">
                            <small class="opacity-75 d-block" style="font-size: 0.8rem;">
                                <i class="bi bi-person-circle me-1"></i>
                                Welcome, <strong><?php echo htmlspecialchars($current_user['username']); ?></strong>
                                <span class="badge bg-light text-dark role-badge ms-1"><?php echo ucfirst($role); ?></span>
                            </small>
                            <small class="opacity-75" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('l, F j, Y'); ?>
                            </small>
                        </div>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <div class="navigation-bar">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <?php if ($role == 'admin' || $role == 'teacher') { ?>
                    <a href="modules/experiments/manage_experiments.php" class="btn btn-primary btn-nav">
                        <i class="bi bi-flask me-1"></i>Manage Experiments
                    </a>
                    <a href="modules/tests/manage.php" class="btn btn-primary btn-nav">
                        <i class="bi bi-clipboard-check me-1"></i>Manage Tests
                    </a>
                    <a href="modules/results/view_results.php" class="btn btn-primary btn-nav">
                        <i class="bi bi-graph-up me-1"></i>View Results
                    </a>
                    <?php if ($role == 'admin') { ?>
                        <a href="modules/users/manage.php" class="btn btn-danger btn-nav">
                            <i class="bi bi-people me-1"></i>Manage Users
                        </a>
                    <?php } ?>
                <?php } ?>
                
                <?php if ($role == 'student') { ?>
                    <a href="modules/experiments/student_experiments.php" class="btn btn-success btn-nav">
                        <i class="bi bi-flask me-1"></i>Experiments
                    </a>
                    <a href="modules/tests/take_test.php" class="btn btn-success btn-nav">
                        <i class="bi bi-pencil-square me-1"></i>Tests
                    </a>
                    <a href="modules/results/view_results.php" class="btn btn-success btn-nav">
                        <i class="bi bi-trophy me-1"></i>Results
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <?php if ($role == 'student') { ?>
                <!-- Compact Student Instructions -->
                <div class="student-layout">
                    <div class="instructions-card compact-instructions">
                        <div class="text-center mb-3">
                            <i class="bi bi-lightbulb fs-2 mb-2" style="color: #667eea;"></i>
                            <h4>🎓 Welcome to Your Learning Portal!</h4>
                            <p class="mb-0" style="font-size: 0.9rem;">Here's how to use your science experiments portal</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="compact-step">
                                    <h6><i class="bi bi-flask text-primary me-2"></i>Step 1: View Experiments</h6>
                                    <p>Click <strong>"Experiments"</strong> to see all assigned science experiments.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="compact-step">
                                    <h6><i class="bi bi-camera text-success me-2"></i>Step 2: Submit Work</h6>
                                    <p>Complete experiments, take photos of results, and submit them.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="compact-step">
                                    <h6><i class="bi bi-trophy text-warning me-2"></i>Attempt Test & Check Results</h6>
                                    <p>After attemting test, view your test results in <strong>"Results"</strong>.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <div class="alert alert-info d-inline-block mb-0 py-2">
                                <i class="bi bi-info-circle me-2"></i>
                                <small><strong>Pro Tip:</strong> You can submit multiple files and update them anytime!</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Quick Stats -->
                    <div class="row">
                        <?php
                        // Get student's experiment stats
                        $stmt = $conn->prepare("
                            SELECT 
                                COUNT(e.id) as total_experiments,
                                COUNT(es.id) as completed_experiments
                            FROM experiments e
                            LEFT JOIN experiment_submissions es ON e.id = es.experiment_id AND es.student_id = ?
                            WHERE e.class = ?
                        ");
                        $stmt->bind_param("is", $user_id, $current_user['class']);
                        $stmt->execute();
                        $student_stats = $stmt->get_result()->fetch_assoc();
                        ?>
                        
                        <div class="col-md-6">
                            <div class="dashboard-card text-center">
                                <h4 class="text-primary"><?php echo $student_stats['completed_experiments']; ?> / <?php echo $student_stats['total_experiments']; ?></h4>
                                <p class="text-muted mb-1" style="font-size: 0.9rem;">Experiments Completed</p>
                                <?php if ($student_stats['total_experiments'] > 0): ?>
                                    <div class="progress progress-custom">
                                        <div class="progress-bar bg-success" style="width: <?php echo round(($student_stats['completed_experiments'] / $student_stats['total_experiments']) * 100); ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo round(($student_stats['completed_experiments'] / $student_stats['total_experiments']) * 100); ?>% Complete</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="dashboard-card text-center">
                                <h4 class="text-info"><?php echo $current_user['class']; ?></h4>
                                <p class="text-muted mb-1" style="font-size: 0.9rem;">Your Class</p>
                                <small class="text-success">Keep up the great work!</small>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php } else { ?>
                <!-- Admin/Teacher Dashboard -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <span class="live-indicator"></span>
                                    Real-time Dashboard
                                </h5>
                                <button class="refresh-btn" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['users']['student'] ?? 0; ?></div>
                            <div style="font-size: 0.9rem;">Total Students</div>
                            <small><i class="bi bi-people me-1"></i>Active Learners</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="stat-number"><?php echo $stats['experiments']['total']; ?></div>
                            <div style="font-size: 0.9rem;">Total Experiments</div>
                            <small><i class="bi bi-flask me-1"></i>Available</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="stat-number"><?php echo $stats['experiments']['total_submissions']; ?></div>
                            <div style="font-size: 0.9rem;">Total Submissions</div>
                            <small><i class="bi bi-upload me-1"></i>All Time</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <div class="stat-number"><?php echo $stats['experiments']['recent_submissions']; ?></div>
                            <div style="font-size: 0.9rem;">Recent (24h)</div>
                            <small><i class="bi bi-clock me-1"></i>Last Day</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Class Performance -->
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h6 class="mb-3">
                                <i class="bi bi-bar-chart text-primary me-2"></i>Class Performance
                            </h6>
                            <?php if (empty($stats['class_performance'])): ?>
                                <p class="text-muted text-center py-2" style="font-size: 0.9rem;">No class data available</p>
                            <?php else: ?>
                                <?php foreach ($stats['class_performance'] as $class): ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold" style="font-size: 0.9rem;">Class <?php echo htmlspecialchars($class['class']); ?></span>
                                            <span class="text-muted" style="font-size: 0.8rem;"><?php echo $class['active_students']; ?>/<?php echo $class['total_students']; ?> students</span>
                                        </div>
                                        <div class="progress progress-custom">
                                            <div class="progress-bar" 
                                                 style="width: <?php echo $class['completion_rate']; ?>%; background: linear-gradient(90deg, #667eea, #764ba2);">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $class['completion_rate']; ?>% completion</small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h6 class="mb-3">
                                <i class="bi bi-activity text-success me-2"></i>Recent Activity
                                <span class="live-indicator"></span>
                            </h6>
                            <?php if (empty($stats['active_users'])): ?>
                                <p class="text-muted text-center py-2" style="font-size: 0.9rem;">No recent activity</p>
                            <?php else: ?>
                                <?php foreach (array_slice($stats['active_users'], 0, 5) as $user): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="user-avatar bg-primary">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <small class="text-muted">
                                                <span class="badge bg-light text-dark role-badge me-1"><?php echo ucfirst($user['role']); ?></span>
                                                Active <?php echo time_elapsed_string($user['updated_at']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <i class="bi bi-circle-fill text-success" style="font-size: 6px;"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Developer Footer -->
    <div class="developer-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-md-start text-center mb-1 mb-md-0">
                    <strong style="font-size: 0.9rem;">Don Bosco School Computer Lab Management System with Mr. xD</strong>
                    <br><small>Empowering Education Through Technology</small>
                </div>
                <div class="col-md-6 text-md-end text-center">
                    <span style="font-size: 0.9rem;">Crafted with ❤️ by <a href="#" data-bs-toggle="modal" data-bs-target="#developerModal">Sir Sanjoy</a></span>
                    <br><small class="opacity-75">Version 1.0 • <?php echo date('Y'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Developer Modal -->
    <div class="modal fade" id="developerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2"></i>About the Developer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <div class="mx-auto position-relative" style="width: 80px; height: 80px;">
                            <img src="assets/images/developer-photo.jpg" 
                                 alt="Developer Photo" 
                                 class="developer-photo"
                                 id="developerPhoto"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid #007bff; display: block;"
                                 onload="console.log('Developer photo loaded successfully'); this.style.display='block';"
                                 onerror="console.log('Developer photo failed, showing fallback'); this.style.display='none'; document.getElementById('devPhotoFallback').style.display='flex';">
                            <!-- Clean fallback -->
                            <div id="devPhotoFallback" 
                                 style="display: none; width: 80px; height: 80px; background: #007bff; color: white; border-radius: 50%; 
                                        position: absolute; top: 0; left: 0; align-items: center; justify-content: center; font-size: 2rem;">
                                <i class="bi bi-code-square"></i>
                            </div>
                        </div>
                    </div>
                    <h5>Sanjoy Choudhury</h5>
                    <p class="text-muted mb-3">
                        Passionate full-stack developer dedicated to creating innovative educational technology solutions.
                    </p>
                    <div class="row text-center">
                        <div class="col-4">
                            <i class="bi bi-code-slash text-primary fs-4 d-block"></i>
                            <small>Full-Stack</small>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-database text-success fs-4 d-block"></i>
                            <small>Database</small>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-palette text-warning fs-4 d-block"></i>
                            <small>UI/UX</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-start">
                        <h6><i class="bi bi-tools me-2"></i>Technologies Used:</h6>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-danger">PHP</span>
                            <span class="badge bg-primary">MariaDB</span>
                            <span class="badge bg-warning">JavaScript</span>
                            <span class="badge bg-info">Bootstrap</span>
                            <span class="badge bg-success">HTML/CSS</span>
                        </div>
                    </div>
                    <hr>
                    <div class="text-start">
                        <h6><i class="bi bi-star me-2"></i>Key Features:</h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle text-success me-2"></i>User Management System</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Experiment Management</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>File Upload & Management</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Responsive Design</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Real-time Analytics</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Force show images for testing
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Testing image paths...');
            
            // Test school logo
            setTimeout(() => {
                const logoImg = document.getElementById('dashboardLogo');
                if (logoImg) {
                    console.log('Logo image element found:', logoImg.src);
                    console.log('Logo image computed style:', window.getComputedStyle(logoImg).display);
                    console.log('Logo image natural dimensions:', logoImg.naturalWidth, 'x', logoImg.naturalHeight);
                    
                    // Force show for testing
                    logoImg.style.display = 'block';
                    logoImg.style.visibility = 'visible';
                    logoImg.style.opacity = '1';
                    logoImg.style.position = 'relative';
                    logoImg.style.zIndex = '10';
                }
            }, 1000);
            
            // Test developer photo  
            setTimeout(() => {
                const devPhoto = document.getElementById('developerPhoto');
                if (devPhoto) {
                    console.log('Dev photo element found:', devPhoto.src);
                    console.log('Dev photo computed style:', window.getComputedStyle(devPhoto).display);
                    console.log('Dev photo natural dimensions:', devPhoto.naturalWidth, 'x', devPhoto.naturalHeight);
                    
                    // Force show for testing
                    devPhoto.style.display = 'block';
                    devPhoto.style.visibility = 'visible';
                    devPhoto.style.opacity = '1';
                    devPhoto.style.position = 'relative';
                    devPhoto.style.zIndex = '10';
                }
            }, 1000);
        });

        // Auto-refresh statistics every 30 seconds for admin/teacher
        <?php if ($role == 'admin' || $role == 'teacher'): ?>
        setInterval(function() {
            const refreshBtn = document.querySelector('.refresh-btn');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1 spin"></i>Refreshing...';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
