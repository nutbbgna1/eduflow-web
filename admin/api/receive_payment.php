<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    if ($payment_id) {
        try {
            $pdo->beginTransaction();

            // Fetch payment details
            $stmt = $pdo->prepare("SELECT student_id, subject_id FROM payments WHERE id = :id");
            $stmt->execute(['id' => $payment_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                // Update payment status
                $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', paid_date = CURDATE() WHERE id = :id");
                $stmt->execute(['id' => $payment_id]);

                // Check if already enrolled
                $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = :sid AND subject_id = :subid");
                $checkStmt->execute(['sid' => $payment['student_id'], 'subid' => $payment['subject_id']]);
                
                if (!$checkStmt->fetch()) {
                    // Enroll the student
                    $enrollStmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject_id) VALUES (:sid, :subid)");
                    $enrollStmt->execute(['sid' => $payment['student_id'], 'subid' => $payment['subject_id']]);
                }
            }

            $pdo->commit();
            header("Location: ../finance.php?payment=received");
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error updating payment and enrollment: " . $e->getMessage());
        }
    }
}
header("Location: ../finance.php");
