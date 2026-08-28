<?php
// ÉP HTTP 200
http_response_code(200);

// Header giống server gốc
header("Content-Type: text/plain; charset=UTF-8");
header("Retry-After: 60");
header("Content-Security-Policy: upgrade-insecure-requests");

// KHÔNG set Content-Encoding ở đây
// LiteSpeed sẽ tự gzip nếu client gửi Accept-Encoding: gzip

// Lấy uid và trả về Y NGUYÊN
$uid = $_GET['uid'] ?? '';
echo $uid;

// Không echo thêm gì nữa (không newline, không space)
exit;

