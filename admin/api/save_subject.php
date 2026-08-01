<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $subcategory_id = !empty($_POST['subcategory_id']) ? $_POST['subcategory_id'] : null;
    $description = trim($_POST['description'] ?? '');

    if ($code && $name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE subjects SET code = :code, name = :name, category_id = :category_id, subcategory_id = :subcategory_id, description = :desc WHERE id = :id");
            $stmt->execute(['code' => $code, 'name' => $name, 'category_id' => $category_id, 'subcategory_id' => $subcategory_id, 'desc' => $description, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (code, name, category_id, subcategory_id, description) VALUES (:code, :name, :category_id, :subcategory_id, :desc)");
            $stmt->execute(['code' => $code, 'name' => $name, 'category_id' => $category_id, 'subcategory_id' => $subcategory_id, 'desc' => $description]);
        }
    }
}
header("Location: ../subjects.php");
exit;
