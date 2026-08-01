<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = $_POST['id'] ?? '';
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $hourly_rate = (float)($_POST['hourly_rate'] ?? 500);
    $status      = in_array($_POST['status'] ?? '', ['active','inactive','on_leave']) ? $_POST['status'] : 'active';

    if (!$first_name || !$last_name || !$username) {
        header("Location: ../staff.php?error=missing_fields");
        exit;
    }

    if (empty($id) && !$password) {
        // Password required for new teacher
        header("Location: ../staff.php?error=missing_fields");
        exit;
    }

    // Check username not taken by someone else
    if (empty($id)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :u");
        $check->execute(['u' => $username]);
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id");
        $check->execute(['u' => $username, 'id' => $id]);
    }
    
    if ($check->fetch()) {
        header("Location: ../staff.php?error=username_taken");
        exit;
    }

    if (empty($id)) {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, first_name, last_name, role, email, phone, hourly_rate, status)
            VALUES (:username, :password, :fn, :ln, 'teacher', :email, :phone, :rate, :status)
        ");
        $stmt->execute([
            'username' => $username,
            'password' => $password, // In production: use password_hash()
            'fn'       => $first_name,
            'ln'       => $last_name,
            'email'    => $email,
            'phone'    => $phone,
            'rate'     => $hourly_rate,
            'status'   => $status,
        ]);
    } else {
        // UPDATE
        if (!empty($password)) {
            $stmt = $pdo->prepare("
                UPDATE users SET username = :username, password = :password, first_name = :fn, last_name = :ln, 
                                 email = :email, phone = :phone, hourly_rate = :rate, status = :status
                WHERE id = :id AND role = 'teacher'
            ");
            $stmt->execute([
                'username' => $username,
                'password' => $password, // In production: use password_hash()
                'fn'       => $first_name,
                'ln'       => $last_name,
                'email'    => $email,
                'phone'    => $phone,
                'rate'     => $hourly_rate,
                'status'   => $status,
                'id'       => $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET username = :username, first_name = :fn, last_name = :ln, 
                                 email = :email, phone = :phone, hourly_rate = :rate, status = :status
                WHERE id = :id AND role = 'teacher'
            ");
            $stmt->execute([
                'username' => $username,
                'fn'       => $first_name,
                'ln'       => $last_name,
                'email'    => $email,
                'phone'    => $phone,
                'rate'     => $hourly_rate,
                'status'   => $status,
                'id'       => $id
            ]);
        }
    }

    header("Location: ../staff.php?success=1");
    exit;
}
header("Location: ../staff.php");
