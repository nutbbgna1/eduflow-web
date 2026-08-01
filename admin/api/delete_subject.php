<?php
require_once '../includes/db.php';
require_login('admin');

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Must check if used in enrollments or schedules first
        // For simplicity, we just try to delete, if constraints fail, we catch
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = :id");
        $stmt->execute(['id' => $id]);
    } catch (\PDOException $e) {
        // Can't delete if referenced
        header("Location: ../subjects.php?error=in_use");
        exit;
    }
}
header("Location: ../subjects.php");
exit;
