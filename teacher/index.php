<?php
require_once __DIR__ . '/../config/db.php';

// Helper functions
function getAvatarColor($name) {
    $colors = ['#2563EB','#3B82F6','#EC4899','#10B981','#F59E0B','#06B6D4','#EF4444','#4F46E5'];
    if (empty($name)) return $colors[0];
    $idx = ctype_alpha($name[0]) ? ord(strtoupper($name[0])) % count($colors) : 0;
    return $colors[$idx];
}
function getInitials($first, $last) {
    $f = !empty($first) ? mb_substr($first, 0, 1) : '';
    $l = !empty($last) ? mb_substr($last, 0, 1) : '';
    return strtoupper($f . $l);
}

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

// Greeting based on time
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

// Fetch teaching logs for today
$stmt = $pdo->prepare("SELECT schedule_id FROM teaching_logs WHERE actual_teacher_id = :tid AND log_date = :ldate");
$stmt->execute(['tid' => $current_user_id, 'ldate' => $current_date]);
$completed_logs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Count stats
$total_classes = count($schedules);
$completed_classes = 0;
foreach ($schedules as $s) {
    if (in_array($s['id'], $completed_logs)) $completed_classes++;
}

// Total subjects taught by this teacher
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM schedules WHERE teacher_id = :tid AND schedule_month = :month");
$stmt->execute(['tid' => $current_user_id, 'month' => $current_month]);
$total_subjects = (int)$stmt->fetchColumn();

// Total students in teacher's subjects
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.student_id) 
    FROM enrollments e 
    JOIN schedules sc ON sc.subject_id = e.subject_id 
    WHERE sc.teacher_id = :tid AND sc.schedule_month = :month
");
$stmt->execute(['tid' => $current_user_id, 'month' => $current_month]);
$total_students = (int)$stmt->fetchColumn();

