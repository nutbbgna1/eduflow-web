<?php
require_once '../includes/db.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payroll_id  = (int)($_POST['payroll_id'] ?? 0);
    $teacher_id  = (int)($_POST['teacher_id'] ?? 0);
    $month       = $_POST['month'] ?? date('Y-m');
    $total_hours = (float)($_POST['total_hours'] ?? 0);
    $hourly_rate = (float)($_POST['hourly_rate'] ?? 0);
    $bonus       = (float)($_POST['bonus'] ?? 0);
    $deduction   = (float)($_POST['deduction'] ?? 0);
    $net_pay     = (float)($_POST['net_pay'] ?? 0);
    $status      = $_POST['status'] ?? 'draft';
    $note        = trim($_POST['note'] ?? '');
    $paid_date   = ($status === 'paid') ? date('Y-m-d') : null;
    $total_pay   = $total_hours * $hourly_rate;

    if ($teacher_id && $month) {
        if ($payroll_id) {
            $stmt = $pdo->prepare("
                UPDATE payroll SET 
                    total_hours = :hrs, hourly_rate = :rate, total_pay = :pay,
                    bonus = :bonus, deduction = :ded, net_pay = :net,
                    status = :status, paid_date = :paid, note = :note
                WHERE id = :id
            ");
            $stmt->execute([
                'hrs' => $total_hours, 'rate' => $hourly_rate, 'pay' => $total_pay,
                'bonus' => $bonus, 'ded' => $deduction, 'net' => $net_pay,
                'status' => $status, 'paid' => $paid_date, 'note' => $note, 'id' => $payroll_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO payroll (teacher_id, month, total_hours, hourly_rate, total_pay, bonus, deduction, net_pay, status, paid_date, note)
                VALUES (:tid, :month, :hrs, :rate, :pay, :bonus, :ded, :net, :status, :paid, :note)
            ");
            $stmt->execute([
                'tid' => $teacher_id, 'month' => $month,
                'hrs' => $total_hours, 'rate' => $hourly_rate, 'pay' => $total_pay,
                'bonus' => $bonus, 'ded' => $deduction, 'net' => $net_pay,
                'status' => $status, 'paid' => $paid_date, 'note' => $note
            ]);
        }
    }
}
header("Location: ../payroll.php?month={$_POST['month']}&success=1");
exit;
