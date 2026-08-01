<?php
require_once __DIR__ . '/../config/db.php';

// Fetch teacher details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $current_user_id]);
$teacher = $stmt->fetch();

// Fetch schedules for all days
$stmt = $pdo->prepare("
    SELECT s.*, sub.name as subject_name, sub.code as subject_code
    FROM schedules s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.teacher_id = :tid
    ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time
");
$stmt->execute(['tid' => $current_user_id]);
$all_schedules = $stmt->fetchAll();

// Group by day
$schedules_by_day = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => []
];

foreach ($all_schedules as $schedule) {
    if (isset($schedules_by_day[$schedule['day_of_week']])) {
        $schedules_by_day[$schedule['day_of_week']][] = $schedule;
    }
}

$thai_days = [
    'Monday' => 'วันจันทร์',
    'Tuesday' => 'วันอังคาร',
    'Wednesday' => 'วันพุธ',
    'Thursday' => 'วันพฤหัสบดี',
    'Friday' => 'วันศุกร์'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ตารางสอนสัปดาห์นี้</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .day-header {
            background-color: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="app-container">
        
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-4 animate-slide-up">
            <h3 class="font-bold text-xl mb-4">ตารางสอนสัปดาห์นี้</h3>
            
            <div id="weekly-schedule-container">
                <?php foreach($schedules_by_day as $day => $schedules): ?>
                    <?php if (count($schedules) > 0): ?>
                        <div class="day-header"><?= $thai_days[$day] ?></div>
                        <div class="timeline mb-6">
                            <?php foreach($schedules as $schedule): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background-color: #E2E8F0; border-color: #CBD5E1;"></div>
                                    <div class="timeline-card">
                                        <div class="flex items-center gap-2 text-sm text-secondary mb-1">
                                            <span class="material-symbols-rounded" style="font-size: 16px;">schedule</span> 
                                            <?= date('H:i', strtotime($schedule['start_time'])) ?> - <?= date('H:i', strtotime($schedule['end_time'])) ?>
                                        </div>
                                        <h4 class="font-bold text-lg mb-1"><?= htmlspecialchars($schedule['subject_name']) ?></h4>
                                        <div class="flex items-center gap-1 text-sm text-secondary">
                                            <span class="material-symbols-rounded" style="font-size: 16px;">location_on</span> <?= htmlspecialchars($schedule['room']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="day-header" style="background-color: var(--secondary); opacity: 0.7;"><?= $thai_days[$day] ?></div>
                        <div class="text-center p-4 text-secondary text-sm mb-4">ไม่มีคาบสอน</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
