<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="css/bottom_nav.css">

<!-- Spacer to prevent content from being hidden behind fixed bottom nav -->
<div class="bottom-nav-spacer"></div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
    <a href="index.php" class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">dashboard</span>
        <span class="nav-label">Home</span>
    </a>
    <a href="schedule.php" class="nav-item <?php echo ($currentPage == 'schedule.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">calendar_today</span>
        <span class="nav-label">Schedule</span>
    </a>
    <a href="roster.php" class="nav-item <?php echo ($currentPage == 'roster.php' || $currentPage == 'attendance.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">school</span>
        <span class="nav-label">Students</span>
    </a>
    <a href="exam-scanner.php" class="nav-item <?php echo ($currentPage == 'exam-scanner.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">document_scanner</span>
        <span class="nav-label">Exam</span>
    </a>
    <a href="leave.php" class="nav-item <?php echo ($currentPage == 'leave.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">badge</span>
        <span class="nav-label">Staff</span>
    </a>
    <a href="earnings.php" class="nav-item <?php echo ($currentPage == 'earnings.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">payments</span>
        <span class="nav-label">Finance</span>
    </a>
</nav>
