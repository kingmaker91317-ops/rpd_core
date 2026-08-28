<?php

$servername = "localhost";
$username = "mbktunp_hama";
$password = "mbktunp_hama";
$dbname = "mbktunp_hama";

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
  
?>