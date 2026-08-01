<?php
require_once 'includes/db.php';
require_login('admin');

// Fetch enrollments with student and subject info
$stmt = $pdo->query("
    SELECT e.id as enrollment_id, e.status, e.created_at,
           s.first_name, s.last_name, s.student_code,
           subj.name as subject_name, subj.code as subject_code
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN subjects subj ON e.subject_id = subj.id
    ORDER BY e.created_at DESC
");
$enrollments = $stmt->fetchAll();
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
            
            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Manage Enrollments</h1>
                    <p>จัดการสิทธิ์การเข้าเรียนของนักเรียน (เปิด/ปิดสิทธิ์)</p>
                </div>
            </div>

            <div class="card-desktop">
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>STUDENT</th>
                            <th>COURSE</th>
                            <th>ENROLLED DATE</th>
                            <th>STATUS</th>
                            <th style="text-align:right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($enrollments as $e): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; color:#0F172A;"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></div>
                                <div style="font-size:12px; color:#64748B;"><?= htmlspecialchars($e['student_code']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#334155; font-size:14px;"><?= htmlspecialchars($e['subject_name']) ?></div>
                                <div style="font-size:12px; color:#64748B;"><?= htmlspecialchars($e['subject_code']) ?></div>
                            </td>
                            <td style="color:var(--text-muted); font-size:13px;"><?= date('Y-m-d', strtotime($e['created_at'])) ?></td>
                            <td>
                                <?php if($e['status'] === 'active'): ?>
                                    <span style="background:#D1FAE5; color:#065F46; padding:4px 10px; border-radius:100px; font-size:12px; font-weight:700;">Active</span>
                                <?php else: ?>
                                    <span style="background:#FEE2E2; color:#DC2626; padding:4px 10px; border-radius:100px; font-size:12px; font-weight:700;">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <form action="api/update_enrollment_status.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="enrollment_id" value="<?= $e['enrollment_id'] ?>">
                                    <?php if($e['status'] === 'active'): ?>
                                        <input type="hidden" name="status" value="expired">
                                        <button type="submit" class="btn" style="padding:6px 12px; border-radius:6px; background:#FEE2E2; color:#DC2626; border:1px solid #DC2626; font-size:13px; font-weight:600; cursor:pointer;" onclick="return confirm('ระงับสิทธิ์การเข้าเรียนของนักเรียนคนนี้ใช่หรือไม่?')">Expire Access</button>
                                    <?php else: ?>
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn" style="padding:6px 12px; border-radius:6px; background:#D1FAE5; color:#065F46; border:1px solid #065F46; font-size:13px; font-weight:600; cursor:pointer;" onclick="return confirm('เปิดสิทธิ์การเข้าเรียนอีกครั้งใช่หรือไม่?')">Re-activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($enrollments)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">ไม่มีข้อมูลการสมัครเรียน</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
