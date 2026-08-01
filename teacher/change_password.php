<?php
require_once '../config/db.php';
$current_user_id = $_SESSION['user_id'] ?? 1;

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === $current_pass) {
        if ($new_pass === $confirm_pass) {
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($upd->execute([$new_pass, $current_user_id])) {
                $msg = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                $msg_type = 'success';
            }
        } else {
            $msg = 'รหัสผ่านใหม่ไม่ตรงกัน';
            $msg_type = 'error';
        }
    } else {
        $msg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        $msg_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - เปลี่ยนรหัสผ่าน</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
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
                <a href="profile.php" style="color:var(--primary);"><span class="material-symbols-rounded">arrow_back</span></a>
                <h2 class="font-bold text-xl">เปลี่ยนรหัสผ่าน</h2>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-top:24px;">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึกรหัสผ่าน</button>
                </form>
            </div>
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
