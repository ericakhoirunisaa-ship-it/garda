<?php
session_start();
require_once __DIR__ . '/../config.php';

$step = 'login'; // login | daftar_ujian | kerjakan | selesai

// ─── Tentukan step ────────────────────────────────────────────────────────────
if (isset($_SESSION['ujian_peserta_id'])) {
    $pesertaId = (int)$_SESSION['ujian_peserta_id'];
    if (isset($_SESSION['ujian_hasil_id'])) {
        $hasilId = (int)$_SESSION['ujian_hasil_id'];
        $hasil = $conn->query("SELECT * FROM pelatihan_hasil WHERE id=$hasilId")->fetch_assoc();
        if ($hasil && $hasil['status'] === 'selesai') {
            $step = 'selesai';
        } else {
            $step = 'kerjakan';
        }
    } else {
        $step = 'daftar_ujian';
    }
}

$error = '';
$peserta = null;
$pelatihan = null;
$soalList = [];

// ─── POST: Login peserta ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $pesertaInput = trim($_POST['peserta_id'] ?? '');
    if ($pesertaInput) {
        // Cari berdasarkan sobat_id atau id
        $esc = $conn->real_escape_string($pesertaInput);
        $p = $conn->query("SELECT * FROM peserta WHERE (sobat_id='$esc' OR id='$esc') AND status_seleksi='Diterima' LIMIT 1")->fetch_assoc();
        if ($p) {
            $_SESSION['ujian_peserta_id'] = $p['id'];
            unset($_SESSION['ujian_hasil_id']);
            header('Location: ujian.php');
            exit;
        } else {
            $error = "ID peserta tidak ditemukan atau Anda belum diterima sebagai peserta.";
        }
    } else {
        $error = "Masukkan ID peserta Anda.";
    }
}

// ─── POST: Pilih ujian ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pilih_ujian' && isset($_SESSION['ujian_peserta_id'])) {
    $pelId = (int)($_POST['pelatihan_id'] ?? 0);
    $pid   = (int)$_SESSION['ujian_peserta_id'];
    if ($pelId) {
        $pel = $conn->query("SELECT * FROM pelatihan WHERE id=$pelId AND status='aktif'")->fetch_assoc();
        if ($pel) {
            // Cek apakah sudah ada hasil
            $existing = $conn->query("SELECT * FROM pelatihan_hasil WHERE pelatihan_id=$pelId AND peserta_id=$pid")->fetch_assoc();
            if ($existing && $existing['status'] === 'selesai') {
                $error = "Anda sudah menyelesaikan ujian ini sebelumnya.";
                $step = 'daftar_ujian';
            } else {
                if (!$existing) {
                    $now = date('Y-m-d H:i:s');
                    $conn->query("INSERT INTO pelatihan_hasil (pelatihan_id, peserta_id, waktu_mulai, status) VALUES ($pelId, $pid, '$now', 'sedang')");
                    $_SESSION['ujian_hasil_id'] = $conn->insert_id;
                } else {
                    $_SESSION['ujian_hasil_id'] = $existing['id'];
                }
                header('Location: ujian.php');
                exit;
            }
        } else {
            $error = "Ujian tidak tersedia.";
        }
    }
}

