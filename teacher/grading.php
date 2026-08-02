<?php
require_once '../config/db.php';

$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    die("Invalid Assignment ID");
}

$msg = '';
$msg_type = '';

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scores'])) {
    try {
        foreach ($_POST['scores'] as $student_id => $score_data) {
            $score = $score_data['score'];
            $feedback = $score_data['feedback'];
            
            if ($score !== '') {
                // Check if grade exists
                $stmt = $pdo->prepare("SELECT id FROM student_grades WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
                if ($stmt->fetch()) {
                    $upd = $pdo->prepare("UPDATE student_grades SET score = ?, feedback = ?, status = 'graded' WHERE assignment_id = ? AND student_id = ?");
                    $upd->execute([$score, $feedback, $assignment_id, $student_id]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO student_grades (assignment_id, student_id, score, feedback, status) VALUES (?, ?, ?, ?, 'graded')");
                    $ins->execute([$assignment_id, $student_id, $score, $feedback]);
                }
            }
        }
        $msg = "บันทึกคะแนนเรียบร้อยแล้ว";
        $msg_type = "success";
    } catch (Exception $e) {
        $msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $msg_type = "error";
    }
}

// Fetch assignment details
try {
    $stmt = $pdo->prepare("SELECT a.*, sub.name as subject_name FROM assignments a JOIN subjects sub ON a.subject_id = sub.id WHERE a.id = ? AND a.teacher_id = ?");
    $stmt->execute([$assignment_id, $current_user_id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        die("Assignment not found or permission denied.");
    }

    // Fetch enrolled students and their grades
    $stmt_students = $pdo->prepare("
        SELECT st.id, st.student_code, st.first_name, st.last_name, g.score, g.feedback, g.submission_text, g.submission_file_url, g.status
        FROM enrollments e
        JOIN students st ON e.student_id = st.id
        LEFT JOIN student_grades g ON st.id = g.student_id AND g.assignment_id = ?
        WHERE e.subject_id = ?
        ORDER BY st.student_code ASC
    ");
    $stmt_students->execute([$assignment_id, $assignment['subject_id']]);
    $students = $stmt_students->fetchAll();
    
} catch (Exception $e) {
    die("Database Error. Ensure you have run the SQL script.");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ตรวจการบ้าน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .student-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); margin-bottom: 12px; }
        .student-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .avatar-sm { width: 40px; height: 40px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569; }
        .score-input { width: 80px; padding: 8px; border: 1px solid var(--border); border-radius: 8px; font-weight: bold; text-align: center; }
        .feedback-input { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; margin-top: 8px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: bold; }
        .alert-success { background: #DCFCE7; color: #16A34A; }
        .alert-error { background: #FEE2E2; color: #DC2626; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-6 py-4">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <a href="assignments.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">ตรวจให้คะแนน</h2>
            </div>
            
            <div style="background:#F1F5F9; border-radius:12px; padding:16px; margin-bottom:20px;">
                <div class="font-bold text-lg"><?= htmlspecialchars($assignment['title']) ?></div>
                <div class="text-sm text-secondary mt-1">วิชา: <?= htmlspecialchars($assignment['subject_name']) ?></div>
                <div class="text-sm font-bold text-primary mt-2">คะแนนเต็ม: <?= htmlspecialchars($assignment['max_score']) ?></div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php foreach($students as $s): ?>
                    <?php 
                        $status_badge = '<span style="font-size:10px; padding:2px 6px; background:#FEE2E2; color:#DC2626; border-radius:8px;">ยังไม่ส่ง</span>';
                        if (($s['status'] ?? '') === 'submitted') {
                            $status_badge = '<span style="font-size:10px; padding:2px 6px; background:#FEF3C7; color:#D97706; border-radius:8px;">ส่งแล้ว</span>';
                        } elseif (($s['status'] ?? '') === 'graded') {
                            $status_badge = '<span style="font-size:10px; padding:2px 6px; background:#DCFCE7; color:#16A34A; border-radius:8px;">ตรวจแล้ว</span>';
                        }
                    ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="avatar-sm"><?= substr($s['first_name'], 0, 3) ?></div>
                            <div style="flex:1;">
                                <div class="font-bold text-sm text-main"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?> <?= $status_badge ?></div>
                                <div class="text-xs text-secondary"><?= htmlspecialchars($s['student_code']) ?></div>
                            </div>
                            <div>
                                <input type="number" name="scores[<?= $s['id'] ?>][score]" class="score-input" placeholder="คะแนน" value="<?= htmlspecialchars($s['score'] ?? '') ?>" max="<?= $assignment['max_score'] ?>" step="0.1">
                            </div>
                        </div>
                        
                        <?php if(!empty($s['submission_text']) || !empty($s['submission_file_url'])): ?>
                            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:12px; margin-bottom:12px; font-size:12px;">
                                <div class="font-bold text-primary mb-1">งานที่ส่ง:</div>
                                <?php if(!empty($s['submission_text'])): ?>
                                    <div style="margin-bottom:8px;"><?= nl2br(htmlspecialchars($s['submission_text'])) ?></div>
                                <?php endif; ?>
                                <?php if(!empty($s['submission_file_url'])): ?>
                                    <a href="<?= htmlspecialchars($s['submission_file_url']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:4px; background:#E0E7FF; color:var(--primary); padding:4px 8px; border-radius:6px; text-decoration:none; font-weight:600;">
                                        <span class="material-symbols-rounded" style="font-size:14px;">link</span> เปิดดูไฟล์แนบ
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <input type="text" name="scores[<?= $s['id'] ?>][feedback]" class="feedback-input" placeholder="ข้อเสนอแนะ (Feedback)..." value="<?= htmlspecialchars($s['feedback'] ?? '') ?>">
                    </div>
                <?php endforeach; ?>
                
                <div style="position: sticky; bottom: 80px; padding-top: 16px;">
                    <button type="submit" class="btn btn-primary w-full shadow-lg">บันทึกคะแนนทั้งหมด</button>
                </div>
            </form>
            
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
