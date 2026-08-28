<?php
header('Content-Type: application/json; charset=utf-8');

$staticWords = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";

$game = $_POST['game'] ?? '';
$userKey = $_POST['user_key'] ?? '';
$serial = $_POST['serial'] ?? '';

$plain = $game . '-' . $userKey . '-' . $serial . '-' . $staticWords;
$token = md5($plain);

$expire = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))
    ->add(new DateInterval('P36D'))
    ->format('Y-m-d H:i:s');

// RNG is Unix Timestamp
$rng = time();

$response = [
    "status" => true,
    "data" => [
        "modname" => " ",
        "mod_status" => "",
        "credit" => "ONLINE MOD",
        "token" => $token,
        "exdate" => $expire,
        "EXP" => $expire,
        "ESP" => "on",
        "ITEMS" => "on",
        "AIM" => "on",
        "BULLETTRACK" => "off",
        "FLOATING" => "on",
        "MEMORY" => "on",
        "SETTING" => "off",
        "ANTICRACK" => "on",
        "Protect" => "List Of Games",
        "Enc" => "List Of Games",
        "rng" => $rng
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
