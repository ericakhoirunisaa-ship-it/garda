-- ============================================
-- MIGRATION: Tabel Pencacahan SE 2026
-- Jalankan di database: db_semanis
-- ============================================

CREATE TABLE IF NOT EXISTS pencacahan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(150) NOT NULL COMMENT 'Nama petugas pencacah',
    kdkec           VARCHAR(10)  NOT NULL COMMENT 'Kode kecamatan',
    kddesa          VARCHAR(100) NULL     COMMENT 'Kode/nama desa',
    kdsls           VARCHAR(100) NULL     COMMENT 'Kode SLS',
    status          ENUM('OPEN','SUBMITTED','REJECTED') DEFAULT 'OPEN',
    catatan         TEXT         NULL,
    tanggal_update  DATE         NULL,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kdkec   (kdkec),
    INDEX idx_status  (status),
    INDEX idx_tanggal (tanggal_update)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
