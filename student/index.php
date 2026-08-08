<?php
require_once '../config/db.php';

$page_title = 'ตารางเรียน';
$student_id = $_SESSION['user_id'] ?? null;

if (!$student_id) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// Check if a specific view is requested (today or weekly)
$view = $_GET['view'] ?? 'today';
$current_day = date('l');

if ($view === 'today') {
    $current_schedule_month = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT sch.id as schedule_id, sch.room, sch.start_time, sch.end_time, sch.day_of_week,
               sub.name as subject_name, sub.code as subject_code,
               t.first_name as teacher_fname, t.last_name as teacher_lname
        FROM enrollments e
        JOIN subjects sub ON e.subject_id = sub.id
        JOIN schedules sch ON sub.id = sch.subject_id
        JOIN users t ON sch.teacher_id = t.id
        WHERE e.student_id = :student_id
          AND sch.day_of_week = :current_day
          AND sch.schedule_month = :month
          AND sch.status = 'published'
        ORDER BY sch.start_time ASC
    ");
    $stmt->execute([
        'student_id' => $student_id,
        'current_day' => $current_day,
        'month' => $current_schedule_month
    ]);
} else {
    $current_schedule_month = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT sch.id as schedule_id, sch.room, sch.start_time, sch.end_time, sch.day_of_week,
               sub.name as subject_name, sub.code as subject_code,
               t.first_name as teacher_fname, t.last_name as teacher_lname
        FROM enrollments e
        JOIN subjects sub ON e.subject_id = sub.id
        JOIN schedules sch ON sub.id = sch.subject_id
        JOIN users t ON sch.teacher_id = t.id
        WHERE e.student_id = :student_id
          AND sch.schedule_month = :month
          AND sch.status = 'published'
        ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), sch.start_time ASC
    ");
    $stmt->execute(['student_id' => $student_id, 'month' => $current_schedule_month]);
}

$schedules = $stmt->fetchAll();

