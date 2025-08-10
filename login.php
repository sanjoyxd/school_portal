<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepared statement for security
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Bosco School Portal - Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .school-header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: visible;
        }
        .school-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .school-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .login-form {
            padding: 2.5rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .developer-credit {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 1rem;
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .developer-credit a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .developer-credit a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; left: 80%; animation-delay: 2s; }
        .floating-element:nth-child(3) { top: 40%; left: 60%; animation-delay: 4s; }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        .error-alert {
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
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
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <i class="bi bi-mortarboard floating-element" style="font-size: 3rem;"></i>
        <i class="bi bi-book floating-element" style="font-size: 2.5rem;"></i>
        <i class="bi bi-pencil floating-element" style="font-size: 2rem;"></i>
    </div>

    <div class="login-container">
        <div class="login-card">
            <!-- School Header -->
            <div class="school-header">
                <div class="school-logo">
                    <img src="assets/images/school-logo.png" 
                         alt="Don Bosco School Logo" 
                         class="logo-image"
                         id="loginLogo"
                         style="width: 72px; height: 72px; object-fit: contain; border-radius: 50%; display: block;"
                         onload="console.log('Login logo loaded successfully'); this.style.display='block';"
                         onerror="console.log('Login logo failed, showing fallback'); this.style.display='none'; document.getElementById('loginLogoFallback').style.display='flex';">
                    <!-- Clean fallback -->
                    <div id="loginLogoFallback" style="display: none; width: 72px; height: 72px; background: white; border-radius: 50%; 
                                color: #003366; font-weight: bold; font-size: 2rem;
                                position: absolute; top: 4px; left: 4px;
                                align-items: center; justify-content: center;">
                        DBS
                    </div>
                </div>
                <div class="school-name">Don Bosco School</div>
                <div class="school-subtitle">Barpeta Road, Assam</div>
                <small class="d-block mt-2 opacity-75">
                    <i class="bi bi-shield-check me-1"></i>Secure Portal Access
                </small>
            </div>

            <!-- Login Form -->
            <div class="login-form">
                <h4 class="text-center mb-4 text-dark">
                    <i class="bi bi-person-circle me-2"></i>Sign In to Portal
                </h4>

                <?php if (!empty($error)): ?>
                <div class="alert error-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In to Portal
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Contact administration for login credentials
                    </small>
                </div>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <i class="bi bi-code-slash me-1"></i>
                Developed with ❤️ by <a href="#" data-bs-toggle="modal" data-bs-target="#developerModal">Sir Sanjoy</a>
                <br><small class="text-muted mt-1">School Computer Lab Management System by Mr. xD v1.1</small>
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
                            <li><i class="bi bi-check-circle text-success me-2"></i>Secure Authentication</li>
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
                const logoImg = document.getElementById('loginLogo');
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

        // Auto-hide error alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.error-alert');
            if (errorAlert) {
                setTimeout(() => {
                    const alert = new bootstrap.Alert(errorAlert);
                    alert.close();
                }, 5000);
            }
        });
    </script>
</body>
</html>
