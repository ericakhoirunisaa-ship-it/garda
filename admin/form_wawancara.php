<?php
require_once 'config.php';
requireLogin();

// Migrasi kolom baru wawancara jika belum ada
(function() use ($conn) {
    $existing = [];
    $r = $conn->query("SHOW COLUMNS FROM wawancara");
    while ($c = $r->fetch_assoc()) $existing[] = $c['Field'];

    $adds = [
        'kepemilikan_motor'      => "VARCHAR(10) NULL",
        'kemampuan_berkendara'   => "VARCHAR(10) NULL",
        'pernah_capi'            => "VARCHAR(10) NULL",
        'merk_hp'                => "VARCHAR(100) NULL",
        'tipe_hp'                => "VARCHAR(100) NULL",
        'ram_hp'                 => "VARCHAR(50) NULL",
        'jawab_perkenalan'       => "TEXT NULL",
        'jawab_pengetahuan_bps'  => "TEXT NULL",
        'jawab_alasan_daftar'    => "TEXT NULL",
        'pernah_pendataan'       => "VARCHAR(10) NULL",
        'jawab_evaluasi'         => "TEXT NULL",
        'jawab_penolak'          => "TEXT NULL",
        'jawab_konflik'          => "TEXT NULL",
        'jawab_manajemen_waktu'  => "TEXT NULL",
        'status_asn'             => "VARCHAR(10) NULL",
        'status_kontrak'         => "VARCHAR(10) NULL",
        'status_hamil'           => "VARCHAR(10) NULL",
        'punya_balita'           => "VARCHAR(10) NULL",
        'keterangan_balita'      => "TEXT NULL",
        'pasangan_daftar'        => "VARCHAR(10) NULL",
        'penyakit_kronis'        => "VARCHAR(10) NULL",
        'keterangan_penyakit'    => "TEXT NULL",
        'bersedia_kec_lain'      => "VARCHAR(10) NULL",
        'bersedia_tanggung_jawab'=> "VARCHAR(10) NULL",
        'bersedia_validasi'      => "VARCHAR(10) NULL",
        'bersedia_bayar_clean'   => "VARCHAR(10) NULL",
        'status_perkawinan'      => "VARCHAR(30) NULL",
        'tanggapan_praktik'      => "TEXT NULL",
        'storage_hp'             => "VARCHAR(50) NULL",
        'apakah_sesuai_merk'     => "VARCHAR(10) NULL",
        'apakah_sesuai_tipe'     => "VARCHAR(10) NULL",
        'apakah_sesuai_ram'      => "VARCHAR(10) NULL",
        'nilai_komunikasi'       => "INT DEFAULT 0",
        'nilai_penampilan'       => "INT DEFAULT 0",
        'nilai_analisa'          => "INT DEFAULT 0",
        'predikat'               => "VARCHAR(5) NULL",
    ];
    foreach ($adds as $col => $def) {
        if (!in_array($col, $existing)) {
            $conn->query("ALTER TABLE wawancara ADD COLUMN `$col` $def");
        }
    }
})();

// Migrasi kolom HP di tabel peserta
(function() use ($conn) {
    $ex = [];
    $r  = $conn->query("SHOW COLUMNS FROM peserta");
    while ($c = $r->fetch_assoc()) $ex[] = $c['Field'];
    foreach (['merk_hp' => "VARCHAR(100) NULL", 'tipe_hp' => "VARCHAR(100) NULL", 'ram_hp' => "VARCHAR(50) NULL"] as $col => $def) {
        if (!in_array($col, $ex)) $conn->query("ALTER TABLE peserta ADD COLUMN `$col` $def");
    }
})();

$pesertaId = (int)($_GET['id'] ?? 0);
if (!$pesertaId) { header('Location: index.php'); exit; }

$peserta = $conn->query("SELECT * FROM peserta WHERE id = $pesertaId AND status_seleksi = 'Diterima'")->fetch_assoc();
if (!$peserta) { header('Location: index.php'); exit; }

