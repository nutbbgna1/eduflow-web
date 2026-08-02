<?php
require_once 'config/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE students;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<h2 style='color:green;'>✅ ลบข้อมูลนักเรียนทั้งหมดเรียบร้อยแล้ว!</h2>";
    echo "<p>ระบบได้ทำการล้างข้อมูล Account นักเรียนทั้งหมดออกจากฐานข้อมูลแล้วครับ</p>";
    echo "<a href='index.php'>กลับหน้าแรก</a>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
