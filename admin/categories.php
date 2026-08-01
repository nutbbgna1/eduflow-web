<?php
$page_title = 'Categories Management';
require_once 'includes/db.php';
require_login('admin');

// Fetch all categories
$stmt = $pdo->query("
    SELECT c1.id, c1.name, c1.parent_id, c2.name as parent_name 
    FROM categories c1 
    LEFT JOIN categories c2 ON c1.parent_id = c2.id 
    ORDER BY c1.parent_id ASC, c1.name ASC
");
$categories = $stmt->fetchAll();

// Fetch only main categories for the dropdown
$stmt_main = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
$main_categories = $stmt_main->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Categories</title>
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
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,77,217,0.1); }
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
                    <h1>Categories (หมวดหมู่วิชา)</h1>
                    <p>จัดการหมวดหมู่วิชาและหมวดหมู่ย่อยสำหรับคอร์สเรียน</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openCategoryModal()" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;background:var(--primary);color:#fff;border:none;font-weight:600;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Category
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'in_use'): ?>
            <div style="background:#FEE2E2;color:#991B1B;padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-rounded">warning</span>
                ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากมีการใช้งานอยู่ หรือมีหมวดหมู่ย่อยอยู่
            </div>
            <?php endif; ?>

            <div class="card-desktop">
                <div style="overflow-x:auto;">
                    <table class="table-desktop">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CATEGORY NAME</th>
                                <th>TYPE (MAIN/SUB)</th>
                                <th>PARENT CATEGORY</th>
                                <th style="text-align:right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $cat): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $cat['id'] ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($cat['name']) ?></td>
                                <td>
                                    <?php if(empty($cat['parent_id'])): ?>
                                        <span style="background:var(--primary-light);color:var(--primary);padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">Main</span>
                                    <?php else: ?>
                                        <span style="background:#F1F5F9;color:#64748B;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;">Sub</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($cat['parent_name'] ?? '-') ?></td>
                                <td style="text-align:right;">
                                    <button onclick='editCategory(<?= json_encode($cat) ?>)' style="padding:6px 14px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;font-size:13px;margin-right:6px;">Edit</button>
                                    <a href="api/delete_category.php?id=<?= $cat['id'] ?>" style="padding:6px 14px;border:1px solid var(--danger);border-radius:6px;color:var(--danger);text-decoration:none;font-size:13px;" onclick="return confirm('ลบหมวดหมู่นี้ใช่ไหม?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($categories)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:60px;color:var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.4;">category</span>
                                    ยังไม่มีหมวดหมู่วิชา กด "Add Category" เพื่อเพิ่มใหม่
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal-box">
            <h3 id="modalTitle">Add New Category</h3>
            <form action="api/save_category.php" method="POST">
                <input type="hidden" name="id" id="cat_id">
                <div class="form-group">
                    <label>ชื่อหมวดหมู่</label>
                    <input type="text" name="name" id="cat_name" placeholder="เช่น คณิตศาสตร์" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่หลัก (ถ้าสร้างหมวดหมู่ย่อยให้เลือกอันนี้)</label>
                    <select name="parent_id" id="cat_parent">
                        <option value="">- ไม่มี (เป็นหมวดหมู่หลัก) -</option>
                        <?php foreach($main_categories as $mcat): ?>
                            <option value="<?= $mcat['id'] ?>"><?= htmlspecialchars($mcat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-weight:600;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-weight:600;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCategoryModal() {
            document.getElementById('modalTitle').innerText = 'Add New Category';
            document.getElementById('cat_id').value = '';
            document.getElementById('cat_name').value = '';
            document.getElementById('cat_parent').value = '';
            document.getElementById('categoryModal').classList.add('active');
        }
        function editCategory(c) {
            document.getElementById('modalTitle').innerText = 'Edit Category';
            document.getElementById('cat_id').value = c.id;
            document.getElementById('cat_name').value = c.name;
            document.getElementById('cat_parent').value = c.parent_id || '';
            document.getElementById('categoryModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }
    </script>
</body>
</html>
