<?php
/*******************************
 *  LKTEAM LOGIN – FIXED 2025  *
 *  Đã sửa hoàn toàn cho bảng mới
 *  Logged_TokHK giờ phản hồi đúng với client
 *******************************/

error_reporting(0);
ini_set('display_errors', 0);

include 'init.php'; // chứa $con, Crypter, v.v.

$crypter = Crypter::init();

/**
 * PRIVATE KEY – BẮT BUỘC PHẢI ĐÚNG VỚI PUBLIC KEY TRONG APP
 */
$privatekey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANYucRdjboHTa7mv
7AfDFeTxnyRhJy+xLAk330cLE07jh0tXhZWsU3WMAkYXqW+TDWFCTXfoe71ugCp2
XIfPFlYau00kt3gG+CuAzeA2oPmAmxrWLVcY7Eo4YphnnICLlS+iMB93gohQiJTb
k+SrKwznykYzaJ4IkwwglITiX93DAgMBAAECgYBHHfqdOn3aC9qMOJbV6PgfIf+m
s7+wPVMS6INx8oKBwlkNMk1/6k14DXo/zeGL07cwoTRZ6U8VEljqQIzu51tRtlqR
zcze70E/gvZdFaHVp9T5xVRuSpC5l64o93O8QvV0Pb/jAmgyEdkgpMk/i/PrAGQi
Ih4HBYZdZodwpxopWQJBAO876O303mXs4ac37OTEFKGtIXVjxNVD3dWmM5/wtQ5G
ND/yrkT93i9sA/lqmAoc+TRpPE5Y+fVFSq2rXBTg5+UCQQDlMRBUwDcRQqRBTNLo
2FmENWwuVBlOILjNCjG6YY36b37qntctbANWJGIiN+4XBUQhUEfCcvPOcYsKdWjd
LgSHAkEAzOq8Mlc0yImHH/y/ZZSvN21c43h5+VMQiRi7z5wW+gsYZk4xB9eMoYIc
RWAQq1j1/PbHOTTtpjGcLfZCAYBEIQJAZvyyEvNAi2//sRwdVeYJ63+5+eNub44C
nJgtGkxF6Tf6tuDjXhTANxAoTKoHQa7rG3Egnb7b0XNyACQcF+9atwJAUJ4og4uZ
w+ec+40tvxrhHHFM7tPRedhEKpGALtpif/J2u4I0Jnqv4Ii9va0HRcEJf44jPN2j
Ton5ezJZv9zfyg==
-----END PRIVATE KEY-----
EOD;

