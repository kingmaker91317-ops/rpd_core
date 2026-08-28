<?php
// Simple login response with base64 load_data from a local file.
header('Content-Type: application/json; charset=utf-8');

$input = file_get_contents('php://input');
$request = json_decode($input, true);

if (!is_array($request)) {
	http_response_code(400);
	echo json_encode([
		'status' => false,
		'message' => 'Invalid JSON'
	]);
	exit;
}

$loadFile = __DIR__ . DIRECTORY_SEPARATOR . 'hwangshu';
$loadData = '';
if (is_file($loadFile)) {
	$loadData = base64_encode(file_get_contents($loadFile));
}

$expireDate = '2026-02-12 00:24:15';
$expireTimestamp = strtotime($expireDate);
if ($expireTimestamp === false) {
	$expireTimestamp = time();
}

$response = [
	'status' => true,
	'expire_date' => $expireDate,
	'message' => 'Login Success',
	'rng' => $expireTimestamp,
	'load' => [
		'load_data' => $loadData
	],
	'auth' => [
		'message' => "?\u0015\"?\u001a9+\t\u0012%8\u001d>,F)?3\u001130\u0015\t9#R\u000f,\u000e\u0015\"?\u001a9+\tZ\u00049\u00197\"\u0007\u001b+1\u00137\"\u0007\u001b",
		'token_access' => 'Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9EVm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E'
	]
];

echo json_encode($response, JSON_UNESCAPED_SLASHES);
