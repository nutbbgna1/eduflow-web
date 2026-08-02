<?php
// includes/db.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$host = 'localhost';
$db   = 'u402846166_eduflow';
$user = 'u402846166_eduflow';
$pass = '@Min1234@';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("
        <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#0f172a;color:#e2e8f0;}
        .box{background:#1e293b;border:1px solid #ef4444;border-radius:12px;padding:2rem;max-width:500px;text-align:center;}
        h2{color:#ef4444;margin-bottom:1rem;}code{background:#0f172a;padding:0.5rem 1rem;border-radius:6px;display:block;margin-top:1rem;font-size:0.85rem;color:#94a3b8;}</style>
        <div class='box'>
          <h2>⚠️ Database Connection Error</h2>
          <p>ไม่สามารถเชื่อมต่อฐานข้อมูล <strong>eduflow_db</strong> ได้</p>
          <p style='font-size:0.85rem;color:#94a3b8;'>กรุณาตรวจสอบว่า XAMPP MySQL กำลังรันอยู่ และฐานข้อมูลถูกสร้างแล้ว</p>
          <code>" . htmlspecialchars($e->getMessage()) . "</code>
        </div>
    ");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix for legacy mock sessions causing redirect loops
if (isset($_SESSION['user_id']) && !isset($_SESSION['role'])) {
    session_unset();
    session_destroy();
    session_start();
}

function require_login($allowed_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /eduflow/index.php?error=unauthorized");
        exit;
    }
    if ($allowed_role && (!isset($_SESSION['role']) || $_SESSION['role'] !== $allowed_role)) {
        header("Location: /eduflow/index.php?error=forbidden");
        exit;
    }
}

$current_user_id = $_SESSION['user_id'] ?? null;

// Automatic Route Protection based on folder
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script, '/admin/') !== false && strpos($script, '/api/') === false) {
    require_login('admin');
} elseif (strpos($script, '/teacher/') !== false && strpos($script, '/api/') === false) {
    require_login('teacher');
} elseif (strpos($script, '/student/') !== false && strpos($script, '/api/') === false) {
    require_login('student');
}
?>
