<?php
$host = "43.128.105.129";
$user = "root";
$pass = "Djt04k91On5HRE8ZyNVxha7JUF6m3bW2";
$db   = "db_semanis";
$port = 30444; // Tambahkan variabel port

// Update baris koneksi dengan menambahkan parameter port di akhir
$conn = mysqli_connect($host, $user, $pass, $db, $port); 

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>