<?php
session_start();
require_once __DIR__ . '/../config.php';

function requireLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireSuperAdmin() {
    if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
        header('Location: index.php');
        exit;
    }
}

function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
}

// Auto-tambah kolom foto ke tabel wawancara jika belum ada
global $conn;
$chkFoto = $conn->query("SHOW COLUMNS FROM wawancara LIKE 'foto'");
if ($chkFoto && $chkFoto->num_rows === 0) {
    $conn->query("ALTER TABLE wawancara ADD COLUMN foto VARCHAR(255) NULL AFTER catatan");
}

// Auto-tambah kolom nilai_kompetensi ke tabel peserta jika belum ada
$chkNK = $conn->query("SHOW COLUMNS FROM peserta LIKE 'nilai_kompetensi'");
if ($chkNK && $chkNK->num_rows === 0) {
    $conn->query("ALTER TABLE peserta ADD COLUMN nilai_kompetensi DECIMAL(6,2) NULL");
}

// Auto-create tabel penilaian_akhir
$conn->query("CREATE TABLE IF NOT EXISTS penilaian_akhir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    nilai_kompetensi DECIMAL(6,2) NULL,
    status_akhir VARCHAR(20) NULL COMMENT 'Lulus / Tidak Lulus',
    keterangan TEXT NULL,
    updated_by INT NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_peserta (peserta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ─── Auto-create tabel Pelatihan Petugas ─────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS pelatihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    jenis ENUM('pretest','posttest','ujian') DEFAULT 'ujian',
    deskripsi TEXT,
    durasi_menit INT DEFAULT 60,
    status ENUM('draft','aktif','selesai') DEFAULT 'draft',
    dibuat_oleh INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelatihan_soal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelatihan_id INT NOT NULL,
    nomor_urut INT DEFAULT 1,
    pertanyaan TEXT NOT NULL,
    jenis ENUM('pilihan_ganda','uraian') DEFAULT 'pilihan_ganda',
    poin INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pelatihan (pelatihan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelatihan_opsi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soal_id INT NOT NULL,
    huruf CHAR(1) NOT NULL,
    teks TEXT NOT NULL,
    is_benar TINYINT(1) DEFAULT 0,
    INDEX idx_soal (soal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelatihan_hasil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelatihan_id INT NOT NULL,
    peserta_id INT NOT NULL,
    waktu_mulai DATETIME,
    waktu_selesai DATETIME NULL,
    nilai DECIMAL(6,2) NULL,
    status ENUM('sedang','selesai') DEFAULT 'sedang',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_peserta_pelatihan (pelatihan_id, peserta_id),
    INDEX idx_pelatihan_hasil (pelatihan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pelatihan_jawaban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hasil_id INT NOT NULL,
    soal_id INT NOT NULL,
    jawaban_teks TEXT NULL,
    opsi_id INT NULL,
    is_benar TINYINT(1) NULL,
    poin_dapat DECIMAL(6,2) DEFAULT 0,
    INDEX idx_hasil (hasil_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
