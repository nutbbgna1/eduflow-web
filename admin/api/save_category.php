<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

    if ($name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, parent_id = :parent_id WHERE id = :id");
            $stmt->execute(['name' => $name, 'parent_id' => $parent_id, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (:name, :parent_id)");
            $stmt->execute(['name' => $name, 'parent_id' => $parent_id]);
        }
    }
}
header("Location: ../categories.php");
exit;
