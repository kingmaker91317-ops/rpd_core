<?php
$servername = "localhost";
$username = "mbktunp_hama";
$password = "mbktunp_hama";
$database = "mbktunp_hama";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
