<?php
require_once 'includes/db.php';
require_login('admin');

$filter_subject_id = $_GET['subject_id'] ?? '';
$where = '';
$params = [];
if ($filter_subject_id) {
    $where = 'WHERE e.subject_id = :sid';
    $params['sid'] = $filter_subject_id;
}

// Fetch enrollments with student and subject info
$stmt = $pdo->prepare("
    SELECT e.id as enrollment_id, e.status, e.created_at,
           s.first_name, s.last_name, s.student_code,
           subj.name as subject_name, subj.code as subject_code
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN subjects subj ON e.subject_id = subj.id
    $where
    ORDER BY e.created_at DESC
");
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Fetch all students and subjects for the Add Modal
$all_students = $pdo->query("SELECT id, student_code, first_name, last_name FROM students ORDER BY first_name")->fetchAll();
$all_subjects = $pdo->query("SELECT id, code, name FROM subjects ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Manage Enrollments</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5); align-items: center; justify-content: center;
            z-index: 1000; backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff; padding: 32px; border-radius: 16px; width: 100%; max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #475569; }
        .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px;
        }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center; justify-content:space-between; display:flex;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Manage Enrollments</h1>
                    <p>จัดการสิทธิ์การเข้าเรียนของนักเรียน (เปิด/ปิดสิทธิ์)</p>
                </div>
                <button onclick="document.getElementById('enrollModal').classList.add('active')" class="btn btn-primary" style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; background:#1D4ED8; color:#fff; border:none; font-weight:600; cursor:pointer;">
                    <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Enrollment
                </button>
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

    <!-- Add Enrollment Modal -->
    <div class="modal-overlay" id="enrollModal">
        <div class="modal-box">
            <h3 style="margin:0 0 24px; font-size:18px; color:#0f172a;">Add New Enrollment</h3>
            <form action="api/add_enrollment.php" method="POST">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- เลือกนักเรียน --</option>
                        <?php foreach($all_students as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['student_code'].' - '.$st['first_name'].' '.$st['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Course</label>
                    <select name="subject_id" required>
                        <option value="">-- เลือกคอร์สเรียน --</option>
                        <?php foreach($all_subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= ($filter_subject_id == $sub['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub['code'].' - '.$sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="document.getElementById('enrollModal').classList.remove('active')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:#1D4ED8;color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
