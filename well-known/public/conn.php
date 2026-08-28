<?php

$servername = "localhost";
$username = "u668489569_Freemod1";
$password = "Freemod1";
$dbname = "u668489569_Freemod1";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>