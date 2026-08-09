<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ── Global Bottom Nav for Teacher ── */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 480px;
        background-color: #fff;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 10px 12px max(env(safe-area-inset-bottom), 12px);
        border-top: 1px solid #E5E7EB;
        z-index: 9999;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: #6B7280;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        transition: all 0.2s ease;
        padding: 8px 16px;
        border-radius: 16px;
        font-family: 'Inter', 'Noto Sans Thai', sans-serif;
    }

    .nav-item .material-symbols-rounded {
        font-size: 24px;
    }

    .nav-item.active {
        color: #2563EB;
        background-color: #EFF6FF;
    }

    .nav-item:hover:not(.active) {
        color: #2563EB;
    }
</style>

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
    <a href="leave.php" class="nav-item <?php echo ($currentPage == 'leave.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">badge</span>
        <span class="nav-label">Staff</span>
    </a>
    <a href="earnings.php" class="nav-item <?php echo ($currentPage == 'earnings.php') ? 'active' : ''; ?>">
        <span class="material-symbols-rounded">payments</span>
        <span class="nav-label">Finance</span>
    </a>
</nav>
