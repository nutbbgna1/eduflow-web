<?php
require_once '../config/db.php';
$page_title = 'Change Password';
$show_back = true;
$student_id = $_SESSION['user_id'] ?? 2;

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    // Fetch current password (check students first, then users)
    $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    $table = 'students';
    
    if (!$student) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        $table = 'users';
    }
    
    if ($student && $student['password'] === $current_pass) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 4) {
                $update = $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?");
                if ($update->execute([$new_pass, $student_id])) {
                    $msg = 'Password changed successfully!';
                    $msg_type = 'success';
                } else {
                    $msg = 'Database error. Failed to change password.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'New password must be at least 4 characters long.';
                $msg_type = 'error';
            }
        } else {
            $msg = 'New password and confirm password do not match.';
            $msg_type = 'error';
        }
    } else {
        $msg = 'Current password is incorrect.';
        $msg_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #0F172A;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            color: #0F172A;
            background: #F8FAFC;
            transition: all 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .btn-save:hover {
            background: #1D4ED8;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .alert-success { background: #DCFCE7; color: #16A34A; }
        .alert-error { background: #FEE2E2; color: #DC2626; }
        
        .pass-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5 py-4">
            <?php if($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="form-card">
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="width: 64px; height: 64px; background: #EEF2FF; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                        <span class="material-symbols-rounded" style="font-size: 32px;">lock</span>
                    </div>
                    <h2 style="font-size: 18px; font-weight: 700; color: #0F172A;">Change Password</h2>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Update your password to keep your account secure.</p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group" style="margin-top: 24px;">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                        <div class="pass-hint">Must be at least 4 characters long.</div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-save" style="margin-top: 16px;">Update Password</button>
                </form>
            </div>
        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
