<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_month = $_POST['source_month'] ?? '';
    $target_month = $_POST['target_month'] ?? '';
    
    if ($source_month && $target_month && $source_month !== $target_month) {
        // Check if target month already has schedules
        $check = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE schedule_month = ?");
        $check->execute([$target_month]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            header("Location: ../schedule.php?month=$target_month&error=exists");
            exit;
        }
        
        // Copy all schedules from source to target month as draft
        $stmt = $pdo->prepare("
            INSERT INTO schedules (teacher_id, subject_id, room, day_of_week, start_time, end_time, schedule_month, status)
            SELECT teacher_id, subject_id, room, day_of_week, start_time, end_time, :target, 'draft'
            FROM schedules
            WHERE schedule_month = :source
        ");
        $stmt->execute(['target' => $target_month, 'source' => $source_month]);
        
        header("Location: ../schedule.php?month=$target_month&success=copied");
        exit;
    }
}

header("Location: ../schedule.php");
exit;
