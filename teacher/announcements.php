<?php
require_once '../config/db.php';
$current_user_id = $_SESSION['user_id'] ?? 1;

// Fetch subjects taught by this teacher
$stmt = $pdo->prepare("SELECT DISTINCT sub.id, sub.code, sub.name FROM schedules s JOIN subjects sub ON s.subject_id = sub.id WHERE s.teacher_id = ?");
$stmt->execute([$current_user_id]);
$subjects = $stmt->fetchAll();

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $message = $_POST['message'];
    
    if ($subject_id && $message) {
        try {
            $insert = $pdo->prepare("INSERT INTO announcements (subject_id, teacher_id, message) VALUES (?, ?, ?)");
            $insert->execute([$subject_id, $current_user_id, $message]);
            $msg = "ส่งประกาศเรียบร้อยแล้ว";
            $msg_type = "success";
        } catch (Exception $e) {
            $msg = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล (คุณได้รัน SQL สร้างตารางหรือยัง?)";
            $msg_type = "error";
        }
    }
}

// Fetch existing announcements
try {
    $stmt = $pdo->prepare("
        SELECT a.*, sub.code 
        FROM announcements a 
        JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.teacher_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$current_user_id]);
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    $announcements = []; // Tables not created yet
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ประกาศข่าวสาร</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: bold; }
        .alert-success { background: #DCFCE7; color: #16A34A; }
        .alert-error { background: #FEE2E2; color: #DC2626; }
        
        .announcement-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: var(--shadow-sm); margin-bottom: 12px; border-left: 4px solid var(--primary); }
        .announcement-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .announcement-code { font-size: 11px; font-weight: 700; background: #E0E7FF; color: var(--primary); padding: 4px 8px; border-radius: 8px; }
        .announcement-time { font-size: 11px; color: var(--text-muted); }
        .announcement-message { font-size: 14px; color: #0F172A; line-height: 1.5; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-6 py-4">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <a href="index.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">ประกาศข่าวสาร</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">เลือกวิชาที่ต้องการประกาศ</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- เลือกวิชา --</option>
                            <?php foreach($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['code'] . ' ' . $sub['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ข้อความประกาศ</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="พิมพ์ข้อความที่ต้องการแจ้งให้นักเรียนทราบ..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">ส่งประกาศ</button>
                </form>
            </div>
            
            <h3 class="font-bold text-lg mb-4">ประวัติการส่งประกาศ</h3>
            
            <?php if(empty($announcements)): ?>
                <div class="text-center p-6 text-secondary" style="background:#fff; border-radius:16px;">ยังไม่มีประวัติการส่งประกาศ หรือยังไม่ได้สร้างตาราง Database</div>
            <?php else: ?>
                <?php foreach($announcements as $a): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <span class="announcement-code"><?= htmlspecialchars($a['code']) ?></span>
                            <span class="announcement-time"><?= date('d M H:i', strtotime($a['created_at'])) ?></span>
                        </div>
                        <div class="announcement-message"><?= htmlspecialchars($a['message']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
