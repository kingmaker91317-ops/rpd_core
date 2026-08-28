<?php
/**
 * Power Cheats - Secure File Proxy
 * This script prevents unauthorized downloads by checking for a secret header.
 */

// 1. THIS MUST MATCH THE KEY IN YOUR ANDROID APP
$SECRET_KEY = "PowerCheats_Secret_99";

// 2. CHECK THE HEADER
// Note: PHP converts 'X-Auth-Token' to 'HTTP_X_AUTH_TOKEN'
if (!isset($_SERVER['HTTP_X_AUTH_TOKEN']) || $_SERVER['HTTP_X_AUTH_TOKEN'] !== $SECRET_KEY) {
    // If someone tries to open this in a browser, show a fake 404 error
    header("HTTP/1.1 404 Not Found");
    echo "<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>";
    exit;
}

// 3. THE REAL FILE TO DOWNLOAD
$real_file = 'powerxxxx.sh'; 

if (file_exists($real_file)) {
    // Set headers to hide the file type and force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // Generic binary type
    header('Content-Disposition: attachment; filename="engine.dat"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($real_file));
    
    // Clear buffer to avoid corrupting the file
    ob_clean();
    flush();
    
    // Send the file content
    readfile($real_file);
    exit;
} else {
    // File not found on server
    header("HTTP/1.1 404 Not Found");
    echo "Error: Engine file missing on server.";
    exit;
}
?>
