<?php
// admin/includes/header.php
?>
<header class="desktop-header">
    <div class="header-left">
        <!-- Optional breadcrumb or title here -->
    </div>
    
    <div class="search-bar">
        <span class="material-symbols-rounded" style="color: #94A3B8; font-size: 20px;">search</span>
        <input type="text" placeholder="<?= __('Search...') ?>">
    </div>
    
    <div class="header-right">
        <div style="display:flex; align-items:center; gap:8px; margin-right:16px;">
            <a href="?lang=th" style="text-decoration:none; font-weight:600; color: <?= $_SESSION['lang'] === 'th' ? 'var(--primary)' : '#94A3B8' ?>;">TH</a>
            <span style="color:#CBD5E1;">|</span>
            <a href="?lang=en" style="text-decoration:none; font-weight:600; color: <?= $_SESSION['lang'] === 'en' ? 'var(--primary)' : '#94A3B8' ?>;">EN</a>
        </div>
        <span class="material-symbols-rounded header-icon">notifications</span>
        <span class="material-symbols-rounded header-icon">settings</span>
        <span class="material-symbols-rounded header-icon">help</span>
        <img src="https://ui-avatars.com/api/?name=Admin+User&background=0D8ABC&color=fff" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #E2E8F0; cursor: pointer;">
    </div>
</header>
