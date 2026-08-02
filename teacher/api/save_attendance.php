<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $photo_data = $_POST['photo_data']; // base64 string
    $attendance = isset($_POST['attendance']) ? $_POST['attendance'] : []; // Array of student_ids

    // In a real app, you would save the base64 photo_data to a file and get the URL
    // For this prototype, we will just use a mock URL or ignore it.
    $photo_url = 'uploads/mock_photo_' . time() . '.jpg'; 

    $log_date = date('Y-m-d');
    $checkin_time = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // Check if log already exists
        $stmt = $pdo->prepare("SELECT id FROM teaching_logs WHERE schedule_id = :sid AND log_date = :ldate");
        $stmt->execute(['sid' => $schedule_id, 'ldate' => $log_date]);
        if ($stmt->fetch()) {
            throw new Exception("บันทึกการสอนสำหรับคาบนี้ไปแล้ว");
        }

        // 1. Insert teaching log
        $stmt = $pdo->prepare("
            INSERT INTO teaching_logs (schedule_id, actual_teacher_id, log_date, checkin_time, photo_url) 
            VALUES (:sid, :tid, :ldate, :ctime, :photo)
        ");
        $stmt->execute([
            'sid' => $schedule_id,
            'tid' => $current_user_id,
            'ldate' => $log_date,
            'ctime' => $checkin_time,
            'photo' => $photo_url
        ]);
        
        $log_id = $pdo->lastInsertId();

        // 2. Insert student attendance
        $stmt = $pdo->prepare("
            INSERT INTO teaching_log_students (teaching_log_id, student_id, source) 
            VALUES (:lid, :stid, 'manual')
        ");
        
        foreach ($attendance as $student_id) {
            $stmt->execute([
                'lid' => $log_id,
                'stid' => $student_id
            ]);
        }

        $pdo->commit();
        
        echo "<script>
            alert('บันทึกการสอนและเช็คชื่อสำเร็จ!');
            window.location.href = '../dashboard.php';
        </script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>
            alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');
            window.location.href = '../dashboard.php';
        </script>";
    }
}
?>
