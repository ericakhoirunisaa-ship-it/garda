<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$monitoringPages = ['monitoring_sensus_ekonomi.php','data_sensus_ekonomi.php','monitoring_pencacahan.php'];
$ujianPages      = ['ujian.php','ujian_soal.php','ujian_hasil.php','ujian_peserta.php'];
$wawancaraPages  = ['index.php','form_wawancara.php','monitoring.php','rekap.php',
                    'penilaian_akhir.php','import_nilai_kompetensi.php','export_penilaian_akhir.php'];
$akunPages       = ['manajemen_akun.php'];

$groupMonitoring = in_array($currentPage, $monitoringPages);
$groupUjian      = in_array($currentPage, $ujianPages);
$groupWawancara  = in_array($currentPage, $wawancaraPages);
$groupAkun       = in_array($currentPage, $akunPages);
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">

    <!-- ── Brand ────────────────────────────────── -->
    <a class="sidebar-brand" href="../index.html">
        <img src="../assets/img/logo.png" height="36" alt="Logo" onerror="this.style.display='none'">
        <div>
            <div class="brand-title">SEMANIS 2026</div>
            <div class="brand-sub">BPS Kab. Toli-Toli</div>
        </div>
    </a>

    <!-- ── Navigation ───────────────────────────── -->
    <nav class="sidebar-nav">

        <!-- Back to beranda -->
        <a href="../index.html" class="nav-home-btn">
            <i class="bi bi-house-fill"></i>
            <span>Kembali ke Beranda</span>
        </a>

        <!-- ╔══ MONITORING ══════════════════════════╗ -->
        <div class="nav-group <?= $groupMonitoring ? 'open active' : '' ?>" id="grp-monitoring">
            <div class="nav-group-header" onclick="toggleGroup('grp-monitoring')">
                <div class="nav-group-header-left">
                    <div class="nav-group-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <span>Monitoring</span>
                </div>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-content">
                <a href="monitoring_sensus_ekonomi.php"
                   class="nav-sub-item <?= $currentPage === 'monitoring_sensus_ekonomi.php' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span>Monitoring Sensus Ekonomi</span>
                </a>
                <?php if (isSuperAdmin()): ?>
                <a href="monitoring_pencacahan.php"
                   class="nav-sub-item <?= $currentPage === 'monitoring_pencacahan.php' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-check-fill"></i>
                    <span>Monitoring Pencacahan</span>
                </a>
                <a href="data_sensus_ekonomi.php"
                   class="nav-sub-item <?= $currentPage === 'data_sensus_ekonomi.php' ? 'active' : '' ?>">
                    <i class="bi bi-database-fill-gear"></i>
                    <span>Data Sensus Ekonomi</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ╔══ UJIAN ════════════════════════════════╗ -->
        <div class="nav-group <?= $groupUjian ? 'open active' : '' ?>" id="grp-ujian">
            <div class="nav-group-header" onclick="toggleGroup('grp-ujian')">
                <div class="nav-group-header-left">
                    <div class="nav-group-icon"><i class="bi bi-journal-check"></i></div>
                    <span>Ujian</span>
                </div>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-content">
                <a href="ujian.php"
                   class="nav-sub-item <?= in_array($currentPage, ['ujian.php','ujian_soal.php']) ? 'active' : '' ?>">
                    <i class="bi bi-pencil-square"></i>
                    <span>Kelola Ujian</span>
                </a>
                <a href="ujian_hasil.php"
                   class="nav-sub-item <?= in_array($currentPage, ['ujian_hasil.php','ujian_peserta.php']) ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-data-fill"></i>
                    <span>Hasil Ujian</span>
                </a>
            </div>
        </div>

        <!-- ╔══ WAWANCARA ════════════════════════════╗ -->
        <div class="nav-group <?= $groupWawancara ? 'open active' : '' ?>" id="grp-wawancara">
            <div class="nav-group-header" onclick="toggleGroup('grp-wawancara')">
                <div class="nav-group-header-left">
                    <div class="nav-group-icon"><i class="bi bi-mic-fill"></i></div>
                    <span>Wawancara</span>
                </div>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </div>
            <div class="nav-group-content">
                <a href="index.php"
                   class="nav-sub-item <?= in_array($currentPage, ['index.php','form_wawancara.php']) ? 'active' : '' ?>">
                    <i class="bi bi-mic"></i>
                    <span>Input Wawancara</span>
                </a>
                <a href="monitoring.php"
                   class="nav-sub-item <?= $currentPage === 'monitoring.php' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Monitoring Wawancara</span>
                </a>
                <?php if (isSuperAdmin()): ?>
                <a href="rekap.php"
                   class="nav-sub-item <?= $currentPage === 'rekap.php' ? 'active' : '' ?>">
                    <i class="bi bi-table"></i>
                    <span>Rekap Wawancara</span>
                </a>
                <a href="penilaian_akhir.php"
                   class="nav-sub-item <?= in_array($currentPage, ['penilaian_akhir.php','import_nilai_kompetensi.php','export_penilaian_akhir.php']) ? 'active' : '' ?>">
                    <i class="bi bi-award-fill"></i>
                    <span>Penilaian Akhir Mitra</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ╔══ MANAJEMEN AKUN ═══════════════════════╗ -->
        <?php if (isSuperAdmin()): ?>
        <div class="nav-standalone-group">
            <a href="manajemen_akun.php"
               class="nav-group-header nav-standalone <?= $groupAkun ? 'active' : '' ?>">
                <div class="nav-group-header-left">
                    <div class="nav-group-icon"><i class="bi bi-people-fill"></i></div>
                    <span>Manajemen Akun</span>
                </div>
            </a>
            <a href="export.php"
               class="nav-group-header nav-standalone <?= $currentPage === 'export.php' ? 'active' : '' ?>">
                <div class="nav-group-header-left">
                    <div class="nav-group-icon" style="background:rgba(34,197,94,.25); color:#bbf7d0;">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                    </div>
                    <span>Export Excel</span>
                </div>
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <!-- ── Footer: User + Logout ─────────────────── -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(mb_substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? '') ?></div>
                <div class="user-role"><?= isSuperAdmin() ? 'Super Admin' : 'Petugas' ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn" title="Keluar">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

