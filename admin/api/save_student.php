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

    if (!$first_name || !$last_name || !$student_code) {
        header("Location: ../students.php?error=missing_fields");
        exit;
    }

    // Check code not taken
    $check = $pdo->prepare("SELECT id FROM students WHERE student_code = :c");
    $check->execute(['c' => $student_code]);
    if ($check->fetch()) {
        header("Location: ../students.php?error=code_taken");
        exit;
    }

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
