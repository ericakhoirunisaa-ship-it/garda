<?php
require_once 'config.php';
requireSuperAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$pesertaId   = (int)($_POST['peserta_id'] ?? 0);
$statusAkhir = trim($_POST['status_akhir'] ?? '');
$keterangan  = trim($_POST['keterangan'] ?? '');
$nilaiKInput = trim($_POST['nilai_kompetensi'] ?? '');

if (!$pesertaId) {
    echo json_encode(['ok' => false, 'msg' => 'peserta_id tidak valid']);
    exit;
}

if ($statusAkhir !== '' && !in_array($statusAkhir, ['Lulus', 'Tidak Lulus'])) {
    echo json_encode(['ok' => false, 'msg' => 'Status tidak valid']);
    exit;
}

$nilaiKompetensi = null;
if ($nilaiKInput !== '') {
    $nilaiKompetensi = (float)$nilaiKInput;
    if ($nilaiKompetensi < 0 || $nilaiKompetensi > 100) {
        echo json_encode(['ok' => false, 'msg' => 'Nilai kompetensi harus antara 0–100']);
        exit;
    }
}

$statusAkhir = $statusAkhir !== '' ? $statusAkhir : null;
$keterangan  = $keterangan  !== '' ? $keterangan  : null;
$adminId     = (int)($_SESSION['admin_id'] ?? 0);

$stmt = $conn->prepare("
    INSERT INTO penilaian_akhir (peserta_id, nilai_kompetensi, status_akhir, keterangan, updated_by, updated_at)
    VALUES (?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        nilai_kompetensi = VALUES(nilai_kompetensi),
        status_akhir     = VALUES(status_akhir),
        keterangan       = VALUES(keterangan),
        updated_by       = VALUES(updated_by),
        updated_at       = NOW()
");
$stmt->bind_param('idssi', $pesertaId, $nilaiKompetensi, $statusAkhir, $keterangan, $adminId);

if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => $conn->error]);
}
