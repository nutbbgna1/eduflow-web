/**
 * ExamChecker — Sheet Processor
 * Image processing engine using OpenCV.js for bubble sheet scanning
 */

class SheetProcessor {
    constructor() {
        this.cvReady = false;
        this.debugMode = false;
        this.onProgress = null;
        
        // Sheet layout configuration (based on ZipGrade-style layout)
        this.config = {
            totalQuestions: 50,
            choicesPerQuestion: 5,
            columns: 5,            // 5 columns of 10 questions
            questionsPerColumn: 10,
            bubbleThreshold: 0.35, // % of dark pixels to consider "filled"
            cornerMinArea: 800,    // Minimum area for corner markers
            cornerMaxArea: 15000,  // Maximum area for corner markers
            studentIdLength: 5,    // Default length of student ID
            examCodeLength: 2,     // Length of Exam Code
        };
    }

    setConfig(config) {
        Object.assign(this.config, config);
    }

    /**
     * Main processing pipeline
     * @param {HTMLImageElement|HTMLCanvasElement} imageSource
     * @returns {Object} { answers, confidence, processedImage }
     */
    async processSheet(imageSource) {
        if (typeof cv === 'undefined') {
            throw new Error('OpenCV.js not loaded');
        }

        const steps = [];
        this._progress('เริ่มประมวลผลภาพ...', 0);

        // Step 1: Load image
        let src = cv.imread(imageSource);
        steps.push({ name: 'original', mat: src.clone() });

        // Resize if too large
        const maxDim = 1200;
        if (src.rows > maxDim || src.cols > maxDim) {
            const scale = maxDim / Math.max(src.rows, src.cols);
            let resized = new cv.Mat();
            cv.resize(src, resized, new cv.Size(0, 0), scale, scale);
            src.delete();
            src = resized;
        }
        this._progress('ปรับขนาดภาพ...', 10);

        // Step 2: Grayscale
        let gray = new cv.Mat();
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
        this._progress('แปลงเป็นขาวดำ...', 20);

        // Step 3: Blur + Threshold
        let blurred = new cv.Mat();
        cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0);
        
        let thresh = new cv.Mat();
        cv.adaptiveThreshold(blurred, thresh, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY_INV, 15, 4);
        this._progress('ทำ Threshold...', 30);

        // Step 4: Find corner markers
        this._progress('ตรวจจับ Corner Markers...', 40);
        let corners = this._findCornerMarkers(thresh, src);
        
        let aligned;
        if (corners && corners.length === 4) {
            // Step 5: Perspective transform
            this._progress('แก้มุมเอียง...', 50);
            aligned = this._perspectiveTransform(src, corners);
        } else {
            // Fallback: use the image as-is
            this._progress('ไม่พบ Corner Markers — ใช้ภาพตรง', 50);
            aligned = src.clone();
        }

        // Step 6: Process aligned image for bubbles
        let alignedGray = new cv.Mat();
        cv.cvtColor(aligned, alignedGray, cv.COLOR_RGBA2GRAY);
        
        let alignedThresh = new cv.Mat();
        cv.adaptiveThreshold(alignedGray, alignedThresh, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY_INV, 15, 4);
        this._progress('วิเคราะห์ Bubbles...', 60);

        // Step 7: Detect and read bubbles
        let result = this._readBubbles(alignedThresh, aligned);
        this._progress('ตรวจคำตอบเสร็จ!', 100);

        // Create preview image
        let preview = aligned.clone();
        this._drawResults(preview, result);

        // Convert preview to base64
        let previewCanvas = document.createElement('canvas');
        cv.imshow(previewCanvas, preview);
        let previewDataUrl = previewCanvas.toDataURL('image/jpeg', 0.85);

        // Save original scan
        let scanCanvas = document.createElement('canvas');
        cv.imshow(scanCanvas, aligned);
        let scanDataUrl = scanCanvas.toDataURL('image/jpeg', 0.85);

        // Cleanup
        src.delete(); gray.delete(); blurred.delete(); thresh.delete();
        aligned.delete(); alignedGray.delete(); alignedThresh.delete();
        preview.delete();
        steps.forEach(s => s.mat && s.mat.delete());

