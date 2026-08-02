<?php
require_once __DIR__ . '/../config/db.php';

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;
$schedule = null;
$enrollments = [];
$rfid_checkins = [];

if ($schedule_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, sub.name as subject_name, sub.code as subject_code
        FROM schedules s 
        JOIN subjects sub ON s.subject_id = sub.id 
        WHERE s.id = :id
    ");
    $stmt->execute(['id' => $schedule_id]);
    $schedule = $stmt->fetch();

    if ($schedule) {
        $stmt = $pdo->prepare("
            SELECT st.* 
            FROM students st
            JOIN enrollments e ON st.id = e.student_id
            WHERE e.subject_id = :sub_id
            ORDER BY st.first_name
        ");
        $stmt->execute(['sub_id' => $schedule['subject_id']]);
        $enrollments = $stmt->fetchAll();

        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT student_id FROM checkins WHERE DATE(checkin_time) = :today AND source = 'rfid'");
        $stmt->execute(['today' => $today]);
        $rfid_checkins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Fetch today's checkins for this teacher (for attendance history section)
$today_checkins = $pdo->prepare("
    SELECT * FROM checkins WHERE DATE(checkin_time) = CURDATE() ORDER BY checkin_time DESC LIMIT 5
");
$today_checkins->execute();
$checkin_history = $today_checkins->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - เช็คชื่อ</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== ATTENDANCE LAYOUT FIXES ===== */

        /* Clock display */
        .clock-display {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            letter-spacing: -2px;
            text-align: center;
            margin-bottom: 6px;
        }

        /* Map/checkin container - properly centered */
        .checkin-container {
            background: linear-gradient(160deg, #EBF4FF 0%, #F8FAFC 100%);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border: 1px solid #DBEAFE;
        }

        /* Big circle button - fixed sizing for symmetry */
        .btn-circle-large {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 0 0 12px rgba(37, 99, 235, 0.1), 0 0 0 24px rgba(37, 99, 235, 0.05);
            margin: 1.5rem auto;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            line-height: 1.3;
            gap: 4px;
        }
        .btn-circle-large:hover {
            transform: scale(1.04);
            box-shadow: 0 0 0 16px rgba(37, 99, 235, 0.15), 0 0 0 30px rgba(37, 99, 235, 0.07);
        }

        /* Location pill - symmetric */
        .location-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border-radius: 100px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            font-size: 12px;
            color: var(--text-muted);
        }
        .location-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            flex-shrink: 0;
        }

        /* Weekly stats grid - 3 equal columns */
        .weekly-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 1rem;
        }
        .weekly-stat-item {
            padding: 12px 8px;
            border-radius: 12px;
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--border);
        }
        .weekly-stat-item .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }
        .weekly-stat-item .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Progress bar */
        .progress-bar-wrapper {
            margin-top: 12px;
        }
        .progress-bar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .progress-bar-header span { font-size: 12px; color: var(--text-muted); }
        .progress-bar-header strong { font-size: 13px; font-weight: 700; color: var(--success); }
        .progress-bar-track {
            width: 100%;
            background-color: var(--border);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--success), #34D399);
            transition: width 0.6s ease;
        }

        /* History list item */
        .history-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        .history-item:last-child { border-bottom: none; }
        .history-item-left { display: flex; align-items: center; gap: 12px; }
        .history-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .history-title { font-size: 14px; font-weight: 600; color: var(--text-main); }
        .history-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .history-time { font-size: 14px; font-weight: 700; color: var(--text-main); white-space: nowrap; }

        /* Student list - symmetric layout */
        .student-row {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            gap: 12px;
        }
        .student-row:last-child { border-bottom: none; }
        .student-check { flex-shrink: 0; }
        .student-info { flex: 1; min-width: 0; }
        .student-name { font-size: 14px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .student-code { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .student-badge-area { flex-shrink: 0; }

        /* Section header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }
        .section-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="main-content px-6 py-2 animate-slide-up" id="main-content">
            <?php if ($schedule): ?>
            <!-- ===== CLASS CHECK-IN FLOW ===== -->
            <div class="mb-4 flex items-center gap-2">
                <a href="index.php" style="color:var(--primary);display:flex;align-items:center;">
                    <span class="material-symbols-rounded">arrow_back</span>
                </a>
                <h2 class="font-bold text-xl">เช็คชื่อ - <?= htmlspecialchars($schedule['subject_name']) ?></h2>
            </div>

            <form id="attendance-form" method="POST" action="api/save_attendance.php">
                <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                <input type="hidden" name="photo_data" id="photo_data" value="">

                <!-- Camera Section -->
                <div class="card mb-4 p-4">
                    <h3 class="font-bold text-md mb-2 flex items-center gap-1">
                        <span class="material-symbols-rounded text-primary">photo_camera</span> ถ่ายรูปห้องเรียน
                    </h3>
                    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">บังคับถ่ายรูปจากกล้องตามระเบียบ</p>
                    <div style="height:200px;background:#F1F5F9;border:1px solid var(--border);border-radius:12px;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;">
                        <video id="camera-feed" style="width:100%;height:100%;object-fit:cover;display:none;" autoplay playsinline></video>
                        <canvas id="photo-canvas" style="width:100%;height:100%;object-fit:cover;display:none;"></canvas>
                        <div id="camera-placeholder" style="text-align:center;color:var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.5;">no_photography</span>
                            <span style="font-size:13px;">กำลังเปิดกล้อง...</span>
                        </div>
                    </div>
                    <button type="button" id="btn-capture" class="btn btn-outline w-full mt-3 flex items-center justify-center gap-2" disabled>
                        <span class="material-symbols-rounded">camera</span> ถ่ายภาพ
                    </button>
                    <button type="button" id="btn-retake" class="btn btn-outline w-full mt-3 flex items-center justify-center gap-2" style="display:none;">
                        <span class="material-symbols-rounded">replay</span> ถ่ายใหม่
                    </button>
                </div>

                <!-- Student List Section -->
                <div class="card mb-4 p-0" style="overflow:hidden;">
                    <div style="padding:12px 16px;background:#F8FAFC;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                        <h3 class="font-bold text-md">รายชื่อนักเรียน (<?= count($enrollments) ?> คน)</h3>
                        <div style="font-size:11px;color:var(--success);font-weight:600;display:flex;align-items:center;gap:4px;">
                            <span class="material-symbols-rounded" style="font-size:14px;">rfid</span> RFID
                        </div>
                    </div>
                    <div id="student-list">
                        <?php foreach($enrollments as $student): ?>
                        <?php $has_rfid = in_array($student['id'], $rfid_checkins); ?>
                        <div class="student-row">
                            <div class="student-check">
                                <input type="checkbox"
                                    name="attendance[]"
                                    value="<?= $student['id'] ?>"
                                    id="chk_<?= $student['id'] ?>"
                                    <?= $has_rfid ? 'checked' : '' ?>
                                    style="width:20px;height:20px;accent-color:var(--primary);cursor:pointer;">
                            </div>
                            <div class="student-info">
                                <label for="chk_<?= $student['id'] ?>" class="student-name" style="cursor:pointer;">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </label>
                                <div class="student-code"><?= htmlspecialchars($student['student_code'] ?? '') ?></div>
                            </div>
                            <div class="student-badge-area">
                                <?php if($has_rfid): ?>
                                <span class="badge badge-success" style="font-size:10px;">RFID ✓</span>
                                <?php else: ?>
                                <span style="font-size:11px;color:var(--text-muted);">Manual</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($enrollments)): ?>
                        <div style="padding:30px;text-align:center;color:var(--text-muted);font-size:14px;">ไม่มีนักเรียนในวิชานี้</div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" id="btn-confirm-checkin" class="btn btn-primary w-full mb-8 text-lg py-3 shadow-md" style="opacity:0.5;" disabled>
                    ยืนยันการสอนและเช็คชื่อ
                </button>
            </form>

            <?php else: ?>
            <!-- ===== SCHOOL CHECK-IN PAGE ===== -->

            <!-- Time & Check-in -->
            <div class="checkin-container">
                <p style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">เช็คอินเข้างาน</p>
                <div class="clock-display" id="clock">--:--:--</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:0;" id="date-display"></p>

                <button class="btn-circle-large" onclick="doCheckin()">
                    <span class="material-symbols-rounded" style="font-size:2rem;">location_on</span>
                    ลงเวลาเข้า
                </button>

                <div class="location-pill">
                    <div class="location-dot"></div>
                    <span>พิกัดในรัศมี 50 เมตร</span>
                </div>
            </div>

            <!-- History Section -->
            <div class="section-header">
                <span class="material-symbols-rounded text-primary">history</span>
                <h3>ประวัติย้อนหลัง</h3>
            </div>

            <div class="card p-0 mb-4" style="overflow:hidden;">
                <?php if (!empty($checkin_history)): ?>
                    <?php foreach($checkin_history as $log): ?>
                    <div class="history-item">
                        <div class="history-item-left">
                            <div class="history-icon" style="background:var(--success-bg);color:var(--success);">
                                <span class="material-symbols-rounded">login</span>
                            </div>
                            <div>
                                <div class="history-title">ลงเวลาเข้างาน</div>
                                <div class="history-sub">วันนี้ • สถาบัน</div>
                            </div>
                        </div>
                        <div class="history-time"><?= date('H:i', strtotime($log['checkin_time'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="history-item" style="justify-content:center; padding: 24px; color: var(--text-muted);">
                        ไม่มีประวัติการลงเวลา
                    </div>
                <?php endif; ?>
            </div>


            <?php endif; ?>
        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>

    <script>
        // Live clock
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const el = document.getElementById('clock');
            if (el) el.textContent = `${h}:${m}:${s}`;

            const days = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
            const months = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
            const dateEl = document.getElementById('date-display');
            if (dateEl) {
                dateEl.textContent = `วัน${days[now.getDay()]}ที่ ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear() + 543}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        function doCheckin() {
            alert('บันทึกเวลาเข้างานแล้ว!');
        }

        // Camera logic (for class attendance)
        document.addEventListener('DOMContentLoaded', async () => {
            const video = document.getElementById('camera-feed');
            if (!video) return;

            const canvas = document.getElementById('photo-canvas');
            const placeholder = document.getElementById('camera-placeholder');
            const btnCapture = document.getElementById('btn-capture');
            const btnRetake = document.getElementById('btn-retake');
            const btnConfirm = document.getElementById('btn-confirm-checkin');
            const form = document.getElementById('attendance-form');
            const photoDataInput = document.getElementById('photo_data');
            let photoTaken = false;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
                video.srcObject = stream;
                video.style.display = 'block';
                placeholder.style.display = 'none';
                btnCapture.removeAttribute('disabled');
            } catch (err) {
                placeholder.innerHTML = '<span style="color:var(--danger);font-size:13px;">ไม่สามารถเข้าถึงกล้องได้</span>';
            }

            btnCapture.addEventListener('click', () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                video.style.display = 'none';
                canvas.style.display = 'block';
                btnCapture.style.display = 'none';
                btnRetake.style.display = 'flex';
                photoDataInput.value = canvas.toDataURL('image/jpeg');
                photoTaken = true;
                btnConfirm.style.opacity = '1';
                btnConfirm.removeAttribute('disabled');
            });

            btnRetake.addEventListener('click', () => {
                canvas.style.display = 'none';
                video.style.display = 'block';
                btnRetake.style.display = 'none';
                btnCapture.style.display = 'flex';
                photoDataInput.value = '';
                photoTaken = false;
                btnConfirm.style.opacity = '0.5';
                btnConfirm.setAttribute('disabled', 'disabled');
            });

            form.addEventListener('submit', (e) => {
                if (!photoTaken) {
                    e.preventDefault();
                    alert('กรุณาถ่ายรูปห้องเรียนก่อนยืนยัน');
                }
            });
        });
    </script>
</body>
</html>