</aside>

<style>
/* ─── CSS Variables ──────────────────────────── */
:root {
    --accent:      #f79039;
    --accent-dark: #e06820;
    --sidebar-bg:  #f79039;
    --sidebar-w:   265px;
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
    overflow-x:hidden;
}
.sidebar::-webkit-scrollbar { width:4px; }
.sidebar::-webkit-scrollbar-track { background:transparent; }
.sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,.25); border-radius:4px; }

/* Brand */
.sidebar-brand {
    display:flex; align-items:center; gap:12px;
    padding:18px 18px 16px;
    border-bottom:1px solid rgba(255,255,255,.22);
    text-decoration:none; flex-shrink:0;
}
.brand-title { font-size:.88rem; font-weight:800; color:#fff; letter-spacing:.03em; line-height:1.2; }
.brand-sub   { font-size:.67rem; color:rgba(255,255,255,.7); margin-top:2px; }

/* Nav container */
.sidebar-nav { flex:1; padding:12px 10px 8px; }

/* Back to beranda */
.nav-home-btn {
    display:flex; align-items:center; gap:10px;
    padding:8px 12px;
    color:rgba(255,255,255,.75);
    text-decoration:none;
    border-radius:9px;
    font-size:.8rem; font-weight:600;
    margin-bottom:10px;
    background:rgba(0,0,0,.1);
    transition:background .18s, color .18s;
    border:1px solid rgba(255,255,255,.15);
}
.nav-home-btn i { font-size:.9rem; }
.nav-home-btn:hover { background:rgba(0,0,0,.18); color:#fff; }

/* ── Group wrapper ───────────────────────────── */
.nav-group {
    margin-bottom:4px;
    border-radius:11px;
    overflow:hidden;
    background:rgba(0,0,0,.06);
    border:1px solid rgba(255,255,255,.1);
    transition:background .2s;
}
.nav-group.active {
    background:rgba(0,0,0,.1);
    border-color:rgba(255,255,255,.2);
}

/* Group header (clickable) */
.nav-group-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 12px;
    color:#fff;
    cursor:pointer;
    font-size:.84rem; font-weight:700;
    transition:background .18s;
    user-select:none;
    text-decoration:none;
}
.nav-group-header:hover { background:rgba(255,255,255,.1); }

.nav-group-header-left {
    display:flex; align-items:center; gap:10px;
}

/* Group icon bubble */
.nav-group-icon {
    width:28px; height:28px;
    border-radius:8px;
    background:rgba(255,255,255,.22);
    display:flex; align-items:center; justify-content:center;
    font-size:.88rem; flex-shrink:0;
    color:#fff;
}

/* Chevron */
.nav-chevron {
    font-size:.65rem;
    opacity:.7;
    transition:transform .22s ease, opacity .18s;
    flex-shrink:0;
}
.nav-group.open .nav-chevron { transform:rotate(180deg); opacity:1; }

/* Collapsible content */
.nav-group-content {
    overflow:hidden;
    max-height:0;
    transition:max-height .28s ease;
    padding:0 6px;
}
.nav-group.open .nav-group-content {
    max-height:400px;
    padding:0 6px 6px;
}

/* Sub-items */
.nav-sub-item {
    display:flex; align-items:center; gap:9px;
    padding:8px 10px 8px 14px;
    color:rgba(255,255,255,.82);
    text-decoration:none;
    border-radius:8px;
    font-size:.81rem;
    margin-bottom:1px;
    transition:background .15s, color .15s;
    position:relative;
}
.nav-sub-item i { width:16px; text-align:center; font-size:.88rem; flex-shrink:0; opacity:.9; }
.nav-sub-item:hover { background:rgba(255,255,255,.15); color:#fff; }
.nav-sub-item.active {
    background:#fff;
    color:var(--accent);
    font-weight:700;
}
.nav-sub-item.active i { opacity:1; }

/* ── Standalone group (Manajemen Akun, Export) ── */
.nav-standalone-group { margin-bottom:4px; }
.nav-standalone {
    border-radius:11px;
    background:rgba(0,0,0,.06);
    border:1px solid rgba(255,255,255,.1);
    margin-bottom:4px;
    font-weight:700;
    cursor:pointer;
}
.nav-standalone:hover { background:rgba(255,255,255,.15); }
.nav-standalone.active {
    background:#fff !important;
    color:var(--accent) !important;
    border-color:rgba(255,255,255,.3);
}
.nav-standalone.active .nav-group-icon {
    background:rgba(247,144,57,.15);
    color:var(--accent);
}

/* ── Sidebar Footer ──────────────────────────── */
.sidebar-footer {
    display:flex; align-items:center; gap:10px;
    padding:12px 14px;
    border-top:1px solid rgba(255,255,255,.22);
    flex-shrink:0;
}
.user-info  { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.user-meta  { overflow:hidden; flex:1; }
.user-avatar {
    width:34px; height:34px; border-radius:50%;
    background:#fff;
    display:flex; align-items:center; justify-content:center;
    font-weight:800; color:var(--accent); font-size:.84rem;
    flex-shrink:0;
    box-shadow:0 2px 6px rgba(0,0,0,.12);
}
.user-name { font-size:.8rem; font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role {
    font-size:.67rem; color:rgba(255,255,255,.7);
    background:rgba(255,255,255,.15); border-radius:20px;
    padding:1px 7px; display:inline-block; margin-top:2px;
}
.logout-btn {
    width:34px; height:34px;
    display:flex; align-items:center; justify-content:center;
    color:rgba(255,255,255,.8); text-decoration:none;
    border-radius:9px; font-size:1.1rem;
    transition:color .18s, background .18s;
    flex-shrink:0;
    background:rgba(220,38,38,.2);
    border:1px solid rgba(220,38,38,.2);
}
.logout-btn:hover { color:#fff; background:rgba(220,38,38,.5); }

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
function toggleGroup(id) {
    const group = document.getElementById(id);
    const isOpen = group.classList.contains('open');
    // Close other open groups
    document.querySelectorAll('.nav-group.open').forEach(g => {
        if (g.id !== id) g.classList.remove('open');
    });
    group.classList.toggle('open', !isOpen);
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
</script>
