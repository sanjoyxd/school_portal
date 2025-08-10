<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Don Bosco School Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            background: #f8f8f8;
        }
        header {
            background-color: #003366;
            color: white;
            padding: 20px;
        }
        h1 {
            margin: 0;
        }
        p {
            font-size: 18px;
            margin-top: 20px;
        }
        a.login-btn {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            margin-top: 20px;
            border-radius: 5px;
        }
        a.login-btn:hover {
            background: #218838;
        }
        footer {
            background: #003366;
            color: white;
            padding: 10px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
<header>
    <h1>Don Bosco School, Barpeta Road</h1>
    <h2>Computer Science Portal</h2>
</header>

<main>
    <p>Welcome to the school’s official learning and testing portal.</p>
    <p>Here you can view experiments, take online tests, and read articles.</p>
    <a class="login-btn" href="login.php">Login to Continue</a>
</main>

<footer>
    &copy; <?php echo date("Y"); ?> Don Bosco School | Computer Science Department
</footer>
</body>
</html>
