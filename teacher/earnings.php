<?php
require_once __DIR__ . '/../config/db.php';

// Mock settings
$rate_per_hour = 500;
$hour_per_class = 1;

// Fetch teaching logs for the current user
$stmt = $pdo->prepare("
    SELECT tl.*, s.start_time, s.end_time, sub.name as subject_name 
    FROM teaching_logs tl
    JOIN schedules s ON tl.schedule_id = s.id
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE tl.actual_teacher_id = :tid
    ORDER BY tl.log_date DESC, s.start_time DESC
");
$stmt->execute(['tid' => $current_user_id]);
$teaching_logs = $stmt->fetchAll();

$normal_hours = 0;
$sub_hours = 0;

foreach ($teaching_logs as $log) {
    if ($log['is_substitution']) {
        $sub_hours += $log['hours'];
    } else {
        $normal_hours += $log['hours'];
    }
}

$total_hours = $normal_hours + $sub_hours;
$total_earnings = $total_hours * $rate_per_hour;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ชั่วโมงของฉัน</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
        
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-4 animate-slide-up">
            
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="font-bold text-2xl mb-1">ชั่วโมงของฉัน</h2>
                    <p class="text-sm text-secondary">สรุปชั่วโมงสอนและรายได้ เดือนสิงหาคม 2566</p>
                </div>
            </div>

            <!-- Main Earnings Card -->
            <div class="card gradient-card-blue p-6 mb-6" style="border-radius: var(--border-radius-lg);">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-sm" style="color: rgba(255,255,255,0.8);">ประมาณการรายได้ (เดือนนี้)</span>
                    <span class="material-symbols-rounded" style="color: rgba(255,255,255,0.8);">payments</span>
                </div>
                <h3 class="font-bold mb-6" style="font-size: 2.5rem; line-height: 1.2;">฿<?= number_format($total_earnings, 2) ?></h3>
                
                <div class="flex justify-between items-end">
                    <div>
                        <div class="text-xs mb-1" style="color: rgba(255,255,255,0.8);">ชั่วโมงสอนทั้งหมด</div>
                        <div class="flex items-center gap-1 text-sm font-medium" style="color: #6EE7B7;">
                            <?= $total_hours ?> ชั่วโมง
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div class="card p-4" style="border-radius: var(--border-radius-lg); margin-bottom: 0;">
                    <div class="p-2 rounded inline-block mb-3" style="background-color: var(--primary-light); color: var(--primary);">
                        <span class="material-symbols-rounded" style="font-size: 20px;">menu_book</span>
                    </div>
                    <div class="text-xs text-secondary mb-1">สอนปกติ</div>
                    <div class="font-bold text-xl"><?= $normal_hours ?> ชม.</div>
                </div>
                <div class="card p-4" style="border-radius: var(--border-radius-lg); margin-bottom: 0;">
                    <div class="p-2 rounded inline-block mb-3" style="background-color: #FEF3C7; color: #D97706;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">group_add</span>
                    </div>
                    <div class="text-xs text-secondary mb-1">สอนแทน</div>
                    <div class="font-bold text-xl"><?= $sub_hours ?> ชม.</div>
                </div>
            </div>

            <!-- History Section -->
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-rounded text-primary">history</span>
                <h3 class="font-bold text-lg">ประวัติการสอน</h3>
            </div>

            <div class="card p-0 mb-6" style="border-radius: var(--border-radius-lg); overflow: hidden;">
                <?php foreach($teaching_logs as $log): ?>
                    <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border);">
                        <div>
                            <div class="text-sm font-medium">
                                <?= htmlspecialchars($log['subject_name']) ?> 
                                <?= $log['is_substitution'] ? '(สอนแทน)' : '' ?>
                            </div>
                            <div class="text-xs text-secondary">
                                <?= date('d M Y', strtotime($log['log_date'])) ?> • 
                                <?= date('H:i', strtotime($log['start_time'])) ?> - <?= date('H:i', strtotime($log['end_time'])) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-sm">+฿<?= number_format($log['hours'] * $rate_per_hour, 2) ?></div>
                            <div class="text-xs text-success">สอนเสร็จสิ้น</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($teaching_logs)): ?>
                    <div class="p-4 text-center text-sm text-secondary">ยังไม่มีข้อมูลการสอน</div>
                <?php endif; ?>
            </div>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