$wawancara = $conn->query("SELECT * FROM wawancara WHERE peserta_id = $pesertaId ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
$w = $wawancara ?? [];

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e  = fn($v) => $conn->real_escape_string(trim((string)($v ?? '')));
    $ei = fn($v) => min(100, max(0, (int)($v ?? 0)));

    $km  = $e($_POST['kepemilikan_motor']     ?? '');
    $kb  = $e($_POST['kemampuan_berkendara']  ?? '');
    $pc  = $e($_POST['pernah_capi']           ?? '');
    $mhp = $e($_POST['merk_hp']               ?? '');
    $thp = $e($_POST['tipe_hp']               ?? '');
    $rhp = $e($_POST['ram_hp']                ?? '');
    $shp = $e($_POST['storage_hp']            ?? '');
    $asm = $e($_POST['apakah_sesuai_merk']    ?? '');
    $ast = $e($_POST['apakah_sesuai_tipe']    ?? '');
    $asr = $e($_POST['apakah_sesuai_ram']     ?? '');

    $jp  = $e($_POST['jawab_perkenalan']       ?? '');
    $jb  = $e($_POST['jawab_pengetahuan_bps']  ?? '');
    $ja  = $e($_POST['jawab_alasan_daftar']    ?? '');
    $pp  = $e($_POST['pernah_pendataan']       ?? '');
    $je  = $e($_POST['jawab_evaluasi']         ?? '');
    $jpo = $e($_POST['jawab_penolak']          ?? '');
    $jk  = $e($_POST['jawab_konflik']          ?? '');
    $jmw = $e($_POST['jawab_manajemen_waktu']  ?? '');
    $sa  = $e($_POST['status_asn']             ?? '');
    $sk  = $e($_POST['status_kontrak']         ?? '');
    $sh  = $e($_POST['status_hamil']           ?? '');
    $pb  = $e($_POST['punya_balita']           ?? '');
    $ktb = $e($_POST['keterangan_balita']      ?? '');
    $pd  = $e($_POST['pasangan_daftar']        ?? '');
    $pyk = $e($_POST['penyakit_kronis']        ?? '');
    $ktp = $e($_POST['keterangan_penyakit']    ?? '');
    $bkl = $e($_POST['bersedia_kec_lain']      ?? '');
    $btj = $e($_POST['bersedia_tanggung_jawab'] ?? '');
    $bv  = $e($_POST['bersedia_validasi']      ?? '');
    $bbc = $e($_POST['bersedia_bayar_clean']   ?? '');
    $spk = $e($_POST['status_perkawinan']      ?? '');
    $tpr = $e($_POST['tanggapan_praktik']      ?? '');

    $nkom  = $ei($_POST['nilai_komunikasi'] ?? 0);
    $npen  = $ei($_POST['nilai_penampilan'] ?? 0);
    $nana  = $ei($_POST['nilai_analisa']    ?? 0);
    $nilai = (int)round(($nkom + $npen + $nana) / 3);

    $catatan  = $e($_POST['catatan']  ?? '');
    $predikat = $e($_POST['predikat'] ?? '');

    // Konversi string kosong → NULL untuk kolom ENUM/radio (mencegah "Data truncated")
    $sq = fn($v) => $v !== '' ? "'$v'" : 'NULL';

    $fotoName = $w['foto'] ?? null;
    if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/foto/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['foto']['size'] <= 3145728) {
            $newName = 'peserta_' . $pesertaId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $newName)) {
                if ($fotoName && file_exists($uploadDir . $fotoName)) unlink($uploadDir . $fotoName);
                $fotoName = $newName;
            }
        }
    }
    $foto    = $conn->real_escape_string($fotoName ?? '');
    $adminId = (int)$_SESSION['admin_id'];

    if ($w) {
        $wid = (int)$w['id'];
        $sql = "UPDATE wawancara SET
            foto='$foto',
            kepemilikan_motor={$sq($km)}, kemampuan_berkendara={$sq($kb)}, pernah_capi={$sq($pc)},
            merk_hp='$mhp', tipe_hp='$thp', ram_hp='$rhp',
            storage_hp='$shp',
            apakah_sesuai_merk={$sq($asm)}, apakah_sesuai_tipe={$sq($ast)}, apakah_sesuai_ram={$sq($asr)},
            jawab_perkenalan='$jp', jawab_pengetahuan_bps='$jb', jawab_alasan_daftar='$ja',
            pernah_pendataan={$sq($pp)}, jawab_evaluasi='$je', jawab_penolak='$jpo',
            jawab_konflik='$jk', jawab_manajemen_waktu='$jmw',
            status_asn={$sq($sa)}, status_kontrak={$sq($sk)}, status_hamil={$sq($sh)},
            punya_balita={$sq($pb)}, keterangan_balita='$ktb', pasangan_daftar={$sq($pd)},
            penyakit_kronis={$sq($pyk)}, keterangan_penyakit='$ktp',
            bersedia_kec_lain={$sq($bkl)}, bersedia_tanggung_jawab={$sq($btj)},
            bersedia_validasi={$sq($bv)}, bersedia_bayar_clean={$sq($bbc)},
            status_perkawinan={$sq($spk)}, tanggapan_praktik='$tpr',
            nilai_komunikasi=$nkom, nilai_penampilan=$npen, nilai_analisa=$nana, nilai=$nilai,
            catatan='$catatan', predikat={$sq($predikat)},
            admin_id=$adminId, updated_at=NOW()
            WHERE id=$wid";
    } else {
        $sql = "INSERT INTO wawancara (
            peserta_id, admin_id, foto,
            kepemilikan_motor, kemampuan_berkendara, pernah_capi,
            merk_hp, tipe_hp, ram_hp,
            storage_hp, apakah_sesuai_merk, apakah_sesuai_tipe, apakah_sesuai_ram,
            jawab_perkenalan, jawab_pengetahuan_bps, jawab_alasan_daftar, pernah_pendataan,
            jawab_evaluasi, jawab_penolak, jawab_konflik, jawab_manajemen_waktu,
            status_asn, status_kontrak, status_hamil, punya_balita, keterangan_balita,
            pasangan_daftar, penyakit_kronis, keterangan_penyakit,
            bersedia_kec_lain, bersedia_tanggung_jawab, bersedia_validasi, bersedia_bayar_clean,
            status_perkawinan, tanggapan_praktik,
            nilai_komunikasi, nilai_penampilan, nilai_analisa, nilai, catatan, predikat
        ) VALUES (
            $pesertaId, $adminId, '$foto',
            {$sq($km)},{$sq($kb)},{$sq($pc)},
            '$mhp','$thp','$rhp',
            '$shp',{$sq($asm)},{$sq($ast)},{$sq($asr)},
            '$jp','$jb','$ja',{$sq($pp)},'$je','$jpo','$jk','$jmw',
            {$sq($sa)},{$sq($sk)},{$sq($sh)},{$sq($pb)},'$ktb',{$sq($pd)},{$sq($pyk)},'$ktp',
            {$sq($bkl)},{$sq($btj)},{$sq($bv)},{$sq($bbc)},
            {$sq($spk)},'$tpr',
            $nkom,$npen,$nana,$nilai,'$catatan',{$sq($predikat)}
        )";
    }

    if ($conn->query($sql)) {
        $success   = 'Data wawancara berhasil disimpan.';
        $wawancara = $conn->query("SELECT * FROM wawancara WHERE peserta_id = $pesertaId ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
        $w = $wawancara ?? [];
    } else {
        $error = 'Gagal menyimpan: ' . $conn->error;
    }
}

function sel($val, $check) {
    return $val == $check ? 'checked' : '';
}

function scoreBadge($v) {
    $v = (int)$v;
    if ($v >= 91) return '<span class="badge bg-success ms-2">Baik Sekali (91–100)</span>';
    if ($v >= 76) return '<span class="badge bg-primary ms-2">Baik (76–90)</span>';
    if ($v >= 51) return '<span class="badge bg-warning text-dark ms-2">Sedang (51–75)</span>';
    if ($v > 0)   return '<span class="badge bg-danger ms-2">Kurang (0–50)</span>';
    return '';
}

