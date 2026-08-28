<?php

$servername = "localhost";
$username = "ffdetect_main";
$password = "ffdetect_main";
$dbname = "ffdetect_main";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>