<?php
$page_title = 'Rooms Management';
require_once 'includes/db.php';
require_login('admin');

$stmt = $pdo->query("SELECT * FROM rooms ORDER BY name ASC");
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Rooms</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
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
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-box h3 { margin: 0 0 24px; font-size: 18px; color: #0f172a; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15,77,217,0.1);
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">

            <div class="page-header">
                <div class="page-title">
                    <h1>Rooms</h1>
                    <p>จัดการห้องเรียนและสถานที่สอนในสถาบัน</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openRoomModal()" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;background:var(--primary);color:#fff;border:none;font-weight:600;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Room
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'in_use'): ?>
            <div style="background:#FEE2E2;color:#991B1B;padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-rounded">warning</span>
                ไม่สามารถลบห้องเรียนนี้ได้ เนื่องจากมีตารางสอนที่อ้างอิงห้องนี้อยู่
            </div>
            <?php endif; ?>

            <div class="card-desktop">
                <div style="overflow-x:auto;">
                    <table class="table-desktop">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ROOM NAME</th>
                                <th>BUILDING</th>
                                <th>CAPACITY</th>
                                <th style="text-align:right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rooms as $room): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $room['id'] ?></td>
                                <td style="font-weight:600;">
                                    <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;color:var(--primary);margin-right:6px;">meeting_room</span>
                                    <?= htmlspecialchars($room['name']) ?>
                                </td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($room['building'] ?? '-') ?></td>
                                <td>
                                    <?php if($room['capacity'] > 0): ?>
                                    <span style="background:var(--primary-light);color:var(--primary);padding:3px 10px;border-radius:100px;font-size:12px;font-weight:600;"><?= $room['capacity'] ?> คน</span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <button onclick='editRoom(<?= json_encode($room) ?>)' style="padding:6px 14px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;font-size:13px;margin-right:6px;">Edit</button>
                                    <a href="api/delete_room.php?id=<?= $room['id'] ?>" style="padding:6px 14px;border:1px solid var(--danger);border-radius:6px;color:var(--danger);text-decoration:none;font-size:13px;" onclick="return confirm('ลบห้องเรียนนี้ใช่ไหม?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($rooms)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:60px;color:var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.4;">meeting_room</span>
                                    ยังไม่มีห้องเรียน กด "Add Room" เพื่อเพิ่มห้องใหม่
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Room Modal -->
    <div class="modal-overlay" id="roomModal">
        <div class="modal-box">
            <h3 id="roomModalTitle">Add New Room</h3>
            <form action="api/save_room.php" method="POST">
                <input type="hidden" name="id" id="room_id">
                <div class="form-group">
                    <label>ชื่อห้องเรียน (เช่น Room 401, Lab 1)</label>
                    <input type="text" name="name" id="room_name" placeholder="Room 401" required>
                </div>
                <div class="form-group">
                    <label>อาคาร / ตึก</label>
                    <input type="text" name="building" id="room_building" placeholder="Main Building">
                </div>
                <div class="form-group">
                    <label>ความจุ (จำนวนนักเรียนสูงสุด)</label>
                    <input type="number" name="capacity" id="room_capacity" min="0" placeholder="30">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRoomModal() {
            document.getElementById('roomModalTitle').innerText = 'Add New Room';
            document.getElementById('room_id').value = '';
            document.getElementById('room_name').value = '';
            document.getElementById('room_building').value = '';
            document.getElementById('room_capacity').value = '';
            document.getElementById('roomModal').classList.add('active');
        }
        function editRoom(r) {
            document.getElementById('roomModalTitle').innerText = 'Edit Room';
            document.getElementById('room_id').value = r.id;
            document.getElementById('room_name').value = r.name;
            document.getElementById('room_building').value = r.building || '';
            document.getElementById('room_capacity').value = r.capacity || '';
            document.getElementById('roomModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('roomModal').classList.remove('active');
        }
    </script>
</body>
</html>
