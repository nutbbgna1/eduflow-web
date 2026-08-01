<?php
// migrate.php - ดึงค่าคอนฟิกฐานข้อมูล
require_once 'config/db.php';

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

-- 1. ลบตารางเก่าทิ้งให้หมดเพื่อเคลียร์ปัญหา
DROP TABLE IF EXISTS student_grades;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS course_contents;
DROP TABLE IF EXISTS teaching_log_students;
DROP TABLE IF EXISTS teaching_logs;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS checkins;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS users;

-- 2. สร้างโครงสร้างหลัก
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('teacher', 'admin', 'student') DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    rfid_tag VARCHAR(100) NULL,
    username VARCHAR(50) NULL,
    password VARCHAR(255) NULL
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 3. สร้างโครงสร้างส่วนฟีเจอร์เพิ่มเติม
CREATE TABLE course_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('video', 'document') NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    order_num INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_score INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE student_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT NULL,
    submission_file_url VARCHAR(255) NULL,
    submitted_at TIMESTAMP NULL,
    status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
    score DECIMAL(5,2) DEFAULT NULL,
    feedback TEXT,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    checkin_time DATETIME NOT NULL,
    source ENUM('rfid', 'manual') DEFAULT 'rfid',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE teaching_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    actual_teacher_id INT NOT NULL,
    log_date DATE NOT NULL,
    checkin_time DATETIME NOT NULL,
    photo_url VARCHAR(255) NULL,
    is_substitution BOOLEAN DEFAULT FALSE,
    hours DECIMAL(5,2) DEFAULT 1.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (actual_teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE teaching_log_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teaching_log_id INT NOT NULL,
    student_id INT NOT NULL,
    source ENUM('rfid', 'manual') DEFAULT 'manual',
    FOREIGN KEY (teaching_log_id) REFERENCES teaching_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    substitute_id INT NULL,
    schedule_id INT NOT NULL,
    leave_date DATE NOT NULL,
    leave_type ENUM('sick', 'personal', 'vacation') NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    substitute_status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
);

-- 4. ใส่ข้อมูลจำลองแบบคลีนๆ
INSERT INTO users (id, username, password, first_name, last_name, role) VALUES 
(1, 'teacher1', '1234', 'ดร. สมหญิง', 'รักเรียน', 'teacher'),
(2, 'admin1', '1234', 'แอดมิน', 'ระบบ', 'admin');

INSERT INTO subjects (id, code, name, description) VALUES 
(1, 'PHY101', 'ฟิสิกส์ ม.5', 'วิชาฟิสิกส์พื้นฐาน');

INSERT INTO students (id, student_code, first_name, last_name, username, password) VALUES 
(1, 'S001', 'ด.ช. สมชาย', 'ใจดี', 'S001', '1234');

INSERT INTO enrollments (student_id, subject_id) VALUES 
(1, 1);

INSERT INTO schedules (id, teacher_id, subject_id, room, day_of_week, start_time, end_time) VALUES 
(1, 1, 1, 'ห้อง 302', 'Thursday', '09:00:00', '10:30:00');

INSERT INTO course_contents (subject_id, teacher_id, title, content_type, file_url, order_num) VALUES 
(1, 1, 'บทที่ 1: การเคลื่อนที่แนวตรง', 'video', 'https://www.youtube.com/watch?v=example', 1);

INSERT INTO assignments (id, subject_id, teacher_id, title, description, due_date, max_score) VALUES 
(1, 1, 1, 'แบบฝึกหัดที่ 1', 'ให้นักเรียนทำแบบฝึกหัดแล้วส่งเป็นไฟล์ PDF', '2026-12-31 23:59:59', 10);

INSERT INTO announcements (subject_id, teacher_id, message) VALUES 
(1, 1, 'ยินดีต้อนรับเข้าสู่วิชาฟิสิกส์ครับทุกคน!');

SET FOREIGN_KEY_CHECKS = 1;
";

try {
    $pdo->exec($sql);
    echo "<h2 style='color:green;'>รีเซ็ตและสร้างฐานข้อมูลสำเร็จ 100%!</h2>";
    echo "<p>ตอนนี้ฐานข้อมูลสะอาดเรียบร้อยแล้ว คุณสามารถกลับไปเข้าใช้งานได้เลยครับ</p>";
    echo "<a href='index.php'>กลับหน้าแรก</a>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>เกิดข้อผิดพลาด:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
