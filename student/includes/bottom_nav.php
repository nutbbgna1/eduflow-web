<?php
// student/includes/bottom_nav.php
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="bottom-nav">
    <a href="index.php" class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
        <span class="material-symbols-rounded">calendar_today</span>
        <span class="nav-text">ตารางเรียน</span>
    </a>
    <a href="courses.php" class="nav-item <?= ($current_page == 'courses.php' || $current_page == 'course_detail.php') ? 'active' : '' ?>">
        <span class="material-symbols-rounded">school</span>
        <span class="nav-text">คอร์สเรียน</span>
    </a>
    <a href="assignments.php" class="nav-item <?= ($current_page == 'assignments.php' || $current_page == 'submit_assignment.php') ? 'active' : '' ?>">
        <span class="material-symbols-rounded">assignment</span>
        <span class="nav-text">การบ้าน</span>
    </a>
    <a href="profile.php" class="nav-item <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
        <span class="material-symbols-rounded">account_circle</span>
        <span class="nav-text">โปรไฟล์</span>
    </a>
</div>
