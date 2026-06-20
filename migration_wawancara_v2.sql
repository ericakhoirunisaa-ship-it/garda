-- ============================================
-- MIGRATION v2: Form Wawancara Mitra BPS
-- Jalankan di database: db_semanis
-- ============================================

ALTER TABLE wawancara
  ADD COLUMN IF NOT EXISTS kepemilikan_motor      ENUM('Ya','Tidak') NULL AFTER foto,
  ADD COLUMN IF NOT EXISTS kemampuan_berkendara   ENUM('Ya','Tidak') NULL AFTER kepemilikan_motor,
  ADD COLUMN IF NOT EXISTS pernah_capi            ENUM('Ya','Tidak') NULL AFTER kemampuan_berkendara,
  ADD COLUMN IF NOT EXISTS merk_hp                VARCHAR(100)       NULL AFTER pernah_capi,
  ADD COLUMN IF NOT EXISTS tipe_hp                VARCHAR(100)       NULL AFTER merk_hp,
  ADD COLUMN IF NOT EXISTS ram_hp                 VARCHAR(50)        NULL AFTER tipe_hp,
  ADD COLUMN IF NOT EXISTS jawab_perkenalan       TEXT               NULL AFTER ram_hp,
  ADD COLUMN IF NOT EXISTS jawab_pengetahuan_bps  TEXT               NULL AFTER jawab_perkenalan,
  ADD COLUMN IF NOT EXISTS jawab_alasan_daftar    TEXT               NULL AFTER jawab_pengetahuan_bps,
  ADD COLUMN IF NOT EXISTS pernah_pendataan       ENUM('Ya','Tidak') NULL AFTER jawab_alasan_daftar,
  ADD COLUMN IF NOT EXISTS jawab_evaluasi         TEXT               NULL AFTER pernah_pendataan,
  ADD COLUMN IF NOT EXISTS jawab_penolak          TEXT               NULL AFTER jawab_evaluasi,
  ADD COLUMN IF NOT EXISTS jawab_konflik          TEXT               NULL AFTER jawab_penolak,
  ADD COLUMN IF NOT EXISTS jawab_manajemen_waktu  TEXT               NULL AFTER jawab_konflik,
  ADD COLUMN IF NOT EXISTS status_asn             ENUM('Ya','Tidak') NULL AFTER jawab_manajemen_waktu,
  ADD COLUMN IF NOT EXISTS status_kontrak         ENUM('Ya','Tidak') NULL AFTER status_asn,
  ADD COLUMN IF NOT EXISTS status_hamil           ENUM('Ya','Tidak') NULL AFTER status_kontrak,
  ADD COLUMN IF NOT EXISTS punya_balita           ENUM('Ya','Tidak') NULL AFTER status_hamil,
  ADD COLUMN IF NOT EXISTS keterangan_balita      TEXT               NULL AFTER punya_balita,
  ADD COLUMN IF NOT EXISTS pasangan_daftar        ENUM('Ya','Tidak') NULL AFTER keterangan_balita,
  ADD COLUMN IF NOT EXISTS penyakit_kronis        ENUM('Ya','Tidak') NULL AFTER pasangan_daftar,
  ADD COLUMN IF NOT EXISTS keterangan_penyakit    TEXT               NULL AFTER penyakit_kronis,
  ADD COLUMN IF NOT EXISTS bersedia_kec_lain      ENUM('Ya','Tidak') NULL AFTER keterangan_penyakit,
  ADD COLUMN IF NOT EXISTS bersedia_tanggung_jawab ENUM('Ya','Tidak') NULL AFTER bersedia_kec_lain,
  ADD COLUMN IF NOT EXISTS bersedia_validasi      ENUM('Ya','Tidak') NULL AFTER bersedia_tanggung_jawab,
  ADD COLUMN IF NOT EXISTS bersedia_bayar_clean   ENUM('Ya','Tidak') NULL AFTER bersedia_validasi,
  ADD COLUMN IF NOT EXISTS nilai_komunikasi       INT DEFAULT 0      AFTER bersedia_bayar_clean,
  ADD COLUMN IF NOT EXISTS nilai_penampilan       INT DEFAULT 0      AFTER nilai_komunikasi,
  ADD COLUMN IF NOT EXISTS nilai_analisa          INT DEFAULT 0      AFTER nilai_penampilan,
  ADD COLUMN IF NOT EXISTS predikat               ENUM('A','B','C','D','E') NULL AFTER catatan;
