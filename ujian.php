<?php
require_once __DIR__ . '/config.php';

$kecMap = [
    "010" => "Dampal Selatan",
    "020" => "Dampal Utara",
    "030" => "Dondo",
    "031" => "Ogodeide",
    "032" => "Basidondo",
    "040" => "Baolan",
    "041" => "Lampasio",
    "050" => "Galang",
    "060" => "Tolitoli Utara",
    "061" => "Dako Pemean",
];

// ─── Mitra list: dari tabel ujian_mitra, fallback ke afirmasi.csv ────────────
$mitraData = [];
$dbMitra = $conn->query("SELECT nama, kecamatan, kelas, peran FROM ujian_mitra ORDER BY kelas ASC, kecamatan ASC, nama ASC");
if ($dbMitra && $dbMitra->num_rows > 0) {
    while ($_row = $dbMitra->fetch_assoc()) {
        $mitraData[] = [
            'nama'      => $_row['nama'],
            'kecamatan' => $_row['kecamatan'] ?? '',
            'kelas'     => $_row['kelas'] ?? '',
            'peran'     => $_row['peran'] ?? '',
        ];
    }
} elseif (($_fh = @fopen(__DIR__ . '/afirmasi.csv', 'r')) !== false) {
    fgetcsv($_fh);
    while (($_row = fgetcsv($_fh)) !== false) {
        $_nama = trim($_row[0] ?? '');
        if (!$_nama) continue;
        preg_match('/\((\d+)\)/', $_row[6] ?? '', $_m);
        $mitraData[] = ['nama' => $_nama, 'kecamatan' => $_m[1] ?? '', 'kelas' => '', 'peran' => ''];
    }
    fclose($_fh);
    usort($mitraData, fn($a, $b) => strcasecmp($a['nama'], $b['nama']));
}

// ─── Determine step ───────────────────────────────────────────────────────────
$ujianId   = (int)($_GET['id'] ?? 0);
$doneId    = (int)($_GET['done'] ?? 0);
$error     = '';

// Step: selesai — thank you page after redirect-after-POST
if ($doneId > 0) {
    $result = $conn->query("
        SELECT p.*, u.judul AS judul_ujian
        FROM ujian_pengerjaan p
        LEFT JOIN ujian u ON u.id = p.ujian_id
        WHERE p.id=$doneId LIMIT 1
    ")->fetch_assoc();
    $step = $result ? 'selesai' : 'pilih';

// Step: kerjakan — an exam is selected (GET ?id=X or POST with error)
} elseif ($ujianId > 0 || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ujian_id']))) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ujianId = (int)($_POST['ujian_id'] ?? 0);
    }
    $ujianRow = $ujianId ? $conn->query("SELECT * FROM ujian WHERE id=$ujianId AND status='aktif' LIMIT 1")->fetch_assoc() : null;
    if ($ujianRow) {
        // POST = submit form (validation error keeps step kerjakan); GET = always intro
        $step = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'kerjakan' : 'intro';
        $soalList = $conn->query("SELECT * FROM ujian_soal WHERE ujian_id=$ujianId ORDER BY nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);
        foreach ($soalList as &$soal) {
            $sid = (int)$soal['id'];
            $soal['opsi'] = $conn->query("SELECT * FROM ujian_opsi WHERE soal_id=$sid ORDER BY huruf ASC")->fetch_all(MYSQLI_ASSOC);
        }
        unset($soal);
    } else {
        $step = 'pilih';
    }

// Step: pilih — default, show list of active exams
} else {
    $step = 'pilih';
}

// ─── Process POST submit ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'kerjakan') {
    $nama  = trim($_POST['nama'] ?? '');
    $nik   = trim($_POST['nik'] ?? '');
    $kec   = trim($_POST['kecamatan'] ?? '');
    $kelas = strtoupper(trim($_POST['kelas'] ?? ''));
    $peran = trim($_POST['peran'] ?? '');

    if (!in_array($kelas, ['A','B','C','D','E','F','G','H','I'])) {
        $error = "Pilih kelas yang valid.";
    } elseif (!isset($kecMap[$kec])) {
        $error = "Pilih kecamatan yang valid.";
    } elseif (!$nama) {
        $error = "Nama wajib diisi.";
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $error = "NIK harus 16 digit angka.";
    } elseif (!in_array($peran, ['PPL Sensus', 'PML Sensus'])) {
        $error = "Pilih peran yang valid.";
    } elseif (empty($soalList)) {
        $error = "Soal ujian belum tersedia. Hubungi panitia.";
    } else {
        $n   = $conn->real_escape_string($nama);
        $ni  = $conn->real_escape_string($nik);
        $k   = $conn->real_escape_string($kec);
        $kl  = $conn->real_escape_string($kelas);
        $pr  = $conn->real_escape_string($peran);
        if (!$error) {
        $now = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO ujian_pengerjaan (ujian_id, nama, nik, kecamatan, kelas, peran, nilai, total_soal, total_benar, waktu) VALUES ($ujianId,'$n','$ni','$k','$kl','$pr',0,0,0,'$now')");
        $pengerjaanId = $conn->insert_id;

        $totalSoal  = count($soalList);
        $totalBenar = 0;

        foreach ($soalList as $soal) {
            $soalId  = (int)$soal['id'];
            $opsiId  = (int)($_POST['jawab_' . $soalId] ?? 0);
            $isBenar = 0;
            if ($opsiId) {
                $opsiRow = $conn->query("SELECT is_benar FROM ujian_opsi WHERE id=$opsiId AND soal_id=$soalId LIMIT 1")->fetch_assoc();
                $isBenar = ($opsiRow && $opsiRow['is_benar']) ? 1 : 0;
                if ($isBenar) $totalBenar++;
            }
            $opsiParam = $opsiId ?: 'NULL';
            $conn->query("INSERT INTO ujian_jawaban (pengerjaan_id, soal_id, opsi_id, is_benar) VALUES ($pengerjaanId, $soalId, $opsiParam, $isBenar)");
        }

        $nilai = $totalSoal > 0 ? round(($totalBenar / $totalSoal) * 100, 2) : 0;
        $conn->query("UPDATE ujian_pengerjaan SET nilai=$nilai, total_soal=$totalSoal, total_benar=$totalBenar WHERE id=$pengerjaanId");

        header('Location: ujian.php?done=' . $pengerjaanId);
        exit;
        } // end if (!$error)
    }
}

