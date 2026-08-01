<?php
require_once '../config/db.php';
$current_user_id = $_SESSION['user_id'] ?? 1;
$subject_id = $_GET['subject_id'] ?? null;

// Fetch subjects taught by this teacher
$stmt = $pdo->prepare("SELECT DISTINCT sub.id, sub.code, sub.name FROM schedules s JOIN subjects sub ON s.subject_id = sub.id WHERE s.teacher_id = ?");
$stmt->execute([$current_user_id]);
$subjects = $stmt->fetchAll();

// If no subject selected but they have subjects, default to the first one
if (!$subject_id && count($subjects) > 0) {
    $subject_id = $subjects[0]['id'];
}

$students = [];
$current_subject = null;

if ($subject_id) {
    // Get current subject info
    foreach($subjects as $s) {
        if ($s['id'] == $subject_id) {
            $current_subject = $s;
            break;
        }
    }
    
    // Fetch enrolled students
    $stmt_students = $pdo->prepare("
        SELECT st.*, 
        (SELECT COUNT(*) FROM teaching_log_students tls JOIN teaching_logs tl ON tls.teaching_log_id = tl.id JOIN schedules s ON tl.schedule_id = s.id WHERE s.subject_id = ? AND tls.student_id = st.id) as attended_count,
        (SELECT COUNT(*) FROM teaching_logs tl JOIN schedules s ON tl.schedule_id = s.id WHERE s.subject_id = ?) as total_classes
        FROM enrollments e
        JOIN students st ON e.student_id = st.id
        WHERE e.subject_id = ?
        ORDER BY st.student_code ASC
    ");
    $stmt_students->execute([$subject_id, $subject_id, $subject_id]);
    $students = $stmt_students->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - รายชื่อนักเรียน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .subject-selector { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 12px; margin-bottom: 16px; scrollbar-width: none; }
        .subject-selector::-webkit-scrollbar { display: none; }
        .subject-tab { white-space: nowrap; padding: 8px 16px; border-radius: 20px; background: #F1F5F9; color: var(--text-muted); font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid transparent; }
        .subject-tab.active { background: #E0E7FF; color: var(--primary); border-color: var(--primary-light); }
        
        .student-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); margin-bottom: 12px; display: flex; align-items: center; gap: 12px; }
        .avatar-sm { width: 44px; height: 44px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569; font-size: 16px; }
        .stat-badge { background: #F8FAFC; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px; border: 1px solid var(--border); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-6 py-4">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <a href="index.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">รายชื่อนักเรียน</h2>
            </div>
            
            <?php if(count($subjects) > 0): ?>
                <div class="subject-selector">
                    <?php foreach($subjects as $sub): ?>
                        <a href="?subject_id=<?= $sub['id'] ?>" class="subject-tab <?= ($sub['id'] == $subject_id) ? 'active' : '' ?>">
                            <?= htmlspecialchars($sub['code']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if($current_subject): ?>
                <div style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: #0F172A;">
                    วิชา: <?= htmlspecialchars($current_subject['name']) ?> (จำนวนนักเรียน <?= count($students) ?> คน)
                </div>
            <?php endif; ?>

            <?php if(empty($students) && $current_subject): ?>
                <div class="text-center p-6 text-secondary" style="background:#fff; border-radius:16px;">ไม่มีนักเรียนลงทะเบียนในวิชานี้</div>
            <?php elseif (empty($subjects)): ?>
                <div class="text-center p-6 text-secondary" style="background:#fff; border-radius:16px;">คุณยังไม่มีวิชาที่สอน</div>
            <?php else: ?>
                <?php foreach($students as $s): ?>
                    <?php 
                        // Calculate attendance rate
                        $total = (int)$s['total_classes'];
                        $attended = (int)$s['attended_count'];
                        $rate = $total > 0 ? round(($attended / $total) * 100) : 100;
                        $rate_color = $rate >= 80 ? '#16A34A' : ($rate >= 50 ? '#EA580C' : '#DC2626');
                    ?>
                    <div class="student-card">
                        <div class="avatar-sm"><?= substr($s['first_name'], 0, 3) ?></div>
                        <div style="flex:1;">
                            <div class="font-bold text-sm text-main"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                            <div class="text-xs text-secondary mb-1">รหัส: <?= htmlspecialchars($s['student_code']) ?></div>
                            <div class="stat-badge">
                                <span class="material-symbols-rounded" style="font-size: 14px; color: <?= $rate_color ?>;">how_to_reg</span>
                                เข้าเรียน <?= $rate ?>% (<?= $attended ?>/<?= $total ?>)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
