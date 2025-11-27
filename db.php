<?php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbname = 'inventory';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Připojení selhalo: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>