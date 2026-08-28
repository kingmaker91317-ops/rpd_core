<?php
declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    return;
}

$phar = __DIR__ . '/phpseclib.phar';
if (is_file($phar)) {
    require_once $phar;
    return;
}

http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => false,
    'error' => 'phpseclib not installed. Run: composer require phpseclib/phpseclib',
], JSON_UNESCAPED_SLASHES);
exit;
