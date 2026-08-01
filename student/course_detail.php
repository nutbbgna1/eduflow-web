<?php
require_once '../config/db.php';

$student_id = $_SESSION['user_id'] ?? 2; // Default demo student
$subject_id = $_GET['id'] ?? null;

if (!$subject_id) {
    die("Invalid course ID");
}

// 1. Verify access
$stmt = $pdo->prepare("
    SELECT e.status, subj.name, subj.code 
    FROM enrollments e
    JOIN subjects subj ON e.subject_id = subj.id
    WHERE e.student_id = ? AND e.subject_id = ?
");
$stmt->execute([$student_id, $subject_id]);
$enrollment = $stmt->fetch();

$has_access = ($enrollment && $enrollment['status'] === 'active');
$course_name = $enrollment['name'] ?? 'Unknown Course';
$course_code = $enrollment['code'] ?? 'Unknown Code';

// 2. Fetch contents if has access
$videos = [];
$documents = [];

if ($has_access) {
    $stmt_content = $pdo->prepare("SELECT * FROM course_contents WHERE subject_id = ? ORDER BY order_num ASC");
    $stmt_content->execute([$subject_id]);
    $contents = $stmt_content->fetchAll();
    
    foreach ($contents as $c) {
        if ($c['content_type'] === 'video') {
            $videos[] = $c;
        } else {
            $documents[] = $c;
        }
    }
}

$page_title = 'EduFlow - ' . htmlspecialchars($course_name);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Course Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-link {
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 16px;
        }
        .course-title {
            font-size: 22px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .instructor-info {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .section-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
        }
        .count-pill {
            background: #E8F0FE;
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
        }
        .ep-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            gap: 16px;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        .ep-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #E8F0FE;
            color: var(--primary);
        }
        .ep-title {
            font-size: 14px;
            font-weight: 600;
            color: #0F172A;
            margin-top: 2px;
        }
        
        .material-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .mat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #FEE2E2; 
            color: #EF4444;
        }
        .mat-btn {
            background: #F8FAFC;
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            margin-left: auto;
        }
        .locked-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5">
            <a href="courses.php" class="back-link">
                <span class="material-symbols-rounded" style="font-size: 14px;">arrow_back_ios_new</span>
                Courses
            </a>
            
            <h1 class="course-title"><?= htmlspecialchars($course_name) ?></h1>
            <div class="instructor-info"><?= htmlspecialchars($course_code) ?></div>

            <?php if (!$has_access): ?>
                <div class="locked-state">
                    <span class="material-symbols-rounded" style="font-size: 64px; color: #CBD5E1; margin-bottom: 16px;">lock</span>
                    <h2 style="font-size: 18px; font-weight: 700; color: #0F172A; margin-bottom: 8px;">คอร์สเรียนนี้ถูกล็อค</h2>
                    <p style="font-size: 14px; color: var(--text-muted);">
                        <?= $enrollment ? 'สิทธิ์การเข้าเรียนของคุณหมดอายุ หรือถูกระงับ กรุณาติดต่อแอดมิน' : 'คุณยังไม่ได้สมัครเรียนคอร์สนี้' ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- Videos -->
                <div class="section-header">
                    <h2>Course Videos</h2>
                    <div class="count-pill"><?= count($videos) ?></div>
                </div>
                
                <?php if (empty($videos)): ?>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">ยังไม่มีวิดีโอสำหรับคอร์สนี้</p>
                <?php else: ?>
                    <?php foreach ($videos as $v): ?>
                        <a href="<?= htmlspecialchars($v['file_url']) ?>" target="_blank" class="ep-card">
                            <div class="ep-icon">
                                <span class="material-symbols-rounded">play_circle</span>
                            </div>
                            <div style="flex:1;">
                                <div class="ep-title"><?= htmlspecialchars($v['title']) ?></div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">คลิกเพื่อรับชมวิดีโอ</div>
                            </div>
                            <span class="material-symbols-rounded" style="color:#CBD5E1;">chevron_right</span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Materials -->
                <div class="section-header mt-5">
                    <h2>Course Materials</h2>
                    <div class="count-pill"><?= count($documents) ?></div>
                </div>
                
                <?php if (empty($documents)): ?>
                    <p style="font-size: 13px; color: var(--text-muted);">ยังไม่มีเอกสารสำหรับคอร์สนี้</p>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="material-card">
                            <div class="mat-icon">
                                <span class="material-symbols-rounded">picture_as_pdf</span>
                            </div>
                            <div>
                                <div class="font-bold text-sm" style="color: #0F172A;"><?= htmlspecialchars($doc['title']) ?></div>
                                <div class="text-xs text-muted mt-1">Uploaded <?= date('M d, Y', strtotime($doc['created_at'])) ?></div>
                            </div>
                            <a href="<?= htmlspecialchars($doc['file_url']) ?>" target="_blank" class="mat-btn">Download</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
