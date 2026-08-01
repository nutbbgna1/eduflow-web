<?php
// admin/leave.php
require_once 'includes/db.php';

// Handle Action
$done_msg = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $done_msg = "อนุมัติคำร้องเรียบร้อย";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $done_msg = "ปฏิเสธคำร้องเรียบร้อย";
    }
}

// Stats
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM leave_requests GROUP BY status");
$counts_data = $stmt->fetchAll();
$counts = ['pending'=>0, 'approved'=>0, 'rejected'=>0];
foreach ($counts_data as $row) {
    $counts[$row['status']] = $row['cnt'];
}
$pending_count = $counts['pending'];

// Filter
$filter = $_GET['filter'] ?? 'pending';
$where = "lr.status = 'pending'";
if ($filter === 'approved') $where = "lr.status = 'approved'";
if ($filter === 'rejected') $where = "lr.status = 'rejected'";
if ($filter === 'all') $where = "1";

$stmt = $pdo->query("
    SELECT lr.*, 
           u.first_name as req_fname, u.last_name as req_lname, u.username as req_email,
           sub.first_name as sub_fname, sub.last_name as sub_lname,
           s.room, s.start_time, s.end_time, s.day_of_week,
           subj.name as subject_name
    FROM leave_requests lr
    JOIN users u ON lr.requester_id = u.id
    LEFT JOIN users sub ON lr.substitute_id = sub.id
    JOIN schedules s ON lr.schedule_id = s.id
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE $where
    ORDER BY lr.created_at DESC
");
$leaves = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Leave</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabs-row {
            display: flex;
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 8px;
            gap: 8px;
            margin-bottom: 24px;
        }
        .tab-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #64748B;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .tab-btn.active {
            background: #EFF6FF;
            color: #1D4ED8;
        }
        .badge {
            background: #FEE2E2;
            color: #DC2626;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .badge.gray { background: #F1F5F9; color: #64748B; }
        
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #10B981;
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            opacity: 0;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Leave Requests</h1>
                    <p>Manage and approve staff leave requests.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; margin-bottom:24px;">
                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">PENDING REQUESTS</div>
                            <div class="sc-value"><?= $pending_count ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#FEF3C7; display:flex; align-items:center; justify-content:center; color:#D97706;">
                            <span class="material-symbols-rounded">pending_actions</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">APPROVED</div>
                            <div class="sc-value"><?= $counts['approved'] ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#DCFCE7; display:flex; align-items:center; justify-content:center; color:#16A34A;">
                            <span class="material-symbols-rounded">check_circle</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card-d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="sc-title">REJECTED</div>
                            <div class="sc-value"><?= $counts['rejected'] ?></div>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:#FEE2E2; display:flex; align-items:center; justify-content:center; color:#DC2626;">
                            <span class="material-symbols-rounded">cancel</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-row">
                <a href="?filter=pending" class="tab-btn <?= $filter==='pending' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">pending_actions</span>
                    Pending
                    <?php if($pending_count > 0): ?><span class="badge" style="background:#FEE2E2; color:#DC2626;"><?= $pending_count ?></span><?php endif; ?>
                </a>
                <a href="?filter=approved" class="tab-btn <?= $filter==='approved' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">check_circle</span>
                    Approved
                </a>
                <a href="?filter=rejected" class="tab-btn <?= $filter==='rejected' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">cancel</span>
                    Rejected
                </a>
                <a href="?filter=all" class="tab-btn <?= $filter==='all' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">list</span>
                    All Requests
                </a>
            </div>

            <div class="panel-d" style="padding:0; overflow:hidden;">
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>TEACHER</th>
                            <th>LEAVE DETAILS</th>
                            <th>CLASS / SUBSTITUTE</th>
                            <th>STATUS</th>
                            <th style="text-align:right;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($leaves)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:32px; color:#64748B;">No requests found.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($leaves as $leave): 
                            $initials = getInitials($leave['req_fname'], $leave['req_lname']);
                            $color = getAvatarColor($leave['req_fname']);
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:40px; height:40px; border-radius:50%; background:<?= $color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px;">
                                        <?= $initials ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:#0F172A;"><?= htmlspecialchars($leave['req_fname'].' '.$leave['req_lname']) ?></div>
                                        <div style="font-size:12px; color:#64748B;"><?= htmlspecialchars($leave['req_email']) ?>@eduflow.edu</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#334155; font-size:14px; text-transform:capitalize;"><?= htmlspecialchars($leave['leave_type']) ?> Leave</div>
                                <div style="font-size:12px; color:#64748B;"><?= date('d M Y', strtotime($leave['leave_date'])) ?></div>
                                <?php if($leave['reason']): ?>
                                <div style="font-size:11px; color:#94A3B8; margin-top:2px;">"<?= htmlspecialchars($leave['reason']) ?>"</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#334155; font-size:14px;"><?= htmlspecialchars($leave['subject_name']) ?></div>
                                <div style="font-size:12px; color:#64748B;">
                                    Sub: <?= $leave['sub_fname'] ? htmlspecialchars($leave['sub_fname'].' '.$leave['sub_lname']) : 'None' ?>
                                </div>
                            </td>
                            <td>
                                <?php if($leave['status'] === 'pending'): ?>
                                    <span style="background:#FEF3C7; color:#D97706; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;">Pending</span>
                                <?php elseif($leave['status'] === 'approved'): ?>
                                    <span style="background:#DCFCE7; color:#16A34A; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;">Approved</span>
                                <?php else: ?>
                                    <span style="background:#FEE2E2; color:#DC2626; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if($leave['status'] === 'pending'): ?>
                                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                                        <a href="?action=approve&id=<?= $leave['id'] ?>&filter=<?= $filter ?>" class="btn btn-outline" style="padding:6px; border-radius:6px; border:1px solid #16A34A; color:#16A34A; text-decoration:none;" title="Approve">
                                            <span class="material-symbols-rounded">check</span>
                                        </a>
                                        <a href="?action=reject&id=<?= $leave['id'] ?>&filter=<?= $filter ?>" class="btn btn-outline" style="padding:6px; border-radius:6px; border:1px solid #DC2626; color:#DC2626; text-decoration:none;" title="Reject">
                                            <span class="material-symbols-rounded">close</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php if ($done_msg): ?>
    <div id="toast" class="toast show"><?= $done_msg ?></div>
    <script>setTimeout(() => document.getElementById('toast').classList.remove('show'), 2500);</script>
    <?php endif; ?>
</body>
</html>
