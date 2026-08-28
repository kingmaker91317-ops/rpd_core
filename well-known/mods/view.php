<?php

if (!isset($_GET['file'])) {
    die("No file specified.");
}

$file = basename($_GET['file']); // Prevent directory traversal
$path = __DIR__ . '/' . $file;

if (!file_exists($path)) {
    die("File not found.");
}

header("Content-Type: text/plain");
header("Content-Disposition: inline; filename=\"$file\"");
readfile($path);