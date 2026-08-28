<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Đọc request từ client
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

// Log request để debug
$logFile = __DIR__ . '/api_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " REQUEST: " . $input . "\n", FILE_APPEND);

// Validate request
if (!$requestData || !isset($requestData['auth']) || !isset($requestData['device'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

// Lấy thông tin từ request
$auth = $requestData['auth'];
$device = $requestData['device'];
$package = $requestData['package'] ?? '';
$phienban = $requestData['phienban'] ?? '1';

// Kiểm tra auth token (tùy chỉnh logic của bạn)
$validAuth = "doanhlam"; // Thay đổi theo yêu cầu
if ($auth !== $validAuth) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid auth token'
    ]);
    exit;
}

// Public Key (RSA public key dùng cho SSL pinning hoặc encryption)
$publicKeyPEM = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwN0+hG7QRFX36h8cDdHq
DcF8aLsqzU6K3g8lKJLpBLv9K2Q9x1HV2eKq3kX+M7G0fPnrR4L5mQ7vK0dH8zF5
yN3bT9rU6L4mZ8wP1cH2dN9V3kX6qN1H7F3cL9pQ2K8rU5tZ4mY3W0dH6oP9bL7K
1Q5vR8X2yT4mN3W0dH6oP9bL7K1Q5vR8X2yT4mN3cL9pQ2K8rU5tZ4mY3W0dH6oP
9bL7K1Q5vR8X2yT4mN3W0dH6oP9bL7K1Q5vR8X2yT4mN3cL9pQ2K8rU5tZ4mY3W0
dH6oP9bL7K1Q5vR8X2yT4mN3W0dH6oP9bL7K1Q5vR8X2yT4mN3cL9pQ2K8rU5tZ4
mY3W0dH6oQIDAQAB
-----END PUBLIC KEY-----
EOD;

$publicKeyBase64 = base64_encode($publicKeyPEM);

// Danh sách nhạc
$mp3List = [
    "https://vip.vnhax.top/public/files/music/xich linh.mp3",
    "https://vip.vnhax.top/public/files/music/list 1.mp3",
    "https://vip.vnhax.top/public/files/music/list tet.mp3",
    "https://vip.vnhax.top/public/files/music/em thua co ta.mp3",
    "https://vip.vnhax.top/public/files/music/remix trung 3.mp3",
    "https://vip.vnhax.top/public/files/music/list buon.mp3",
    "https://vip.vnhax.top/public/files/music/list 2.mp3",
    "https://vip.vnhax.top/public/files/music/list buon 2.mp3",
    "https://vip.vnhax.top/public/files/music/chudaibi.mp3",
    "https://vip.vnhax.top/public/files/music/list 3.mp3",
    "https://vip.vnhax.top/public/files/music/nhac trung 1.mp3",
    "https://vip.vnhax.top/public/files/music/phap ta ba.mp3",
    "https://vip.vnhax.top/public/files/music/remix trung 2.mp3",
    "https://vip.vnhax.top/public/files/music/remix trung 1.mp3"
];

// Đường dẫn đến file lib .so cần gửi cho client
$libPath = __DIR__ . '/libs/libmodule_core.so';

$libBase64 = '';
if (file_exists($libPath)) {
    $libContent = file_get_contents($libPath);
    $libBase64 = base64_encode($libContent);
}

// Tính hạn sử dụng (30 ngày từ bây giờ)
$hansudung = date('Y-m-d H:i', strtotime('+999 days'));

// Chuẩn bị response - ĐÚNG FORMAT NHƯ SERVER THẬT
$response = [
    'ketqua' => 'thanhcong',
    'mes' => 'Đăng Nhập Thành Công',
    'auth' => $auth,
    'device' => $device,
    'package' => $package,
    'hansudung' => $hansudung,
    'beta' => 'khong',
    'vip' => 'true',
    'code' => 'vipadmin',
    'lib' => $libBase64,
    'mp3' => json_encode($mp3List),
    'bidanh' => 'Mr Light',
    'gameName' => 'Liên Quân',
    'phienban' => 'V1',
    'icon' => 'https://vip.vnhax.top/uploads/icons/68fb464f9144a_icon.png',
    'icon_size' => '35',
    'boder' => '999',
    'text_size' => '15',
    'menu_width' => '500',
    'menu_height' => '250',
    'background1_color' => '000000',
    'background2_color' => '000000',
    'color_title' => 'FFFFFFFF',
    'toggle_text_color' => 'FFFFFFFF',
    'publicKey' => $publicKeyBase64
];

// Log response
file_put_contents($logFile, date('Y-m-d H:i:s') . " RESPONSE: " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Trả về response (với unicode và slashes không escaped)
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
