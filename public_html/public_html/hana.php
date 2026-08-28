<?php
session_start();
// Inline config replacing DB.php/Utils.php/ddata.php.
$servername = "localhost";
$username = "mbktunp_hama";
$password = "mbktunp_hama";
$database = "mbktunp_hama";
$tabela = "users";
$required_game = "zsmmobile";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB connection failed.");
}
$conn->set_charset("utf8mb4");
$data = $_POST;

$loadinfo = array();

function ext($ar) {
    $vmg = json_encode($ar);
    exit($vmg);
}

$encryption_key = "QgddxVnVjMRJBGOonubvWRNPRjCSPrNN"; // 32 byte
$iv = "XvfjZmDIMHbLeozW";                     // 16 byte

function encryptAES($data, $key, $iv) {
    return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv));
}

function generateToken($user_key, $uuid) {
    $static_key = "TuanMeta";
    $secret = "OfZbWRtfdVeHDibEIOpDuFaybQgETEoH";
    $auth = $static_key . "-" . $user_key . "-" . $uuid . "-" . $secret;
    return md5($auth); // Tạo MD5 hash
}

$uname = isset($data["uname"]) ? (string)$data["uname"] : "";
$uuid = isset($data["cs"]) ? (string)$data["cs"] : ""; // cs là UUID từ client
$uname_db = mysqli_real_escape_string($conn, $uname);

if ($uname == null || preg_match("([a-zA-Z0-9]+)", $uname) == 0) {
    $loadinfo["status"] = "false";
    $loadinfo["reason"] = "Key Đã Bị Lỗi Hoặc Không Chính Xác, Vui Lòng Vượt Link Để Nhận Key Mới :3";
    ext($loadinfo);
}

$query = $conn->query("SELECT * FROM `$tabela` WHERE `username` = '$uname_db' AND `game` = '$required_game'");
if ($query->num_rows < 1) {
    $loadinfo["status"] = "false";
    $loadinfo["reason"] = "Key Đã Bị Lỗi Hoặc Không Chính Xác, Vui Lòng Vượt Link Để Nhận Key Mới :3";
    ext($loadinfo);
}

$res = $query->fetch_assoc();
$row_id = (int)$res["id"];

$created_at = $res["created_at"];
if ($created_at == null || $created_at == '0000-00-00 00:00:00') {
    $conn->query("UPDATE `$tabela` SET `created_at` = CURRENT_TIMESTAMP WHERE `id` = $row_id");
    $created_at = date('Y-m-d H:i:s');
}

$expired_date = $res["expired_date"];
if ($expired_date == null || $expired_date == '0000-00-00 00:00:00') {
    $expired_date = $res["expired"];
}
if ($expired_date == null || $expired_date == '0000-00-00 00:00:00') {
    $duration = (int)$res["duration"];
    if ($duration > 0) {
        $base_time = $created_at ? $created_at : date('Y-m-d H:i:s');
        $adicionardias = date('Y-m-d H:i:s', strtotime($base_time . " +$duration hours"));
        $conn->query("UPDATE `$tabela` SET `expired_date` = '$adicionardias', `expired` = '$adicionardias' WHERE `id` = $row_id");
        $expired_date = $adicionardias;
    }
}

if ($expired_date != null && $expired_date != '0000-00-00 00:00:00') {
    if (strtotime(date('Y-m-d H:i:s', strtotime("+0 hours"))) > strtotime($expired_date)) {
        $conn->query("DELETE FROM `$tabela` WHERE `id` = $row_id");
        $loadinfo["status"] = "false";
        $loadinfo["reason"] = "Key Của Bạn đã Hết Hạn 🗓.Vui Lòng Vượt Link Để Nhận Key Mới :3";
        ext($loadinfo);
    }
}

$uidup = $uuid;
$device_list = array();
$device_source = "";
if (!empty($res["devices"])) {
    $device_source = $res["devices"];
} elseif (!empty($res["UID"])) {
    $device_source = $res["UID"];
}

if ($device_source !== "") {
    $decoded_devices = json_decode($device_source, true);
    if (is_array($decoded_devices)) {
        $device_list = $decoded_devices;
    } else {
        $device_list = array_filter(array_map('trim', explode(',', $device_source)), 'strlen');
    }
}

if ($uidup !== "") {
    if (empty($device_list)) {
        $device_db = $conn->real_escape_string($uidup);
        $conn->query("UPDATE `$tabela` SET `devices` = '$device_db', `UID` = '$device_db', `updated_at` = CURRENT_TIMESTAMP WHERE `id` = $row_id");
        $device_list = array($uidup);
    } elseif (!in_array($uidup, $device_list, true)) {
        $loadinfo["status"] = "false";
        $loadinfo["reason"] = "Key này tồn tại trên thiết bị khác :3";
        ext($loadinfo);
    }
}

if ((int)$res["status"] !== 1) {
    $loadinfo["status"] = "false";
    $loadinfo["reason"] = "Key Của Bạn Đã Bị Tạm Khóa";
    ext($loadinfo);
}

$datenow = date('Y-m-d H:i:s', strtotime("+0 hours"));
$user = $res['username'];
$vendedor = $res['registrator'];
$currentTimer = $expired_date;
$ngayend = "0 Ngày 0 Giờ 0 Phút .";
if (!empty($currentTimer) && $currentTimer != '0000-00-00 00:00:00') {
    $database = date_create($currentTimer);
    if ($database !== false) {
        $datadehoje = date_create();
        $resultado = date_diff($database, $datadehoje);
        $ngayend = date_interval_format($resultado, '%a Ngày %h Giờ %i Phút .');
    }
}

// Tạo token
$token = generateToken($uname, $uuid);

$loadinfo["status"] = "Ok";
$loadinfo["reason"] = "";
$loadinfo["link"] = encryptAES("https://t.me/mrlightxvdd", $encryption_key, $iv);
$loadinfo["phienban"] = encryptAES("2.0", $encryption_key, $iv);
$loadinfo["thongbao"] = encryptAES("Đã Tìm Thấy Phiên Bản Mới Nhất. \nVui Lòng Bấm Tải APK Mới Nhất Có Thể Cài Đè Lên APK Cũ!", $encryption_key, $iv);
$loadinfo["Username"] = encryptAES($uname, $encryption_key, $iv);
$loadinfo["SubscriptionLeft"] = encryptAES($currentTimer, $encryption_key, $iv);
$loadinfo["Validade"] = encryptAES($currentTimer, $encryption_key, $iv);
$loadinfo["Vendedor"] = encryptAES($res["registrator"], $encryption_key, $iv);
$loadinfo["Dias"] = encryptAES($ngayend, $encryption_key, $iv);
$loadinfo["Token"] = encryptAES($token, $encryption_key, $iv); // Thêm token vào response

ext($loadinfo);
?>
