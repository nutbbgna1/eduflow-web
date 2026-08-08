<?php
require_once '../includes/db.php';
require_login('admin');

$id = $_GET['id'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

if ($id) {
    // Check if this schedule has any teaching logs or leave requests referencing it
    $check_logs = $pdo->prepare("SELECT COUNT(*) FROM teaching_logs WHERE schedule_id = ?");
    $check_logs->execute([$id]);
    $log_count = $check_logs->fetchColumn();
    
    $check_leave = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE schedule_id = ?");
    $check_leave->execute([$id]);
    $leave_count = $check_leave->fetchColumn();
    
    if ($log_count > 0 || $leave_count > 0) {
        header("Location: ../schedule.php?month=$month&error=in_use");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: ../schedule.php?month=$month");
exit;
