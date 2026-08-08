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
    $id = $_POST['id'] ?? '';
    $schedule_month = $_POST['schedule_month'] ?? date('Y-m');
    
    // If room is empty (disabled in frontend for online courses), default to 'Online'
    if (empty($room)) {
        $room = 'Online';
    }

    if ($teacher_id && $subject_id && $room && $day_of_week && $start_time && $end_time) {
        // Conflict detection: check if teacher or room is already booked at same time/day/month
        $conflict_sql = "SELECT s.id, sub.name as subject_name, u.first_name, u.last_name 
                         FROM schedules s 
                         JOIN subjects sub ON s.subject_id = sub.id 
                         JOIN users u ON s.teacher_id = u.id 
                         WHERE s.schedule_month = :month 
                           AND s.day_of_week = :day 
                           AND s.start_time < :end_time 
                           AND s.end_time > :start_time 
                           AND (s.teacher_id = :teacher_id OR (s.room = :room AND s.room != 'Online'))";
        
        // Exclude current schedule if editing
        if ($id) {
            $conflict_sql .= " AND s.id != :exclude_id";
        }
        
        $conflict_stmt = $pdo->prepare($conflict_sql);
        $conflict_params = [
            'month' => $schedule_month,
            'day' => $day_of_week,
            'end_time' => $end_time,
            'start_time' => $start_time,
            'teacher_id' => $teacher_id,
            'room' => $room,
        ];
        if ($id) {
            $conflict_params['exclude_id'] = $id;
        }
        $conflict_stmt->execute($conflict_params);
        $conflict = $conflict_stmt->fetch();
        
        if ($conflict) {
            header("Location: ../schedule.php?month=$schedule_month&error=conflict");
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE schedules SET teacher_id=?, subject_id=?, room=?, day_of_week=?, start_time=?, end_time=?, schedule_month=? WHERE id=?");
            $stmt->execute([$teacher_id, $subject_id, $room, $day_of_week, $start_time, $end_time, $schedule_month, $id]);
        } else {
            // Get the status of this month (if there are existing schedules, match their status; otherwise default to draft)
            $status_stmt = $pdo->prepare("SELECT status FROM schedules WHERE schedule_month = ? LIMIT 1");
            $status_stmt->execute([$schedule_month]);
            $existing_status = $status_stmt->fetchColumn();
            $status = $existing_status ?: 'draft';
            
            $stmt = $pdo->prepare("INSERT INTO schedules (teacher_id, subject_id, room, day_of_week, start_time, end_time, schedule_month, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $subject_id, $room, $day_of_week, $start_time, $end_time, $schedule_month, $status]);
        }
    }
    
    header("Location: ../schedule.php?month=$schedule_month");
    exit;
}

header("Location: ../schedule.php");
exit;
