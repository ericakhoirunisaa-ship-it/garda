<?php
require_once 'config.php';
requireLogin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_peserta_wawancara.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

fputcsv($out, [
    'Nama',
    'Posisi Daftar',
    'Alamat Detail',
    'Kode Kecamatan (3 digit)',
    'Pendidikan',
    'Pekerjaan',
    'Jenis Kelamin (Lk/Pr)',
    'No Telp',
    'Email',
    'Merk HP',
    'Tipe HP',
    'RAM HP',
]);

// Sample rows
fputcsv($out, [
    'Contoh Nama Lengkap',
    'Mitra Pendataan',
    'Jl. Contoh No. 1 Kelurahan Contoh',
    '040',
    'Tamat D4/S1',
    'Wiraswasta',
    'Pr',
    '+62 812-3456-7890',
    'contoh@email.com',
    'Samsung',
    'Galaxy A15',
    '4',
]);
fputcsv($out, [
    'Nama Peserta Dua',
    'Mitra Pendataan',
    'Jl. Merdeka No. 5',
    '060',
    'Tamat SMA/Sederajat',
    'Pelajar / Mahasiswa',
    'Lk',
    '+62 811-2345-6789',
    'peserta2@gmail.com',
    'Redmi',
    'Note 14 4G',
    '8',
]);

fclose($out);
