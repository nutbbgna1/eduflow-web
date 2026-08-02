<?php
// admin/includes/db.php
// Include the main database connection (shared with teacher app)
require_once dirname(__DIR__, 2) . '/config/db.php';

// Helper: get initials color from name
function getAvatarColor($name) {
    $colors = ['#2563EB','#7C3AED','#DB2777','#059669','#D97706','#0891B2','#DC2626','#4F46E5'];
    if (empty($name)) return $colors[0];
    $idx = ctype_alpha($name[0]) ? ord(strtoupper($name[0])) % count($colors) : 0;
    return $colors[$idx];
}

// Helper: get initials
function getInitials($first, $last) {
    $f = !empty($first) ? mb_substr($first, 0, 1) : '';
    $l = !empty($last) ? mb_substr($last, 0, 1) : '';
    return strtoupper($f . $l);
}

// Helper: Thai leave type
function leaveTypeLabel($type) {
    return match($type) {
        'sick'     => 'ลาป่วย',
        'personal' => 'ลากิจ',
        'vacation' => 'ลาพักผ่อน',
        default    => ucfirst($type ?? '')
    };
}

// Helper: Thai day
function thaiDay($day) {
    return match($day) {
        'Monday'    => 'จันทร์',
        'Tuesday'   => 'อังคาร',
        'Wednesday' => 'พุธ',
        'Thursday'  => 'พฤหัสบดี',
        'Friday'    => 'ศุกร์',
        'Saturday'  => 'เสาร์',
        'Sunday'    => 'อาทิตย์',
        default     => $day
    };
}
?>
