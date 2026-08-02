<?php
require_once '../config/db.php';


// Fetch subjects taught by this teacher
$stmt = $pdo->prepare("SELECT DISTINCT sub.id, sub.code, sub.name FROM schedules s JOIN subjects sub ON s.subject_id = sub.id WHERE s.teacher_id = ?");
$stmt->execute([$current_user_id]);
$subjects = $stmt->fetchAll();

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $title = $_POST['title'];
    $type = $_POST['content_type'];
    $url = $_POST['file_url'];
    
    if ($subject_id && $title && $type && $url) {
        try {
            $insert = $pdo->prepare("INSERT INTO course_contents (subject_id, teacher_id, title, content_type, file_url) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$subject_id, $current_user_id, $title, $type, $url]);
            $msg = "เพิ่มสื่อการสอนเรียบร้อยแล้ว";
            $msg_type = "success";
        } catch (Exception $e) {
            $msg = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล (คุณได้รัน SQL สร้างตารางหรือยัง?)";
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - จัดการสื่อการสอน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; }
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
                <a href="index.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">เพิ่มสื่อการสอนใหม่</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">เลือกวิชา</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- เลือกวิชา --</option>
                            <?php foreach($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['code'] . ' ' . $sub['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อเรื่อง / บทเรียน</label>
                        <input type="text" name="title" class="form-control" placeholder="เช่น บทที่ 1: การเคลื่อนที่" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ประเภท</label>
                        <select name="content_type" class="form-control" required>
                            <option value="video">วิดีโอ (YouTube/Vimeo)</option>
                            <option value="document">เอกสาร (PDF/Google Drive)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ลิงก์ (URL)</label>
                        <input type="url" name="file_url" class="form-control" placeholder="https://..." required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">อัปโหลด / เพิ่มสื่อการสอน</button>
                </form>
            </div>
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