/* ================== HELPERS ================== */
if (!function_exists('toJson')) {
    function toJson($arr) { return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
}
if (!function_exists('fromJson')) {
    function fromJson($s, $assoc = true) { return json_decode($s, $assoc); }
}
if (!function_exists('toBase64')) {
    function toBase64($s) { return base64_encode($s); }
}
if (!function_exists('fromBase64')) {
    function fromBase64($s) { return base64_decode($s); }
}
if (!function_exists('sha256')) {
    function sha256($data) { return strtoupper(hash('sha256', $data)); }
}

if (!function_exists('profileEncrypt')) {
    function profileEncrypt($plain, $hashKey) {
        $plainChars = mb_str_split($plain);
        $keyChars   = mb_str_split($hashKey);
        $outChars   = [];
        $klen       = count($keyChars);
        foreach ($plainChars as $i => $ch) {
            $kc = $keyChars[$i % $klen];
            $outChars[] = chr(ord($ch) ^ ord($kc));
        }
        return base64_encode(implode('', $outChars));
    }
}

function sign_sha256_rsa($plain, $privatekey) {
    $signature = '';
    openssl_sign($plain, $signature, $privatekey, OPENSSL_ALGO_SHA256);
    return $signature;
}

/* ================== TOKEN RESPONSE ================== */
function tokenResponse($plainData) {
    global $privatekey;
    $json = toJson($plainData);
    $hash = sha256($json);
    $dados = profileEncrypt($json, $hash);
    $signBin = sign_sha256_rsa($json, $privatekey);
    $signB64 = toBase64($signBin);

    $outer = [
        "Dados_hk" => $dados,
        "Sign_hk"  => $signB64,
        "Hash_hk"  => $hash
    ];
    return toBase64(toJson($outer));
}

function fail($msg) {
    PlainDie(tokenResponse([
        "ConnectSt_hk"  => "Failed",
        "MessageFromSv" => $msg
    ]));
}

/* ================== MAIN HANDLE ================== */
if (!isset($_POST['tokserver_hk'])) {
    fail("Invalid request");
}

$tokenRaw = fromBase64($_POST['tokserver_hk']);
$tokArr   = fromJson($tokenRaw, true);

if (!is_array($tokArr) || !isset($tokArr['Dados_hk'], $tokArr['Hash_hk'])) {
    fail("Invalid token");
}

/* LẤY TOKEN CLIENT GỬI LÊN */
$tok_cli = $tokArr['Tok_hk'] ?? null;

$encData = $tokArr['Dados_hk'];
$hashCli = $tokArr['Hash_hk'];

/* ====== RSA DECRYPT ====== */
$binCipher = fromBase64($encData);
$dec       = $crypter->decryptByPrivate($privatekey, $binCipher);
if ($dec === false || trim($dec) === '') {
    fail("Falha ao descriptografar os dados");
}
$dec = trim($dec);

/* ====== HASH CHECK ====== */
if (strtoupper($hashCli) !== strtoupper(sha256($dec))) {
    fail("Hash inválido");
}

$data = fromJson($dec, true);
if (!is_array($data)) {
    fail("Formato de dados inválido");
}

$uname = $data['User_hk'] ?? null;
$uid   = $data['Uid_hk']  ?? null;
$ip    = $data['Ip_hk']   ?? $_SERVER['REMOTE_ADDR'];

/* ====== VALIDATION ====== */
if (!$uname || preg_match('/^[a-zA-Z0-9\-]+$/', $uname) !== 1) {
    fail("Usuario Invalido");
}
if (!$uid) {
    fail("ID do dispositivo invalido");
}

$uEsc = $con->real_escape_string($uname);

/* ====== CHECK USER ====== */
$q = $con->query("SELECT * FROM `users` WHERE `username` = '$uEsc'");
if ($q->num_rows < 1) {
    fail("User Invalido");
}
$res = $q->fetch_assoc();

/* ====== CHECK GAME ====== */
if (!isset($res['game']) || $res['game'] !== 'LKTEAM') {
    fail("Key Invalido");
}

/* ====== CHECK BAN ====== */
if (isset($res['status']) && (int)$res['status'] === 0) {
    fail("Login has been Banned!");
}

/* ====== FIRST REGISTER TIME ====== */
if ($res['registered'] == null) {
    $con->query("UPDATE `users` SET `registered` = CURRENT_TIMESTAMP WHERE `username` = '$uEsc'");
}

/* ====== DEVICE LIMIT ====== */
$device_limit = isset($res['max_devices']) ? (int)$res['max_devices'] :
                (isset($res['device_limit']) ? (int)$res['device_limit'] : 1);

if ($device_limit < 1) $device_limit = 1;

$storedUIDs = isset($res['UID']) ? trim($res['UID']) : '';
$uids = $storedUIDs === '' || $storedUIDs === null ? [] : array_filter(array_map('trim', explode(',', $storedUIDs)));

if (!in_array($uid, $uids, true)) {
    if (count($uids) >= $device_limit) {
        fail("Device limit reached");
    }
    $uids[] = $uid;
    $newUIDs = implode(',', $uids);

    $durationHours = isset($res['duration']) ? (int)$res['duration'] : 24;
    $registeredAt  = $res['registered'] ?: date('Y-m-d H:i:s');
    $expiresAt     = $res['expired'] ?? $res['expired_date'];

    if (!$expiresAt) {
        $expiresAt = date('Y-m-d H:i:s', strtotime($registeredAt) + $durationHours * 3600);
    }

    $con->query("UPDATE `users` SET 
        `UID` = '".$con->real_escape_string($newUIDs)."',
        `expired` = '".$con->real_escape_string($expiresAt)."'
        WHERE `username` = '$uEsc'");

    $res['expired'] = $expiresAt;
}

/* ====== EXPIRATION CHECK ====== */
$expTs = strtotime($res['expired'] ?? $res['expired_date']);
if ($expTs !== false && time() > $expTs) {
    fail("Assinatura expirada");
}

/* ====== DAYS LEFT ====== */
$Asabsa = date_create($res['expired'] ?? $res['expired_date']);
$datadehoje = date_create();
$resultado = date_diff($Asabsa, $datadehoje);
$dias = date_interval_format($resultado, '%a');

$ack = [
    "ConnectSt_hk"    => "HasBeenSucceeded",
    "MessageFromSv"   => "Conectado com sucesso",

    "Logged_UserHK"   => $uname,
    "Logged_TokHK"    => $tok_cli,

    /* TRƯỜNG BẮT BUỘC CHO FLOATER */
    "piddaemon"       => rand(1111, 9999),
    "jofjofjoe"       => "9",

    "SubscriptionLeft"=> (string)$dias
];

echo tokenResponse($ack);

?>
