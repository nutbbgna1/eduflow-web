<?php
// admin/schedule.php
require_once 'includes/db.php';
require_login('admin');

// Fetch schedules
$stmt = $pdo->query("
    SELECT s.*, t.first_name, t.last_name, sub.name as subject_name
    FROM schedules s
    JOIN users t ON s.teacher_id = t.id
    JOIN subjects sub ON s.subject_id = sub.id
");
$schedules = $stmt->fetchAll();

// Fetch teachers, subjects, and rooms for modal
$teachers = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'teacher' OR role = 'admin'")->fetchAll();
$subjects = $pdo->query("SELECT id, code, name, is_online, is_onsite FROM subjects ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$colors = ['blue', 'red', 'green', 'purple', 'orange', 'blue', 'red'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Schedule</title>
    <link rel="stylesheet" href="css/admin.css">
    <script>
        function handleSubjectChange() {
            const subjSelect = document.getElementById('subject_select');
            const roomSelect = document.getElementById('room_select');
            
            const selectedOption = subjSelect.options[subjSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.onsite === '0') {
                // Online only course
                roomSelect.value = 'Online';
                roomSelect.disabled = true;
            } else {
                // Onsite or Hybrid course
                roomSelect.disabled = false;
                if (roomSelect.value === 'Online') {
                    roomSelect.value = '';
                }
            }
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
    </style>
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Weekly Schedule</h1>
                    <p>Manage classes for the current semester</p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <button onclick="document.getElementById('scheduleModal').classList.add('active')" class="btn btn-primary" style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; background:#1D4ED8; color:#fff; border:none; font-weight:600; cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Session
                    </button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-wrapper">
                <div class="calendar-header-row">
                    <div class="cal-head-cell" style="display:flex; align-items:center; justify-content:center; color:#94A3B8; font-size:11px;">TIME</div>
                    <div class="cal-head-cell">Monday</div>
                    <div class="cal-head-cell">Tuesday</div>
                    <div class="cal-head-cell">Wednesday</div>
                    <div class="cal-head-cell">Thursday</div>
                    <div class="cal-head-cell">Friday</div>
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
                                
                                // Calculate top position (08:00 = 0px)
                                $top = (($start_hour - 8) * 80) + ($start_min / 60 * 80);
                                
                                // Calculate height
                                $duration_mins = (($end_hour * 60) + $end_min) - (($start_hour * 60) + $start_min);
                                $height = ($duration_mins / 60) * 80;
                                
                                $color = $colors[$s['id'] % count($colors)];
                                
                                echo "<div class='class-block $color' style='top: {$top}px; height: {$height}px;'>";
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
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal-overlay" id="scheduleModal">
        <div class="modal-box">
            <h3 style="margin:0 0 24px; font-size:18px; color:#0f172a;">Add New Session</h3>
            <form action="api/save_schedule.php" method="POST">
                <div class="form-group">
                    <label>Select Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">-- เลือกครูผู้สอน --</option>
                        <?php foreach($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'].' '.$t['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Course</label>
                    <select name="subject_id" id="subject_select" required onchange="handleSubjectChange()">
                        <option value="">-- เลือกคอร์สเรียน --</option>
                        <?php foreach($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" data-onsite="<?= $sub['is_onsite'] ? '1' : '0' ?>">
                                <?= htmlspecialchars($sub['code'].' - '.$sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select name="room" id="room_select" required>
                        <option value="">-- เลือกห้องเรียน --</option>
                        <option value="Online" style="display:none;" id="online_room_option">Online</option>
                        <?php foreach($rooms as $r): ?>
                            <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Day of Week</label>
                    <select name="day_of_week" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div style="display:flex; gap:12px;">
                    <div class="form-group" style="flex:1;">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="document.getElementById('scheduleModal').classList.remove('active')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:#1D4ED8;color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
