<?php
require_once __DIR__ . '/../config/db.php';

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

// Fetch schedules for today (current month, published only)
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

// Fetch teaching logs for today to check completion status
$stmt = $pdo->prepare("SELECT schedule_id FROM teaching_logs WHERE actual_teacher_id = :tid AND log_date = :ldate");
$stmt->execute(['tid' => $current_user_id, 'ldate' => $current_date]);
$completed_logs = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ตารางสอนวันนี้</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
        
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-4 animate-slide-up">
            
            <div class="profile-section">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&q=80" alt="Profile" class="profile-avatar-large">
                <h2 class="font-bold text-xl mb-1"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></h2>
                
                <div class="w-full flex flex-col gap-2 mt-6 p-4" style="background-color: var(--surface); border-radius: var(--border-radius-md); box-shadow: var(--shadow-sm);">
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary">วันที่</span>
                        <span class="text-sm font-semibold"><?= $display_date_thai ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary">คาบสอนทั้งหมด</span>
                        <span class="text-sm font-semibold"><?= count($schedules) ?> คาบ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-secondary">สถานะ</span>
                        <span class="text-sm font-semibold text-success flex items-center gap-1"><span class="material-symbols-rounded" style="font-size: 16px;">check_circle</span> สอนอยู่</span>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 24px;">
                <a href="materials.php" style="text-align: center; text-decoration: none;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 8px; background: #E0E7FF; color: var(--primary); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded">menu_book</span>
                    </div>
                    <span style="font-size: 11px; color: var(--text-main); font-weight: 600;">สื่อสอน</span>
                </a>
                <a href="assignments.php" style="text-align: center; text-decoration: none;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 8px; background: #DCFCE7; color: #16A34A; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded">task</span>
                    </div>
                    <span style="font-size: 11px; color: var(--text-main); font-weight: 600;">การบ้าน</span>
                </a>
                <a href="roster.php" style="text-align: center; text-decoration: none;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 8px; background: #F3E8FF; color: #9333EA; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded">groups</span>
                    </div>
                    <span style="font-size: 11px; color: var(--text-main); font-weight: 600;">รายชื่อ</span>
                </a>
                <a href="announcements.php" style="text-align: center; text-decoration: none;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 8px; background: #FFEDD5; color: #EA580C; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded">campaign</span>
                    </div>
                    <span style="font-size: 11px; color: var(--text-main); font-weight: 600;">ประกาศ</span>
                </a>
            </div>

            <h3 class="font-bold text-xl mb-6">ตารางสอนวันนี้</h3>

            <div class="timeline" id="timeline-container">
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
                        $dot_color = '#E2E8F0'; // Default gray
                        $dot_border = '#CBD5E1';

                        if ($is_completed) {
                            $state = 'completed';
                            $badge_html = '<span class="badge badge-success"><span class="material-symbols-rounded" style="font-size: 12px; margin-right: 4px;">check_circle</span> สอนเสร็จสิ้น</span>';
                            $btn_html = '<button class="btn btn-outline w-full" disabled>สอนแล้ว</button>';
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
                        <div class="timeline-dot" style="background-color: <?= $dot_color ?>; border-color: <?= $dot_border ?>;"></div>
                        <div class="timeline-card" style="<?php echo $state === 'active' ? 'border: 2px solid var(--primary);' : ''; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2 text-sm text-secondary font-medium">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">schedule</span> <?= date('H:i', $start_ts) ?> - <?= date('H:i', $end_ts) ?>
                                </div>
                                <?= $badge_html ?>
                            </div>
                            <h4 class="font-bold text-lg mb-1"><?= htmlspecialchars($schedule['subject_name']) ?></h4>
                            <div class="flex items-center gap-3 text-sm text-secondary mb-4">
                                <span class="flex items-center gap-1"><span class="material-symbols-rounded" style="font-size: 16px;">location_on</span> <?= htmlspecialchars($schedule['room']) ?></span>
                            </div>
                            <?= $btn_html ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($schedules)): ?>
                    <div class="text-center p-4 text-secondary text-sm mb-4">ไม่มีคาบสอนวันนี้</div>
                <?php endif; ?>
            </div>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
