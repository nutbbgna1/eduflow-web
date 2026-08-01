<?php
// admin/staff.php
require_once 'includes/db.php';

// Fetch all teachers
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY first_name ASC");
$teachers = $stmt->fetchAll();

$total_teachers = count($teachers);
$active_teachers = count(array_filter($teachers, function($t) { return ($t['status'] ?? 'active') === 'active'; }));

$total_rate = 0;
foreach ($teachers as $t) {
    $total_rate += ($t['hourly_rate'] ?? 0);
}
$avg_rate = $total_teachers > 0 ? $total_rate / $total_teachers : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Teachers</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header">
                <div class="page-title">
                    <h1>Teachers Directory</h1>
                    <p>Manage teaching staff, rates, and system access.</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openTeacherModal()" style="display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:8px; background:var(--primary); color:#fff; border:none; font-weight:600; cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add New Teacher
                    </button>
                </div>
            </div>

            <!-- Stats Grid (3 columns) -->
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; margin-bottom:24px;">
                <div class="stat-card-d" style="display:flex; flex-direction:column; justify-content:center;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <div style="width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#3B82F6;">
                            <span class="material-symbols-rounded">group</span>
                        </div>
                        <div class="sc-title" style="margin:0;">TOTAL TEACHERS</div>
                    </div>
                    <div class="sc-value"><?= $total_teachers ?></div>
                </div>

                <div class="stat-card-d" style="display:flex; flex-direction:column; justify-content:center;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <div style="width:40px; height:40px; border-radius:50%; background:#DCFCE7; display:flex; align-items:center; justify-content:center; color:#16A34A;">
                            <span class="material-symbols-rounded">check_circle</span>
                        </div>
                        <div class="sc-title" style="margin:0;">ACTIVE STATUS</div>
                    </div>
                    <div class="sc-value"><?= $active_teachers ?></div>
                </div>

                <div class="stat-card-d" style="display:flex; flex-direction:column; justify-content:center;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <div style="width:40px; height:40px; border-radius:50%; background:#F1F5F9; display:flex; align-items:center; justify-content:center; color:#64748B;">
                            <span class="material-symbols-rounded">payments</span>
                        </div>
                        <div class="sc-title" style="margin:0;">AVG. HOURLY RATE</div>
                    </div>
                    <div class="sc-value">฿<?= number_format($avg_rate, 2) ?><span style="font-size:16px; color:#64748B; font-weight:500;">/hr</span></div>
                </div>
            </div>

            <!-- Teachers Table -->
            <div class="panel-d" style="padding:0; overflow:hidden;">
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>TEACHER NAME</th>
                            <th>CONTACT</th>
                            <th>HOURLY RATE</th>
                            <th>STATUS</th>
                            <th style="text-align:right;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $teacher): 
                            $initials = getInitials($teacher['first_name'], $teacher['last_name']);
                            $color = getAvatarColor($teacher['first_name']);
                            $status = $teacher['status'] ?? 'active';
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:40px; height:40px; border-radius:50%; background:<?= $color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px;">
                                        <?= $initials ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:#0F172A;"><?= htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']) ?></div>
                                        <div style="font-size:12px; color:#64748B;"><?= htmlspecialchars($teacher['username']) ?>@eduflow.edu</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color:#334155; font-size:14px;"><?= htmlspecialchars($teacher['phone'] ?? '+66 000 0000') ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0F172A;">฿<?= number_format($teacher['hourly_rate'] ?? 0, 2) ?></div>
                            </td>
                            <td>
                                <?php if($status === 'active'): ?>
                                    <span style="background:#DCFCE7; color:#16A34A; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;">Active</span>
                                <?php else: ?>
                                    <span style="background:#F1F5F9; color:#64748B; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end;">
                                <button class="btn btn-outline" onclick='editTeacher(<?= json_encode($teacher) ?>)' style="padding:6px; border:none; background:transparent; color:#3B82F6; cursor:pointer;" title="Edit">
                                    <span class="material-symbols-rounded">edit</span>
                                </button>
                                <button class="btn btn-outline" onclick="deleteTeacher(<?= $teacher['id'] ?>)" style="padding:6px; border:none; background:transparent; color:#EF4444; cursor:pointer;" title="Delete">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:16px 24px; border-top:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; color:#64748B; font-size:14px;">
                    <div>Showing 1 to <?= count($teachers) ?> of <?= count($teachers) ?></div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-outline" style="padding:6px 12px; border-radius:6px; border:1px solid #E2E8F0; background:#fff; color:#94A3B8;" disabled>Prev</button>
                        <button class="btn btn-outline" style="padding:6px 12px; border-radius:6px; border:1px solid #E2E8F0; background:#fff; color:#94A3B8;" disabled>Next</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Teacher Modal -->
    <div id="teacherModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; width:100%; max-width:500px; border-radius:12px; padding:24px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="modalTitle" style="margin:0; font-size:20px; color:#0F172A;">Add New Teacher</h2>
                <button onclick="closeTeacherModal()" style="background:transparent; border:none; font-size:24px; cursor:pointer; color:#64748B;">&times;</button>
            </div>
            <form id="teacherForm" action="api/save_teacher.php" method="POST">
                <input type="hidden" name="id" id="teacherId" value="">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">First Name</label>
                        <input type="text" name="first_name" id="teacherFirstName" required style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Last Name</label>
                        <input type="text" name="last_name" id="teacherLastName" required style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Username</label>
                        <input type="text" name="username" id="teacherUsername" required style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Password <small id="pwdHelp" style="color:#94A3B8; font-weight:normal;"></small></label>
                        <input type="password" name="password" id="teacherPassword" required style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Email</label>
                        <input type="email" name="email" id="teacherEmail" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Phone</label>
                        <input type="text" name="phone" id="teacherPhone" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Hourly Rate (฿)</label>
                        <input type="number" step="0.01" name="hourly_rate" id="teacherRate" value="500" required style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#334155;">Status</label>
                        <select name="status" id="teacherStatus" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:8px; outline:none; box-sizing:border-box; background:#fff;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" onclick="closeTeacherModal()" style="padding:10px 16px; border-radius:8px; background:#F1F5F9; color:#475569; border:none; font-weight:600; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 16px; border-radius:8px; background:#3B82F6; color:#fff; border:none; font-weight:600; cursor:pointer;">Save Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openTeacherModal() {
        document.getElementById('modalTitle').innerText = 'Add New Teacher';
        document.getElementById('teacherForm').reset();
        document.getElementById('teacherId').value = '';
        document.getElementById('teacherPassword').required = true;
        document.getElementById('pwdHelp').innerText = '';
        document.getElementById('teacherModal').style.display = 'flex';
    }

    function closeTeacherModal() {
        document.getElementById('teacherModal').style.display = 'none';
    }

    function editTeacher(teacher) {
        document.getElementById('modalTitle').innerText = 'Edit Teacher';
        document.getElementById('teacherId').value = teacher.id;
        document.getElementById('teacherFirstName').value = teacher.first_name;
        document.getElementById('teacherLastName').value = teacher.last_name;
        document.getElementById('teacherUsername').value = teacher.username;
        document.getElementById('teacherPassword').required = false; // Optional on edit
        document.getElementById('pwdHelp').innerText = '(Leave blank to keep current)';
        document.getElementById('teacherEmail').value = teacher.email || '';
        document.getElementById('teacherPhone').value = teacher.phone || '';
        document.getElementById('teacherRate').value = teacher.hourly_rate || '0';
        document.getElementById('teacherStatus').value = teacher.status || 'active';
        
        document.getElementById('teacherModal').style.display = 'flex';
    }

    function deleteTeacher(id) {
        if(confirm('Are you sure you want to delete this teacher? This action cannot be undone.')) {
            window.location.href = 'api/delete_teacher.php?id=' + id;
        }
    }

    // Close modal on outside click
    document.getElementById('teacherModal').addEventListener('click', function(e) {
        if(e.target === this) {
            closeTeacherModal();
        }
    });
    </script>
</body>
</html>
