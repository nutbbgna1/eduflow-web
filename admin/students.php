<?php
// admin/students.php
require_once 'includes/db.php';

// Fetch students
$stmt = $pdo->query("
    SELECT s.*, 
           (SELECT name FROM subjects subj JOIN enrollments e ON e.subject_id = subj.id WHERE e.student_id = s.id LIMIT 1) as primary_subject
    FROM students s
    ORDER BY s.first_name ASC
");
$students = $stmt->fetchAll();

// Determine selected student
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : (count($students) > 0 ? $students[0]['id'] : 0);

$selected_student = null;
$enrolled_subjects = [];

if ($selected_id) {
    // Fetch selected student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute(['id' => $selected_id]);
    $selected_student = $stmt->fetch();
    
    // Fetch enrolled subjects for selected student
    $stmt = $pdo->prepare("
        SELECT sub.id, sub.code, sub.name, sub.is_online, sub.is_onsite
        FROM enrollments e
        JOIN subjects sub ON e.subject_id = sub.id
        WHERE e.student_id = :sid
    ");
    $stmt->execute(['sid' => $selected_id]);
    $enrolled_subjects = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Students</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;"><?= __('Students') ?></h1>
                    <p><?= __('Students desc') ?></p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="document.getElementById('studentModal').classList.add('active')" style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; background:#1D4ED8; color:#fff; border:none; font-weight:600; cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> <?= __('New Student') ?>
                    </button>
                </div>
            </div>

            <div class="split-layout">
                
                <!-- Left Pane: Roster -->
                <div class="panel-d" style="padding:0; overflow:hidden;">
                    <div style="padding:24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <h2 style="font-size:18px; font-weight:700;"><?= __('Active Roster') ?></h2>
                        <div style="display:flex; gap:12px;">
                            <select style="padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:#fff; outline:none; font-size:14px;">
                                <option><?= __('All Subjects') ?></option>
                            </select>
                            <button class="btn btn-outline" style="padding:8px; border-radius:8px; border:1px solid var(--border); background:#fff; color:#64748B; display:flex; align-items:center;">
                                <span class="material-symbols-rounded" style="font-size:20px;">filter_list</span>
                            </button>
                        </div>
                    </div>
                    
                    <table class="table-desktop">
                        <thead>
                            <tr>
                                <th><?= __('ID / STUDENT') ?></th>
                                <th><?= __('PRIMARY SUBJECT') ?></th>
                                <th><?= __('STATUS') ?></th>
                                <th style="text-align:right;"><?= __('ACTIONS') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $idx => $student): 
                                $initials = getInitials($student['first_name'], $student['last_name']);
                                $color = getAvatarColor($student['first_name']);
                                $isActive = ($student['id'] == $selected_id);
                            ?>
                            <tr style="cursor: pointer; <?= $isActive ? 'background:#F8FAFC;' : '' ?>" onclick="window.location.href='students.php?id=<?= $student['id'] ?>'">
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <?php if($isActive): ?>
                                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&q=80" alt="Profile" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:40px; height:40px; border-radius:50%; background:<?= $color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px;">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:600; color:#0F172A;"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></div>
                                            <div style="font-size:12px; color:#64748B;"><?= htmlspecialchars($student['student_code']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="color:#334155; font-size:14px;"><?= htmlspecialchars($student['primary_subject'] ?? __('Unassigned')) ?></div>
                                </td>
                                <td>
                                    <?php if($idx % 2 === 0): ?>
                                        <span style="background:#DCFCE7; color:#16A34A; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('Active') ?></span>
                                    <?php else: ?>
                                        <span style="background:#FEF3C7; color:#D97706; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('Unpaid') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <button class="btn btn-outline" style="padding:6px; border:none; background:transparent; color:#94A3B8;">
                                        <span class="material-symbols-rounded">more_vert</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="padding:16px 24px; border-top:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; color:#64748B; font-size:14px;">
                        <div><?= __('Showing text') ?></div>
                        <div style="display:flex; gap:8px;">
                            <button class="btn btn-outline" style="padding:4px; border-radius:6px; border:none; background:transparent; color:#94A3B8;" disabled><span class="material-symbols-rounded">chevron_left</span></button>
                            <button class="btn btn-outline" style="padding:4px; border-radius:6px; border:none; background:transparent; color:#0F172A;"><span class="material-symbols-rounded">chevron_right</span></button>
                        </div>
                    </div>
                </div>

                <!-- Right Pane: Details -->
                <div class="profile-pane">
                    <?php if ($selected_student): ?>
                    <div class="profile-card">
                        <div class="profile-card-header"></div>
                        <?php
                            $s_initials = getInitials($selected_student['first_name'], $selected_student['last_name']);
                            $s_color = getAvatarColor($selected_student['first_name']);
                        ?>
                        <div class="profile-card-img" style="background:<?= $s_color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:40px; border:4px solid #fff;">
                            <?= $s_initials ?>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-card-name"><?= htmlspecialchars($selected_student['first_name'].' '.$selected_student['last_name']) ?></div>
                            <div class="profile-card-meta"><?= htmlspecialchars($selected_student['student_code']) ?></div>
                            
                            <div class="profile-card-actions">
                                <button class="btn" style="flex:1; padding:10px; border-radius:8px; background:#F1F5F9; color:#0F172A; border:none; font-weight:600;"><?= __('Add Subject') ?></button>
                                <button class="btn" style="padding:10px; border-radius:8px; background:#fff; border:1px solid var(--border); color:#64748B;"><span class="material-symbols-rounded">pause</span></button>
                                <button class="btn" style="padding:10px; border-radius:8px; background:#fff; border:1px solid var(--border); color:#64748B;"><span class="material-symbols-rounded">person_remove</span></button>
                            </div>
                        </div>
                    </div>

                    <div class="subjects-card">
                        <h3><?= __('ENROLLED SUBJECTS') ?></h3>
                        <?php if (empty($enrolled_subjects)): ?>
                            <div style="padding:20px; text-align:center; color:#94A3B8; font-size:14px;">
                                ไม่มีรายวิชาที่ลงทะเบียน
                            </div>
                        <?php else: ?>
                            <?php foreach ($enrolled_subjects as $sub): ?>
                            <div class="subject-item">
                                <div>
                                    <div style="font-weight:700; font-size:14px; color:#0F172A;"><?= htmlspecialchars($sub['code'] . ' - ' . $sub['name']) ?></div>
                                    <div style="font-size:12px; color:#64748B; margin-top:2px;">
                                        <?php 
                                        $types = [];
                                        if ($sub['is_online']) $types[] = 'Online';
                                        if ($sub['is_onsite']) $types[] = 'Onsite';
                                        echo implode(' / ', $types);
                                        ?>
                                    </div>
                                </div>
                                <span class="material-symbols-rounded" style="color:#16A34A;">check_circle</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="status-cards">
                        <div class="status-card">
                            <span class="material-symbols-rounded" style="color:#16A34A;">payments</span>
                            <div class="status-card-title"><?= __('Payment Status') ?></div>
                            <div class="status-card-value" style="color:#16A34A;">Paid (Mock)</div>
                        </div>
                        <div class="status-card">
                            <span class="material-symbols-rounded" style="<?= $selected_student['rfid_tag'] ? 'color:#16A34A;' : 'color:#94A3B8;' ?>">badge</span>
                            <div class="status-card-title"><?= __('RFID Card') ?></div>
                            <div class="status-card-value" style="<?= $selected_student['rfid_tag'] ? 'color:#16A34A;' : 'color:#94A3B8;' ?>">
                                <?= $selected_student['rfid_tag'] ? __('Linked') : 'Not Linked' ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94A3B8;">
                            กรุณาเลือกนักเรียนจากรายชื่อด้านซ้าย
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal-overlay" id="studentModal">
        <div class="modal-box">
            <h3 style="margin:0 0 24px; font-size:18px; color:#0f172a;">Add New Student</h3>
            <form action="api/save_student.php" method="POST">
                <div style="display:flex; gap:12px; margin-bottom:12px;">
                    <div class="form-group" style="flex:1;">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;">
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Student Code *</label>
                    <input type="text" name="student_code" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;" placeholder="e.g. S1001">
                </div>
                
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Grade (Optional)</label>
                    <input type="text" name="grade" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;">
                </div>
                
                <div class="form-group" style="margin-bottom:24px;">
                    <label>Program/Course (Optional)</label>
                    <input type="text" name="program" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;">
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" onclick="document.getElementById('studentModal').classList.remove('active')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">Cancel</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:#1D4ED8;color:#fff;cursor:pointer;font-weight:600;">Save</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
