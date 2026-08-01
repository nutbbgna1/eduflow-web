<?php
require_once '../config/db.php';
$page_title = 'Edit Profile';
$show_back = true;
$student_id = $_SESSION['user_id'] ?? 2;

$msg = '';
$msg_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    if ($first_name && $last_name) {
        // Check which table to update
        $check = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $check->execute([$student_id]);
        $table = $check->fetch() ? 'students' : 'users';

        $stmt = $pdo->prepare("UPDATE $table SET first_name = ?, last_name = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $student_id])) {
            $msg = 'Profile updated successfully!';
            $msg_type = 'success';
        } else {
            $msg = 'Failed to update profile.';
            $msg_type = 'error';
        }
    } else {
        $msg = 'Please fill in all fields.';
        $msg_type = 'error';
    }
}

// Fetch current data
$stmt = $pdo->prepare("SELECT first_name, last_name, student_code, username FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    // Try users table
    $stmt = $pdo->prepare("SELECT first_name, last_name, username FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if ($student) {
        $student['student_code'] = '-'; // Users table doesn't have student_code
    }
}

if (!$student) {
    die("Student not found.");
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
        .form-control:disabled {
            background: #E2E8F0;
            color: var(--text-muted);
            cursor: not-allowed;
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
        
        .avatar-edit-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }
        .avatar-wrapper {
            position: relative;
            display: inline-block;
        }
        .avatar-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .camera-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            cursor: pointer;
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
                <div class="avatar-edit-container">
                    <div class="avatar-wrapper">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop" class="avatar-img" alt="Avatar">
                        <div class="camera-btn">
                            <span class="material-symbols-rounded" style="font-size: 16px;">photo_camera</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Student ID (รหัสนักเรียน)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($student['student_code']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($student['username'] ?? '-') ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>First Name (ชื่อ)</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($student['first_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name (นามสกุล)</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($student['last_name']) ?>" required>
                    </div>

                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>
        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
