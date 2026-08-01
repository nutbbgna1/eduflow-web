<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0);
    $due_date   = $_POST['due_date'] ?: null;
    $description = trim($_POST['description'] ?? '');

    if ($student_id && $subject_id && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO payments (student_id, subject_id, amount, due_date, description, status) VALUES (:sid, :subid, :amt, :due, :desc, 'unpaid')");
        $stmt->execute([
            'sid'   => $student_id,
            'subid' => $subject_id,
            'amt'   => $amount,
            'due'   => $due_date,
            'desc'  => $description,
        ]);
    }
}
header("Location: ../finance.php?payment=added");
exit;
