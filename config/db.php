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

// ===================================
// AUTO-MIGRATION (SAFE UPDATE)
// ===================================
if ($pdo) {
    try {
        $col_check = fn($table, $col) => (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$col'")->fetchColumn();

        // 0. สร้างตารางหลักที่อาจจะหายไปบน Hostinger
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            role ENUM('teacher', 'admin', 'student') DEFAULT 'teacher',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_code VARCHAR(20) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            rfid_tag VARCHAR(100) NULL,
            username VARCHAR(50) NULL,
            password VARCHAR(255) NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            subject_id INT NOT NULL,
            room VARCHAR(50) NOT NULL,
            day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        )");

        // 1. ตรวจสอบและเพิ่มคอลัมน์ใน students
        if (!$col_check('students', 'username')) $pdo->exec("ALTER TABLE students ADD COLUMN username VARCHAR(50) NULL");
        if (!$col_check('students', 'password')) $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL");
        
        // กำหนด Default Username ให้กับนักเรียนเดิมถ้ายังไม่มี
        $pdo->exec("UPDATE students SET username = CONCAT('S', LPAD(id, 3, '0')), password = '1234' WHERE username IS NULL OR username = ''");

        // ตรวจสอบและสร้างบัญชีนักเรียน S001 สำหรับทดสอบ (กันพลาด)
        $s001_exists = $pdo->query("SELECT COUNT(*) FROM students WHERE username = 'S001'")->fetchColumn();
        if ($s001_exists == 0) {
            try {
                $pdo->exec("INSERT INTO students (student_code, first_name, last_name, username, password) VALUES ('STU-001', 'สมชาย', 'รักเรียน', 'S001', '1234')");
            } catch (\Exception $e) {}
        } else {
            // ถัามีอยู่แล้ว รีเซ็ตรหัสผ่านให้เป็น 1234 เผื่อเปลี่ยนไป
            $pdo->exec("UPDATE students SET password = '1234' WHERE username = 'S001'");
        }
        
        // รีเซ็ตรหัสผ่าน teacher1 ให้เป็น 1234 เผื่อเปลี่ยนไป
        $pdo->exec("UPDATE users SET password = '1234' WHERE username = 'teacher1'");

        // 2. สร้างตาราง assignments ถ้ายังไม่มี
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT NOT NULL,
            teacher_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATETIME NOT NULL,
            status ENUM('active', 'closed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // 3. สร้างตาราง student_grades ถ้ายังไม่มี
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            student_id INT NOT NULL,
            submission_text TEXT,
            submission_file_url VARCHAR(255),
            score DECIMAL(5,2),
            status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
            submitted_at DATETIME,
            graded_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_submission (assignment_id, student_id)
        )");

        // หากเคยมีตาราง student_grades แล้ว แต่อาจขาดคอลัมน์ส่งงานใหม่ ให้เพิ่มเข้าไป
        if (!$col_check('student_grades', 'submission_text')) $pdo->exec("ALTER TABLE student_grades ADD COLUMN submission_text TEXT NULL");
        if (!$col_check('student_grades', 'submission_file_url')) $pdo->exec("ALTER TABLE student_grades ADD COLUMN submission_file_url VARCHAR(255) NULL");

        // 4. ตรวจสอบและเพิ่มคอลัมน์อื่นๆ ที่อาจตกหล่นจากฝั่ง Admin
        if (!$col_check('enrollments', 'status')) $pdo->exec("ALTER TABLE enrollments ADD COLUMN status ENUM('active', 'expired') DEFAULT 'active'");
        
        if (!$col_check('users', 'email'))       $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL");
        if (!$col_check('users', 'phone'))       $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL");
        if (!$col_check('users', 'hourly_rate')) $pdo->exec("ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT 500.00");
        if (!$col_check('users', 'status'))      $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','inactive','on_leave') DEFAULT 'active'");

        if (!$col_check('students', 'grade'))     $pdo->exec("ALTER TABLE students ADD COLUMN grade VARCHAR(20) NULL");
        if (!$col_check('students', 'program'))   $pdo->exec("ALTER TABLE students ADD COLUMN program VARCHAR(50) NULL");
        if (!$col_check('students', 'status'))    $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('enrolled','at_risk','inactive') DEFAULT 'enrolled'");

        if (!$col_check('subjects', 'category'))  $pdo->exec("ALTER TABLE subjects ADD COLUMN category VARCHAR(100) NULL");
        if (!$col_check('subjects', 'category_id')) $pdo->exec("ALTER TABLE subjects ADD COLUMN category_id INT NULL");
        if (!$col_check('subjects', 'subcategory_id')) $pdo->exec("ALTER TABLE subjects ADD COLUMN subcategory_id INT NULL");

        if (!$col_check('course_contents', 'order_num')) $pdo->exec("ALTER TABLE course_contents ADD COLUMN order_num INT DEFAULT 0");

    } catch (\Exception $e) {
        // ปล่อยผ่านถ้าบางตารางมี Foreign Key ผูกอยู่แล้วไม่สามารถสร้างซ้ำได้
    }
}

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
