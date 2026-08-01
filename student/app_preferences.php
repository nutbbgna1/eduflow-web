<?php
require_once '../config/db.php';
$page_title = 'App Preferences';
$show_back = true;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pref-card {
            background: #fff;
            border-radius: 20px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        .pref-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            margin-left: 8px;
        }
        .pref-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }
        .pref-item:last-child {
            border-bottom: none;
        }
        .pref-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pref-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F1F5F9;
            color: var(--text-main);
        }
        .pref-text {
            font-size: 15px;
            font-weight: 600;
            color: #0F172A;
        }
        .pref-subtext {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        /* Toggle Switch CSS */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 28px;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #CBD5E1;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        input:checked + .slider {
            background-color: var(--primary);
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }
        
        /* Language selector */
        .lang-select {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5 py-4">
            
            <div class="pref-section-title">Notifications</div>
            <div class="pref-card">
                <div class="pref-item">
                    <div class="pref-info">
                        <div class="pref-icon" style="background:#E0E7FF; color:var(--primary);">
                            <span class="material-symbols-rounded" style="font-size: 20px;">notifications_active</span>
                        </div>
                        <div>
                            <div class="pref-text">Push Notifications</div>
                            <div class="pref-subtext">Alerts for upcoming classes</div>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="pref-item">
                    <div class="pref-info">
                        <div class="pref-icon" style="background:#E0E7FF; color:var(--primary);">
                            <span class="material-symbols-rounded" style="font-size: 20px;">mail</span>
                        </div>
                        <div>
                            <div class="pref-text">Email Updates</div>
                            <div class="pref-subtext">Weekly progress reports</div>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="pref-section-title">Display & Language</div>
            <div class="pref-card">
                <div class="pref-item">
                    <div class="pref-info">
                        <div class="pref-icon" style="background:#F1F5F9; color:#475569;">
                            <span class="material-symbols-rounded" style="font-size: 20px;">dark_mode</span>
                        </div>
                        <div>
                            <div class="pref-text">Dark Mode</div>
                            <div class="pref-subtext">Easier on your eyes</div>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="pref-item">
                    <div class="pref-info">
                        <div class="pref-icon" style="background:#F1F5F9; color:#475569;">
                            <span class="material-symbols-rounded" style="font-size: 20px;">language</span>
                        </div>
                        <div>
                            <div class="pref-text">Language</div>
                        </div>
                    </div>
                    <div class="lang-select">
                        English (US)
                        <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; font-size: 12px; color: var(--text-muted); margin-top: 32px;">
                EduFlow Student App v1.0.4<br>
                All settings are automatically saved.
            </p>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
