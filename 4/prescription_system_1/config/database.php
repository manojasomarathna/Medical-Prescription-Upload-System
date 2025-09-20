<?php
// Database configuration with port 3307
$host = 'localhost';
$port = '3307';        // Your MySQL port
$username = 'root';
$password = '';
$database = 'prescription_system_db';

try {
    // Include port in connection string
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Better error message with troubleshooting tips
    $error_msg = "Database connection failed: " . $e->getMessage();
    
    // Add helpful debugging info
    if (strpos($e->getMessage(), 'refused') !== false) {
        $error_msg .= "<br><br><strong>🔧 Troubleshooting:</strong><br>";
        $error_msg .= "1. Check if MySQL is running on port 3307<br>";
        $error_msg .= "2. Open XAMPP Control Panel<br>";
        $error_msg .= "3. Make sure MySQL shows 'Running' status<br>";
        $error_msg .= "4. Test phpMyAdmin: <a href='http://localhost:3307/phpmyadmin'>http://localhost:3307/phpmyadmin</a><br>";
        $error_msg .= "5. Or try: <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        $error_msg .= "<br><br><strong>🗃️ Database Issue:</strong><br>";
        $error_msg .= "Database 'prescription_system_db' not found!<br>";
        $error_msg .= "1. Go to phpMyAdmin<br>";
        $error_msg .= "2. Create new database: prescription_system_db<br>";
        $error_msg .= "3. Import the SQL schema";
    }
    
    die("<div style='background:#f8d7da;color:#721c24;padding:20px;border-radius:8px;margin:20px;font-family:Arial;border:1px solid #f5c6cb;'>
    <h3>❌ Database Connection Error</h3>
    <p>$error_msg</p>
    </div>");
}

// Start session
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isPharmacy() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'pharmacy';
}

function isUser() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Email configuration (for quotation notifications)
$smtp_host = 'smtp.gmail.com'; // Change as needed
$smtp_username = 'your_email@gmail.com';
$smtp_password = 'your_password';
$smtp_port = 587;
?>