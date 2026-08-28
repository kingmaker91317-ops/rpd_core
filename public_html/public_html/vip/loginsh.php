<?php
// Allow all methods; validate by presence of parameters

include 'init.php';

$crypter = Crypter::init();
$privatekey = readFileData("Keys/PrivateKey.prk");

// Simple error wrapper so client still gets a signed token
function fail_ack($msg)
{
	global $crypter, $privatekey;
	$payload = array(
		"Checkvar" => "Failed",
		"MessageString" => $msg,
		"Function" => "Error"
	);
	$response = tokenResponse($payload);
	header("Content-Length: " . strlen($response));
	echo $response;
	exit;
}

function log_event($message)
{
	error_log("[loginlamdo] " . $message);
}

function tokenResponse($data)
{
	global $crypter, $privatekey;
	$data = toJson($data);
	$datahash = sha256($data);
	$acktoken = array(
		"app_Data" => profileEncrypt($data, $datahash),
		"app_Sign" => toBase64($crypter->signByPrivate($privatekey, $data)),
		"app_Hash" => $datahash
	);
	return toBase64(toJson($acktoken));
}

function extract_param($raw)
{
	if ($raw === null || $raw === '') {
		return null;
	}
	// Try query-string style: ap_vkm=...
	parse_str($raw, $parsed);
	if (isset($parsed['ap_vkm'])) {
		return $parsed['ap_vkm'];
	}
	return $raw;
}

$rawParam = null;
if (isset($_GET['ap_vkm'])) {
	$rawParam = $_GET['ap_vkm'];
	log_event("Got ap_vkm from GET. length=" . strlen($rawParam));
} elseif (isset($_POST['ap_vk11mccccxccc'])) {
	$rawParam = $_POST['ap_vk11mccccxccc'];
	log_event("Got ap_vk11mccccxccc from POST. length=" . strlen($rawParam));
} else {
	$body = file_get_contents('php://input');
	if ($body !== false && $body !== '') {
		log_event("Got raw body. length=" . strlen($body));
		$rawParam = extract_param($body);
		if ($rawParam !== null) {
			log_event("Parsed ap_vkm from raw body. length=" . strlen($rawParam));
		}
	}
}

if ($rawParam === null || $rawParam === '') {
	log_event("Missing request parameter (ap_vkm or ap_vk11mccccxccc)");
	header("Location: https://t.me/MrInterWorld");
	exit;
}

// Decode request
$rawParam = urldecode($rawParam);
$logRawLen = is_string($rawParam) ? strlen($rawParam) : 0;
log_event("After urldecode. length=" . $logRawLen);
$token = fromBase64($rawParam);
$tokenLen = is_string($token) ? strlen($token) : 0;
log_event("After base64 decode. length=" . $tokenLen);
$tokarr = fromJson($token, true);
if (!is_array($tokarr)) {
	log_event("Decoded token (first 80 chars): " . substr($token, 0, 80));
	log_event("Invalid token JSON after base64 decode");
	http_response_code(400);
	exit;
}

$encdata = $tokarr['app_Data'] ?? '';
$logLen = is_string($encdata) ? strlen($encdata) : 0;
log_event("Decoded token. app_Data length=" . $logLen);
$decdata = trim($crypter->decryptByPrivate($privatekey, fromBase64($encdata)));
$logPlainLen = is_string($decdata) ? strlen($decdata) : 0;
log_event("Decrypted app_Data length=" . $logPlainLen);
log_event("Decrypted plaintext: '$decdata'");
$data = fromJson($decdata);
log_event("Data keys: " . (is_array($data) ? implode(",", array_keys($data)) : "NOT_ARRAY"));

// Dump everything
foreach ($data as $k => $v) {
	log_event("  [$k] => " . substr((string)$v, 0, 100));
}

// Try all possible field name combinations
$uname = $data["app_uname"] ?? $data["User_hk"] ?? $data["username"] ?? $data["uname"] ?? "";
$uid = $data["app_ID"] ?? $data["Uid_hk"] ?? $data["device_id"] ?? $data["uid"] ?? $data["deviceid"] ?? $data["app_cs"] ?? "";

// If no uid yet, use app_cs as device identifier
if ($uid === "" && isset($data["app_cs"])) {
	$uid = $data["app_cs"];
}

log_event("Extracted: uname='$uname', uid='$uid'");

if ($uname === "") {
	log_event("Missing username");
	fail_ack("Missing username");
}

global $con;
$unameEsc = $con->real_escape_string($uname);
$query = $con->query("SELECT * FROM `users` WHERE `username` = '" . $unameEsc . "' LIMIT 1");

