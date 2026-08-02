<?php
require_once 'config/db.php';

try {
    // 1. Teacher
    $teacher1 = $pdo->query("SELECT id FROM users WHERE username = 'teacher1'")->fetchColumn();
    if (!$teacher1) {
        $pdo->exec("INSERT INTO users (username, password, first_name, last_name, role) VALUES ('teacher1', '1234', 'ดร. สมหญิง', 'รักเรียน', 'teacher')");
        $teacher1 = $pdo->query("SELECT id FROM users WHERE username = 'teacher1'")->fetchColumn();
    }

    // 2. Subject
    $subject_exists = $pdo->query("SELECT COUNT(*) FROM subjects WHERE id = 1")->fetchColumn();
    if ($subject_exists == 0) {
        $pdo->exec("INSERT INTO subjects (id, code, name, description) VALUES (1, 'PHY101', 'ฟิสิกส์ ม.5', 'วิชาฟิสิกส์พื้นฐาน')");
    }

    // 3. Student S001
    $student1 = $pdo->query("SELECT id FROM students WHERE username = 'S001'")->fetchColumn();
    if (!$student1) {
        $pdo->exec("INSERT INTO students (student_code, first_name, last_name, username, password) VALUES ('STU-001', 'สมชาย', 'รักเรียน', 'S001', '1234')");
        $student1 = $pdo->query("SELECT id FROM students WHERE username = 'S001'")->fetchColumn();
    }

    // 4. Enroll S001 to Subject 1
    $enroll_exists = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE student_id = $student1 AND subject_id = 1")->fetchColumn();
    if ($enroll_exists == 0) {
        $pdo->exec("INSERT INTO enrollments (student_id, subject_id, status) VALUES ($student1, 1, 'active')");
    }

    // 5. Schedule
    $schedule_exists = $pdo->query("SELECT COUNT(*) FROM schedules WHERE subject_id = 1 AND teacher_id = $teacher1")->fetchColumn();
    if ($schedule_exists == 0) {
        $pdo->exec("INSERT INTO schedules (teacher_id, subject_id, room, day_of_week, start_time, end_time) VALUES ($teacher1, 1, 'ห้อง 302', 'Thursday', '09:00:00', '10:30:00')");
    }

    // 6. Course Content
    $content_exists = $pdo->query("SELECT COUNT(*) FROM course_contents WHERE subject_id = 1")->fetchColumn();
    if ($content_exists == 0) {
        $pdo->exec("INSERT INTO course_contents (subject_id, teacher_id, title, content_type, file_url, order_num) VALUES (1, $teacher1, 'บทที่ 1: การเคลื่อนที่แนวตรง', 'video', 'https://www.youtube.com/watch?v=example', 1)");
    }

    // 7. Assignment
    $assign_exists = $pdo->query("SELECT COUNT(*) FROM assignments WHERE subject_id = 1")->fetchColumn();
    if ($assign_exists == 0) {
        $pdo->exec("INSERT INTO assignments (subject_id, teacher_id, title, description, due_date) VALUES (1, $teacher1, 'แบบฝึกหัดที่ 1 (ทดสอบ)', 'ให้นักเรียนทำแบบฝึกหัดแล้วส่งเป็นลิงก์ Google Drive หรือข้อความ', '2026-12-31 23:59:59')");
    }

    echo "<h2 style='color:green;'>✅ สร้างข้อมูลทดสอบสำเร็จ!</h2>";
    echo "<p>ระบบได้สร้างนักเรียน S001 (รหัสผ่าน 1234) และครู teacher1 พร้อมวิชาเรียนให้แล้วครับ</p>";
    echo "<a href='index.php'>กลับไปหน้าเข้าสู่ระบบ</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
