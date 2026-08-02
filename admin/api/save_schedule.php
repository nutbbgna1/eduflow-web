<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $room = $_POST['room'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    // If room is empty (disabled in frontend for online courses), default to 'Online'
    if (empty($room)) {
        $room = 'Online';
    }

    if ($teacher_id && $subject_id && $room && $day_of_week && $start_time && $end_time) {
        $stmt = $pdo->prepare("INSERT INTO schedules (teacher_id, subject_id, room, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $subject_id, $room, $day_of_week, $start_time, $end_time]);
    }
}

header("Location: ../schedule.php");
exit;
