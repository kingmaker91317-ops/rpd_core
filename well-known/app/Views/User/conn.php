<?php

$servername = "localhost";
$username = "rapidcor_main";
$password = "rapidcor_main";
$dbname = "rapidcor_main";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>