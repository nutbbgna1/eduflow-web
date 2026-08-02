<?php
require_once '../config/db.php';


$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    if ($first_name && $last_name) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $current_user_id])) {
            $msg = 'อัปเดตโปรไฟล์เรียบร้อยแล้ว';
            $msg_type = 'success';
        } else {
            $msg = 'เกิดข้อผิดพลาดในการอัปเดต';
            $msg_type = 'error';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$teacher = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - แก้ไขโปรไฟล์</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; background: #F8FAFC; }
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
                <a href="profile.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">แก้ไขโปรไฟล์</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($teacher['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อ</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($teacher['first_name']) ?>" style="background:#fff;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">นามสกุล</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($teacher['last_name']) ?>" style="background:#fff;" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึกข้อมูล</button>
                </form>
            </div>
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
