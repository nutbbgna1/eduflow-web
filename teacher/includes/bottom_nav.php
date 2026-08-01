<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="bottom-nav">
    <a href="index.php" class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">calendar_today</span>
    </a>
    <a href="attendance.php" class="nav-item <?php echo ($currentPage == 'attendance.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">check_circle</span>
    </a>
    <a href="earnings.php" class="nav-item <?php echo ($currentPage == 'earnings.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">payments</span>
    </a>
    <a href="leave.php" class="nav-item <?php echo ($currentPage == 'leave.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">assignment</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo ($currentPage == 'profile.php' || $currentPage == 'edit_profile.php' || $currentPage == 'change_password.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">person</span>
    </a>
</nav>
