<?php
$page_title = 'Exam Scanner';
require_once 'includes/db.php';
require_login('admin');
require_once 'api/exam-db.php';
exam_auto_migrate($pdo);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — ตรวจข้อสอบ</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        /* ===== Exam Scanner — Admin Layout Styles ===== */

        /* Tab Navigation */
        .exam-tabs {
            display: flex;
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 4px;
            gap: 4px;
            box-shadow: var(--shadow-xs);
            margin-bottom: 20px;
            border: 1px solid var(--border-light);
        }
        .exam-tab {
            flex: 1;
            padding: 12px 16px;
            border: none;
            background: transparent;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .exam-tab:hover { background: var(--border-light); }
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
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-xs);
            border: 1px solid var(--border-light);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .exam-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-mid);
        }
        .exam-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .exam-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .exam-badge.blue { background: var(--primary-light); color: var(--primary); }
        .exam-badge.green { background: var(--success-bg); color: var(--success); }
        .exam-badge.amber { background: var(--warning-bg); color: var(--warning); }
        .exam-badge.red { background: var(--danger-bg); color: var(--danger); }

        /* Answer Key Grid */
        .answer-key-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        .answer-key-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 8px;
            background: var(--border-light);
            border-radius: var(--radius-sm);
        }
        .answer-key-item .q-num {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
        }
        .answer-choices { display: flex; gap: 3px; }
        .choice-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--surface);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-family: inherit;
        }
        .choice-btn:hover { border-color: var(--primary); color: var(--primary); }
        .choice-btn.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        /* Scanner Area */
        .scanner-area {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            border-radius: var(--radius-xl);
            padding: 32px;
            text-align: center;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .scanner-area img, .scanner-area video, .scanner-area canvas {
            max-width: 100%;
            max-height: 400px;
            border-radius: var(--radius-md);
        }
        .scanner-placeholder { color: rgba(255,255,255,0.6); }
        .scanner-placeholder .material-symbols-rounded {
            font-size: 64px;
            margin-bottom: 12px;
            color: rgba(255,255,255,0.3);
        }

        /* Scan Actions */
        .scan-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .scan-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            border: none;
            font-family: inherit;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .scan-btn.primary { background: var(--primary); color: #fff; }
        .scan-btn.primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .scan-btn.secondary { background: var(--border-light); color: var(--text-primary); border: 1px solid var(--border); }
        .scan-btn.secondary:hover { background: var(--border); }
        .scan-btn.success { background: var(--success); color: #fff; }
        .scan-btn.success:hover { opacity: 0.9; }

        /* Progress Bar */
        .progress-bar-container {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 16px;
            display: none;
        }
        .progress-bar-container.active { display: block; }
        .progress-bar {
            height: 6px;
            background: linear-gradient(90deg, #3B82F6, #06B6D4);
            border-radius: 10px;
            width: 0%;
            transition: width 0.3s ease;
        }
        .progress-text {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            margin-top: 8px;
        }

        /* PDF Thumbnails */
        .pdf-pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        .pdf-page-thumb {
            position: relative;
            background: var(--surface);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .pdf-page-thumb:hover { border-color: var(--primary-mid); }
        .pdf-page-thumb.processing { border-color: var(--warning); }
        .pdf-page-thumb.done { border-color: var(--success); }
        .pdf-page-thumb.error { border-color: var(--danger); }
        .pdf-page-thumb canvas, .pdf-page-thumb img {
            width: 100%;
            height: auto;
            display: block;
        }
        .pdf-page-label {
            position: absolute;
            top: 6px;
            left: 6px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .pdf-page-status {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 6px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
        }
        .pdf-page-status.success { background: rgba(22,163,74,0.9); color: #fff; }
        .pdf-page-status.fail { background: rgba(220,38,38,0.9); color: #fff; }
        .pdf-page-status.pending { background: rgba(217,119,6,0.9); color: #fff; }

        /* Batch Summary */
        .batch-summary {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-top: 20px;
            border: 1px solid var(--border-light);
        }
        .batch-summary-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .batch-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .batch-results-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .batch-result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 4px;
            font-size: 13px;
        }
        .batch-result-item.success { background: var(--success-bg); }
        .batch-result-item.fail { background: var(--danger-bg); }
        .batch-result-item.manual { background: var(--warning-bg); }

        /* Result Preview */
        .result-preview {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-top: 20px;
            border: 1px solid var(--border-light);
        }
        .result-answer-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 6px;
            margin: 12px 0;
        }
        .result-answer-item {
            text-align: center;
            padding: 8px 4px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 700;
        }
        .result-answer-item.correct { background: var(--success-bg); color: var(--success); }
        .result-answer-item.wrong { background: var(--danger-bg); color: var(--danger); }
        .result-answer-item.blank { background: var(--border-light); color: var(--text-light); }
        .result-answer-item .q-label { font-size: 9px; font-weight: 500; color: var(--text-light); display: block; }

        /* Score Circle */
        .score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .score-circle .score-value { font-size: 36px; font-weight: 800; }
        .score-circle .score-label { font-size: 12px; opacity: 0.8; }

        /* Stats Row */
        .exam-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .exam-stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 16px;
            text-align: center;
            box-shadow: var(--shadow-xs);
            border: 1px solid var(--border-light);
        }
        .exam-stat-card .stat-value {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }
        .exam-stat-card .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        /* Chart Container */
        .chart-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-xs);
            border: 1px solid var(--border-light);
        }

        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }
        .results-table th {
            background: var(--border-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .results-table th:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
        .results-table th:last-child { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
        .results-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-light);
        }
        .results-table tr:hover td { background: var(--border-light); }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: var(--surface);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 520px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Quick Fill Input */
        .quick-fill-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-family: 'Courier New', monospace;
            font-size: 16px;
            letter-spacing: 2px;
            text-transform: uppercase;
            box-sizing: border-box;
        }
        .quick-fill-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Student Search */
        .student-search {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 12px;
            box-sizing: border-box;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") 12px center no-repeat;
        }
        .student-search:focus { outline: none; border-color: var(--primary); }

        .student-select-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--border-light);
            border-radius: var(--radius-md);
            margin-bottom: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.15s ease;
        }
        .student-select-card:hover { background: var(--primary-light); }
        .student-select-card.selected { border-color: var(--primary); background: var(--primary-light); }

        /* Form helpers */
        .form-label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--text-secondary); }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-group { margin-bottom: 16px; }

        /* Button helpers */
        .btn { padding: 10px 20px; border-radius: var(--radius-sm); border: none; font-family: inherit; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: var(--surface); color: var(--text-primary); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--border-light); }
        .w-full { width: 100%; justify-content: center; }
        .mt-4 { margin-top: 16px; }

        /* Delete button */
        .delete-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            padding: 4px;
            border-radius: var(--radius-sm);
            transition: background 0.15s;
        }
        .delete-btn:hover { background: var(--danger-bg); }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-state .material-symbols-rounded {
            font-size: 56px;
            color: var(--text-light);
            margin-bottom: 12px;
        }

        /* OpenCV loading */
        .opencv-loading {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 8px 16px;
            background: linear-gradient(90deg, #3B82F6, #06B6D4);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            z-index: 2000;
            transition: transform 0.3s ease;
        }
        .opencv-loading.hidden { transform: translateY(-100%); }

        /* Spinner */
        .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive: collapse grid on smaller main content */
        @media (max-width: 900px) {
            .exam-stats-row { grid-template-columns: repeat(2, 1fr); }
            .result-answer-grid { grid-template-columns: repeat(5, 1fr); }
        }

        /* Two column layout for scanner */
        .scanner-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 1100px) {
            .scanner-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">

            <!-- Page Header -->
            <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div>
                    <h1 class="page-title" style="font-size:28px;">ตรวจข้อสอบ</h1>
                    <p style="font-size:14px;color:var(--text-secondary);margin-top:4px;">สร้างข้อสอบ กรอกเฉลย สแกนกระดาษคำตอบ ตรวจอัตโนมัติ</p>
                </div>
                <a href="../assets/downloads/answer_sheet_template.pdf" target="_blank" class="btn btn-outline" style="text-decoration:none;">
                    <span class="material-symbols-rounded">print</span> โหลดใบคำตอบ (PDF)
                </a>
            </div>

            <!-- OpenCV Loading Bar -->
            <div id="opencvLoading" class="opencv-loading">
                <span class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">hourglass_top</span>
                กำลังโหลด OpenCV.js...
            </div>

            <!-- Tabs -->
            <div class="exam-tabs">
                <button class="exam-tab active" onclick="switchTab('exams')" id="tab-exams">
                    <span class="material-symbols-rounded">quiz</span>
                    ข้อสอบ
                </button>
                <button class="exam-tab" onclick="switchTab('keys')" id="tab-keys">
                    <span class="material-symbols-rounded">key</span>
                    เฉลย
                </button>
                <button class="exam-tab" onclick="switchTab('scanner')" id="tab-scanner">
                    <span class="material-symbols-rounded">document_scanner</span>
                    สแกน / PDF
                </button>
                <button class="exam-tab" onclick="switchTab('results')" id="tab-results">
                    <span class="material-symbols-rounded">analytics</span>
                    ผลคะแนน
                </button>
            </div>

            <!-- ===== TAB 1: EXAMS ===== -->
            <div id="panel-exams" class="tab-panel active">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="font-size:18px;font-weight:700;">รายการข้อสอบ</h3>
                    <button class="btn btn-primary" onclick="showCreateExamModal()">
                        <span class="material-symbols-rounded" style="font-size:18px;">add</span> สร้างใหม่
                    </button>
                </div>
                <div id="examsList"></div>
            </div>

            <!-- ===== TAB 2: ANSWER KEYS ===== -->
            <div id="panel-keys" class="tab-panel">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="font-size:18px;font-weight:700;">กรอกเฉลย</h3>
                </div>

                <div style="margin-bottom:16px;">
                    <select id="keyExamSelect" class="form-input" onchange="loadAnswerKeys()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>

                <div id="quickFillSection" style="display:none;margin-bottom:16px;">
                    <label class="form-label">Quick Fill (พิมพ์เฉลยต่อเนื่อง เช่น ABCDEABC...)</label>
                    <input type="text" id="quickFillInput" class="quick-fill-input" placeholder="ABCDEABCDE..." oninput="applyQuickFill()">
                </div>

                <div id="answerKeyGrid" class="answer-key-grid"></div>

                <div id="saveKeySection" style="display:none;margin-top:16px;">
                    <button class="btn btn-primary w-full" onclick="saveAnswerKeys()" style="border-radius:var(--radius-md);">
                        <span class="material-symbols-rounded">save</span> บันทึกเฉลย
                    </button>
                </div>
            </div>

            <!-- ===== TAB 3: SCANNER ===== -->
            <div id="panel-scanner" class="tab-panel">
                <div style="margin-bottom:16px;">
                    <select id="scanExamSelect" class="form-input" onchange="onScanExamChange()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>

                <div class="scanner-layout">
                    <!-- Left: Scanner Area -->
                    <div>
                        <div class="scanner-area" id="scannerArea">
                            <div class="scanner-placeholder" id="scannerPlaceholder">
                                <span class="material-symbols-rounded">document_scanner</span>
                                <div style="font-size:15px;font-weight:600;">อัปโหลดกระดาษคำตอบ</div>
                                <div style="font-size:13px;margin-top:6px;">รองรับ JPG, PNG และ <strong>PDF</strong> (หลายหน้า)</div>
                            </div>
                            <img id="scanPreviewImg" style="display:none;">
                            <canvas id="scanCanvas" style="display:none;"></canvas>
                            <video id="cameraVideo" style="display:none;" autoplay playsinline></video>

                            <!-- Progress -->
                            <div class="progress-bar-container" id="progressContainer">
                                <div class="progress-bar" id="progressBar"></div>
                            </div>
                            <div class="progress-text" id="progressText" style="display:none;"></div>
                        </div>

                        <!-- Scan Actions -->
                        <div class="scan-actions">
                            <button class="scan-btn secondary" onclick="openCamera()" id="btnCamera">
                                <span class="material-symbols-rounded">photo_camera</span> กล้อง
                            </button>
                            <label class="scan-btn secondary" style="margin:0;cursor:pointer;">
                                <span class="material-symbols-rounded">image</span> รูปภาพ
                                <input type="file" accept="image/*" onchange="handleFileUpload(event)" style="display:none;" id="fileInputImage">
                            </label>
                            <label class="scan-btn primary" style="margin:0;cursor:pointer;">
                                <span class="material-symbols-rounded">picture_as_pdf</span> PDF
                                <input type="file" accept=".pdf,application/pdf" onchange="handlePdfUpload(event)" style="display:none;" id="fileInputPdf">
                            </label>
                            <button class="scan-btn primary" onclick="processCurrentImage()" id="btnProcess" style="display:none;">
                                <span class="material-symbols-rounded">play_arrow</span> ตรวจ
                            </button>
                        </div>

                        <!-- Manual Input -->
                        <div style="margin-top:12px;">
                            <button class="scan-btn secondary w-full" onclick="toggleManualInput()">
                                <span class="material-symbols-rounded">edit</span> กรอกคำตอบด้วยมือ
                            </button>
                            <div id="manualInputArea" style="display:none;margin-top:12px;">
                                <label class="form-label">พิมพ์คำตอบต่อเนื่อง (เช่น ABCDEABC...)</label>
                                <input type="text" id="manualAnswerInput" class="quick-fill-input" placeholder="ABCDEABCDE...">
                                <button class="btn btn-primary w-full mt-4" onclick="submitManualAnswers()" style="border-radius:var(--radius-md);">
                                    <span class="material-symbols-rounded">check</span> ใช้คำตอบนี้
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Results / PDF Pages -->
                    <div>
                        <!-- PDF Pages Grid -->
                        <div id="pdfPagesContainer" style="display:none;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                <h4 style="font-size:15px;font-weight:700;">
                                    <span class="material-symbols-rounded" style="font-size:18px;vertical-align:middle;color:var(--primary);">picture_as_pdf</span>
                                    หน้า PDF (<span id="pdfPageCount">0</span> หน้า)
                                </h4>
                                <button class="btn btn-primary" onclick="batchProcessAllPages()" id="btnBatchProcess">
                                    <span class="material-symbols-rounded" style="font-size:18px;">bolt</span> ตรวจทั้งหมด
                                </button>
                            </div>
                            <div id="pdfPagesGrid" class="pdf-pages-grid"></div>
                        </div>

                        <!-- Batch Summary -->
                        <div id="batchSummary" style="display:none;"></div>

                        <!-- Single Scan Result Preview -->
                        <div id="scanResultPreview" style="display:none;"></div>
                    </div>
                </div>
            </div>

            <!-- ===== TAB 4: RESULTS ===== -->
            <div id="panel-results" class="tab-panel">
                <div style="margin-bottom:16px;">
                    <select id="resultExamSelect" class="form-input" onchange="loadResults()">
                        <option value="">-- เลือกข้อสอบ --</option>
                    </select>
                </div>
                <div id="resultsContent"></div>
            </div>

        </div><!-- .main-content-desktop -->
    </div><!-- .main-wrapper -->

    <!-- Create Exam Modal -->
    <div class="modal-overlay" id="createExamModal">
        <div class="modal-content">
            <h3 style="font-size:18px;font-weight:700;margin-bottom:24px;">สร้างข้อสอบใหม่</h3>

            <div class="form-group">
                <label class="form-label">วิชา</label>
                <select id="newExamSubject" class="form-input"></select>
            </div>
            <div class="form-group">
                <label class="form-label">ชื่อข้อสอบ</label>
                <div style="display:flex;gap:12px;">
                    <input type="text" id="newExamTitle" class="form-input" style="flex:1;" placeholder="เช่น สอบกลางภาค 1/2569">
                    <input type="text" id="newExamCode" class="form-input" style="width:100px;" placeholder="รหัส (เช่น 01)" maxlength="2">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">จำนวนข้อ</label>
                    <input type="number" id="newExamQuestions" class="form-input" value="50" min="5" max="200">
                </div>
                <div class="form-group">
                    <label class="form-label">ตัวเลือก</label>
                    <select id="newExamChoices" class="form-input">
                        <option value="4">4 (A-D)</option>
                        <option value="5" selected>5 (A-E)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">คะแนนต่อข้อ</label>
                <input type="number" id="newExamPoints" class="form-input" value="1" min="0.5" step="0.5">
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button class="btn btn-outline w-full" onclick="closeModal('createExamModal')" style="border-radius:var(--radius-md);">ยกเลิก</button>
                <button class="btn btn-primary w-full" onclick="createExam()" style="border-radius:var(--radius-md);">สร้าง</button>
            </div>
        </div>
    </div>

    <!-- Student Select Modal -->
    <div class="modal-overlay" id="studentSelectModal">
        <div class="modal-content">
            <h3 style="font-size:18px;font-weight:700;margin-bottom:16px;">เลือกนักเรียน</h3>
            <input type="text" class="student-search" placeholder="ค้นหาชื่อหรือรหัส..." oninput="filterStudents(this.value)">
            <div id="studentList" style="max-height:400px;overflow-y:auto;"></div>
            <button class="btn btn-primary w-full mt-4" onclick="confirmStudentAndSave()" style="border-radius:var(--radius-md);" id="btnConfirmStudent">
                <span class="material-symbols-rounded">check</span> ยืนยันและบันทึก
            </button>
        </div>
    </div>

    <!-- OpenCV.js -->
    <script async src="https://docs.opencv.org/4.9.0/opencv.js" onload="onOpenCvReady()" id="opencvScript"></script>

    <!-- Sheet Processor -->
    <script src="js/exam-sheet-processor.js"></script>

    <script>
    // ===== PDF.js Config =====
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    // ===== Global State =====
    const API = 'api/exam-api.php';
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

    // PDF state
    let pdfPages = []; // Array of { canvas, dataUrl, pageNum, status, result }
    let batchPendingManual = []; // pages needing manual student selection

    // ===== OpenCV Ready =====
    function onOpenCvReady() {
        cvReady = true;
        processor = new SheetProcessor();
        processor.onProgress = (msg, pct) => {
            document.getElementById('progressText').textContent = msg;
            document.getElementById('progressBar').style.width = pct + '%';
        };
        document.getElementById('opencvLoading').classList.add('hidden');
        setTimeout(() => document.getElementById('opencvLoading').remove(), 500);
    }

    // ===== Tab Navigation =====
    function switchTab(tab) {
        document.querySelectorAll('.exam-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');

        if (tab === 'exams') loadExams();
        if (tab === 'keys') populateExamSelectors();
        if (tab === 'scanner') populateExamSelectors();
        if (tab === 'results') populateExamSelectors();
    }

    // ===== API Helper =====
    async function api(action, params = {}, method = 'GET') {
        let url = `${API}?action=${action}`;
        let options = { method };

        if (method === 'GET') {
            Object.keys(params).forEach(k => url += `&${k}=${encodeURIComponent(params[k])}`);
        } else {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(params);
        }

        const res = await fetch(url, options);
        return res.json();
    }

    // ===== EXAMS =====
    async function loadExams() {
        const data = await api('list_exams');
        currentExams = data.data || [];
        populateExamSelectors();
        renderExamsList();
    }

    function renderExamsList() {
        const el = document.getElementById('examsList');
        if (!currentExams.length) {
            el.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-rounded">quiz</span>
                    <div style="font-weight:700;font-size:16px;margin-bottom:4px;">ยังไม่มีข้อสอบ</div>
                    <div style="font-size:14px;">กดปุ่ม "สร้างใหม่" เพื่อเริ่มต้น</div>
                </div>`;
            return;
        }

        el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:12px;">` +
            currentExams.map(e => `
            <div class="exam-card" onclick="selectExam(${e.id})">
                <div class="exam-card-header">
                    <div>
                        <div style="font-weight:700;font-size:16px;">
                            ${escHtml(e.title)}
                            ${e.exam_code ? `<span class="badge" style="background:var(--primary-light);color:var(--primary);font-size:10px;padding:2px 6px;margin-left:4px;">ชุด ${e.exam_code}</span>` : ''}
                        </div>
                        <div style="font-size:13px;color:var(--text-secondary);margin-top:2px;">
                            ${escHtml(e.subject_code)} — ${escHtml(e.subject_name)}
                        </div>
                    </div>
                    <button class="delete-btn" onclick="event.stopPropagation();deleteExam(${e.id})" title="ลบ">
                        <span class="material-symbols-rounded" style="font-size:18px;">delete</span>
                    </button>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                    <span class="exam-badge blue">${e.total_questions} ข้อ</span>
                    <span class="exam-badge ${e.keys_count > 0 ? 'green' : 'amber'}">
                        ${e.keys_count > 0 ? '✓ มีเฉลย' : '✏️ ยังไม่มีเฉลย'}
                    </span>
                    <span class="exam-badge ${e.scans_count > 0 ? 'green' : 'blue'}">
                        📄 ${e.scans_count} ใบ
                    </span>
                </div>
            </div>
        `).join('') + `</div>`;
    }

    function selectExam(examId) {
        currentExamId = examId;
        switchTab('keys');
        document.getElementById('keyExamSelect').value = examId;
        loadAnswerKeys();
    }

    function populateExamSelectors() {
        const selectors = ['keyExamSelect', 'scanExamSelect', 'resultExamSelect'];
        const options = '<option value="">-- เลือกข้อสอบ --</option>' +
            currentExams.map(e => `<option value="${e.id}" ${e.id == currentExamId ? 'selected' : ''}>${e.exam_code ? '['+e.exam_code+'] ' : ''}${escHtml(e.title)} (${escHtml(e.subject_code)})</option>`).join('');

        selectors.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = options;
        });
    }

    async function showCreateExamModal() {
        const data = await api('get_subjects');
        const subjects = data.data || [];
        document.getElementById('newExamSubject').innerHTML =
            '<option value="">-- เลือกวิชา --</option>' +
            subjects.map(s => `<option value="${s.id}">${escHtml(s.code)} — ${escHtml(s.name)}</option>`).join('');

        openModal('createExamModal');
    }

    async function createExam() {
        const subject_id = document.getElementById('newExamSubject').value;
        const title = document.getElementById('newExamTitle').value.trim();
        const exam_code = document.getElementById('newExamCode').value.trim();
        const total_questions = document.getElementById('newExamQuestions').value;
        const choices_count = document.getElementById('newExamChoices').value;
        const points_per_question = document.getElementById('newExamPoints').value;

        if (!subject_id || !title) {
            alert('กรุณากรอกข้อมูลให้ครบ');
            return;
        }

        const result = await api('create_exam', { subject_id, title, exam_code, total_questions, choices_count, points_per_question }, 'POST');
        if (result.success) {
            closeModal('createExamModal');
            currentExamId = result.exam_id;
            loadExams();
        } else {
            alert('Error: ' + result.error);
        }
    }

    async function deleteExam(examId) {
        if (!confirm('ลบข้อสอบนี้? (ข้อมูลเฉลยและผลสแกนจะถูกลบด้วย)')) return;
        await api('delete_exam', { exam_id: examId }, 'POST');
        if (currentExamId === examId) currentExamId = null;
        loadExams();
    }

    // ===== ANSWER KEYS =====
    async function loadAnswerKeys() {
        const examId = document.getElementById('keyExamSelect').value;
        if (!examId) {
            document.getElementById('answerKeyGrid').innerHTML = '';
            document.getElementById('quickFillSection').style.display = 'none';
            document.getElementById('saveKeySection').style.display = 'none';
            return;
        }
        currentExamId = parseInt(examId);

        const data = await api('get_exam', { exam_id: examId });
        if (!data.success) return;

        const exam = data.data;
        const keys = exam.answer_keys || [];
        currentAnswerKeys = {};
        keys.forEach(k => { currentAnswerKeys[k.question_no] = k.correct_answer; });

        const choiceLabels = 'ABCDE'.slice(0, exam.choices_count);
        let html = '';
        for (let q = 1; q <= exam.total_questions; q++) {
            const selected = currentAnswerKeys[q] || '';
            html += `
                <div class="answer-key-item">
                    <span class="q-num">${q}</span>
                    <div class="answer-choices">
                        ${choiceLabels.split('').map(c => `
                            <button type="button" class="choice-btn ${selected === c ? 'selected' : ''}"
                                onclick="setAnswer(${q}, '${c}', this)">${c}</button>
                        `).join('')}
                    </div>
                </div>`;
        }

        document.getElementById('answerKeyGrid').innerHTML = html;
        document.getElementById('quickFillSection').style.display = 'block';
        document.getElementById('saveKeySection').style.display = 'block';
        document.getElementById('quickFillInput').value = Object.values(currentAnswerKeys).join('');
    }

    function setAnswer(qNum, answer, btn) {
        if (currentAnswerKeys[qNum] === answer) {
            delete currentAnswerKeys[qNum];
            btn.classList.remove('selected');
        } else {
            currentAnswerKeys[qNum] = answer;
            btn.parentElement.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        }
    }

    function applyQuickFill() {
        const input = document.getElementById('quickFillInput').value.toUpperCase().replace(/[^A-E]/g, '');
        currentAnswerKeys = {};

        for (let i = 0; i < input.length; i++) {
            currentAnswerKeys[i + 1] = input[i];
        }

        document.querySelectorAll('.answer-key-item').forEach((item, idx) => {
            const q = idx + 1;
            const selected = currentAnswerKeys[q] || '';
            item.querySelectorAll('.choice-btn').forEach(btn => {
                btn.classList.toggle('selected', btn.textContent === selected);
            });
        });
    }

    async function saveAnswerKeys() {
        if (!currentExamId) return;

        const keys = Object.entries(currentAnswerKeys).map(([qNo, answer]) => ({
            question_no: parseInt(qNo),
            answer: answer,
            points: 1
        }));

        if (keys.length === 0) {
            alert('กรุณากรอกเฉลยอย่างน้อย 1 ข้อ');
            return;
        }

        const result = await api('save_answer_key', { exam_id: currentExamId, keys }, 'POST');
        if (result.success) {
            alert(`บันทึกเฉลย ${result.count} ข้อ เรียบร้อย ✓`);
            loadExams();
        } else {
            alert('Error: ' + result.error);
        }
    }

    // ===== SCANNER =====
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
        document.getElementById('scanCanvas').style.display = 'none';
        document.getElementById('scannerPlaceholder').style.display = 'flex';
        document.getElementById('btnProcess').style.display = 'none';
        document.getElementById('scanResultPreview').style.display = 'none';
        document.getElementById('pdfPagesContainer').style.display = 'none';
        document.getElementById('batchSummary').style.display = 'none';
        document.getElementById('progressContainer').classList.remove('active');
        document.getElementById('progressText').style.display = 'none';
        stopCamera();
    }

    // ----- Single Image Upload -----
    function handleFileUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        // Reset PDF state
        pdfPages = [];
        document.getElementById('pdfPagesContainer').style.display = 'none';
        document.getElementById('batchSummary').style.display = 'none';

        const reader = new FileReader();
        reader.onload = (ev) => {
            showPreviewImage(ev.target.result);
        };
        reader.readAsDataURL(file);
    }

    function showPreviewImage(dataUrl) {
        currentScanImage = dataUrl;
        const img = document.getElementById('scanPreviewImg');
        img.src = dataUrl;
        img.style.display = 'block';
        document.getElementById('scannerPlaceholder').style.display = 'none';
        document.getElementById('btnProcess').style.display = 'flex';
    }

    // ----- PDF Upload & Processing -----
    async function handlePdfUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!currentExamId) {
            alert('กรุณาเลือกข้อสอบก่อน');
            e.target.value = '';
            return;
        }

        // Reset state
        pdfPages = [];
        currentScanImage = null;
        document.getElementById('scanPreviewImg').style.display = 'none';
        document.getElementById('btnProcess').style.display = 'none';
        document.getElementById('scanResultPreview').style.display = 'none';
        document.getElementById('batchSummary').style.display = 'none';
        document.getElementById('scannerPlaceholder').style.display = 'none';

        // Show loading in scanner area
        const scannerArea = document.getElementById('scannerArea');
        scannerArea.innerHTML = `
            <div class="scanner-placeholder">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                <div style="font-size:14px;font-weight:600;">กำลังอ่านไฟล์ PDF...</div>
                <div style="font-size:12px;margin-top:4px;" id="pdfLoadStatus">โปรดรอสักครู่</div>
            </div>
        `;

        try {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const totalPages = pdf.numPages;

            document.getElementById('pdfLoadStatus').textContent = `พบ ${totalPages} หน้า กำลังแปลง...`;

            const grid = document.getElementById('pdfPagesGrid');
            grid.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const page = await pdf.getPage(i);
                const scale = 1.5;
                const viewport = page.getViewport({ scale });

                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                const ctx = canvas.getContext('2d');

                await page.render({ canvasContext: ctx, viewport }).promise;

                const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

                pdfPages.push({
                    pageNum: i,
                    dataUrl: dataUrl,
                    status: 'pending',
                    result: null
                });

                // Create thumbnail
                const thumb = document.createElement('div');
                thumb.className = 'pdf-page-thumb';
                thumb.id = `pdf-page-${i}`;
                thumb.innerHTML = `
                    <img src="${dataUrl}" alt="Page ${i}">
                    <span class="pdf-page-label">หน้า ${i}</span>
                `;
                thumb.onclick = () => previewPdfPage(i);
                grid.appendChild(thumb);
            }

            // Restore scanner area
            scannerArea.innerHTML = `
                <div class="scanner-placeholder" id="scannerPlaceholder" style="display:none;">
                    <span class="material-symbols-rounded">document_scanner</span>
                    <div style="font-size:15px;font-weight:600;">อัปโหลดกระดาษคำตอบ</div>
                    <div style="font-size:13px;margin-top:6px;">รองรับ JPG, PNG และ <strong>PDF</strong> (หลายหน้า)</div>
                </div>
                <img id="scanPreviewImg" style="display:none;">
                <canvas id="scanCanvas" style="display:none;"></canvas>
                <video id="cameraVideo" style="display:none;" autoplay playsinline></video>
                <div class="progress-bar-container" id="progressContainer">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText" style="display:none;"></div>
            `;

            // Show first page preview
            if (pdfPages.length > 0) {
                showPreviewImage(pdfPages[0].dataUrl);
            }

            // Show PDF pages container
            document.getElementById('pdfPagesContainer').style.display = 'block';
            document.getElementById('pdfPageCount').textContent = totalPages;

        } catch (err) {
            console.error('PDF load error:', err);
            alert('เกิดข้อผิดพลาดในการอ่าน PDF: ' + err.message);
            // Restore scanner area
            scannerArea.innerHTML = `
                <div class="scanner-placeholder" id="scannerPlaceholder">
                    <span class="material-symbols-rounded">document_scanner</span>
                    <div style="font-size:15px;font-weight:600;">อัปโหลดกระดาษคำตอบ</div>
                    <div style="font-size:13px;margin-top:6px;">รองรับ JPG, PNG และ <strong>PDF</strong> (หลายหน้า)</div>
                </div>
                <img id="scanPreviewImg" style="display:none;">
                <canvas id="scanCanvas" style="display:none;"></canvas>
                <video id="cameraVideo" style="display:none;" autoplay playsinline></video>
                <div class="progress-bar-container" id="progressContainer">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText" style="display:none;"></div>
            `;
        }
        e.target.value = '';
    }

    function previewPdfPage(pageNum) {
        const page = pdfPages.find(p => p.pageNum === pageNum);
        if (page) {
            showPreviewImage(page.dataUrl);
        }
    }

    // ----- Batch Process All PDF Pages -----
    async function batchProcessAllPages() {
        if (!cvReady) {
            alert('OpenCV.js ยังโหลดไม่เสร็จ กรุณารอสักครู่...');
            return;
        }
        
        let fallbackExamId = currentExamId;
        if (!fallbackExamId && currentExams.length > 0) {
            fallbackExamId = currentExams[0].id;
        }
        if (!fallbackExamId) {
            alert('กรุณาสร้างข้อสอบก่อนทำการสแกน Batch');
            return;
        }
        if (pdfPages.length === 0) {
            alert('ไม่มีหน้า PDF ให้ตรวจ');
            return;
        }

        const fallbackEx = await api('get_exam', { exam_id: fallbackExamId });
        if (!fallbackEx.success) { alert(fallbackEx.error); return; }

        processor.setConfig({
            totalQuestions: fallbackEx.data.total_questions,
            choicesPerQuestion: fallbackEx.data.choices_count
        });

        document.getElementById('btnBatchProcess').disabled = true;
        document.getElementById('btnBatchProcess').innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;"></div> กำลังตรวจ...';

        const batchScans = [];
        const manualNeeded = [];
        let processedCount = 0;

        for (const page of pdfPages) {
            const thumbEl = document.getElementById(`pdf-page-${page.pageNum}`);
            if (thumbEl) {
                thumbEl.className = 'pdf-page-thumb processing';
                // Remove old status labels
                const oldStatus = thumbEl.querySelector('.pdf-page-status');
                if (oldStatus) oldStatus.remove();
            }

            try {
                // Create temp image element
                const img = new Image();
                img.src = page.dataUrl;
                await new Promise(r => img.onload = r);

                const result = await processor.processSheet(img);
                page.result = result;

                let targetExamId = currentExamId;
                
                if (result.examCode && !result.examCode.includes('?')) {
                    const eMatch = await api('find_exam_by_code', {exam_code: result.examCode});
                    if (eMatch.found) {
                        targetExamId = eMatch.data.id;
                    }
                }
                
                if (!targetExamId) {
                    throw new Error('ไม่พบรหัสชุดข้อสอบบนกระดาษ และไม่ได้เลือกข้อสอบใน Dropdown ไว้');
                }
                
                const ex = await api('get_exam', {exam_id: targetExamId});
                page.targetExamId = targetExamId;

                // Try to find student by scanned ID
                let studentFound = null;
                if (result.studentId && !result.studentId.includes('?')) {
                    const lookupRes = await api('find_student_by_code', {
                        student_code: result.studentId,
                        subject_id: ex.data.subject_id
                    });
                    if (lookupRes.found) {
                        studentFound = lookupRes.data;
                    }
                }

                if (studentFound) {
                    // Auto-matched!
                    batchScans.push({
                        exam_id: targetExamId,
                        student_id: studentFound.id,
                        answers: result.answers,
                        image_data: result.scanImage || null
                    });
                    page.status = 'auto';
                    page.studentName = `${studentFound.first_name} ${studentFound.last_name}`;
                    page.studentCode = studentFound.student_code;

                    if (thumbEl) {
                        thumbEl.className = 'pdf-page-thumb done';
                        thumbEl.innerHTML += `<div class="pdf-page-status success">✓ ${escHtml(studentFound.student_code)}</div>`;
                    }
                } else {
                    // Need manual selection
                    page.status = 'manual';
                    manualNeeded.push(page);

                    if (thumbEl) {
                        thumbEl.className = 'pdf-page-thumb error';
                        thumbEl.innerHTML += `<div class="pdf-page-status pending">⚠ ต้องเลือกนักเรียน</div>`;
                    }
                }
            } catch (err) {
                console.error(`Error processing page ${page.pageNum}:`, err);
                page.status = 'error';
                page.errorMsg = err.message;

                if (thumbEl) {
                    thumbEl.className = 'pdf-page-thumb error';
                    thumbEl.innerHTML += `<div class="pdf-page-status fail">✗ Error</div>`;
                }
            }

            processedCount++;
        }

        // Save auto-matched scans in batch
        let savedResults = [];
        if (batchScans.length > 0) {
            const batchRes = await api('batch_save_scan', {
                exam_id: currentExamId,
                scans: batchScans
            }, 'POST');
            if (batchRes.success) {
                savedResults = batchRes.results || [];
            }
        }

        batchPendingManual = manualNeeded;

        // Show batch summary
        showBatchSummary(savedResults, manualNeeded, pdfPages);

        // Reset button
        document.getElementById('btnBatchProcess').disabled = false;
        document.getElementById('btnBatchProcess').innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;">bolt</span> ตรวจทั้งหมด';

        loadExams(); // refresh counts
    }

    function showBatchSummary(savedResults, manualNeeded, allPages) {
        const el = document.getElementById('batchSummary');
        el.style.display = 'block';

        const autoCount = savedResults.length;
        const manualCount = manualNeeded.length;
        const errorCount = allPages.filter(p => p.status === 'error').length;
        const totalPages = allPages.length;

        let html = `
            <div class="batch-summary">
                <div class="batch-summary-header">
                    <div class="batch-summary-icon" style="background:${autoCount === totalPages ? 'var(--success-bg)' : 'var(--warning-bg)'};">
                        <span class="material-symbols-rounded" style="color:${autoCount === totalPages ? 'var(--success)' : 'var(--warning)'};">
                            ${autoCount === totalPages ? 'check_circle' : 'info'}
                        </span>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:16px;">ผลการตรวจ Batch</div>
                        <div style="font-size:13px;color:var(--text-secondary);">ตรวจ ${totalPages} หน้า</div>
                    </div>
                </div>

                <div class="exam-stats-row" style="grid-template-columns:repeat(3,1fr);">
                    <div class="exam-stat-card" style="border-left:3px solid var(--success);">
                        <div class="stat-value" style="color:var(--success);">${autoCount}</div>
                        <div class="stat-label">สำเร็จอัตโนมัติ</div>
                    </div>
                    <div class="exam-stat-card" style="border-left:3px solid var(--warning);">
                        <div class="stat-value" style="color:var(--warning);">${manualCount}</div>
                        <div class="stat-label">ต้องเลือก</div>
                    </div>
                    <div class="exam-stat-card" style="border-left:3px solid var(--danger);">
                        <div class="stat-value" style="color:var(--danger);">${errorCount}</div>
                        <div class="stat-label">ผิดพลาด</div>
                    </div>
                </div>

                <div class="batch-results-list">`;

        // Auto-saved results
        savedResults.forEach(r => {
            html += `
                <div class="batch-result-item success">
                    <div>
                        <span class="material-symbols-rounded" style="font-size:16px;color:var(--success);vertical-align:middle;">check_circle</span>
                        <strong style="margin-left:4px;">${escHtml(r.student_id + '')}</strong>
                    </div>
                    <div style="font-weight:700;color:var(--success);">${r.total_score}/${r.total_possible} (${r.percentage}%)</div>
                </div>`;
        });

        // Manual needed
        manualNeeded.forEach((page, idx) => {
            const detectedId = page.result?.studentId || '-';
            html += `
                <div class="batch-result-item manual">
                    <div>
                        <span class="material-symbols-rounded" style="font-size:16px;color:var(--warning);vertical-align:middle;">warning</span>
                        <span style="margin-left:4px;">หน้า ${page.pageNum}</span>
                        <span style="font-size:12px;color:var(--text-secondary);margin-left:4px;">(ID: ${escHtml(detectedId)})</span>
                    </div>
                    <button class="btn btn-primary" style="padding:6px 14px;font-size:12px;border-radius:8px;" onclick="manualSelectForPage(${idx})">
                        <span class="material-symbols-rounded" style="font-size:14px;">person_add</span> เลือก
                    </button>
                </div>`;
        });

        // Errors
        allPages.filter(p => p.status === 'error').forEach(page => {
            html += `
                <div class="batch-result-item fail">
                    <div>
                        <span class="material-symbols-rounded" style="font-size:16px;color:var(--danger);vertical-align:middle;">error</span>
                        <span style="margin-left:4px;">หน้า ${page.pageNum}</span>
                    </div>
                    <span style="font-size:12px;color:var(--danger);">${escHtml(page.errorMsg || 'Unknown error')}</span>
                </div>`;
        });

        html += `</div></div>`;
        el.innerHTML = html;
    }

    // Manual select for a specific batch page
    let currentBatchPageIdx = null;
    async function manualSelectForPage(idx) {
        currentBatchPageIdx = idx;
        const page = batchPendingManual[idx];
        if (!page) return;

        // Show the page preview
        showPreviewImage(page.dataUrl);

        // Set up scan answers from the result
        currentScanAnswers = page.result.answers;
        currentScanImage = page.dataUrl;

        // Open student select modal
        await openStudentSelect(page.result.studentId || '');
    }

    // ----- Camera -----
    async function openCamera() {
        try {
            const video = document.getElementById('cameraVideo');
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
            });
            cameraStream = stream;
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('scannerPlaceholder').style.display = 'none';
            document.getElementById('scanPreviewImg').style.display = 'none';

            const btn = document.getElementById('btnCamera');
            btn.innerHTML = '<span class="material-symbols-rounded">camera</span> ถ่าย';
            btn.onclick = captureFromCamera;
        } catch (err) {
            alert('ไม่สามารถเข้าถึงกล้อง: ' + err.message);
        }
    }

    function captureFromCamera() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        stopCamera();
        showPreviewImage(dataUrl);
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }
        const video = document.getElementById('cameraVideo');
        if (video) {
            video.srcObject = null;
            video.style.display = 'none';
        }

        const btn = document.getElementById('btnCamera');
        if (btn) {
            btn.innerHTML = '<span class="material-symbols-rounded">photo_camera</span> กล้อง';
            btn.onclick = openCamera;
        }
    }

    // ----- Process Single Image -----
    async function processCurrentImage() {
        if (!currentScanImage) return;
        if (!cvReady) {
            alert('OpenCV.js ยังโหลดไม่เสร็จ กรุณารอสักครู่...');
            return;
        }
        if (!currentExamId) {
            alert('กรุณาเลือกข้อสอบก่อน');
            return;
        }

        const examData = await api('get_exam', { exam_id: currentExamId });
        if (!examData.success) { alert(examData.error); return; }

        processor.setConfig({
            totalQuestions: examData.data.total_questions,
            choicesPerQuestion: examData.data.choices_count
        });

        document.getElementById('progressContainer').classList.add('active');
        document.getElementById('progressText').style.display = 'block';
        document.getElementById('btnProcess').disabled = true;

        try {
            const img = new Image();
            img.src = currentScanImage;
            await new Promise(r => img.onload = r);

            const result = await processor.processSheet(img);
            currentScanAnswers = result.answers;

            if (result.previewImage) {
                document.getElementById('scanPreviewImg').src = result.previewImage;
            }

            showScanResults(result, examData.data);
        } catch (err) {
            alert('เกิดข้อผิดพลาด: ' + err.message);
            console.error(err);
        } finally {
            document.getElementById('btnProcess').disabled = false;
            document.getElementById('progressContainer').classList.remove('active');
        }
    }

    function toggleManualInput() {
        const area = document.getElementById('manualInputArea');
        area.style.display = area.style.display === 'none' ? 'block' : 'none';
    }

    async function submitManualAnswers() {
        if (!currentExamId) { alert('กรุณาเลือกข้อสอบก่อน'); return; }

        const input = document.getElementById('manualAnswerInput').value.toUpperCase().replace(/[^A-E\-]/g, '');
        if (!input) { alert('กรุณากรอกคำตอบ'); return; }

        const examData = await api('get_exam', { exam_id: currentExamId });
        if (!examData.success) return;

        const answers = {};
        for (let i = 0; i < input.length && i < examData.data.total_questions; i++) {
            answers[i + 1] = input[i] === '-' ? '-' : input[i];
        }
        for (let q = input.length + 1; q <= examData.data.total_questions; q++) {
            answers[q] = '-';
        }

        currentScanAnswers = answers;
        showScanResults({ answers, confidence: {}, warnings: [], studentId: '' }, examData.data);
    }

    async function showScanResults(result, exam) {
        const examFullData = await api('get_exam', { exam_id: currentExamId });
        const answerKeys = {};
        if (examFullData.success) {
            (examFullData.data.answer_keys || []).forEach(k => {
                answerKeys[k.question_no] = k.correct_answer;
            });
        }

        let correct = 0, wrong = 0, blank = 0;
        const totalQ = exam.total_questions;

        let gridHtml = '';
        for (let q = 1; q <= totalQ; q++) {
            const ans = result.answers[q] || '-';
            const correctAns = answerKeys[q];
            let cls = 'blank';

            if (ans === '-' || ans === '') {
                blank++;
                cls = 'blank';
            } else if (correctAns && ans === correctAns) {
                correct++;
                cls = 'correct';
            } else if (correctAns) {
                wrong++;
                cls = 'wrong';
            } else {
                cls = 'blank';
            }

            gridHtml += `
                <div class="result-answer-item ${cls}">
                    <span class="q-label">${q}</span>
                    ${ans}${cls === 'wrong' ? `<span style="font-size:9px;display:block;">(${correctAns || '?'})</span>` : ''}
                </div>`;
        }

        const score = correct * parseFloat(exam.points_per_question);
        const totalPossible = totalQ * parseFloat(exam.points_per_question);
        const pct = totalPossible > 0 ? Math.round((score / totalPossible) * 100) : 0;
        const hue = pct >= 80 ? 142 : pct >= 60 ? 45 : 0;

        const previewEl = document.getElementById('scanResultPreview');
        previewEl.style.display = 'block';
        previewEl.innerHTML = `
            <div class="result-preview">
                <div class="score-circle" style="background:linear-gradient(135deg, hsl(${hue},70%,95%), hsl(${hue},70%,90%)); color:hsl(${hue},70%,35%);">
                    <span class="score-value">${score}</span>
                    <span class="score-label">/ ${totalPossible} (${pct}%)</span>
                </div>

                <div class="exam-stats-row" style="grid-template-columns:repeat(2,1fr);">
                    <div class="exam-stat-card" style="background:var(--success-bg);">
                        <div class="stat-value" style="color:var(--success);">${correct}</div>
                        <div class="stat-label">ถูก</div>
                    </div>
                    <div class="exam-stat-card" style="background:var(--danger-bg);">
                        <div class="stat-value" style="color:var(--danger);">${wrong}</div>
                        <div class="stat-label">ผิด</div>
                    </div>
                </div>
                ${blank > 0 ? `<div style="text-align:center;font-size:13px;color:var(--text-secondary);margin-bottom:12px;">ไม่ตอบ: ${blank} ข้อ</div>` : ''}

                <div style="background:var(--primary-light);border:1px solid var(--primary-mid);border-radius:var(--radius-md);padding:14px;margin-bottom:16px;text-align:center;">
                    <div style="font-weight:600;font-size:12px;color:var(--primary-dark);margin-bottom:4px;">รหัสนักเรียนที่ตรวจจับได้</div>
                    <div style="font-size:28px;font-weight:800;color:var(--primary);letter-spacing:4px;font-family:monospace;">${result.studentId || '-'}</div>
                </div>

                ${result.warnings && result.warnings.length > 0 ? `
                    <div style="background:var(--warning-bg);border-radius:var(--radius-md);padding:14px;margin-bottom:16px;">
                        <div style="font-weight:700;font-size:12px;color:var(--warning);margin-bottom:4px;">⚠️ คำเตือน</div>
                        ${result.warnings.slice(0, 5).map(w => `<div style="font-size:12px;color:#92400E;">${escHtml(w)}</div>`).join('')}
                    </div>
                ` : ''}

                <div style="font-weight:700;font-size:14px;margin-bottom:10px;">คำตอบที่ตรวจได้:</div>
                <div class="result-answer-grid">${gridHtml}</div>

                <button class="btn btn-primary w-full mt-4" onclick="openStudentSelect('${result.studentId || ''}')" style="border-radius:var(--radius-md);">
                    <span class="material-symbols-rounded">person_add</span> เลือกนักเรียนและบันทึก
                </button>
            </div>`;
    }

    async function openStudentSelect(detectedStudentId = '') {
        if (!currentExamId) return;

        const examData = await api('get_exam', { exam_id: currentExamId });
        if (!examData.success) return;

        const studentsData = await api('get_students', { subject_id: examData.data.subject_id });
        allStudents = studentsData.data || [];
        selectedStudentId = null;

        const searchInput = document.querySelector('.student-search');
        if (detectedStudentId && !detectedStudentId.includes('?')) {
            const matched = allStudents.find(s => s.student_code === detectedStudentId);
            if (matched) {
                selectedStudentId = matched.id;
            }
            searchInput.value = detectedStudentId;
            filterStudents(detectedStudentId);
        } else {
            searchInput.value = '';
            renderStudentList(allStudents);
        }

        openModal('studentSelectModal');
    }

    function renderStudentList(students) {
        document.getElementById('studentList').innerHTML = students.map(s => `
            <div class="student-select-card ${selectedStudentId == s.id ? 'selected' : ''}"
                onclick="selectStudent(${s.id}, this)">
                <div style="width:36px;height:36px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:var(--primary);">
                    ${escHtml((s.first_name || '')[0] || '?')}
                </div>
                <div>
                    <div style="font-weight:600;font-size:14px;">${escHtml(s.first_name)} ${escHtml(s.last_name)}</div>
                    <div style="font-size:12px;color:var(--text-secondary);">${escHtml(s.student_code)}</div>
                </div>
            </div>
        `).join('') || '<div class="empty-state"><p>ไม่พบนักเรียนที่ลงทะเบียนวิชานี้</p></div>';
    }

    function selectStudent(id, el) {
        selectedStudentId = id;
        document.querySelectorAll('.student-select-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
    }

    function filterStudents(query) {
        query = query.toLowerCase();
        const filtered = allStudents.filter(s =>
            s.student_code.toLowerCase().includes(query) ||
            s.first_name.toLowerCase().includes(query) ||
            s.last_name.toLowerCase().includes(query)
        );
        renderStudentList(filtered);
    }

    async function confirmStudentAndSave() {
        if (!selectedStudentId) { alert('กรุณาเลือกนักเรียน'); return; }
        if (!currentScanAnswers) { alert('ไม่มีข้อมูลคำตอบ'); return; }

        const result = await api('save_scan', {
            exam_id: currentExamId,
            student_id: selectedStudentId,
            answers: currentScanAnswers,
            image_data: currentScanImage
        }, 'POST');

        if (result.success) {
            closeModal('studentSelectModal');
            const r = result.result;
            alert(`บันทึกเรียบร้อย ✓\nคะแนน: ${r.total_score}/${r.total_possible} (${r.percentage}%)\nถูก: ${r.correct_count} ผิด: ${r.wrong_count} ไม่ตอบ: ${r.blank_count}`);

            // If from batch, update that page's status
            if (currentBatchPageIdx !== null && batchPendingManual[currentBatchPageIdx]) {
                const page = batchPendingManual[currentBatchPageIdx];
                page.status = 'manual-saved';
                const thumbEl = document.getElementById(`pdf-page-${page.pageNum}`);
                if (thumbEl) {
                    thumbEl.className = 'pdf-page-thumb done';
                    const oldStatus = thumbEl.querySelector('.pdf-page-status');
                    if (oldStatus) oldStatus.className = 'pdf-page-status success';
                    if (oldStatus) oldStatus.textContent = '✓ บันทึกแล้ว';
                }
                currentBatchPageIdx = null;
            } else {
                resetScanner();
            }

            loadExams();
        } else {
            alert('Error: ' + result.error);
        }
    }

    // ===== RESULTS =====
    async function loadResults() {
        const examId = document.getElementById('resultExamSelect').value;
        if (!examId) { document.getElementById('resultsContent').innerHTML = ''; return; }

        currentExamId = parseInt(examId);
        const data = await api('get_results', { exam_id: examId });
        if (!data.success) { alert(data.error); return; }

        renderResults(data);
    }

    function renderResults(data) {
        const { exam, results, stats, item_analysis, answer_keys } = data;
        const el = document.getElementById('resultsContent');

        if (!results.length) {
            el.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-rounded">analytics</span>
                    <div style="font-weight:700;font-size:16px;">ยังไม่มีผลสแกน</div>
                    <div style="font-size:14px;">ไปที่แท็บ "สแกน / PDF" เพื่อเริ่มตรวจ</div>
                </div>`;
            return;
        }

        let html = `
            <div class="exam-stats-row">
                <div class="exam-stat-card" style="border-top:3px solid var(--primary);">
                    <div class="stat-value" style="color:var(--primary);">${stats.count}</div>
                    <div class="stat-label">จำนวนคน</div>
                </div>
                <div class="exam-stat-card" style="border-top:3px solid var(--success);">
                    <div class="stat-value" style="color:var(--success);">${stats.mean}</div>
                    <div class="stat-label">คะแนนเฉลี่ย</div>
                </div>
                <div class="exam-stat-card" style="border-top:3px solid #06B6D4;">
                    <div class="stat-value" style="color:#06B6D4;">${stats.max}</div>
                    <div class="stat-label">สูงสุด</div>
                </div>
                <div class="exam-stat-card" style="border-top:3px solid var(--warning);">
                    <div class="stat-value" style="color:var(--warning);">${stats.sd}</div>
                    <div class="stat-label">SD</div>
                </div>
            </div>`;

        // Score Distribution Chart
        html += `
            <div class="chart-container">
                <div style="font-weight:700;font-size:15px;margin-bottom:12px;">📊 การกระจายคะแนน</div>
                <canvas id="scoreDistChart" height="200"></canvas>
            </div>`;

        // Results Table
        html += `
            <div class="chart-container">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div style="font-weight:700;font-size:15px;">📋 ตารางคะแนน</div>
                    <a href="${API}?action=export_csv&exam_id=${exam.id}" class="exam-badge blue" style="text-decoration:none;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:14px;">download</span> CSV
                    </a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>รหัส</th>
                                <th>ชื่อ</th>
                                <th>คะแนน</th>
                                <th>%</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${results.map((r, i) => {
                                const pct = r.total_possible > 0 ? Math.round((r.total_score / r.total_possible) * 100) : 0;
                                const color = pct >= 80 ? 'var(--success)' : pct >= 60 ? 'var(--warning)' : 'var(--danger)';
                                return `
                                <tr>
                                    <td>${i + 1}</td>
                                    <td style="font-weight:600;">${escHtml(r.student_code)}</td>
                                    <td>${escHtml(r.first_name)} ${escHtml(r.last_name)}</td>
                                    <td><strong>${r.total_score}</strong>/${r.total_possible}</td>
                                    <td style="color:${color};font-weight:700;">${pct}%</td>
                                    <td>
                                        <button class="delete-btn" onclick="deleteResult(${r.id})" title="ลบ">
                                            <span class="material-symbols-rounded" style="font-size:16px;">close</span>
                                        </button>
                                    </td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;

        // Item Analysis Chart
        if (Object.keys(item_analysis).length > 0) {
            html += `
                <div class="chart-container">
                    <div style="font-weight:700;font-size:15px;margin-bottom:12px;">📈 Item Analysis (% ที่ตอบถูก)</div>
                    <canvas id="itemAnalysisChart" height="250"></canvas>
                </div>`;
        }

        el.innerHTML = html;

        setTimeout(() => {
            renderScoreDistChart(results, exam);
            if (Object.keys(item_analysis).length > 0) {
                renderItemAnalysisChart(item_analysis, exam);
            }
        }, 100);
    }

    function renderScoreDistChart(results, exam) {
        const canvas = document.getElementById('scoreDistChart');
        if (!canvas) return;

        const totalPossible = parseFloat(exam.total_questions) * parseFloat(exam.points_per_question);
        const bins = 10;
        const binSize = totalPossible / bins;
        const counts = new Array(bins).fill(0);
        const labels = [];

        for (let i = 0; i < bins; i++) {
            const low = Math.round(i * binSize);
            const high = Math.round((i + 1) * binSize);
            labels.push(`${low}-${high}`);
        }

        results.forEach(r => {
            const bin = Math.min(Math.floor(r.total_score / binSize), bins - 1);
            counts[bin]++;
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'จำนวนคน',
                    data: counts,
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderItemAnalysisChart(itemAnalysis, exam) {
        const canvas = document.getElementById('itemAnalysisChart');
        if (!canvas) return;

        const labels = [];
        const data = [];
        const colors = [];

        Object.values(itemAnalysis).forEach(item => {
            labels.push(`${item.question_no}`);
            data.push(item.correct_pct);
            colors.push(item.correct_pct >= 80 ? 'rgba(22,163,74,0.7)' : item.correct_pct >= 50 ? 'rgba(217,119,6,0.7)' : 'rgba(220,38,38,0.7)');
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '% ถูก',
                    data,
                    backgroundColor: colors,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'ข้อที่' }
                    }
                }
            }
        });
    }

    async function deleteResult(resultId) {
        if (!confirm('ลบผลสแกนนี้?')) return;
        await api('delete_result', { result_id: resultId }, 'POST');
        loadResults();
    }

    // ===== MODAL =====
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) m.classList.remove('active');
        });
    });

    // ===== UTILS =====
    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== INIT =====
    document.addEventListener('DOMContentLoaded', () => {
        loadExams();
    });
    </script>
</body>
</html>
