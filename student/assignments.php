<?php
require_once '../config/db.php';
$student_id = $_SESSION['user_id'] ?? 2; // Default to student ID 2 for demo purposes
$page_title = 'การบ้านของฉัน';

try {
    // Fetch all assignments for enrolled subjects
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.max_score, sub.code as subject_code, sub.name as subject_name,
               g.status, g.score, g.graded_at
        FROM assignments a
        JOIN subjects sub ON a.subject_id = sub.id
        JOIN enrollments e ON sub.id = e.subject_id
        LEFT JOIN student_grades g ON a.id = g.assignment_id AND g.student_id = ?
        WHERE e.student_id = ?
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$student_id, $student_id]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    $assignments = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - <?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .assignment-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: var(--shadow-sm); margin-bottom: 12px; display: block; text-decoration: none; border-left: 4px solid var(--primary); }
        .assignment-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .assignment-code { font-size: 11px; font-weight: 700; background: #F1F5F9; color: var(--text-muted); padding: 4px 8px; border-radius: 8px; }
        .assignment-title { font-weight: bold; color: #0F172A; font-size: 15px; margin-bottom: 4px; }
        .assignment-due { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
        
        .status-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; }
        .status-pending { background: #FEE2E2; color: #DC2626; }
        .status-submitted { background: #FEF3C7; color: #D97706; }
        .status-graded { background: #DCFCE7; color: #16A34A; }
        
        .score-display { font-size: 13px; font-weight: 800; color: #16A34A; text-align: right; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        
        <div class="px-5 pb-24">
            <h2 class="font-bold text-xl mb-4">งานและการบ้าน</h2>
            
            <?php if(empty($assignments)): ?>
                <div class="text-center p-8 text-secondary" style="background:#fff; border-radius:16px;">
                    <span class="material-symbols-rounded" style="font-size:48px; opacity:0.5; margin-bottom:16px;">assignment</span>
                    <div>ยังไม่มีการบ้านหรือชิ้นงานที่ต้องทำในตอนนี้</div>
                </div>
            <?php else: ?>
                <?php foreach($assignments as $a): ?>
                    <?php 
                        $status = $a['status'] ?? 'pending'; 
                        $badge_class = 'status-pending';
                        $badge_text = 'ยังไม่ส่ง';
                        
                        if ($status === 'submitted') {
                            $badge_class = 'status-submitted';
                            $badge_text = 'ส่งแล้ว (รอตรวจ)';
                        } elseif ($status === 'graded') {
                            $badge_class = 'status-graded';
                            $badge_text = 'ตรวจแล้ว';
                        }
                    ?>
                    <a href="submit_assignment.php?id=<?= $a['id'] ?>" class="assignment-card" style="border-left-color: <?= ($status === 'graded') ? '#16A34A' : (($status === 'submitted') ? '#D97706' : '#DC2626') ?>;">
                        <div class="assignment-header">
                            <span class="assignment-code"><?= htmlspecialchars($a['subject_code']) ?></span>
                            <span class="status-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                        </div>
                        <div class="assignment-title"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="assignment-due">
                            <span class="material-symbols-rounded" style="font-size:14px; <?= ($status === 'pending') ? 'color:#DC2626;' : '' ?>">event</span>
                            กำหนดส่ง: <?= date('d M Y, H:i', strtotime($a['due_date'])) ?>
                        </div>
                        
                        <?php if ($status === 'graded'): ?>
                            <div class="score-display">
                                ได้คะแนน <?= htmlspecialchars($a['score']) ?> / <?= htmlspecialchars($a['max_score']) ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
