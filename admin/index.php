<?php
// admin/index.php
require_once 'includes/db.php';

// Fetch real stats
$active_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$active_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$current_day = date('l');
$today_classes = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE day_of_week = :day");
$today_classes->execute(['day' => $current_day]);
$today_classes = $today_classes->fetchColumn();

// Pending Leaves
$stmt = $pdo->query("
    SELECT lr.*, u.first_name, u.last_name, subj.name as subject_name
    FROM leave_requests lr
    JOIN users u ON lr.requester_id = u.id
    JOIN schedules s ON lr.schedule_id = s.id
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at DESC LIMIT 3
");
$pending_leaves = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header">
                <div class="page-title">
                    <h1><?= __('Dashboard Overview') ?></h1>
                    <p><?= __('Welcome back. Here\'s what\'s happening today.') ?></p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn btn-outline" style="display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:8px; border:1px solid var(--border); background:#fff; font-weight:600;">
                        <span class="material-symbols-rounded" style="font-size:18px;">download</span> <?= __('Export Report') ?>
                    </button>
                    <button class="btn btn-primary" style="padding:10px 16px; border-radius:8px; background:var(--primary); color:#fff; border:none; font-weight:600;">
                        <?= __('New Enrollment') ?>
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stat-cards-grid">
                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title"><?= __('Total Students') ?></div>
                            <div class="sc-value"><?= number_format($active_students) ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#3B82F6;">
                            <span class="material-symbols-rounded">school</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title"><?= __('Total Teachers') ?></div>
                            <div class="sc-value"><?= number_format($active_teachers) ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#F1F5F9; display:flex; align-items:center; justify-content:center; color:#64748B;">
                            <span class="material-symbols-rounded">badge</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title"><?= __('Total Subjects') ?></div>
                            <div class="sc-value"><?= number_format($total_subjects) ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#3B82F6;">
                            <span class="material-symbols-rounded">menu_book</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d" style="border-top: 4px solid #10B981;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title"><?= __('Today\'s Classes') ?></div>
                            <div class="sc-value"><?= number_format($today_classes) ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#D1FAE5; display:flex; align-items:center; justify-content:center; color:#059669;">
                            <span class="material-symbols-rounded">event</span>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Bottom Row -->
            <div class="dash-bottom-row">
                <div class="panel-d">
                    <div class="panel-d-title">
                        <?= __('Pending Leave Requests') ?>
                        <a href="leave.php" style="font-size:14px; color:var(--primary); font-weight:600; text-decoration:none;"><?= __('View All') ?> &rarr;</a>
                    </div>
                    
                    <?php if (empty($pending_leaves)): ?>
                        <div style="color:#64748B; padding:20px 0;"><?= __('No pending requests.') ?></div>
                    <?php else: ?>
                        <?php foreach($pending_leaves as $leave): 
                            $initials = getInitials($leave['first_name'], $leave['last_name']);
                            $color = getAvatarColor($leave['first_name']);
                        ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 0; border-bottom:1px solid #F1F5F9;">
                            <div style="display:flex; align-items:center; gap:16px;">
                                <div style="width:48px; height:48px; border-radius:50%; background:<?= $color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div style="font-weight:600; font-size:15px; color:#0F172A;"><?= htmlspecialchars($leave['first_name'].' '.$leave['last_name']) ?></div>
                                    <div style="font-size:13px; color:#64748B;"><?= htmlspecialchars($leave['subject_name']) ?> &bull; <?= ucfirst($leave['leave_type']) ?> Leave</div>
                                </div>
                            </div>
                            <div style="font-size:13px; color:#475569; font-weight:500;">
                                Oct <?= date('d', strtotime($leave['leave_date'])) ?> - <?= date('M d', strtotime($leave['leave_date'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="panel-d">
                    <div class="panel-d-title"><?= __('Quick Actions') ?></div>
                    <div class="qa-grid">
                        <a href="staff.php" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">person_add</span>
                            <?= __('Add Teacher') ?>
                        </a>
                        <a href="schedule.php" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">event</span>
                            <?= __('New Event') ?>
                        </a>
                        <a href="#" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">campaign</span>
                            <?= __('Announcement') ?>
                        </a>
                        <a href="#" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">description</span>
                            <?= __('Generate Report') ?>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
