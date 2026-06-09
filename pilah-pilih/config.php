<?php
/**
 * Database Configuration File
 * Pilah-Pilih Partner Dashboard
 * 
 * This file handles all database connections using PDO with prepared statements
 * for SQL injection prevention.
 */

// Database Credentials (UPDATE WITH YOUR DATABASE INFO)
// Contoh penyesuaian di config.php
$db_host = 'localhost';
$db_user = 'root';        // Standar XAMPP/WAMP
$db_pass = '';            // Standar XAMPP/WAMP (kosong)
$db_name = 'db_pilah_pilih';
$db_charset = 'utf8mb4';

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass);

// PDO Configuration
define('DB_CHARSET', 'utf8mb4');

// Create PDO Connection
try {
    $pdo = new PDO(
        'mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=' . $db_charset,
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Session Configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);  // Set to 1 if using HTTPS (recommended for production)
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);  // 1 hour session timeout

// Start Session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Log audit action to audit_logs table
 */
function log_audit_action($user_id, $action, $details = '', $pdo) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        error_log("Audit logging failed: " . $e->getMessage());
    }
}

/**
 * Hash password securely using bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Redirect to login if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
