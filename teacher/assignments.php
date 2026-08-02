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
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_score = $_POST['max_score'];
    
    if ($subject_id && $title && $due_date) {
        try {
            $insert = $pdo->prepare("INSERT INTO assignments (subject_id, teacher_id, title, description, due_date, max_score) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$subject_id, $current_user_id, $title, $description, $due_date, $max_score]);
            $msg = "สร้างการบ้านใหม่เรียบร้อยแล้ว";
            $msg_type = "success";
        } catch (Exception $e) {
            $msg = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล (คุณได้รัน SQL สร้างตารางหรือยัง?)";
            $msg_type = "error";
        }
    }
}

// Fetch existing assignments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, sub.code 
        FROM assignments a 
        JOIN subjects sub ON a.subject_id = sub.id 
        WHERE a.teacher_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$current_user_id]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    $assignments = []; // Tables not created yet
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - จัดการการบ้าน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: bold; }
        .alert-success { background: #DCFCE7; color: #16A34A; }
        .alert-error { background: #FEE2E2; color: #DC2626; }
        
        .assignment-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: var(--shadow-sm); margin-bottom: 12px; display: flex; flex-direction: column; }
        .assignment-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .assignment-code { font-size: 11px; font-weight: 700; background: #F1F5F9; color: var(--text-muted); padding: 4px 8px; border-radius: 8px; }
        .assignment-title { font-weight: bold; color: #0F172A; font-size: 15px; margin-bottom: 4px; }
        .assignment-due { font-size: 12px; color: var(--danger); display: flex; align-items: center; gap: 4px; }
        .btn-grade { background: #F8FAFC; color: var(--primary); font-size: 12px; font-weight: 700; padding: 8px 16px; border-radius: 8px; text-decoration: none; text-align: center; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-6 py-4">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <a href="index.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">จัดการการบ้าน</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <!-- Create Assignment Form -->
            <div class="form-card">
                <h3 class="font-bold text-lg mb-4">สร้างการบ้านใหม่</h3>
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
                        <label class="form-label">หัวข้อการบ้าน</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">รายละเอียด / คำสั่ง (ถ้ามี)</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">กำหนดส่ง</label>
                            <input type="date" name="due_date" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">คะแนนเต็ม</label>
                            <input type="number" name="max_score" class="form-control" value="100" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">สั่งการบ้าน</button>
                </form>
            </div>
            
            <h3 class="font-bold text-lg mb-4">การบ้านที่สั่งแล้ว</h3>
            
            <?php if(empty($assignments)): ?>
                <div class="text-center p-6 text-secondary" style="background:#fff; border-radius:16px;">ยังไม่มีการบ้านที่สั่งไว้ หรือยังไม่ได้สร้างตาราง Database</div>
            <?php else: ?>
                <?php foreach($assignments as $a): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <span class="assignment-code"><?= htmlspecialchars($a['code']) ?></span>
                            <span class="text-xs text-muted" style="font-weight:600;">เต็ม <?= htmlspecialchars($a['max_score']) ?></span>
                        </div>
                        <div class="assignment-title"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="assignment-due">
                            <span class="material-symbols-rounded" style="font-size:14px;">event</span>
                            Due: <?= date('d M Y', strtotime($a['due_date'])) ?>
                        </div>
                        <a href="grading.php?id=<?= $a['id'] ?>" class="btn-grade">ให้คะแนนนักเรียน</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
