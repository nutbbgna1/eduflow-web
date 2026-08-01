<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['request_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['approved','rejected']) ? $_POST['status'] : null;

    if ($id && $status) {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :s WHERE id = :id");
        $stmt->execute(['s' => $status, 'id' => $id]);
        header("Location: ../leave.php?done=$status");
    } else {
        header("Location: ../leave.php?error=invalid");
    }
    exit;
}
header("Location: ../leave.php");
