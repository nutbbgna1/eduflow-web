<?php
$page_title = 'Manage Course Content';
require_once 'includes/db.php';
require_login('admin');

$subject_id = $_GET['id'] ?? null;
if (!$subject_id) {
    header("Location: subjects.php");
    exit;
}

// Get subject details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: subjects.php");
    exit;
}

// Get contents
$stmt_content = $pdo->prepare("SELECT * FROM course_contents WHERE subject_id = ? ORDER BY created_at ASC");
$stmt_content->execute([$subject_id]);
$contents = $stmt_content->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Manage Content: <?= htmlspecialchars($subject['name']) ?></title>
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
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #475569; }
        .form-group input {
            width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,77,217,0.1); }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">

            <div class="page-header">
                <div class="page-title">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <a href="subjects.php" style="color:var(--text-muted); text-decoration:none;"><span class="material-symbols-rounded">arrow_back</span></a>
                        <h1>Manage Content: <?= htmlspecialchars($subject['name']) ?></h1>
                    </div>
                    <p>จัดการเนื้อหาวิดีโอและชีทเรียนของคอร์สเรียนนี้</p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn" onclick="openVideoModal()" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;background:#EF4444;color:#fff;border:none;font-weight:600;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">play_circle</span> Add Video Link
                    </button>
                    <button class="btn" onclick="openDocModal()" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;background:#10B981;color:#fff;border:none;font-weight:600;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">description</span> Upload Sheet
                    </button>
                </div>
            </div>

            <div class="card-desktop">
                <div style="overflow-x:auto;">
                    <table class="table-desktop">
                        <thead>
                            <tr>
                                <th style="width:50px;">TYPE</th>
                                <th>TITLE</th>
                                <th>URL / FILE</th>
                                <th>ADDED ON</th>
                                <th style="text-align:right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($contents as $c): ?>
                            <tr>
                                <td>
                                    <?php if($c['content_type'] == 'video'): ?>
                                        <span class="material-symbols-rounded" style="color:#EF4444;">play_circle</span>
                                    <?php else: ?>
                                        <span class="material-symbols-rounded" style="color:#10B981;">description</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:600;"><?= htmlspecialchars($c['title']) ?></td>
                                <td>
                                    <?php if($c['content_type'] == 'video'): ?>
                                        <a href="<?= htmlspecialchars($c['file_url']) ?>" target="_blank" style="color:var(--primary); text-decoration:none; font-size:13px;">Watch Video &nearr;</a>
                                    <?php else: ?>
                                        <a href="../<?= htmlspecialchars($c['file_url']) ?>" target="_blank" style="color:var(--primary); text-decoration:none; font-size:13px;">View File &nearr;</a>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--text-muted); font-size:13px;"><?= date('Y-m-d H:i', strtotime($c['created_at'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="api/delete_course_content.php?id=<?= $c['id'] ?>&subject_id=<?= $subject_id ?>" style="padding:6px 14px;border:1px solid var(--danger);border-radius:6px;color:var(--danger);text-decoration:none;font-size:13px;" onclick="return confirm('ยืนยันการลบเนื้อหานี้ใช่ไหม?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($contents)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:60px;color:var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.4;">inventory_2</span>
                                    ยังไม่มีเนื้อหาสำหรับคอร์สนี้
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-box">
            <h3 id="modalTitle">Add Video Link</h3>
            <form action="api/save_course_content.php" method="POST">
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                <input type="hidden" name="content_type" value="video">
                <div class="form-group">
                    <label>ชื่อวิดีโอ (Title)</label>
                    <input type="text" name="title" placeholder="เช่น EP.1 บทนำ" required>
                </div>
                <div class="form-group">
                    <label>ลิงก์วิดีโอ (URL เช่น YouTube)</label>
                    <input type="url" name="file_url" placeholder="https://youtube.com/..." required>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('videoModal')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">เพิ่มวิดีโอ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Document Modal -->
    <div class="modal-overlay" id="docModal">
        <div class="modal-box">
            <h3 id="modalTitle">Upload Sheet (PDF, Image, Word)</h3>
            <form action="api/save_course_content.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                <input type="hidden" name="content_type" value="document">
                <div class="form-group">
                    <label>ชื่อเอกสาร (Title)</label>
                    <input type="text" name="title" placeholder="เช่น Sheet ประกอบการเรียน EP.1" required>
                </div>
                <div class="form-group">
                    <label>ไฟล์เอกสาร</label>
                    <input type="file" name="file_upload" required style="padding:6px;">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('docModal')" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">อัปโหลด</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVideoModal() {
            document.getElementById('videoModal').classList.add('active');
        }
        function openDocModal() {
            document.getElementById('docModal').classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
    </script>
</body>
</html>
