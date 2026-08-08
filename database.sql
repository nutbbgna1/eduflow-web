-- Database Schema for EduFlow (Teacher Check-in System)

CREATE DATABASE IF NOT EXISTS eduflow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduflow_db;

-- 1. Users (Teachers & Admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('teacher', 'admin') DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Subjects/Courses
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_online TINYINT(1) DEFAULT 1,
    is_onsite TINYINT(1) DEFAULT 1,
    description TEXT
);

-- 3. Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    rfid_tag VARCHAR(100) NULL
);

-- 4. Enrollments (Students in Subjects)
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- 5. Schedules (Monthly Timetable)
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    schedule_month VARCHAR(7) NOT NULL DEFAULT '2026-08' COMMENT 'YYYY-MM format',
    status ENUM('draft', 'published') DEFAULT 'published',
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    INDEX idx_schedule_month (schedule_month)
);

-- 6. Leave Requests & Substitutions
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    substitute_id INT NULL, -- The teacher asked to substitute
    schedule_id INT NOT NULL,
    leave_date DATE NOT NULL,
    leave_type ENUM('sick', 'personal', 'vacation') NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    substitute_status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (substitute_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

-- 7. RFID Checkins (Student Taps)
CREATE TABLE IF NOT EXISTS checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    checkin_time DATETIME NOT NULL,
    source ENUM('rfid', 'manual') DEFAULT 'rfid',
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- 8. Teaching Logs (Class Check-ins)
CREATE TABLE IF NOT EXISTS teaching_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    actual_teacher_id INT NOT NULL,
    log_date DATE NOT NULL,
    checkin_time DATETIME NOT NULL,
    photo_url VARCHAR(255) NULL,
    is_substitution BOOLEAN DEFAULT FALSE,
    hours DECIMAL(5,2) DEFAULT 1.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id),
    FOREIGN KEY (actual_teacher_id) REFERENCES users(id)
);

-- 9. Teaching Log Students (Attendance for the class)
CREATE TABLE IF NOT EXISTS teaching_log_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teaching_log_id INT NOT NULL,
    student_id INT NOT NULL,
    source ENUM('rfid', 'manual') DEFAULT 'manual',
    FOREIGN KEY (teaching_log_id) REFERENCES teaching_logs(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Mock Data Injection
INSERT INTO users (id, username, password, first_name, last_name) VALUES 
(1, 'teacher1', '1234', 'ดร. สมหญิง', 'รักเรียน'),
(2, 'teacher2', '1234', 'ครูสมศักดิ์', 'รักไทย');

INSERT INTO subjects (id, code, name) VALUES 
(1, 'PHY101', 'ฟิสิกส์ ม.5/1'),
(2, 'PHY102', 'ฟิสิกส์ ม.5/2'),
(3, 'AST101', 'ดาราศาสตร์ ม.4/3');

INSERT INTO students (id, student_code, first_name, last_name, rfid_tag) VALUES 
(1, 'S001', 'ด.ช. สมชาย', 'ใจดี', 'RFID001'),
(2, 'S002', 'ด.ญ. สมหญิง', 'รักเรียน', 'RFID002'),
(3, 'S003', 'ด.ช. ปิติ', 'มานะ', 'RFID003');

INSERT INTO enrollments (student_id, subject_id) VALUES 
(1, 1), (2, 1), (3, 1),
(1, 2), (2, 2);

-- Assuming today is Thursday for the mock display
INSERT INTO schedules (id, teacher_id, subject_id, room, day_of_week, start_time, end_time) VALUES 
(1, 1, 1, 'ห้อง 302', 'Thursday', '08:30:00', '09:20:00'),
(2, 1, 2, 'ห้อง 405', 'Thursday', '09:30:00', '10:20:00'),
(3, 1, 3, 'ห้อง 501', 'Thursday', '11:10:00', '12:00:00');

