<?php
require_once __DIR__ . '/../config/db.php';

// Fetch teacher's schedules for the dropdown (current month, published only)
$current_leave_month = date('Y-m');
$stmt = $pdo->prepare("
    SELECT s.*, sub.name as subject_name 
    FROM schedules s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.teacher_id = :tid AND s.schedule_month = :month AND s.status = 'published'
");
$stmt->execute(['tid' => $current_user_id, 'month' => $current_leave_month]);
$my_schedules = $stmt->fetchAll();

// Fetch other teachers for substitution
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'teacher' AND id != :tid");
$stmt->execute(['tid' => $current_user_id]);
$other_teachers = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'leave_request') {
    $leave_type = $_POST['leave_type'];
    $leave_date = $_POST['leave_date'];
    $schedule_id = $_POST['schedule_id'];
    $substitute_id = !empty($_POST['substitute_id']) ? $_POST['substitute_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (requester_id, substitute_id, schedule_id, leave_date, leave_type) 
        VALUES (:req_id, :sub_id, :sch_id, :ldate, :ltype)
    ");
    $stmt->execute([
        'req_id' => $current_user_id,
        'sub_id' => $substitute_id,
        'sch_id' => $schedule_id,
        'ldate' => $leave_date,
        'ltype' => $leave_type
    ]);
    
    echo "<script>alert('ยื่นคำร้องขอลางานสำเร็จ'); window.location.href='leave.php';</script>";
}

// Handle substitution response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'respond_sub') {
    $req_id = $_POST['request_id'];
    $response = $_POST['response']; // 'accepted' or 'rejected'
    
    $stmt = $pdo->prepare("UPDATE leave_requests SET substitute_status = :res WHERE id = :id AND substitute_id = :tid");
    $stmt->execute(['res' => $response, 'id' => $req_id, 'tid' => $current_user_id]);
    
    echo "<script>alert('บันทึกการตอบรับเรียบร้อย'); window.location.href='leave.php';</script>";
}

