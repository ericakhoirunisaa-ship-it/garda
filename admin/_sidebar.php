<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <a class="sidebar-brand" href="../index.html">
        <img src="../assets/img/logo.png" height="38" alt="Logo" onerror="this.style.display='none'">
        <div>
            <div class="brand-title">SEMANIS 2026</div>
            <div class="brand-sub">BPS Kab. Toli-Toli</div>
        </div>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <a href="../index.html" class="nav-item" style="background:rgba(255,255,255,.12); margin-bottom:6px;">
            <i class="bi bi-house-fill"></i>
            <span>Kembali ke Beranda</span>
        </a>

        <div class="nav-section-label">MENU UTAMA</div>

        <a href="index.php" class="nav-item <?= in_array($currentPage, ['index.php','form_wawancara.php']) ? 'active' : '' ?>">
            <i class="bi bi-mic-fill"></i>
            <span>Wawancara</span>
        </a>

        <a href="monitoring.php" class="nav-item <?= $currentPage === 'monitoring.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Monitoring Wawancara</span>
        </a>

        <?php if (isSuperAdmin()): ?>
        <a href="monitoring_pencacahan.php" class="nav-item <?= $currentPage === 'monitoring_pencacahan.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i>
            <span>Monitoring Pencacahan</span>
        </a>
        <?php endif; ?>

        <a href="monitoring_sensus_ekonomi.php" class="nav-item <?= $currentPage === 'monitoring_sensus_ekonomi.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Monitoring Sensus Ekonomi</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-section-label">UJIAN PUBLIK</div>

        <a href="ujian.php" class="nav-item <?= in_array($currentPage, ['ujian.php','ujian_soal.php']) ? 'active' : '' ?>">
            <i class="bi bi-journal-check"></i>
            <span>Kelola Ujian</span>
        </a>

        <a href="ujian_hasil.php" class="nav-item <?= $currentPage === 'ujian_hasil.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-data-fill"></i>
            <span>Hasil Ujian</span>
        </a>

        <?php if (isSuperAdmin()): ?>
        <div class="nav-divider"></div>
        <div class="nav-section-label">SUPER ADMIN</div>

        <a href="rekap.php" class="nav-item <?= $currentPage === 'rekap.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-data-fill"></i>
            <span>Rekap Wawancara</span>
        </a>

        <a href="penilaian_akhir.php" class="nav-item <?= in_array($currentPage, ['penilaian_akhir.php','import_nilai_kompetensi.php','export_penilaian_akhir.php']) ? 'active' : '' ?>">
            <i class="bi bi-award-fill"></i>
            <span>Penilaian Akhir Mitra</span>
        </a>

        <a href="data_sensus_ekonomi.php" class="nav-item <?= $currentPage === 'data_sensus_ekonomi.php' ? 'active' : '' ?>">
            <i class="bi bi-database-fill-gear"></i>
            <span>Data Sensus Ekonomi</span>
        </a>

        <a href="manajemen_akun.php" class="nav-item <?= $currentPage === 'manajemen_akun.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Manajemen Akun</span>
        </a>

        <a href="export.php" class="nav-item">
            <i class="bi bi-file-earmark-excel-fill" style="color:#22c55e"></i>
            <span>Export Excel</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Footer: User Info + Logout -->
    <div class="sidebar-footer" style="flex-direction:column; gap:8px; padding:12px 14px;">
        <div class="user-info" style="width:100%">
            <div class="user-avatar">
                <?= strtoupper(mb_substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? '') ?></div>
                <div class="user-role"><?= isSuperAdmin() ? 'Super Admin' : 'Petugas' ?></div>
            </div>
        </div>
        <div style="display:flex; gap:6px; width:100%;">
            <a href="../index.html"
               style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
                      background:rgba(255,255,255,.15); color:#fff; text-decoration:none;
                      border-radius:8px; padding:7px 10px; font-size:.78rem; font-weight:600;
                      transition:background .18s;"
               onmouseover="this.style.background='rgba(255,255,255,.25)'"
               onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="bi bi-house-fill"></i> Beranda
            </a>
            <a href="logout.php"
               style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
                      background:rgba(220,38,38,.25); color:#fff; text-decoration:none;
                      border-radius:8px; padding:7px 10px; font-size:.78rem; font-weight:600;
                      transition:background .18s;"
               onmouseover="this.style.background='rgba(220,38,38,.5)'"
               onmouseout="this.style.background='rgba(220,38,38,.25)'">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </div>
</aside>

<style>
/* ─── CSS Variables ──────────────────────────── */
:root {
    --accent:      #f79039;
    --accent-dark: #e06820;
    --sidebar-bg:  #f79039;
    --sidebar-w:   260px;
    --topbar-h:    60px;
}

