<?php
require_once '../config/db.php';
require_once '../admin/api/exam-db.php';
exam_auto_migrate($pdo);

// current_user_id is available from config/db.php for teacher role
if (!isset($current_user_id)) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow - ตรวจข้อสอบ</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        /* Base Overrides for Mobile Layout */
        .exam-tabs {
            display: flex;
            background: var(--surface);
            border-radius: var(--border-radius-md);
            padding: 4px;
            gap: 4px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
            border: 1px solid var(--border);
            overflow-x: auto;
            scrollbar-width: none;
        }
        .exam-tabs::-webkit-scrollbar { display: none; }
        
        .exam-tab {
            flex: 1;
            min-width: 80px;
            padding: 10px 8px;
            border: none;
            background: transparent;
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .exam-tab.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .exam-tab .material-symbols-rounded { font-size: 20px; }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: examFadeIn 0.3s ease; }

        @keyframes examFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Exam Cards */
        .exam-card {
            background: var(--surface);
            border-radius: var(--border-radius-lg);
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255,255,255,0.8);
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        .exam-card:active { transform: scale(0.98); }
        .exam-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .exam-badge.blue { background: var(--primary-light); color: var(--primary); }
        .exam-badge.green { background: var(--success-bg); color: var(--success); }
        .exam-badge.amber { background: #FEF3C7; color: #D97706; }

        /* Answer Key Grid (Mobile Optimized) */
        .answer-key-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
        }
        .answer-key-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 4px;
            background: #F8FAFC;
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border);
        }
        .answer-key-item .q-num {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
        }
        .answer-choices { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
        .choice-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }
        .choice-btn.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        /* Scanner Area */
        .scanner-area {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            text-align: center;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .scanner-area img, .scanner-area video, .scanner-area canvas {
            max-width: 100%;
            max-height: 300px;
            border-radius: var(--border-radius-md);
        }
        .scanner-placeholder { color: rgba(255,255,255,0.6); }
        .scanner-placeholder .material-symbols-rounded {
            font-size: 48px;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.3);
        }

        /* Scan Actions */
        .scan-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .scan-btn {
            padding: 12px;
            border-radius: var(--border-radius-md);
            border: none;
            font-family: inherit;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .scan-btn .material-symbols-rounded { font-size: 24px; }
        .scan-btn.primary { background: var(--primary); color: #fff; }
        .scan-btn.secondary { background: var(--surface); color: var(--primary); border: 1px solid var(--primary-light); box-shadow: var(--shadow-sm); }
        .scan-btn.full-width { grid-column: span 2; flex-direction: row; }

        /* PDF Grid */
        .pdf-pages-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        .pdf-page-thumb {
            position: relative;
            background: var(--surface);
            border-radius: var(--border-radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
        }
        .pdf-page-thumb.processing { border-color: #F59E0B; }
        .pdf-page-thumb.done { border-color: var(--success); }
        .pdf-page-thumb.error { border-color: var(--danger); }
        .pdf-page-thumb canvas, .pdf-page-thumb img { width: 100%; height: auto; display: block; }
        
        .pdf-page-label {
            position: absolute; top: 4px; left: 4px;
            background: rgba(0,0,0,0.7); color: #fff;
            font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px;
        }
        .pdf-page-status {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 4px; font-size: 10px; font-weight: 600; text-align: center;
        }
        .pdf-page-status.success { background: rgba(16,185,129,0.9); color: #fff; }
        .pdf-page-status.fail { background: rgba(239,68,68,0.9); color: #fff; }
        .pdf-page-status.pending { background: rgba(245,158,11,0.9); color: #fff; }

        /* Progress Bar */
        .progress-bar-container {
            width: 100%; background: rgba(255,255,255,0.1);
            border-radius: 10px; overflow: hidden; margin-top: 12px; display: none;
        }
        .progress-bar-container.active { display: block; }
        .progress-bar {
            height: 4px; background: linear-gradient(90deg, #3B82F6, #06B6D4);
            width: 0%; transition: width 0.3s ease;
        }
        .progress-text { color: rgba(255,255,255,0.7); font-size: 11px; margin-top: 6px; display: none; }

        /* Modals & Overlays (Mobile optimized) */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); z-index: 1000;
            align-items: flex-end; /* Bottom sheet style for mobile */
            justify-content: center; backdrop-filter: blur(2px);
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: var(--background);
            border-radius: 24px 24px 0 0;
            width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto;
            padding: 24px 20px 40px; box-shadow: 0 -10px 25px rgba(0,0,0,0.1);
            animation: slideUpModal 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUpModal {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        .modal-handle {
            width: 40px; height: 4px; background: #CBD5E1; border-radius: 4px;
            margin: 0 auto 20px;
        }

        /* Results / Answer Grid */
        .result-answer-grid {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin: 12px 0;
        }
        .result-answer-item {
            text-align: center; padding: 6px 2px; border-radius: var(--border-radius-sm);
            font-size: 11px; font-weight: 700;
        }
        .result-answer-item.correct { background: var(--success-bg); color: var(--success); }
        .result-answer-item.wrong { background: var(--danger-bg); color: var(--danger); }
        .result-answer-item.blank { background: #F1F5F9; color: var(--text-muted); }
        .result-answer-item .q-label { font-size: 9px; font-weight: 500; display: block; opacity: 0.7; }

        .score-circle {
            width: 100px; height: 100px; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .score-circle .score-value { font-size: 28px; font-weight: 800; line-height: 1.1; }
        .score-circle .score-label { font-size: 11px; font-weight: 600; opacity: 0.8; }

        .exam-stats-row {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px;
        }
        .exam-stat-card {
            background: var(--surface); border-radius: var(--border-radius-md); padding: 12px;
            text-align: center; border: 1px solid var(--border);
        }
        .exam-stat-card .stat-value { font-size: 24px; font-weight: 800; }
        .exam-stat-card .stat-label { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

        /* Quick Fill */
        .quick-fill-input {
            width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: var(--border-radius-md);
            font-family: 'Courier New', monospace; font-size: 16px; letter-spacing: 2px;
            text-transform: uppercase; box-sizing: border-box; text-align: center;
        }
        .quick-fill-input:focus { outline: none; border-color: var(--primary); }

        /* OpenCV loader */
        .opencv-loading {
            position: fixed; top: 0; left: 0; right: 0; padding: 6px;
            background: var(--primary); color: #fff; font-size: 11px; font-weight: 600;
            text-align: center; z-index: 2000; transition: transform 0.3s;
        }
        .opencv-loading.hidden { transform: translateY(-100%); }
        .spinner {
            width: 24px; height: 24px; border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Student Search Modal Styles */
        .student-search {
            width: 100%; padding: 12px 12px 12px 40px; border: 2px solid var(--border);
            border-radius: var(--border-radius-md); font-family: inherit; font-size: 14px;
            margin-bottom: 12px; box-sizing: border-box;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") 12px center no-repeat;
        }
        .student-select-card {
            display: flex; align-items: center; gap: 12px; padding: 12px;
            background: #F8FAFC; border-radius: var(--border-radius-md); margin-bottom: 8px;
            border: 2px solid transparent;
        }
        .student-select-card.selected { border-color: var(--primary); background: var(--primary-light); }
        
        .batch-result-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px; border-radius: var(--border-radius-sm); margin-bottom: 6px; font-size: 12px;
        }
        .batch-result-item.success { background: var(--success-bg); }
        .batch-result-item.fail { background: var(--danger-bg); }
        .batch-result-item.manual { background: #FEF3C7; }
        
        .delete-btn { background:none; border:none; color:var(--danger); font-size:16px; padding:4px; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
    </style>
</head>
<body>
    <!-- OpenCV Loading -->
    <div id="opencvLoading" class="opencv-loading">
        <span class="material-symbols-rounded" style="font-size:12px;vertical-align:middle;">hourglass_top</span> กำลังโหลดระบบสแกน...
    </div>

    <div class="app-container">
        <?php include 'includes/header.php'; ?>
        
        <div class="main-content px-4 py-4">
            
            <!-- Tabs Navigation -->
            <div class="exam-tabs">
                <button class="exam-tab active" onclick="switchTab('exams')" id="tab-exams">
                    <span class="material-symbols-rounded">quiz</span> ข้อสอบ
                </button>
                <button class="exam-tab" onclick="switchTab('keys')" id="tab-keys">
                    <span class="material-symbols-rounded">key</span> เฉลย
                </button>
                <button class="exam-tab" onclick="switchTab('scanner')" id="tab-scanner">
                    <span class="material-symbols-rounded">document_scanner</span> สแกน
                </button>
                <button class="exam-tab" onclick="switchTab('results')" id="tab-results">
                    <span class="material-symbols-rounded">analytics</span> ผลคะแนน
                </button>
            </div>

            <!-- ===== TAB 1: EXAMS ===== -->
            <div id="panel-exams" class="tab-panel active">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-lg">รายการข้อสอบ</h2>
                    <div style="display:flex;gap:8px;">
                        <a href="../assets/downloads/answer_sheet_template.pdf" target="_blank" class="btn btn-outline" style="padding:8px 12px; font-size:13px; text-decoration:none; display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-rounded" style="font-size:16px;">print</span> โหลดใบคำตอบ
                        </a>
                        <button class="btn btn-primary" style="padding:8px 12px; font-size:13px;" onclick="showCreateExamModal()">
                            <span class="material-symbols-rounded" style="font-size:16px;">add</span> สร้าง
                        </button>
                    </div>
                </div>
                <div id="examsList"></div>
            </div>

            <!-- ===== TAB 2: KEYS ===== -->
            <div id="panel-keys" class="tab-panel">
                <h2 class="font-bold text-lg mb-4">เฉลยข้อสอบ</h2>
                
                <div class="form-group">
                    <select id="keyExamSelect" class="form-input" onchange="loadAnswerKeys()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>

                <div id="quickFillSection" style="display:none; margin-bottom:16px;">
                    <label class="form-label text-xs">Quick Fill (เช่น ABCDEABC...)</label>
                    <input type="text" id="quickFillInput" class="quick-fill-input" placeholder="ABCDEABCDE..." oninput="applyQuickFill()">
                </div>

                <div id="answerKeyGrid" class="answer-key-grid mb-4"></div>

                <div id="saveKeySection" style="display:none; margin-bottom:20px;">
                    <button class="btn btn-primary w-full" onclick="saveAnswerKeys()">
                        <span class="material-symbols-rounded">save</span> บันทึกเฉลย
                    </button>
                </div>
            </div>

            <!-- ===== TAB 3: SCANNER ===== -->
            <div id="panel-scanner" class="tab-panel">
                <h2 class="font-bold text-lg mb-4">ตรวจกระดาษคำตอบ</h2>
                
                <div class="form-group">
                    <select id="scanExamSelect" class="form-input" onchange="onScanExamChange()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>

                <!-- Scanner Area -->
                <div class="scanner-area" id="scannerArea">
                    <div class="scanner-placeholder" id="scannerPlaceholder">
                        <span class="material-symbols-rounded">document_scanner</span>
                        <div class="font-semibold mt-2">เตรียมกระดาษคำตอบ</div>
                        <div class="text-xs mt-1">รองรับกล้อง, รูปภาพ หรือ PDF</div>
                    </div>
                    <img id="scanPreviewImg" style="display:none;">
                    <canvas id="scanCanvas" style="display:none;"></canvas>
                    <video id="cameraVideo" style="display:none;" autoplay playsinline></video>

                    <div class="progress-bar-container" id="progressContainer">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                    <div class="progress-text" id="progressText"></div>
                </div>

                <!-- Scan Buttons -->
                <div class="scan-actions" id="scanActionsArea">
                    <button class="scan-btn secondary" onclick="openCamera()" id="btnCamera">
                        <span class="material-symbols-rounded">photo_camera</span> ถ่ายรูป
                    </button>
                    <label class="scan-btn secondary" style="margin:0;">
                        <span class="material-symbols-rounded">image</span> รูปภาพ
                        <input type="file" accept="image/*" onchange="handleFileUpload(event)" style="display:none;" id="fileInputImage">
                    </label>
                    
                    <label class="scan-btn secondary full-width" style="margin:0;">
                        <span class="material-symbols-rounded" style="color:var(--danger);">picture_as_pdf</span> ไฟล์ PDF (รองรับหลายหน้า)
                        <input type="file" accept=".pdf,application/pdf" onchange="handlePdfUpload(event)" style="display:none;" id="fileInputPdf">
                    </label>
                    
                    <button class="scan-btn primary full-width" onclick="processCurrentImage()" id="btnProcess" style="display:none;">
                        <span class="material-symbols-rounded">play_arrow</span> เริ่มตรวจ
                    </button>
                </div>

                <div class="mt-4 mb-4" id="manualInputToggleArea">
                    <button class="btn btn-light w-full" onclick="toggleManualInput()">
                        <span class="material-symbols-rounded" style="font-size:18px;">edit</span> กรอกคำตอบด้วยมือ
                    </button>
                    <div id="manualInputArea" style="display:none; margin-top:12px;">
                        <label class="form-label text-xs">พิมพ์คำตอบที่นักเรียนฝน (เช่น ABCDEABC...)</label>
                        <input type="text" id="manualAnswerInput" class="quick-fill-input" placeholder="ABCDEABCDE...">
                        <button class="btn btn-primary w-full mt-3" onclick="submitManualAnswers()">
                            <span class="material-symbols-rounded">check</span> ใช้คำตอบนี้
                        </button>
                    </div>
                </div>

                <!-- PDF Multiple Pages Grid -->
                <div id="pdfPagesContainer" style="display:none; margin-bottom:20px;">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-sm">
                            <span class="material-symbols-rounded" style="font-size:16px;color:var(--danger);vertical-align:bottom;">picture_as_pdf</span>
                            ไฟล์ PDF (<span id="pdfPageCount">0</span>)
                        </h4>
                        <button class="btn btn-primary" style="padding:6px 12px; font-size:12px;" onclick="batchProcessAllPages()" id="btnBatchProcess">
                            <span class="material-symbols-rounded" style="font-size:16px;">bolt</span> ตรวจทั้งหมด
                        </button>
                    </div>
                    <div id="pdfPagesGrid" class="pdf-pages-grid"></div>
                </div>

                <!-- Batch Summary Area -->
                <div id="batchSummary" style="display:none; margin-bottom:20px;"></div>

                <!-- Single Scan Result Area -->
                <div id="scanResultPreview" style="display:none; margin-bottom:20px;"></div>

            </div>

            <!-- ===== TAB 4: RESULTS ===== -->
            <div id="panel-results" class="tab-panel">
                <h2 class="font-bold text-lg mb-4">ผลคะแนนสอบ</h2>
                <div class="form-group">
                    <select id="resultExamSelect" class="form-input" onchange="loadResults()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>
                <div id="resultsContent"></div>
            </div>

        </div> <!-- .main-content -->
        <?php include 'includes/bottom_nav.php'; ?>
    </div> <!-- .app-container -->

    <!-- ================== MODALS ================== -->

    <!-- 1. Create Exam Modal -->
    <div class="modal-overlay" id="createExamModal">
        <div class="modal-content">
            <div class="modal-handle"></div>
            <h3 class="font-bold text-lg mb-4">สร้างข้อสอบใหม่</h3>
            
            <div class="form-group">
                <label class="form-label">วิชา</label>
                <select id="newExamSubject" class="form-input"></select>
            </div>
            <div class="flex gap-3">
                <div class="form-group w-full">
                    <label class="form-label">ชื่อการสอบ</label>
                    <input type="text" id="newExamTitle" class="form-input" placeholder="เช่น กลางภาค 1/69">
                </div>
                <div class="form-group" style="width:120px;">
                    <label class="form-label">รหัสชุดข้อสอบ</label>
                    <input type="text" id="newExamCode" class="form-input" placeholder="เช่น 01" maxlength="2">
                </div>
            </div>
            <div class="flex gap-3">
                <div class="form-group w-full">
                    <label class="form-label">จำนวนข้อ</label>
                    <input type="number" id="newExamQuestions" class="form-input" value="50" min="5" max="200">
                </div>
                <div class="form-group w-full">
                    <label class="form-label">ตัวเลือก</label>
                    <select id="newExamChoices" class="form-input">
                        <option value="4">4 (A-D)</option>
                        <option value="5" selected>5 (A-E)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">คะแนนต่อข้อ</label>
                <input type="number" id="newExamPoints" class="form-input" value="1" step="0.5">
            </div>

            <div class="flex gap-3 mt-2">
                <button class="btn btn-light w-full" onclick="closeModal('createExamModal')">ยกเลิก</button>
                <button class="btn btn-primary w-full" onclick="createExam()">บันทึก</button>
            </div>
        </div>
    </div>

    <!-- 2. Student Match Modal -->
    <div class="modal-overlay" id="studentSelectModal">
        <div class="modal-content">
            <div class="modal-handle"></div>
            <h3 class="font-bold text-lg mb-3">เลือกนักเรียน</h3>
            <p class="text-xs text-muted mb-3">จับคู่คะแนนกับนักเรียนในรายวิชา</p>
            
            <input type="text" class="student-search" placeholder="ค้นหาชื่อหรือรหัส..." oninput="filterStudents(this.value)">
            
            <div id="studentList" style="max-height:300px; overflow-y:auto; margin-bottom:16px;"></div>
            
            <button class="btn btn-primary w-full" onclick="confirmStudentAndSave()" id="btnConfirmStudent">
                <span class="material-symbols-rounded">check</span> บันทึกคะแนน
            </button>
            <button class="btn btn-light w-full mt-2" onclick="closeModal('studentSelectModal')">
                ยกเลิก
            </button>
        </div>
    </div>


    <!-- ================== SCRIPTS ================== -->
    <!-- OpenCV.js -->
    <script async src="https://docs.opencv.org/4.9.0/opencv.js" onload="onOpenCvReady()" id="opencvScript"></script>
    
    <!-- Sheet Processor -->
    <script src="../admin/js/exam-sheet-processor.js"></script>

    <script>
    // PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    // Global state
    const API = '../admin/api/exam-api.php';
    let currentExams = [];
    let currentExamId = null;
    let currentAnswerKeys = {};
    let currentScanAnswers = null;
    let currentScanImage = null;
    let selectedStudentId = null;
    let allStudents = [];
    
    let cvReady = false;
    let processor = null;
    let cameraStream = null;

    // Batch PDF state
    let pdfPages = []; 
    let batchPendingManual = [];
    let currentBatchPageIdx = null;

    // --- Init OpenCV ---
    function onOpenCvReady() {
        cvReady = true;
        processor = new SheetProcessor();
        processor.onProgress = (msg, pct) => {
            document.getElementById('progressText').textContent = msg;
            document.getElementById('progressBar').style.width = pct + '%';
        };
        const loader = document.getElementById('opencvLoading');
        loader.classList.add('hidden');
        setTimeout(() => loader.remove(), 400);
    }

    // --- Tabs Logic ---
    function switchTab(tab) {
        document.querySelectorAll('.exam-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');

        if (tab === 'exams') loadExams();
        if (tab === 'keys') populateSelectors();
        if (tab === 'scanner') populateSelectors();
        if (tab === 'results') populateSelectors();
    }

    // --- API Helper ---
    async function api(action, params = {}, method = 'GET') {
        let url = `${API}?action=${action}`;
        let options = { method };
        if (method === 'GET') {
            Object.keys(params).forEach(k => url += `&${k}=${encodeURIComponent(params[k])}`);
        } else {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(params);
        }
        try {
            const res = await fetch(url, options);
            return await res.json();
        } catch (e) {
            console.error(e);
            return { success: false, error: 'Network Error' };
        }
    }

    // --- Modals Logic ---
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) closeModal(m.id);
        });
    });

    // ==========================================
    // TAB 1: EXAMS
    // ==========================================
    async function loadExams() {
        const data = await api('list_exams');
        currentExams = data.data || [];
        
        let html = '';
        let opts = '<option value="">-- เลือกข้อสอบ --</option>';
        
        currentExams.forEach(ex => {
            const badge = ex.exam_code ? `<span class="badge text-primary bg-primary-light" style="padding:2px 6px; font-size:10px;">ชุด ${ex.exam_code}</span>` : '';
            html += `
                <div class="exam-card" onclick="selectExam(${ex.id})">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-base flex items-center gap-2">${esc(ex.title)} ${badge}</div>
                            <div class="text-xs text-muted mt-1">${esc(ex.subject_code)} — ${esc(ex.subject_name)}</div>
                        </div>
                        <button class="delete-btn" onclick="event.stopPropagation(); deleteExam(${ex.id})">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </div>
                    <div class="flex gap-2 flex-wrap mt-3">
                        <span class="exam-badge blue">${ex.total_questions} ข้อ</span>
                        <span class="exam-badge ${ex.keys_count > 0 ? 'green' : 'amber'}">${ex.keys_count > 0 ? '✓ เฉลยแล้ว' : '✏️ ยังไม่มีเฉลย'}</span>
                        <span class="exam-badge ${ex.scans_count > 0 ? 'green' : 'blue'}">📄 ตรวจแล้ว ${ex.scans_count}</span>
                    </div>
                </div>
            `;
            opts += `<option value="${ex.id}">${ex.exam_code ? '['+ex.exam_code+'] ' : ''}${esc(ex.title)}</option>`;
        });
        
        const el = document.getElementById('examsList');
        el.innerHTML = html || `<div class="empty-state">
                    <span class="material-symbols-rounded" style="font-size:48px;">quiz</span>
                    <div class="font-bold text-base mt-2">ยังไม่มีข้อสอบ</div>
                    <div class="text-xs mt-1">กดปุ่มสร้างเพื่อเริ่มต้น</div>
                </div>`;
        
        // Populate dropdowns globally
        ['keyExamSelect', 'scanExamSelect', 'resultExamSelect'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = opts;
        });
    }

    function selectExam(id) {
        currentExamId = id;
        switchTab('scanner');
        document.getElementById('scanExamSelect').value = id;
        onScanExamChange();
    }

    function populateSelectors() {
        const options = '<option value="">-- เลือกข้อสอบ --</option>' + 
            currentExams.map(e => `<option value="${e.id}" ${e.id == currentExamId ? 'selected':''}>${e.exam_code ? '['+e.exam_code+'] ' : ''}${esc(e.title)}</option>`).join('');
        
        ['keyExamSelect', 'scanExamSelect', 'resultExamSelect'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = options;
        });
    }

    async function showCreateExamModal() {
        const data = await api('get_subjects'); 
        const subjects = data.data || [];
        
        document.getElementById('newExamSubject').innerHTML = '<option value="">-- เลือกวิชา --</option>' +
            subjects.map(s => `<option value="${s.id}">${esc(s.code)} - ${esc(s.name)}</option>`).join('');
        openModal('createExamModal');
    }

    async function createExam() {
        const subject_id = document.getElementById('newExamSubject').value;
        const title = document.getElementById('newExamTitle').value.trim();
        const exam_code = document.getElementById('newExamCode').value.trim();
        const total_questions = document.getElementById('newExamQuestions').value;
        const choices_count = document.getElementById('newExamChoices').value;
        const points_per_question = document.getElementById('newExamPoints').value;

        if(!subject_id || !title) { alert('กรุณากรอกข้อมูลให้ครบ'); return; }

        const res = await api('create_exam', { subject_id, title, exam_code, total_questions, choices_count, points_per_question }, 'POST');
        if(res.success) {
            closeModal('createExamModal');
            currentExamId = res.exam_id;
            loadExams();
        } else {
            alert(res.error);
        }
    }

    async function deleteExam(id) {
        if(!confirm('ลบข้อสอบนี้? ข้อมูลการตรวจจะถูกลบทั้งหมด')) return;
        await api('delete_exam', { exam_id: id }, 'POST');
        if(currentExamId === id) currentExamId = null;
        loadExams();
    }

    // ==========================================
    // TAB 2: KEYS
    // ==========================================
    async function loadAnswerKeys() {
        const id = document.getElementById('keyExamSelect').value;
        if (!id) {
            document.getElementById('answerKeyGrid').innerHTML = '';
            document.getElementById('quickFillSection').style.display = 'none';
            document.getElementById('saveKeySection').style.display = 'none';
            return;
        }
        currentExamId = parseInt(id);

        const data = await api('get_exam', { exam_id: currentExamId });
        if(!data.success) return;
        const exam = data.data;

        currentAnswerKeys = {};
        (exam.answer_keys || []).forEach(k => { currentAnswerKeys[k.question_no] = k.correct_answer; });

        const letters = 'ABCDE'.slice(0, exam.choices_count);
        let html = '';
        for(let q=1; q<=exam.total_questions; q++) {
            const sel = currentAnswerKeys[q] || '';
            html += `
                <div class="answer-key-item">
                    <span class="q-num">${q}</span>
                    <div class="answer-choices">
                        ${letters.split('').map(c => `
                            <button class="choice-btn ${sel===c?'selected':''}" onclick="setAnswer(${q},'${c}',this)">${c}</button>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        document.getElementById('answerKeyGrid').innerHTML = html;
        document.getElementById('quickFillSection').style.display = 'block';
        document.getElementById('saveKeySection').style.display = 'block';
        document.getElementById('quickFillInput').value = Object.values(currentAnswerKeys).join('');
    }

    function setAnswer(q, ans, btn) {
        if(currentAnswerKeys[q] === ans) {
            delete currentAnswerKeys[q];
            btn.classList.remove('selected');
        } else {
            currentAnswerKeys[q] = ans;
            btn.parentElement.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        }
    }

    function applyQuickFill() {
        const input = document.getElementById('quickFillInput').value.toUpperCase().replace(/[^A-E]/g, '');
        currentAnswerKeys = {};
        for(let i=0; i<input.length; i++) currentAnswerKeys[i+1] = input[i];
        
        document.querySelectorAll('.answer-key-item').forEach((item, idx) => {
            const sel = currentAnswerKeys[idx+1] || '';
            item.querySelectorAll('.choice-btn').forEach(b => b.classList.toggle('selected', b.textContent === sel));
        });
    }

    async function saveAnswerKeys() {
        if(!currentExamId) return;
        const keys = Object.entries(currentAnswerKeys).map(([q,a]) => ({ question_no: parseInt(q), answer: a, points: 1 }));
        if(!keys.length) { alert('กรุณากรอกเฉลย'); return; }

        const res = await api('save_answer_key', { exam_id: currentExamId, keys }, 'POST');
        if(res.success) {
            alert('บันทึกเรียบร้อย');
            loadExams();
        }
    }

    // ==========================================
    // TAB 3: SCANNER
    // ==========================================
    function onScanExamChange() {
        currentExamId = parseInt(document.getElementById('scanExamSelect').value);
        resetScanner();
    }

    function resetScanner() {
        currentScanAnswers = null;
        currentScanImage = null;
        pdfPages = [];
        batchPendingManual = [];
        document.getElementById('scanPreviewImg').style.display = 'none';
        document.getElementById('scannerPlaceholder').style.display = 'flex';
        document.getElementById('btnProcess').style.display = 'none';
        document.getElementById('scanResultPreview').style.display = 'none';
        document.getElementById('pdfPagesContainer').style.display = 'none';
        document.getElementById('batchSummary').style.display = 'none';
        document.getElementById('progressContainer').classList.remove('active');
        document.getElementById('progressText').style.display = 'none';
        stopCamera();
    }

    // Camera
    async function openCamera() {
        try {
            const video = document.getElementById('cameraVideo');
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width:{ideal:1280}, height:{ideal:720} } });
            cameraStream = stream;
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('scannerPlaceholder').style.display = 'none';
            document.getElementById('scanPreviewImg').style.display = 'none';
            
            const btn = document.getElementById('btnCamera');
            btn.innerHTML = '<span class="material-symbols-rounded">camera</span> แชะ!';
            btn.onclick = captureFromCamera;
        } catch(e) { alert('ไม่สามารถเปิดกล้องได้'); }
    }

    function captureFromCamera() {
        const video = document.getElementById('cameraVideo');
        const cvs = document.createElement('canvas');
        cvs.width = video.videoWidth; cvs.height = video.videoHeight;
        cvs.getContext('2d').drawImage(video,0,0);
        
        stopCamera();
        showPreviewImage(cvs.toDataURL('image/jpeg', 0.9));
    }

    function stopCamera() {
        if(cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
        document.getElementById('cameraVideo').style.display = 'none';
        const btn = document.getElementById('btnCamera');
        if(btn) { btn.innerHTML = '<span class="material-symbols-rounded">photo_camera</span> ถ่ายรูป'; btn.onclick = openCamera; }
    }

    // File Uploads
    function handleFileUpload(e) {
        if(!e.target.files[0]) return;
        pdfPages = []; document.getElementById('pdfPagesContainer').style.display='none';
        document.getElementById('batchSummary').style.display='none';
        
        const r = new FileReader();
        r.onload = ev => showPreviewImage(ev.target.result);
        r.readAsDataURL(e.target.files[0]);
    }

    function showPreviewImage(dataUrl) {
        currentScanImage = dataUrl;
        const img = document.getElementById('scanPreviewImg');
        img.src = dataUrl; img.style.display = 'block';
        document.getElementById('scannerPlaceholder').style.display = 'none';
        document.getElementById('btnProcess').style.display = 'flex';
        // Scroll to process button on mobile
        document.getElementById('btnProcess').scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    async function handlePdfUpload(e) {
        if(!e.target.files[0]) return;
        if(!currentExamId) { alert('กรุณาเลือกข้อสอบก่อน'); return; }
        resetScanner();

        document.getElementById('scannerArea').innerHTML = `
            <div class="scanner-placeholder">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                <div class="font-bold">กำลังอ่าน PDF...</div>
            </div>
        `;

        try {
            const pdf = await pdfjsLib.getDocument({data: await e.target.files[0].arrayBuffer()}).promise;
            const t = pdf.numPages;
            const grid = document.getElementById('pdfPagesGrid'); grid.innerHTML = '';
            
            for(let i=1; i<=t; i++) {
                const page = await pdf.getPage(i);
                const cvs = document.createElement('canvas');
                const vp = page.getViewport({scale: 1.5});
                cvs.width = vp.width; cvs.height = vp.height;
                await page.render({canvasContext: cvs.getContext('2d'), viewport: vp}).promise;
                
                const dataUrl = cvs.toDataURL('image/jpeg', 0.8);
                pdfPages.push({ pageNum: i, dataUrl, status: 'pending', result: null });
                
                grid.innerHTML += `
                    <div class="pdf-page-thumb" id="pdf-page-${i}" onclick="previewPdfPage(${i})">
                        <img src="${dataUrl}">
                        <span class="pdf-page-label">หน้า ${i}</span>
                    </div>`;
            }

            document.getElementById('pdfPageCount').textContent = t;
            document.getElementById('pdfPagesContainer').style.display = 'block';
            if(t > 0) showPreviewImage(pdfPages[0].dataUrl);

        } catch(err) { alert('Error PDF: ' + err.message); }
        
        // Restore scanner UI
        document.getElementById('scannerArea').innerHTML = `
            <div class="scanner-placeholder" id="scannerPlaceholder" style="display:none;">
                <span class="material-symbols-rounded">document_scanner</span>
            </div>
            <img id="scanPreviewImg" style="display:block;">
            <canvas id="scanCanvas" style="display:none;"></canvas>
            <video id="cameraVideo" style="display:none;" autoplay playsinline></video>
            <div class="progress-bar-container" id="progressContainer"><div class="progress-bar" id="progressBar"></div></div>
            <div class="progress-text" id="progressText"></div>
        `;
        document.getElementById('scanPreviewImg').src = currentScanImage;
    }

    function previewPdfPage(i) {
        const p = pdfPages.find(x => x.pageNum === i);
        if(p) showPreviewImage(p.dataUrl);
    }

    // Processing Logic
    async function processCurrentImage() {
        if(!currentScanImage || !cvReady || !currentExamId) return;

        const exData = await api('get_exam', {exam_id: currentExamId});
        processor.setConfig({ totalQuestions: exData.data.total_questions, choicesPerQuestion: exData.data.choices_count });

        document.getElementById('progressContainer').classList.add('active');
        document.getElementById('progressText').style.display = 'block';
        document.getElementById('btnProcess').disabled = true;

        try {
            const img = new Image(); img.src = currentScanImage; await new Promise(r => img.onload = r);
            const res = await processor.processSheet(img);
            currentScanAnswers = res.answers;
            if(res.previewImage) document.getElementById('scanPreviewImg').src = res.previewImage;
            
            // Check if sheet has exam code that overrides current dropdown
            let targetExam = exData.data;
            if(res.examCode && !res.examCode.includes('?')) {
                const eMatch = await api('find_exam_by_code', {exam_code: res.examCode});
                if(eMatch.found) {
                    targetExam = eMatch.data;
                    currentExamId = targetExam.id; // Switch context
                    document.getElementById('scanExamSelect').value = targetExam.id;
                }
            }
            
            showScanResults(res, targetExam);
        } catch(e) { alert(e.message); }
        finally {
            document.getElementById('btnProcess').disabled = false;
            document.getElementById('progressContainer').classList.remove('active');
        }
    }

    function toggleManualInput() {
        const a = document.getElementById('manualInputArea');
        a.style.display = a.style.display === 'none' ? 'block' : 'none';
    }

    async function submitManualAnswers() {
        if(!currentExamId) return;
        const val = document.getElementById('manualAnswerInput').value.toUpperCase().replace(/[^A-E\-]/g,'');
        const ex = await api('get_exam', {exam_id: currentExamId});
        
        const ans = {};
        for(let i=0; i<ex.data.total_questions; i++) ans[i+1] = val[i] || '-';
        currentScanAnswers = ans;
        showScanResults({answers: ans, studentId:''}, ex.data);
    }

    async function showScanResults(res, exam) {
        const keys = {};
        (exam.answer_keys || []).forEach(k => keys[k.question_no] = k.correct_answer);

        let c=0, w=0, b=0;
        let html = '';
        for(let q=1; q<=exam.total_questions; q++) {
            const a = res.answers[q] || '-';
            const k = keys[q];
            let cls = 'blank';
            if(a === '-' || !a) { b++; cls='blank'; }
            else if(k && a === k) { c++; cls='correct'; }
            else { w++; cls='wrong'; }
            
            html += `<div class="result-answer-item ${cls}"><span class="q-label">${q}</span>${a}${cls==='wrong'?` (${k||'?'})`:''}</div>`;
        }

        const pts = parseFloat(exam.points_per_question);
        const score = c * pts; const full = exam.total_questions * pts;
        const pct = full > 0 ? Math.round((score/full)*100) : 0;
        const hue = pct>=80 ? 142 : pct>=50 ? 45 : 0;

        const pv = document.getElementById('scanResultPreview');
        pv.style.display = 'block';
        pv.innerHTML = `
            <div class="card" style="margin-top:16px;">
                <div class="score-circle" style="background:hsl(${hue}, 80%, 95%); color:hsl(${hue}, 80%, 35%);">
                    <div class="score-value">${score}</div>
                    <div class="score-label">/ ${full}</div>
                </div>
                
                <div class="exam-stats-row">
                    <div class="exam-stat-card" style="background:var(--success-bg);"><div class="stat-value text-success">${c}</div><div class="stat-label">ถูก</div></div>
                    <div class="exam-stat-card" style="background:var(--danger-bg);"><div class="stat-value text-danger">${w}</div><div class="stat-label">ผิด</div></div>
                </div>
                
                <div style="background:#F1F5F9; border-radius:12px; padding:12px; text-align:center; margin-bottom:16px;">
                    <div class="flex justify-around">
                        <div>
                            <div class="text-xs font-bold text-primary">ชุดข้อสอบ</div>
                            <div class="text-xl font-bold font-mono tracking-widest mt-1">${res.examCode || '-'}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-primary">รหัสนักเรียน</div>
                            <div class="text-xl font-bold font-mono tracking-widest mt-1">${res.studentId || '-'}</div>
                        </div>
                    </div>
                </div>
                
                <div class="font-bold text-sm mb-2">คำตอบ:</div>
                <div class="result-answer-grid">${html}</div>
                
                <button class="btn btn-primary w-full mt-4" onclick="openStudentSelect('${res.studentId||''}')">
                    <span class="material-symbols-rounded">person_add</span> เลือกนักเรียน & บันทึก
                </button>
            </div>`;
        pv.scrollIntoView({behavior:'smooth'});
    }

    // Batch PDF Processing
    async function batchProcessAllPages() {
        if(!cvReady || !pdfPages.length) return;
        
        let fallbackExamId = currentExamId;
        if (!fallbackExamId && currentExams.length > 0) {
            fallbackExamId = currentExams[0].id; // Give it some valid config to start processing
        }
        if(!fallbackExamId) { alert('กรุณาสร้างข้อสอบก่อนทำการสแกน Batch'); return; }

        const fallbackEx = await api('get_exam', {exam_id: fallbackExamId});
        processor.setConfig({ totalQuestions: fallbackEx.data.total_questions, choicesPerQuestion: fallbackEx.data.choices_count });

        const btn = document.getElementById('btnBatchProcess');
        btn.disabled = true; btn.innerHTML = '<div class="spinner" style="width:14px;height:14px;"></div>';
        
        let batch = [], manual = [];
        
        for(let p of pdfPages) {
            const el = document.getElementById(`pdf-page-${p.pageNum}`);
            if(el) el.className = 'pdf-page-thumb processing';
            
            try {
                const img = new Image(); img.src = p.dataUrl; await new Promise(r=>img.onload=r);
                const res = await processor.processSheet(img);
                p.result = res;
                
                let targetExamId = currentExamId; // The one selected in dropdown
                
                // If the sheet has an exam code, find the exam
                if(res.examCode && !res.examCode.includes('?')) {
                    const eData = await api('find_exam_by_code', {exam_code: res.examCode});
                    if(eData.found) targetExamId = eData.data.id;
                }
                
                if(!targetExamId) {
                    throw new Error('ไม่พบรหัสชุดข้อสอบบนกระดาษ และไม่ได้เลือกข้อสอบใน Dropdown ไว้');
                }
                
                const ex = await api('get_exam', {exam_id: targetExamId});
                p.targetExamId = targetExamId;

                let std = null;
                if(res.studentId && !res.studentId.includes('?')) {
                    const l = await api('find_student_by_code', {student_code: res.studentId, subject_id: ex.data.subject_id});
                    if(l.found) std = l.data;
                }
                
                if(std) {
                    batch.push({ exam_id: targetExamId, student_id: std.id, answers: res.answers, image_data: res.scanImage });
                    p.status = 'auto';
                    if(el) { el.className='pdf-page-thumb done'; el.innerHTML+=`<div class="pdf-page-status success">✓ ${std.student_code}</div>`; }
                } else {
                    p.status = 'manual'; manual.push(p);
                    if(el) { el.className='pdf-page-thumb error'; el.innerHTML+=`<div class="pdf-page-status pending">⚠ รอเลือกนักเรียน</div>`; }
                }
            } catch(err) {
                p.status = 'error'; p.errorMsg = err.message;
                if(el) { el.className='pdf-page-thumb error'; el.innerHTML+=`<div class="pdf-page-status fail">✗ Error</div>`; }
            }
        }
        
        let saved = [];
        if(batch.length) {
            const r = await api('batch_save_scan', {exam_id: currentExamId, scans: batch}, 'POST');
            if(r.success) saved = r.results || [];
        }
        
        batchPendingManual = manual;
        showBatchSummary(saved, manual, pdfPages);
        btn.disabled = false; btn.innerHTML = '<span class="material-symbols-rounded">bolt</span> ตรวจทั้งหมด';
        loadExams();
    }

    function showBatchSummary(saved, manual, all) {
        const el = document.getElementById('batchSummary');
        el.style.display = 'block';
        
        let html = `
            <div class="card" style="border:1px solid var(--primary-light);">
                <div class="font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-rounded text-primary">analytics</span> สรุปการตรวจ (Batch)
                </div>
                <div class="flex gap-2 mb-4 text-xs font-bold text-center">
                    <div class="flex-1 bg-white p-2 rounded-lg border">
                        <div class="text-lg text-success">${saved.length}</div> สำเร็จ
                    </div>
                    <div class="flex-1 bg-white p-2 rounded-lg border">
                        <div class="text-lg text-amber-500" style="color:#D97706;">${manual.length}</div> รอเลือก
                    </div>
                    <div class="flex-1 bg-white p-2 rounded-lg border">
                        <div class="text-lg text-danger">${all.filter(x=>x.status==='error').length}</div> ผิดพลาด
                    </div>
                </div>
                <div style="max-height:250px; overflow-y:auto;">
        `;
        
        saved.forEach(r => html+=`<div class="batch-result-item success"><div><span class="material-symbols-rounded text-success" style="font-size:14px;vertical-align:bottom;">check_circle</span> ${r.student_id}</div><div class="font-bold text-success">${r.total_score}/${r.total_possible}</div></div>`);
        
        manual.forEach((p, idx) => html+=`<div class="batch-result-item manual"><div><span class="material-symbols-rounded" style="font-size:14px;vertical-align:bottom;color:#D97706;">warning</span> หน้า ${p.pageNum} (ID: ${p.result?.studentId||'-'})</div><button class="btn btn-primary" style="padding:4px 8px;font-size:11px;" onclick="manualSelectForPage(${idx})">จับคู่</button></div>`);
        
        html += `</div></div>`;
        el.innerHTML = html;
        el.scrollIntoView({behavior:'smooth'});
    }

    async function manualSelectForPage(idx) {
        currentBatchPageIdx = idx;
        const p = batchPendingManual[idx];
        if(!p) return;
        showPreviewImage(p.dataUrl);
        currentScanAnswers = p.result.answers;
        currentScanImage = p.dataUrl;
        
        // Ensure context is correct
        if(p.targetExamId) currentExamId = p.targetExamId;
        
        await openStudentSelect(p.result.studentId||'');
    }

    // Student Select Modal
    async function openStudentSelect(detectedId = '') {
        const ex = await api('get_exam', {exam_id: currentExamId});
        const std = await api('get_students', {subject_id: ex.data.subject_id});
        allStudents = std.data || [];
        selectedStudentId = null;
        
        const search = document.querySelector('.student-search');
        if(detectedId && !detectedId.includes('?')) {
            const m = allStudents.find(s => s.student_code === detectedId);
            if(m) selectedStudentId = m.id;
            search.value = detectedId;
            filterStudents(detectedId);
        } else {
            search.value = '';
            renderStudentList(allStudents);
        }
        openModal('studentSelectModal');
    }

    function renderStudentList(students) {
        document.getElementById('studentList').innerHTML = students.map(s => `
            <div class="student-select-card ${selectedStudentId == s.id ? 'selected':''}" onclick="selectStudent(${s.id}, this)">
                <div class="avatar-sm" style="width:36px;height:36px;border-radius:10px;background:var(--primary-light);color:var(--primary);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:14px;">${(s.first_name||'')[0]||'?'}</div>
                <div>
                    <div class="font-bold text-sm">${esc(s.first_name)} ${esc(s.last_name)}</div>
                    <div class="text-xs text-muted">${esc(s.student_code)}</div>
                </div>
            </div>
        `).join('') || '<div class="text-center text-xs text-muted py-4">ไม่พบข้อมูล</div>';
    }

    function selectStudent(id, el) {
        selectedStudentId = id;
        document.querySelectorAll('.student-select-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
    }

    function filterStudents(q) {
        q = q.toLowerCase();
        renderStudentList(allStudents.filter(s => 
            s.student_code.toLowerCase().includes(q) || s.first_name.toLowerCase().includes(q)
        ));
    }

    async function confirmStudentAndSave() {
        if(!selectedStudentId) { alert('กรุณาเลือกนักเรียน'); return; }
        const r = await api('save_scan', { exam_id: currentExamId, student_id: selectedStudentId, answers: currentScanAnswers, image_data: currentScanImage }, 'POST');
        if(r.success) {
            closeModal('studentSelectModal');
            
            if(currentBatchPageIdx !== null && batchPendingManual[currentBatchPageIdx]) {
                const p = batchPendingManual[currentBatchPageIdx];
                p.status = 'manual-saved';
                const th = document.getElementById(`pdf-page-${p.pageNum}`);
                if(th) { th.className = 'pdf-page-thumb done'; th.innerHTML += `<div class="pdf-page-status success">✓ สำเร็จ</div>`; }
                currentBatchPageIdx = null;
                // Re-render batch summary
                const auto = pdfPages.filter(x=>x.status==='auto' || x.status==='manual-saved');
                const man = pdfPages.filter(x=>x.status==='manual');
                showBatchSummary(auto, man, pdfPages);
            } else {
                alert('บันทึกคะแนนเรียบร้อย');
                resetScanner();
            }
            loadExams();
        } else alert(r.error);
    }

    // ==========================================
    // TAB 4: RESULTS
    // ==========================================
    async function loadResults() {
        const id = document.getElementById('resultExamSelect').value;
        if(!id) { document.getElementById('resultsContent').innerHTML=''; return; }
        
        currentExamId = parseInt(id);
        const data = await api('get_results', {exam_id: id});
        if(!data.success) return;

        const {exam, results, stats} = data;
        let html = `
            <div class="exam-stats-row">
                <div class="exam-stat-card border-t-2" style="border-top-color:var(--primary);">
                    <div class="stat-value text-primary">${stats.count}</div><div class="stat-label">เข้าสอบ</div>
                </div>
                <div class="exam-stat-card border-t-2" style="border-top-color:var(--success);">
                    <div class="stat-value text-success">${stats.mean}</div><div class="stat-label">เฉลี่ย</div>
                </div>
            </div>
            
            <div class="card p-0 mb-4" style="overflow:hidden;">
                <div style="background:var(--surface); padding:12px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between;">
                    <span class="font-bold text-sm">ตารางคะแนน</span>
                </div>
                ${results.map(r => `
                    <div class="flex justify-between items-center p-3 border-b" style="border-bottom:1px solid var(--border);">
                        <div>
                            <div class="font-bold text-sm">${esc(r.first_name)}</div>
                            <div class="text-xs text-muted">${esc(r.student_code)}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold ${r.total_score >= exam.total_questions/2 ? 'text-success':'text-danger'}">${r.total_score}/${r.total_possible}</div>
                            <button class="delete-btn mt-1" onclick="deleteResult(${r.id})"><span class="material-symbols-rounded" style="font-size:14px;">close</span></button>
                        </div>
                    </div>
                `).join('')}
                ${!results.length ? '<div class="text-center text-xs text-muted p-4">ไม่มีข้อมูล</div>':''}
            </div>
        `;
        document.getElementById('resultsContent').innerHTML = html;
    }

    async function deleteResult(id) {
        if(confirm('ลบผลสแกนนี้?')) {
            await api('delete_result', {result_id: id}, 'POST');
            loadResults();
        }
    }

    function esc(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

    document.addEventListener('DOMContentLoaded', loadExams);
    </script>
</body>
</html>
