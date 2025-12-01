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

/*
CREATE DATABASE IF NOT EXISTS inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;
USE inventory;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    avatar VARCHAR(100) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


INSERT INTO users (username, password, email, avatar) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@apex.cz', 'default.png');

→ Heslo k tomuhle účtu je: password
*/
?>
