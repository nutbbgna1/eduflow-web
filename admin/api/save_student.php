<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $student_code = trim($_POST['student_code'] ?? '');
    $grade        = trim($_POST['grade'] ?? '');
    $program      = trim($_POST['program'] ?? '');
    $rfid_tag     = trim($_POST['rfid_tag'] ?? '') ?: null;
    $status       = 'enrolled';

    if (!$first_name || !$last_name) {
        header("Location: ../students.php?error=missing_fields");
        exit;
    }

    // Generate student_code like STU-001 right before insert to prevent race conditions
    $stmt_code = $pdo->query("SELECT student_code FROM students WHERE student_code LIKE 'STU-%' ORDER BY CAST(SUBSTRING(student_code, 5) AS UNSIGNED) DESC LIMIT 1");
    $last_code = $stmt_code->fetchColumn();
    $next_num = $last_code ? ((int)substr($last_code, 4)) + 1 : 1;
    $student_code = 'STU-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("
        INSERT INTO students (student_code, first_name, last_name, rfid_tag, grade, program, status)
        VALUES (:code, :fn, :ln, :rfid, :grade, :program, :status)
    ");
    $stmt->execute([
        'code'    => $student_code,
        'fn'      => $first_name,
        'ln'      => $last_name,
        'rfid'    => $rfid_tag,
        'grade'   => $grade,
        'program' => $program,
        'status'  => $status,
    ]);

    header("Location: ../students.php?success=1");
    exit;
}
header("Location: ../students.php");
