<?php
/**
 * ExamChecker — Auto-migration
 * สร้าง 3 tables ใน eduflow_db อัตโนมัติถ้ายังไม่มี
 */

function exam_auto_migrate($pdo) {
    // Table 1: exams
    try {
        $pdo->query("SELECT 1 FROM exams LIMIT 1");
    } catch (\PDOException $e) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS exams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                subject_id INT NOT NULL,
                teacher_id INT NOT NULL,
                exam_code VARCHAR(10) NULL,
                title VARCHAR(200) NOT NULL,
                total_questions INT DEFAULT 50,
                choices_count INT DEFAULT 5,
                points_per_question DECIMAL(5,2) DEFAULT 1.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (subject_id) REFERENCES subjects(id),
                FOREIGN KEY (teacher_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Table 2: exam_answer_keys
    try {
        $pdo->query("SELECT 1 FROM exam_answer_keys LIMIT 1");
    } catch (\PDOException $e) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS exam_answer_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                exam_id INT NOT NULL,
                question_no INT NOT NULL,
                correct_answer CHAR(1) NOT NULL,
                points DECIMAL(5,2) DEFAULT 1.00,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
                UNIQUE KEY unique_exam_question (exam_id, question_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Table 3: exam_scan_results
    try {
        $pdo->query("SELECT 1 FROM exam_scan_results LIMIT 1");
    } catch (\PDOException $e) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS exam_scan_results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                exam_id INT NOT NULL,
                student_id INT NOT NULL,
                answers_json TEXT,
                total_score DECIMAL(8,2) DEFAULT 0,
                total_possible DECIMAL(8,2) DEFAULT 0,
                correct_count INT DEFAULT 0,
                wrong_count INT DEFAULT 0,
                blank_count INT DEFAULT 0,
                image_path VARCHAR(500),
                scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES students(id),
                UNIQUE KEY unique_exam_student (exam_id, student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
?>