function cekSesuaiHP($ram) {
    $r = trim((string)($ram ?? ''));
    if ($r === '' || $r === '-' || $r === '0') return null;
    $num = (float)preg_replace('/[^0-9.]/', '', $r);
    if ($num <= 0) return null;
    return ($num >= 3 && $num <= 24) ? 'Iya' : 'Tidak';
}

function sesuaiBadge($ram) {
    $s = cekSesuaiHP($ram);
    if ($s === 'Iya')   return '<span class="badge" style="background:#16a34a;font-size:.82rem"><i class="bi bi-check-circle-fill me-1"></i>Iya</span>';
    if ($s === 'Tidak') return '<span class="badge bg-danger" style="font-size:.82rem"><i class="bi bi-x-circle-fill me-1"></i>Tidak</span>';
    return '<span class="badge bg-secondary" style="font-size:.82rem">Data kosong</span>';
}

function findFotoCalon($nama) {
    $nama = trim((string)$nama);
    $dir  = __DIR__ . '/../0. FOTO CALON MITRA STATISTIK TAMBAHAN/';
    // Special case: foto disimpan dengan nama berbeda
    $special = ['MIFTAHUL JANNAH' => 'MIFTAHUL JANNAH (2).png'];
    if (isset($special[$nama]) && file_exists($dir . $special[$nama])) return $special[$nama];
    // Normalkan spasi ganda
    $norm = preg_replace('/\s+/', ' ', $nama);
    foreach ([$nama, $norm] as $n) {
        if (file_exists($dir . $n . '.png')) return $n . '.png';
    }
    // Fallback: case-insensitive scan
    $normLow = strtolower($norm);
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if (!str_ends_with(strtolower($f), '.png')) continue;
            if (strtolower(pathinfo($f, PATHINFO_FILENAME)) === $normLow) return $f;
        }
    }
    return null;
}

function fotoCalon_url($fname) {
    return '../' . rawurlencode('0. FOTO CALON MITRA STATISTIK TAMBAHAN') . '/' . rawurlencode($fname);
}

