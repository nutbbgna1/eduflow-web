<?php
require_once '../includes/db.php';
require_login('admin');

header('Content-Type: application/json');

$parent_id = $_GET['parent_id'] ?? null;
if ($parent_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE parent_id = :parent_id ORDER BY name ASC");
    $stmt->execute(['parent_id' => $parent_id]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}
