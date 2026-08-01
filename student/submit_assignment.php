<?php
require_once '../config/db.php';
$student_id = $_SESSION['user_id'] ?? 2;
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    die("Invalid Assignment ID");
}

$msg = '';
$msg_type = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_text = $_POST['submission_text'] ?? '';
    $submission_file_url = $_POST['submission_file_url'] ?? '';
    
    try {
        // Check if grade record exists
        $stmt = $pdo->prepare("SELECT id, status FROM student_grades WHERE assignment_id = ? AND student_id = ?");
        $stmt->execute([$assignment_id, $student_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update
            if ($existing['status'] !== 'graded') {
                $upd = $pdo->prepare("UPDATE student_grades SET submission_text = ?, submission_file_url = ?, submitted_at = NOW(), status = 'submitted' WHERE assignment_id = ? AND student_id = ?");
                $upd->execute([$submission_text, $submission_file_url, $assignment_id, $student_id]);
                $msg = "อัปเดตงานที่ส่งเรียบร้อยแล้ว";
                $msg_type = "success";
            } else {
                $msg = "การบ้านนี้ถูกตรวจแล้ว ไม่สามารถแก้ไขได้";
                $msg_type = "error";
            }
        } else {
            // Insert
            $ins = $pdo->prepare("INSERT INTO student_grades (assignment_id, student_id, submission_text, submission_file_url, submitted_at, status) VALUES (?, ?, ?, ?, NOW(), 'submitted')");
            $ins->execute([$assignment_id, $student_id, $submission_text, $submission_file_url]);
            $msg = "ส่งการบ้านเรียบร้อยแล้ว";
            $msg_type = "success";
        }
    } catch (Exception $e) {
        $msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $msg_type = "error";
    }
}

// Fetch assignment details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, sub.code, sub.name as subject_name
        FROM assignments a
        JOIN subjects sub ON a.subject_id = sub.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        die("Assignment not found");
    }

    // Fetch my submission
    $stmt_sub = $pdo->prepare("SELECT * FROM student_grades WHERE assignment_id = ? AND student_id = ?");
    $stmt_sub->execute([$assignment_id, $student_id]);
    $submission = $stmt_sub->fetch();
    
} catch (Exception $e) {
    die("Database Error");
}

$status = $submission['status'] ?? 'pending';
$is_readonly = ($status === 'graded');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ส่งการบ้าน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: bold; }
        .alert-success { background: #DCFCE7; color: #16A34A; }
        .alert-error { background: #FEE2E2; color: #DC2626; }
        
        .detail-box { background: #F1F5F9; padding: 16px; border-radius: 12px; margin-bottom: 20px; }
        .feedback-box { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 16px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-5 pb-24 pt-4">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <a href="assignments.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">ส่งการบ้าน</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="detail-box">
                <div class="text-sm text-primary font-bold mb-1"><?= htmlspecialchars($assignment['code'] . ' ' . $assignment['subject_name']) ?></div>
                <div class="text-lg font-bold mb-2"><?= htmlspecialchars($assignment['title']) ?></div>
                <div class="text-sm text-secondary mb-4"><?= nl2br(htmlspecialchars($assignment['description'])) ?></div>
                
                <div style="display:flex; justify-content:space-between; border-top:1px solid #E2E8F0; padding-top:12px; font-size:12px;">
                    <div><span class="font-bold">คะแนนเต็ม:</span> <?= $assignment['max_score'] ?></div>
                    <div style="color:var(--danger);"><span class="font-bold">กำหนดส่ง:</span> <?= date('d M Y, H:i', strtotime($assignment['due_date'])) ?></div>
                </div>
            </div>
            
            <?php if ($status === 'graded'): ?>
                <div class="feedback-box">
                    <h3 class="font-bold text-success mb-2" style="font-size:16px; display:flex; align-items:center; gap:4px;">
                        <span class="material-symbols-rounded">verified</span> ตรวจแล้ว
                    </h3>
                    <div class="text-3xl font-bold text-success mb-2"><?= $submission['score'] ?> <span class="text-sm text-secondary">/ <?= $assignment['max_score'] ?></span></div>
                    <div class="text-sm">
                        <span class="font-bold">ข้อเสนอแนะจากครู:</span><br>
                        <?= nl2br(htmlspecialchars($submission['feedback'] ?? 'ไม่มีข้อเสนอแนะเพิ่มเติม')) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">พิมพ์คำตอบที่นี่</label>
                        <textarea name="submission_text" class="form-control" rows="4" placeholder="พิมพ์เนื้อหาการบ้านของคุณ..." <?= $is_readonly ? 'disabled' : '' ?>><?= htmlspecialchars($submission['submission_text'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ลิงก์แนบไฟล์ (Google Drive, Docs ฯลฯ)</label>
                        <input type="url" name="submission_file_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($submission['submission_file_url'] ?? '') ?>" <?= $is_readonly ? 'disabled' : '' ?>>
                    </div>
                    
                    <?php if (!$is_readonly): ?>
                        <button type="submit" class="btn btn-primary w-full"><?= ($status === 'submitted') ? 'อัปเดตการส่งงาน' : 'ส่งการบ้าน' ?></button>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline w-full" disabled>ให้คะแนนแล้ว ไม่สามารถแก้ไขได้</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
