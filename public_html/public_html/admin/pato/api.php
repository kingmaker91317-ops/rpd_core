<?php
// Simple JSON API that mirrors the sample response structure.

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'invalid_json',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$username = isset($payload['username']) ? (string)$payload['username'] : '';
$sign = '';
if (isset($payload['sign64'])) {
    $sign = (string)$payload['sign64'];
} elseif (isset($payload['sign'])) {
    $sign = (string)$payload['sign'];
}

$nonce = null;
if (isset($payload['nonce'])) {
    $nonceValue = (string)$payload['nonce'];
    if ($nonceValue !== '' && strtolower($nonceValue) !== 'null') {
        $nonce = $nonceValue;
    }
}

$response = [
    'success' => true,
    'message' => 'cT_VuV{^T^_',
    'username' => $username,
    'data' => '2028-02-09 17:40:49',
    'access_token' => null,
    'c_l' => "\u0001",
    'ads' => 'aHR0cHM6Ly93d3cueW91dHViZS5jb20vQFBhdG9saW5vQlI',
    'ctt' => 'aHR0cHM6Ly93d3cueW91dHViZS5jb20vQFRoZUJyYWl6',
    'ib' => 'libelmisterioso.so',
    'p12' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEFPWSFaB5WOONtYbePxyCGNX/v5Z6L9cQFbHIN7z4wD9ba+vD20XIAzVlG5MUIYCVqd5W54BThqpyMwLpTUTlZA==',
    'sign64' => $sign,
    'nonce' => $nonce,
];

echo json_encode($response, JSON_UNESCAPED_SLASHES);
