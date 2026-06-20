<?php
date_default_timezone_set('Asia/Makassar'); // WITA (UTC+8)

$host = "43.128.105.129";
$user = "root";
$pass = "Djt04k91On5HRE8ZyNVxha7JUF6m3bW2";
$db   = "db_semanis";
$port = 30444; // Tambahkan variabel port

// Update baris koneksi dengan menambahkan parameter port di akhir
try {
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }
} catch (mysqli_sql_exception $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ─── Auto-create tabel Ujian Publik ──────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS ujian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    dibuat_oleh INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS ujian_soal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_urut INT DEFAULT 1,
    pertanyaan TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nomor (nomor_urut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS ujian_opsi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soal_id INT NOT NULL,
    huruf CHAR(1) NOT NULL,
    teks TEXT NOT NULL,
    is_benar TINYINT(1) DEFAULT 0,
    INDEX idx_soal (soal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS ujian_pengerjaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    nik VARCHAR(20) NULL,
    kecamatan VARCHAR(10) NOT NULL,
    nilai DECIMAL(6,2) DEFAULT 0,
    total_soal INT DEFAULT 0,
    total_benar INT DEFAULT 0,
    waktu DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-tambah kolom nik jika tabel sudah ada sebelumnya
$chkNik = $conn->query("SHOW COLUMNS FROM ujian_pengerjaan LIKE 'nik'");
if ($chkNik && $chkNik->num_rows === 0) {
    $conn->query("ALTER TABLE ujian_pengerjaan ADD COLUMN nik VARCHAR(20) NULL AFTER nama");
}

// Auto-tambah kolom durasi_menit ke ujian
$chkDurasi = $conn->query("SHOW COLUMNS FROM ujian LIKE 'durasi_menit'");
if ($chkDurasi && $chkDurasi->num_rows === 0) {
    $conn->query("ALTER TABLE ujian ADD COLUMN durasi_menit INT DEFAULT 0 AFTER deskripsi");
}

// Auto-tambah kolom petunjuk ke ujian
$chkPetunjuk = $conn->query("SHOW COLUMNS FROM ujian LIKE 'petunjuk'");
if ($chkPetunjuk && $chkPetunjuk->num_rows === 0) {
    $conn->query("ALTER TABLE ujian ADD COLUMN petunjuk TEXT NULL AFTER durasi_menit");
}

// Auto-tambah kolom ujian_id ke ujian_soal
$chkUjianSoal = $conn->query("SHOW COLUMNS FROM ujian_soal LIKE 'ujian_id'");
if ($chkUjianSoal && $chkUjianSoal->num_rows === 0) {
    $conn->query("ALTER TABLE ujian_soal ADD COLUMN ujian_id INT NOT NULL DEFAULT 0 AFTER id, ADD INDEX idx_ujian_soal (ujian_id)");
}

// Auto-tambah kolom ujian_id ke ujian_pengerjaan
$chkUjianPengerjaan = $conn->query("SHOW COLUMNS FROM ujian_pengerjaan LIKE 'ujian_id'");
if ($chkUjianPengerjaan && $chkUjianPengerjaan->num_rows === 0) {
    $conn->query("ALTER TABLE ujian_pengerjaan ADD COLUMN ujian_id INT NOT NULL DEFAULT 0 AFTER id, ADD INDEX idx_ujian_pengerjaan (ujian_id)");
}

$conn->query("CREATE TABLE IF NOT EXISTS ujian_jawaban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengerjaan_id INT NOT NULL,
    soal_id INT NOT NULL,
    opsi_id INT NULL,
    is_benar TINYINT(1) DEFAULT 0,
    INDEX idx_pengerjaan (pengerjaan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS ujian_mitra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(200) NOT NULL,
    kecamatan VARCHAR(10) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-perlebar kolom peran agar cukup untuk 'PPL Sensus'/'PML Sensus'
$chkPeranP = $conn->query("SHOW COLUMNS FROM ujian_pengerjaan LIKE 'peran'");
if ($chkPeranP && $chkPeranP->num_rows > 0) {
    $conn->query("ALTER TABLE ujian_pengerjaan MODIFY COLUMN peran VARCHAR(20) DEFAULT ''");
}
$chkPeranM = $conn->query("SHOW COLUMNS FROM ujian_mitra LIKE 'peran'");
if ($chkPeranM && $chkPeranM->num_rows > 0) {
    $conn->query("ALTER TABLE ujian_mitra MODIFY COLUMN peran VARCHAR(20) DEFAULT ''");
}
?>