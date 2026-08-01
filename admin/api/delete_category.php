<?php
require_once '../includes/db.php';
require_login('admin');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if category is used as a parent for another category
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$id]);
    $sub_count = $stmt->fetchColumn();

    // Check if category is used in subjects
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE category_id = ? OR subcategory_id = ?");
    $stmt2->execute([$id, $id]);
    $subj_count = $stmt2->fetchColumn();

    if ($sub_count > 0 || $subj_count > 0) {
        header("Location: ../categories.php?error=in_use");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: ../categories.php");
exit;