// ─── POST: Submit jawaban ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_ujian' && isset($_SESSION['ujian_hasil_id'])) {
    $hasilId = (int)$_SESSION['ujian_hasil_id'];
    $hasil   = $conn->query("SELECT * FROM pelatihan_hasil WHERE id=$hasilId")->fetch_assoc();

    if ($hasil && $hasil['status'] === 'sedang') {
        $pelId = $hasil['pelatihan_id'];
        $soalAll = $conn->query("SELECT * FROM pelatihan_soal WHERE pelatihan_id=$pelId ORDER BY nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);

        $totalPoin = 0;
        $dapatPoin = 0;

        // Hapus jawaban lama jika ada
        $conn->query("DELETE FROM pelatihan_jawaban WHERE hasil_id=$hasilId");

        foreach ($soalAll as $soal) {
            $sid = (int)$soal['id'];
            $totalPoin += (int)$soal['poin'];

            if ($soal['jenis'] === 'pilihan_ganda') {
                $opsiId = (int)($_POST["jawab_$sid"] ?? 0);
                $isBenar = 0;
                $poinDapat = 0;
                if ($opsiId) {
                    $opsi = $conn->query("SELECT * FROM pelatihan_opsi WHERE id=$opsiId AND soal_id=$sid")->fetch_assoc();
                    if ($opsi && $opsi['is_benar']) {
                        $isBenar = 1;
                        $poinDapat = $soal['poin'];
                        $dapatPoin += $poinDapat;
                    }
                }
                $opsiIdVal = $opsiId ?: 'NULL';
                $conn->query("INSERT INTO pelatihan_jawaban (hasil_id, soal_id, opsi_id, is_benar, poin_dapat) VALUES ($hasilId, $sid, $opsiIdVal, $isBenar, $poinDapat)");
            } else {
                // Uraian
                $jawaban = $conn->real_escape_string(trim($_POST["jawab_$sid"] ?? ''));
                $conn->query("INSERT INTO pelatihan_jawaban (hasil_id, soal_id, jawaban_teks, is_benar, poin_dapat) VALUES ($hasilId, $sid, '$jawaban', NULL, 0)");
            }
        }

        $nilai = $totalPoin > 0 ? round(($dapatPoin / $totalPoin) * 100, 2) : 0;
        $selesai = date('Y-m-d H:i:s');
        $conn->query("UPDATE pelatihan_hasil SET status='selesai', nilai=$nilai, waktu_selesai='$selesai' WHERE id=$hasilId");

        $step = 'selesai';
        unset($_SESSION['ujian_hasil_id']);
    }
}

// ─── Logout peserta ───────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    unset($_SESSION['ujian_peserta_id'], $_SESSION['ujian_hasil_id']);
    header('Location: ujian.php');
    exit;
}

// ─── Load data berdasarkan step ───────────────────────────────────────────────
if ($step === 'daftar_ujian' || $step === 'kerjakan') {
    $pid = (int)$_SESSION['ujian_peserta_id'];
    $peserta = $conn->query("SELECT * FROM peserta WHERE id=$pid")->fetch_assoc();
}

