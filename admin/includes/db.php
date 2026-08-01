<?php
// admin/includes/db.php
// Include the main database connection (shared with teacher app)
require_once __DIR__ . '/../../config/db.php';

// Helper: get initials color from name
function getAvatarColor($name) {
    $colors = ['#2563EB','#7C3AED','#DB2777','#059669','#D97706','#0891B2','#DC2626','#4F46E5'];
    $idx = ctype_alpha($name[0]) ? ord(strtoupper($name[0])) % count($colors) : 0;
    return $colors[$idx];
}

// Helper: get initials
function getInitials($first, $last) {
    return strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
}

// Helper: Thai leave type
function leaveTypeLabel($type) {
    return match($type) {
        'sick'     => 'ลาป่วย',
        'personal' => 'ลากิจ',
        'vacation' => 'ลาพักผ่อน',
        default    => ucfirst($type)
    };
}

// Helper: Thai day
function thaiDay($day) {
    return match($day) {
        'Monday'    => 'จันทร์',
        'Tuesday'   => 'อังคาร',
        'Wednesday' => 'พุธ',
        'Thursday'  => 'พฤหัสบดี',
        'Friday'    => 'ศุกร์',
        'Saturday'  => 'เสาร์',
        'Sunday'    => 'อาทิตย์',
        default     => $day
    };
}

// ===================================
// AUTO-MIGRATION: add columns only
// ===================================
if ($pdo) {
    // Utility: check if column exists
    $col_check = fn($table, $col) => (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$col'")->fetchColumn();

    // Users table extra columns
    if (!$col_check('users', 'email'))       $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL");
    if (!$col_check('users', 'phone'))       $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL");
    if (!$col_check('users', 'hourly_rate')) $pdo->exec("ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT 500.00");
    if (!$col_check('users', 'status'))      $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','inactive','on_leave') DEFAULT 'active'");

    // Students table extra columns
    if (!$col_check('students', 'grade'))     $pdo->exec("ALTER TABLE students ADD COLUMN grade VARCHAR(20) NULL");
    if (!$col_check('students', 'program'))   $pdo->exec("ALTER TABLE students ADD COLUMN program VARCHAR(50) NULL");
    if (!$col_check('students', 'status'))    $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('enrolled','at_risk','inactive') DEFAULT 'enrolled'");
    if (!$col_check('students', 'username'))  $pdo->exec("ALTER TABLE students ADD COLUMN username VARCHAR(50) NULL");
    if (!$col_check('students', 'password'))  $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL");

    // Set mock data for students if username is missing
    $pdo->exec("UPDATE students SET username = CONCAT('student', id), password = '1234' WHERE username IS NULL");

    // Categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            parent_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
        )
    ");

    // Course contents table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_contents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content_type ENUM('video', 'document') NOT NULL,
            file_url VARCHAR(255) NOT NULL,
            order_index INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        )
    ");

    // Subjects table extra columns
    if (!$col_check('subjects', 'category'))  $pdo->exec("ALTER TABLE subjects ADD COLUMN category VARCHAR(100) NULL");
    if (!$col_check('subjects', 'category_id')) $pdo->exec("ALTER TABLE subjects ADD COLUMN category_id INT NULL");
    if (!$col_check('subjects', 'subcategory_id')) $pdo->exec("ALTER TABLE subjects ADD COLUMN subcategory_id INT NULL");
    
    // Enrollments table extra columns
    if (!$col_check('enrollments', 'status')) $pdo->exec("ALTER TABLE enrollments ADD COLUMN status ENUM('active', 'expired') DEFAULT 'active'");
    
    // Add foreign keys if they don't exist
    try { $pdo->exec("ALTER TABLE subjects ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE subjects ADD FOREIGN KEY (subcategory_id) REFERENCES categories(id) ON DELETE SET NULL"); } catch (Exception $e) {}

    // Payments table (create if not exists — no seeding)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            description VARCHAR(255),
            due_date DATE,
            paid_date DATE NULL,
            status ENUM('unpaid','pending_confirm','paid') DEFAULT 'unpaid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id),
            FOREIGN KEY (subject_id) REFERENCES subjects(id)
        )
    ");

    // Add leave_requests reason column if missing
    if (!$col_check('leave_requests', 'reason')) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN reason TEXT NULL");
    }
}
?>
