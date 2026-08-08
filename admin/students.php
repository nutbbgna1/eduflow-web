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
                    <button class="btn btn-primary" style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; background:#1D4ED8; color:#fff; border:none; font-weight:600;">
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
                                $isActive = ($idx === 1); // Mock second item as selected
                            ?>
                            <tr style="<?= $isActive ? 'background:#F8FAFC;' : '' ?>">
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
                    <div class="profile-card">
                        <div class="profile-card-header"></div>
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&q=80" alt="Elena" class="profile-card-img">
                        <div class="profile-card-body">
                            <div class="profile-card-name">Elena Rodriguez</div>
                            <div class="profile-card-meta">S0002 &bull; Junior Year</div>
                            
                            <div class="profile-card-actions">
                                <button class="btn" style="flex:1; padding:10px; border-radius:8px; background:#F1F5F9; color:#0F172A; border:none; font-weight:600;"><?= __('Add Subject') ?></button>
                                <button class="btn" style="padding:10px; border-radius:8px; background:#fff; border:1px solid var(--border); color:#64748B;"><span class="material-symbols-rounded">pause</span></button>
                                <button class="btn" style="padding:10px; border-radius:8px; background:#fff; border:1px solid var(--border); color:#64748B;"><span class="material-symbols-rounded">person_remove</span></button>
                            </div>
                        </div>
                    </div>

                    <div class="subjects-card">
                        <h3><?= __('ENROLLED SUBJECTS') ?></h3>
                        <div class="subject-item">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:#0F172A;">Physics 101</div>
                                <div style="font-size:12px; color:#64748B; margin-top:2px;">Prof. Davis &bull; Room 302</div>
                            </div>
                            <span class="material-symbols-rounded" style="color:#16A34A;">check_circle</span>
                        </div>
                        <div class="subject-item">
                            <div>
                                <div style="font-weight:700; font-size:14px; color:#0F172A;">Calculus II</div>
                                <div style="font-size:12px; color:#64748B; margin-top:2px;">Prof. Smith &bull; Room 105</div>
                            </div>
                            <span class="material-symbols-rounded" style="color:#16A34A;">check_circle</span>
                        </div>
                    </div>

                    <div class="status-cards">
                        <div class="status-card">
                            <span class="material-symbols-rounded" style="color:#D97706;">payments</span>
                            <div class="status-card-title"><?= __('Payment Status') ?></div>
                            <div class="status-card-value" style="color:#D97706;"><?= __('Unpaid') ?></div>
                        </div>
                        <div class="status-card">
                            <span class="material-symbols-rounded" style="color:#16A34A;">badge</span>
                            <div class="status-card-title"><?= __('RFID Card') ?></div>
                            <div class="status-card-value" style="color:#16A34A;"><?= __('Linked') ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
