<?php
require_once __DIR__ . '/../config/db.php';

// Fetch teacher details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $current_user_id]);
$teacher = $stmt->fetch();

$current_day = date('l');
$current_time = date('H:i:s');
$current_date = date('Y-m-d');
$current_hour = (int)date('G');

$thai_days = ['Sunday'=>'อาทิตย์','Monday'=>'จันทร์','Tuesday'=>'อังคาร','Wednesday'=>'พุธ','Thursday'=>'พฤหัสบดี','Friday'=>'ศุกร์','Saturday'=>'เสาร์'];
$thai_months = ['01'=>'มกราคม','02'=>'กุมภาพันธ์','03'=>'มีนาคม','04'=>'เมษายน','05'=>'พฤษภาคม','06'=>'มิถุนายน','07'=>'กรกฎาคม','08'=>'สิงหาคม','09'=>'กันยายน','10'=>'ตุลาคม','11'=>'พฤศจิกายน','12'=>'ธันวาคม'];
$display_date = date('j') . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543);
$display_day = 'วัน' . $thai_days[date('l')];

if ($current_hour < 12) $greeting = 'สวัสดีตอนเช้า';
elseif ($current_hour < 17) $greeting = 'สวัสดีตอนบ่าย';
else $greeting = 'สวัสดีตอนเย็น';

// Fetch schedules for today
$current_month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT s.*, sub.name as subject_name, sub.code as subject_code
    FROM schedules s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.teacher_id = :tid AND s.day_of_week = :day AND s.schedule_month = :month AND s.status = 'published'
    ORDER BY s.start_time
");
$stmt->execute(['tid' => $current_user_id, 'day' => $current_day, 'month' => $current_month]);
$schedules = $stmt->fetchAll();

// Fetch teaching logs
$stmt = $pdo->prepare("SELECT schedule_id FROM teaching_logs WHERE actual_teacher_id = :tid AND log_date = :ldate");
$stmt->execute(['tid' => $current_user_id, 'ldate' => $current_date]);
$completed_logs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Stats
$total_classes = count($schedules);
$completed_classes = 0;
foreach ($schedules as $s) {
    if (in_array($s['id'], $completed_logs)) $completed_classes++;
}
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM schedules WHERE teacher_id = :tid AND schedule_month = :month");
$stmt->execute(['tid' => $current_user_id, 'month' => $current_month]);
$total_subjects = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN schedules sc ON sc.subject_id = e.subject_id WHERE sc.teacher_id = :tid AND sc.schedule_month = :month");
$stmt->execute(['tid' => $current_user_id, 'month' => $current_month]);
$total_students = (int)$stmt->fetchColumn();

