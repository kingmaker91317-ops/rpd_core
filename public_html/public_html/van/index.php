<?php
header('Content-Type: application/json; charset=utf-8');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit <= 0) $limit = 50;

$notifications = [];

$response = [
    "data" => $notifications,
    "meta" => [
        "total" => count($notifications)
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;