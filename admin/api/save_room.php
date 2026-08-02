<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);

    if ($name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE rooms SET name = :name, capacity = :cap WHERE id = :id");
            $stmt->execute(['name' => $name, 'cap' => $capacity, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity) VALUES (:name, :cap)");
            $stmt->execute(['name' => $name, 'cap' => $capacity]);
        }
    }
}
header("Location: ../rooms.php");
exit;