/* ─── Reset / Base ───────────────────────────── */
body { background:#f0f2f5; font-family:'Roboto',system-ui,sans-serif; margin:0; }

/* ─── Sidebar ────────────────────────────────── */
.sidebar {
    position:fixed; top:0; left:0; bottom:0;
    width:var(--sidebar-w);
    background:var(--sidebar-bg);
    display:flex; flex-direction:column;
    z-index:1000;
    transition:transform .28s cubic-bezier(.4,0,.2,1);
    overflow-y:auto;
}

.sidebar-brand {
    display:flex; align-items:center; gap:12px;
    padding:18px 20px 16px;
    border-bottom:1px solid rgba(255,255,255,.25);
    text-decoration:none;
    flex-shrink:0;
}
.brand-title { font-size:.88rem; font-weight:800; color:#fff; letter-spacing:.03em; line-height:1.2; }
.brand-sub   { font-size:.68rem; color:rgba(255,255,255,.72); margin-top:1px; }

.sidebar-nav { flex:1; padding:14px 10px; }

.nav-section-label {
    font-size:.62rem; font-weight:700;
    letter-spacing:.12em; color:rgba(255,255,255,.55);
    padding:10px 10px 5px;
    text-transform:uppercase;
}

.nav-divider { height:1px; background:rgba(255,255,255,.22); margin:8px 10px; }

.sidebar .nav-item {
    display:flex; align-items:center; gap:11px;
    padding:10px 12px;
    color:rgba(255,255,255,.88);
    text-decoration:none;
    border-radius:9px;
    font-size:.855rem;
    margin-bottom:2px;
    transition:background .18s, color .18s;
}
.sidebar .nav-item i { width:20px; text-align:center; font-size:1rem; flex-shrink:0; }
.sidebar .nav-item:hover  { background:rgba(255,255,255,.18); color:#fff; }
.sidebar .nav-item.active {
    background:#fff;
    color:var(--accent);
    font-weight:700;
}

.sidebar-footer {
    display:flex; align-items:center; gap:10px;
    padding:12px 14px;
    border-top:1px solid rgba(255,255,255,.25);
    flex-shrink:0;
}
.user-info  { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.user-meta  { overflow:hidden; }
.user-avatar {
    width:34px; height:34px; border-radius:50%;
    background:#fff;
    display:flex; align-items:center; justify-content:center;
    font-weight:800; color:var(--accent); font-size:.82rem;
    flex-shrink:0;
}
.user-name { font-size:.8rem; font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role { font-size:.68rem; color:rgba(255,255,255,.7); }
.logout-btn {
    color:rgba(255,255,255,.8); text-decoration:none;
    padding:7px 9px; border-radius:8px;
    font-size:1.1rem; transition:color .18s, background .18s; flex-shrink:0;
}
.logout-btn:hover { color:#fff; background:rgba(255,255,255,.2); }

/* ─── Main Content ───────────────────────────── */
.main-content {
    margin-left:var(--sidebar-w);
    min-height:100vh;
    display:flex; flex-direction:column;
}

/* ─── Topbar ─────────────────────────────────── */
.topbar {
    height:var(--topbar-h);
    background:#fff;
    border-bottom:1px solid #e9ecef;
    display:flex; align-items:center;
    padding:0 24px; gap:14px;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
    position:sticky; top:0; z-index:100;
}
.topbar-toggle {
    display:none;
    background:none; border:none;
    font-size:1.25rem; color:#3c4049; cursor:pointer; padding:4px;
    border-radius:6px; transition:background .15s;
}
.topbar-toggle:hover { background:#f1f5f9; }
.topbar-title { font-weight:700; color:#112344; font-size:.95rem; line-height:1.2; }
.topbar-sub   { font-size:.72rem; color:#94a3b8; }
.topbar-right { margin-left:auto; display:flex; align-items:center; gap:12px; }

/* ─── Page Body ──────────────────────────────── */
.page-body { padding:24px; flex:1; }

/* ─── Overlay (mobile) ───────────────────────── */
.sidebar-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:999;
}

/* ─── Responsive ─────────────────────────────── */
@media (max-width:991.98px) {
    .sidebar         { transform:translateX(-100%); }
    .sidebar.open    { transform:translateX(0); }
    .sidebar-overlay.open { display:block; }
    .main-content    { margin-left:0; }
    .topbar-toggle   { display:block; }
    .page-body       { padding:16px; }
}

/* ─── Shared Component Styles ────────────────── */
.stat-card { border:none; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); }
.badge-lulus { background:#16a34a; }
.badge-tidak { background:#dc2626; }
.badge-belum { background:#6b7280; }
.progress-accent { background:var(--accent); }

/* Pagination */
.page-link { color:var(--accent); }
.page-link:hover { color:var(--accent-dark); }
.page-item.active .page-link { background:var(--accent); border-color:var(--accent); color:#fff; }
</style>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
</script>
