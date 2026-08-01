<?php
require_once 'includes/db.php';
require_login('admin');

// Determine active tab
$tab = $_GET['tab'] ?? 'unpaid';

// Counts for each tab
$counts = [];
foreach (['unpaid', 'pending_confirm', 'paid'] as $st) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = ?");
    $s->execute([$st]);
    $counts[$st] = $s->fetchColumn();
}

// Fetch payments based on tab
$stmt = $pdo->prepare("
    SELECT p.id, p.amount, p.status, p.due_date, p.paid_date, p.description,
           s.id as student_id, s.first_name as s_fname, s.last_name as s_lname, s.student_code,
           sub.name as subject_name, sub.code as subject_code
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE p.status = ?
    ORDER BY p.due_date ASC
");
$stmt->execute([$tab]);
$payments = $stmt->fetchAll();

// Fetch students and subjects for "Add Payment" modal
$all_students = $pdo->query("SELECT id, student_code, first_name, last_name FROM students ORDER BY first_name")->fetchAll();
$all_subjects = $pdo->query("SELECT id, code, name FROM subjects ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Enrollments & Payments</title>
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
            padding: 10px 20px;
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
        .tab-count {
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
        }
        .tab-count.red { background: #FEE2E2; color: #DC2626; }
        .tab-count.yellow { background: #FEF3C7; color: #D97706; }
        .tab-count.green { background: #D1FAE5; color: #059669; }
        .tab-count.gray { background: #F1F5F9; color: #64748B; }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-unpaid { background: #FEE2E2; color: #DC2626; }
        .status-pending_confirm { background: #FEF3C7; color: #B45309; }
        .status-paid { background: #D1FAE5; color: #065F46; }
        
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            padding: 32px;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-box h3 { margin: 0 0 24px; font-size: 18px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #475569; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15,77,217,0.1);
        }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        
        .overdue-text { font-size: 12px; color: #D97706; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 4px; }
        
        .empty-state { text-align: center; padding: 60px; color: var(--text-muted); }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #065F46;
            color: #fff;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 9999;
        }
        .toast.active { display: flex; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">

            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <p style="font-size:12px;font-weight:600;color:#64748B;margin-bottom:4px;text-transform:uppercase;display:flex;align-items:center;gap:4px;">
                        <span class="material-symbols-rounded" style="font-size:16px;">account_balance_wallet</span> Finance Operations
                    </p>
                    <h1 style="margin-bottom:0;">Enrollments &amp; Payments</h1>
                </div>
                <div>
                    <button onclick="openAddPaymentModal()" style="display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;background:#1D4ED8;color:#fff;border:none;font-weight:600;cursor:pointer;font-size:14px;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Payment Record
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['payment'])): ?>
            <div style="background:#D1FAE5;color:#065F46;padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-rounded">check_circle</span>
                <?= $_GET['payment'] === 'received' ? 'รับชำระเงินสำเร็จและลงทะเบียนนักเรียนเรียบร้อยแล้ว' : 'บันทึกข้อมูลสำเร็จ' ?>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs-row">
                <a href="?tab=unpaid" class="tab-btn <?= $tab === 'unpaid' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">info</span>
                    ยังไม่ได้ชำระ
                    <span class="tab-count red"><?= $counts['unpaid'] ?></span>
                </a>
                <a href="?tab=pending_confirm" class="tab-btn <?= $tab === 'pending_confirm' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">pending_actions</span>
                    รอยืนยัน
                    <span class="tab-count yellow"><?= $counts['pending_confirm'] ?></span>
                </a>
                <a href="?tab=paid" class="tab-btn <?= $tab === 'paid' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded" style="font-size:18px;">check_circle</span>
                    ชำระแล้ว
                    <span class="tab-count green"><?= $counts['paid'] ?></span>
                </a>
            </div>

            <div class="panel-d" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="font-size:16px; font-weight:700; color:#0F172A; margin:0;">
                        <?= $tab === 'unpaid' ? 'รายการที่ยังไม่ชำระ' : ($tab === 'pending_confirm' ? 'รายการรอยืนยัน' : 'รายการที่ชำระแล้ว') ?>
                        <span style="font-weight:400; color:var(--text-muted); font-size:14px;">(<?= count($payments) ?> รายการ)</span>
                    </h2>
                </div>

                <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.3;">receipt_long</span>
                    <p>ไม่มีรายการในหมวดนี้</p>
                </div>
                <?php else: ?>
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>รายวิชา</th>
                            <th>จำนวนเงิน</th>
                            <th>สถานะ / วันครบกำหนด</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <?php
                            $overdue_days = 0;
                            if ($p['status'] !== 'paid' && $p['due_date']) {
                                $diff = (new DateTime())->diff(new DateTime($p['due_date']));
                                $overdue_days = $p['due_date'] < date('Y-m-d') ? $diff->days : 0;
                            }
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:40px;height:40px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">
                                        <?= mb_strtoupper(mb_substr($p['s_fname'], 0, 1)) . mb_strtoupper(mb_substr($p['s_lname'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;color:#0F172A;"><?= htmlspecialchars($p['s_fname'] . ' ' . $p['s_lname']) ?></div>
                                        <div style="font-size:12px;color:#64748B;">รหัส: <?= htmlspecialchars($p['student_code']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:#334155;font-size:14px;"><?= htmlspecialchars($p['subject_name']) ?></div>
                                <div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($p['subject_code']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:#0F172A;font-size:15px;">฿<?= number_format($p['amount'], 2) ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $p['status'] ?>">
                                    <?= $p['status'] === 'unpaid' ? 'Unpaid' : ($p['status'] === 'pending_confirm' ? 'Pending' : 'Paid') ?>
                                </span>
                                <?php if ($overdue_days > 0): ?>
                                <div class="overdue-text">
                                    <span class="material-symbols-rounded" style="font-size:14px;">timer</span>
                                    เกินกำหนด <?= $overdue_days ?> วัน
                                </div>
                                <?php elseif ($p['due_date'] && $p['status'] !== 'paid'): ?>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">ครบกำหนด: <?= date('d M Y', strtotime($p['due_date'])) ?></div>
                                <?php elseif ($p['paid_date']): ?>
                                <div style="font-size:12px;color:#059669;margin-top:4px;">ชำระวันที่: <?= date('d M Y', strtotime($p['paid_date'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($p['status'] === 'unpaid' || $p['status'] === 'pending_confirm'): ?>
                                <form action="api/receive_payment.php" method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันรับชำระเงินจาก <?= htmlspecialchars($p['s_fname']) ?> ใช่ไหม?')">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button type="submit" style="padding:8px 16px;border-radius:6px;background:#2563EB;color:#fff;border:none;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                                        <span class="material-symbols-rounded" style="font-size:16px;">point_of_sale</span> รับชำระ
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color:#059669;font-size:13px;font-weight:600;display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                                    <span class="material-symbols-rounded" style="font-size:16px;">check_circle</span> ชำระแล้ว
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal-overlay" id="addPaymentModal">
        <div class="modal-box">
            <h3>เพิ่มรายการชำระเงิน</h3>
            <form action="api/add_payment.php" method="POST">
                <div class="form-group">
                    <label>นักเรียน</label>
                    <select name="student_id" required>
                        <option value="">-- เลือกนักเรียน --</option>
                        <?php foreach($all_students as $st): ?>
                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['student_code'] . ' — ' . $st['first_name'] . ' ' . $st['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>รายวิชา</label>
                    <select name="subject_id" required>
                        <option value="">-- เลือกวิชา --</option>
                        <?php foreach($all_subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['code'] . ' — ' . $sub['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>จำนวนเงิน (บาท)</label>
                    <input type="number" name="amount" min="0" step="0.01" placeholder="15000" required>
                </div>
                <div class="form-group">
                    <label>วันครบกำหนดชำระ</label>
                    <input type="date" name="due_date">
                </div>
                <div class="form-group">
                    <label>หมายเหตุ (ไม่บังคับ)</label>
                    <input type="text" name="description" placeholder="ค่าเรียนภาคเรียนที่ 1/2567">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddPaymentModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span class="material-symbols-rounded">check_circle</span>
        <span id="toast-msg">สำเร็จ!</span>
    </div>

    <script>
        function openAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.add('active');
        }
        function closeAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.remove('active');
        }
        // Close modal on overlay click
        document.getElementById('addPaymentModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddPaymentModal();
        });
    </script>
</body>
</html>
