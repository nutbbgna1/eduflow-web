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
            background: #F4F6F8;
            color: #111827;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        .shell { 
            max-width: 460px; margin: 0 auto; min-height: 100vh; 
             
            background: transparent;
        }

        /* ── Header ── */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            position: sticky; top: 0; z-index: 50;
        }
        .nav-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #2563EB; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        .nav-logo {
            font-size: 18px; font-weight: 800; color: #1D4ED8;
            display: flex; align-items: center; gap: 4px;
        }
        .nav-bell {
            position: relative; color: #4B5563;
        }
        .nav-bell::after {
            content: ''; position: absolute;
            top: 2px; right: 2px; width: 6px; height: 6px;
            background: #EF4444; border-radius: 50%;
            border: 1px solid #fff;
        }

        /* ── Page Header ── */
        .page-header {
            padding: 24px 20px 16px;
        }
        .page-header h1 {
            font-size: 26px; font-weight: 700; color: #111827;
            margin-bottom: 4px; letter-spacing: -0.5px;
        }
        .page-header p {
            font-size: 14px; color: #6B7280;
        }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin: 0 20px 16px;
            border: 1px solid #F3F4F6;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            position: relative;
        }
        
        /* ── Main Highlight Card (Like Revenue) ── */
        .main-card {
            background: #2563EB;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
            border: none;
        }
        .main-card::after {
            content: ''; position: absolute;
            top: 0; right: 0; width: 150px; height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%; pointer-events: none;
        }
        .mc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; position: relative; z-index: 2; }
        .mc-title { font-size: 11px; font-weight: 700; color: #93C5FD; letter-spacing: 0.5px; text-transform: uppercase; }
        .mc-icon {
            width: 32px; height: 32px; border-radius: 8px;
            color: #FFFFFF; border: 1px solid rgba(255,255,255,0.3);
            display: flex; align-items: center; justify-content: center;
        }
        .mc-value { font-size: 36px; font-weight: 800; color: #FFFFFF; line-height: 1.1; margin-bottom: 4px; position: relative; z-index: 2; }
        .mc-trend { font-size: 12px; font-weight: 600; color: #10B981; display: flex; align-items: center; gap: 4px; margin-bottom: 24px; position: relative; z-index: 2; }
        .mc-trend .material-symbols-rounded { font-size: 14px; }
        
        .mc-chart { display: flex; align-items: flex-end; gap: 4px; height: 40px; margin-top: 10px; }
        .chart-bar { flex: 1; background: #BFDBFE; border-radius: 2px 2px 0 0; }
        .chart-bar.active { background: #3B82F6; }

        .mc-class-list { margin-top: 16px; display: flex; flex-direction: column; gap: 8px; position: relative; z-index: 2; }
        .mc-class-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 12px; background: rgba(255,255,255,0.8); border-radius: 10px; 
            border: 1px solid #E5E7EB; border-left: 4px solid #3B82F6;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        }
        .mc-class-info h5 { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 2px; }
        .mc-class-info p { font-size: 11px; color: #6B7280; }
        .mc-class-time { font-size: 11px; font-weight: 700; color: #1D4ED8; background: #EFF6FF; padding: 4px 8px; border-radius: 6px; }

        /* ── Small Stats Grid ── */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 0 20px 20px; }
        .stat-card {
            background: #fff; border-radius: 16px; padding: 16px;
            border: 1px solid #F3F4F6; box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .sc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .sc-title { font-size: 11px; font-weight: 700; color: #6B7280; letter-spacing: 0.5px; text-transform: uppercase; }
        .sc-icon { color: #2563EB; }
        .sc-icon.orange { color: #F59E0B; }
        .sc-value { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .sc-sub { font-size: 11px; color: #6B7280; }

        /* ── Distribution Card (Main Menu) ── */
        .dist-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .dist-title { font-size: 16px; font-weight: 700; color: #111827; }
        .dist-link { font-size: 13px; font-weight: 600; color: #2563EB; }

        .menu-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 8px; }
        .menu-item {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 8px 4px;
        }
        .menu-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: #F3F4F6; color: #4B5563; transition: 0.2s;
        }
        .menu-item:hover .menu-icon { background: #EFF6FF; color: #2563EB; }
        .menu-label { font-size: 11px; font-weight: 600; color: #374151; text-align: center; }

        /* ── Schedule List (Like Pending Leave Requests) ── */
        .list-section-title {
            padding: 0 20px; margin: 24px 0 12px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .list-section-title h3 { font-size: 18px; font-weight: 700; color: #111827; }
        .list-badge {
            background: #FFEDD5; color: #EA580C;
            font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px;
        }

        .list-card {
            background: #fff; border-radius: 12px; padding: 16px;
            margin: 0 20px 12px;
            border-left: 4px solid #F59E0B; /* Orange accent */
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex; justify-content: space-between; align-items: center;
            text-decoration: none; color: inherit;
        }
        .list-card.active { border-left-color: #2563EB; }
        .list-card.done { border-left-color: #10B981; opacity: 0.7; }
        
        .lc-left { display: flex; align-items: center; gap: 12px; }
        .lc-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: #F3F4F6; color: #6B7280;
            display: flex; align-items: center; justify-content: center;
        }
        .lc-avatar .material-symbols-rounded { font-size: 20px; }
        .lc-info h4 { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 2px; }
        .lc-info p { font-size: 12px; color: #6B7280; }
        .lc-arrow {
            width: 32px; height: 32px; border-radius: 50%;
            background: #F3F4F6; color: #6B7280;
            display: flex; align-items: center; justify-content: center;
        }
        .lc-arrow .material-symbols-rounded { font-size: 18px; }

        /* ── Empty ── */
        .empty {
            text-align: center; padding: 30px 20px;
            margin: 0 20px; background: #fff;
            border-radius: 12px; border: 1px dashed #D1D5DB;
        }
        .empty h4 { font-size: 14px; color: #6B7280; font-weight: 600; }
    </style>
</head>
<body>
<div class="shell">

    <!-- Top Bar -->
    <div class="top-nav">
        <div class="nav-avatar"><?= $initials ?></div>
        <div class="nav-logo">
            EduFlow
        </div>
        <div class="nav-bell">
            <span class="material-symbols-rounded">notifications</span>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <h1>Overview</h1>
        <p>Your daily administrative summary.</p>
    </div>

    <!-- Main Highlight Card -->
    <div class="card main-card">
        <div class="mc-header">
            <div class="mc-title">TODAY'S CLASSES</div>
            <div class="mc-icon">
                <span class="material-symbols-rounded" style="font-size:20px;">school</span>
            </div>
        </div>
        <div class="mc-value"><?= $total_classes ?> <span style="font-size:14px; color:#BFDBFE; font-weight:600;">คาบ</span></div>
        
        <div class="mc-class-list">
            <?php if (empty($schedules)): ?>
                <div style="font-size:12px; color:#BFDBFE;">ไม่มีสอนในวันนี้</div>
            <?php else: ?>
                <?php foreach(array_slice($schedules, 0, 3) as $sch): ?>
                <div class="mc-class-item">
                    <div class="mc-class-info">
                        <h5><?= htmlspecialchars($sch['subject_code']) ?></h5>
                        <p>ห้อง <?= htmlspecialchars($sch['room'] ?? '-') ?></p>
                    </div>
                    <div class="mc-class-time">
                        <?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(count($schedules) > 3): ?>
                    <div style="font-size:11px; color:#2563EB; font-weight:600; text-align:center; padding-top:4px;">
                        + อีก <?= count($schedules) - 3 ?> คาบ (ดูด้านล่าง)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="sc-header">
                    <div class="sc-title">TOTAL<br>SUBJECTS</div>
                    <span class="material-symbols-rounded sc-icon orange" style="font-size:20px;">menu_book</span>
                </div>
                <div class="sc-value"><?= $total_subjects ?></div>
                <div class="sc-sub">Active terms</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="sc-header">
                    <div class="sc-title">ACTIVE<br>STUDENTS</div>
                    <span class="material-symbols-rounded sc-icon" style="font-size:20px;">school</span>
                </div>
                <div class="sc-value"><?= number_format($total_students) ?></div>
                <div class="sc-sub">+45 this term</div>
            </div>
        </div>
    </div>

    <!-- Menu Grid inside Card -->
    <div class="card">
        <div class="dist-header">
            <div class="dist-title">Quick Actions</div>
            <a href="profile.php" class="dist-link">View All</a>
        </div>
        <div class="menu-grid">
            <a href="schedule.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">calendar_month</span></div>
                <span class="menu-label">ตารางสอน</span>
            </a>
            <a href="roster.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">groups</span></div>
                <span class="menu-label">รายชื่อ</span>
            </a>
            <a href="leave.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">event_busy</span></div>
                <span class="menu-label">ลางาน</span>
            </a>
            <a href="earnings.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">payments</span></div>
                <span class="menu-label">รายได้</span>
            </a>
            <a href="materials.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">menu_book</span></div>
                <span class="menu-label">สื่อสอน</span>
            </a>
            <a href="assignments.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">assignment</span></div>
                <span class="menu-label">การบ้าน</span>
            </a>
            <a href="grading.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">grading</span></div>
                <span class="menu-label">ให้คะแนน</span>
            </a>
            <a href="profile.php" class="menu-item">
                <div class="menu-icon"><span class="material-symbols-rounded">person</span></div>
                <span class="menu-label">โปรไฟล์</span>
            </a>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="list-section-title">
        <h3>Today's Schedule</h3>
        <?php if($total_classes > 0): ?>
            <span class="list-badge"><?= $total_classes ?> Classes</span>
        <?php endif; ?>
    </div>

    <?php if(empty($schedules)): ?>
        <div class="empty">
            <h4>No classes scheduled for today.</h4>
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

                $state = 'wait'; // default yellow border
                if ($is_completed) $state = 'done'; // green border
                elseif ($curr_ts >= $start_minus_30 && $curr_ts <= $end_plus_30) $state = 'active'; // blue border
            ?>
            <a href="<?= ($state === 'active') ? 'attendance.php?schedule_id='.$schedule['id'] : '#' ?>" class="list-card <?= $state ?>">
                <div class="lc-left">
                    <div class="lc-avatar">
                        <span class="material-symbols-rounded">menu_book</span>
                    </div>
                    <div class="lc-info">
                        <h4><?= htmlspecialchars($schedule['subject_name']) ?></h4>
                        <p><?= htmlspecialchars($schedule['subject_code']) ?> • <?= date('H:i', $start_ts) ?> - <?= date('H:i', $end_ts) ?></p>
                    </div>
                </div>
                <div class="lc-arrow">
                    <span class="material-symbols-rounded">chevron_right</span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php include 'includes/bottom_nav.php'; ?>
</div>
</body>
</html>
