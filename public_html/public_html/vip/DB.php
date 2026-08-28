<?php
// DB.php
// Configure DB connection here
$db_host = 'localhost';
$db_user = 'mbktunp_hama';
$db_pass = 'mbktunp_hama';
$db_name = 'mbktunp_hama';
$db_port = 3306; // nếu cần thay đổi

// tạo kết nối MySQLi
$con = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($con->connect_error) {
    // tùy xử lý: log hoặc dừng
    error_log("DB connection failed: " . $con->connect_error);
    die("Database connection failed");
}
$con->set_charset("utf8mb4");
