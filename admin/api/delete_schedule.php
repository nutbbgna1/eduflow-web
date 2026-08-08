<?php
require_once '../includes/db.php';
require_login('admin');

$id = $_GET['id'] ?? '';

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: ../schedule.php");
exit;
