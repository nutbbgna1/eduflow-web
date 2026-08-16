<?php
/**
 * ExamChecker — REST API
 * Handles all exam-related operations
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';
require_once 'exam-db.php';

// Auto-migrate tables
exam_auto_migrate($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$teacher_id = $_SESSION['user_id'] ?? null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    switch ($action) {
        // ========== SUBJECTS ==========
        case 'get_subjects':
            $stmt = $pdo->query("SELECT id, code, name FROM subjects ORDER BY name");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ========== STUDENTS ==========
        case 'get_students':
            $subject_id = $_GET['subject_id'] ?? null;
            if ($subject_id) {
                // Get students enrolled in this subject
                $stmt = $pdo->prepare("
                    SELECT s.id, s.student_code, s.first_name, s.last_name 
                    FROM students s 
                    JOIN enrollments e ON s.id = e.student_id 
                    WHERE e.subject_id = ? 
                    ORDER BY s.student_code
                ");
                $stmt->execute([$subject_id]);
            } else {
                $stmt = $pdo->query("SELECT id, student_code, first_name, last_name FROM students ORDER BY student_code");
            }
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ========== EXAMS ==========
        case 'list_exams':
            $stmt = $pdo->prepare("
                SELECT e.*, sub.code as subject_code, sub.name as subject_name,
                    (SELECT COUNT(*) FROM exam_answer_keys WHERE exam_id = e.id) as keys_count,
                    (SELECT COUNT(*) FROM exam_scan_results WHERE exam_id = e.id) as scans_count
                FROM exams e 
                JOIN subjects sub ON e.subject_id = sub.id 
                WHERE e.teacher_id = ? 
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$teacher_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get_exam':
            $exam_id = $_GET['exam_id'] ?? null;
            if (!$exam_id) throw new Exception('Missing exam_id');
            
            $stmt = $pdo->prepare("
                SELECT e.*, sub.code as subject_code, sub.name as subject_name
                FROM exams e 
                JOIN subjects sub ON e.subject_id = sub.id 
                WHERE e.id = ? AND e.teacher_id = ?
            ");
            $stmt->execute([$exam_id, $teacher_id]);
            $exam = $stmt->fetch();
            if (!$exam) throw new Exception('Exam not found');
            
            // Get answer keys
            $keys_stmt = $pdo->prepare("SELECT question_no, correct_answer, points FROM exam_answer_keys WHERE exam_id = ? ORDER BY question_no");
            $keys_stmt->execute([$exam_id]);
            $exam['answer_keys'] = $keys_stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $exam]);
            break;

        case 'create_exam':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;
            
            $subject_id = $data['subject_id'] ?? null;
            $title = $data['title'] ?? '';
            $exam_code = $data['exam_code'] ?? null;
            $total_questions = intval($data['total_questions'] ?? 50);
            $choices_count = intval($data['choices_count'] ?? 5);
            $points_per_question = floatval($data['points_per_question'] ?? 1);

            if (!$subject_id || !$title) throw new Exception('Missing required fields');
            
            $stmt = $pdo->prepare("INSERT INTO exams (subject_id, teacher_id, exam_code, title, total_questions, choices_count, points_per_question) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$subject_id, $teacher_id, $exam_code, $title, $total_questions, $choices_count, $points_per_question]);
            
            echo json_encode(['success' => true, 'exam_id' => $pdo->lastInsertId()]);
            break;

        case 'find_exam_by_code':
            $exam_code = $_GET['exam_code'] ?? '';
            if (!$exam_code) throw new Exception('Missing exam_code');
            
            $stmt = $pdo->prepare("
                SELECT e.*, sub.code as subject_code, sub.name as subject_name
                FROM exams e 
                JOIN subjects sub ON e.subject_id = sub.id 
                WHERE e.exam_code = ? AND e.teacher_id = ?
                LIMIT 1
            ");
            $stmt->execute([$exam_code, $teacher_id]);
            $exam = $stmt->fetch();
            if ($exam) {
                echo json_encode(['success' => true, 'found' => true, 'data' => $exam]);
            } else {
                echo json_encode(['success' => true, 'found' => false]);
            }
            break;

        case 'delete_exam':
            $data = json_decode(file_get_contents('php://input'), true);
            $exam_id = $data['exam_id'] ?? null;
            if (!$exam_id) throw new Exception('Missing exam_id');
            
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$exam_id, $teacher_id]);
            echo json_encode(['success' => true]);
            break;

        // ========== ANSWER KEYS ==========
        case 'save_answer_key':
            $data = json_decode(file_get_contents('php://input'), true);
            $exam_id = $data['exam_id'] ?? null;
            $keys = $data['keys'] ?? [];
            
            if (!$exam_id || empty($keys)) throw new Exception('Missing data');
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$exam_id, $teacher_id]);
            if (!$stmt->fetch()) throw new Exception('Exam not found');
            
            // Delete existing keys and re-insert
            $pdo->prepare("DELETE FROM exam_answer_keys WHERE exam_id = ?")->execute([$exam_id]);
            
            $insert = $pdo->prepare("INSERT INTO exam_answer_keys (exam_id, question_no, correct_answer, points) VALUES (?, ?, ?, ?)");
            foreach ($keys as $key) {
                $insert->execute([
                    $exam_id,
                    $key['question_no'],
                    strtoupper($key['answer']),
                    $key['points'] ?? 1
                ]);
            }
            
            echo json_encode(['success' => true, 'count' => count($keys)]);
            break;

        // ========== SCAN RESULTS ==========
        case 'save_scan':
            $data = json_decode(file_get_contents('php://input'), true);
            $exam_id = $data['exam_id'] ?? null;
            $student_id = $data['student_id'] ?? null;
            $answers = $data['answers'] ?? [];
            $image_data = $data['image_data'] ?? null;
            
            if (!$exam_id || !$student_id) throw new Exception('Missing data');
            
            // Get exam info
            $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$exam_id, $teacher_id]);
            $exam = $stmt->fetch();
            if (!$exam) throw new Exception('Exam not found');
            
            // Get answer keys
            $keys_stmt = $pdo->prepare("SELECT question_no, correct_answer, points FROM exam_answer_keys WHERE exam_id = ?");
            $keys_stmt->execute([$exam_id]);
            $answer_keys = [];
            foreach ($keys_stmt->fetchAll() as $k) {
                $answer_keys[$k['question_no']] = $k;
            }
            
            // Grade
            $total_score = 0;
            $total_possible = 0;
            $correct_count = 0;
            $wrong_count = 0;
            $blank_count = 0;
            
            for ($q = 1; $q <= $exam['total_questions']; $q++) {
                $student_answer = strtoupper($answers[$q] ?? '');
                $correct = $answer_keys[$q] ?? null;
                $pts = $correct ? floatval($correct['points']) : floatval($exam['points_per_question']);
                $total_possible += $pts;
                
                if (empty($student_answer) || $student_answer === '-') {
                    $blank_count++;
                } elseif ($correct && $student_answer === $correct['correct_answer']) {
                    $correct_count++;
                    $total_score += $pts;
                } else {
                    $wrong_count++;
                }
            }
            
            // Save image if provided
            $image_path = null;
            if ($image_data) {
                $upload_dir = '../../uploads/exams/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'scan_' . $exam_id . '_' . $student_id . '_' . time() . '.jpg';
                $image_path = 'uploads/exams/' . $filename;
                
                // Decode base64
                $img_parts = explode(',', $image_data);
                $img_decoded = base64_decode(end($img_parts));
                file_put_contents($upload_dir . $filename, $img_decoded);
            }
            
            // Upsert result
            $answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("SELECT id FROM exam_scan_results WHERE exam_id = ? AND student_id = ?");
            $stmt->execute([$exam_id, $student_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("
                    UPDATE exam_scan_results SET 
                        answers_json = ?, total_score = ?, total_possible = ?,
                        correct_count = ?, wrong_count = ?, blank_count = ?,
                        image_path = COALESCE(?, image_path), scanned_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$answers_json, $total_score, $total_possible, $correct_count, $wrong_count, $blank_count, $image_path, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO exam_scan_results 
                        (exam_id, student_id, answers_json, total_score, total_possible, correct_count, wrong_count, blank_count, image_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$exam_id, $student_id, $answers_json, $total_score, $total_possible, $correct_count, $wrong_count, $blank_count, $image_path]);
            }
            
            echo json_encode([
                'success' => true,
                'result' => [
                    'total_score' => $total_score,
                    'total_possible' => $total_possible,
                    'correct_count' => $correct_count,
                    'wrong_count' => $wrong_count,
                    'blank_count' => $blank_count,
                    'percentage' => $total_possible > 0 ? round(($total_score / $total_possible) * 100, 1) : 0
                ]
            ]);
            break;

        case 'get_results':
            $exam_id = $_GET['exam_id'] ?? null;
            if (!$exam_id) throw new Exception('Missing exam_id');
            
            // Get exam info
            $stmt = $pdo->prepare("
                SELECT e.*, sub.code as subject_code, sub.name as subject_name
                FROM exams e JOIN subjects sub ON e.subject_id = sub.id
                WHERE e.id = ? AND e.teacher_id = ?
            ");
            $stmt->execute([$exam_id, $teacher_id]);
            $exam = $stmt->fetch();
            if (!$exam) throw new Exception('Exam not found');
            
            // Get all results with student info
            $stmt = $pdo->prepare("
                SELECT r.*, s.student_code, s.first_name, s.last_name
                FROM exam_scan_results r
                JOIN students s ON r.student_id = s.id
                WHERE r.exam_id = ?
                ORDER BY s.student_code
            ");
            $stmt->execute([$exam_id]);
            $results = $stmt->fetchAll();
            
            // Get answer keys for item analysis
            $keys_stmt = $pdo->prepare("SELECT question_no, correct_answer FROM exam_answer_keys WHERE exam_id = ? ORDER BY question_no");
            $keys_stmt->execute([$exam_id]);
            $answer_keys = $keys_stmt->fetchAll();
            
            // Item analysis
            $item_analysis = [];
            if (!empty($results)) {
                for ($q = 1; $q <= $exam['total_questions']; $q++) {
                    $correct = 0;
                    $total = count($results);
                    $choice_dist = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, '-' => 0];
                    
                    foreach ($results as $r) {
                        $answers = json_decode($r['answers_json'], true);
                        $ans = strtoupper($answers[$q] ?? '-');
                        if ($ans === '' || !isset($choice_dist[$ans])) $ans = '-';
                        $choice_dist[$ans]++;
                        
                        // Check if correct
                        foreach ($answer_keys as $ak) {
                            if ($ak['question_no'] == $q && strtoupper($ans) === $ak['correct_answer']) {
                                $correct++;
                            }
                        }
                    }
                    
                    $item_analysis[$q] = [
                        'question_no' => $q,
                        'correct_count' => $correct,
                        'total' => $total,
                        'correct_pct' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                        'distribution' => $choice_dist
                    ];
                }
            }
            
            // Statistics
            $scores = array_column($results, 'total_score');
            $stats = [
                'count' => count($scores),
                'mean' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0,
                'max' => count($scores) > 0 ? max($scores) : 0,
                'min' => count($scores) > 0 ? min($scores) : 0,
                'sd' => 0
            ];
            
            if (count($scores) > 1) {
                $mean = $stats['mean'];
                $variance = array_sum(array_map(function($s) use ($mean) { 
                    return pow($s - $mean, 2); 
                }, $scores)) / count($scores);
                $stats['sd'] = round(sqrt($variance), 2);
            }
            
            echo json_encode([
                'success' => true,
                'exam' => $exam,
                'results' => $results,
                'answer_keys' => $answer_keys,
                'item_analysis' => $item_analysis,
                'stats' => $stats
            ]);
            break;

        case 'delete_result':
            $data = json_decode(file_get_contents('php://input'), true);
            $result_id = $data['result_id'] ?? null;
            if (!$result_id) throw new Exception('Missing result_id');
            
            // Verify ownership through exam
            $stmt = $pdo->prepare("
                SELECT r.id FROM exam_scan_results r
                JOIN exams e ON r.exam_id = e.id
                WHERE r.id = ? AND e.teacher_id = ?
            ");
            $stmt->execute([$result_id, $teacher_id]);
            if (!$stmt->fetch()) throw new Exception('Result not found');
            
            $pdo->prepare("DELETE FROM exam_scan_results WHERE id = ?")->execute([$result_id]);
            echo json_encode(['success' => true]);
            break;

        case 'export_csv':
            $exam_id = $_GET['exam_id'] ?? null;
            if (!$exam_id) throw new Exception('Missing exam_id');
            
            // Get exam
            $stmt = $pdo->prepare("SELECT e.*, sub.name as subject_name FROM exams e JOIN subjects sub ON e.subject_id = sub.id WHERE e.id = ? AND e.teacher_id = ?");
            $stmt->execute([$exam_id, $teacher_id]);
            $exam = $stmt->fetch();
            if (!$exam) throw new Exception('Exam not found');
            
            // Get results
            $stmt = $pdo->prepare("
                SELECT s.student_code, s.first_name, s.last_name, 
                    r.total_score, r.total_possible, r.correct_count, r.wrong_count, r.blank_count, r.answers_json
                FROM exam_scan_results r
                JOIN students s ON r.student_id = s.id
                WHERE r.exam_id = ?
                ORDER BY s.student_code
            ");
            $stmt->execute([$exam_id]);
            $results = $stmt->fetchAll();
            
            // Generate CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="exam_results_' . $exam_id . '.csv"');
            
            // BOM for Excel
            echo "\xEF\xBB\xBF";
            
            $out = fopen('php://output', 'w');
            
            // Header row
            $header = ['รหัสนักเรียน', 'ชื่อ', 'นามสกุล', 'คะแนนที่ได้', 'คะแนนเต็ม', 'ถูก', 'ผิด', 'ไม่ตอบ'];
            for ($q = 1; $q <= $exam['total_questions']; $q++) {
                $header[] = "ข้อ $q";
            }
            fputcsv($out, $header);
            
            // Data rows
            foreach ($results as $r) {
                $answers = json_decode($r['answers_json'], true);
                $row = [
                    $r['student_code'],
                    $r['first_name'],
                    $r['last_name'],
                    $r['total_score'],
                    $r['total_possible'],
                    $r['correct_count'],
                    $r['wrong_count'],
                    $r['blank_count']
                ];
                for ($q = 1; $q <= $exam['total_questions']; $q++) {
                    $row[] = $answers[$q] ?? '-';
                }
                fputcsv($out, $row);
            }
            fclose($out);
            exit;

        // ========== FIND STUDENT BY CODE ==========
        case 'find_student_by_code':
            $student_code = $_GET['student_code'] ?? '';
            $subject_id = $_GET['subject_id'] ?? null;
            
            if (!$student_code) throw new Exception('Missing student_code');
            
            if ($subject_id) {
                $stmt = $pdo->prepare("
                    SELECT s.id, s.student_code, s.first_name, s.last_name 
                    FROM students s 
                    JOIN enrollments e ON s.id = e.student_id 
                    WHERE s.student_code = ? AND e.subject_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$student_code, $subject_id]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, student_code, first_name, last_name 
                    FROM students WHERE student_code = ? LIMIT 1
                ");
                $stmt->execute([$student_code]);
            }
            
            $student = $stmt->fetch();
            echo json_encode([
                'success' => true, 
                'found' => !!$student, 
                'data' => $student ?: null
            ]);
            break;

        // ========== BATCH SAVE SCANS ==========
        case 'batch_save_scan':
            $data = json_decode(file_get_contents('php://input'), true);
            $global_exam_id = $data['exam_id'] ?? null;
            $scans = $data['scans'] ?? [];
            
            if (empty($scans)) throw new Exception('Missing data');
            
            $results = [];
            $saved_count = 0;
            $failed_count = 0;
            
            // Cache for exams and keys to avoid querying per scan if same exam
            $exam_cache = [];
            $keys_cache = [];
            
            foreach ($scans as $scan) {
                $student_id = $scan['student_id'] ?? null;
                $answers = $scan['answers'] ?? [];
                $image_data = $scan['image_data'] ?? null;
                
                if (!$student_id || empty($answers)) {
                    $failed_count++;
                    $results[] = ['student_id' => $student_id, 'success' => false, 'error' => 'Missing data'];
                    continue;
                }
                
                $scan_exam_id = $scan['exam_id'] ?? $global_exam_id;
                if (!$scan_exam_id) {
                    $failed_count++;
                    $results[] = ['student_id' => $student_id, 'success' => false, 'error' => 'Missing exam_id'];
                    continue;
                }

                // Fetch exam from cache or DB
                if (!isset($exam_cache[$scan_exam_id])) {
                    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$scan_exam_id, $teacher_id]);
                    $exam_cache[$scan_exam_id] = $stmt->fetch();
                    
                    if ($exam_cache[$scan_exam_id]) {
                        $keys_stmt = $pdo->prepare("SELECT question_no, correct_answer, points FROM exam_answer_keys WHERE exam_id = ?");
                        $keys_stmt->execute([$scan_exam_id]);
                        $keys = [];
                        foreach ($keys_stmt->fetchAll() as $k) {
                            $keys[$k['question_no']] = $k;
                        }
                        $keys_cache[$scan_exam_id] = $keys;
                    }
                }
                
                $exam = $exam_cache[$scan_exam_id];
                $answer_keys = $keys_cache[$scan_exam_id] ?? [];
                
                if (!$exam) {
                    $failed_count++;
                    $results[] = ['student_id' => $student_id, 'success' => false, 'error' => 'Exam not found'];
                    continue;
                }
                
                try {
                    // Calculate score
                    $total_score = 0;
                    $total_possible = 0;
                    $correct_count = 0;
                    $wrong_count = 0;
                    $blank_count = 0;
                    
                    for ($q = 1; $q <= $exam['total_questions']; $q++) {
                        $student_answer = strtoupper($answers[$q] ?? '');
                        $correct = $answer_keys[$q] ?? null;
                        $pts = $correct ? floatval($correct['points']) : floatval($exam['points_per_question']);
                        $total_possible += $pts;
                        
                        if (empty($student_answer) || $student_answer === '-') {
                            $blank_count++;
                        } elseif ($correct && $student_answer === $correct['correct_answer']) {
                            $correct_count++;
                            $total_score += $pts;
                        } else {
                            $wrong_count++;
                        }
                    }
                    
                    // Save image
                    $image_path = null;
                    if ($image_data) {
                        $upload_dir = '../../uploads/exams/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                        $filename = 'scan_' . $scan_exam_id . '_' . $student_id . '_' . time() . '_' . uniqid() . '.jpg';
                        $image_path = 'uploads/exams/' . $filename;
                        $img_parts = explode(',', $image_data);
                        $img_decoded = base64_decode(end($img_parts));
                        file_put_contents($upload_dir . $filename, $img_decoded);
                    }
                    
                    // Upsert
                    $answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);
                    $stmt = $pdo->prepare("SELECT id FROM exam_scan_results WHERE exam_id = ? AND student_id = ?");
                    $stmt->execute([$scan_exam_id, $student_id]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        $update_stmt = $pdo->prepare("
                            UPDATE exam_scan_results SET 
                                answers_json = ?, total_score = ?, total_possible = ?,
                                correct_count = ?, wrong_count = ?, blank_count = ?,
                                image_path = COALESCE(?, image_path), scanned_at = NOW()
                            WHERE id = ?
                        ");
                        $update_stmt->execute([$answers_json, $total_score, $total_possible, $correct_count, $wrong_count, $blank_count, $image_path, $existing['id']]);
                    } else {
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO exam_scan_results 
                                (exam_id, student_id, answers_json, total_score, total_possible, correct_count, wrong_count, blank_count, image_path)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert_stmt->execute([$scan_exam_id, $student_id, $answers_json, $total_score, $total_possible, $correct_count, $wrong_count, $blank_count, $image_path]);
                    }
                    
                    $saved_count++;
                    $results[] = [
                        'student_id' => $student_id, 
                        'success' => true, 
                        'total_score' => $total_score, 
                        'total_possible' => $total_possible,
                        'correct_count' => $correct_count,
                        'wrong_count' => $wrong_count,
                        'percentage' => $total_possible > 0 ? round(($total_score / $total_possible) * 100, 1) : 0
                    ];
                } catch (Exception $ex) {
                    $failed_count++;
                    $results[] = ['student_id' => $student_id, 'success' => false, 'error' => $ex->getMessage()];
                }
            }
            
            echo json_encode([
                'success' => true,
                'saved_count' => $saved_count,
                'failed_count' => $failed_count,
                'results' => $results
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
