<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

include 'init.php';

$crypter = Crypter::init();

function logLine($msg) {
	$file = __DIR__ . '/getdata_debug.log';
	$line = '[' . date('Y-m-d H:i:s') . '] getdata.php - ' . $msg . PHP_EOL;
	@file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

$privatekey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANF5vcpEtIT2/xJI
pkLkRPAHCQWOTia3D5ZvnHw85Aa7EHn0eMiTug8AITzMk1J40bzdgjmXpKnJWNWg
Sa8YGMAvBrWXVKiV7BNqx3O/ouR/lXqZ3Tvi+eLIOnFyeGIOgZWvKj9suKy3762P
CpUzyGO/9xZD+K/lVf3kzQs4DX1pAgMBAAECgYBOmM07bZgGI98E7zli899h6GHc
Mc7k+97fZTVj8DzmwZ2nBLGlILi5FCVkeKX2VdrscHiCP4HLKv8n+KJtDi+Kjg1S
i04rrBaeuXAHx8Oh6mfOR3u9HzKPfVE1gGzvY+YUsUs0VVdMlkik2NqYEVK1JijX
tPepd163ip0xb8g3uQJBAP55rGYfsPd5K00Anng+TJDQ0Nx0cmQPPMkWW9vaOPcb
c3iiR8abCq5Pm/Uii7agKTpssax38KP67Xper7UN/sMCQQDSuwuPMAW7szHoMfe9
lOxnrnB8/Mz2lLYHy6BOB5TgH5rr7cnLWS7g3WWkUqzQOgv/WKbeWBlcIVZnV4QK
fKhjAkEAvuxm7lAEpAei9yjpvGlxZI1mxqAPWwcboftGfBKj/rH31qBanaWhQ9qy
th5vGFvd0tnODAoI397Z4Z+80GhppQJBALW25zvc/ESkPFfupqQLRGQPrx6IXDIR
gHpuY9iFfyJY/p2NmiJI0DzFjX1KcYzJUUkqaBJ5Q70HXluUwt7MKeMCQFQEXB2h
0JB/RtuZCeGGUSI8o5QihaaeOq1oeqLdkrAo8sdoDuxN0t3GBP9F8B5z7IbvPO9r
eyLs5Ncjj/kT4Wk=
-----END PRIVATE KEY-----
EOD;

$bindUrl = "https://lamdovn.fun/vip/nanohara.php";
$serverName = "Stricks Br";
$libFileName = "libalone.so";
$libPath = __DIR__ . "/" . $libFileName;

function base64Encode($s) { return base64_encode($s); }
function base64Decode($s) { return base64_decode($s, true); }
function sha256Upper($s) { return strtoupper(hash('sha256', $s)); }

function authProfileEncrypt($plain, $key) {
	$out = '';
	$klen = strlen($key);
	for ($i = 0; $i < strlen($plain); $i++) {
		$out .= chr(ord($plain[$i]) ^ ord($key[$i % $klen]));
	}
	return base64Encode($out);
}

function rsaSign($data) {
	global $privatekey;
	$sig = '';
	openssl_sign($data, $sig, $privatekey, OPENSSL_ALGO_SHA256);
	return base64Encode($sig);
}

function buildResponse($array) {
	$json = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$hash = sha256Upper($json);
	$enc  = authProfileEncrypt($json, $hash);
	$sign = rsaSign($json);

	$final = [
		"Data" => $enc,
		"Sign" => $sign,
		"Hash" => $hash
	];

	return base64Encode(json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function failResponse($msg) {
	die(buildResponse([
		"Status"        => "Failed",
		"MessageString" => $msg
	]));
}

if (!isset($_POST['token']) && !isset($_POST['tokserver_hk'])) {
	logLine('Missing token params');
	failResponse("Invalid request");
}

$tokenParam = isset($_POST['token']) ? $_POST['token'] : $_POST['tokserver_hk'];
$tokenLen = strlen((string)$tokenParam);
logLine('Token param length=' . $tokenLen);
$tokenRaw = base64Decode($tokenParam);
$tokenRawLen = is_string($tokenRaw) ? strlen($tokenRaw) : 0;
logLine('Base64 token decode length=' . $tokenRawLen);
$tokArr   = json_decode($tokenRaw, true);

if (!is_array($tokArr)) {
	logLine('Token JSON decode failed');
	failResponse("Invalid token");
}

if (isset($tokArr['Data'], $tokArr['Hash'])) {
	$encData = $tokArr['Data'];
	$hashCli = $tokArr['Hash'];
} elseif (isset($tokArr['Dados_hk'], $tokArr['Hash_hk'])) {
	$encData = $tokArr['Dados_hk'];
	$hashCli = $tokArr['Hash_hk'];
} else {
	logLine('Token missing Data/Hash fields');
	failResponse("Invalid token format");
}

$encDataLen = strlen((string)$encData);
$hashCliLen = strlen((string)$hashCli);
logLine('EncData length=' . $encDataLen . ' Hash length=' . $hashCliLen);

$encBytes = base64Decode($encData);
$encBytesLen = is_string($encBytes) ? strlen($encBytes) : 0;
logLine('EncData base64 decode length=' . $encBytesLen);

$plain = $crypter->decryptByPrivate($privatekey, $encBytes);
if ($plain === false) {
	logLine('Decrypt failed');
	failResponse("Decrypt failed");
}

if (sha256Upper($plain) !== strtoupper($hashCli)) {
	logLine('Hash mismatch');
	failResponse("Hash invalido");
}

$data = json_decode($plain, true);
if (!is_array($data)) {
	logLine('Plaintext JSON decode failed');
	failResponse("Dados invalidos");
}

logLine('Decrypt OK uname=' . ($data['uname'] ?? '') . ' cs=' . ($data['cs'] ?? ''));

$libBase64 = "-";
$libSha256 = "-";
if (file_exists($libPath)) {
	$libBytes = file_get_contents($libPath);
	if ($libBytes !== false) {
		$libBase64 = base64Encode($libBytes);
		$libSha256 = strtoupper(hash('sha256', $libBytes));
		logLine('Lib loaded name=' . $libFileName . ' size=' . strlen($libBytes));
	} else {
		logLine('Lib read failed: ' . $libPath);
	}
	} else {
		logLine('Lib missing: ' . $libPath);
}

$now = new DateTime('now', new DateTimeZone('UTC'));
$now2 = clone $now;
$interval = $now->diff($now2);

$response = [
	"Status"           => "Success",
	"MessageString"    => "",
	"Bind"             => $bindUrl,
	"Name"             => $serverName,
	"Lib"              => $libBase64,
	"Sha256"           => $libSha256,
	"Username"         => null,
	"SubscriptionLeft" => null,
	"Vendedor"         => null,
	"RegisterDate"     => null,
	"0"                => $now,
	"1"                => $now2,
	"2"                => $interval,
	"3"                => "0",
	"Dias"             => "0"
];

logLine('Response ready');

echo buildResponse($response);
?>
