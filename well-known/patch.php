<?php
$files = array_merge(
    glob('app/Views/User/*.php'),
    glob('app/Views/Keys/*.php'),
    glob('app/Views/Admin/*.php')
);

foreach($files as $file) {
    if(strpos($file, 'server_management.php') !== false) continue;
    $content = file_get_contents($file);
    if(strpos($content, 'admin/server-management') !== false) continue;
    
    // Find the create-referral block
    $pattern = '/(<a href="<\?= site_url\(\'admin\/create-referral\'\).*?<\/a>)/s';
    $replacement = "$1\n                <a href=\"<?= site_url('admin/server-management') ?>\" class=\"sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group\">\n                    <i class=\"fas fa-server w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors\"></i>\n                    <span class=\"font-medium\">Server Management</span>\n                </a>";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    if($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo 'Updated: ' . $file . "\n";
    }
}
