<?php
$file = '/Applications/XAMPP/xamppfiles/htdocs/eduflow/admin/css/admin.css';
$css = file_get_contents($file);

// 1. Update body
$css = preg_replace('/body\s*\{[^}]+\}/', "body {\n  font-family: 'Inter', sans-serif;\n  background: var(--background);\n  color: var(--text-primary);\n  line-height: 1.5;\n  min-height: 100vh;\n  margin: 0;\n  display: flex;\n  -webkit-font-smoothing: antialiased;\n}", $css);

// 2. Update app-container
$css = preg_replace('/\.app-container\s*\{[^}]+\}/', ".app-container {\n  display: flex;\n  flex: 1;\n  min-height: 100vh;\n  width: 100%;\n}", $css);

file_put_contents($file, $css);
echo "CSS Updated.\\n";