$next_class = null;
$current_time = date('H:i:s');
if ($view === 'today') {
    foreach ($schedules as $sch) {
        if ($sch['start_time'] > $current_time) {
            $next_class = $sch;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Schedule</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .top-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .toggle-switch {
            background: #E2E8F0;
            border-radius: 100px;
            display: flex;
            padding: 4px;
        }
        
        .toggle-btn {
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
        }
        .toggle-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 10px rgba(15, 77, 217, 0.2);
        }
        
        .live-badge {
            background: #fff;
            border-radius: 100px;
            padding: 6px 12px;
            font-size: 10px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            letter-spacing: 0.5px;
        }
        
        .hero-card {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            border-radius: 20px;
            padding: 24px;
            color: #fff;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }
        
        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .hero-room {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            text-align: right;
            margin-top: 16px;
            letter-spacing: -1px;
        }
        
        .timeline-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }
        
        .timeline-card.border-blue {
            border-left: 4px solid var(--primary);
        }
        .timeline-card.border-green {
            border-left: 4px solid #059669;
        }
        .timeline-card.border-gray {
            border-left: 4px solid #94A3B8;
        }
        
        .time-header {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .time-start {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
        }
        .time-start.text-blue { color: var(--primary); }
        .time-start.text-green { color: #059669; }
        
        .time-end {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .subject-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .subject-title {
            font-size: 13px;
            font-weight: 600;
            color: #0F172A;
        }
        
        .status-pill {
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 600;
        }
        .pill-ongoing { background: #6EE7B7; color: #064E3B; }
        .pill-cancelled { background: #FECACA; color: #991B1B; }
        .pill-makeup { background: #059669; color: #fff; }
        
        .instructor-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .room-block {
            text-align: center;
        }
        
        .room-name {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
        }
        .room-name.text-blue { color: var(--primary); }
        .room-name.text-gray { color: #64748B; }
        .room-name.text-green { color: #059669; }
        
        .building-name {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 4px;
            background: transparent;
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 20px;
            font-style: italic;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5">
            <!-- Controls -->
            <div class="top-controls">
                <div class="toggle-switch">
                    <a href="index.php?view=today" class="toggle-btn <?= $view === 'today' ? 'active' : '' ?>">Today</a>
                    <a href="index.php?view=weekly" class="toggle-btn <?= $view === 'weekly' ? 'active' : '' ?>">Weekly</a>
                </div>
                <div class="live-badge">
                    <span style="font-size:16px;">•</span> LIVE UPDATES
                </div>
            </div>
            
            <?php if ($view === 'today' && $next_class): ?>
            <!-- Hero Card (Next Class) -->
            <div class="hero-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="hero-badge">NEXT CLASS</div>
                    <?php
                        $start = strtotime($next_class['start_time']);
                        $now = time();
                        $diff_mins = round(($start - $now) / 60);
                        if ($diff_mins > 0 && $diff_mins <= 60) {
                            $time_text = "Starts in {$diff_mins} mins";
                        } else {
                            $time_text = "Starts at " . date('h:i A', $start);
                        }
                    ?>
                    <div class="text-xs" style="opacity:0.9;"><?= htmlspecialchars($time_text) ?></div>
                </div>
                
                <div class="font-bold text-sm mb-1"><?= htmlspecialchars($next_class['subject_name']) ?></div>
                <div class="flex items-center gap-1 text-xs" style="opacity:0.9;">
                    <span class="material-symbols-rounded" style="font-size:14px;">schedule</span>
                    <?= date('h:i', strtotime($next_class['start_time'])) ?> - <?= date('h:i A', strtotime($next_class['end_time'])) ?>
                </div>
                
                <div class="hero-room"><?= htmlspecialchars($next_class['room']) ?></div>
                <div class="text-right text-xs font-bold" style="opacity:0.8; letter-spacing:1px; text-transform:uppercase;">Campus</div>
            </div>
            <?php endif; ?>
            
            <?php if (empty($schedules)): ?>
                <div class="empty-state">
                    <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px;">calendar_today</span>
                    <h3>ไม่มีคลาสเรียน</h3>
                    <p>คุณไม่มีคลาสเรียนที่ต้องเข้าเรียน<?= $view === 'today' ? 'ในวันนี้' : 'ในสัปดาห์นี้' ?></p>
                </div>
            <?php else: ?>
                
                <?php foreach ($schedules as $sch): ?>
                <?php
                    $is_ongoing = false;
                    $is_past = false;
                    if ($view === 'today') {
                        if ($current_time >= $sch['start_time'] && $current_time <= $sch['end_time']) {
                            $is_ongoing = true;
                        } elseif ($current_time > $sch['end_time']) {
                            $is_past = true;
                        }
                    }
                    
                    $border_class = $is_ongoing ? 'border-blue' : ($is_past ? 'border-gray' : '');
                    $time_color_class = $is_ongoing ? 'text-blue' : '';
                    $room_color_class = $is_ongoing ? 'text-blue' : ($is_past ? 'text-gray' : '');
                ?>
                <div class="timeline-card <?= $border_class ?>">
                    <div class="time-header">
                        <?php if ($view === 'weekly'): ?>
                        <div class="text-xs font-bold" style="color:var(--primary); margin-bottom: 4px;"><?= htmlspecialchars($sch['day_of_week']) ?></div>
                        <?php endif; ?>
                        <div class="time-start <?= $time_color_class ?>"><?= date('H:i', strtotime($sch['start_time'])) ?></div>
                        <div class="time-end"><?= date('H:i', strtotime($sch['end_time'])) ?></div>
                    </div>
                    
                    <div class="subject-row">
                        <div class="subject-title"><?= htmlspecialchars($sch['subject_name']) ?> (<?= htmlspecialchars($sch['subject_code']) ?>)</div>
                        <?php if ($is_ongoing): ?>
                            <div class="status-pill pill-ongoing">Ongoing</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="instructor-row">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($sch['teacher_fname'] . ' ' . $sch['teacher_lname']) ?>&background=random" class="avatar avatar-sm" alt="Inst">
                        <div>
                            <div class="text-xs font-bold" style="color:#0F172A;"><?= htmlspecialchars($sch['teacher_fname'] . ' ' . $sch['teacher_lname']) ?></div>
                            <div style="font-size:10px; color:var(--text-muted);">Instructor</div>
                        </div>
                    </div>
                    
                    <div class="room-block">
                        <div class="room-name <?= $room_color_class ?>"><?= htmlspecialchars($sch['room']) ?></div>
                        <div class="building-name">Campus</div>
                    </div>
                </div>
                <?php endforeach; ?>
                
            <?php endif; ?>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