$teacher_name = htmlspecialchars(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
$initials = strtoupper(mb_substr($teacher['first_name'] ?? 'T', 0, 1) . mb_substr($teacher['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow — แดชบอร์ดครู</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Noto Sans Thai', -apple-system, sans-serif;
            background: #EEF0F8;
            color: #111827;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        .shell { max-width: 460px; margin: 0 auto; min-height: 100vh; background: #EEF0F8; padding-bottom: 80px; }

        /* ── Header ── */
        .header {
            padding: 20px 20px 12px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 14px; }
        .avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: #2563EB; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; font-weight: 700;
        }
        .header-text h2 { font-size: 16px; font-weight: 700; line-height: 1.3; }
        .header-text p { font-size: 12px; color: #6B7280; font-weight: 500; }
        .header-actions { display: flex; gap: 8px; }
        .header-btn {
            width: 40px; height: 40px; border-radius: 50%;
            border: 1px solid #E5E7EB; background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: #6B7280; cursor: pointer; transition: 0.15s;
        }
        .header-btn:hover { border-color: #2563EB; color: #2563EB; }
        .header-btn .material-symbols-rounded { font-size: 20px; }

        /* ── Date Bar ── */
        .date-bar {
            margin: 0 20px 16px;
            padding: 12px 16px;
            background: #fff; border-radius: 12px;
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #E5E7EB;
        }
        .date-bar-left { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #374151; }
        .date-bar-left .material-symbols-rounded { font-size: 18px; color: #2563EB; }
        .date-bar-right { font-size: 12px; color: #6B7280; font-weight: 500; }

        /* ── Stats ── */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 0 20px; margin-bottom: 20px; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 16px 14px;
            text-align: center; border: 1px solid #E5E7EB;
        }
        .stat-num { font-size: 24px; font-weight: 800; color: #111827; line-height: 1; }
        .stat-label { font-size: 11px; color: #6B7280; font-weight: 600; margin-top: 4px; }

        /* ── Menu Grid ── */
        .menu-title { padding: 0 20px; font-size: 15px; font-weight: 700; margin-bottom: 10px; color: #111827; }
        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; padding: 0 20px; margin-bottom: 24px; }
        .menu-item {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 14px 6px; border-radius: 12px;
            background: #fff; border: 1px solid #E5E7EB;
            transition: 0.15s;
        }
        .menu-item:hover { border-color: #2563EB; background: #EFF6FF; }
        .menu-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .menu-icon .material-symbols-rounded { font-size: 22px; }
        .menu-label { font-size: 11px; font-weight: 600; color: #374151; text-align: center; }

        /* ── Schedule Section ── */
        .section-head {
            padding: 0 20px; margin-bottom: 12px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-head h3 { font-size: 15px; font-weight: 700; }
        .section-head a { font-size: 13px; font-weight: 600; color: #2563EB; }

        .sched-list { padding: 0 20px; }

        .sched-card {
            background: #fff; border-radius: 12px; padding: 16px;
            margin-bottom: 10px; border: 1px solid #E5E7EB;
            position: relative;
        }
        .sched-card.now { border-color: #2563EB; border-width: 2px; }
        .sched-card.done { opacity: 0.55; }
        .sched-card.late { border-left: 4px solid #DC2626; }

        .sched-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .sched-time { font-size: 13px; font-weight: 600; color: #6B7280; display: flex; align-items: center; gap: 4px; }
        .sched-time .material-symbols-rounded { font-size: 16px; }

        .tag {
            font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 6px;
            display: inline-flex; align-items: center; gap: 3px;
        }
        .tag .material-symbols-rounded { font-size: 14px; }
        .tag-now { background: #DBEAFE; color: #1D4ED8; }
        .tag-done { background: #DCFCE7; color: #16A34A; }
        .tag-late { background: #FEE2E2; color: #DC2626; }
        .tag-wait { background: #F3F4F6; color: #6B7280; }

        .sched-subject { font-size: 15px; font-weight: 700; color: #111827; }
        .sched-name { font-size: 13px; color: #6B7280; margin-top: 2px; }
        .sched-room { font-size: 12px; color: #9CA3AF; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
        .sched-room .material-symbols-rounded { font-size: 15px; }

        .sched-btn {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 10px; margin-top: 12px;
            border-radius: 8px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .sched-btn .material-symbols-rounded { font-size: 18px; }
        .btn-blue { background: #2563EB; color: #fff; }
        .btn-blue:hover { background: #1D4ED8; }
        .btn-gray { background: #F3F4F6; color: #9CA3AF; cursor: default; }
        .btn-green { background: #DCFCE7; color: #16A34A; cursor: default; }

        /* ── Empty ── */
        .empty {
            text-align: center; padding: 40px 20px;
            margin: 0 20px; background: #fff;
            border-radius: 12px; border: 1px solid #E5E7EB;
        }
        .empty .material-symbols-rounded { font-size: 40px; color: #D1D5DB; margin-bottom: 8px; }
        .empty h4 { font-size: 14px; color: #6B7280; font-weight: 600; }
        .empty p { font-size: 12px; color: #9CA3AF; margin-top: 4px; }

        /* ── Bottom Nav ── */
        .bottom-nav {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 460px;
            background: #fff; border-top: 1px solid #E5E7EB;
            display: flex; justify-content: space-around;
            padding: 6px 0 max(env(safe-area-inset-bottom), 10px);
            z-index: 100;
        }
        .nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            font-size: 10px; font-weight: 600; color: #9CA3AF;
            padding: 6px 12px; border-radius: 10px; transition: 0.15s;
        }
        .nav-item .material-symbols-rounded { font-size: 22px; }
        .nav-item.on { color: #2563EB; }
    </style>
</head>
<body>
<div class="shell">

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="avatar"><?= $initials ?></div>
            <div class="header-text">
                <p><?= $greeting ?></p>
                <h2><?= $teacher_name ?></h2>
            </div>
        </div>
        <div class="header-actions">
            <a href="schedule.php" class="header-btn"><span class="material-symbols-rounded">calendar_month</span></a>
            <a href="../logout.php" class="header-btn" style="color:#DC2626;border-color:#FEE2E2;"><span class="material-symbols-rounded">logout</span></a>
        </div>
    </div>

    <!-- Date Bar -->
    <div class="date-bar">
        <div class="date-bar-left">
            <span class="material-symbols-rounded">today</span>
            <?= $display_day ?>, <?= $display_date ?>
        </div>
        <div class="date-bar-right"><?= $total_classes ?> คาบวันนี้</div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-num"><?= $total_classes ?></div>
            <div class="stat-label">คาบวันนี้</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $total_subjects ?></div>
            <div class="stat-label">วิชาที่สอน</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $total_students ?></div>
            <div class="stat-label">นักเรียน</div>
        </div>
    </div>

    <!-- Menu -->
    <div class="menu-title">เมนู</div>
    <div class="menu-grid">
        <a href="schedule.php" class="menu-item">
            <div class="menu-icon" style="background:#EFF6FF;color:#2563EB;"><span class="material-symbols-rounded">calendar_month</span></div>
            <span class="menu-label">ตารางสอน</span>
        </a>
        <a href="roster.php" class="menu-item">
            <div class="menu-icon" style="background:#ECFDF5;color:#10B981;"><span class="material-symbols-rounded">groups</span></div>
            <span class="menu-label">รายชื่อ</span>
        </a>
        <a href="leave.php" class="menu-item">
            <div class="menu-icon" style="background:#FEF3C7;color:#D97706;"><span class="material-symbols-rounded">event_busy</span></div>
            <span class="menu-label">ลางาน</span>
        </a>
        <a href="earnings.php" class="menu-item">
            <div class="menu-icon" style="background:#FEE2E2;color:#DC2626;"><span class="material-symbols-rounded">payments</span></div>
            <span class="menu-label">รายได้</span>
        </a>
        <a href="materials.php" class="menu-item">
            <div class="menu-icon" style="background:#F0FDFA;color:#0D9488;"><span class="material-symbols-rounded">menu_book</span></div>
            <span class="menu-label">สื่อสอน</span>
        </a>
        <a href="assignments.php" class="menu-item">
            <div class="menu-icon" style="background:#EFF6FF;color:#2563EB;"><span class="material-symbols-rounded">assignment</span></div>
            <span class="menu-label">การบ้าน</span>
        </a>
        <a href="grading.php" class="menu-item">
            <div class="menu-icon" style="background:#FEF3C7;color:#D97706;"><span class="material-symbols-rounded">grading</span></div>
            <span class="menu-label">ให้คะแนน</span>
        </a>
        <a href="profile.php" class="menu-item">
            <div class="menu-icon" style="background:#F3F4F6;color:#6B7280;"><span class="material-symbols-rounded">person</span></div>
            <span class="menu-label">โปรไฟล์</span>
        </a>
    </div>

    <!-- Schedule -->
    <div class="section-head">
        <h3>ตารางสอนวันนี้</h3>
        <?php if(!empty($schedules)): ?>
            <a href="schedule.php">ดูทั้งหมด</a>
        <?php endif; ?>
    </div>

    <div class="sched-list">
        <?php if(empty($schedules)): ?>
            <div class="empty">
                <span class="material-symbols-rounded">event_available</span>
                <h4>ไม่มีคาบสอนวันนี้</h4>
                <p>พักผ่อน หรือเตรียมการสอนสำหรับวันถัดไป</p>
            </div>
        <?php else: ?>
            <?php foreach($schedules as $schedule): ?>
                <?php
                    $is_completed = in_array($schedule['id'], $completed_logs);
                    $start_ts = strtotime($schedule['start_time']);
                    $end_ts = strtotime($schedule['end_time']);
                    $curr_ts = strtotime($current_time);
                    $start_minus_30 = $start_ts - (30 * 60);
                    $end_plus_30 = $end_ts + (30 * 60);

                    $state = 'waiting';
                    if ($is_completed) $state = 'completed';
                    elseif ($curr_ts >= $start_minus_30 && $curr_ts <= $end_plus_30) $state = 'active';
                    elseif ($curr_ts > $end_plus_30) $state = 'missed';
                ?>
                <div class="sched-card <?= $state === 'active' ? 'now' : '' ?> <?= $state === 'completed' ? 'done' : '' ?> <?= $state === 'missed' ? 'late' : '' ?>">
                    <div class="sched-row">
                        <div class="sched-time">
                            <span class="material-symbols-rounded">schedule</span>
                            <?= date('H:i', $start_ts) ?> – <?= date('H:i', $end_ts) ?>
                        </div>
                        <?php if ($state === 'active'): ?>
                            <span class="tag tag-now"><span class="material-symbols-rounded">radio_button_checked</span> กำลังสอน</span>
                        <?php elseif ($state === 'completed'): ?>
                            <span class="tag tag-done"><span class="material-symbols-rounded">check_circle</span> เสร็จสิ้น</span>
                        <?php elseif ($state === 'missed'): ?>
                            <span class="tag tag-late"><span class="material-symbols-rounded">error</span> เลยเวลา</span>
                        <?php else: ?>
                            <span class="tag tag-wait"><span class="material-symbols-rounded">hourglass_empty</span> รอ</span>
                        <?php endif; ?>
                    </div>
                    <div class="sched-subject"><?= htmlspecialchars($schedule['subject_code']) ?></div>
                    <div class="sched-name"><?= htmlspecialchars($schedule['subject_name']) ?></div>
                    <div class="sched-room">
                        <span class="material-symbols-rounded">meeting_room</span>
                        ห้อง <?= htmlspecialchars($schedule['room'] ?? '-') ?>
                    </div>
                    <?php if ($state === 'active'): ?>
                        <a href="attendance.php?schedule_id=<?= $schedule['id'] ?>" class="sched-btn btn-blue">
                            <span class="material-symbols-rounded">play_arrow</span> เริ่มสอน / เช็คชื่อ
                        </a>
                    <?php elseif ($state === 'completed'): ?>
                        <div class="sched-btn btn-green"><span class="material-symbols-rounded">check</span> สอนเสร็จแล้ว</div>
                    <?php elseif ($state === 'missed'): ?>
                        <div class="sched-btn btn-gray">เลยกำหนดเวลา</div>
                    <?php else: ?>
                        <div class="sched-btn btn-gray">เช็คได้เวลา <?= date('H:i', $start_minus_30) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bottom Nav -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item on"><span class="material-symbols-rounded">home</span> หน้าหลัก</a>
        <a href="schedule.php" class="nav-item"><span class="material-symbols-rounded">calendar_month</span> ตาราง</a>
        <a href="leave.php" class="nav-item"><span class="material-symbols-rounded">event_busy</span> ลางาน</a>
        <a href="earnings.php" class="nav-item"><span class="material-symbols-rounded">payments</span> รายได้</a>
        <a href="profile.php" class="nav-item"><span class="material-symbols-rounded">person</span> โปรไฟล์</a>
    </nav>

</div>
</body>
</html>
