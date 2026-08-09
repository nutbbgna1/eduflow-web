<?php
require_once __DIR__ . '/../config/db.php';

// Helper functions for avatars
function getAvatarColor($name) {
    $colors = ['#2563EB','#7C3AED','#DB2777','#059669','#D97706','#0891B2','#DC2626','#4F46E5'];
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

$thai_days = ['Sunday' => 'อาทิตย์', 'Monday' => 'จันทร์', 'Tuesday' => 'อังคาร', 'Wednesday' => 'พุธ', 'Thursday' => 'พฤหัสบดี', 'Friday' => 'ศุกร์', 'Saturday' => 'เสาร์'];
$thai_months = ['01'=>'ม.ค.', '02'=>'ก.พ.', '03'=>'มี.ค.', '04'=>'เม.ย.', '05'=>'พ.ค.', '06'=>'มิ.ย.', '07'=>'ก.ค.', '08'=>'ส.ค.', '09'=>'ก.ย.', '10'=>'ต.ค.', '11'=>'พ.ย.', '12'=>'ธ.ค.'];
$display_date_thai = $thai_days[date('l')] . ', ' . date('j') . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543);

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

$teacher_initials = getInitials($teacher['first_name'] ?? 'T', $teacher['last_name'] ?? '');
$teacher_color = getAvatarColor($teacher['first_name'] ?? 'T');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - แดชบอร์ดครู</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .w-full { width: 100%; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .text-sm { font-size: 0.875rem; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1rem; }
        .p-4 { padding: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        
        .avatar-circle {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700; color: #fff;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="app-container" style="background-color: #F8FAFC; min-height: 100vh; padding-bottom: 80px;">
        
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-4 animate-slide-up">
            
            <!-- Premium Hero Section -->
            <div class="hero-gradient rounded-2xl p-4 shadow-md mb-6 relative overflow-hidden" style="margin-top: 10px;">
                <div class="absolute" style="top: -20px; right: -20px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%); border-radius: 50%;"></div>
                
                <div class="flex items-center gap-4 relative z-10">
                    <div class="avatar-circle" style="background-color: <?= $teacher_color ?>;">
                        <?= $teacher_initials ?>
                    </div>
                    <div>
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 2px;">สวัสดีครับคุณครู</div>
                        <h2 class="font-bold text-2xl" style="margin: 0; line-height: 1.2;"><?= htmlspecialchars($teacher['first_name']) ?></h2>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-4 p-3 rounded-xl" style="background: rgba(0,0,0,0.15); backdrop-filter: blur(4px);">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded" style="font-size: 18px;">calendar_today</span>
                        <span class="text-sm font-semibold"><?= $display_date_thai ?></span>
                    </div>
                    <div class="text-sm">
                        สอนวันนี้ <span class="font-bold" style="background: white; color: var(--primary); padding: 2px 8px; border-radius: 12px; margin-left: 4px;"><?= count($schedules) ?></span> คาบ
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <h3 class="font-bold text-xl mb-4 gradient-text" style="display:inline-block;">เมนูด่วน</h3>
            <div class="grid grid-cols-4 gap-3 mb-6">
                <a href="materials.php" class="flex flex-col items-center hover-lift" style="text-decoration: none;">
                    <div style="width: 54px; height: 54px; background: #EEF2FF; color: #4F46E5; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.1);">
                        <span class="material-symbols-rounded" style="font-size: 26px;">menu_book</span>
                    </div>
                    <span style="font-size: 12px; color: var(--text-main); font-weight: 600;">สื่อสอน</span>
                </a>
                <a href="assignments.php" class="flex flex-col items-center hover-lift" style="text-decoration: none;">
                    <div style="width: 54px; height: 54px; background: #ECFDF5; color: #10B981; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);">
                        <span class="material-symbols-rounded" style="font-size: 26px;">task</span>
                    </div>
                    <span style="font-size: 12px; color: var(--text-main); font-weight: 600;">การบ้าน</span>
                </a>
                <a href="roster.php" class="flex flex-col items-center hover-lift" style="text-decoration: none;">
                    <div style="width: 54px; height: 54px; background: #FAF5FF; color: #9333EA; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; box-shadow: 0 4px 6px rgba(147, 51, 234, 0.1);">
                        <span class="material-symbols-rounded" style="font-size: 26px;">groups</span>
                    </div>
                    <span style="font-size: 12px; color: var(--text-main); font-weight: 600;">รายชื่อ</span>
                </a>
                <a href="announcements.php" class="flex flex-col items-center hover-lift" style="text-decoration: none;">
                    <div style="width: 54px; height: 54px; background: #FFF7ED; color: #EA580C; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; box-shadow: 0 4px 6px rgba(234, 88, 12, 0.1);">
                        <span class="material-symbols-rounded" style="font-size: 26px;">campaign</span>
                    </div>
                    <span style="font-size: 12px; color: var(--text-main); font-weight: 600;">ประกาศ</span>
                </a>
            </div>

            <!-- Schedule Timeline -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-xl gradient-text" style="margin:0;">ตารางสอนวันนี้</h3>
                <?php if(empty($schedules)): ?>
                    <span class="badge badge-gray">ว่าง</span>
                <?php endif; ?>
            </div>

            <div class="timeline" id="timeline-container">
                <?php if(empty($schedules)): ?>
                    <div class="glass-card rounded-xl p-4 text-center" style="border: 2px dashed #CBD5E1;">
                        <span class="material-symbols-rounded" style="font-size: 48px; color: #94A3B8; margin-bottom: 8px;">free_cancellation</span>
                        <h4 style="color: #64748B; font-weight: 600;">ไม่มีคาบสอนสำหรับวันนี้</h4>
                        <p style="font-size: 13px; color: #94A3B8;">พักผ่อนหรือเตรียมการสอนสำหรับวันพรุ่งนี้ได้เลยครับ</p>
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
                            $badge_html = '';
                            $btn_html = '';
                            $dot_color = '#E2E8F0';
                            $dot_border = '#CBD5E1';

                            if ($is_completed) {
                                $state = 'completed';
                                $badge_html = '<span class="badge badge-success"><span class="material-symbols-rounded" style="font-size: 12px; margin-right: 4px;">check_circle</span> สอนเสร็จสิ้น</span>';
                                $btn_html = '<button class="btn btn-outline w-full" disabled style="opacity:0.7;">สอนแล้ว</button>';
                                $dot_color = 'var(--success)';
                                $dot_border = 'var(--success-bg)';
                            } else if ($curr_ts >= $start_minus_30 && $curr_ts <= $end_plus_30) {
                                $state = 'active';
                                $badge_html = '<span class="badge badge-primary animate-pulse-light"><span class="material-symbols-rounded" style="font-size: 12px; margin-right: 4px;">play_circle</span> ถึงเวลาสอน</span>';
                                $btn_html = '<a href="attendance.php?schedule_id=' . $schedule['id'] . '" class="btn btn-primary w-full shadow-md">เริ่มสอน / เช็คชื่อ</a>';
                                $dot_color = 'var(--primary)';
                                $dot_border = 'var(--primary-light)';
                            } else if ($curr_ts > $end_plus_30) {
                                $state = 'missed';
                                $badge_html = '<span class="badge badge-danger">เลยเวลาเช็คชื่อ</span>';
                                $btn_html = '<button class="btn btn-outline w-full" style="color: var(--danger); border-color: var(--danger-bg);" disabled>เลยกำหนด</button>';
                                $dot_color = 'var(--danger)';
                                $dot_border = 'var(--danger-bg)';
                            } else {
                                $state = 'waiting';
                                $check_time = date('H:i', $start_minus_30);
                                $badge_html = '<span class="badge badge-gray">รอถึงเวลา</span>';
                                $btn_html = '<button class="btn w-full" style="background-color: var(--surface); color: var(--secondary); border: 1px solid var(--border);" disabled>เช็คได้เวลา ' . $check_time . '</button>';
                            }
                        ?>
                        <div class="timeline-item <?php echo $state === 'active' ? 'active' : ''; ?>">
                            <div class="timeline-dot <?php echo $state === 'active' ? 'pulse-glow' : ''; ?>" style="background-color: <?= $dot_color ?>; border-color: <?= $dot_border ?>;"></div>
                            <div class="timeline-card glass-card hover-lift" style="<?php echo $state === 'active' ? 'border: 2px solid var(--primary); background: #F8FAFC;' : ''; ?> <?php echo $state === 'completed' ? 'opacity: 0.8;' : ''; ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2 text-sm font-semibold" style="color: <?= $state === 'active' ? 'var(--primary)' : 'var(--secondary)' ?>;">
                                        <span class="material-symbols-rounded" style="font-size: 18px;">schedule</span> <?= date('H:i', $start_ts) ?> - <?= date('H:i', $end_ts) ?>
                                    </div>
                                    <?= $badge_html ?>
                                </div>
                                <div style="margin: 12px 0;">
                                    <div class="font-bold text-xl" style="color: var(--text-main); margin-bottom: 4px;"><?= htmlspecialchars($schedule['subject_code']) ?></div>
                                    <div class="text-sm font-semibold" style="color: var(--text-muted);"><?= htmlspecialchars($schedule['subject_name']) ?></div>
                                    <div class="flex items-center gap-1 text-sm mt-2" style="color: var(--text-muted);">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">meeting_room</span> ห้อง <?= htmlspecialchars($schedule['room'] ?? '-') ?>
                                    </div>
                                </div>
                                <?= $btn_html ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>
