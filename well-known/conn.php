<?php

$servername = getenv('database.default.hostname') ?: ($_ENV['database.default.hostname'] ?? "localhost");
$username = getenv('database.default.username') ?: ($_ENV['database.default.username'] ?? "xkynpbah_titoo");
$password = getenv('database.default.password') ?: ($_ENV['database.default.password'] ?? "xkynpbah_titoo");
$dbname = getenv('database.default.database') ?: ($_ENV['database.default.database'] ?? "xkynpbah_titoo");

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>