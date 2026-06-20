-- ============================================
-- SCHEMA: Sistem Wawancara Seleksi SE2026
-- Jalankan di database: db_semanis
-- ============================================

CREATE TABLE IF NOT EXISTS peserta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150),
    posisi VARCHAR(100),
    status_seleksi VARCHAR(50),
    posisi_daftar VARCHAR(100),
    alamat_detail TEXT,
    alamat_prov VARCHAR(10),
    alamat_kab VARCHAR(10),
    alamat_kec VARCHAR(10),
    alamat_desa VARCHAR(10),
    ttl VARCHAR(150),
    jenis_kelamin VARCHAR(10),
    pendidikan VARCHAR(100),
    pekerjaan VARCHAR(100),
    deskripsi_pekerjaan TEXT,
    no_telp VARCHAR(30),
    sobat_id VARCHAR(50),
    email VARCHAR(150),
    status_nik VARCHAR(50),
    verifikasi_pada VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_seleksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('petugas', 'superadmin') DEFAULT 'petugas',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wawancara (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    admin_id INT NOT NULL,
    motivasi TEXT,
    pengalaman TEXT,
    pengetahuan_se TEXT,
    sanggup_pelatihan ENUM('Ya', 'Tidak'),
    ketersediaan_waktu ENUM('Ya', 'Tidak'),
    punya_smartphone ENUM('Ya', 'Tidak'),
    kemampuan_baca_tulis ENUM('Lancar', 'Cukup', 'Kurang'),
    bahasa_daerah ENUM('Ya', 'Tidak'),
    kondisi_kesehatan ENUM('Baik', 'Cukup', 'Kurang'),
    catatan TEXT,
    nilai INT DEFAULT 0,
    rekomendasi ENUM('Lulus', 'Tidak Lulus'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (admin_id) REFERENCES admin_seleksi(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
