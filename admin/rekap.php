<?php
require_once 'config.php';
requireSuperAdmin();

$search   = trim($_GET['search'] ?? '');
$filter   = $_GET['filter'] ?? 'semua';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$where  = "WHERE p.status_seleksi = 'Diterima' AND w.id IS NOT NULL";
$params = []; $types = '';

if ($search !== '') {
    $where  .= " AND (p.nama LIKE ? OR p.no_telp LIKE ?)";
    $like    = "%$search%";
    $params  = [$like, $like]; $types = 'ss';
}
if (in_array($filter, ['A','B','C','D','E'])) {
    $where .= " AND w.predikat = ?";
    $params[] = $filter; $types .= 's';
}

$selectCols = "p.id AS peserta_id, p.nama, p.jenis_kelamin, p.pendidikan, p.pekerjaan,
               p.no_telp, p.email, p.posisi_daftar, p.alamat_kec, p.alamat_detail,
               w.id AS wawancara_id, w.foto,
               w.kepemilikan_motor, w.kemampuan_berkendara, w.pernah_capi,
               w.merk_hp, w.tipe_hp, w.ram_hp,
               w.jawab_perkenalan, w.jawab_pengetahuan_bps, w.jawab_alasan_daftar,
               w.pernah_pendataan, w.jawab_evaluasi, w.jawab_penolak,
               w.jawab_konflik, w.jawab_manajemen_waktu,
               w.status_asn, w.status_kontrak, w.status_hamil,
               w.punya_balita, w.keterangan_balita, w.pasangan_daftar,
               w.penyakit_kronis, w.keterangan_penyakit,
               w.bersedia_kec_lain, w.bersedia_tanggung_jawab,
               w.bersedia_validasi, w.bersedia_bayar_clean,
               w.nilai_komunikasi, w.nilai_penampilan, w.nilai_analisa, w.nilai,
               w.catatan, w.predikat,
               a.nama AS pewawancara, w.created_at";

$joinSQL = "FROM peserta p
            JOIN wawancara w ON w.id = (
                SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
            )
            JOIN admin_seleksi a ON a.id = w.admin_id
            $where";

$stmt = $conn->prepare("SELECT $selectCols $joinSQL ORDER BY w.nilai DESC, p.nama ASC LIMIT ? OFFSET ?");
$paramsFull = array_merge($params, [$perPage, $offset]);
$typesFull  = $types . 'ii';
$stmt->bind_param($typesFull, ...$paramsFull);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtC = $conn->prepare("SELECT COUNT(*) $joinSQL");
if ($params) $stmtC->bind_param($types, ...$params);
$stmtC->execute();
$total     = $stmtC->get_result()->fetch_row()[0];
$totalPage = ceil($total / $perPage);

