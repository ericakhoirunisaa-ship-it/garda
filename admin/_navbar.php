<?php
// Tentukan halaman aktif berdasarkan nama file
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#f79039;">
    <div class="container-fluid px-4">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="../index.html">
            <img src="../assets/img/logo.png" height="32" alt="Logo" onerror="this.style.display='none'">
            <span>SEMANIS 2026</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <!-- Menu Utama -->
            <ul class="navbar-nav me-auto ms-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?= in_array($currentPage, ['index.php','form_wawancara.php']) ? 'active bg-white bg-opacity-25 fw-semibold' : '' ?>"
                       href="index.php">
                        <i class="bi bi-mic-fill me-1"></i>Wawancara
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?= $currentPage === 'monitoring.php' ? 'active bg-white bg-opacity-25 fw-semibold' : '' ?>"
                       href="monitoring.php">
                        <i class="bi bi-bar-chart-line-fill me-1"></i>Monitoring
                    </a>
                </li>
                <?php if (isSuperAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?= $currentPage === 'rekap.php' ? 'active bg-white bg-opacity-25 fw-semibold' : '' ?>"
                       href="rekap.php">
                        <i class="bi bi-table me-1"></i>Rekap
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?= $currentPage === 'manajemen_akun.php' ? 'active bg-white bg-opacity-25 fw-semibold' : '' ?>"
                       href="manajemen_akun.php">
                        <i class="bi bi-people-fill me-1"></i>Akun
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Kanan: Export + User + Logout -->
            <div class="d-flex align-items-center gap-2">
                <?php if (isSuperAdmin()): ?>
                <a href="export.php" class="btn btn-sm btn-light fw-semibold">
                    <i class="bi bi-file-earmark-excel-fill text-success me-1"></i>Export Excel
                </a>
                <?php endif; ?>
                <div class="vr bg-white opacity-50 mx-1"></div>
                <span class="text-white small">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['admin_nama']) ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</nav>
