<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: pelatihan.php'); exit; }

$pelatihan = $conn->query("SELECT * FROM pelatihan WHERE id=$id")->fetch_assoc();
if (!$pelatihan) { header('Location: pelatihan.php'); exit; }

// ─── AJAX: beri nilai uraian ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'beri_nilai') {
    if (!isSuperAdmin()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Tidak ada akses']);
        exit;
    }
    $jawabanId = (int)($_POST['jawaban_id'] ?? 0);
    $poin      = max(0, (float)($_POST['poin_dapat'] ?? 0));
    if ($jawabanId) {
        $conn->query("UPDATE pelatihan_jawaban SET poin_dapat=$poin WHERE id=$jawabanId");
        // Recalculate nilai hasil
        $hasilId = (int)($_POST['hasil_id'] ?? 0);
        if ($hasilId) {
            $totalPoin = $conn->query("SELECT COALESCE(SUM(s.poin),0) AS tp FROM pelatihan_soal s WHERE s.pelatihan_id=$id")->fetch_assoc()['tp'];
            $dapatPoin = $conn->query("SELECT COALESCE(SUM(j.poin_dapat),0) AS dp FROM pelatihan_jawaban j WHERE j.hasil_id=$hasilId")->fetch_assoc()['dp'];
            $nilai = $totalPoin > 0 ? round(($dapatPoin / $totalPoin) * 100, 2) : 0;
            $conn->query("UPDATE pelatihan_hasil SET nilai=$nilai WHERE id=$hasilId");
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ─── AJAX: load jawaban peserta ───────────────────────────────────────────────
if (isset($_GET['ajax_jawaban'])) {
    $hasilId = (int)($_GET['hasil_id'] ?? 0);
    if (!$hasilId) { echo '{}'; exit; }

    $hasil = $conn->query("SELECT ph.*, p.nama, p.sobat_id, p.no_telp
        FROM pelatihan_hasil ph
        JOIN peserta p ON p.id = ph.peserta_id
        WHERE ph.id=$hasilId AND ph.pelatihan_id=$id")->fetch_assoc();
    if (!$hasil) { echo '{}'; exit; }

    $jawaban = $conn->query("SELECT j.*, s.pertanyaan, s.jenis, s.poin, s.nomor_urut,
        o.teks AS opsi_dipilih_teks, o.huruf AS opsi_dipilih_huruf,
        (SELECT teks FROM pelatihan_opsi WHERE soal_id=s.id AND is_benar=1 LIMIT 1) AS opsi_benar_teks,
        (SELECT huruf FROM pelatihan_opsi WHERE soal_id=s.id AND is_benar=1 LIMIT 1) AS opsi_benar_huruf
        FROM pelatihan_jawaban j
        JOIN pelatihan_soal s ON s.id = j.soal_id
        LEFT JOIN pelatihan_opsi o ON o.id = j.opsi_id
        WHERE j.hasil_id=$hasilId
        ORDER BY s.nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['hasil' => $hasil, 'jawaban' => $jawaban]);
    exit;
}

// ─── Stats ───────────────────────────────────────────────────────────────────
$totalSoal  = $conn->query("SELECT COUNT(*) AS c FROM pelatihan_soal WHERE pelatihan_id=$id")->fetch_assoc()['c'];
$totalPoin  = $conn->query("SELECT COALESCE(SUM(poin),0) AS s FROM pelatihan_soal WHERE pelatihan_id=$id")->fetch_assoc()['s'];

$statsHasil = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) AS selesai,
    ROUND(AVG(CASE WHEN status='selesai' THEN nilai END),1) AS rata_nilai,
    MAX(CASE WHEN status='selesai' THEN nilai END) AS max_nilai,
    MIN(CASE WHEN status='selesai' THEN nilai END) AS min_nilai
    FROM pelatihan_hasil WHERE pelatihan_id=$id")->fetch_assoc();

// ─── Semua peserta (Diterima) + status pengerjaan ────────────────────────────
$filter  = $_GET['filter'] ?? 'semua';
$search  = trim($_GET['q'] ?? '');
$where   = "p.status_seleksi = 'Diterima'";
if ($search) {
    $sq = $conn->real_escape_string($search);
    $where .= " AND (p.nama LIKE '%$sq%' OR p.sobat_id LIKE '%$sq%')";
}
if ($filter === 'selesai') $where .= " AND ph.status = 'selesai'";
elseif ($filter === 'belum') $where .= " AND ph.id IS NULL";
elseif ($filter === 'sedang') $where .= " AND ph.status = 'sedang'";

$pesertaList = $conn->query("
    SELECT p.id AS peserta_id, p.nama, p.sobat_id, p.alamat_kec, p.no_telp,
        ph.id AS hasil_id, ph.status AS status_pengerjaan,
        ph.waktu_mulai, ph.waktu_selesai, ph.nilai
    FROM peserta p
    LEFT JOIN pelatihan_hasil ph ON ph.peserta_id = p.id AND ph.pelatihan_id = $id
    WHERE $where
    ORDER BY ph.status DESC, ph.nilai DESC, p.nama ASC
")->fetch_all(MYSQLI_ASSOC);

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara', '030' => 'Dondo',
    '031' => 'Ogodeide', '032' => 'Basidondo', '040' => 'Baolan',
    '041' => 'Lampasio', '050' => 'Galang', '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$jenisLabel  = ['pretest' => 'Pretest', 'posttest' => 'Posttest', 'ujian' => 'Ujian'];
$statusBadge = ['draft' => 'bg-secondary', 'aktif' => 'bg-success', 'selesai' => 'bg-dark'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring — <?= htmlspecialchars($pelatihan['judul']) ?></title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .nilai-badge { font-size:.95rem; font-weight:700; min-width:52px; display:inline-block; text-align:center; border-radius:6px; padding:3px 8px; }
        .nilai-a  { background:#d1fae5; color:#065f46; }
        .nilai-b  { background:#dbeafe; color:#1e3a8a; }
        .nilai-c  { background:#fef9c3; color:#713f12; }
        .nilai-d  { background:#fee2e2; color:#7f1d1d; }
        .jawaban-pg-benar  { background:#d1fae5; border-left:3px solid #16a34a; border-radius:6px; padding:4px 10px; margin-bottom:3px; font-size:.85rem; }
        .jawaban-pg-salah  { background:#fee2e2; border-left:3px solid #dc2626; border-radius:6px; padding:4px 10px; margin-bottom:3px; font-size:.85rem; }
        .jawaban-uraian    { background:#f0f9ff; border-left:3px solid #0ea5e9; border-radius:6px; padding:6px 10px; margin-bottom:3px; font-size:.85rem; }
    </style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <a href="pelatihan.php" class="text-muted text-decoration-none me-1"><i class="bi bi-chevron-left"></i></a>
                <i class="bi bi-people-fill me-2" style="color:#f79039"></i>
                Monitoring — <?= htmlspecialchars($pelatihan['judul']) ?>
            </div>
            <div class="topbar-sub">
                <span class="badge <?= $statusBadge[$pelatihan['status']] ?> me-1"><?= $pelatihan['status'] ?></span>
                <?= $jenisLabel[$pelatihan['jenis']] ?> · <?= $totalSoal ?> soal · <?= $totalPoin ?> poin
            </div>
        </div>
        <div class="topbar-right">
            <a href="pelatihan_detail.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul me-1"></i>Kelola Soal
            </a>
        </div>
    </header>

    <div class="page-body">
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3"><?= $statsHasil['selesai'] ?? 0 ?></div>
                    <div class="text-muted small">Sudah Mengerjakan</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-warning"><?= $statsHasil['rata_nilai'] ?? '—' ?></div>
                    <div class="text-muted small">Rata-rata Nilai</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-success"><?= $statsHasil['max_nilai'] ?? '—' ?></div>
                    <div class="text-muted small">Nilai Tertinggi</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-danger"><?= $statsHasil['min_nilai'] ?? '—' ?></div>
                    <div class="text-muted small">Nilai Terendah</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card stat-card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="text" name="q" class="form-control form-control-sm" style="max-width:200px"
                        placeholder="Cari nama / sobat ID..." value="<?= htmlspecialchars($search) ?>">
                    <div class="btn-group btn-group-sm">
                        <?php foreach (['semua' => 'Semua', 'selesai' => 'Selesai', 'sedang' => 'Sedang', 'belum' => 'Belum Mulai'] as $k => $v): ?>
                        <a href="?id=<?= $id ?>&filter=<?= $k ?>&q=<?= urlencode($search) ?>"
                           class="btn btn-outline-secondary <?= $filter === $k ? 'active' : '' ?>"><?= $v ?></a>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card stat-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Peserta</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Waktu Selesai</th>
                                <th class="text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($pesertaList)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data peserta ditemukan.</td></tr>
                        <?php else: foreach ($pesertaList as $i => $p): ?>
                            <?php
                                $statusP = $p['status_pengerjaan'] ?? null;
                                if ($statusP === 'selesai') { $stBadge = 'bg-success'; $stLabel = 'Selesai'; }
                                elseif ($statusP === 'sedang') { $stBadge = 'bg-warning text-dark'; $stLabel = 'Sedang'; }
                                else { $stBadge = 'bg-secondary'; $stLabel = 'Belum'; }

                                $nilai = $p['nilai'];
                                if ($nilai === null) { $nilaiCls = ''; $nilaiStr = '—'; }
                                elseif ($nilai >= 80) { $nilaiCls = 'nilai-a'; $nilaiStr = number_format($nilai,1); }
                                elseif ($nilai >= 70) { $nilaiCls = 'nilai-b'; $nilaiStr = number_format($nilai,1); }
                                elseif ($nilai >= 60) { $nilaiCls = 'nilai-c'; $nilaiStr = number_format($nilai,1); }
                                else { $nilaiCls = 'nilai-d'; $nilaiStr = number_format($nilai,1); }
                            ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= $i+1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                                    <div class="text-muted small">ID: <?= htmlspecialchars($p['sobat_id'] ?? $p['peserta_id']) ?> · <?= htmlspecialchars($p['no_telp'] ?? '') ?></div>
                                </td>
                                <td class="small text-muted"><?= $kecNama[$p['alamat_kec']] ?? $p['alamat_kec'] ?></td>
                                <td class="text-center"><span class="badge <?= $stBadge ?>"><?= $stLabel ?></span></td>
                                <td class="text-center">
                                    <?php if ($nilai !== null): ?>
                                    <span class="nilai-badge <?= $nilaiCls ?>"><?= $nilaiStr ?></span>
                                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                </td>
                                <td class="text-center small text-muted">
                                    <?= $p['waktu_selesai'] ? date('d/m/Y H:i', strtotime($p['waktu_selesai'])) : '—' ?>
                                </td>
                                <td class="text-center pe-3">
                                    <?php if ($p['hasil_id']): ?>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="lihatJawaban(<?= $p['hasil_id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')">
                                        <i class="bi bi-eye me-1"></i>Jawaban
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Modal Lihat Jawaban ───────────────────────────────────────────────── -->
<div class="modal fade" id="modalJawaban" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journal-text me-2 text-primary"></i>Jawaban: <span id="jawabanNama"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jawabanBody">
                <div class="text-center py-4"><div class="spinner-border text-warning"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
let currentHasilId = null;

function lihatJawaban(hasilId, nama) {
    currentHasilId = hasilId;
    document.getElementById('jawabanNama').textContent = nama;
    document.getElementById('jawabanBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-warning"></div></div>';
    new bootstrap.Modal(document.getElementById('modalJawaban')).show();

    fetch('pelatihan_monitoring.php?id=<?= $id ?>&ajax_jawaban=1&hasil_id=' + hasilId)
        .then(r => r.json())
        .then(data => {
            if (!data.hasil) {
                document.getElementById('jawabanBody').innerHTML = '<p class="text-danger">Data tidak ditemukan.</p>';
                return;
            }
            renderJawaban(data);
        });
}

function renderJawaban(data) {
    const h = data.hasil;
    const jawaban = data.jawaban;
    let html = '';

    // Info header
    html += `<div class="alert alert-light py-2 mb-3 d-flex gap-4 flex-wrap small">
        <span><i class="bi bi-clock me-1"></i>Mulai: <strong>${h.waktu_mulai || '—'}</strong></span>
        <span><i class="bi bi-check-circle me-1"></i>Selesai: <strong>${h.waktu_selesai || '—'}</strong></span>
        <span><i class="bi bi-star me-1"></i>Nilai: <strong class="text-warning fs-6">${h.nilai !== null ? parseFloat(h.nilai).toFixed(1) : '—'}</strong></span>
    </div>`;

    if (!jawaban.length) {
        html += '<p class="text-muted">Belum ada jawaban tercatat.</p>';
    } else {
        jawaban.forEach((j, i) => {
            html += `<div class="mb-3 p-3 border rounded">
                <div class="fw-semibold mb-1"><span class="badge bg-warning text-dark me-2">No. ${j.nomor_urut}</span>${escHtml(j.pertanyaan)}</div>`;

            if (j.jenis === 'pilihan_ganda') {
                const cls = parseInt(j.is_benar) === 1 ? 'jawaban-pg-benar' : 'jawaban-pg-salah';
                const icon = parseInt(j.is_benar) === 1 ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' : '<i class="bi bi-x-circle-fill text-danger me-1"></i>';
                html += `<div class="${cls}">${icon}Dijawab: <strong>${j.opsi_dipilih_huruf || '—'}. ${escHtml(j.opsi_dipilih_teks || '(tidak menjawab)')}</strong></div>`;
                if (parseInt(j.is_benar) !== 1) {
                    html += `<div class="text-muted small mt-1"><i class="bi bi-check2 text-success me-1"></i>Jawaban benar: <strong>${j.opsi_benar_huruf || '—'}. ${escHtml(j.opsi_benar_teks || '')}</strong></div>`;
                }
                html += `<div class="text-muted small mt-1">Poin didapat: ${parseFloat(j.poin_dapat).toFixed(0)} / ${j.poin}</div>`;
            } else {
                // Uraian
                html += `<div class="jawaban-uraian"><i class="bi bi-pencil me-1"></i>${escHtml(j.jawaban_teks || '(tidak menjawab)')}</div>`;
                html += `<div class="mt-2 d-flex align-items-center gap-2">
                    <span class="text-muted small">Beri nilai (maks ${j.poin}):</span>
                    <input type="number" class="form-control form-control-sm" style="width:80px"
                        min="0" max="${j.poin}" step="0.5" value="${parseFloat(j.poin_dapat).toFixed(1)}"
                        id="poin_uraian_${j.id}">
                    <button class="btn btn-sm btn-outline-success" onclick="simpanNilaiUraian(${j.id}, ${h.id}, ${j.poin})">
                        <i class="bi bi-check2"></i> Simpan
                    </button>
                    <span class="text-muted small" id="msg_uraian_${j.id}"></span>
                </div>`;
            }

            html += '</div>';
        });
    }

    document.getElementById('jawabanBody').innerHTML = html;
}

function simpanNilaiUraian(jawabanId, hasilId, maxPoin) {
    const inp = document.getElementById('poin_uraian_' + jawabanId);
    const poin = Math.min(maxPoin, Math.max(0, parseFloat(inp.value) || 0));
    inp.value = poin;

    const fd = new FormData();
    fd.append('action', 'beri_nilai');
    fd.append('jawaban_id', jawabanId);
    fd.append('hasil_id', hasilId);
    fd.append('poin_dapat', poin);

    fetch('pelatihan_monitoring.php?id=<?= $id ?>', { method:'POST', body: fd })
        .then(r => r.json())
        .then(() => {
            const msg = document.getElementById('msg_uraian_' + jawabanId);
            msg.textContent = '✓ Tersimpan';
            msg.style.color = 'green';
            setTimeout(() => { msg.textContent = ''; }, 2000);
        });
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
