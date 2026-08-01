<?php
require_once '../config/db.php';
$current_user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("Teacher not found");
}

$stmt_stats = $pdo->prepare("SELECT COUNT(*) as total_classes FROM schedules WHERE teacher_id = ?");
$stmt_stats->execute([$current_user_id]);
$stats = $stmt_stats->fetch();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - โปรไฟล์</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: var(--shadow-sm); text-align: center; margin-bottom: 24px; margin-top: 24px; }
        .avatar-lg { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: -50px auto 16px; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .teacher-name { font-size: 20px; font-weight: 700; color: #0F172A; margin-bottom: 4px; }
        .teacher-role { font-size: 13px; color: var(--primary); font-weight: 600; background: #E0E7FF; display: inline-block; padding: 4px 12px; border-radius: 20px; margin-bottom: 16px; }
        
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .stat-box { background: #F8FAFC; border-radius: 12px; padding: 16px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: 700; color: #0F172A; }
        .stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-top: 4px; }
        
        .settings-card { background: #fff; border-radius: 20px; padding: 8px 16px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .setting-item { display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid var(--border); color: var(--text-main); text-decoration: none; font-weight: 500; font-size: 14px; }
        .setting-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        <div class="px-6 py-4">
            
            <div class="profile-card">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&q=80" alt="Profile" class="avatar-lg">
                <div class="teacher-name"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></div>
                <div class="teacher-role">Teacher Account</div>
                
                <div class="stat-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?= $stats['total_classes'] ?></div>
                        <div class="stat-label">Classes Taught</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">100%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                </div>
            </div>
            
            <h3 class="font-bold text-lg mb-4">การตั้งค่า (Settings)</h3>
            
            <div class="settings-card">
                <a href="edit_profile.php" class="setting-item">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="material-symbols-rounded" style="color:var(--text-muted); font-size:20px;">person_outline</span>
                        แก้ไขโปรไฟล์
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--border); font-size:18px;">chevron_right</span>
                </a>
                <a href="change_password.php" class="setting-item">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="material-symbols-rounded" style="color:var(--text-muted); font-size:20px;">lock_outline</span>
                        เปลี่ยนรหัสผ่าน
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--border); font-size:18px;">chevron_right</span>
                </a>
                <a href="../logout.php" class="setting-item" style="color:var(--danger);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="material-symbols-rounded" style="font-size:20px;">logout</span>
                        ออกจากระบบ
                    </div>
                </a>
            </div>
            
        </div>
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
