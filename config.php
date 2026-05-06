<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_semanis";
$port = 3307; // Tambahkan variabel port

// Update baris koneksi dengan menambahkan parameter port di akhir
$conn = mysqli_connect($host, $user, $pass, $db, $port); 

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>