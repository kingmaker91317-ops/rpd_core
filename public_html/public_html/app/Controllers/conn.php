<?php

$servername = getenv('database.default.hostname') ?: ($_ENV['database.default.hostname'] ?? "localhost");
$username = getenv('database.default.username') ?: ($_ENV['database.default.username'] ?? "mbktunp_hama");
$password = getenv('database.default.password') ?: ($_ENV['database.default.password'] ?? "mbktunp_hama");
$dbname = getenv('database.default.database') ?: ($_ENV['database.default.database'] ?? "mbktunp_hama");

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
  
?>