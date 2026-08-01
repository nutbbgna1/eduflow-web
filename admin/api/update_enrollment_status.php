<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($enrollment_id && in_array($status, ['active', 'expired'])) {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $enrollment_id]);
    }
}
header("Location: ../student_enrollments.php");
exit;
