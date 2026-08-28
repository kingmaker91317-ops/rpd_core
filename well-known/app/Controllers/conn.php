<?php

$servername = "localhost";
$username = "u160951123_Titooxxx1";
$password = "Titooxxx1";
$dbname = "u160951123_Titooxxx1";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>