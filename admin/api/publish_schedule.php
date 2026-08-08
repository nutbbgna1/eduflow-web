<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'] ?? '';
    
    if ($month) {
        $stmt = $pdo->prepare("UPDATE schedules SET status = 'published' WHERE schedule_month = ?");
        $stmt->execute([$month]);
        
        header("Location: ../schedule.php?month=$month&success=published");
        exit;
    }
}

header("Location: ../schedule.php");
exit;