if ($step === 'kerjakan') {
    $hasilId = (int)$_SESSION['ujian_hasil_id'];
    $hasil   = $conn->query("SELECT ph.*, pel.judul, pel.durasi_menit, pel.jenis, pel.deskripsi
        FROM pelatihan_hasil ph JOIN pelatihan pel ON pel.id = ph.pelatihan_id
        WHERE ph.id=$hasilId")->fetch_assoc();
    $pelatihan = $hasil;

    if ($pelatihan) {
        $pelId = $hasil['pelatihan_id'];
        $soalList = $conn->query("SELECT s.*, '' AS jawaban_awal
            FROM pelatihan_soal s WHERE s.pelatihan_id=$pelId ORDER BY s.nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);
        foreach ($soalList as &$soal) {
            if ($soal['jenis'] === 'pilihan_ganda') {
                $soal['opsi'] = $conn->query("SELECT * FROM pelatihan_opsi WHERE soal_id=" . (int)$soal['id'] . " ORDER BY huruf ASC")->fetch_all(MYSQLI_ASSOC);
            }
        }
        unset($soal);

        // Hitung sisa waktu
        $mulai = strtotime($hasil['waktu_mulai']);
        $durasi = (int)$hasil['durasi_menit'] * 60;
        $elapsed = time() - $mulai;
        $sisaDetik = max(0, $durasi - $elapsed);

        // Jika waktu habis, biarkan JS yang auto-submit (sisaDetik=0 akan trigger submit)
        $sisaDetik = max(0, $sisaDetik);
    }
}

if ($step === 'daftar_ujian') {
    $pid = (int)$_SESSION['ujian_peserta_id'];
    $pelList = $conn->query("SELECT p.*,
        COALESCE((SELECT COUNT(*) FROM pelatihan_soal WHERE pelatihan_id=p.id),0) AS jumlah_soal,
        (SELECT ph.status FROM pelatihan_hasil ph WHERE ph.pelatihan_id=p.id AND ph.peserta_id=$pid LIMIT 1) AS status_saya
        FROM pelatihan p WHERE p.status='aktif' ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$jenisLabel = ['pretest' => 'Pretest', 'posttest' => 'Posttest', 'ujian' => 'Ujian'];
$jenisBadge = ['pretest' => '#0ea5e9', 'posttest' => '#16a34a', 'ujian' => '#f59e0b'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Petugas — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root { --accent:#f79039; }
        body { background:#f0f2f5; font-family:'Roboto',system-ui,sans-serif; min-height:100vh; }

        /* Header */
        .ujian-header {
            background:linear-gradient(135deg,#f79039,#e06820);
            color:#fff; padding:16px 24px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 2px 8px rgba(0,0,0,.15);
        }
        .ujian-header .brand { font-size:1.05rem; font-weight:800; }
        .ujian-header .sub   { font-size:.75rem; opacity:.8; }

        /* Cards */
        .card { border:none; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.07); }
        .login-card { max-width:420px; margin:60px auto; }

        /* Soal */
        .soal-card { border-left:4px solid var(--accent); margin-bottom:16px; }
        .opsi-label { display:flex; align-items:flex-start; gap:10px; padding:10px 14px;
            border:2px solid #e9ecef; border-radius:8px; cursor:pointer; margin-bottom:6px;
            transition:all .15s; }
        .opsi-label:hover { border-color:var(--accent); background:#fff7ed; }
        .opsi-label input[type=radio]:checked + .opsi-content { color:#92400e; }
        .opsi-label:has(input:checked) { border-color:var(--accent); background:#fff7ed; }
        .opsi-huruf { font-weight:700; color:var(--accent); min-width:22px; }

        /* Timer */
        .timer-bar { background:#fff; border-radius:12px; padding:10px 20px;
            display:flex; align-items:center; gap:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .timer-display { font-size:1.4rem; font-weight:800; font-variant-numeric:tabular-nums;
            color:#112344; min-width:80px; }
        .timer-display.warning { color:#dc2626; animation:pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

        .btn-submit { background:var(--accent); border:none; color:#fff; font-weight:700;
            padding:10px 32px; border-radius:8px; font-size:1rem; }
        .btn-submit:hover { background:#e06820; color:#fff; }
    </style>
</head>
<body>

<!-- Header -->
<div class="ujian-header">
    <div>
        <div class="brand"><i class="bi bi-journal-check me-2"></i>Ujian Petugas — SEMANIS 2026</div>
        <div class="sub">BPS Kabupaten Toli-Toli</div>
    </div>
    <?php if (isset($_SESSION['ujian_peserta_id'])): ?>
    <div class="d-flex align-items-center gap-3">
        <?php if ($peserta): ?>
        <span class="small opacity-75"><i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($peserta['nama']) ?></span>
        <?php endif; ?>
        <a href="ujian.php?logout=1" class="btn btn-sm btn-light text-dark"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($step === 'login'): ?>
<!-- ─────────────────────────────────────────────────────────────── LOGIN ─── -->
<div class="container">
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <div style="width:60px;height:60px;background:#fff7ed;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="bi bi-person-badge fs-3 text-warning"></i>
            </div>
            <h5 class="fw-bold">Masuk sebagai Peserta</h5>
            <p class="text-muted small">Masukkan ID Peserta atau Sobat ID yang diberikan oleh petugas.</p>
        </div>
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label class="form-label fw-semibold">ID Peserta / Sobat ID</label>
                <input type="text" name="peserta_id" class="form-control form-control-lg text-center fw-bold"
                    placeholder="Masukkan ID Anda..." autofocus autocomplete="off">
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                <i class="bi bi-arrow-right-circle me-2"></i>Masuk
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="../index.html" class="text-muted small"><i class="bi bi-house me-1"></i>Kembali ke Beranda</a>
        </div>
    </div>
</div>

<?php elseif ($step === 'daftar_ujian'): ?>
<!-- ──────────────────────────────────────────────────────── DAFTAR UJIAN ─── -->
<div class="container py-4" style="max-width:700px">
    <div class="card p-4 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:#fff7ed;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-check-fill fs-4 text-warning"></i>
            </div>
            <div>
                <div class="fw-bold fs-6"><?= htmlspecialchars($peserta['nama']) ?></div>
                <div class="text-muted small">ID: <?= htmlspecialchars($peserta['sobat_id'] ?? $peserta['id']) ?></div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h6 class="fw-bold mb-3">Ujian / Pelatihan Tersedia</h6>

    <?php if (empty($pelList)): ?>
    <div class="card p-4 text-center text-muted">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        Belum ada ujian yang aktif saat ini. Silakan hubungi petugas.
    </div>
    <?php else: foreach ($pelList as $pel): ?>
    <div class="card mb-3 p-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="badge mb-1" style="background:<?= $jenisBadge[$pel['jenis']] ?>">
                    <?= $jenisLabel[$pel['jenis']] ?>
                </span>
                <div class="fw-bold"><?= htmlspecialchars($pel['judul']) ?></div>
                <?php if ($pel['deskripsi']): ?>
                <div class="text-muted small"><?= htmlspecialchars($pel['deskripsi']) ?></div>
                <?php endif; ?>
                <div class="text-muted small mt-1">
                    <i class="bi bi-list-ul me-1"></i><?= $pel['jumlah_soal'] ?> soal
                    <i class="bi bi-clock ms-2 me-1"></i><?= $pel['durasi_menit'] ?> menit
                </div>
            </div>
            <div>
                <?php if ($pel['status_saya'] === 'selesai'): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                <?php elseif ($pel['status_saya'] === 'sedang'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="pilih_ujian">
                    <input type="hidden" name="pelatihan_id" value="<?= $pel['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning fw-bold">
                        <i class="bi bi-play-fill me-1"></i>Lanjutkan
                    </button>
                </form>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="pilih_ujian">
                    <input type="hidden" name="pelatihan_id" value="<?= $pel['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning fw-bold"
                        onclick="return confirm('Mulai ujian \'<?= htmlspecialchars(addslashes($pel['judul'])) ?>\'?\nDurasi: <?= $pel['durasi_menit'] ?> menit.\n\nPastikan Anda siap sebelum memulai.')">
                        <i class="bi bi-play-fill me-1"></i>Mulai
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<?php elseif ($step === 'kerjakan' && $pelatihan): ?>
<!-- ──────────────────────────────────────────────────────── KERJAKAN ─── -->
<div class="container py-4" style="max-width:800px">
    <!-- Timer sticky bar -->
    <div class="timer-bar mb-4 sticky-top" style="top:0; z-index:100;">
        <div>
            <div class="fw-bold"><?= htmlspecialchars($pelatihan['judul']) ?></div>
            <div class="text-muted small"><?= count($soalList) ?> soal</div>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div>
                <div class="text-muted small">Sisa Waktu</div>
                <div class="timer-display" id="timer">--:--</div>
            </div>
            <button type="button" class="btn-submit" onclick="submitUjian()">
                <i class="bi bi-check-lg me-1"></i>Kumpulkan
            </button>
        </div>
    </div>

    <?php if (isset($_GET['timeout'])): ?>
    <div class="alert alert-warning"><i class="bi bi-clock-history me-2"></i>Waktu habis! Jawaban Anda akan dikumpulkan otomatis.</div>
    <?php endif; ?>

    <form method="POST" id="formUjian">
        <input type="hidden" name="action" value="submit_ujian">

        <?php foreach ($soalList as $soal): ?>
        <div class="card soal-card p-3">
            <div class="d-flex gap-2 mb-2">
                <span class="badge bg-warning text-dark fw-bold">No. <?= $soal['nomor_urut'] ?></span>
                <span class="badge bg-light text-dark border small">
                    <?= $soal['jenis'] === 'pilihan_ganda' ? 'Pilihan Ganda' : 'Uraian' ?>
                </span>
                <span class="text-muted small"><?= $soal['poin'] ?> poin</span>
            </div>
            <p class="fw-semibold mb-3" style="white-space:pre-wrap"><?= htmlspecialchars($soal['pertanyaan']) ?></p>

            <?php if ($soal['jenis'] === 'pilihan_ganda'): ?>
            <div>
                <?php foreach ($soal['opsi'] as $opsi): ?>
                <label class="opsi-label w-100">
                    <input type="radio" name="jawab_<?= $soal['id'] ?>" value="<?= $opsi['id'] ?>" style="margin-top:2px">
                    <div class="d-flex gap-2">
                        <span class="opsi-huruf"><?= $opsi['huruf'] ?>.</span>
                        <span class="opsi-content"><?= htmlspecialchars($opsi['teks']) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <textarea name="jawab_<?= $soal['id'] ?>" class="form-control"
                rows="4" placeholder="Tuliskan jawaban Anda di sini..."></textarea>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="text-center mt-4 mb-5">
            <button type="button" class="btn-submit btn btn-lg" onclick="submitUjian()">
                <i class="bi bi-send-check me-2"></i>Kumpulkan Jawaban
            </button>
        </div>
    </form>
</div>

<script>
const sisaDetik = <?= $sisaDetik ?? 0 ?>;
let detik = sisaDetik;

function updateTimer() {
    const m = Math.floor(detik / 60).toString().padStart(2,'0');
    const s = (detik % 60).toString().padStart(2,'0');
    const el = document.getElementById('timer');
    el.textContent = m + ':' + s;
    if (detik <= 120) el.classList.add('warning');
    if (detik <= 0) {
        el.textContent = '00:00';
        document.getElementById('formUjian').submit();
    } else {
        detik--;
        setTimeout(updateTimer, 1000);
    }
}
updateTimer();

function submitUjian() {
    if (!confirm('Kumpulkan jawaban sekarang?\n\nPastikan semua soal sudah dijawab.')) return;
    document.getElementById('formUjian').submit();
}
</script>

<?php elseif ($step === 'selesai'): ?>
<!-- ──────────────────────────────────────────────────────── SELESAI ─── -->
<div class="container py-5" style="max-width:480px">
    <div class="card p-5 text-center">
        <div style="width:80px;height:80px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="bi bi-check-circle-fill fs-2 text-success"></i>
        </div>
        <h4 class="fw-bold mb-2">Ujian Selesai!</h4>
        <p class="text-muted mb-4">Jawaban Anda telah berhasil dikumpulkan. Terima kasih telah mengikuti ujian pelatihan.</p>
        <?php
        // Ambil nilai jika ada
        $pid = (int)($_SESSION['ujian_peserta_id'] ?? 0);
        $lastHasil = $conn->query("SELECT ph.*, pel.judul FROM pelatihan_hasil ph
            JOIN pelatihan pel ON pel.id = ph.pelatihan_id
            WHERE ph.peserta_id=$pid AND ph.status='selesai'
            ORDER BY ph.waktu_selesai DESC LIMIT 1")->fetch_assoc();
        if ($lastHasil && $lastHasil['nilai'] !== null):
        ?>
        <div class="bg-warning bg-opacity-10 border border-warning rounded p-3 mb-3">
            <div class="text-muted small mb-1"><?= htmlspecialchars($lastHasil['judul']) ?></div>
            <div class="fw-bold fs-2"><?= number_format($lastHasil['nilai'], 1) ?></div>
            <div class="text-muted small">dari 100</div>
        </div>
        <?php endif; ?>
        <a href="ujian.php" class="btn btn-warning fw-bold">
            <i class="bi bi-arrow-repeat me-2"></i>Kembali ke Daftar Ujian
        </a>
        <a href="../index.html" class="btn btn-outline-secondary mt-2">
            <i class="bi bi-house me-2"></i>Kembali ke Beranda
        </a>
    </div>
</div>

<?php endif; ?>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
