<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? null;
    $content_type = $_POST['content_type'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $file_url = '';

    if (!$subject_id || !$content_type || !$title) {
        header("Location: ../course_content.php?id=$subject_id&error=missing_data");
        exit;
    }

    if ($content_type === 'video') {
        $file_url = trim($_POST['file_url'] ?? '');
        if (!$file_url) {
            header("Location: ../course_content.php?id=$subject_id&error=missing_url");
            exit;
        }
    } elseif ($content_type === 'document') {
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/materials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('sheet_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
                $file_url = 'uploads/materials/' . $filename; // Store relative to root
            } else {
                header("Location: ../course_content.php?id=$subject_id&error=upload_failed");
                exit;
            }
        } else {
            header("Location: ../course_content.php?id=$subject_id&error=no_file");
            exit;
        }
    }

    // Insert into db
    $stmt = $pdo->prepare("INSERT INTO course_contents (subject_id, title, content_type, file_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$subject_id, $title, $content_type, $file_url]);
    
    header("Location: ../course_content.php?id=$subject_id");
    exit;
}
header("Location: ../subjects.php");
exit;
