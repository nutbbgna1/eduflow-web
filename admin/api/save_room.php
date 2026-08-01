<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);

    if ($name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE rooms SET name = :name, building = :bldg, capacity = :cap WHERE id = :id");
            $stmt->execute(['name' => $name, 'bldg' => $building, 'cap' => $capacity, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, building, capacity) VALUES (:name, :bldg, :cap)");
            $stmt->execute(['name' => $name, 'bldg' => $building, 'cap' => $capacity]);
        }
    }
}
header("Location: ../rooms.php");
exit;
