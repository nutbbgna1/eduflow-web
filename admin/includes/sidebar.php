<?php
// admin/includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['icon' => 'grid_view', 'label' => __('Dashboard'), 'url' => 'index.php'],
    ['icon' => 'person', 'label' => __('Teachers'), 'url' => 'staff.php'],
    ['icon' => 'book', 'label' => __('Courses'), 'url' => 'subjects.php'],
    ['icon' => 'category', 'label' => __('Categories'), 'url' => 'categories.php'],
    ['icon' => 'location_on', 'label' => __('Rooms'), 'url' => 'rooms.php'],
    ['icon' => 'calendar_today', 'label' => __('Schedule'), 'url' => 'schedule.php'],
    ['icon' => 'groups', 'label' => __('Students'), 'url' => 'students.php'],
    ['icon' => 'manage_accounts', 'label' => __('Enroll. Status'), 'url' => 'student_enrollments.php'],
    ['icon' => 'payments', 'label' => __('Payments'), 'url' => 'finance.php'],
    ['icon' => 'account_balance_wallet', 'label' => __('Payroll'), 'url' => 'payroll.php'],
    ['icon' => 'event_busy', 'label' => __('Leave'), 'url' => 'leave.php'],
    ['icon' => 'document_scanner', 'label' => __('Exam Scanner'), 'url' => 'exam-scanner.php'],
    ['icon' => 'badge', 'label' => __('RFID'), 'url' => '#'],
];
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span class="material-symbols-rounded">school</span>
            EduFlow
        </div>
        <div class="sidebar-subtitle"><?= __('Admin Portal') ?></div>
    </div>
    
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['url'] ?>" class="sidebar-item <?= ($currentPage == $item['url']) ? 'active' : '' ?>">
                <span class="material-symbols-rounded"><?= $item['icon'] ?></span>
                <span class="label"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <img src="https://ui-avatars.com/api/?name=Admin+User&background=0D8ABC&color=fff" alt="Admin" class="profile-img">
        <div style="flex: 1; overflow: hidden;">
            <div style="font-weight: 600; font-size: 14px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= __('Admin User') ?></div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.6); white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">admin@eduflow.edu</div>
        </div>
        <a href="../logout.php" style="color: rgba(255,255,255,0.6); text-decoration: none;" title="<?= __('Logout') ?>">
            <span class="material-symbols-rounded">logout</span>
        </a>
    </div>
</aside>
