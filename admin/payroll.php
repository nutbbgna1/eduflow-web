<?php
require_once 'includes/db.php';
require_login('admin');

// Default to current month
$selected_month = $_GET['month'] ?? date('Y-m');

// Fetch all teachers with their teaching hours for selected month
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.hourly_rate,
        u.status,
        COALESCE(SUM(tl.hours), 0) as total_hours,
        COALESCE(SUM(tl.hours * u.hourly_rate), 0) as calculated_pay,
        pr.id as payroll_id,
        pr.bonus,
        pr.deduction,
        pr.net_pay,
        pr.status as payroll_status,
        pr.paid_date,
        pr.note
    FROM users u
    LEFT JOIN teaching_logs tl ON u.id = tl.actual_teacher_id 
        AND DATE_FORMAT(tl.log_date, '%Y-%m') = :month
    LEFT JOIN payroll pr ON u.id = pr.teacher_id AND pr.month = :month2
    WHERE u.role = 'teacher'
    GROUP BY u.id
    ORDER BY u.first_name
");
$stmt->execute(['month' => $selected_month, 'month2' => $selected_month]);
$teachers = $stmt->fetchAll();

// Summary stats
$total_teachers = count($teachers);
$total_payroll = array_sum(array_column($teachers, 'calculated_pay'));
$paid_count = count(array_filter($teachers, fn($t) => $t['payroll_status'] === 'paid'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Payroll</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .month-picker-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .month-picker-bar label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; }
        .month-picker-bar input[type="month"] {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        
        .stat-mini {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
        }
        .stat-mini .label { font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-mini .value { font-size: 26px; font-weight: 800; color: #0F172A; }
        .stat-mini .sub { font-size: 12px; color: #64748B; margin-top: 4px; }

        .payroll-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .ps-draft { background: #F1F5F9; color: #64748B; }
        .ps-approved { background: #FEF3C7; color: #B45309; }
        .ps-paid { background: #D1FAE5; color: #065F46; }
        .ps-none { background: #F1F5F9; color: #94A3B8; }

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
            max-width: 520px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-box h3 { margin: 0 0 8px; font-size: 18px; }
        .modal-box .sub-title { font-size: 13px; color: #64748B; margin-bottom: 24px; }
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        
        .net-pay-preview {
            background: var(--primary-light);
            border-radius: 10px;
            padding: 16px;
            margin-top: 8px;
            text-align: center;
        }
        .net-pay-preview .label { font-size: 12px; color: var(--primary); font-weight: 600; }
        .net-pay-preview .amount { font-size: 28px; font-weight: 800; color: var(--primary); }
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
                        <span class="material-symbols-rounded" style="font-size:16px;">account_balance_wallet</span> HR Operations
                    </p>
                    <h1 style="margin-bottom:0;">Payroll Management</h1>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div style="background:#D1FAE5;color:#065F46;padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-rounded">check_circle</span>
                บันทึกข้อมูล Payroll เรียบร้อยแล้ว
            </div>
            <?php endif; ?>

            <!-- Month Selector -->
            <div class="month-picker-bar">
                <span class="material-symbols-rounded" style="color:var(--primary);">calendar_month</span>
                <label>เดือนที่คำนวณ:</label>
                <form method="GET" style="display:flex;gap:10px;align-items:center;">
                    <input type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" onchange="this.form.submit()">
                </form>
                <span style="font-size:13px;color:#64748B;margin-left:8px;">
                    <?= date('F Y', strtotime($selected_month . '-01')) ?>
                </span>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-mini">
                    <div class="label">ครูทั้งหมด</div>
                    <div class="value"><?= $total_teachers ?> คน</div>
                    <div class="sub">จ่ายแล้ว <?= $paid_count ?> คน</div>
                </div>
                <div class="stat-mini">
                    <div class="label">ชั่วโมงสอนรวม</div>
                    <div class="value"><?= number_format(array_sum(array_column($teachers, 'total_hours')), 1) ?> ชม.</div>
                    <div class="sub">ในเดือน <?= date('M Y', strtotime($selected_month . '-01')) ?></div>
                </div>
                <div class="stat-mini">
                    <div class="label">ยอดเงินเดือนรวม</div>
                    <div class="value" style="color:var(--primary);">฿<?= number_format($total_payroll, 0) ?></div>
                    <div class="sub">คำนวณจากชั่วโมงสอน</div>
                </div>
            </div>

            <!-- Teacher Payroll Table -->
            <div class="panel-d" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; border-bottom:1px solid var(--border);">
                    <h2 style="font-size:16px; font-weight:700; color:#0F172A; margin:0;">รายละเอียดเงินเดือนครู</h2>
                </div>
                <table class="table-desktop">
                    <thead>
                        <tr>
                            <th>ครูผู้สอน</th>
                            <th>อัตราค่าจ้าง/ชม.</th>
                            <th>ชม. สอน</th>
                            <th>ยอดคำนวณ</th>
                            <th>สถานะ</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t): ?>
                        <?php
                            $net = $t['net_pay'] > 0 ? $t['net_pay'] : $t['calculated_pay'];
                            $ps_class = match($t['payroll_status'] ?? '') {
                                'draft'    => 'ps-draft',
                                'approved' => 'ps-approved',
                                'paid'     => 'ps-paid',
                                default    => 'ps-none',
                            };
                            $ps_label = match($t['payroll_status'] ?? '') {
                                'draft'    => 'Draft',
                                'approved' => 'Approved',
                                'paid'     => 'Paid',
                                default    => 'ยังไม่บันทึก',
                            };
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($t['first_name'].' '.$t['last_name']) ?>&background=random&color=fff" 
                                         style="width:38px;height:38px;border-radius:50%;object-fit:cover;" alt="">
                                    <div>
                                        <div style="font-weight:600;color:#0F172A;"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                                        <div style="font-size:12px;color:#64748B;"><?= htmlspecialchars($t['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:600;">฿<?= number_format($t['hourly_rate'], 0) ?>/ชม.</td>
                            <td>
                                <span style="font-weight:700;font-size:16px;color:#0F172A;"><?= number_format($t['total_hours'], 1) ?></span>
                                <span style="font-size:12px;color:#64748B;"> ชม.</span>
                            </td>
                            <td style="font-weight:700;color:var(--primary);">฿<?= number_format($t['calculated_pay'], 2) ?></td>
                            <td>
                                <span class="payroll-status <?= $ps_class ?>">
                                    <?= $ps_label ?>
                                </span>
                                <?php if ($t['paid_date']): ?>
                                <div style="font-size:11px;color:#059669;margin-top:4px;"><?= date('d M Y', strtotime($t['paid_date'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <button onclick='openPayrollModal(<?= json_encode([
                                    "id"         => $t["id"],
                                    "name"       => $t["first_name"] . " " . $t["last_name"],
                                    "hours"      => (float)$t["total_hours"],
                                    "rate"       => (float)$t["hourly_rate"],
                                    "calculated" => (float)$t["calculated_pay"],
                                    "payroll_id" => $t["payroll_id"],
                                    "bonus"      => (float)($t["bonus"] ?? 0),
                                    "deduction"  => (float)($t["deduction"] ?? 0),
                                    "note"       => $t["note"] ?? "",
                                    "status"     => $t["payroll_status"] ?? "draft",
                                ]) ?>)'
                                style="padding:8px 16px;border:1px solid var(--primary);border-radius:6px;background:#fff;color:var(--primary);cursor:pointer;font-size:13px;font-weight:600;">
                                    <?= $t['payroll_id'] ? 'แก้ไข' : 'บันทึก' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Payroll Modal -->
    <div class="modal-overlay" id="payrollModal">
        <div class="modal-box">
            <h3 id="pm_teacher_name">บันทึก Payroll</h3>
            <p class="sub-title" id="pm_month_label"></p>
            <form action="api/save_payroll.php" method="POST">
                <input type="hidden" name="teacher_id" id="pm_teacher_id">
                <input type="hidden" name="payroll_id" id="pm_payroll_id">
                <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
                <input type="hidden" name="total_hours" id="pm_total_hours">
                <input type="hidden" name="hourly_rate" id="pm_rate">

                <div class="form-row">
                    <div class="form-group">
                        <label>ชั่วโมงสอน</label>
                        <input type="number" id="pm_hours_display" step="0.5" min="0" readonly style="background:#F8FAFC;">
                    </div>
                    <div class="form-group">
                        <label>ยอดค่าสอน (฿)</label>
                        <input type="number" id="pm_base_display" step="0.01" min="0" readonly style="background:#F8FAFC;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>โบนัส / ค่าพิเศษ (฿)</label>
                        <input type="number" name="bonus" id="pm_bonus" step="0.01" min="0" value="0" oninput="updateNet()">
                    </div>
                    <div class="form-group">
                        <label>หักค่าใช้จ่าย (฿)</label>
                        <input type="number" name="deduction" id="pm_deduction" step="0.01" min="0" value="0" oninput="updateNet()">
                    </div>
                </div>
                <div class="net-pay-preview">
                    <div class="label">NET PAY</div>
                    <div class="amount" id="pm_net_display">฿0</div>
                    <input type="hidden" name="net_pay" id="pm_net">
                </div>
                <div class="form-group" style="margin-top:16px;">
                    <label>สถานะ</label>
                    <select name="status" id="pm_status">
                        <option value="draft">Draft (ร่าง)</option>
                        <option value="approved">Approved (อนุมัติ)</option>
                        <option value="paid">Paid (จ่ายแล้ว)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <input type="text" name="note" id="pm_note" placeholder="หมายเหตุเพิ่มเติม...">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closePayrollModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">บันทึก Payroll</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let basePayAmount = 0;

        function openPayrollModal(data) {
            basePayAmount = data.calculated;

            document.getElementById('pm_teacher_name').textContent = data.name;
            document.getElementById('pm_month_label').textContent = 'เดือน <?= date('F Y', strtotime($selected_month . '-01')) ?>';
            document.getElementById('pm_teacher_id').value = data.id;
            document.getElementById('pm_payroll_id').value = data.payroll_id || '';
            document.getElementById('pm_total_hours').value = data.hours;
            document.getElementById('pm_rate').value = data.rate;
            document.getElementById('pm_hours_display').value = data.hours;
            document.getElementById('pm_base_display').value = data.calculated.toFixed(2);
            document.getElementById('pm_bonus').value = data.bonus || 0;
            document.getElementById('pm_deduction').value = data.deduction || 0;
            document.getElementById('pm_note').value = data.note || '';
            
            // Set status
            const statusSel = document.getElementById('pm_status');
            statusSel.value = data.status || 'draft';

            updateNet();
            document.getElementById('payrollModal').classList.add('active');
        }

        function closePayrollModal() {
            document.getElementById('payrollModal').classList.remove('active');
        }

        function updateNet() {
            const bonus = parseFloat(document.getElementById('pm_bonus').value) || 0;
            const deduction = parseFloat(document.getElementById('pm_deduction').value) || 0;
            const net = basePayAmount + bonus - deduction;
            document.getElementById('pm_net_display').textContent = '฿' + net.toLocaleString('th-TH', {minimumFractionDigits: 2});
            document.getElementById('pm_net').value = net.toFixed(2);
        }

        document.getElementById('payrollModal').addEventListener('click', function(e) {
            if (e.target === this) closePayrollModal();
        });
    </script>
</body>
</html>