$teacher_initials = getInitials($teacher['first_name'] ?? 'T', $teacher['last_name'] ?? '');
$teacher_color = getAvatarColor($teacher['first_name'] ?? 'T');
$teacher_name = htmlspecialchars(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow — แดชบอร์ดครู</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --primary: #2563EB;
            --primary-dark: #1D4ED8;
            --primary-light: #EFF6FF;
            --surface: #FFFFFF;
            --bg: #F1F5F9;
            --text: #0F172A;
            --text-secondary: #64748B;
            --text-muted: #94A3B8;
            --border: #E2E8F0;
            --success: #10B981;
            --success-bg: #D1FAE5;
            --warning: #F59E0B;
            --warning-bg: #FEF3C7;
            --danger: #EF4444;
            --danger-bg: #FEE2E2;
            --radius: 16px;
            --radius-sm: 12px;
            --radius-xs: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Inter', 'Noto Sans Thai', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        .app-shell {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
            padding-bottom: 90px;
        }

        /* ── Top Bar ── */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px 8px;
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--bg);
        }
        .top-bar-left { display: flex; align-items: center; gap: 12px; }
        .top-bar-avatar {
            width: 42px; height: 42px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px; color: #fff;
            box-shadow: var(--shadow-md);
        }
        .top-bar-info { line-height: 1.3; }
        .top-bar-greeting { font-size: 12px; color: var(--text-secondary); font-weight: 500; }
        .top-bar-name { font-size: 16px; font-weight: 700; }
        .top-bar-actions { display: flex; gap: 8px; }
        .icon-btn {
            width: 42px; height: 42px; border-radius: 14px;
            border: 1px solid var(--border); background: var(--surface);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary); cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .icon-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }

        /* ── Hero Card ── */
        .hero-card {
            margin: 12px 20px 20px;
            background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 50%, #3B82F6 100%);
            border-radius: 24px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.3);
        }
        .hero-card::before {
            content: '';
            position: absolute;
            top: -40%; right: -20%;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-card::after {
            content: '';
            position: absolute;
            bottom: -30%; left: -10%;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-date {
            position: relative; z-index: 1;
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; opacity: 0.9; margin-bottom: 16px;
        }
        .hero-stats {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
        }
        .hero-stat {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-sm);
            padding: 14px 12px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .hero-stat-num {
            font-size: 28px; font-weight: 800; line-height: 1;
            margin-bottom: 4px;
        }
        .hero-stat-label {
            font-size: 11px; opacity: 0.85; font-weight: 500;
        }

        /* ── Section Title ── */
        .section-title {
            padding: 0 20px;
            font-size: 17px; font-weight: 700;
            margin-bottom: 12px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-title a {
            font-size: 13px; font-weight: 600; color: var(--primary);
            text-decoration: none;
        }

        /* ── Quick Actions Grid ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 0 20px;
            margin-bottom: 28px;
        }
        .quick-item {
            display: flex; flex-direction: column;
            align-items: center; gap: 8px;
            text-decoration: none;
            padding: 14px 4px;
            border-radius: var(--radius);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .quick-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }
        .quick-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .quick-label {
            font-size: 11px; font-weight: 600; color: var(--text);
            text-align: center; line-height: 1.3;
        }

        /* ── Schedule Cards ── */
        .schedule-list { padding: 0 20px; }

        .schedule-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }
        .schedule-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        .schedule-card.is-active {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary), var(--shadow-md);
        }
        .schedule-card.is-active::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1D4ED8, #2563EB, #3B82F6);
        }
        .schedule-card.is-completed { opacity: 0.65; }
        .schedule-card.is-missed { border-left: 4px solid var(--danger); }

        .sched-top {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 10px;
        }
        .sched-time {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--text-secondary);
        }
        .sched-time .material-symbols-rounded { font-size: 18px; }

        .sched-badge {
            font-size: 11px; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .sched-badge .material-symbols-rounded { font-size: 14px; }
        .badge-active { background: #DBEAFE; color: var(--primary); animation: softPulse 2s infinite; }
        .badge-done { background: var(--success-bg); color: var(--success); }
        .badge-missed { background: var(--danger-bg); color: var(--danger); }
        .badge-waiting { background: #F1F5F9; color: var(--text-secondary); }

        .sched-body { margin-bottom: 12px; }
        .sched-subject {
            font-size: 16px; font-weight: 700; color: var(--text);
            margin-bottom: 2px;
        }
        .sched-name {
            font-size: 13px; color: var(--text-secondary); font-weight: 500;
        }
        .sched-meta {
            display: flex; align-items: center; gap: 12px;
            margin-top: 8px; font-size: 12px; color: var(--text-muted);
        }
        .sched-meta-item {
            display: flex; align-items: center; gap: 4px;
        }
        .sched-meta-item .material-symbols-rounded { font-size: 16px; }

        .sched-btn {
            width: 100%; padding: 10px;
            border-radius: var(--radius-xs); border: none;
            font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            text-decoration: none;
        }
        .sched-btn-primary {
            background: var(--primary); color: white;
            box-shadow: 0 2px 8px rgba(37,99,235,0.3);
        }
        .sched-btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .sched-btn-disabled {
            background: #F1F5F9; color: var(--text-muted);
            cursor: not-allowed;
        }
        .sched-btn-done {
            background: var(--success-bg); color: var(--success);
            cursor: default;
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center; padding: 48px 24px;
            margin: 0 20px;
            background: var(--surface);
            border-radius: var(--radius);
            border: 2px dashed var(--border);
        }
        .empty-state .material-symbols-rounded {
            font-size: 56px; color: var(--text-muted); margin-bottom: 12px;
        }
        .empty-state h4 { font-size: 16px; color: var(--text-secondary); margin-bottom: 6px; }
        .empty-state p { font-size: 13px; color: var(--text-muted); }

        /* ── Bottom Nav ── */
        .bottom-nav {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 480px;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(226,232,240,0.6);
            display: flex; justify-content: space-around;
            padding: 8px 0 max(env(safe-area-inset-bottom), 12px);
            z-index: 100;
        }
        .nav-item {
            display: flex; flex-direction: column;
            align-items: center; gap: 2px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10px; font-weight: 600;
            padding: 6px 16px;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .nav-item .material-symbols-rounded { font-size: 24px; }
        .nav-item.active {
            color: var(--primary);
            background: var(--primary-light);
        }
        .nav-item:hover:not(.active) { color: var(--primary); }

        /* ── Animations ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes softPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .anim-1 { animation: fadeInUp 0.4s ease both; }
        .anim-2 { animation: fadeInUp 0.4s ease 0.08s both; }
        .anim-3 { animation: fadeInUp 0.4s ease 0.16s both; }
        .anim-4 { animation: fadeInUp 0.4s ease 0.24s both; }
    </style>
</head>
<body>
    <div class="app-shell">

        <!-- Top Bar -->
        <div class="top-bar anim-1">
            <div class="top-bar-left">
                <div class="top-bar-avatar" style="background: <?= $teacher_color ?>;">
                    <?= $teacher_initials ?>
                </div>
                <div class="top-bar-info">
                    <div class="top-bar-greeting"><?= $greeting ?> 👋</div>
                    <div class="top-bar-name"><?= $teacher_name ?></div>
                </div>
            </div>
            <div class="top-bar-actions">
                <a href="schedule.php" class="icon-btn" title="ตารางสอน">
                    <span class="material-symbols-rounded" style="color:#2563EB;">calendar_month</span>
                </a>
                <a href="../logout.php" class="icon-btn" title="ออกจากระบบ" style="color: var(--danger);">
                    <span class="material-symbols-rounded">logout</span>
                </a>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="hero-card anim-2">
            <div class="hero-date">
                <span class="material-symbols-rounded" style="font-size: 18px;">calendar_today</span>
                <?= $display_day ?> · <?= $display_date ?>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-num"><?= $total_classes ?></div>
                    <div class="hero-stat-label">คาบวันนี้</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-num"><?= $total_subjects ?></div>
                    <div class="hero-stat-label">วิชาที่สอน</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-num"><?= $total_students ?></div>
                    <div class="hero-stat-label">นักเรียน</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-title anim-3">เมนูหลัก</div>
        <div class="quick-grid anim-3">
            <a href="schedule.php" class="quick-item">
                <div class="quick-icon" style="background: #DBEAFE; color: #2563EB;">
                    <span class="material-symbols-rounded">calendar_month</span>
                </div>
                <span class="quick-label">ตารางสอน</span>
            </a>
            <a href="roster.php" class="quick-item">
                <div class="quick-icon" style="background: #ECFDF5; color: #10B981;">
                    <span class="material-symbols-rounded">groups</span>
                </div>
                <span class="quick-label">รายชื่อ</span>
            </a>
            <a href="leave.php" class="quick-item">
                <div class="quick-icon" style="background: #FFF7ED; color: #F59E0B;">
                    <span class="material-symbols-rounded">event_busy</span>
                </div>
                <span class="quick-label">ลางาน</span>
            </a>
            <a href="earnings.php" class="quick-item">
                <div class="quick-icon" style="background: #FDF2F8; color: #EC4899;">
                    <span class="material-symbols-rounded">payments</span>
                </div>
                <span class="quick-label">รายได้</span>
            </a>
            <a href="materials.php" class="quick-item">
                <div class="quick-icon" style="background: #F0FDFA; color: #14B8A6;">
                    <span class="material-symbols-rounded">menu_book</span>
                </div>
                <span class="quick-label">สื่อสอน</span>
            </a>
            <a href="assignments.php" class="quick-item">
                <div class="quick-icon" style="background: #FEF2F2; color: #EF4444;">
                    <span class="material-symbols-rounded">assignment</span>
                </div>
                <span class="quick-label">การบ้าน</span>
            </a>
            <a href="grading.php" class="quick-item">
                <div class="quick-icon" style="background: #FFFBEB; color: #D97706;">
                    <span class="material-symbols-rounded">grading</span>
                </div>
                <span class="quick-label">ให้คะแนน</span>
            </a>
            <a href="profile.php" class="quick-item">
                <div class="quick-icon" style="background: #DBEAFE; color: #2563EB;">
                    <span class="material-symbols-rounded">person</span>
                </div>
                <span class="quick-label">โปรไฟล์</span>
            </a>
        </div>

        <!-- Today's Schedule -->
        <div class="section-title anim-4">
            ตารางสอนวันนี้
            <?php if(!empty($schedules)): ?>
                <a href="schedule.php">ดูทั้งหมด →</a>
            <?php endif; ?>
        </div>

        <div class="schedule-list anim-4">
            <?php if(empty($schedules)): ?>
                <div class="empty-state">
                    <span class="material-symbols-rounded">weekend</span>
                    <h4>ไม่มีคาบสอนวันนี้</h4>
                    <p>พักผ่อน หรือเตรียมการสอนสำหรับวันถัดไปได้เลยครับ</p>
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
                        if ($is_completed) {
                            $state = 'completed';
                        } elseif ($curr_ts >= $start_minus_30 && $curr_ts <= $end_plus_30) {
                            $state = 'active';
                        } elseif ($curr_ts > $end_plus_30) {
                            $state = 'missed';
                        }
                    ?>
                    <div class="schedule-card <?= $state === 'active' ? 'is-active' : '' ?> <?= $state === 'completed' ? 'is-completed' : '' ?> <?= $state === 'missed' ? 'is-missed' : '' ?>">
                        <div class="sched-top">
                            <div class="sched-time">
                                <span class="material-symbols-rounded">schedule</span>
                                <?= date('H:i', $start_ts) ?> — <?= date('H:i', $end_ts) ?>
                            </div>
                            <?php if ($state === 'active'): ?>
                                <span class="sched-badge badge-active">
                                    <span class="material-symbols-rounded">radio_button_checked</span> กำลังสอน
                                </span>
                            <?php elseif ($state === 'completed'): ?>
                                <span class="sched-badge badge-done">
                                    <span class="material-symbols-rounded">check_circle</span> เสร็จสิ้น
                                </span>
                            <?php elseif ($state === 'missed'): ?>
                                <span class="sched-badge badge-missed">
                                    <span class="material-symbols-rounded">error</span> เลยเวลา
                                </span>
                            <?php else: ?>
                                <span class="sched-badge badge-waiting">
                                    <span class="material-symbols-rounded">hourglass_empty</span> รอ
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="sched-body">
                            <div class="sched-subject"><?= htmlspecialchars($schedule['subject_code']) ?></div>
                            <div class="sched-name"><?= htmlspecialchars($schedule['subject_name']) ?></div>
                            <div class="sched-meta">
                                <div class="sched-meta-item">
                                    <span class="material-symbols-rounded">meeting_room</span>
                                    ห้อง <?= htmlspecialchars($schedule['room'] ?? '-') ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($state === 'active'): ?>
                            <a href="attendance.php?schedule_id=<?= $schedule['id'] ?>" class="sched-btn sched-btn-primary">
                                <span class="material-symbols-rounded" style="font-size: 18px;">play_arrow</span>
                                เริ่มสอน · เช็คชื่อ
                            </a>
                        <?php elseif ($state === 'completed'): ?>
                            <div class="sched-btn sched-btn-done">
                                <span class="material-symbols-rounded" style="font-size: 18px;">check</span>
                                สอนเสร็จแล้ว
                            </div>
                        <?php elseif ($state === 'missed'): ?>
                            <div class="sched-btn sched-btn-disabled">เลยกำหนดเวลา</div>
                        <?php else: ?>
                            <div class="sched-btn sched-btn-disabled">เช็คได้เวลา <?= date('H:i', $start_minus_30) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item active">
                <span class="material-symbols-rounded">home</span>
                หน้าหลัก
            </a>
            <a href="schedule.php" class="nav-item">
                <span class="material-symbols-rounded">calendar_month</span>
                ตารางสอน
            </a>
            <a href="leave.php" class="nav-item">
                <span class="material-symbols-rounded">event_busy</span>
                ลางาน
            </a>
            <a href="earnings.php" class="nav-item">
                <span class="material-symbols-rounded">payments</span>
                รายได้
            </a>
            <a href="profile.php" class="nav-item">
                <span class="material-symbols-rounded">person</span>
                โปรไฟล์
            </a>
        </nav>

    </div>
</body>
</html>