// Fetch my leave requests
$stmt = $pdo->prepare("
    SELECT lr.*, sub.name as subject_name, u.first_name as sub_fname, u.last_name as sub_lname
    FROM leave_requests lr
    JOIN schedules s ON lr.schedule_id = s.id
    JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN users u ON lr.substitute_id = u.id
    WHERE lr.requester_id = :tid
    ORDER BY lr.created_at DESC
");
$stmt->execute(['tid' => $current_user_id]);
$my_leaves = $stmt->fetchAll();

// Fetch requests asking me to substitute
$stmt = $pdo->prepare("
    SELECT lr.*, sub.name as subject_name, req.first_name as req_fname, req.last_name as req_lname,
           s.start_time, s.end_time
    FROM leave_requests lr
    JOIN schedules s ON lr.schedule_id = s.id
    JOIN subjects sub ON s.subject_id = sub.id
    JOIN users req ON lr.requester_id = req.id
    WHERE lr.substitute_id = :tid
    ORDER BY lr.created_at DESC
");
$stmt->execute(['tid' => $current_user_id]);
$sub_requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ลางานและสอนแทน</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
        
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-4 animate-slide-up">
            
            <h2 class="font-bold text-2xl mb-1">ลางาน / สอนแทน</h2>
            <p class="text-sm text-secondary mb-6">จัดการคำร้องขอลางานและคำขอสอนแทน</p>

            <!-- Tabs -->
            <div style="display: flex; border-bottom: 1px solid var(--border); margin-bottom: 24px; gap: 24px;">
                <button id="tab-my-leave" onclick="switchTab('my-leave')" style="padding-bottom: 8px; border: none; border-bottom: 2px solid var(--primary); background: transparent; font-weight: 600; color: var(--primary); cursor: pointer; font-size: 14px;">
                    คำร้องขอลางาน
                </button>
                <button id="tab-substitute" onclick="switchTab('substitute')" style="padding-bottom: 8px; border: none; border-bottom: 2px solid transparent; background: transparent; font-weight: 600; color: var(--secondary); cursor: pointer; font-size: 14px; display: flex; align-items: center;">
                    คำขอให้ฉันสอนแทน
                    <?php 
                        $pending_count = count(array_filter($sub_requests, fn($r) => $r['substitute_status'] == 'pending'));
                        if($pending_count > 0): 
                    ?>
                        <span class="badge badge-danger ml-1" style="border-radius: 50%; padding: 2px 6px;"><?= $pending_count ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Tab Content 1: My Leave -->
            <div id="content-my-leave">
                <form method="POST">
                    <input type="hidden" name="action" value="leave_request">
                    <div class="card p-5 mb-6" style="border-radius: var(--border-radius-lg);">
                        <h3 class="font-bold text-lg mb-4">ยื่นคำร้องขอลางาน</h3>

                        <div class="form-group mb-4">
                            <label class="form-label">ประเภทการลา <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-input" required style="padding-left: 1rem; appearance: none;">
                                <option value="">เลือกประเภทการลา</option>
                                <option value="personal">ลากิจ</option>
                                <option value="sick">ลาป่วย</option>
                                <option value="vacation">ลาพักผ่อน</option>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">วันที่ลา <span class="text-danger">*</span></label>
                            <input type="date" name="leave_date" class="form-input" required style="padding-left: 1rem;">
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">คาบที่ลา (อ้างอิงจากตาราง) <span class="text-danger">*</span></label>
                            <select name="schedule_id" class="form-input" required style="padding-left: 1rem; appearance: none;">
                                <option value="">เลือกคาบสอน</option>
                                <?php foreach($my_schedules as $sch): ?>
                                    <option value="<?= $sch['id'] ?>">
                                        <?= htmlspecialchars($sch['subject_name']) ?> (<?= $sch['day_of_week'] ?> <?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">ครูสอนแทน (ตัวเลือก)</label>
                            <select name="substitute_id" class="form-input" style="padding-left: 1rem; appearance: none;">
                                <option value="">ไม่มี (ให้ระบบจัดหา)</option>
                                <?php foreach($other_teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-full shadow-md py-3 text-lg">ยื่นคำร้อง</button>
                    </div>
                </form>

                <h3 class="font-bold text-lg mb-4">สถานะคำร้องของฉัน</h3>
                <?php foreach($my_leaves as $leave): ?>
                    <div class="card p-4 flex justify-between items-center mb-3 border-l-4" style="border-left-color: #F59E0B;">
                        <div>
                            <div class="font-semibold">ลา<?= $leave['leave_type'] === 'sick' ? 'ป่วย' : 'กิจ' ?> (<?= htmlspecialchars($leave['subject_name']) ?>)</div>
                            <div class="text-xs text-secondary">
                                <?= date('d M Y', strtotime($leave['leave_date'])) ?> • 
                                ครูสอนแทน: <?= $leave['substitute_id'] ? htmlspecialchars($leave['sub_fname']) : 'ไม่มี' ?>
                                (<?= ucfirst($leave['substitute_status']) ?>)
                            </div>
                        </div>
                        <span class="badge" style="background-color: #FEF3C7; color: #D97706;"><?= ucfirst($leave['status']) ?></span>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($my_leaves)): ?>
                    <div class="text-center p-4 text-secondary text-sm">ยังไม่มีคำร้องขอลางาน</div>
                <?php endif; ?>
            </div>

            <!-- Tab Content 2: Substitution Requests -->
            <div id="content-substitute" class="hidden">
                <h3 class="font-bold text-lg mb-4">คำขอให้ฉันสอนแทน</h3>
                
                <?php foreach($sub_requests as $req): ?>
                    <div class="card p-4 mb-4 <?= $req['substitute_status'] != 'pending' ? 'opacity-70' : '' ?>">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="font-semibold text-primary"><?= htmlspecialchars($req['subject_name']) ?></div>
                                <div class="text-xs text-secondary">วันที่: <?= date('d M Y', strtotime($req['leave_date'])) ?> • <?= date('H:i', strtotime($req['start_time'])) ?> - <?= date('H:i', strtotime($req['end_time'])) ?></div>
                                <div class="text-xs text-secondary mt-1">ผู้ขอ: <?= htmlspecialchars($req['req_fname'] . ' ' . $req['req_lname']) ?></div>
                            </div>
                            <?php if($req['substitute_status'] == 'accepted'): ?>
                                <span class="badge badge-success">รับสอนแล้ว</span>
                            <?php elseif($req['substitute_status'] == 'rejected'): ?>
                                <span class="badge badge-danger">ปฏิเสธแล้ว</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($req['substitute_status'] == 'pending'): ?>
                        <div class="flex gap-2">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="action" value="respond_sub">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="response" value="accepted">
                                <button type="submit" class="btn btn-primary w-full py-2 text-sm">รับสอนแทน</button>
                            </form>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="action" value="respond_sub">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="response" value="rejected">
                                <button type="submit" class="btn btn-outline w-full py-2 text-sm text-secondary">ปฏิเสธ</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($sub_requests)): ?>
                    <div class="text-center p-4 text-secondary text-sm">ยังไม่มีคำขอสอนแทน</div>
                <?php endif; ?>
            </div>

            <script>
                function switchTab(tabId) {
                    document.getElementById('content-my-leave').classList.add('hidden');
                    document.getElementById('content-substitute').classList.add('hidden');
                    
                    const tabMyLeave = document.getElementById('tab-my-leave');
                    const tabSubstitute = document.getElementById('tab-substitute');
                    
                    tabMyLeave.style.borderColor = "transparent";
                    tabMyLeave.style.color = "var(--secondary)";
                    
                    tabSubstitute.style.borderColor = "transparent";
                    tabSubstitute.style.color = "var(--secondary)";
                    
                    document.getElementById(`content-${tabId}`).classList.remove('hidden');
                    
                    const activeTab = document.getElementById(`tab-${tabId}`);
                    activeTab.style.borderColor = "var(--primary)";
                    activeTab.style.color = "var(--primary)";
                }
            </script>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
