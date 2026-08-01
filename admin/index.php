<?php
// admin/index.php
require_once 'includes/db.php';

// Fetch Revenue (Mock calculation)
$monthly_revenue = 124500;
$teacher_costs = 45200;
$active_students = 1248;
$outstanding = 12400;

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
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back. Here's what's happening today.</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn btn-outline" style="display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:8px; border:1px solid var(--border); background:#fff; font-weight:600;">
                        <span class="material-symbols-rounded" style="font-size:18px;">download</span> Export Report
                    </button>
                    <button class="btn btn-primary" style="padding:10px 16px; border-radius:8px; background:var(--primary); color:#fff; border:none; font-weight:600;">
                        New Enrollment
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stat-cards-grid">
                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">MONTHLY REVENUE</div>
                            <div class="sc-value">$<?= number_format($monthly_revenue) ?></div>
                            <div class="sc-trend up"><span class="material-symbols-rounded">trending_up</span> +12.5% vs last month</div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#3B82F6;">
                            <span class="material-symbols-rounded">trending_up</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">EST. TEACHER COSTS</div>
                            <div class="sc-value">$<?= number_format($teacher_costs) ?></div>
                            <div class="sc-trend"><span class="material-symbols-rounded" style="font-size:16px;">arrow_right_alt</span> -2.1% vs last month</div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#F1F5F9; display:flex; align-items:center; justify-content:center; color:#64748B;">
                            <span class="material-symbols-rounded">payments</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">ACTIVE STUDENTS</div>
                            <div class="sc-value"><?= number_format($active_students) ?></div>
                            <div class="sc-trend up"><span class="material-symbols-rounded">trending_up</span> +45 new this week</div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; color:#3B82F6;">
                            <span class="material-symbols-rounded">groups</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d" style="border-top: 4px solid #F59E0B;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">OUTSTANDING</div>
                            <div class="sc-value">$<?= number_format($outstanding) ?></div>
                            <div class="sc-trend down"><span class="material-symbols-rounded">trending_up</span> +8.4% needs attention</div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#FEF3C7; display:flex; align-items:center; justify-content:center; color:#D97706;">
                            <span class="material-symbols-rounded">error</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Row -->
            <div class="dash-middle-row">
                <div class="panel-d">
                    <div class="panel-d-title">
                        Student Distribution by Subject
                        <span class="material-symbols-rounded" style="color:#94A3B8; cursor:pointer;">more_horiz</span>
                    </div>
                    <!-- Mock Bar Chart -->
                    <div style="height: 250px; display:flex; align-items:flex-end; gap:20px; padding-top:20px; border-left:1px solid #E2E8F0; border-bottom:1px solid #E2E8F0; position:relative;">
                        <div style="flex:1; background:#1D4ED8; height:75%; border-radius:4px 4px 0 0;"></div>
                        <div style="flex:1; background:#BFDBFE; height:55%; border-radius:4px 4px 0 0;"></div>
                        <div style="flex:1; background:#E2E8F0; height:35%; border-radius:4px 4px 0 0;"></div>
                        <div style="flex:1; background:#2563EB; height:85%; border-radius:4px 4px 0 0;"></div>
                        <div style="flex:1; background:#E2E8F0; height:25%; border-radius:4px 4px 0 0;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:12px; color:#64748B; font-size:12px; padding:0 20px;">
                        <span>Math</span>
                        <span>Science</span>
                        <span>English</span>
                        <span>Physics</span>
                        <span>Art</span>
                    </div>
                </div>

                <div class="panel-d">
                    <div class="panel-d-title">Monthly Attendance</div>
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height: 180px;">
                        <div style="width: 140px; height: 140px; border-radius: 50%; border: 16px solid #1D4ED8; border-right-color: #E2E8F0; display:flex; align-items:center; justify-content:center; transform: rotate(45deg);">
                            <div style="transform: rotate(-45deg); text-align:center;">
                                <div style="font-size:28px; font-weight:800; color:#0F172A;">85%</div>
                                <div style="font-size:10px; font-weight:700; color:#64748B; letter-spacing:1px;">AVG RATE</div>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:space-around; border-top:1px solid #E2E8F0; padding-top:16px; margin-top:24px;">
                        <div style="text-align:center;">
                            <div style="font-size:12px; color:#64748B; margin-bottom:4px;">Present</div>
                            <div style="font-size:18px; font-weight:700; color:#16A34A;">1,060</div>
                        </div>
                        <div style="width:1px; background:#E2E8F0;"></div>
                        <div style="text-align:center;">
                            <div style="font-size:12px; color:#64748B; margin-bottom:4px;">Absent</div>
                            <div style="font-size:18px; font-weight:700; color:#D97706;">188</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="dash-bottom-row">
                <div class="panel-d">
                    <div class="panel-d-title">
                        Pending Leave Requests
                        <a href="leave.php" style="font-size:14px; color:var(--primary); font-weight:600; text-decoration:none;">View All &rarr;</a>
                    </div>
                    
                    <?php if (empty($pending_leaves)): ?>
                        <div style="color:#64748B; padding:20px 0;">No pending requests.</div>
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
                    <div class="panel-d-title">Quick Actions</div>
                    <div class="qa-grid">
                        <a href="staff.php" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">person_add</span>
                            Add Teacher
                        </a>
                        <a href="schedule.php" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">event</span>
                            New Event
                        </a>
                        <a href="#" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">campaign</span>
                            Announcement
                        </a>
                        <a href="#" class="qa-btn" style="text-decoration:none;">
                            <span class="material-symbols-rounded">description</span>
                            Generate Report
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
