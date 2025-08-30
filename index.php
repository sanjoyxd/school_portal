<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Don Bosco School Portal</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .school-logo {
            width: 90px;
            height: 90px;
            margin-bottom: 1rem;
            background: white;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .welcome-card {
            background: rgba(255,255,255,0.95);
            padding: 2rem;
            max-width: 550px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            text-align: center;
            animation: fadeInUp 1s ease;
        }
        .welcome-card h1 {
            color: #003366;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .welcome-card p {
            font-size: 1rem;
            color: #555;
            margin-bottom: 1rem;
        }
        .welcome-btn {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            color: white;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .welcome-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40,167,69,0.4);
        }
        footer {
            background: #003366;
            color: white;
            text-align: center;
            padding: 0.7rem;
            font-size: 0.85rem;
            margin-top: auto;
        }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(30px);}
            100% { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="school-logo mx-auto">
        <!-- Replace with your logo -->
        <img src="assets/images/school-logo.png" alt="School Logo" onerror="this.src='assets/images/default-logo.png'">
    </div>
    <h2 class="mb-0">Don Bosco School, Barpeta Road</h2>
    <small class="opacity-75">Computer Science Portal</small>
</header>

<!-- Main Content -->
<main>
    <div class="welcome-card">
        <h1>Welcome to the Learning Portal</h1>
        <p>
            Access experiments, take online tests, and explore resources to boost your learning in computer science.
        </p>
        <p>
            Stay connected and learn smarter — anytime, anywhere within the school network and from home.
        </p>
        <a href="login.php" class="btn welcome-btn">
            <i class="bi bi-box-arrow-in-right me-2"></i> Login to Continue
        </a>
    </div>
</main>

<!-- Footer -->
<footer>
    &copy; <?php echo date('Y'); ?> Don Bosco School | Computer Science Department | Developed by Sir Sanjoy Choudhury
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
