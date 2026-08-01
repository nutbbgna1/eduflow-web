<?php
// admin/schedule.php
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFlow Admin — Schedule</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="main-content-desktop animate-slide-up">
            
            <div class="page-header" style="align-items:center;">
                <div class="page-title">
                    <h1 style="margin-bottom:0;">Weekly Schedule</h1>
                    <p>Manage classes for Fall Semester 2024</p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="background:#F1F5F9; border-radius:8px; padding:4px; display:flex;">
                        <button class="btn" style="background:#fff; border:1px solid var(--border); box-shadow:var(--shadow-xs); border-radius:6px; padding:6px 12px; font-size:13px; font-weight:600; color:var(--primary);">Weekly Grid</button>
                        <button class="btn" style="background:transparent; border:none; padding:6px 12px; font-size:13px; font-weight:500; color:#64748B;">Room View</button>
                    </div>
                    <button class="btn btn-primary" style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; background:#1D4ED8; color:#fff; border:none; font-weight:600;">
                        <span class="material-symbols-rounded" style="font-size:20px;">add</span> Add Session
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; background:#fff; padding:16px 24px; border-radius:12px; border:1px solid var(--border);">
                <div style="display:flex; align-items:center; gap:16px;">
                    <span class="material-symbols-rounded" style="color:#64748B;">filter_list</span>
                    <span style="font-size:14px; font-weight:600;">Filters:</span>
                    <select style="padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:#F8FAFC; outline:none; font-size:14px;">
                        <option>All Departments</option>
                    </select>
                    <select style="padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:#F8FAFC; outline:none; font-size:14px;">
                        <option>All Rooms</option>
                    </select>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button class="btn btn-outline" style="padding:6px; border-radius:8px; border:1px solid var(--border); background:#fff; color:#64748B; display:flex; align-items:center;">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div style="padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:#F8FAFC; font-size:14px; font-weight:600; color:#0F172A;">
                        Oct 14 - Oct 20, 2024
                    </div>
                    <button class="btn btn-outline" style="padding:6px; border-radius:8px; border:1px solid var(--border); background:#fff; color:#64748B; display:flex; align-items:center;">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-wrapper">
                <div class="calendar-header-row">
                    <div class="cal-head-cell" style="display:flex; align-items:center; justify-content:center; color:#94A3B8; font-size:11px;">TIME</div>
                    <div class="cal-head-cell">Monday<div class="cal-date">Oct 14</div></div>
                    <div class="cal-head-cell">Tuesday<div class="cal-date">Oct 15</div></div>
                    <div class="cal-head-cell">Wednesday<div class="cal-date">Oct 16</div></div>
                    <div class="cal-head-cell">Thursday<div class="cal-date">Oct 17</div></div>
                    <div class="cal-head-cell">Friday<div class="cal-date">Oct 18</div></div>
                </div>
                
                <div class="calendar-body">
                    <div class="time-col">
                        <div class="time-slot">08:00</div>
                        <div class="time-slot">09:00</div>
                        <div class="time-slot">10:00</div>
                        <div class="time-slot">11:00</div>
                        <div class="time-slot">12:00<div style="color:#CBD5E1; font-weight:600; font-size:10px; margin-top:4px;">LUNCH</div></div>
                        <div class="time-slot">13:00</div>
                        <div class="time-slot">14:00</div>
                        <div class="time-slot">15:00</div>
                    </div>
                    
                    <div class="day-col">
                        <!-- Monday -->
                        <!-- 09:00 - 10:30 (1.5 hours) -> top: 80px (for 09:00), height: 1.5 * 80 = 120px -->
                        <div class="class-block blue" style="top: 80px; height: 120px;">
                            <div class="class-title">Advanced Calculus</div>
                            <div class="class-meta">Dr. Smith &bull; Lab 101<br>09:00 - 10:30</div>
                        </div>
                    </div>
                    
                    <div class="day-col">
                        <!-- Tuesday -->
                        <!-- 10:00 - 11:30 (1.5 hours) -> top: 160px (for 10:00), height: 1.5 * 80 = 120px -->
                        <div class="class-block red" style="top: 160px; height: 120px;">
                            <div style="display:flex; justify-content:space-between;">
                                <div class="class-title">Physics 201</div>
                                <span class="material-symbols-rounded" style="font-size:14px; color:#DC2626;">warning</span>
                            </div>
                            <div class="class-meta">Prof. Allen &bull; Room 302<br>10:00 - 11:30</div>
                        </div>
                    </div>
                    
                    <div class="day-col">
                        <!-- Wednesday -->
                        <!-- 13:00 - 15:00 (2 hours) -> top: 400px (for 13:00), height: 2 * 80 = 160px -->
                        <div class="class-block green" style="top: 400px; height: 160px;">
                            <div class="class-title">Creative Writing<br>Workshop</div>
                            <div class="class-meta">Ms. Davis &bull; Hall A<br>13:00 - 15:00</div>
                        </div>
                    </div>
                    
                    <div class="day-col">
                        <!-- Thursday -->
                    </div>
                    
                    <div class="day-col">
                        <!-- Friday -->
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
