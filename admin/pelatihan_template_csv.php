<?php
require_once 'config.php';
requireLogin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_soal_pelatihan.csv"');

$out = fopen('php://output', 'w');

// Header row (sebagai komentar/panduan)
fputcsv($out, ['nomor', 'pertanyaan', 'jenis', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'jawab_benar', 'poin']);

// Contoh pilihan ganda
fputcsv($out, [1, 'Apa singkatan dari SE2026?', 'pilihan_ganda', 'Survei Ekonomi 2026', 'Sensus Ekonomi 2026', 'Statistik Ekonomi 2026', 'Sistem Ekonomi 2026', 'B', 1]);

// Contoh uraian
fputcsv($out, [2, 'Jelaskan secara singkat tugas utama petugas lapangan dalam Sensus Ekonomi!', 'uraian', '', '', '', '', '', 2]);

fputcsv($out, [3, 'Dokumen apa yang wajib dibawa saat melakukan kunjungan ke responden?', 'pilihan_ganda', 'KTP saja', 'Surat tugas dan dokumen SE', 'Ijazah pendidikan', 'Kartu peserta saja', 'B', 1]);

fclose($out);
