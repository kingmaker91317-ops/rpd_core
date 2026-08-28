<?php
session_start();
include 'DB.php';
include 'Utils.php';
// include 'ddata.php'; // File không tồn tại - đã xóa
$data = $_POST;

// Định nghĩa các biến từ ddata.php (nếu cần)
$tabela = "users"; // Tên bảng trong database
$column_key = "username"; // Tên cột chứa key/username (thay đổi nếu cần: chave, key, license_key, etc.)
$tt = "on"; // Trạng thái hệ thống (on/off)
$keybm = ""; // Key bypass nếu có
$nd = "Hệ thống đang bảo trì"; // Thông báo khi tắt

$loadinfo = Array();
function ext($ar){
    $vmg = json_encode($ar);
    exit($vmg);
} 
$uname = mysqli_real_escape_string($con, $data["uname"]);



$status = "$tt";
if($uname == "$keybm"){
}
else{

if($status == "off"){
$loadinfo["status"] = "false";
$loadinfo["reason"] = "$nd";
ext($loadinfo);
}
}
//Username Validator




/*
if($uname != "MemeVN") {
$loadinfo["status"] = "false";
$loadinfo["reason"] = "Chó Meme SucVat !";
ext($loadinfo);
}
*/
if($uname == null || preg_match("([a-zA-Z0-9]+)", $uname) == 0){
$loadinfo["status"] = "false";
$loadinfo["reason"] = "Key Đã Bị Lỗi Hoặc Không Chính Xác, Vui Lòng Vượt Link Để Nhận Key Mới Hoặc Liên Hệ Zalo 0987242940 Để Mua Key :3";
ext($loadinfo);
}

$query = $con->query("SELECT * FROM `$tabela` WHERE `$column_key` = '".$uname."'");
if($query->num_rows < 1){
$loadinfo["status"] = "false";
$loadinfo["reason"] = "Key Đã Bị Lỗi Hoặc Không Chính Xác, Vui Lòng Vượt Link Để Nhận Key Mới Hoặc Liên Hệ Zalo 0987242940 Để Mua Key :3";
ext($loadinfo);
}


$res = $query->fetch_assoc();
if($res["created_at"] == NULL || $res["created_at"] == '0000-00-00 00:00:00'){
    $query = $con->query("UPDATE `$tabela` SET `created_at` = CURRENT_TIMESTAMP WHERE `$column_key` = '$uname'");
}
$hansudung = $res['expired_date'];
if($res["expired_date"] == NULL || $res["expired_date"] == '0000-00-00 00:00:00'){
    $dias = $res["duration"];
    $adicionardias = date('Y-m-d H:i:s', strtotime("+$dias hours"));
    $query = $con->query("UPDATE `$tabela` SET `expired_date` = '$adicionardias' WHERE `$column_key` = '$uname'");
} else 

if(strtotime(date('Y-m-d H:i:s', strtotime("+0 hours"))) > strtotime($res['expired_date'])){
    $query = $con->query("DELETE FROM `$tabela` WHERE `$column_key` = '".$uname."'");
    $loadinfo["status"] = "false";
    $loadinfo["reason"] = "Key Của Bạn đã Hết Hạn 🗓.Vui Lòng Vượt Link Để Nhận Key Mới Hoặc Liên Hệ Zalo 0987242940 Để Mua Key :3";
    ext($loadinfo);
}


$uidup = $data["cs"];

// Parse existing devices from JSON
$existing_devices = !empty($res["devices"]) ? json_decode($res["devices"], true) : [];
if(!is_array($existing_devices)) $existing_devices = [];

$max_allowed = $res["max_devices"];

// Check if current device already registered
if(!in_array($uidup, $existing_devices)){
    // Device not registered, check if we can add it
    if(count($existing_devices) >= $max_allowed){
        $loadinfo["status"] = "false";
        $loadinfo["reason"] = "Đã Tồn Tại Thiết Bị Khác, Nếu Là Key Mua Thì Liên Hệ Zalo 0987242940 Để Được Admin Reset";
        ext($loadinfo);
    } else {
        // Add new device
        $existing_devices[] = $uidup;
        $devices_json = json_encode($existing_devices);
        $query = $con->query("UPDATE `$tabela` SET `devices` = '$devices_json' WHERE `$column_key` = '".$uname."'");
    }
}




if($res["status"] == 0 || $res["status"] == "expire"){
$loadinfo["status"] = "false";
$loadinfo["reason"] = "Join TG @BypassPubgCP Để Nhận Key Free";
ext($loadinfo);
}


        
$datenow = date('Y-m-d H:i:s', strtotime("+0 hours"));
$user = $res[$column_key];
$vendedor = $res['registrator'];
    $currentTimer = $res["expired_date"];
    $database = date_create($currentTimer);
    $datadehoje = date_create();
    $resultado = date_diff($database, $datadehoje);
    $ngayend = date_interval_format($resultado, 'Time Còn Lại : %a Ngày %h Giờ %i Phút .');

$lib = readFileData("LibOnHNGDuMeMay/libMeme64.so");

$updete = array();
///
$loadinfo["status"] = "Ok";
$loadinfo["reason"] = "";
$loadinfo["link"] = "https://bom.so/HackLQMVNx64";
$loadinfo["phienban"] = "1.61.1.5";
$loadinfo["thongbao"] = "Đã Tìm Thấy Phiên Bản : V" . $loadinfo["phienban"] . " ( Mới Nhất ). \nẤn Ok Tải Hack Mới (Nhớ Xoá Hack Cũ)";
$loadinfo["Username"] = $uname;
$loadinfo["lib"] = toBase64($lib);
$loadinfo["SubscriptionLeft"] = $res["expired_date"];
$loadinfo["Validade"] = $res["expired_date"];
$loadinfo["Vendedor"] = $res["registrator"];
$loadinfo["Dias"] = $ngayend;
ext($loadinfo);


?>