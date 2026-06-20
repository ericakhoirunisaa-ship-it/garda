<?php
require_once 'config.php';

echo "<h3>Cek Koneksi & Database</h3>";

// Cek tabel peserta
$r = $conn->query("SHOW TABLES LIKE 'peserta'");
echo "Tabel <b>peserta</b>: " . ($r->num_rows > 0 ? "✅ Ada" : "❌ Belum dibuat") . "<br>";

// Cek tabel admin_seleksi
$r = $conn->query("SHOW TABLES LIKE 'admin_seleksi'");
echo "Tabel <b>admin_seleksi</b>: " . ($r->num_rows > 0 ? "✅ Ada" : "❌ Belum dibuat") . "<br>";

// Cek tabel wawancara
$r = $conn->query("SHOW TABLES LIKE 'wawancara'");
echo "Tabel <b>wawancara</b>: " . ($r->num_rows > 0 ? "✅ Ada" : "❌ Belum dibuat") . "<br><br>";

// Cek isi akun
$r = $conn->query("SELECT id, username, nama, role FROM admin_seleksi");
if ($r) {
    echo "Jumlah akun: <b>" . $r->num_rows . "</b><br><br>";
    if ($r->num_rows > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th></tr>";
        while ($row = $r->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['nama']}</td><td>{$row['role']}</td></tr>";
        }
        echo "</table>";
    }
} else {
    echo "<span style='color:red'>Tabel admin_seleksi belum ada.</span><br>";
}

// Cek extension zip
echo "<br>Extension zip: " . (class_exists('ZipArchive') ? "✅ Aktif" : "❌ Tidak aktif") . "<br>";

// Link setup
echo "<br><a href='setup_akun.php?token=SE2026_SETUP'>▶ Jalankan Setup Akun</a><br>";
echo "<a href='import_peserta.php?token=SE2026_IMPORT'>▶ Jalankan Import Peserta</a><br>";
?>
