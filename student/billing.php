<?php
$page_title = 'EduFlow';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Billing</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-header {
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
        }
        .page-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        
        .summary-card {
            background: #fff;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            margin-bottom: 16px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
        }
        .month-badge {
            background: #F1F5F9;
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .bill-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 700;
            font-size: 18px;
        }
        .icon-green { background: #E6F4EA; color: #137333; }
        .icon-red { background: #FEE2E2; color: #DC2626; }
        
        .item-details {
            flex: 1;
            margin-left: 12px;
        }
        .item-name {
            font-size: 13px;
            font-weight: 700;
            color: #0F172A;
        }
        .item-code {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .status-paid {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #10B981;
            font-size: 12px;
            font-weight: 700;
        }
        .status-unpaid {
            text-align: right;
        }
        .unpaid-amount {
            color: #DC2626;
            font-size: 14px;
            font-weight: 700;
        }
        .unpaid-text {
            color: #DC2626;
            font-size: 10px;
            font-weight: 600;
        }
        
        .total-section {
            border-top: 1px dashed var(--border);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .total-label {
            font-size: 10px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .total-amount {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        .btn-pay {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        
        /* History Accordion */
        .history-card {
            background: #fff;
            border-radius: 20px;
            padding: 16px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 16px;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-top: 1px solid var(--border);
        }
        
        /* Promo Card */
        .promo-card {
            background: linear-gradient(135deg, #2563EB, #1E3A8A);
            border-radius: 24px;
            padding: 24px;
            color: #fff;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }
        .promo-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 16px;
        }
        .promo-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        .promo-desc {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .btn-white {
            background: #fff;
            color: var(--primary);
            text-align: center;
            padding: 12px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            display: block;
            text-decoration: none;
            margin-bottom: 12px;
        }
        .btn-outline-white {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            text-align: center;
            padding: 12px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            display: block;
            text-decoration: none;
        }
        
        /* Upload Card */
        .upload-card {
            background: transparent;
            border: 2px dashed #93C5FD;
            border-radius: 24px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        .upload-icon {
            width: 56px;
            height: 56px;
            background: #DBEAFE;
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .upload-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
        }
        .upload-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 20px;
            padding: 0 10px;
        }
        .btn-upload {
            background: #F1F5F9;
            color: #0F172A;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }
        .upload-note {
            font-size: 9px;
            color: var(--text-muted);
            margin-top: 12px;
        }
        
        /* Payment Methods */
        .payment-methods-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
        }
        .methods-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 40px;
        }
        .method-box {
            flex: 1;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .method-box img {
            height: 20px;
            opacity: 0.6;
        }
        .method-box span {
            font-size: 9px;
            color: var(--text-muted);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5">
            <div class="page-header">
                <h1 class="page-title">ค่าเรียนของฉัน</h1>
                <p class="page-subtitle">ตรวจสอบสถานะการชำระเงินและจัดการการลงทะเบียนเรียน</p>
            </div>
            
            <!-- Summary Card -->
            <div class="summary-card">
                <div class="card-header">
                    <div class="card-title">สรุปยอดปัจจุบัน</div>
                    <div class="month-badge">พฤษภาคม 2567</div>
                </div>
                
                <div class="bill-item">
                    <div class="icon-box icon-green">
                        <span class="material-symbols-rounded">functions</span>
                    </div>
                    <div class="item-details">
                        <div class="item-name">Advanced Mathematics</div>
                        <div class="item-code">รหัสวิชา: MATH402</div>
                    </div>
                    <div class="status-paid">
                        <span class="material-symbols-rounded" style="font-size:16px;">check_circle</span>
                        จ่ายแล้ว
                    </div>
                </div>
                
                <div class="bill-item">
                    <div class="icon-box icon-red">
                        <span class="material-symbols-rounded">science</span>
                    </div>
                    <div class="item-details">
                        <div class="item-name">Quantum Physics</div>
                        <div class="item-code">รหัสวิชา: PHYS301</div>
                    </div>
                    <div class="status-unpaid">
                        <div class="unpaid-amount">฿2,500</div>
                        <div class="unpaid-text">ค้างจ่าย</div>
                    </div>
                </div>
                
                <div class="total-section">
                    <div>
                        <div class="total-label">ยอดรวมที่ต้องชำระทั้งสิ้น</div>
                        <div class="total-amount">฿2,500</div>
                    </div>
                    <button class="btn-pay">ชำระเงินทันที</button>
                </div>
            </div>
            
            <!-- History -->
            <div class="history-card">
                <div class="history-header">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded" style="font-size:18px; color:var(--text-muted);">history</span>
                        ประวัติการชำระเงิน
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--text-muted);">expand_more</span>
                </div>
                
                <div class="history-item">
                    <div>
                        <div class="text-xs font-bold" style="color:#0F172A;">เมษายน 2567</div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">ใบเสร็จ #REC-8842</div>
                    </div>
                    <div class="text-xs font-bold" style="color:#10B981;">฿5,000 จ่ายแล้ว</div>
                </div>
                
                <div class="history-item">
                    <div>
                        <div class="text-xs font-bold" style="color:#0F172A;">มีนาคม 2567</div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">ใบเสร็จ #REC-8102</div>
                    </div>
                    <div class="text-xs font-bold" style="color:#10B981;">฿5,000 จ่ายแล้ว</div>
                </div>
            </div>
            
            <!-- Promo Card -->
            <div class="promo-card">
                <div class="promo-badge">สมัครเรียนล่วงหน้า</div>
                <div class="promo-title">ลงทะเบียนสำหรับเดือนหน้า</div>
                <div class="promo-desc">ยืนยันสิทธิ์การเข้าเรียนในวิชาเดิมสำหรับรอบเดือนมิถุนายน เพื่อไม่ให้พลาดที่นั่งในกลุ่มเดิม</div>
                <a href="#" class="btn-white">เรียนต่อเดือนหน้า</a>
                <a href="#" class="btn-outline-white">ไม่เรียนต่อ</a>
            </div>
            
            <!-- Upload Slip -->
            <div class="upload-card">
                <div class="upload-icon">
                    <span class="material-symbols-rounded" style="font-size:28px;">upload_file</span>
                </div>
                <div class="upload-title">อัปโหลดสลิปโอนเงิน</div>
                <div class="upload-desc">กรุณาอัปโหลดไฟล์ภาพสลิป (.jpg, .png) เพื่อยืนยันการชำระเงินที่เคาน์เตอร์หรือแอปฯ ธนาคาร</div>
                <button class="btn-upload">
                    <span class="material-symbols-rounded" style="font-size:16px;">image</span>
                    เลือกไฟล์รูปภาพ
                </button>
                <div class="upload-note">รองรับไฟล์ขนาดสูงสุด 5MB</div>
            </div>
            
            <!-- Payment Methods -->
            <div class="payment-methods-title">ช่องทางชำระเงิน</div>
            <div class="methods-grid">
                <div class="method-box">
                    <span class="material-symbols-rounded" style="color:var(--text-muted);">qr_code_2</span>
                    <span>PromptPay</span>
                </div>
                <div class="method-box">
                    <span class="material-symbols-rounded" style="color:var(--text-muted);">credit_card</span>
                    <span>Credit Card</span>
                </div>
                <div class="method-box">
                    <span class="material-symbols-rounded" style="color:var(--text-muted);">account_balance</span>
                    <span>Bank Transfer</span>
                </div>
            </div>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
