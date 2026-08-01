<?php
require_once '../includes/db.php';
require_login('admin');

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
    } catch (\PDOException $e) {
        header("Location: ../rooms.php?error=in_use");
        exit;
    }
}
header("Location: ../rooms.php");
exit;