// ─── Fetch active exam list (for step=pilih) ──────────────────────────────────
if ($step === 'pilih') {
    $ujianAktif = $conn->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM ujian_soal WHERE ujian_id=u.id) AS jml_soal
        FROM ujian u
        WHERE u.status = 'aktif'
        ORDER BY u.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = match($step) {
    'selesai'           => 'Ujian Selesai',
    'intro', 'kerjakan' => htmlspecialchars($ujianRow['judul']),
    default             => 'Ujian Petugas',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Ujian — SEMANIS 2026</title>
  <link href="assets/img/logo.png" rel="icon">
  <link href="assets/img/logo.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    .ujian-section { padding: 60px 0; }

    /* Exam selection cards */
    .ujian-card {
      border: 2px solid #e9ecef; border-radius: 16px;
      transition: border-color .2s, box-shadow .2s, transform .15s;
      cursor: pointer; text-decoration: none; color: inherit; display: block;
    }
    .ujian-card:hover {
      border-color: #f79039; box-shadow: 0 6px 24px rgba(247,144,57,.18);
      transform: translateY(-2px); color: inherit;
    }
    .ujian-icon {
      width: 56px; height: 56px; border-radius: 14px;
      background: #fff3e6; display: flex; align-items: center; justify-content: center;
      margin-bottom: 14px; font-size: 1.6rem; color: #f79039;
    }

    /* Question styles */
    .soal-card { border-left: 4px solid #f79039; transition: box-shadow .2s; }
    .soal-card:hover { box-shadow: 0 4px 20px rgba(247,144,57,.15); }
    .opsi-wrap { margin-bottom: 8px; }
    .opsi-wrap input[type="radio"] { display: none; }
    .opsi-label {
      cursor: pointer; border-radius: 10px; padding: 11px 16px;
      border: 1.5px solid #dee2e6; transition: all .15s;
      display: flex; align-items: center; gap: 12px; margin: 0;
    }
    .opsi-label:hover { border-color: #f79039; background: #fff8f3; }
    .opsi-wrap input[type="radio"]:checked + .opsi-label {
      border-color: #f79039; background: #fff3e6; font-weight: 600;
    }
    .opsi-huruf {
      width: 30px; height: 30px; min-width: 30px; border-radius: 50%;
      background: #f0f2f5; color: #555; border: 1.5px solid #dee2e6;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: .82rem; transition: all .15s;
    }
    .opsi-wrap input[type="radio"]:checked + .opsi-label .opsi-huruf {
      background: #f79039; color: #fff; border-color: #f79039;
    }

    /* Thank-you */
    .thankyou-icon { width: 90px; height: 90px; border-radius: 50%; background: #fff3e6; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .thankyou-icon i { font-size: 2.8rem; color: #f79039; }

    /* Timer */
    .timer-bar {
      position: sticky; top: 72px; z-index: 90;
      display: flex; align-items: center; justify-content: center; gap: 12px;
      padding: 10px 20px; border-radius: 12px; margin-bottom: 20px;
      font-weight: 700; font-size: 1.05rem;
      transition: background .3s, color .3s;
    }
    .timer-bar.ok      { background: #fff3e6; color: #c05300; border: 1.5px solid #f79039; }
    .timer-bar.warn    { background: #fef9c3; color: #854d0e; border: 1.5px solid #eab308; }
    .timer-bar.danger  { background: #fee2e2; color: #991b1b; border: 1.5px solid #ef4444; }
    .timer-bar.danger  { animation: pulse .7s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
  </style>
</head>
<body class="ujian-page">

  <header id="header" class="header fixed-top">
    <div class="branding d-flex align-items-cente">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.html" class="logo d-flex align-items-center">
          <h1 class="sitename">SEMANIS 2026</h1>
        </a>
        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index.html">Beranda</a></li>
            <li class="dropdown"><a href="penjelasan.html"><span>Penjelasan Umum</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
              <ul>
                <li><a href="penjelasan.html">Sensus Ekonomi</a></li>
                <li><a href="tim.html">Tim Pelaksana</a></li>
                <li><a href="kbli.php">KBLI</a></li>
              </ul>
            </li>
            <li><a href="administrasi.html">Teknis Administrasi</a></li>
            <li><a href="laporan.html">Laporan Kegiatan</a></li>
            <li><a href="ujian.php" class="active">Ujian</a></li>
            <li><a href="admin/login.php"><i class="bi bi-shield-lock-fill"></i> Admin</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>

  <main class="main">

    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1 class="heading-title"><?= $pageTitle ?></h1>
              <p class="mb-0">
                <?php if ($step === 'selesai' && !empty($result['judul_ujian'])): ?>
                  <?= htmlspecialchars($result['judul_ujian']) ?>
                <?php else: ?>
                  Ujian pelatihan petugas Sensus Ekonomi BPS Kabupaten Toli-Toli 2026.
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.html">Beranda</a></li>
            <?php if ($step === 'intro' || $step === 'kerjakan'): ?>
            <li><a href="ujian.php">Ujian</a></li>
            <li class="current"><?= htmlspecialchars($ujianRow['judul']) ?></li>
            <?php elseif ($step === 'selesai'): ?>
            <li><a href="ujian.php">Ujian</a></li>
            <li class="current">Selesai</li>
            <?php else: ?>
            <li class="current">Ujian</li>
            <?php endif; ?>
          </ol>
        </div>
      </nav>
    </div>

    <section class="ujian-section">
      <div class="container">

        <?php if ($step === 'selesai'): ?>
        <!-- Hapus localStorage timer setelah ujian selesai -->
        <script>
          (function(){
            const uid = <?= (int)($result['ujian_id'] ?? 0) ?>;
            if (uid) localStorage.removeItem('ujian_start_' + uid);
          })();
        </script>
        <!-- ── STEP 3: Thank you ─────────────────────────────────────────── -->
        <div class="row justify-content-center">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-5 mb-4 text-center" data-aos="zoom-in">
              <div class="thankyou-icon">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <h3 class="fw-bold mb-2">Terima kasih telah mengerjakan<br><?= htmlspecialchars($result['judul_ujian'] ?? 'ujian ini') ?>!</h3>
              <p class="text-muted mb-1">
                Jawaban Anda telah berhasil disimpan.
              </p>
              <p class="text-muted mb-4">
                <span class="fw-semibold"><?= htmlspecialchars($result['nama']) ?></span>
                &nbsp;·&nbsp;
                <?= htmlspecialchars($kecMap[$result['kecamatan']] ?? $result['kecamatan']) ?>
              </p>
              <a href="ujian.php" class="btn btn-lg fw-bold px-5"
                 style="background:#f79039; color:#fff; border-radius:10px;">
                <i class="bi bi-house me-2"></i>Kembali ke Halaman Ujian
              </a>
            </div>
          </div>
        </div>

        <?php elseif ($step === 'pilih'): ?>
        <!-- ── STEP 1: Select exam ──────────────────────────────────────── -->
        <div class="row justify-content-center mb-4">
          <div class="col-lg-7 text-center" data-aos="fade-up">
            <h4 class="fw-bold mb-1">Pilih Ujian</h4>
            <p class="text-muted">Pilih salah satu ujian di bawah ini untuk memulai.</p>
          </div>
        </div>

        <?php if (empty($ujianAktif)): ?>
        <div class="row justify-content-center">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm text-center py-5 px-4" data-aos="fade-up">
              <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
              <h5 class="text-muted fw-semibold">Tidak ada ujian yang tersedia</h5>
              <p class="text-muted small">Silakan hubungi panitia untuk informasi lebih lanjut.</p>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="row justify-content-center g-3">
          <?php foreach ($ujianAktif as $uj): ?>
          <div class="col-md-6 col-lg-4" data-aos="fade-up">
            <a href="ujian.php?id=<?= $uj['id'] ?>" class="ujian-card p-4 h-100 d-block text-decoration-none">
              <div class="ujian-icon">
                <i class="bi bi-journal-check"></i>
              </div>
              <h5 class="fw-bold mb-2"><?= htmlspecialchars($uj['judul']) ?></h5>
              <?php if ($uj['deskripsi']): ?>
              <p class="text-muted small mb-3"><?= htmlspecialchars($uj['deskripsi']) ?></p>
              <?php endif; ?>
              <div class="d-flex align-items-center gap-2 mt-auto">
                <span class="badge rounded-pill px-3 py-2" style="background:#fff3e6; color:#f79039; font-size:.8rem;">
                  <i class="bi bi-list-check me-1"></i><?= $uj['jml_soal'] ?> Soal
                </span>
                <span class="ms-auto small fw-semibold" style="color:#f79039">
                  Mulai <i class="bi bi-arrow-right-short"></i>
                </span>
              </div>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($step === 'intro' || $step === 'kerjakan'): ?>
        <?php
            $showIntro = ($step === 'intro');
            $ptTeks = trim($ujianRow['petunjuk'] ?? '');
            $ptDefault = [
                'Baca setiap soal dengan teliti sebelum menjawab.',
                'Pilih <strong>satu jawaban</strong> yang paling tepat untuk setiap soal.',
                'Pastikan semua data diri (Nama, NIK, Kecamatan, Peran) diisi dengan benar.',
                'Ujian dapat dikerjakan lebih dari satu kali. Setelah dikumpulkan, jawaban tidak dapat diubah.',
                'Jawaban tidak dapat diubah setelah dikumpulkan.',
                ((int)($ujianRow['durasi_menit'] ?? 0) > 0
                    ? 'Waktu pengerjaan <strong>' . (int)$ujianRow['durasi_menit'] . ' menit</strong>. Jawaban dikumpulkan otomatis ketika waktu habis.'
                    : 'Tidak ada batasan waktu pengerjaan.'),
            ];
            $jmlSoal = (int)$conn->query("SELECT COUNT(*) AS c FROM ujian_soal WHERE ujian_id=$ujianId")->fetch_assoc()['c'];
        ?>

        <!-- ── Intro: judul + petunjuk + tombol mulai ──────────────────── -->
        <div id="introSection"<?= !$showIntro ? ' style="display:none"' : '' ?>>
        <div class="row justify-content-center">
          <div class="col-lg-7" data-aos="fade-up">

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-left:4px solid #f79039!important;">
              <div class="d-flex align-items-center gap-3">
                <div style="width:52px;height:52px;border-radius:12px;background:#fff3e6;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#f79039;flex-shrink:0;">
                  <i class="bi bi-journal-check"></i>
                </div>
                <div>
                  <h4 class="fw-bold mb-1"><?= htmlspecialchars($ujianRow['judul']) ?></h4>
                  <?php if ($ujianRow['deskripsi']): ?>
                  <p class="text-muted small mb-0"><?= htmlspecialchars($ujianRow['deskripsi']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="badge rounded-pill px-3 py-2" style="background:#fff3e6;color:#f79039;font-size:.82rem;">
                  <i class="bi bi-list-check me-1"></i><?= $jmlSoal ?> Soal
                </span>
                <?php if ((int)($ujianRow['durasi_menit'] ?? 0) > 0): ?>
                <span class="badge rounded-pill px-3 py-2" style="background:#e0f2fe;color:#0369a1;font-size:.82rem;">
                  <i class="bi bi-clock me-1"></i><?= (int)$ujianRow['durasi_menit'] ?> Menit
                </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-left:4px solid #3b82f6!important;background:#f0f7ff;">
              <h6 class="fw-bold mb-3" style="color:#1d4ed8;">
                <i class="bi bi-info-circle-fill me-2"></i>Petunjuk Pengerjaan
              </h6>
              <?php if ($ptTeks): ?>
              <div style="white-space:pre-line;font-size:.93rem;line-height:1.7;color:#334155;">
                <?= htmlspecialchars($ptTeks) ?>
              </div>
              <?php else: ?>
              <ol class="mb-0 ps-3" style="font-size:.93rem;line-height:1.9;color:#334155;">
                <?php foreach ($ptDefault as $p): ?><li><?= $p ?></li><?php endforeach; ?>
              </ol>
              <?php endif; ?>
            </div>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="ujian.php" class="btn btn-lg btn-outline-secondary px-4" style="border-radius:10px;">
                <i class="bi bi-arrow-left me-2"></i>Kembali
              </a>
              <button type="button" onclick="startUjian()"
                      class="btn btn-lg fw-bold px-5"
                      style="background:#f79039;color:#fff;border-radius:10px;min-width:200px;">
                <i class="bi bi-play-fill me-2"></i>Mulai Ujian
              </button>
            </div>

          </div>
        </div>
        </div><!-- /#introSection -->

        <!-- ── Kerjakan: form data peserta + soal ──────────────────────── -->
        <div id="kerjakanSection"<?= $showIntro ? ' style="display:none"' : '' ?>>
        <div class="row justify-content-center">
          <div class="col-lg-8">

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
              <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (empty($soalList)): ?>
            <div class="card border-0 shadow-sm text-center py-5 px-4">
              <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
              <h5 class="text-muted fw-semibold">Soal ujian belum tersedia</h5>
              <p class="text-muted small">Silakan hubungi panitia untuk informasi lebih lanjut.</p>
              <a href="ujian.php" class="btn btn-sm btn-outline-secondary mt-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali
              </a>
            </div>
            <?php else: ?>

            <form method="POST" action="ujian.php" id="ujianForm">
              <input type="hidden" name="ujian_id" value="<?= $ujianId ?>">

              <!-- Judul ujian -->
              <div class="card border-0 shadow-sm p-4 mb-4" style="border-left:4px solid #f79039!important;">
                <div class="d-flex align-items-center gap-3">
                  <div style="width:48px;height:48px;border-radius:12px;background:#fff3e6;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#f79039;flex-shrink:0;">
                    <i class="bi bi-journal-check"></i>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($ujianRow['judul']) ?></h5>
                    <?php if ($ujianRow['deskripsi']): ?>
                    <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($ujianRow['deskripsi']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Timer -->
              <?php if ((int)($ujianRow['durasi_menit'] ?? 0) > 0): ?>
              <div class="timer-bar ok" id="timerBar">
                <i class="bi bi-clock-fill"></i>
                <span id="timerLabel">Menunggu dimulai...</span>
              </div>
              <?php endif; ?>

              <!-- Data Peserta -->
              <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-person-badge-fill me-2" style="color:#f79039"></i>Data Peserta</h5>
                <p class="text-muted small mb-3">Pilih data diri Anda secara berurutan.</p>
                <div class="row g-3">

                  <!-- Step 1: Kelas -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                    <select name="kelas" id="selKelas" class="form-select form-select-lg" required
                            onchange="onKelasChange()">
                      <option value="">— Pilih Kelas —</option>
                      <?php foreach (['A','B','C','D','E','F','G','H','I'] as $_kl): ?>
                      <option value="<?= $_kl ?>" <?= (($_POST['kelas'] ?? '') === $_kl) ? 'selected' : '' ?>>
                        Kelas <?= $_kl ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- NIK (selalu visible) -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">NIK <span class="text-danger">*</span></label>
                    <input type="text" name="nik" class="form-control form-control-lg"
                           placeholder="Masukkan 16 digit NIK Anda"
                           maxlength="16" inputmode="numeric" pattern="\d{16}"
                           value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>" required>
                    <div class="form-text">
                      <i class="bi bi-shield-lock-fill me-1 text-warning"></i>
                      NIK wajib diisi dan digunakan sebagai identitas peserta.
                    </div>
                  </div>

                  <!-- Step 2: Kecamatan (cascade) -->
                  <div class="col-md-6" id="secKec" style="display:none">
                    <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                    <select name="kecamatan" id="selKec" class="form-select form-select-lg"
                            onchange="onKecChange()">
                      <option value="">— Pilih Kecamatan —</option>
                    </select>
                  </div>

                  <!-- Step 3: Nama (cascade) -->
                  <div class="col-md-6" id="secNama" style="display:none">
                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <select name="nama" id="selNama" class="form-select form-select-lg"
                            onchange="onNamaChange()">
                      <option value="">— Pilih Nama —</option>
                    </select>
                  </div>

                  <!-- Step 4: PPL Sensus / PML Sensus (cascade) -->
                  <div class="col-md-6" id="secPeran" style="display:none">
                    <label class="form-label fw-semibold">Peran <span class="text-danger">*</span></label>
                    <select name="peran" id="selPeran" class="form-select form-select-lg">
                      <option value="">— Pilih Peran —</option>
                      <option value="PPL Sensus" <?= (($_POST['peran'] ?? '') === 'PPL Sensus') ? 'selected' : '' ?>>PPL Sensus — Petugas Lapangan Sensus</option>
                      <option value="PML Sensus" <?= (($_POST['peran'] ?? '') === 'PML Sensus') ? 'selected' : '' ?>>PML Sensus — Pemeriksa Lapangan Sensus</option>
                    </select>
                  </div>

                </div>
              </div>

              <!-- Soal Ujian -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2" style="color:#f79039"></i>Soal Ujian</h5>
                <span class="badge rounded-pill px-3 py-2" style="background:#f79039; font-size:.85rem;">
                  <?= count($soalList) ?> Soal
                </span>
              </div>

              <?php foreach ($soalList as $i => $soal): ?>
              <div class="card border-0 shadow-sm soal-card mb-3">
                <div class="card-body p-4">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge fw-bold px-3 py-2" style="background:#f79039; font-size:.82rem;">
                      No. <?= $soal['nomor_urut'] ?>
                    </span>
                    <span class="text-danger fw-semibold" style="font-size:.78rem;">
                      <i class="bi bi-asterisk me-1" style="font-size:.6rem;vertical-align:middle;"></i>Wajib dijawab
                    </span>
                  </div>
                  <p class="fw-semibold mb-3" style="font-size:1rem; line-height:1.6;">
                    <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                  </p>
                  <?php if (empty($soal['opsi'])): ?>
                  <p class="text-muted small fst-italic">Soal ini belum memiliki pilihan jawaban.</p>
                  <?php else: ?>
                  <?php foreach ($soal['opsi'] as $opsi): ?>
                  <div class="opsi-wrap">
                    <input type="radio" name="jawab_<?= $soal['id'] ?>" id="opsi_<?= $opsi['id'] ?>" value="<?= $opsi['id'] ?>"
                           <?= (isset($_POST['jawab_'.$soal['id']]) && (int)$_POST['jawab_'.$soal['id']] === (int)$opsi['id']) ? 'checked' : '' ?>>

                    <label for="opsi_<?= $opsi['id'] ?>" class="opsi-label w-100">
                      <span class="opsi-huruf"><?= htmlspecialchars($opsi['huruf']) ?></span>
                      <span><?= htmlspecialchars($opsi['teks']) ?></span>
                    </label>
                  </div>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Submit -->
              <div class="card border-0 shadow-sm p-4 text-center">
                <p class="text-muted small mb-3">
                  <i class="bi bi-info-circle me-1"></i>
                  Pastikan semua soal telah dijawab sebelum mengirim.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                  <a href="ujian.php" class="btn btn-lg btn-outline-secondary px-4" style="border-radius:10px;">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                  </a>
                  <button type="submit" class="btn btn-lg fw-bold px-5"
                          style="background:#f79039; color:#fff; border-radius:10px; min-width:200px;"
                          onclick="return validateUjian()">
                    <i class="bi bi-send-fill me-2"></i>Kumpulkan Jawaban
                  </button>
                </div>
              </div>

            </form>
            <?php endif; ?>
          </div>
        </div>
        </div><!-- /#kerjakanSection -->
        <?php endif; ?>

      </div>
    </section>

  </main>

  <footer id="footer" class="footer-16 footer position-relative" style="background-color: var(--accent-color); color: #ffffff; padding-top: 60px;">
    <div class="container">
      <div class="footer-main" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-start">
          <div class="col-lg-4">
            <div class="brand-section">
              <a href="index.html" class="logo d-flex align-items-center mb-4" style="text-decoration: none;">
                <span class="sitename" style="color: #ffffff; font-weight: 700; font-size: 1.5rem;">BPS Kabupaten Toli-Toli</span>
              </a>
              <div class="contact-info mt-4">
                <div class="contact-item mb-2">
                  <i class="bi bi-geo-alt-fill me-2"></i>
                  <span style="font-size: 0.9rem;">Jl. Magamu No.111, Tuweley, Kec. Baolan, Kabupaten Toli-Toli, Sulawesi Tengah 94515</span>
                </div>
                <div class="contact-item mb-2">
                  <i class="bi bi-telephone-fill me-2"></i>
                  <span>085183287206</span>
                </div>
                <div class="contact-item mb-2">
                  <i class="bi bi-envelope-fill me-2"></i>
                  <span>bps7206@bps.go.id</span>
                </div>
              </div>
              <div class="social-links mt-4">
                <a href="https://www.facebook.com/profile.php?id=61589155730437" class="me-3 text-white"><i class="bi bi-facebook fs-4"></i></a>
                <a href="https://www.instagram.com/bpstolitoli/" class="me-3 text-white"><i class="bi bi-instagram fs-4"></i></a>
                <a href="https://youtube.com/@bpskabupatentolitoli4010?si=hPO81wq7eE5WNBgk" class="me-3 text-white"><i class="bi bi-youtube fs-4"></i></a>
                <a href="https://tolitolikab.bps.go.id/" class="text-white"><i class="bi bi-globe fs-4"></i></a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 mt-5 mt-lg-0">
            <div class="row">
              <div class="col-6">
                <div class="nav-column">
                  <h6 style="color: #ffffff; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 20px;">Navigasi</h6>
                  <nav class="footer-nav d-flex flex-column gap-2">
                    <a href="index.html" style="color: rgba(255,255,255,.85); text-decoration: none;">Beranda</a>
                    <a href="penjelasan.html" style="color: rgba(255,255,255,.85); text-decoration: none;">Penjelasan Umum</a>
                    <a href="administrasi.html" style="color: rgba(255,255,255,.85); text-decoration: none;">Teknis Administrasi</a>
                    <a href="laporan.html" style="color: rgba(255,255,255,.85); text-decoration: none;">Laporan</a>
                    <a href="ujian.php" style="color: rgba(255,255,255,.85); text-decoration: none;">Ujian</a>
                  </nav>
                </div>
              </div>
              <div class="col-6">
                <div class="nav-column">
                  <h6 style="color: #ffffff; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 20px;">Jam Layanan</h6>
                  <p class="small mb-1" style="opacity: 0.8;">Senin - Kamis:</p>
                  <p class="fw-bold small mb-2">07.30 - 16.00 WITA</p>
                  <p class="small mb-1" style="opacity: 0.8;">Jumat:</p>
                  <p class="fw-bold small mb-3">07.30 - 16.30 WITA</p>
                  <p style="color: rgba(255,255,255,.7); font-size: .75rem; font-style: italic;">*Sabtu, Minggu & Hari Libur Nasional Tutup</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 mt-5 mt-lg-0">
            <h6 style="color: #ffffff; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 20px;">Lokasi Kami</h6>
            <div style="width:100%; height:200px; border-radius:15px; overflow:hidden; border:3px solid rgba(255,255,255,.2);">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.1551286282065!2d120.8198227747264!3d1.0446880624812578!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32712b4be7b628b7%3A0x602e57994bfb76da!2sBadan%20Pusat%20Statistik%20Kabupaten%20Tolitoli!5e0!3m2!1sen!2sid!4v1776297625755!5m2!1sen!2sid"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom mt-5" style="background-color: rgba(0,0,0,.1); padding: 25px 0;">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6 text-center text-md-start">
            <p class="mb-0" style="font-size:.9rem; opacity:.9;">© 2026 <strong>BPS Kabupaten Toli-Toli</strong></p>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <a href="#!" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>
  <div id="preloader"></div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
    // ── Cascading Peserta ─────────────────────────────────────────────────────
    const _mitraData = <?= json_encode(array_values($mitraData)) ?>;
    const _kecMap    = <?= json_encode($kecMap) ?>;

    function onKelasChange() {
      const kelas    = document.getElementById('selKelas').value;
      const secKec   = document.getElementById('secKec');
      const secNama  = document.getElementById('secNama');
      const secPeran = document.getElementById('secPeran');
      const selKec   = document.getElementById('selKec');
      const selNama  = document.getElementById('selNama');
      const selPeran = document.getElementById('selPeran');

      secNama.style.display  = 'none';
      secPeran.style.display = 'none';
      if (selPeran) { selPeran.value = ''; selPeran.removeAttribute('required'); }
      selNama.innerHTML = '<option value="">— Pilih Nama —</option>';
      selNama.removeAttribute('required');
      selKec.innerHTML = '<option value="">— Pilih Kecamatan —</option>';

      if (!kelas) {
        secKec.style.display = 'none';
        selKec.removeAttribute('required');
        return;
      }

      const kecs = [...new Set(
        _mitraData.filter(m => m.kelas === kelas).map(m => m.kecamatan).filter(Boolean)
      )].sort();

      kecs.forEach(kode => {
        const o = document.createElement('option');
        o.value = kode;
        o.textContent = _kecMap[kode] || kode;
        selKec.appendChild(o);
      });

      secKec.style.display = '';
      selKec.setAttribute('required', '');
      selKec.value = '';
    }

    function onKecChange() {
      const kelas    = document.getElementById('selKelas').value;
      const kec      = document.getElementById('selKec').value;
      const secNama  = document.getElementById('secNama');
      const secPeran = document.getElementById('secPeran');
      const selNama  = document.getElementById('selNama');
      const selPeran = document.getElementById('selPeran');

      secPeran.style.display = 'none';
      if (selPeran) { selPeran.value = ''; selPeran.removeAttribute('required'); }
      selNama.innerHTML = '<option value="">— Pilih Nama —</option>';

      if (!kec) {
        secNama.style.display = 'none';
        selNama.removeAttribute('required');
        return;
      }

      _mitraData
        .filter(m => m.kelas === kelas && m.kecamatan === kec)
        .forEach(m => {
          const o = document.createElement('option');
          o.value = m.nama;
          o.textContent = m.nama;
          selNama.appendChild(o);
        });

      secNama.style.display = '';
      selNama.setAttribute('required', '');
      selNama.value = '';
    }

    function onNamaChange() {
      const selNama  = document.getElementById('selNama');
      const secPeran = document.getElementById('secPeran');
      const selPeran = document.getElementById('selPeran');

      if (!selNama.value) {
        secPeran.style.display = 'none';
        if (selPeran) selPeran.removeAttribute('required');
        return;
      }
      if (selPeran) { selPeran.value = ''; selPeran.setAttribute('required', ''); }
      secPeran.style.display = '';
    }

    // Restore cascade state after POST error
    (function restoreCascade() {
      const k  = <?= json_encode($_POST['kelas'] ?? '') ?>;
      const kc = <?= json_encode($_POST['kecamatan'] ?? '') ?>;
      const nm = <?= json_encode($_POST['nama'] ?? '') ?>;
      const pr = <?= json_encode($_POST['peran'] ?? '') ?>;
      if (!k) return;
      const selK = document.getElementById('selKelas');
      if (!selK) return;
      selK.value = k; onKelasChange();
      if (kc) {
        const selKc = document.getElementById('selKec');
        if (selKc) { selKc.value = kc; onKecChange(); }
      }
      if (nm) {
        const selN = document.getElementById('selNama');
        if (selN) {
          selN.value = nm; onNamaChange();
          if (pr) {
            const selP = document.getElementById('selPeran');
            if (selP) selP.value = pr;
          }
        }
      }
    })();

    function validateUjian() {
      const cards = document.querySelectorAll('.soal-card');
      const unanswered = [];
      cards.forEach((card, i) => {
        const radios = card.querySelectorAll('input[type="radio"]');
        if (radios.length > 0 && !Array.from(radios).some(r => r.checked)) {
          const badge = card.querySelector('.badge');
          unanswered.push(badge ? badge.textContent.trim() : 'No. ' + (i + 1));
          // Highlight soal yang belum dijawab
          card.style.outline = '2px solid #ef4444';
          card.style.outlineOffset = '2px';
        } else {
          card.style.outline = '';
          card.style.outlineOffset = '';
        }
      });
      if (unanswered.length > 0) {
        let alertEl = document.getElementById('ujianValidasiAlert');
        if (!alertEl) {
          alertEl = document.createElement('div');
          alertEl.id = 'ujianValidasiAlert';
          alertEl.className = 'alert alert-danger alert-dismissible fade show mb-4';
          const submitCard = document.querySelector('#ujianForm .card:last-child');
          if (submitCard) submitCard.before(alertEl);
        }
        alertEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>'
          + '<strong>Semua soal wajib dijawab!</strong> Soal yang belum dijawab: '
          + unanswered.join(', ') + '.'
          + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }
      // Hapus alert jika semua sudah dijawab
      const old = document.getElementById('ujianValidasiAlert');
      if (old) old.remove();
      return true;
    }

    // ── Countdown Timer (harus dideklarasi sebelum IIFE yang memanggilnya) ───────
    let _timerRunning = false;
    function _startTimer(setStart) {
      const durasi  = <?= isset($ujianRow) ? (int)($ujianRow['durasi_menit'] ?? 0) : 0 ?>;
      const uid     = <?= $ujianId ?>;
      if (durasi <= 0 || _timerRunning) return;

      const KEY      = 'ujian_start_' + uid;
      const totalSec = durasi * 60;

      if (setStart && !localStorage.getItem(KEY)) {
        localStorage.setItem(KEY, Date.now().toString());
      }
      if (!localStorage.getItem(KEY)) return;

      _timerRunning = true;
      const bar   = document.getElementById('timerBar');
      const label = document.getElementById('timerLabel');
      function pad(n) { return String(n).padStart(2, '0'); }

      function tick() {
        const elapsed = Math.floor((Date.now() - parseInt(localStorage.getItem(KEY))) / 1000);
        const rem     = totalSec - elapsed;
        if (rem <= 0) {
          label.textContent = 'Waktu habis! Jawaban dikumpulkan otomatis...';
          bar.className = 'timer-bar danger';
          localStorage.removeItem(KEY);
          document.getElementById('ujianForm').submit();
          return;
        }
        const m = Math.floor(rem / 60), s = rem % 60;
        label.textContent = pad(m) + ':' + pad(s) + ' tersisa';
        if (rem <= 60)                   bar.className = 'timer-bar danger';
        else if (rem <= totalSec * 0.25) bar.className = 'timer-bar warn';
        else                             bar.className = 'timer-bar ok';
      }

      tick();
      setInterval(tick, 1000);
    }

    // ── Auto-resume: tampilkan soal + lanjutkan timer setelah refresh/navigasi ──
    (function() {
      const uid = <?= $ujianId ?>;
      if (!uid || !localStorage.getItem('ujian_start_' + uid)) return;
      const intro    = document.getElementById('introSection');
      const kerjakan = document.getElementById('kerjakanSection');
      if (intro)    intro.style.display    = 'none';
      if (kerjakan) kerjakan.style.display = '';
      _startTimer(false);
    })();

    // ── Mulai ujian tanpa reload ──────────────────────────────────────────────
    function startUjian() {
      const intro    = document.getElementById('introSection');
      const kerjakan = document.getElementById('kerjakanSection');
      if (intro)    intro.style.display    = 'none';
      if (kerjakan) kerjakan.style.display = '';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      _startTimer(true);
    }

    // ── Redirect ke ujian aktif jika buka halaman daftar ujian ───────────────
    <?php if ($step === 'pilih'): ?>
    (function() {
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('ujian_start_')) {
          const uid = parseInt(key.replace('ujian_start_', ''));
          if (uid > 0) { location.replace('ujian.php?id=' + uid); break; }
        }
      }
    })();
    <?php endif; ?>
  </script>
</body>
</html>
