<?php
require_once '../config/db.php';
$page_title = 'My Courses';
$student_id = $_SESSION['user_id'] ?? 2; // Default to 2 for demo purposes

// Fetch active enrollments
$stmt = $pdo->prepare("
    SELECT subj.id as subject_id, subj.name as subject_name, subj.code as subject_code,
           c.name as category_name
    FROM enrollments e
    JOIN subjects subj ON e.subject_id = subj.id
    LEFT JOIN categories c ON subj.category_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
");
$stmt->execute([$student_id]);
$my_courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Courses</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .section-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-title a {
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .course-card {
            background: #fff;
            border-radius: 24px;
            padding: 12px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .course-img {
            height: 140px;
            border-radius: 16px;
            background-size: cover;
            background-position: center;
            position: relative;
            margin-bottom: 16px;
        }
        .category-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255,255,255,0.95);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5">
            <div class="section-title">
                <span>คอร์สเรียนของฉัน (My Courses)</span>
            </div>

            <?php if(empty($my_courses)): ?>
                <div style="text-align:center; padding:40px 0; color:var(--text-muted);">
                    <span class="material-symbols-rounded" style="font-size:48px; opacity:0.3; margin-bottom:12px; display:block;">auto_stories</span>
                    คุณยังไม่มีคอร์สเรียนที่สามารถเข้าเรียนได้
                </div>
            <?php else: ?>
                <?php foreach($my_courses as $c): ?>
                <a href="course_detail.php?id=<?= $c['subject_id'] ?>" class="course-card">
                    <div class="course-img" style="background-color: var(--primary-light);">
                        <?php if($c['category_name']): ?>
                        <div class="category-badge"><?= htmlspecialchars($c['category_name']) ?></div>
                        <?php endif; ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--primary);">
                            <span class="material-symbols-rounded" style="font-size:48px; opacity:0.5;">play_lesson</span>
                        </div>
                    </div>
                    <div class="px-2 pb-2">
                        <div class="font-bold text-base mb-1" style="color: #0F172A;"><?= htmlspecialchars($c['subject_name']) ?></div>
                        <div class="flex items-center gap-1 text-xs text-muted mb-2">
                            <span class="material-symbols-rounded" style="font-size:14px;">label</span>
                            <?= htmlspecialchars($c['subject_code']) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
