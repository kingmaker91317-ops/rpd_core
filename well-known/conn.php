<?php

$servername = "localhost";
$username = "xkynpbah_titoo";
$password = "xkynpbah_titoo";
$dbname = "xkynpbah_titoo";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>