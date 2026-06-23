<?php
// =============================================
// DATABASE CONFIGURATION
// Hostel Management System
// =============================================
// ★ UPDATE THESE with your InfinityFree credentials ★

define('DB_HOST', 'sql200.infinityfree.com');  // InfinityFree MySQL host (check panel)
define('DB_USER', 'if0_42250741');              // InfinityFree DB username
define('DB_PASS', 'HOST12345');                 // InfinityFree DB password
define('DB_NAME', 'if0_42250741_hostel_management'); // InfinityFree DB name

// Production settings
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// Session save path for shared hosting
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../tmp/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }
    ini_set('session.save_path', $sessionPath);
    session_start();
}

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('DB Connection failed: ' . $conn->connect_error);
        die('<strong>Database connection failed.</strong> Please check credentials in <code>includes/config.php</code>.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
