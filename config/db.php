<?php
$host = "localhost";
$user = "schooluser";
$pass = "StrongPassword123"; // WAMP default has no password
$db   = "schoolportal";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: Set charset to avoid special character issues
$conn->set_charset("utf8mb4");
?>
