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
    $is_online = isset($_POST['is_online']) ? 1 : 0;
    $is_onsite = isset($_POST['is_onsite']) ? 1 : 0;

    if ($code && $name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE subjects SET code = :code, name = :name, category_id = :category_id, subcategory_id = :subcategory_id, description = :desc, is_online = :is_online, is_onsite = :is_onsite WHERE id = :id");
            $stmt->execute(['code' => $code, 'name' => $name, 'category_id' => $category_id, 'subcategory_id' => $subcategory_id, 'desc' => $description, 'is_online' => $is_online, 'is_onsite' => $is_onsite, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (code, name, category_id, subcategory_id, description, is_online, is_onsite) VALUES (:code, :name, :category_id, :subcategory_id, :desc, :is_online, :is_onsite)");
            $stmt->execute(['code' => $code, 'name' => $name, 'category_id' => $category_id, 'subcategory_id' => $subcategory_id, 'desc' => $description, 'is_online' => $is_online, 'is_onsite' => $is_onsite]);
        }
    }
}
header("Location: ../subjects.php");
exit;
