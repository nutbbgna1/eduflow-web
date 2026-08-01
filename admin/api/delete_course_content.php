<?php
require_once '../includes/db.php';
require_login('admin');

if (isset($_GET['id']) && isset($_GET['subject_id'])) {
    $id = $_GET['id'];
    $subject_id = $_GET['subject_id'];
    
    // Get file info
    $stmt = $pdo->prepare("SELECT content_type, file_url FROM course_contents WHERE id = ?");
    $stmt->execute([$id]);
    $content = $stmt->fetch();

    if ($content) {
        // If it's a document, try to delete the physical file
        if ($content['content_type'] === 'document' && $content['file_url']) {
            $file_path = '../../' . $content['file_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete from db
        $stmt_del = $pdo->prepare("DELETE FROM course_contents WHERE id = ?");
        $stmt_del->execute([$id]);
    }
    
    header("Location: ../course_content.php?id=$subject_id");
    exit;
}
header("Location: ../subjects.php");
exit;
