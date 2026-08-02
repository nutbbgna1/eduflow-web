<?php
$page_title = 'Courses Management';
require_once 'includes/db.php';
require_login('admin');

$cat_filter = $_GET['cat'] ?? '';
$subcat_filter = $_GET['subcat'] ?? '';

$where = [];
$params = [];
if ($cat_filter) {
    $where[] = "s.category_id = :cat";
    $params['cat'] = $cat_filter;
}
if ($subcat_filter) {
    $where[] = "s.subcategory_id = :subcat";
    $params['subcat'] = $subcat_filter;
}

$where_clause = '';
if (!empty($where)) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Join with categories to get names
$stmt = $pdo->prepare("
    SELECT s.*, c1.name as category_name, c2.name as subcategory_name 
    FROM subjects s 
    LEFT JOIN categories c1 ON s.category_id = c1.id 
    LEFT JOIN categories c2 ON s.subcategory_id = c2.id 
    $where_clause 
    ORDER BY s.name ASC
");
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Get main categories for filter and form
$stmt_cats = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
$main_categories = $stmt_cats->fetchAll();

// Get subcategories if main category is selected for filter
$subcategories = [];
if ($cat_filter) {
    $stmt_sub = $pdo->prepare("SELECT * FROM categories WHERE parent_id = :cat ORDER BY name ASC");
    $stmt_sub->execute(['cat' => $cat_filter]);
    $subcategories = $stmt_sub->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Courses</title>
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
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,77,217,0.1); }
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
                    <h1>Courses (คอร์สเรียน)</h1>
                    <p>จัดการคอร์สเรียนที่เปิดสอนในสถาบัน</p>
                </div>
                <div style="display:flex; gap: 12px; flex-wrap:wrap; justify-content:flex-end;">
                    <form method="GET" action="subjects.php" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <select name="cat" onchange="this.form.submit()" style="padding:10px 14px; border:1px solid #E2E8F0; border-radius:8px; outline:none; cursor:pointer; font-family:inherit; color:#475569;">
                            <option value="">ทุกหมวดหมู่หลัก (All)</option>
                            <?php foreach($main_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($cat_filter && count($subcategories) > 0): ?>
                        <select name="subcat" onchange="this.form.submit()" style="padding:10px 14px; border:1px solid #E2E8F0; border-radius:8px; outline:none; cursor:pointer; font-family:inherit; color:#475569;">
                            <option value="">ทุกหมวดหมู่ย่อย (All)</option>
                            <?php foreach($subcategories as $scat): ?>
                            <option value="<?= $scat['id'] ?>" <?= $subcat_filter == $scat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($scat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </form>
                    <button class="btn btn-primary" onclick="openSubjectModal()" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;background:var(--primary);color:#fff;border:none;font-weight:600;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Course
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'in_use'): ?>
            <div style="background:#FEE2E2;color:#991B1B;padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-rounded">warning</span>
                ไม่สามารถลบรายวิชานี้ได้ เนื่องจากมีตารางสอนหรือการลงทะเบียนที่อ้างอิงอยู่
            </div>
            <?php endif; ?>

            <div class="card-desktop">
                <div style="overflow-x:auto;">
                    <table class="table-desktop">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CODE</th>
                                <th>COURSE NAME</th>
                                <th>CATEGORY (หมวดหมู่)</th>
                                <th>DESCRIPTION</th>
                                <th style="text-align:right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subjects as $sub): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $sub['id'] ?></td>
                                <td><span style="background:var(--primary-light);color:var(--primary);padding:4px 10px;border-radius:6px;font-weight:700;font-size:12px;"><?= htmlspecialchars($sub['code']) ?></span></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($sub['name']) ?></td>
                                <td>
                                    <?php if(!empty($sub['category_name'])): ?>
                                        <span style="background:#F1F5F9;color:#475569;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                                            <?= htmlspecialchars($sub['category_name']) ?>
                                            <?= !empty($sub['subcategory_name']) ? ' &gt; ' . htmlspecialchars($sub['subcategory_name']) : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#94A3B8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($sub['description'] ?? '-') ?></td>
                                <td style="text-align:right;">
                                    <a href="student_enrollments.php?subject_id=<?= $sub['id'] ?>" style="padding:6px 14px;border:1px solid #10B981;border-radius:6px;color:#10B981;text-decoration:none;font-size:13px;margin-right:6px;">Enroll Students</a>
                                    <a href="course_content.php?id=<?= $sub['id'] ?>" style="padding:6px 14px;border:1px solid var(--primary);border-radius:6px;color:var(--primary);text-decoration:none;font-size:13px;margin-right:6px;">Manage Content</a>
                                    <button onclick='editSubject(<?= json_encode($sub) ?>)' style="padding:6px 14px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;font-size:13px;margin-right:6px;">Edit</button>
                                    <a href="api/delete_subject.php?id=<?= $sub['id'] ?>" style="padding:6px 14px;border:1px solid var(--danger);border-radius:6px;color:var(--danger);text-decoration:none;font-size:13px;" onclick="return confirm('ลบรายวิชานี้ใช่ไหม?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($subjects)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:60px;color:var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.4;">book</span>
                                    ยังไม่มีคอร์สเรียน กด "Add Course" เพื่อเพิ่มคอร์สใหม่
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Subject Modal -->
    <div class="modal-overlay" id="subjectModal">
        <div class="modal-box">
            <h3 id="modalTitle">Add New Course</h3>
            <form action="api/save_subject.php" method="POST">
                <input type="hidden" name="id" id="subject_id">
                <div class="form-group">
                    <label>รหัสคอร์ส (เช่น PHY101)</label>
                    <input type="text" name="code" id="subject_code" placeholder="PHY101" required>
                </div>
                <div class="form-group">
                    <label>ชื่อคอร์สเรียน</label>
                    <input type="text" name="name" id="subject_name" placeholder="ฟิสิกส์ ม.5/1" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่หลัก (Category)</label>
                    <select name="category_id" id="subject_category" onchange="loadSubcategories(this.value)">
                        <option value="">- ไม่มี -</option>
                        <?php foreach($main_categories as $mcat): ?>
                            <option value="<?= $mcat['id'] ?>"><?= htmlspecialchars($mcat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="subcat_group" style="display:none;">
                    <label>หมวดหมู่ย่อย (Sub Category)</label>
                    <select name="subcategory_id" id="subject_subcategory">
                        <option value="">- ไม่มี -</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>คำอธิบาย (ไม่บังคับ)</label>
                    <textarea name="description" id="subject_desc" rows="3" placeholder="รายละเอียดคอร์ส..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function loadSubcategories(parentId, selectedSubcat = null) {
            const subcatGroup = document.getElementById('subcat_group');
            const subcatSelect = document.getElementById('subject_subcategory');
            
            subcatSelect.innerHTML = '<option value="">- ไม่มี -</option>';
            
            if (!parentId) {
                subcatGroup.style.display = 'none';
                return;
            }

            fetch(`api/get_subcategories.php?parent_id=${parentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        subcatGroup.style.display = 'block';
                        data.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.name;
                            if (selectedSubcat && sub.id == selectedSubcat) {
                                option.selected = true;
                            }
                            subcatSelect.appendChild(option);
                        });
                    } else {
                        subcatGroup.style.display = 'none';
                    }
                })
                .catch(err => console.error('Error fetching subcategories:', err));
        }

        function openSubjectModal() {
            document.getElementById('modalTitle').innerText = 'Add New Course';
            document.getElementById('subject_id').value = '';
            document.getElementById('subject_code').value = '';
            document.getElementById('subject_name').value = '';
            document.getElementById('subject_category').value = '';
            document.getElementById('subcat_group').style.display = 'none';
            document.getElementById('subject_subcategory').innerHTML = '<option value="">- ไม่มี -</option>';
            document.getElementById('subject_desc').value = '';
            document.getElementById('subjectModal').classList.add('active');
        }
        function editSubject(s) {
            document.getElementById('modalTitle').innerText = 'Edit Course';
            document.getElementById('subject_id').value = s.id;
            document.getElementById('subject_code').value = s.code;
            document.getElementById('subject_name').value = s.name;
            document.getElementById('subject_category').value = s.category_id || '';
            document.getElementById('subject_desc').value = s.description || '';
            
            if (s.category_id) {
                loadSubcategories(s.category_id, s.subcategory_id);
            } else {
                document.getElementById('subcat_group').style.display = 'none';
                document.getElementById('subject_subcategory').innerHTML = '<option value="">- ไม่มี -</option>';
            }

            document.getElementById('subjectModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('subjectModal').classList.remove('active');
        }
    </script>
</body>
</html>
