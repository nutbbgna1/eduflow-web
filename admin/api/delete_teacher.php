<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // We only allow deleting users with role = 'teacher' to be safe
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'teacher'");
    $stmt->execute(['id' => $id]);
    
    header("Location: ../staff.php?success=deleted");
    exit;
}

header("Location: ../staff.php");