        return {
            answers: result.answers,
            studentId: result.studentId,
            examCode: result.examCode,
            confidence: result.confidence,
            details: result.details,
            previewImage: previewDataUrl,
            scanImage: scanDataUrl,
            warnings: result.warnings
        };
    }

    /**
     * Find 4 corner markers (black squares)
     */
    _findCornerMarkers(thresh, src) {
        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        cv.findContours(thresh, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        let candidates = [];
        for (let i = 0; i < contours.size(); i++) {
            let cnt = contours.get(i);
            let area = cv.contourArea(cnt);
            
            if (area < this.config.cornerMinArea || area > this.config.cornerMaxArea) continue;

            let peri = cv.arcLength(cnt, true);
            let approx = new cv.Mat();
            cv.approxPolyDP(cnt, approx, 0.04 * peri, true);

            // Square has 4 vertices
            if (approx.rows === 4) {
                let rect = cv.boundingRect(cnt);
                let aspectRatio = rect.width / rect.height;
                
                // Check if roughly square
                if (aspectRatio > 0.6 && aspectRatio < 1.4) {
                    // Check if mostly filled (dark)
                    let roi = thresh.roi(rect);
                    let nonZero = cv.countNonZero(roi);
                    let fillRatio = nonZero / (rect.width * rect.height);
                    roi.delete();
                    
                    if (fillRatio > 0.5) {
                        candidates.push({
                            x: rect.x + rect.width / 2,
                            y: rect.y + rect.height / 2,
                            area: area,
                            rect: rect
                        });
                    }
                }
            }
            approx.delete();
        }

        contours.delete();
        hierarchy.delete();

        if (candidates.length < 4) return null;

        // Sort by area (largest first) and take top candidates
        candidates.sort((a, b) => b.area - a.area);
        candidates = candidates.slice(0, Math.min(candidates.length, 8));

        // Identify corners: top-left, top-right, bottom-left, bottom-right
        let imgCx = src.cols / 2;
        let imgCy = src.rows / 2;

        let topLeft = candidates.filter(c => c.x < imgCx && c.y < imgCy).sort((a, b) => (a.x + a.y) - (b.x + b.y))[0];
        let topRight = candidates.filter(c => c.x > imgCx && c.y < imgCy).sort((a, b) => (b.x - a.x))[0];
        let bottomLeft = candidates.filter(c => c.x < imgCx && c.y > imgCy).sort((a, b) => (a.x - b.x))[0];
        let bottomRight = candidates.filter(c => c.x > imgCx && c.y > imgCy).sort((a, b) => (b.x + b.y) - (a.x + a.y))[0];

        if (!topLeft || !topRight || !bottomLeft || !bottomRight) return null;

        return [topLeft, topRight, bottomRight, bottomLeft];
    }

    /**
     * Apply perspective transform to straighten the sheet
     */
    _perspectiveTransform(src, corners) {
        const width = 800;
        const height = 1100;

        let srcPts = cv.matFromArray(4, 1, cv.CV_32FC2, [
            corners[0].x, corners[0].y,
            corners[1].x, corners[1].y,
            corners[2].x, corners[2].y,
            corners[3].x, corners[3].y
        ]);

        let dstPts = cv.matFromArray(4, 1, cv.CV_32FC2, [
            0, 0,
            width, 0,
            width, height,
            0, height
        ]);

        let M = cv.getPerspectiveTransform(srcPts, dstPts);
        let dst = new cv.Mat();
        cv.warpPerspective(src, dst, M, new cv.Size(width, height));

        srcPts.delete(); dstPts.delete(); M.delete();
        return dst;
    }

    /**
     * Read bubble answers from the aligned sheet
     */
    _readBubbles(thresh, colorImg) {
        const answers = {};
        const confidence = {};
        const details = {};
        const warnings = [];

        const h = thresh.rows;
        const w = thresh.cols;

        // Define bubble grid regions relative to the image
        // These ratios are calibrated for a standard 50-question sheet
        // Layout: 5 columns x 10 rows each
        const gridConfig = {
            // First section (Questions 1-10, 11-20)
            sections: [
                // Column 1: Questions 1-10
                { startQ: 1, startX: 0.065, endX: 0.34, startY: 0.10, endY: 0.48 },
                // Column 2: Questions 11-20  
                { startQ: 11, startX: 0.355, endX: 0.63, startY: 0.10, endY: 0.48 },
                // Column 3: Questions 31-40
                { startQ: 31, startX: 0.645, endX: 0.92, startY: 0.10, endY: 0.48 },
                // Column 4: Questions 21-30 (second row left)
                // Note: numbering might not match layout exactly, but we detect by position
            ]
        };

        // Use contour-based bubble detection
        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        cv.findContours(thresh, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        // Find all circular contours (bubbles)
        let bubbles = [];
        for (let i = 0; i < contours.size(); i++) {
            let cnt = contours.get(i);
            let area = cv.contourArea(cnt);
            let peri = cv.arcLength(cnt, true);
            
            if (area < 100 || area > 3000) continue;
            
            // Circularity check
            let circularity = (4 * Math.PI * area) / (peri * peri);
            if (circularity < 0.5) continue;

            let rect = cv.boundingRect(cnt);
            let aspectRatio = rect.width / rect.height;
            if (aspectRatio < 0.6 || aspectRatio > 1.5) continue;

            // Check fill ratio
            let roi = thresh.roi(rect);
            let nonZero = cv.countNonZero(roi);
            let fillRatio = nonZero / (rect.width * rect.height);
            roi.delete();

            bubbles.push({
                x: rect.x + rect.width / 2,
                y: rect.y + rect.height / 2,
                w: rect.width,
                h: rect.height,
                area: area,
                fillRatio: fillRatio,
                rect: rect
            });
        }

        contours.delete();
        hierarchy.delete();

        if (bubbles.length < 20) {
            // Fallback: grid-based detection
            return this._readBubblesGrid(thresh, w, h, warnings);
        }

        // Cluster bubbles into rows by Y coordinate
        bubbles.sort((a, b) => a.y - b.y);
        
        // Group by rows (bubbles with similar Y)
        let rows = [];
        let currentRow = [bubbles[0]];
        const rowThreshold = (bubbles[0]?.h || 20) * 0.8;

        for (let i = 1; i < bubbles.length; i++) {
            if (Math.abs(bubbles[i].y - currentRow[0].y) < rowThreshold) {
                currentRow.push(bubbles[i]);
            } else {
                if (currentRow.length >= 3) rows.push(currentRow);
                currentRow = [bubbles[i]];
            }
        }
        if (currentRow.length >= 3) rows.push(currentRow);

        // Sort each row by X
        rows.forEach(row => row.sort((a, b) => a.x - b.x));

        // Parse answers from rows
        // Each row group should correspond to questions
        // Group rows into sections (look for gaps)
        let questionNum = 1;
        const choiceLabels = ['A', 'B', 'C', 'D', 'E'];

        for (let row of rows) {
            if (questionNum > this.config.totalQuestions) break;

            // Determine how many question groups are in this row
            // Look for gaps in X to split into groups
            let groups = this._splitIntoGroups(row);

            for (let group of groups) {
                if (questionNum > this.config.totalQuestions) break;
                if (group.length < 3) continue; // Need at least A,B,C

                // Find the most filled bubble
                let maxFill = 0;
                let maxIdx = -1;
                let fills = [];

                for (let j = 0; j < Math.min(group.length, this.config.choicesPerQuestion); j++) {
                    fills.push(group[j].fillRatio);
                    if (group[j].fillRatio > maxFill) {
                        maxFill = group[j].fillRatio;
                        maxIdx = j;
                    }
                }

                if (maxFill > this.config.bubbleThreshold && maxIdx >= 0) {
                    // Check for multiple filled bubbles
                    let filledCount = fills.filter(f => f > this.config.bubbleThreshold).length;
                    
                    if (filledCount > 1) {
                        answers[questionNum] = 'X'; // Multiple answers
                        confidence[questionNum] = 0.5;
                        warnings.push(`ข้อ ${questionNum}: ตอบหลายตัวเลือก`);
                    } else {
                        answers[questionNum] = choiceLabels[maxIdx] || '?';
                        confidence[questionNum] = Math.min(maxFill / 0.6, 1);
                    }
                } else {
                    answers[questionNum] = '-'; // Blank
                    confidence[questionNum] = 0.9;
                }

                details[questionNum] = {
                    fills: fills,
                    selected: maxIdx,
                    bubbles: group.map(b => ({ x: b.x, y: b.y, fill: b.fillRatio }))
                };

                questionNum++;
            }
        }

        // Fill remaining as blank
        while (questionNum <= this.config.totalQuestions) {
            answers[questionNum] = '-';
            confidence[questionNum] = 0;
            warnings.push(`ข้อ ${questionNum}: ไม่พบ bubble`);
            questionNum++;
        }

        const ids = this._readIdentificationGrid(thresh, w, h);
        if (ids.studentId.includes('?')) {
            warnings.push('รหัสนักเรียนบางหลักอ่านไม่ได้ หรือฝนไม่ชัดเจน');
        }
        if (ids.examCode.includes('?')) {
            warnings.push('รหัสชุดข้อสอบบางหลักอ่านไม่ได้ หรือฝนไม่ชัดเจน');
        }

        return { answers, studentId: ids.studentId, examCode: ids.examCode, confidence, details, warnings };
    }

    /**
     * Fallback: Grid-based bubble reading (when contour detection fails)
     */
    _readBubblesGrid(thresh, w, h, warnings) {
        const answers = {};
        const confidence = {};
        const details = {};

        // Predefined grid positions (ratios) for standard 50-question sheet
        // Top section: Q1-10, Q11-20, Q31-40
        // Bottom section: Q21-30 (actually labeled differently on some sheets)
        const sections = [
            // Section 1: columns with number labels + ABCDE
            { questions: this._range(1, 10), xStart: 0.11, xEnd: 0.34, yStart: 0.095, yEnd: 0.46 },
            { questions: this._range(11, 20), xStart: 0.39, xEnd: 0.62, yStart: 0.095, yEnd: 0.46 },
            { questions: this._range(31, 40), xStart: 0.67, xEnd: 0.90, yStart: 0.095, yEnd: 0.46 },
            // Bottom sections
            { questions: this._range(21, 30), xStart: 0.39, xEnd: 0.62, yStart: 0.52, yEnd: 0.88 },
            { questions: this._range(41, 50), xStart: 0.67, xEnd: 0.90, yStart: 0.52, yEnd: 0.88 },
            // Bottom left
            { questions: this._range(1, 10), xStart: 0.11, xEnd: 0.34, yStart: 0.52, yEnd: 0.88, isSecondSection: true }
        ];

        // Simple approach: divide each section into rows and columns
        const choiceLabels = ['A', 'B', 'C', 'D', 'E'];
        const choicesCount = this.config.choicesPerQuestion;

        for (let section of sections) {
            if (section.isSecondSection) continue; // Skip duplicate mappings

            const qCount = section.questions.length;
            const x1 = Math.round(section.xStart * w);
            const x2 = Math.round(section.xEnd * w);
            const y1 = Math.round(section.yStart * h);
            const y2 = Math.round(section.yEnd * h);
            const sectionW = x2 - x1;
            const sectionH = y2 - y1;
            const rowH = sectionH / qCount;
            const colW = sectionW / choicesCount;

            for (let qi = 0; qi < qCount; qi++) {
                const qNum = section.questions[qi];
                if (qNum > this.config.totalQuestions) break;

                let maxFill = 0;
                let maxIdx = -1;
                let fills = [];

                for (let ci = 0; ci < choicesCount; ci++) {
                    const bx = Math.round(x1 + ci * colW + colW * 0.2);
                    const by = Math.round(y1 + qi * rowH + rowH * 0.2);
                    const bw = Math.round(colW * 0.6);
                    const bh = Math.round(rowH * 0.6);

                    // Clamp to image bounds
                    const rx = Math.max(0, Math.min(bx, w - 1));
                    const ry = Math.max(0, Math.min(by, h - 1));
                    const rw = Math.min(bw, w - rx);
                    const rh = Math.min(bh, h - ry);

                    if (rw <= 0 || rh <= 0) {
                        fills.push(0);
                        continue;
                    }

                    let roi = thresh.roi(new cv.Rect(rx, ry, rw, rh));
                    let nonZero = cv.countNonZero(roi);
                    let fillRatio = nonZero / (rw * rh);
                    roi.delete();

                    fills.push(fillRatio);
                    if (fillRatio > maxFill) {
                        maxFill = fillRatio;
                        maxIdx = ci;
                    }
                }

                if (maxFill > this.config.bubbleThreshold && maxIdx >= 0) {
                    let filledCount = fills.filter(f => f > this.config.bubbleThreshold).length;
                    if (filledCount > 1) {
                        answers[qNum] = 'X';
                        confidence[qNum] = 0.5;
                        warnings.push(`ข้อ ${qNum}: ตอบหลายตัวเลือก`);
                    } else {
                        answers[qNum] = choiceLabels[maxIdx];
                        confidence[qNum] = Math.min(maxFill / 0.6, 1);
                    }
                } else {
                    answers[qNum] = '-';
                    confidence[qNum] = 0.8;
                }

                details[qNum] = { fills, selected: maxIdx };
            }
        }

        const ids = this._readIdentificationGrid(thresh, w, h);
        if (ids.studentId.includes('?')) {
            warnings.push('รหัสนักเรียนบางหลักอ่านไม่ได้ หรือฝนไม่ชัดเจน');
        }
        if (ids.examCode.includes('?')) {
            warnings.push('รหัสชุดข้อสอบบางหลักอ่านไม่ได้ หรือฝนไม่ชัดเจน');
        }

        return { answers, studentId: ids.studentId, examCode: ids.examCode, confidence, details, warnings };
    }

    /**
     * Split a row of bubbles into question groups based on X gaps
     */
    _splitIntoGroups(row) {
        if (row.length <= this.config.choicesPerQuestion) return [row];

        let groups = [];
        let current = [row[0]];
        const avgWidth = row.reduce((s, b) => s + b.w, 0) / row.length;
        const gapThreshold = avgWidth * 2.5;

        for (let i = 1; i < row.length; i++) {
            let gap = row[i].x - row[i - 1].x;
            if (gap > gapThreshold) {
                groups.push(current);
                current = [row[i]];
            } else {
                current.push(row[i]);
            }
        }
        groups.push(current);
        return groups;
    }

    /**
     * Read Exam Code and Student ID from the specific grid (bottom-left)
     * Layout: [Exam Code] [Gap] [Student ID]
     */
    _readIdentificationGrid(thresh, w, h) {
        let studentId = "";
        let examCode = "";
        const idLength = this.config.studentIdLength;
        const examLength = this.config.examCodeLength;
        const totalCols = idLength + 1 + examLength; // e.g. 5 + 1 + 2 = 8
        const rowsCount = 10; // Digits 0-9
        
        // Define Identification section (Bottom-Left)
        const x1 = Math.round(0.11 * w);
        const x2 = Math.round(0.34 * w);
        const y1 = Math.round(0.52 * h);
        const y2 = Math.round(0.88 * h);
        const sectionW = x2 - x1;
        const sectionH = y2 - y1;
        const rowH = sectionH / rowsCount;
        const colW = sectionW / totalCols;

        const readColumns = (startCol, count) => {
            let result = "";
            for (let ci = startCol; ci < startCol + count; ci++) {
                let maxFill = 0;
                let maxDigit = -1;
                
                for (let ri = 0; ri < rowsCount; ri++) {
                    const bx = Math.round(x1 + ci * colW + colW * 0.2);
                    const by = Math.round(y1 + ri * rowH + rowH * 0.2);
                    const bw = Math.round(colW * 0.6);
                    const bh = Math.round(rowH * 0.6);

                    const rx = Math.max(0, Math.min(bx, w - 1));
                    const ry = Math.max(0, Math.min(by, h - 1));
                    const rw = Math.min(bw, w - rx);
                    const rh = Math.min(bh, h - ry);

                    if (rw <= 0 || rh <= 0) continue;

                    let roi = thresh.roi(new cv.Rect(rx, ry, rw, rh));
                    let nonZero = cv.countNonZero(roi);
                    let fillRatio = nonZero / (rw * rh);
                    roi.delete();

                    if (fillRatio > maxFill) {
                        maxFill = fillRatio;
                        maxDigit = ri;
                    }
                }
                if (maxFill > this.config.bubbleThreshold && maxDigit >= 0) {
                    result += maxDigit.toString();
                } else {
                    result += "?";
                }
            }
            return result;
        };

        examCode = readColumns(0, examLength);
        studentId = readColumns(examLength + 1, idLength);

        return { studentId, examCode };
    }

    /**
     * Draw results overlay on image
     */
    _drawResults(img, result) {
        // This is a simplified version - draw text overlay
        const h = img.rows;
        const w = img.cols;
        
        // Draw a summary box at the bottom
        let answered = Object.values(result.answers).filter(a => a !== '-' && a !== 'X').length;
        let blank = Object.values(result.answers).filter(a => a === '-').length;
        let multi = Object.values(result.answers).filter(a => a === 'X').length;
        
        // Draw background rectangle
        cv.rectangle(img, new cv.Point(0, h - 60), new cv.Point(w, h), new cv.Scalar(0, 0, 0, 200), -1);
        
        // Draw text
        cv.putText(img, `ID: ${result.studentId || '?'} | Ans: ${answered} Blank: ${blank} Multi: ${multi}`, 
            new cv.Point(10, h - 25), cv.FONT_HERSHEY_SIMPLEX, 0.5, new cv.Scalar(255, 255, 255, 255), 1);
    }

    _range(start, end) {
        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
    }

    _progress(message, percent) {
        if (this.onProgress) {
            this.onProgress(message, percent);
        }
    }
}

// Export for use
window.SheetProcessor = SheetProcessor;
