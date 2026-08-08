<?php
session_start();
require_once 'config/db.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['role'] === 'teacher') {
        header("Location: teacher/index.php");
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/index.php");
        exit;
    }
    // If role is invalid, destroy session to prevent loop
    session_unset();
    session_destroy();
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, role, password FROM users WHERE username = :u");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } elseif ($user['role'] === 'student') {
                header("Location: student/index.php");
            } else {
                header("Location: teacher/index.php");
            }
            exit;
        } else {
            // Check student table
            $stmt_student = $pdo->prepare("SELECT id, password FROM students WHERE student_code = :u");
            $stmt_student->execute(['u' => $username]);
            $student = $stmt_student->fetch();

            if ($student && $student['password'] === $password) {
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['role'] = 'student';
                
                header("Location: student/index.php");
                exit;
            } else {
                $error = 'Username หรือ Password ไม่ถูกต้อง';
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #0F172A;
        }
        .login-card {
            background: #FFFFFF;
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            margin: 20px;
        }
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #3B82F6;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        .subtitle {
            color: #64748B;
            font-size: 15px;
            margin-bottom: 32px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #CBD5E1;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #3B82F6;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #3B82F6;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
        }
        .btn-primary:hover {
            background: #2563EB;
        }
        .error-msg {
            background: #FEE2E2;
            color: #B91C1C;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .info {
            margin-top: 24px;
            font-size: 13px;
            color: #94A3B8;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">EduFlow</div>
    <div class="subtitle">ระบบบริหารจัดการโรงเรียนกวดวิชา</div>

    <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
        <div class="error-msg">กรุณาเข้าสู่ระบบก่อนใช้งาน</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'forbidden'): ?>
        <div class="error-msg">คุณไม่มีสิทธิ์เข้าถึงส่วนนี้</div>
    <?php endif; ?>

    <form method="POST" action="index.php">
        <div class="form-group">
            <label for="username">Username (ผู้ดูแล/ครู) หรือ รหัสนักเรียน</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="ชื่อผู้ใช้ หรือ รหัสนักเรียน..." required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน (นักเรียนใช้ 123456)..." required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary">เข้าสู่ระบบ</button>
    </form>

    <div class="info">
        <b>ทดสอบระบบ:</b><br>
        Admin: admin / 1234<br>
        Teacher: teacher1 / 1234<br>
        Student: student1 / 1234
    </div>
</div>

</body>
</html>
