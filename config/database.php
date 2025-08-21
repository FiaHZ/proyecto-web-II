<?php
$host = "localhost";
$user = "root"; 
$pass = "";     
$db   = "real_estate_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8");
?>
