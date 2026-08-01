<?php
// student/includes/header.php
$show_back = $show_back ?? false;
$page_title = $page_title ?? 'EduFlow';
?>
<div class="top-header">
    <div class="brand-logo">
        <?php if($show_back): ?>
            <button onclick="history.back()" style="background:none;border:none;color:var(--primary);cursor:pointer;padding:0;display:flex;align-items:center;">
                <span class="material-symbols-rounded">arrow_back_ios_new</span>
            </button>
            <span style="margin-left:8px;font-size:16px;"><?= htmlspecialchars($page_title) ?></span>
        <?php else: ?>
            <span class="material-symbols-rounded" style="font-size:28px;">school</span>
            <?= htmlspecialchars($page_title) ?>
        <?php endif; ?>
    </div>
    
    <div class="header-actions">
        <button class="icon-btn" style="background:transparent;box-shadow:none;">
            <span class="material-symbols-rounded" style="font-size:24px;">search</span>
        </button>
        <button class="icon-btn" style="background:transparent;box-shadow:none;position:relative;">
            <span class="material-symbols-rounded" style="font-size:24px;">notifications</span>
            <span style="position:absolute;top:4px;right:6px;width:8px;height:8px;background:var(--danger);border-radius:50%;border:2px solid var(--bg-color);"></span>
        </button>
        <?php if($show_back): ?>
            <img src="https://ui-avatars.com/api/?name=Somsak&background=E0E7FF&color=4F46E5" class="avatar avatar-sm" alt="User">
        <?php endif; ?>
    </div>
</div>
