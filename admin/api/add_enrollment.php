<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $return_to = $_POST['return_to'] ?? '';

    if ($student_id && $subject_id) {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?");
        $stmt->execute([$student_id, $subject_id]);
        if ($stmt->fetch()) {
            // Already enrolled, update status to active
            $pdo->prepare("UPDATE enrollments SET status = 'active' WHERE student_id = ? AND subject_id = ?")->execute([$student_id, $subject_id]);
        } else {
            // Insert new enrollment
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$student_id, $subject_id]);
        }
    }
}

if (!empty($return_to)) {
    header("Location: ../" . ltrim($return_to, '/'));
} else {
    header("Location: ../student_enrollments.php" . ($subject_id ? "?subject_id=$subject_id" : ""));
}
exit;
