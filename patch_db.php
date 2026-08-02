<?php
require_once 'config/db.php';

function addColumn($pdo, $table, $column, $definition) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "Successfully added $column to $table.<br>";
    } catch (Exception $e) {
        // If it already exists, it will throw an exception. We ignore it.
        echo "Note on $table ($column): " . $e->getMessage() . "<br>";
    }
}

// Enrollments
addColumn($pdo, 'enrollments', 'status', "VARCHAR(50) DEFAULT 'active'");
addColumn($pdo, 'enrollments', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Teaching Logs
addColumn($pdo, 'teaching_logs', 'hours', "DECIMAL(5,2) DEFAULT 1.0");
addColumn($pdo, 'teaching_logs', 'is_substitution', "BOOLEAN DEFAULT FALSE");
addColumn($pdo, 'teaching_logs', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
addColumn($pdo, 'teaching_logs', 'checkin_time', "DATETIME");
addColumn($pdo, 'teaching_logs', 'photo_url', "VARCHAR(255)");

echo "<br><strong>Database patch completed successfully.</strong>";
?>
