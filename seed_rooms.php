<?php
require_once 'config/db.php';

try {
    $pdo->beginTransaction();
    
    // Optional: Clear existing rooms if you want a clean slate
    $pdo->exec("DELETE FROM rooms");
    $pdo->exec("ALTER TABLE rooms AUTO_INCREMENT = 1");
    
    $rooms = ['201', '202', '204', '301', '302', '303', '401', '402', '403'];
    $capacity = 18;
    
    $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity) VALUES (:name, :cap)");
    
    foreach ($rooms as $room) {
        $stmt->execute(['name' => "Room $room", 'cap' => $capacity]);
        echo "Added Room $room with capacity $capacity<br>";
    }
    
    $pdo->commit();
    echo "<br><strong>Successfully created all rooms!</strong>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
