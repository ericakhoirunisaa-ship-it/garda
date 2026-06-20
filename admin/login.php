<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, nama, role FROM admin_seleksi WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        $_SESSION['admin_role'] = $admin['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root { --accent:#f79039; --accent-dark:#e06820; }

        *, *::before, *::after { box-sizing:border-box; }

        body {
            margin:0; min-height:100vh;
            display:flex; align-items:center; justify-content:center;
            font-family:'Roboto',system-ui,sans-serif;
            background:#f1f4f8;
        }

        .login-card {
            width:100%; max-width:420px;
            background:#fff;
            border-radius:16px;
            box-shadow:0 8px 40px rgba(0,0,0,.1);
            padding:40px 40px 36px;
            margin:20px;
        }

        .login-card h2 {
            font-size:1.5rem; font-weight:700;
            color:#1e293b; margin:0 0 4px;
            text-align:center;
        }
        .login-card .sub {
            color:#94a3b8; font-size:.85rem;
            margin:0 0 28px; text-align:center;
        }

        .form-label {
            font-size:.7rem; font-weight:700;
            color:#64748b; letter-spacing:.08em;
            text-transform:uppercase; margin-bottom:6px;
            display:block;
        }

        .input-wrap {
            position:relative;
            display:flex; align-items:center;
        }
        .input-icon {
            position:absolute; left:14px;
            color:#94a3b8; font-size:1rem; pointer-events:none;
        }
        .input-wrap input {
            width:100%;
            border:1.5px solid #e2e8f0;
            border-radius:10px;
            padding:11px 44px 11px 42px;
            font-size:.9rem;
            color:#1e293b;
            outline:none;
            transition:border-color .2s, box-shadow .2s;
            background:#fff;
        }
        .input-wrap input:focus {
            border-color:var(--accent);
            box-shadow:0 0 0 3px rgba(247,144,57,.14);
        }
        .eye-btn {
            position:absolute; right:12px;
            background:none; border:none;
            color:#94a3b8; cursor:pointer; padding:4px;
            border-radius:5px; transition:color .15s;
            font-size:1rem;
        }
        .eye-btn:hover { color:var(--accent); }

        .btn-login {
            width:100%;
            background:linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color:#fff; border:none;
            border-radius:10px;
            padding:13px;
            font-weight:700; font-size:.95rem;
            cursor:pointer;
            box-shadow:0 4px 18px rgba(247,144,57,.32);
            transition:transform .18s, box-shadow .18s;
            display:flex; align-items:center; justify-content:center; gap:8px;
            margin-top:8px;
        }
        .btn-login:hover {
            transform:translateY(-1px);
            box-shadow:0 6px 24px rgba(247,144,57,.42);
        }
        .btn-login:active { transform:translateY(0); }

        .error-box {
            display:flex; align-items:center; gap:10px;
            background:#fef2f2; border:1px solid #fecaca;
            border-radius:10px; padding:11px 14px;
            color:#dc2626; font-size:.875rem;
            margin-bottom:20px;
        }

        .back-link {
            display:inline-flex; align-items:center; gap:6px;
            color:#94a3b8; text-decoration:none;
            font-size:.8rem; margin-top:20px;
            transition:color .18s;
        }
        .back-link:hover { color:var(--accent); }

        .mb-3 { margin-bottom:16px; }
        .mb-4 { margin-bottom:20px; }
        .text-center { text-align:center; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Masuk</h2>
    <p class="sub">Sistem Admin Seleksi Mitra Statistik</p>

    <?php if ($error): ?>
        <div class="error-box">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-wrap">
                <i class="input-icon bi bi-person-fill"></i>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="input-icon bi bi-lock-fill"></i>
                <input type="password" name="password" id="pwdInput" placeholder="Masukkan password" required>
                <button type="button" class="eye-btn" onclick="togglePwd()" tabindex="-1">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i>
            Masuk
        </button>
    </form>

    <div class="text-center">
        <a href="../index.html" class="back-link">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('pwdInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
