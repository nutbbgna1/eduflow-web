<?php
$page_title = 'EduFlow';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-main);
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            padding: 24px 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            text-align: center;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 16px;
        }
        
        .avatar-main {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .edit-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 28px;
            height: 28px;
            background: var(--primary);
            border-radius: 50%;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .tag-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-title a {
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }
        
        .subject-card {
            background: #fff;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            position: relative;
        }
        
        .subject-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .code-pill {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #F1F5F9;
            color: var(--text-muted);
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .progress-bar-bg {
            height: 4px;
            background: #E2E8F0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
        }
        
        .checkin-card {
            background: #fff;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        
        .checkin-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .checkin-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .check-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .attendance-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            text-align: center;
        }
        
        .circle-progress {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 6px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 16px auto;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .settings-card {
            background: #fff;
            border-radius: 20px;
            padding: 8px 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .help-box {
            background: linear-gradient(135deg, #0F4DD9 0%, #002D96 100%);
            border-radius: 24px;
            padding: 24px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .help-bg-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 80px;
            color: rgba(255,255,255,0.1);
            font-weight: 700;
            line-height: 1;
        }
        .btn-white {
            background: #fff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/header.php'; ?>

        <div class="px-5">
            <h1 class="page-title">โปรไฟล์ของฉัน</h1>
            
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="avatar-container">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop" class="avatar-main" alt="Somsak">
                    <div class="edit-badge">
                        <span class="material-symbols-rounded" style="font-size: 14px;">edit</span>
                    </div>
                </div>
                <div class="font-bold text-lg">Somsak Student</div>
                <div class="text-xs text-muted mt-1">ID: 6509123456</div>
                <div class="verified-badge">
                    <span class="material-symbols-rounded" style="font-size: 14px;">verified</span>
                    Verified Undergraduate
                </div>
                
                <div class="flex justify-center gap-2 mt-4">
                    <div class="tag-pill" style="background:#E6F4EA; color:#137333;">Academic Year<br>3</div>
                    <div class="tag-pill" style="background:#E8F0FE; color:#1967D2;">Computer<br>Science</div>
                </div>
            </div>

            <!-- My Subjects -->
            <div class="section-title">
                <span>My Subjects</span>
                <a href="courses.php">View All</a>
            </div>
            
            <div class="subject-card">
                <div class="code-pill">CS302</div>
                <div class="flex gap-3">
                    <div class="subject-icon" style="background:#E8F0FE; color:#1967D2;">{}</div>
                    <div>
                        <div class="font-bold text-sm mb-1" style="color: #0F172A; padding-right: 40px;">Algorithms & Data Structures</div>
                        <div class="text-xs text-muted">Mon, Wed • 09:30 - 11:00</div>
                    </div>
                </div>
                <div class="flex justify-between text-xs font-medium mt-3" style="color: #0F172A;">
                    <span style="font-size: 10px; color: var(--text-muted);">Course Progress</span>
                    <span class="text-primary font-bold">75%</span>
                </div>
                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:75%; background:var(--primary);"></div></div>
            </div>
            
            <div class="subject-card">
                <div class="code-pill">CS305</div>
                <div class="flex gap-3">
                    <div class="subject-icon" style="background:#E6F4EA; color:#137333;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">database</span>
                    </div>
                    <div>
                        <div class="font-bold text-sm mb-1" style="color: #0F172A; padding-right: 40px;">Database Systems</div>
                        <div class="text-xs text-muted">Tue, Thu • 13:00 - 15:00</div>
                    </div>
                </div>
                <div class="flex justify-between text-xs font-medium mt-3" style="color: #0F172A;">
                    <span style="font-size: 10px; color: var(--text-muted);">Course Progress</span>
                    <span class="font-bold" style="color:#10B981;">40%</span>
                </div>
                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:40%; background:#10B981;"></div></div>
            </div>

            <!-- Recent Check-ins -->
            <div class="section-title mt-4">
                <span>Recent Check-ins</span>
            </div>
            
            <div class="checkin-card">
                <div class="checkin-item">
                    <div class="flex items-center gap-3">
                        <div class="check-icon" style="background:#E6F4EA; color:#137333;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">check_circle</span>
                        </div>
                        <div>
                            <div class="font-bold text-xs" style="color: #0F172A;">Algorithms & Data Structures</div>
                            <div style="font-size:10px; color:var(--text-muted);">Oct 24, 2023 • 09:05 AM</div>
                        </div>
                    </div>
                    <div class="tag-pill" style="background:#6EE7B7; color:#064E3B; text-transform:none;">On Time</div>
                </div>
                
                <div class="checkin-item">
                    <div class="flex items-center gap-3">
                        <div class="check-icon" style="background:#F1F5F9; color:#64748B;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">check_circle</span>
                        </div>
                        <div>
                            <div class="font-bold text-xs" style="color: #0F172A;">Database Systems</div>
                            <div style="font-size:10px; color:var(--text-muted);">Oct 23, 2023 • 01:10 PM</div>
                        </div>
                    </div>
                    <div class="tag-pill" style="background:#FECACA; color:#991B1B; text-transform:none;">Late (15m)</div>
                </div>
            </div>
            
            <!-- Attendance Rate -->
            <div class="attendance-card">
                <div class="text-xs font-bold" style="color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Attendance Rate</div>
                <div class="circle-progress">
                    95%
                </div>
                <div class="text-xs text-muted" style="line-height:1.5;">
                    Excellent! You've missed only 2<br>sessions this semester.
                </div>
            </div>

            <!-- Settings -->
            <div class="section-title">
                <span>Settings</span>
            </div>
            
            <div class="settings-card">
                <a href="edit_profile.php" class="setting-item">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-rounded" style="color:var(--text-muted); font-size:20px;">person_outline</span>
                        Edit Profile
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--border); font-size:18px;">chevron_right</span>
                </a>
                <a href="change_password.php" class="setting-item">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-rounded" style="color:var(--text-muted); font-size:20px;">lock_outline</span>
                        Change Password
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--border); font-size:18px;">chevron_right</span>
                </a>
                <a href="app_preferences.php" class="setting-item">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-rounded" style="color:var(--text-muted); font-size:20px;">settings</span>
                        App Preferences
                    </div>
                    <span class="material-symbols-rounded" style="color:var(--border); font-size:18px;">chevron_right</span>
                </a>
                <a href="../logout.php" class="setting-item" style="color:var(--danger);">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-rounded" style="font-size:20px;">logout</span>
                        Logout
                    </div>
                </a>
            </div>
            
            <!-- Help Box -->
            <div class="help-box">
                <div class="help-bg-icon">?</div>
                <div class="font-bold text-sm mb-2" style="position:relative; z-index:1;">Need Help?</div>
                <div class="text-xs mb-4" style="position:relative; z-index:1; opacity:0.9; line-height:1.5; max-width: 80%;">
                    Our academic advisors are available for 1-on-1 sessions.
                </div>
                <a href="#" class="btn-white" style="position:relative; z-index:1;">Contact Support</a>
            </div>

        </div>

        <?php include 'includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
