<?php

// AES-256-CBC with key = SHA256(timestamp|device_id|secret_bytes)

header('Content-Type: application/json');

function pkcs7_unpad($data) {
	$len = strlen($data);
	if ($len === 0) {
		throw new Exception('empty payload');
	}
	$pad = ord($data[$len - 1]);
	if ($pad < 1 || $pad > 16) {
		throw new Exception('bad padding');
	}
	for ($i = 1; $i <= $pad; $i++) {
		if (ord($data[$len - $i]) !== $pad) {
			throw new Exception('bad padding');
		}
	}
	return substr($data, 0, $len - $pad);
}

function derive_secret_bytes() {
	$raw = [
		0x8E, 0x97, 0xEE, 0x84, 0x13, 0x85, 0x5D, 0x45, 0xFD, 0x81, 0x86, 0xDD, 0x34, 0xE5, 0x1C, 0x63,
		0x91, 0xDA, 0xAE, 0xBC, 0x70, 0xDF, 0x6C, 0x3F, 0xA3, 0xAB, 0xE6, 0x9A, 0x02, 0x87, 0x51, 0x51,
		0xFC, 0x8C, 0x81, 0xD1, 0x21, 0xF9, 0x1C, 0x64, 0x8E, 0xDA, 0xBD, 0xA1, 0x77, 0xD0, 0x6D, 0x3A,
		0xA1, 0xBC, 0xE5, 0x88, 0x12, 0x86, 0x5C, 0x4C, 0xF5, 0x9D, 0x83, 0xD0, 0x3A, 0xE2, 0x13, 0x60,
	];
	$mask = [
		0xC5, 0xEF, 0xD7, 0xE9, 0x43, 0xB7, 0x2B, 0x09,
		0xC5, 0xEF, 0xD7, 0xE9, 0x43, 0xB7, 0x2B, 0x09,
	];
	$out = '';
	foreach ($raw as $i => $b) {
		$out .= chr($b ^ $mask[$i % 16]);
	}
	return $out;
}

function derive_key($timestamp, $deviceId) {
	$secret = derive_secret_bytes();
	$material = $timestamp . '|' . $deviceId . '|';
	return hash('sha256', $material . $secret, true);
}

function decrypt_encrypted_data($encryptedB64, $key) {
	$raw = base64_decode($encryptedB64, true);
	if ($raw === false || strlen($raw) < 32 || (strlen($raw) % 16) !== 0) {
		throw new Exception('bad encrypted_data');
	}
	$iv = substr($raw, 0, 16);
	$ct = substr($raw, 16);
	$pt = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
	if ($pt === false) {
		throw new Exception('decrypt failed');
	}
	return pkcs7_unpad($pt);
}

function encrypt_encrypted_data($plaintext, $key) {
	$iv = random_bytes(16);
	$ct = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
	if ($ct === false) {
		throw new Exception('encrypt failed');
	}
	return base64_encode($iv . $ct);
}

try {
	$body = file_get_contents('php://input');
	$json = json_decode($body, true);
	if (!is_array($json)) {
		throw new Exception('invalid json');
	}

	$timestamp = isset($json['timestamp']) ? intval($json['timestamp']) : time();
	$deviceId = isset($json['device_id']) ? (string)$json['device_id'] : '';
	$encReq = isset($json['encrypted_data']) ? (string)$json['encrypted_data'] : '';

	if ($deviceId === '' || $encReq === '') {
		throw new Exception('missing fields');
	}

	$key = derive_key($timestamp, $deviceId);
	$reqPlain = decrypt_encrypted_data($encReq, $key);
	$reqObj = json_decode($reqPlain, true);
	if (!is_array($reqObj)) {
		throw new Exception('bad request payload');
	}

	$projectId = isset($reqObj['project_id']) ? (string)$reqObj['project_id'] : 'PROJ_936239CF46';
	$packageName = isset($reqObj['package_name']) ? (string)$reqObj['package_name'] : 'unknown';

	$respObj = [
		'status' => 'approved',
		'project_id' => $projectId,
		'project_name' => 'CLIENT ESP',
		'package_name' => $packageName,
		'expires_at' => null,
		'magic_number' => 30402,
		'cache_duration' => 86400,
		'timestamp' => $timestamp,
	];

	$respPlain = json_encode($respObj, JSON_UNESCAPED_SLASHES);
	$encResp = encrypt_encrypted_data($respPlain, $key);

	echo json_encode(['encrypted_data' => $encResp], JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
	http_response_code(400);
	echo json_encode(['error' => $e->getMessage()]);
}

?>
