<?php
// This header is used by sub-pages. index.php has its own top bar.
$page = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$h_teacher = null;
if (isset($pdo) && isset($current_user_id)) {
    $h_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $h_stmt->execute(['id' => $current_user_id]);
    $h_teacher = $h_stmt->fetch();
}
$h_name = ($h_teacher['first_name'] ?? '') . ' ' . ($h_teacher['last_name'] ?? '');
$h_initials = strtoupper(mb_substr($h_teacher['first_name'] ?? 'T', 0, 1) . mb_substr($h_teacher['last_name'] ?? '', 0, 1));
$h_colors = ['#2563EB','#3B82F6','#EC4899','#10B981','#F59E0B','#06B6D4','#EF4444','#4F46E5'];
$h_char = $h_teacher['first_name'] ?? 'T';
$h_color = $h_colors[ctype_alpha($h_char[0] ?? 'T') ? ord(strtoupper($h_char[0])) % count($h_colors) : 0];
?>
<header style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px 8px; background:var(--background, #F1F5F9);">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="index.php" style="display:flex; align-items:center; gap:8px; text-decoration:none; color:inherit;">
            <span class="material-symbols-rounded" style="font-size:24px; color:#2563EB;">arrow_back</span>
        </a>
        <span style="font-size:17px; font-weight:700;"><?= ucfirst($page) ?></span>
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
        <a href="../logout.php" style="color:#EF4444; display:flex; align-items:center; text-decoration:none;">
            <span class="material-symbols-rounded">logout</span>
        </a>
        <div style="width:36px;height:36px;border-radius:12px;background:<?= $h_color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <?= $h_initials ?>
        </div>
    </div>
</header>