if (!$query || $query->num_rows < 1) {
	fail_ack("User not found");
}

$user = $query->fetch_assoc();

// Check game binding
if (!isset($user['game']) || strtoupper($user['game']) !== 'PLAYINJECT') {
	fail_ack("Invalid game");
}

// Check status
if (isset($user['status']) && (int)$user['status'] === 0) {
	fail_ack("Account banned");
}

// Initialize registration time if empty
if ($user['registered'] === null) {
	$con->query("UPDATE `users` SET `registered` = CURRENT_TIMESTAMP WHERE `username` = '" . $unameEsc . "'");
	$user['registered'] = date('Y-m-d H:i:s');
}

// Device limit handling
$deviceLimit = isset($user['max_devices']) ? (int)$user['max_devices'] : (isset($user['device_limit']) ? (int)$user['device_limit'] : 1);
if ($deviceLimit < 1) {
	$deviceLimit = 1;
}

$storedUIDs = isset($user['UID']) ? trim((string)$user['UID']) : '';
$uids = $storedUIDs === '' ? [] : array_filter(array_map('trim', explode(',', $storedUIDs)));

if (!in_array($uid, $uids, true)) {
	if (count($uids) >= $deviceLimit) {
		fail_ack("Device limit reached");
	}

	$uids[] = $uid;
	$newUIDs = implode(',', $uids);
	$con->query("UPDATE `users` SET `UID` = '" . $con->real_escape_string($newUIDs) . "' WHERE `username` = '" . $unameEsc . "'");
	$user['UID'] = $newUIDs;
}

// Expiration handling
$now = new DateTime("now", new DateTimeZone("Asia/HO_CHI_MINH"));

$expiresAt = $user['expired'] ?? $user['expired_date'];
if (!$expiresAt || trim($expiresAt) === '') {
	$durationHours = isset($user['duration']) ? (int)$user['duration'] : 24;
	$registeredAt = $user['registered'] ?: $now->format('Y-m-d H:i:s');
	$expiresAt = date('Y-m-d H:i:s', strtotime($registeredAt) + $durationHours * 3600);
	$con->query("UPDATE `users` SET `expired` = '" . $con->real_escape_string($expiresAt) . "' WHERE `username` = '" . $unameEsc . "'");
}

$expTs = strtotime($expiresAt);
if ($expTs !== false && time() > $expTs) {
	fail_ack("Subscription expired");
}

$end = new DateTime($expiresAt, new DateTimeZone("Asia/HO_CHI_MINH"));
$interval = $now->diff($end);

$diasText = sprintf(
	"Hết Hạn : %d Ngày %d Giờ %d Phút .",
	$interval->days,
	$interval->h,
	$interval->i
);

// Successful response populated with DB values
$ackdata = array(
	"Checkvar" => "Thanhcong",
	"MessageString" => "",
	"Function" => "Ok",
	"Title" => isset($user['menu_name']) ? $user['menu_name'] : "VNG",
	"Username" => $uname,
	"check" => "gfdsgsfsfsdsdcss",
	"checka" => "wewawedasdawweawda",
	"checkb" => "ewrewtretgwrgeww",
	"checkc" => "heo",
	"mrheoa" => "vipproa",
	"mrheob" => "vipprob",
	"mrheoc" => "vipproc",
	"SubscriptionLeft" => $expiresAt,
	"Validade" => $expiresAt,
	"Vendedor" => isset($user['registrator']) ? $user['registrator'] : "ML",
	"RegisterDate" => $user['registered'],
	"0" => $expiresAt,
	"1" => array(
		"date" => $expiresAt,
		"timezone_type" => 3,
		"timezone" => "Asia/HO_CHI_MINH"
	),
	"2" => array(
		"date" => $now->format("Y-m-d H:i:s.u"),
		"timezone_type" => 3,
		"timezone" => "Asia/HO_CHI_MINH"
	),
	"3" => array(
		"y" => $interval->y,
		"m" => $interval->m,
		"d" => $interval->d,
		"h" => $interval->h,
		"i" => $interval->i,
		"s" => $interval->s,
		"f" => 0.0,
		"weekday" => 0,
		"weekday_behavior" => 0,
		"first_last_day_of" => 0,
		"invert" => $interval->invert,
		"days" => $interval->days,
		"special_type" => 0,
		"special_amount" => 0,
		"have_weekday_relative" => 0,
		"have_special_relative" => 0
	),
	"Dias" => $diasText
);

// Send response
$response = tokenResponse($ackdata);
log_event("Response generated. length=" . strlen($response));
header("Content-Length: " . strlen($response));
echo $response;
