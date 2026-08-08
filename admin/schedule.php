<?php
// admin/schedule.php
require_once 'includes/db.php';
require_login('admin');

// Determine which month to display
$current_month = date('Y-m');
$view_month = $_GET['month'] ?? $current_month;

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $view_month)) {
    $view_month = $current_month;
}

// Calculate prev/next months
$prev_month = date('Y-m', strtotime($view_month . '-01 -1 month'));
$next_month = date('Y-m', strtotime($view_month . '-01 +1 month'));

// Thai month names
$thai_months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม',
    '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
    '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน',
    '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$month_num = substr($view_month, 5, 2);
$year_num = substr($view_month, 0, 4);
$year_th = (int)$year_num + 543;
$display_month = $thai_months[$month_num] . ' ' . $year_th;
$display_month_en = date('F Y', strtotime($view_month . '-01'));

// Fetch schedules for this month
$stmt = $pdo->prepare("
    SELECT s.*, t.first_name, t.last_name, sub.name as subject_name, sub.code as subject_code
    FROM schedules s
    JOIN users t ON s.teacher_id = t.id
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.schedule_month = :month
");
$stmt->execute(['month' => $view_month]);
$schedules = $stmt->fetchAll();

// Get month status
$month_status = 'none'; // no schedules
if (!empty($schedules)) {
    $month_status = $schedules[0]['status']; // draft or published
}

// Check if previous month has schedules (for copy button)
$prev_check = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE schedule_month = ?");
$prev_check->execute([$prev_month]);
$prev_has_schedules = $prev_check->fetchColumn() > 0;

// Fetch teachers, subjects, and rooms for modal
$teachers = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'teacher' OR role = 'admin'")->fetchAll();
$subjects_list = $pdo->query("SELECT id, code, name, is_online, is_onsite FROM subjects ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$colors = ['blue', 'red', 'green', 'purple', 'orange', 'blue', 'red'];

$is_current = ($view_month === $current_month);
$is_future = ($view_month > $current_month);
$is_past = ($view_month < $current_month);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Schedule</title>
    <link rel="stylesheet" href="css/admin.css?v=3">
    <script>
        function handleSubjectChange() {
            const subjSelect = document.getElementById('subject_select');
            const roomSelect = document.getElementById('room_select');
            const selectedOption = subjSelect.options[subjSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.onsite === '0') {
                roomSelect.value = 'Online';
                roomSelect.disabled = true;
            } else {
                roomSelect.disabled = false;
                if (roomSelect.value === 'Online') roomSelect.value = '';
            }
        }
        
        function openScheduleModal() {
            document.getElementById('modalTitle').innerText = '<?= __('Add New Session') ?? 'Add New Session' ?>';
            document.getElementById('schedule_id').value = '';
            document.getElementById('deleteBtn').style.display = 'none';
            document.querySelector('[name="teacher_id"]').value = '';
            document.querySelector('[name="subject_id"]').value = '';
            document.querySelector('[name="room"]').value = '';
            document.querySelector('[name="day_of_week"]').value = 'Monday';
            document.querySelector('[name="start_time"]').value = '';
            document.querySelector('[name="end_time"]').value = '';
            document.getElementById('scheduleModal').classList.add('active');
        }
        
        function editSchedule(s) {
            document.getElementById('modalTitle').innerText = '<?= __('Edit Session') ?? 'Edit Session' ?>';
            document.getElementById('schedule_id').value = s.id;
            document.getElementById('deleteBtn').style.display = 'inline-block';
            document.getElementById('deleteBtn').href = 'api/delete_schedule.php?id=' + s.id + '&month=<?= $view_month ?>';
            
            document.querySelector('[name="teacher_id"]').value = s.teacher_id;
            document.querySelector('[name="subject_id"]').value = s.subject_id;
            document.querySelector('[name="room"]').value = s.room;
            document.querySelector('[name="day_of_week"]').value = s.day_of_week;
            document.querySelector('[name="start_time"]').value = s.start_time;
            document.querySelector('[name="end_time"]').value = s.end_time;
            
            handleSubjectChange();
            document.getElementById('scheduleModal').classList.add('active');
        }
    </script>
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
        .form-group select, .form-group input {
            width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; box-sizing: border-box;
        }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        
        .month-nav {
            display: flex; align-items: center; gap: 16px; padding: 16px 0;
        }
        .month-nav-btn {
            width: 40px; height: 40px; border-radius: 50%; border: 1px solid #E2E8F0;
            background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #475569; transition: all 0.2s;
        }
        .month-nav-btn:hover { background: #F1F5F9; border-color: #94A3B8; }
        .month-label {
            font-size: 20px; font-weight: 700; color: #0F172A; flex: 1; text-align: center;
        }
        .month-label small { display: block; font-size: 13px; font-weight: 400; color: #64748B; margin-top: 2px; }
        
        .status-badge {
            padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .status-badge.draft { background: #FEF3C7; color: #D97706; }
        .status-badge.published { background: #DCFCE7; color: #16A34A; }
        .status-badge.none { background: #F1F5F9; color: #94A3B8; }
        .status-badge.current { background: #DBEAFE; color: #2563EB; }
        
        .action-bar {
            display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
        }
        .action-bar .btn-action {
            display: flex; align-items: center; gap: 6px; padding: 8px 16px;
            border-radius: 8px; border: 1px solid #E2E8F0; background: #fff;
            font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; color: #475569;
            transition: all 0.2s;
        }
        .action-bar .btn-action:hover { background: #F8FAFC; border-color: #94A3B8; }
        .action-bar .btn-action.primary {
            background: #1D4ED8; color: #fff; border-color: #1D4ED8;
        }
        .action-bar .btn-action.primary:hover { background: #1E40AF; }
        .action-bar .btn-action.publish {
            background: #059669; color: #fff; border-color: #059669;
        }
        .action-bar .btn-action.publish:hover { background: #047857; }
        
        .alert-bar {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px; font-size: 14px;
        }
        .alert-bar.success { background: #DCFCE7; color: #166534; }
        .alert-bar.error { background: #FEE2E2; color: #991B1B; }
        .alert-bar.info { background: #DBEAFE; color: #1E40AF; }
    </style>
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <!-- Alerts -->
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'copied'): ?>
                    <div class="alert-bar success">
                        <span class="material-symbols-rounded">check_circle</span>
                        <?= __('Schedule copied successfully! All sessions are copied as drafts.') ?>
                    </div>
                <?php elseif ($_GET['success'] === 'published'): ?>
                    <div class="alert-bar success">
                        <span class="material-symbols-rounded">check_circle</span>
                        <?= __('Schedule published successfully. Teachers and students can now see it.') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'conflict'): ?>
                    <div class="alert-bar error">
                        <span class="material-symbols-rounded">warning</span>
                        <?= __('Cannot save! Conflict detected (Teacher or Room is already booked).') ?>
                    </div>
                <?php elseif ($_GET['error'] === 'exists'): ?>
                    <div class="alert-bar error">
                        <span class="material-symbols-rounded">warning</span>
                        <?= __('Target month already has a schedule. Cannot overwrite.') ?>
                    </div>
                <?php elseif ($_GET['error'] === 'in_use'): ?>
                    <div class="alert-bar error">
                        <span class="material-symbols-rounded">warning</span>
                        <?= __('Cannot delete this session because it is referenced by a teaching log or leave request.') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;"><?= __('Weekly Schedule') ?></h1>
                    <p><?= __('Manage classes for the current month') ?></p>
                </div>
            </div>
            
            <!-- Month Navigator -->
            <div style="background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px 24px; margin-bottom:20px;">
                <div class="month-nav">
                    <a href="?month=<?= $prev_month ?>" class="month-nav-btn">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </a>
                    <div class="month-label">
                        <?= $display_month ?>
                        <small><?= $display_month_en ?></small>
                    </div>
                    <a href="?month=<?= $next_month ?>" class="month-nav-btn">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </a>
                    
                    <?php if ($is_current): ?>
                        <span class="status-badge current"><?= __('Current Month') ?></span>
                    <?php elseif ($is_past): ?>
                        <span class="status-badge none"><?= __('Past Month') ?></span>
                    <?php endif; ?>
                    
                    <?php if ($month_status === 'draft'): ?>
                        <span class="status-badge draft"><?= __('Draft') ?></span>
                    <?php elseif ($month_status === 'published'): ?>
                        <span class="status-badge published"><?= __('Published') ?></span>
                    <?php else: ?>
                        <span class="status-badge none"><?= __('No Schedule') ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Action Bar -->
                <div class="action-bar" style="margin-top:12px; padding-top:12px; border-top:1px solid #F1F5F9;">
                    <button onclick="openScheduleModal()" class="btn-action primary">
                        <span class="material-symbols-rounded" style="font-size:18px;">add</span> <?= __('Add Session') ?>
                    </button>
                    
                    <?php if ($month_status === 'none' && $prev_has_schedules): ?>
                        <form action="api/copy_schedule_month.php" method="POST" style="display:inline;" onsubmit="return confirm('<?= __('Copy from previous month') ?> (<?= $prev_month ?>)?')">
                            <input type="hidden" name="source_month" value="<?= $prev_month ?>">
                            <input type="hidden" name="target_month" value="<?= $view_month ?>">
                            <button type="submit" class="btn-action">
                                <span class="material-symbols-rounded" style="font-size:18px;">content_copy</span> <?= __('Copy from previous month') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($month_status === 'draft'): ?>
                        <form action="api/publish_schedule.php" method="POST" style="display:inline;" onsubmit="return confirm('<?= __('Publish Schedule') ?>?')">
                            <input type="hidden" name="month" value="<?= $view_month ?>">
                            <button type="submit" class="btn-action publish">
                                <span class="material-symbols-rounded" style="font-size:18px;">publish</span> <?= __('Publish Schedule') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($view_month !== $current_month): ?>
                        <a href="?month=<?= $current_month ?>" class="btn-action">
                            <span class="material-symbols-rounded" style="font-size:18px;">today</span> <?= __('Back to Current Month') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-wrapper">
                <div class="calendar-header-row">
                    <div class="cal-head-cell" style="display:flex; align-items:center; justify-content:center; color:#94A3B8; font-size:11px;"><?= __('TIME') ?></div>
                    <div class="cal-head-cell"><?= __('Monday') ?></div>
                    <div class="cal-head-cell"><?= __('Tuesday') ?></div>
                    <div class="cal-head-cell"><?= __('Wednesday') ?></div>
                    <div class="cal-head-cell"><?= __('Thursday') ?></div>
                    <div class="cal-head-cell"><?= __('Friday') ?></div>
                    <div class="cal-head-cell"><?= __('Saturday') ?></div>
                    <div class="cal-head-cell"><?= __('Sunday') ?></div>
                </div>
                
                <div class="calendar-body">
                    <div class="time-col">
                        <div class="time-slot">08:00</div>
                        <div class="time-slot">09:00</div>
                        <div class="time-slot">10:00</div>
                        <div class="time-slot">11:00</div>
                        <div class="time-slot">12:00</div>
                        <div class="time-slot">13:00</div>
                        <div class="time-slot">14:00</div>
                        <div class="time-slot">15:00</div>
                        <div class="time-slot">16:00</div>
                        <div class="time-slot">17:00</div>
                        <div class="time-slot">18:00</div>
                        <div class="time-slot">19:00</div>
                        <div class="time-slot">20:00</div>
                        <div class="time-slot">21:00</div>
                    </div>
                    
                    <?php foreach($days as $idx => $day): ?>
                    <div class="day-col">
                        <?php 
                        foreach($schedules as $s) {
                            if ($s['day_of_week'] === $day) {
                                $start_parts = explode(':', $s['start_time']);
                                $end_parts = explode(':', $s['end_time']);
                                
                                $start_hour = (int)$start_parts[0];
                                $start_min = (int)$start_parts[1];
                                $end_hour = (int)$end_parts[0];
                                $end_min = (int)$end_parts[1];
                                
                                $top = (($start_hour - 8) * 80) + ($start_min / 60 * 80);
                                $duration_mins = (($end_hour * 60) + $end_min) - (($start_hour * 60) + $start_min);
                                $height = ($duration_mins / 60) * 80;
                                
                                $color = $colors[$s['id'] % count($colors)];
                                $is_draft = ($s['status'] === 'draft');
                                $draft_style = $is_draft ? 'opacity: 0.7; border-style: dashed;' : '';
                                
                                $json = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
                                echo "<div class='class-block $color' style='top: {$top}px; height: {$height}px; cursor: pointer; $draft_style' onclick='editSchedule($json)'>";
                                if ($is_draft) {
                                    echo "<div style='position:absolute; top:2px; right:4px; font-size:9px; background:rgba(0,0,0,0.15); padding:1px 6px; border-radius:4px;'>" . __('DRAFT') . "</div>";
                                }
                                echo "<div class='class-title'>" . htmlspecialchars($s['subject_name']) . "</div>";
                                echo "<div class='class-meta'>" . htmlspecialchars($s['first_name'].' '.$s['last_name']) . " &bull; " . htmlspecialchars($s['room']) . "<br>" . substr($s['start_time'],0,5) . " - " . substr($s['end_time'],0,5) . "</div>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Schedule Summary -->
            <?php if (!empty($schedules)): ?>
            <div style="background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px 24px; margin-top:20px;">
                <div style="font-weight:700; font-size:15px; color:#0F172A; margin-bottom:12px;">
                    <?= __('Schedule Summary for this month') ?> — <?= count($schedules) ?> <?= __('sessions') ?>
                </div>
                <div style="display:flex; gap:24px; flex-wrap:wrap;">
                    <?php
                    $by_day = [];
                    foreach ($schedules as $s) {
                        $by_day[$s['day_of_week']] = ($by_day[$s['day_of_week']] ?? 0) + 1;
                    }
                    foreach ($days as $d) {
                        $count = $by_day[$d] ?? 0;
                        if ($count > 0) {
                            $translated_day = __($d);
                            echo "<div style='font-size:13px; color:#475569;'><strong>$translated_day:</strong> $count " . __('sessions') . "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal-overlay" id="scheduleModal">
        <div class="modal-box">
            <h3 id="modalTitle" style="margin:0 0 24px; font-size:18px; color:#0f172a;"><?= __('Add New Session') ?></h3>
            <form action="api/save_schedule.php" method="POST">
                <input type="hidden" name="id" id="schedule_id">
                <input type="hidden" name="schedule_month" value="<?= $view_month ?>">
                <div class="form-group">
                    <label><?= __('Select Teacher') ?></label>
                    <select name="teacher_id" required>
                        <option value=""><?= __('-- Select Teacher --') ?></option>
                        <?php foreach($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'].' '.$t['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= __('Select Course') ?></label>
                    <select name="subject_id" id="subject_select" required onchange="handleSubjectChange()">
                        <option value=""><?= __('-- Select Course --') ?></option>
                        <?php foreach($subjects_list as $sub): ?>
                            <option value="<?= $sub['id'] ?>" data-onsite="<?= $sub['is_onsite'] ? '1' : '0' ?>">
                                <?= htmlspecialchars($sub['code'].' - '.$sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= __('Room') ?></label>
                    <select name="room" id="room_select" required>
                        <option value=""><?= __('-- Select Room --') ?></option>
                        <option value="Online" style="display:none;" id="online_room_option">Online</option>
                        <?php foreach($rooms as $r): ?>
                            <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= __('Day of Week') ?></label>
                    <select name="day_of_week" required>
                        <option value="Monday"><?= __('Monday') ?></option>
                        <option value="Tuesday"><?= __('Tuesday') ?></option>
                        <option value="Wednesday"><?= __('Wednesday') ?></option>
                        <option value="Thursday"><?= __('Thursday') ?></option>
                        <option value="Friday"><?= __('Friday') ?></option>
                        <option value="Saturday"><?= __('Saturday') ?></option>
                        <option value="Sunday"><?= __('Sunday') ?></option>
                    </select>
                </div>
                <div style="display:flex; gap:12px;">
                    <div class="form-group" style="flex:1;">
                        <label><?= __('Start Time') ?></label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label><?= __('End Time') ?></label>
                        <input type="time" name="end_time" required>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex; justify-content:space-between; gap:12px;">
                    <div>
                        <a href="#" id="deleteBtn" style="display:none; padding:10px 20px; border:1px solid var(--danger); border-radius:8px; background:#fff; color:var(--danger); cursor:pointer; font-weight:600; text-decoration:none;" onclick="return confirm('<?= __('Delete') ?>?')"><?= __('Delete') ?></a>
                    </div>
                    <div style="display:flex; gap:12px;">
                        <button type="button" onclick="document.getElementById('scheduleModal').classList.remove('active')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;"><?= __('Cancel') ?></button>
                        <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:#1D4ED8;color:#fff;cursor:pointer;font-weight:600;"><?= __('Save') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