$isPerempuan = in_array(strtolower($peserta['jenis_kelamin'] ?? ''), ['pr', 'perempuan', 'p']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Wawancara — <?= htmlspecialchars($peserta['nama']) ?></title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .blok-title {
            background: #f79039; color: #fff;
            padding: 8px 16px; border-radius: 8px;
            font-size: .85rem; font-weight: 700;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
            letter-spacing: .3px;
        }
        .profile-card { border-left: 4px solid #f79039; }
        .form-control:focus, .form-check-input:focus { border-color: #f79039; box-shadow: 0 0 0 3px rgba(247,144,57,.15); }
        .form-check-input:checked { background-color: #f79039; border-color: #f79039; }
        .q-label { font-weight: 600; margin-bottom: 6px; }
        .q-number { display: inline-block; width: 26px; height: 26px; border-radius: 50%;
                    background: #f79039; color: #fff; font-size: .78rem; font-weight: 700;
                    text-align: center; line-height: 26px; margin-right: 6px; flex-shrink: 0; }
        .q-header { display: flex; align-items: flex-start; gap: 4px; margin-bottom: 8px; }
        .radio-row { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 6px; }
        .score-wrap { background: #f8f9fa; border-radius: 10px; padding: 16px; }
        .score-num { font-size: 2.2rem; font-weight: 800; color: #f79039; line-height: 1; }
        .predikat-card {
            border: 2px solid #dee2e6; border-radius: 10px;
            padding: 12px 16px; cursor: pointer; transition: all .15s;
            display: flex; align-items: center; gap: 12px;
        }
        .predikat-card:hover { border-color: #f79039; background: rgba(247,144,57,.05); }
        .predikat-card.selected { border-color: #f79039; background: rgba(247,144,57,.1); }
        .predikat-badge { width: 36px; height: 36px; border-radius: 50%;
                          display: flex; align-items: center; justify-content: center;
                          font-size: 1rem; font-weight: 800; flex-shrink: 0; }
        .foto-img { width: 140px; height: 175px; object-fit: cover;
                    border-radius: 12px; border: 3px solid #f79039;
                    box-shadow: 0 4px 14px rgba(247,144,57,.25); display: block; }
        .foto-placeholder { width: 140px; height: 175px; background: #f8f9fa;
                            border-radius: 12px; border: 2px dashed #dee2e6;
                            display: flex; flex-direction: column; align-items: center;
                            justify-content: center; color: #adb5bd; }
        .foto-placeholder i { font-size: 3rem; }
        .foto-change-btn { display: block; margin-top: 8px; background: #f79039; color: #fff;
                           border: none; border-radius: 20px; padding: 4px 14px;
                           font-size: .72rem; font-weight: 600; cursor: pointer; width: 140px;
                           text-align: center; }
        .section-divider { border-top: 1px solid #f0f0f0; margin: 20px 0; }
        .info-display { background: #f8f9fa; border-radius: 8px; padding: 10px 14px; font-weight: 600; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title">
                    <i class="bi bi-mic-fill me-2" style="color:#f79039"></i>Form Wawancara
                    — <span class="fw-normal"><?= htmlspecialchars($peserta['nama']) ?></span>
                </div>
                <div class="topbar-sub">Mitra BPS — Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </header>

        <div class="page-body">

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center justify-content-between" style="border-radius:10px">
                    <span><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></span>
                    <a href="index.php" class="btn btn-sm btn-success">Kembali ke Daftar</a>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:10px">
                    <i class="bi bi-x-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="mainForm">

                <!-- ═══════════════════════════════════════════════
                     BLOK I — KETERANGAN CALON MITRA
                ════════════════════════════════════════════════ -->
                <div class="card stat-card mb-4">
                    <div class="card-body">
                        <div class="blok-title"><i class="bi bi-person-vcard-fill"></i>BLOK I — Keterangan Calon Mitra</div>

                        <!-- Info display from peserta table -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">Nama Lengkap</div>
                                <div class="info-display"><?= htmlspecialchars($peserta['nama']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">Posisi Daftar</div>
                                <div class="info-display"><?= htmlspecialchars($peserta['posisi'] ?: ($peserta['posisi_daftar'] ?? '—')) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">Alamat Domisili</div>
                                <div class="info-display"><?= htmlspecialchars($peserta['alamat_detail'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Pendidikan Terakhir</div>
                                <div class="info-display"><?= htmlspecialchars($peserta['pendidikan'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small mb-1">Pekerjaan</div>
                                <div class="info-display"><?= htmlspecialchars($peserta['pekerjaan'] ?? '—') ?></div>
                            </div>
                            <div class="col-12">
                                <label class="q-label">Status Perkawinan</label>
                                <div class="radio-row flex-wrap" style="gap:12px 24px">
                                    <?php
                                    $spkOpts = ['Belum Kawin','Kawin Tercatat','Kawin Belum Tercatat','Cerai Hidup','Cerai Mati'];
                                    foreach ($spkOpts as $opt):
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="status_perkawinan" value="<?= $opt ?>"
                                               id="spk_<?= md5($opt) ?>"
                                               <?= sel($w['status_perkawinan'] ?? '', $opt) ?>>
                                        <label class="form-check-label" for="spk_<?= md5($opt) ?>"><?= $opt ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider"></div>

                        <!-- Foto + new Blok I fields -->
                        <div class="row g-4">

                            <!-- Foto -->
                            <div class="col-md-3 col-lg-2">
                                <div class="text-muted small mb-2">Foto Peserta</div>
                                <?php
                                $fotoAda   = !empty($w['foto']) && file_exists(__DIR__ . '/uploads/foto/' . $w['foto']);
                                $fotoCalon = $fotoAda ? null : findFotoCalon($peserta['nama'] ?? '');
                                $adaGbr    = $fotoAda || $fotoCalon;
                                ?>
                                <?php if ($fotoAda): ?>
                                    <img src="uploads/foto/<?= htmlspecialchars($w['foto']) ?>"
                                         id="fotoPreview" class="foto-img" alt="Foto">
                                <?php elseif ($fotoCalon): ?>
                                    <img src="<?= fotoCalon_url($fotoCalon) ?>"
                                         id="fotoPreview" class="foto-img" alt="Foto">
                                <?php else: ?>
                                    <div class="foto-placeholder" id="fotoPlaceholder">
                                        <i class="bi bi-person-fill"></i>
                                        <small style="font-size:.7rem;margin-top:6px">Belum ada foto</small>
                                    </div>
                                    <img id="fotoPreview" class="foto-img d-none" alt="Preview">
                                <?php endif; ?>
                                <button type="button" class="foto-change-btn mt-2"
                                        onclick="document.getElementById('fotoInput').click()">
                                    <i class="bi bi-camera me-1"></i><?= $fotoAda ? 'Ganti Foto' : 'Upload Foto' ?>
                                </button>
                                <input type="file" name="foto" id="fotoInput" accept="image/*" class="d-none" onchange="previewFoto(this)">
                                <div id="fotoInfo" class="mt-1 small d-none"></div>
                            </div>

                            <!-- Fields -->
                            <div class="col">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="q-label">Kepemilikan Motor</label>
                                        <div class="radio-row">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="kepemilikan_motor" value="Ya" <?= sel($w['kepemilikan_motor'] ?? '', 'Ya') ?>><label class="form-check-label">Ya</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="kepemilikan_motor" value="Tidak" <?= sel($w['kepemilikan_motor'] ?? '', 'Tidak') ?>><label class="form-check-label">Tidak</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="q-label">Kemampuan Berkendara</label>
                                        <div class="radio-row">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="kemampuan_berkendara" value="Ya" <?= sel($w['kemampuan_berkendara'] ?? '', 'Ya') ?>><label class="form-check-label">Ya</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="kemampuan_berkendara" value="Tidak" <?= sel($w['kemampuan_berkendara'] ?? '', 'Tidak') ?>><label class="form-check-label">Tidak</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="q-label">Pernah CAPI</label>
                                        <div class="radio-row">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="pernah_capi" value="Ya" <?= sel($w['pernah_capi'] ?? '', 'Ya') ?>><label class="form-check-label">Ya</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="pernah_capi" value="Tidak" <?= sel($w['pernah_capi'] ?? '', 'Tidak') ?>><label class="form-check-label">Tidak</label></div>
                                        </div>
                                    </div>

                                    <!-- Data HP: terisi otomatis, bisa diubah saat wawancara -->
                                    <div class="col-12">
                                        <div class="p-3 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
                                            <div class="fw-semibold mb-3" style="font-size:.82rem;color:#6c757d;">
                                                <i class="bi bi-phone me-1" style="color:#f79039"></i>DATA HP
                                                <span class="fw-normal ms-1">(terisi otomatis dari data pendaftaran, dapat diubah saat wawancara)</span>
                                            </div>

                                            <?php
                                            // Nilai pre-fill: utamakan yang sudah disimpan di wawancara, fallback ke peserta (Excel)
                                            $hpRows = [
                                                [
                                                    'label'       => 'Merk HP',
                                                    'input_name'  => 'merk_hp',
                                                    'input_val'   => ($w['merk_hp']  ?? '') ?: ($peserta['merk_hp']  ?? ''),
                                                    'sesuai_name' => 'apakah_sesuai_merk',
                                                    'sesuai_val'  => $w['apakah_sesuai_merk'] ?? '',
                                                    'placeholder' => 'Contoh: Samsung',
                                                ],
                                                [
                                                    'label'       => 'Tipe HP',
                                                    'input_name'  => 'tipe_hp',
                                                    'input_val'   => ($w['tipe_hp']  ?? '') ?: ($peserta['tipe_hp']  ?? ''),
                                                    'sesuai_name' => 'apakah_sesuai_tipe',
                                                    'sesuai_val'  => $w['apakah_sesuai_tipe'] ?? '',
                                                    'placeholder' => 'Contoh: Galaxy A15',
                                                ],
                                                [
                                                    'label'       => 'RAM (GB)',
                                                    'input_name'  => 'ram_hp',
                                                    'input_val'   => ($w['ram_hp']   ?? '') ?: ($peserta['ram_hp']   ?? ''),
                                                    'sesuai_name' => 'apakah_sesuai_ram',
                                                    'sesuai_val'  => $w['apakah_sesuai_ram'] ?? '',
                                                    'placeholder' => 'Contoh: 4',
                                                ],
                                            ];
                                            foreach ($hpRows as $idx => $hr):
                                                $hasVal  = $hr['input_val'] !== '';
                                                $isYa    = $hr['sesuai_val'] === 'Iya';
                                                $isTidak = $hr['sesuai_val'] === 'Tidak';
                                                // readonly jika ada nilai pre-fill dan belum dipilih "Tidak"
                                                $rdonly  = ($hasVal && !$isTidak) ? 'readonly' : '';
                                                $inputId = 'input_' . $hr['input_name'];
                                            ?>
                                            <div class="row g-2 align-items-center <?= $idx > 0 ? 'mt-3 pt-3 border-top' : '' ?>">
                                                <div class="col-md-4 col-12">
                                                    <label class="q-label mb-1"><?= $hr['label'] ?></label>
                                                    <input type="text"
                                                           id="<?= $inputId ?>"
                                                           name="<?= $hr['input_name'] ?>"
                                                           class="form-control form-control-sm<?= $rdonly ? ' bg-light' : '' ?>"
                                                           value="<?= htmlspecialchars($hr['input_val']) ?>"
                                                           placeholder="<?= $hr['placeholder'] ?>"
                                                           <?= $rdonly ?>>
                                                </div>
                                                <div class="col-md-5 col-12">
                                                    <label class="q-label mb-1" style="font-size:.85rem">Apakah sesuai?</label>
                                                    <div class="radio-row" style="gap:20px">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                   name="<?= $hr['sesuai_name'] ?>" value="Iya"
                                                                   id="<?= $hr['sesuai_name'] ?>_iya"
                                                                   data-target="<?= $inputId ?>"
                                                                   <?= sel($hr['sesuai_val'], 'Iya') ?>>
                                                            <label class="form-check-label" for="<?= $hr['sesuai_name'] ?>_iya">Iya</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                   name="<?= $hr['sesuai_name'] ?>" value="Tidak"
                                                                   id="<?= $hr['sesuai_name'] ?>_tidak"
                                                                   data-target="<?= $inputId ?>"
                                                                   <?= sel($hr['sesuai_val'], 'Tidak') ?>>
                                                            <label class="form-check-label" for="<?= $hr['sesuai_name'] ?>_tidak">Tidak</label>
                                                        </div>
                                                    </div>
                                                    <?php if ($isTidak): ?>
                                                    <small class="text-danger d-block mt-1">
                                                        <i class="bi bi-pencil-fill me-1"></i>Isian dapat diubah
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>

                                            <!-- Storage HP -->
                                            <div class="row g-2 align-items-end mt-2 pt-2 border-top">
                                                <div class="col-md-2 col-12">
                                                    <label class="q-label" style="font-size:.85rem;margin-bottom:4px">Storage HP</label>
                                                    <div class="input-group input-group-sm" style="max-width:160px">
                                                        <input type="text" name="storage_hp" class="form-control"
                                                               value="<?= htmlspecialchars($w['storage_hp'] ?? '') ?>"
                                                               placeholder="mis. 128">
                                                        <span class="input-group-text">GB</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     BLOK II — PERTANYAAN
                ════════════════════════════════════════════════ -->
                <div class="card stat-card mb-4">
                    <div class="card-body">
                        <div class="blok-title"><i class="bi bi-chat-left-text-fill"></i>BLOK II — Pertanyaan</div>
                        <div class="alert mb-4 py-2" style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem">
                            <i class="bi bi-info-circle me-1" style="color:#f79039"></i>
                            <strong>Pertanyaan no. 1–8 bersifat opsional</strong> — dapat ditulis jawabannya atau tidak.
                        </div>

                        <!-- Q1 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">1</span>
                                <label class="q-label mb-0">Silahkan perkenalkan diri Anda! <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_perkenalan" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_perkenalan'] ?? '') ?></textarea>
                        </div>

                        <!-- Q2 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">2</span>
                                <label class="q-label mb-0">Apakah yang Anda ketahui tentang BPS dan pekerjaannya jika nanti Anda diterima? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_pengetahuan_bps" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_pengetahuan_bps'] ?? '') ?></textarea>
                        </div>

                        <!-- Q3 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">3</span>
                                <label class="q-label mb-0">Kenapa Anda tertarik mendaftar di BPS? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_alasan_daftar" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_alasan_daftar'] ?? '') ?></textarea>
                        </div>

                        <!-- Q4 -->
                        <div class="mb-3">
                            <div class="q-header">
                                <span class="q-number">4</span>
                                <label class="q-label mb-0">Apakah pernah mengikuti kegiatan pendataan di BPS? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <div class="radio-row">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pernah_pendataan" value="Ya"
                                           id="pp_ya" <?= sel($w['pernah_pendataan'] ?? '', 'Ya') ?>
                                           onchange="toggleQ5()">
                                    <label class="form-check-label" for="pp_ya">Ya</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pernah_pendataan" value="Tidak"
                                           id="pp_tidak" <?= sel($w['pernah_pendataan'] ?? '', 'Tidak') ?>
                                           onchange="toggleQ5()">
                                    <label class="form-check-label" for="pp_tidak">Tidak</label>
                                </div>
                            </div>
                        </div>

                        <!-- Q5 (conditional) -->
                        <div class="mb-4 ps-4 border-start border-2" id="q5Block"
                             style="border-color:#f79039 !important; <?= ($w['pernah_pendataan'] ?? '') === 'Ya' ? '' : 'display:none' ?>">
                            <div class="q-header">
                                <span class="q-number">5</span>
                                <label class="q-label mb-0">Jika pernah, apa evaluasi atau keluh kesah yang dimiliki terhadap pendataan yang diikuti tersebut? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_evaluasi" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_evaluasi'] ?? '') ?></textarea>
                        </div>

                        <!-- Q6 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">6</span>
                                <label class="q-label mb-0">Jika saat pendataan lapangan terdapat responden yang menolak didata padahal seharusnya didata, apa yang akan Bapak/Ibu lakukan? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_penolak" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_penolak'] ?? '') ?></textarea>
                        </div>

                        <!-- Q7 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">7</span>
                                <label class="q-label mb-0">Bagaimana jika saat Bapak/Ibu dipekerjakan, ternyata Bapak/Ibu tidak cocok dengan PML atau atasan? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_konflik" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_konflik'] ?? '') ?></textarea>
                        </div>

                        <!-- Q8 -->
                        <div class="mb-4">
                            <div class="q-header">
                                <span class="q-number">8</span>
                                <label class="q-label mb-0">Bagaimana cara mengelola waktu sehari-hari, misalnya saat kegiatan sangat padat dan banyak tanggung jawab yang sedang diemban? <span class="badge bg-secondary fw-normal ms-1" style="font-size:.7rem">Opsional</span></label>
                            </div>
                            <textarea name="jawab_manajemen_waktu" class="form-control" rows="3"
                                      placeholder="Tuliskan jawaban peserta..."><?= htmlspecialchars($w['jawab_manajemen_waktu'] ?? '') ?></textarea>
                        </div>

                        <div class="section-divider"></div>

                        <!-- Q9–Q18: Yes/No grid -->
                        <div class="row g-4">

                            <!-- Q9 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">9</span>
                                    <label class="q-label mb-0">Apakah saat ini sedang dalam proses/berstatus CPNS/PNS/PPPK?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_asn" value="Ya" <?= sel($w['status_asn'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_asn" value="Tidak" <?= sel($w['status_asn'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q10 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">10</span>
                                    <label class="q-label mb-0">Apakah saat ini sedang bekerja dengan sistem kontrak?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_kontrak" value="Ya" <?= sel($w['status_kontrak'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_kontrak" value="Tidak" <?= sel($w['status_kontrak'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q11 (perempuan only) -->
                            <?php if ($isPerempuan): ?>
                            <div class="col-md-6" id="q11Block">
                                <div class="q-header">
                                    <span class="q-number">11</span>
                                    <label class="q-label mb-0">Apakah sedang hamil? <span class="badge bg-secondary fw-normal ms-1">Khusus perempuan kawin</span></label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_hamil" value="Ya" <?= sel($w['status_hamil'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="status_hamil" value="Tidak" <?= sel($w['status_hamil'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="status_hamil" value="">
                            <?php endif; ?>

                            <!-- Q12 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">12</span>
                                    <label class="q-label mb-0">Apakah memiliki anak kecil/balita?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="punya_balita" value="Ya" id="pb_ya" <?= sel($w['punya_balita'] ?? '', 'Ya') ?> onchange="toggleKet('pb_ya','q12ket')"><label class="form-check-label" for="pb_ya">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="punya_balita" value="Tidak" id="pb_tidak" <?= sel($w['punya_balita'] ?? '', 'Tidak') ?> onchange="toggleKet('pb_ya','q12ket')"><label class="form-check-label" for="pb_tidak">2. Tidak</label></div>
                                </div>
                                <div id="q12ket" class="mt-2" style="<?= ($w['punya_balita'] ?? '') === 'Ya' ? '' : 'display:none' ?>">
                                    <textarea name="keterangan_balita" class="form-control form-control-sm" rows="2"
                                              placeholder="Bagaimana upaya membagi waktu?"><?= htmlspecialchars($w['keterangan_balita'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Q13 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">13</span>
                                    <label class="q-label mb-0">Apakah pasangan Anda juga mendaftar atau sudah terdaftar sebagai mitra BPS Kab. Toli-Toli?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="pasangan_daftar" value="Ya" <?= sel($w['pasangan_daftar'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="pasangan_daftar" value="Tidak" <?= sel($w['pasangan_daftar'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q14 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">14</span>
                                    <label class="q-label mb-0">Apakah memiliki penyakit kronis/mudah sakit?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="penyakit_kronis" value="Ya" id="pyk_ya" <?= sel($w['penyakit_kronis'] ?? '', 'Ya') ?> onchange="toggleKet('pyk_ya','q14ket')"><label class="form-check-label" for="pyk_ya">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="penyakit_kronis" value="Tidak" id="pyk_tidak" <?= sel($w['penyakit_kronis'] ?? '', 'Tidak') ?> onchange="toggleKet('pyk_ya','q14ket')"><label class="form-check-label" for="pyk_tidak">2. Tidak</label></div>
                                </div>
                                <div id="q14ket" class="mt-2" style="<?= ($w['penyakit_kronis'] ?? '') === 'Ya' ? '' : 'display:none' ?>">
                                    <textarea name="keterangan_penyakit" class="form-control form-control-sm" rows="2"
                                              placeholder="Bagaimana upaya menangani hal tersebut?"><?= htmlspecialchars($w['keterangan_penyakit'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Q15 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">15</span>
                                    <label class="q-label mb-0">Apakah bersedia bertugas di kecamatan lain?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_kec_lain" value="Ya" <?= sel($w['bersedia_kec_lain'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_kec_lain" value="Tidak" <?= sel($w['bersedia_kec_lain'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q16 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">16</span>
                                    <label class="q-label mb-0">Apakah bersedia bertanggung jawab atas kesalahan dan kewajaran isian dokumen yang menjadi beban tugasnya?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_tanggung_jawab" value="Ya" <?= sel($w['bersedia_tanggung_jawab'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_tanggung_jawab" value="Tidak" <?= sel($w['bersedia_tanggung_jawab'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q17 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">17</span>
                                    <label class="q-label mb-0">Apakah bersedia untuk melakukan validasi setelah data diolah/dientri?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_validasi" value="Ya" <?= sel($w['bersedia_validasi'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_validasi" value="Tidak" <?= sel($w['bersedia_validasi'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                            <!-- Q18 -->
                            <div class="col-md-6">
                                <div class="q-header">
                                    <span class="q-number">18</span>
                                    <label class="q-label mb-0">Apakah bersedia jika dibayar setelah semua dokumen diterima dan clean?</label>
                                </div>
                                <div class="radio-row">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_bayar_clean" value="Ya" <?= sel($w['bersedia_bayar_clean'] ?? '', 'Ya') ?>><label class="form-check-label">1. Ya</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="bersedia_bayar_clean" value="Tidak" <?= sel($w['bersedia_bayar_clean'] ?? '', 'Tidak') ?>><label class="form-check-label">2. Tidak</label></div>
                                </div>
                            </div>

                        </div><!-- /row Q9–Q18 -->
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     BLOK III — PRAKTIK WAWANCARA
                ════════════════════════════════════════════════ -->
                <div class="card stat-card mb-4">
                    <div class="card-body">
                        <div class="blok-title"><i class="bi bi-clipboard2-pulse-fill"></i>BLOK III — Praktik Wawancara oleh Calon Petugas</div>
                        <div class="alert mb-4" style="background:#fff8f0;border:1px solid #f79039;border-radius:10px;font-size:.875rem">
                            <i class="bi bi-info-circle-fill me-2" style="color:#f79039"></i>
                            Pewawancara memberikan kuesioner kepada calon mitra untuk dicoba dipraktikkan.
                            Amati cara calon mitra membaca, memahami, dan menyampaikan pertanyaan kuesioner tersebut.
                        </div>
                        <div class="mb-2">
                            <label class="q-label">Tanggapan Pewawancara <span class="badge bg-secondary fw-normal ms-1" style="font-size:.72rem">Opsional</span></label>
                            <textarea name="tanggapan_praktik" class="form-control" rows="4"
                                      placeholder="Tuliskan tanggapan/catatan terhadap praktik wawancara calon mitra (tidak wajib diisi)..."><?= htmlspecialchars($w['tanggapan_praktik'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     BLOK IV — PENILAIAN PEWAWANCARA
                ════════════════════════════════════════════════ -->
                <div class="card stat-card mb-4" style="border-top:3px solid #f79039">
                    <div class="card-body">
                        <div class="blok-title"><i class="bi bi-star-fill"></i>BLOK IV — Penilaian Pewawancara</div>
                        <p class="text-muted small mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            Rentang: <strong>91–100</strong> Baik Sekali &nbsp;|&nbsp;
                            <strong>76–90</strong> Baik &nbsp;|&nbsp;
                            <strong>51–75</strong> Sedang &nbsp;|&nbsp;
                            <strong>0–50</strong> Kurang
                        </p>

                        <div class="row g-4">
                            <!-- Komunikasi -->
                            <div class="col-md-4">
                                <div class="score-wrap">
                                    <label class="q-label">19. Komunikasi</label>
                                    <div class="text-muted small mb-3">Dapat menyampaikan jawaban dengan lugas, jelas, dan dapat dimengerti</div>
                                    <div class="score-num" id="dispKom"><?= $w['nilai_komunikasi'] ?? 0 ?></div>
                                    <div class="mb-2" id="badgeKom"><?= scoreBadge($w['nilai_komunikasi'] ?? 0) ?></div>
                                    <input type="number" name="nilai_komunikasi" id="inpKom"
                                           class="form-control" min="0" max="100"
                                           value="<?= (int)($w['nilai_komunikasi'] ?? 0) ?>"
                                           oninput="updateScore('inpKom','dispKom','badgeKom')" required>
                                </div>
                            </div>

                            <!-- Penampilan -->
                            <div class="col-md-4">
                                <div class="score-wrap">
                                    <label class="q-label">20. Penampilan</label>
                                    <div class="text-muted small mb-3">&nbsp;</div>
                                    <div class="score-num" id="dispPen"><?= $w['nilai_penampilan'] ?? 0 ?></div>
                                    <div class="mb-2" id="badgePen"><?= scoreBadge($w['nilai_penampilan'] ?? 0) ?></div>
                                    <input type="number" name="nilai_penampilan" id="inpPen"
                                           class="form-control" min="0" max="100"
                                           value="<?= (int)($w['nilai_penampilan'] ?? 0) ?>"
                                           oninput="updateScore('inpPen','dispPen','badgePen')" required>
                                </div>
                            </div>

                            <!-- Kemampuan Analisa -->
                            <div class="col-md-4">
                                <div class="score-wrap">
                                    <label class="q-label">21. Kemampuan Analisa</label>
                                    <div class="text-muted small mb-3">Sistematika jawaban, relevansi jawaban atas pertanyaan yang diajukan</div>
                                    <div class="score-num" id="dispAna"><?= $w['nilai_analisa'] ?? 0 ?></div>
                                    <div class="mb-2" id="badgeAna"><?= scoreBadge($w['nilai_analisa'] ?? 0) ?></div>
                                    <input type="number" name="nilai_analisa" id="inpAna"
                                           class="form-control" min="0" max="100"
                                           value="<?= (int)($w['nilai_analisa'] ?? 0) ?>"
                                           oninput="updateScore('inpAna','dispAna','badgeAna')" required>
                                </div>
                            </div>
                        </div>

                        <!-- Rata-rata -->
                        <div class="mt-4 p-3 rounded" style="background:#fff8f0; border:1px solid #f79039;">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <div class="text-muted small">Nilai Rata-rata</div>
                                    <div class="score-num" id="dispRata"><?= $w['nilai'] ?? 0 ?></div>
                                </div>
                                <div id="badgeRata"><?= scoreBadge($w['nilai'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════
                     BLOK V — CATATAN
                ════════════════════════════════════════════════ -->
                <div class="card stat-card mb-4">
                    <div class="card-body">
                        <div class="blok-title"><i class="bi bi-journal-text"></i>BLOK V — Catatan</div>

                        <div class="mb-4">
                            <label class="q-label">Catatan Pewawancara</label>
                            <textarea name="catatan" class="form-control" rows="3"
                                      placeholder="Catatan tambahan (opsional)"><?= htmlspecialchars($w['catatan'] ?? '') ?></textarea>
                        </div>

                        <label class="q-label mb-3">Predikat / Rekomendasi Pewawancara</label>
                        <div class="row g-2">
                            <?php
                            $predikatList = [
                                'A' => ['label' => 'Sangat Direkomendasikan', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
                                'B' => ['label' => 'Direkomendasikan',        'color' => '#2563eb', 'bg' => '#eff6ff'],
                                'C' => ['label' => 'Biasa Saja',              'color' => '#d97706', 'bg' => '#fffbeb'],
                                'D' => ['label' => 'Kurang Direkomendasikan', 'color' => '#ea580c', 'bg' => '#fff7ed'],
                                'E' => ['label' => 'Tidak Direkomendasikan',  'color' => '#dc2626', 'bg' => '#fef2f2'],
                            ];
                            foreach ($predikatList as $kode => $info):
                                $checked = ($w['predikat'] ?? '') === $kode;
                            ?>
                            <div class="col-md-4 col-lg">
                                <label class="predikat-card w-100 <?= $checked ? 'selected' : '' ?>"
                                       style="<?= $checked ? "border-color:{$info['color']};background:{$info['bg']}" : '' ?>"
                                       onclick="selectPredikat(this, '<?= $kode ?>', '<?= $info['color'] ?>', '<?= $info['bg'] ?>')">
                                    <input type="radio" name="predikat" value="<?= $kode ?>" class="d-none" <?= $checked ? 'checked' : '' ?> required>
                                    <div class="predikat-badge" style="background:<?= $info['bg'] ?>; color:<?= $info['color'] ?>; border:2px solid <?= $info['color'] ?>">
                                        <?= $kode ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size:.9rem; color:<?= $info['color'] ?>"><?= $kode ?></div>
                                        <div class="text-muted" style="font-size:.78rem"><?= $info['label'] ?></div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-3 pb-4 flex-wrap align-items-center">
                    <button type="submit" class="btn btn-lg px-5 text-white fw-semibold" style="background:#f79039;border-radius:10px;">
                        <i class="bi bi-save-fill me-2"></i>Simpan Data Wawancara
                    </button>
                    <a href="index.php" class="btn btn-lg btn-outline-secondary" style="border-radius:10px;">Batal</a>

                    <?php if ($w): ?>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-lg btn-outline-danger" style="border-radius:10px;"
                                data-bs-toggle="modal" data-bs-target="#modalResetForm">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Jawaban
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

            </form>
        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<?php if ($w): ?>
<!-- Modal Reset Jawaban -->
<div class="modal fade" id="modalResetForm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:#dc2626;">
                <h6 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Data Wawancara
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-1 fw-bold"><?= htmlspecialchars($peserta['nama']) ?></p>
                <p class="mb-0 text-muted" style="font-size:.875rem">
                    Semua jawaban wawancara yang sudah diisi akan
                    <strong>dihapus permanen</strong> dan peserta kembali ke status
                    <em>Belum Diwawancara</em>.
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="reset_wawancara.php" class="d-inline">
                    <input type="hidden" name="wawancara_id" value="<?= (int)$w['id'] ?>">
                    <input type="hidden" name="redirect" value="index.php">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Ya, Reset Jawaban
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 3 * 1024 * 1024) {
        const info = document.getElementById('fotoInfo');
        info.textContent = 'File terlalu besar (maks. 3 MB).';
        info.className = 'mt-1 small text-danger';
        info.classList.remove('d-none');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('fotoPreview');
        const placeholder = document.getElementById('fotoPlaceholder');
        preview.src = e.target.result;
        preview.classList.remove('d-none');
        if (placeholder) placeholder.classList.add('d-none');
    };
    reader.readAsDataURL(file);
}

function toggleQ5() {
    const ya = document.getElementById('pp_ya');
    document.getElementById('q5Block').style.display = ya && ya.checked ? '' : 'none';
}

function toggleKet(radioId, blockId) {
    const radio = document.getElementById(radioId);
    document.getElementById(blockId).style.display = radio && radio.checked ? '' : 'none';
}

function badgeHTML(v) {
    v = parseInt(v) || 0;
    if (v >= 91) return '<span class="badge bg-success ms-2">Baik Sekali (91–100)</span>';
    if (v >= 76) return '<span class="badge bg-primary ms-2">Baik (76–90)</span>';
    if (v >= 51) return '<span class="badge bg-warning text-dark ms-2">Sedang (51–75)</span>';
    if (v > 0)   return '<span class="badge bg-danger ms-2">Kurang (0–50)</span>';
    return '';
}

function updateScore(inpId, dispId, badgeId) {
    const v = Math.min(100, Math.max(0, parseInt(document.getElementById(inpId).value) || 0));
    document.getElementById(dispId).textContent = v;
    document.getElementById(badgeId).innerHTML  = badgeHTML(v);
    updateRata();
}

function updateRata() {
    const a = parseInt(document.getElementById('inpKom').value) || 0;
    const b = parseInt(document.getElementById('inpPen').value) || 0;
    const c = parseInt(document.getElementById('inpAna').value) || 0;
    const r = Math.round((a + b + c) / 3);
    document.getElementById('dispRata').textContent = r;
    document.getElementById('badgeRata').innerHTML  = badgeHTML(r);
}

function selectPredikat(label, kode, color, bg) {
    document.querySelectorAll('.predikat-card').forEach(el => {
        el.classList.remove('selected');
        el.style.borderColor = '';
        el.style.background  = '';
    });
    label.classList.add('selected');
    label.style.borderColor = color;
    label.style.background  = bg;
    label.querySelector('input[type=radio]').checked = true;
}

// HP field lock/unlock: unlock input when "Tidak" selected, lock when "Iya"
document.querySelectorAll('[data-target]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var input = document.getElementById(this.dataset.target);
        if (!input) return;
        var hint = this.closest('.col-md-5, .col-12').querySelector('small.text-danger');
        if (this.value === 'Tidak') {
            input.removeAttribute('readonly');
            input.classList.remove('bg-light');
            input.focus();
            if (!hint) {
                var s = document.createElement('small');
                s.className = 'text-danger d-block mt-1';
                s.innerHTML = '<i class="bi bi-pencil-fill me-1"></i>Isian dapat diubah';
                this.closest('.col-md-5, .col-12').appendChild(s);
            }
        } else {
            input.setAttribute('readonly', '');
            input.classList.add('bg-light');
            if (hint) hint.remove();
        }
    });
});
</script>
</body>
</html>
