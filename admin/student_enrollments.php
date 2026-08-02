<?php
require_once 'includes/db.php';
require_login('admin');

// Fetch all students with their total enrollments
$stmt = $pdo->query("
    SELECT s.id, s.student_code, s.first_name, s.last_name, s.rfid_tag,
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id AND status = 'active') as active_enrollments,
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id) as total_enrollments
    FROM students s
    ORDER BY s.first_name
");
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Manage Enrollments</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center; justify-content:space-between; display:flex;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Manage Enrollments</h1>
                    <p>เลือกนักเรียนเพื่อจัดการสิทธิ์การเข้าเรียน (เปิด/ปิดสิทธิ์)</p>
                </div>
            </div>

            <div class="card-desktop">
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>STUDENT CODE</th>
                            <th>STUDENT NAME</th>
                            <th>ENROLLMENTS</th>
                            <th style="text-align:right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $st): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; color:#0F172A;"><?= htmlspecialchars($st['student_code']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#334155; font-size:14px;">
                                    <?= htmlspecialchars($st['first_name'].' '.$st['last_name']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if($st['active_enrollments'] > 0): ?>
                                    <span style="background:#D1FAE5; color:#065F46; padding:4px 10px; border-radius:100px; font-size:12px; font-weight:700;"><?= $st['active_enrollments'] ?> Active</span>
                                <?php else: ?>
                                    <span style="background:#F1F5F9; color:#64748B; padding:4px 10px; border-radius:100px; font-size:12px; font-weight:700;">No active courses</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="manage_student_enrollments.php?id=<?= $st['id'] ?>" class="btn" style="padding:6px 14px; border-radius:6px; background:#F8FAFC; color:#0F172A; border:1px solid #E2E8F0; font-size:13px; font-weight:600; text-decoration:none;">Manage Enrollments</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($students)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:40px; color:var(--text-muted);">ไม่พบข้อมูลนักเรียน</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