$stats = $conn->query("SELECT
    SUM(CASE WHEN w.predikat='A' THEN 1 ELSE 0 END) AS jml_a,
    SUM(CASE WHEN w.predikat='B' THEN 1 ELSE 0 END) AS jml_b,
    SUM(CASE WHEN w.predikat='C' THEN 1 ELSE 0 END) AS jml_c,
    SUM(CASE WHEN w.predikat='D' THEN 1 ELSE 0 END) AS jml_d,
    SUM(CASE WHEN w.predikat='E' THEN 1 ELSE 0 END) AS jml_e,
    ROUND(AVG(w.nilai),1) AS rata_nilai, MAX(w.nilai) AS nilai_max
FROM peserta p JOIN wawancara w ON w.id = (
    SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
)
WHERE p.status_seleksi='Diterima'")->fetch_assoc();

$predikatInfo = [
    'A' => ['label' => 'Sangat Direkomendasikan', 'color' => '#16a34a', 'bg' => '#d1fae5'],
    'B' => ['label' => 'Direkomendasikan',        'color' => '#2563eb', 'bg' => '#dbeafe'],
    'C' => ['label' => 'Biasa Saja',              'color' => '#d97706', 'bg' => '#fef3c7'],
    'D' => ['label' => 'Kurang Direkomendasikan', 'color' => '#ea580c', 'bg' => '#ffedd5'],
    'E' => ['label' => 'Tidak Direkomendasikan',  'color' => '#dc2626', 'bg' => '#fee2e2'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Wawancara — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        th { white-space:nowrap; font-size:.76rem; }
        td { font-size:.83rem; }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .detail-foto { width:110px; height:140px; object-fit:cover; border-radius:10px; border:2px solid #f79039; }
        .detail-foto-placeholder { width:110px; height:140px; background:#f8f9fa; border-radius:10px;
            border:2px dashed #dee2e6; display:flex; flex-direction:column;
            align-items:center; justify-content:center; color:#adb5bd; font-size:2.5rem; }
        .blok-title { font-size:.68rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
            color:#f79039; border-bottom:2px solid #f79039; padding-bottom:4px; margin-bottom:10px; }
        .detail-label { font-size:.7rem; color:#6c757d; margin-bottom:1px; }
        .detail-val   { font-size:.855rem; font-weight:500; color:#1e293b; }
        .answer-box   { background:#f8f9fa; border-radius:8px; padding:9px 12px;
            font-size:.83rem; color:#374151; line-height:1.6; min-height:40px; }
        .badge-ya     { background:#d1fae5; color:#065f46; }
        .badge-tidak-kecil { background:#fee2e2; color:#991b1b; }
        .badge-info   { background:#dbeafe; color:#1e40af; }
        .score-chip   { display:inline-flex; align-items:center; gap:6px; background:#fff8f0;
            border:1px solid #f79039; border-radius:8px; padding:4px 12px; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-clipboard2-data-fill me-2" style="color:#f79039"></i>Rekap Hasil Wawancara</div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <a href="export.php?search=<?= urlencode($search) ?>&filter=<?= $filter ?>"
                   class="btn btn-sm d-flex align-items-center gap-2 fw-semibold"
                   style="background:#16a34a;color:#fff;border-radius:8px;">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    <span class="d-none d-md-inline">Download Excel</span>
                </a>
            </div>
        </header>

        <div class="page-body">

            <!-- Stat Cards Predikat -->
            <div class="row g-3 mb-4">
                <?php foreach ($predikatInfo as $kode => $info): ?>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:<?= $info['bg'] ?>; color:<?= $info['color'] ?>; font-size:1.1rem; font-weight:800;">
                                <?= $kode ?>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['jml_'.strtolower($kode)] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem"><?= $info['label'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-star-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['rata_nilai'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Rata-rata Nilai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card stat-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                       placeholder="Cari nama atau telepon..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="filter" class="form-select">
                                <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua Predikat</option>
                                <?php foreach ($predikatInfo as $kode => $info): ?>
                                <option value="<?= $kode ?>" <?= $filter===$kode?'selected':'' ?>>
                                    <?= $kode ?> — <?= $info['label'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn w-100 text-white fw-semibold" style="background:#f79039">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="rekap.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card stat-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:#f79039; color:#fff;">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nama</th>
                                    <th>JK</th>
                                    <th>Pendidikan</th>
                                    <th>No. Telp</th>
                                    <th class="text-center">Motor</th>
                                    <th>HP</th>
                                    <th class="text-center">Kom.</th>
                                    <th class="text-center">Pen.</th>
                                    <th class="text-center">Ana.</th>
                                    <th class="text-center">Rata²</th>
                                    <th class="text-center">Predikat</th>
                                    <th>Pewawancara</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="14" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data wawancara.
                                </td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $i => $r):
                                $pi = $predikatInfo[$r['predikat']] ?? null;
                            ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($r['nama']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['posisi_daftar'] ?? '') ?></small>
                                    </td>
                                    <td><small><?= $r['jenis_kelamin'] === 'Lk' ? 'L' : 'P' ?></small></td>
                                    <td><small><?= htmlspecialchars($r['pendidikan']) ?></small></td>
                                    <td><small><?= htmlspecialchars($r['no_telp']) ?></small></td>
                                    <td class="text-center">
                                        <?php if ($r['kepemilikan_motor'] === 'Ya'): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php elseif ($r['kepemilikan_motor'] === 'Tidak'): ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:.75rem">
                                        <?= htmlspecialchars($r['merk_hp'] ?? '') ?>
                                        <?= $r['tipe_hp'] ? '<br><span class="text-muted">'.htmlspecialchars($r['tipe_hp']).'</span>' : '' ?>
                                    </td>
                                    <td class="text-center fw-semibold"><?= $r['nilai_komunikasi'] ?: '—' ?></td>
                                    <td class="text-center fw-semibold"><?= $r['nilai_penampilan'] ?: '—' ?></td>
                                    <td class="text-center fw-semibold"><?= $r['nilai_analisa'] ?: '—' ?></td>
                                    <td class="text-center fw-bold" style="color:#f79039"><?= $r['nilai'] ?: '—' ?></td>
                                    <td class="text-center">
                                        <?php if ($pi): ?>
                                        <span class="badge fw-bold px-2" style="background:<?= $pi['bg'] ?>;color:<?= $pi['color'] ?>;font-size:.8rem">
                                            <?= $r['predikat'] ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($r['pewawancara']) ?></small></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick='bukaDetail(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'
                                                    title="Lihat Detail">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick='openReset(<?= $r['wawancara_id'] ?>, <?= htmlspecialchars(json_encode($r['nama']), ENT_QUOTES) ?>)'
                                                    title="Reset Data Wawancara">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($totalPage > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center bg-white">
                    <small class="text-muted">Menampilkan <?= count($rows) ?> dari <?= $total ?> hasil</small>
                    <nav><ul class="pagination pagination-sm mb-0">
                        <?php for ($p2=1; $p2<=$totalPage; $p2++): ?>
                            <li class="page-item <?= $p2===$page?'active':'' ?>">
                                <a class="page-link" href="?page=<?=$p2?>&search=<?=urlencode($search)?>&filter=<?=$filter?>"><?=$p2?></a>
                            </li>
                        <?php endfor; ?>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Reset Wawancara ── -->
<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:#dc2626;">
                <h6 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Data Wawancara
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-1">Data wawancara peserta:</p>
                <p class="fw-bold mb-3" id="resetNama">—</p>
                <p class="mb-0 text-muted" style="font-size:.875rem">
                    akan <strong>dihapus</strong> sehingga peserta dapat diwawancara ulang.
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="reset_wawancara.php" class="d-inline">
                    <input type="hidden" name="wawancara_id" id="resetWid">
                    <input type="hidden" name="redirect" value="rekap.php">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Ya, Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Detail Lengkap ── -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:#f79039;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" id="detailNama">—</h5>
                    <small class="text-white opacity-75" id="detailSub">—</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">

                    <!-- Kolom kiri: foto + profil -->
                    <div class="col-md-3">
                        <div class="text-center mb-3">
                            <img id="detailFoto" class="detail-foto d-none" alt="Foto">
                            <div id="detailFotoPlaceholder" class="detail-foto-placeholder mx-auto">
                                <i class="bi bi-person-fill"></i>
                                <small style="font-size:.7rem;">Tidak ada foto</small>
                            </div>
                        </div>
                        <div class="detail-label">Nama</div><div class="detail-val mb-2" id="d-nama">—</div>
                        <div class="detail-label">JK</div><div class="detail-val mb-2" id="d-jk">—</div>
                        <div class="detail-label">Pendidikan</div><div class="detail-val mb-2" id="d-pend">—</div>
                        <div class="detail-label">Pekerjaan</div><div class="detail-val mb-2" id="d-kerja">—</div>
                        <div class="detail-label">No. Telepon</div><div class="detail-val mb-2" id="d-telp">—</div>
                        <div class="detail-label">Posisi Daftar</div><div class="detail-val mb-2" id="d-posisi">—</div>
                        <div class="detail-label">Pewawancara</div><div class="detail-val mb-2" id="d-pewawancara">—</div>
                        <div class="detail-label">Tgl Wawancara</div><div class="detail-val" id="d-tgl">—</div>
                    </div>

                    <!-- Kolom kanan: semua blok -->
                    <div class="col-md-9">

                        <!-- BLOK I -->
                        <div class="blok-title"><i class="bi bi-person-vcard-fill me-1"></i>Blok I — Keterangan Calon Mitra</div>
                        <div class="row g-2 mb-4">
                            <div class="col-4"><div class="detail-label">Kepemilikan Motor</div><div id="d-motor" class="detail-val">—</div></div>
                            <div class="col-4"><div class="detail-label">Kemampuan Berkendara</div><div id="d-kend" class="detail-val">—</div></div>
                            <div class="col-4"><div class="detail-label">Pernah CAPI</div><div id="d-capi" class="detail-val">—</div></div>
                            <div class="col-4"><div class="detail-label">Merk HP</div><div id="d-merk" class="detail-val">—</div></div>
                            <div class="col-4"><div class="detail-label">Tipe HP</div><div id="d-tipe" class="detail-val">—</div></div>
                            <div class="col-4"><div class="detail-label">RAM HP</div><div id="d-ram" class="detail-val">—</div></div>
                        </div>

                        <!-- BLOK II Q1-Q8 -->
                        <div class="blok-title"><i class="bi bi-chat-left-text-fill me-1"></i>Blok II — Pertanyaan (Uraian)</div>
                        <div class="mb-2"><div class="detail-label mb-1">1. Perkenalan diri</div><div class="answer-box" id="d-q1">—</div></div>
                        <div class="mb-2"><div class="detail-label mb-1">2. Pengetahuan tentang BPS</div><div class="answer-box" id="d-q2">—</div></div>
                        <div class="mb-2"><div class="detail-label mb-1">3. Alasan mendaftar</div><div class="answer-box" id="d-q3">—</div></div>
                        <div class="mb-2"><div class="detail-label mb-1">6. Penanganan responden menolak</div><div class="answer-box" id="d-q6">—</div></div>
                        <div class="mb-2"><div class="detail-label mb-1">7. Penanganan konflik dengan PML/atasan</div><div class="answer-box" id="d-q7">—</div></div>
                        <div class="mb-3"><div class="detail-label mb-1">8. Manajemen waktu</div><div class="answer-box" id="d-q8">—</div></div>

                        <div id="evalBlock" class="mb-3 d-none">
                            <div class="detail-label mb-1">5. Evaluasi pendataan sebelumnya</div>
                            <div class="answer-box" id="d-q5">—</div>
                        </div>

                        <!-- BLOK II Q9-Q18 -->
                        <div class="blok-title mt-1"><i class="bi bi-check2-square me-1"></i>Blok II — Pertanyaan (Ya/Tidak)</div>
                        <div class="row g-2 mb-4" id="d-yatidak"></div>

                        <!-- BLOK IV -->
                        <div class="blok-title"><i class="bi bi-star-fill me-1"></i>Blok IV — Penilaian Pewawancara</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 text-center">
                                <div class="score-chip flex-column py-2">
                                    <div class="fw-bold" style="font-size:1.8rem;color:#f79039;line-height:1" id="d-nkom">—</div>
                                    <div style="font-size:.72rem;color:#64748b">Komunikasi</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="score-chip flex-column py-2">
                                    <div class="fw-bold" style="font-size:1.8rem;color:#f79039;line-height:1" id="d-npen">—</div>
                                    <div style="font-size:.72rem;color:#64748b">Penampilan</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="score-chip flex-column py-2">
                                    <div class="fw-bold" style="font-size:1.8rem;color:#f79039;line-height:1" id="d-nana">—</div>
                                    <div style="font-size:.72rem;color:#64748b">Kemampuan Analisa</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="score-chip flex-column py-2" style="background:#f79039;border-color:#f79039;">
                                    <div class="fw-bold" style="font-size:1.8rem;color:#fff;line-height:1" id="d-nilai">—</div>
                                    <div style="font-size:.72rem;color:rgba(255,255,255,.85)">Rata-rata</div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOK V -->
                        <div class="blok-title"><i class="bi bi-journal-text me-1"></i>Blok V — Catatan</div>
                        <div class="mb-2"><div class="detail-label mb-1">Catatan Pewawancara</div><div class="answer-box" id="d-catatan">—</div></div>
                        <div class="mt-2"><div class="detail-label mb-1">Predikat</div><div id="d-predikat">—</div></div>

                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <a id="detailExportLink" href="#" class="btn btn-sm fw-semibold"
                   style="background:#16a34a;color:#fff;border-radius:8px;">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Download Excel
                </a>
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
const PREDIKAT_INFO = <?= json_encode($predikatInfo) ?>;

function badge(val) {
    if (!val || val === '') return '<span class="text-muted">—</span>';
    const cls = val === 'Ya' ? 'badge-ya' : (val === 'Tidak' ? 'badge-tidak-kecil' : 'badge-info');
    return `<span class="badge rounded-pill px-2 ${cls}">${val}</span>`;
}
function txt(v) { return v || '(belum diisi)'; }

function bukaDetail(d) {
    document.getElementById('detailNama').textContent = d.nama;
    document.getElementById('detailSub').textContent  = (d.posisi_daftar||'') + ' — ' + (d.alamat_kec||'');

    // Foto
    const foto = document.getElementById('detailFoto');
    const ph   = document.getElementById('detailFotoPlaceholder');
    if (d.foto) { foto.src = 'uploads/foto/'+d.foto; foto.classList.remove('d-none'); ph.classList.add('d-none'); }
    else        { foto.classList.add('d-none'); ph.classList.remove('d-none'); }

    // Profil
    document.getElementById('d-nama').textContent = d.nama||'—';
    document.getElementById('d-jk').textContent   = d.jenis_kelamin === 'Lk' ? 'Laki-laki' : 'Perempuan';
    document.getElementById('d-pend').textContent = d.pendidikan||'—';
    document.getElementById('d-kerja').textContent= d.pekerjaan||'—';
    document.getElementById('d-telp').textContent = d.no_telp||'—';
    document.getElementById('d-posisi').textContent     = d.posisi_daftar||'—';
    document.getElementById('d-pewawancara').textContent= d.pewawancara||'—';
    document.getElementById('d-tgl').textContent = d.created_at ? d.created_at.substring(0,16) : '—';

    // Blok I
    document.getElementById('d-motor').innerHTML= badge(d.kepemilikan_motor);
    document.getElementById('d-kend').innerHTML = badge(d.kemampuan_berkendara);
    document.getElementById('d-capi').innerHTML = badge(d.pernah_capi);
    document.getElementById('d-merk').textContent= d.merk_hp||'—';
    document.getElementById('d-tipe').textContent= d.tipe_hp||'—';
    document.getElementById('d-ram').textContent = d.ram_hp||'—';

    // Blok II uraian
    document.getElementById('d-q1').textContent = txt(d.jawab_perkenalan);
    document.getElementById('d-q2').textContent = txt(d.jawab_pengetahuan_bps);
    document.getElementById('d-q3').textContent = txt(d.jawab_alasan_daftar);
    document.getElementById('d-q6').textContent = txt(d.jawab_penolak);
    document.getElementById('d-q7').textContent = txt(d.jawab_konflik);
    document.getElementById('d-q8').textContent = txt(d.jawab_manajemen_waktu);

    // Q5 kondisional
    const evalBlock = document.getElementById('evalBlock');
    if (d.pernah_pendataan === 'Ya') {
        document.getElementById('d-q5').textContent = txt(d.jawab_evaluasi);
        evalBlock.classList.remove('d-none');
    } else { evalBlock.classList.add('d-none'); }

    // Blok II Ya/Tidak Q9-Q18
    const ytData = [
        ['9. Status CPNS/PNS/PPPK',       d.status_asn],
        ['10. Bekerja kontrak',             d.status_kontrak],
        ['11. Sedang hamil',                d.status_hamil],
        ['12. Punya anak balita',           d.punya_balita],
        ['13. Pasangan juga daftar',        d.pasangan_daftar],
        ['14. Penyakit kronis',             d.penyakit_kronis],
        ['15. Bersedia kecamatan lain',     d.bersedia_kec_lain],
        ['16. Bersedia tanggung jawab dok.',d.bersedia_tanggung_jawab],
        ['17. Bersedia validasi data',      d.bersedia_validasi],
        ['18. Bersedia bayar setelah clean',d.bersedia_bayar_clean],
    ];
    document.getElementById('d-yatidak').innerHTML = ytData.map(([lbl,val]) => `
        <div class="col-md-6 col-lg-4">
            <div style="background:#f8f9fa;border-radius:8px;padding:8px 10px;">
                <div class="detail-label">${lbl}</div>
                <div class="mt-1">${badge(val)}</div>
                ${(lbl.includes('balita') && val==='Ya' && d.keterangan_balita) ? `<div class="mt-1 small text-muted">${d.keterangan_balita}</div>` : ''}
                ${(lbl.includes('kronis') && val==='Ya' && d.keterangan_penyakit) ? `<div class="mt-1 small text-muted">${d.keterangan_penyakit}</div>` : ''}
            </div>
        </div>`).join('');

    // Blok IV
    document.getElementById('d-nkom').textContent = d.nilai_komunikasi || '—';
    document.getElementById('d-npen').textContent = d.nilai_penampilan  || '—';
    document.getElementById('d-nana').textContent = d.nilai_analisa     || '—';
    document.getElementById('d-nilai').textContent= d.nilai             || '—';

    // Blok V
    document.getElementById('d-catatan').textContent = d.catatan || '(tidak ada catatan)';
    const pi = PREDIKAT_INFO[d.predikat];
    document.getElementById('d-predikat').innerHTML = pi
        ? `<span class="badge px-3 py-2 fw-bold" style="background:${pi.bg};color:${pi.color};font-size:.9rem">${d.predikat} — ${pi.label}</span>`
        : '<span class="text-muted">—</span>';

    document.getElementById('detailExportLink').href = 'export.php?peserta_id=' + d.peserta_id;
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}

function openReset(wid, nama) {
    document.getElementById('resetWid').value  = wid;
    document.getElementById('resetNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('modalReset')).show();
}
</script>
</body>
</html>
