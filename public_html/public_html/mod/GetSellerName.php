<?php
header("Content-Type: text/plain; charset=utf-8");

$uname = $_GET["uname"] ?? "";
if ($uname !== "") {
    file_put_contents(__DIR__ . "/sellername.log", date("c") . " " . $uname . PHP_EOL, FILE_APPEND);
}

$body = "\n\n\n\n\n\nMR LIGHT XD";
header("Content-Length: " . strlen($body));
echo $body;
