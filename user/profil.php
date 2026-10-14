<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: loginUser.php');
    exit;
}
$stmtUser = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    session_destroy();
    header('Location: loginUser.php');
    exit;
}
$errors = [];
$success = '';
$tab = $_GET['tab'] ?? 'info';
if (!in_array($tab, ['info', 'password'], true)) {
    $tab = 'info';
}
// ==== UPDATE INFORMASI AKUN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $tab = 'info';
    $nama     = trim($_POST['nama'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $username = trim($_POST['username'] ?? '');
    if (!$nama || !$alamat || !$username) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (strlen($nama) > 25) {
        $errors[] = 'Nama maksimal 25 karakter.';
    } elseif (strlen($alamat) > 50) {
        $errors[] = 'Alamat maksimal 50 karakter.';
    } elseif (strlen($username) > 25) {
        $errors[] = 'Username maksimal 25 karakter.';
    } else {
        // Cek username dipakai user lain
        $cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $cek->execute([$username, $currentUser['id_user']]);
        if ($cek->fetch()) {
            $errors[] = 'Username sudah digunakan oleh akun lain.';
        } else {
            $stmt = $pdo->prepare("UPDATE user SET nama = ?, alamat = ?, username = ? WHERE id_user = ?");
            $stmt->execute([$nama, $alamat, $username, $currentUser['id_user']]);
            // Refresh session & data lokal biar langsung sinkron tanpa perlu login ulang
            $_SESSION['user_nama']     = $nama;
            $_SESSION['user_username'] = $username;
            $currentUser['nama']       = $nama;
            $currentUser['alamat']     = $alamat;
            $currentUser['username']   = $username;
            $success = 'Informasi akun berhasil diperbarui.';
        }
    }
}
// ==== UBAH PASSWORD ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $tab = 'password';
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!$current_password || !$new_password || !$confirm_password) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (!password_verify($current_password, $currentUser['password'])) {
        $errors[] = 'Password saat ini salah.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    } elseif (password_verify($new_password, $currentUser['password'])) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET password = ? WHERE id_user = ?")->execute([$hash, $currentUser['id_user']]);
        $success = 'Password berhasil diubah.';
    }
}
$namaUser = $currentUser['nama'];
// ==== STATISTIK PESANAN ====
$stmtStat = $pdo->prepare("SELECT COUNT(*) AS total_pesanan,
                                   COALESCE(SUM(CASE WHEN status != 'Dibatalkan' THEN total_bayar ELSE 0 END), 0) AS total_belanja
                            FROM orders WHERE id_user = ?");
$stmtStat->execute([$currentUser['id_user']]);
$stat = $stmtStat->fetch();
$stmtSelesai = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE id_user = ? AND status = 'Selesai'");
$stmtSelesai->execute([$currentUser['id_user']]);
$totalSelesai = $stmtSelesai->fetchColumn();
// Inisial untuk avatar
$parts = preg_split('/\s+/', trim($namaUser));
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : substr($parts[0], 1, 1)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brown: #70401f;
            --brown-dark: #4d2a17;
            --brown-light: #8b542d;
            --cream: #fff9ef;
            --cream-2: #f7ecda;
            --cream-3: #efe0c9;
            --gold: #e5a83c;
            --gold-soft: #f6dfae;
            --text: #3b281c;
            --muted: #8b7b6d;
            --border: #eadfce;
            --white: #ffffff;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 8% 8%,
                    rgba(229, 168, 60, .12),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 22%,
                    rgba(112, 64, 31, .08),
                    transparent 30%
                ),
                var(--cream);
        }
        /* =========================
           MAIN CONTAINER
        ========================= */
        .home-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 32px 35px 70px;
        }
        /* =========================
           TOP TITLE
        ========================= */
        .page-heading {
            margin-bottom: 22px;
        }
        .page-heading .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--brown);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .page-heading h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 850;
            letter-spacing: -1.2px;
            color: var(--brown-dark);
        }
        .page-heading p {
            margin: 7px 0 0;
            font-size: 14px;
            color: var(--muted);
        }
        /* =========================
           PROFILE HERO
        ========================= */
        .profile-hero {
            position: relative;
            overflow: hidden;
            min-height: 245px;
            padding: 34px 38px;
            margin-bottom: 25px;
            border-radius: 26px;
            background:
                linear-gradient(
                    135deg,
                    #4d2a17 0%,
                    #70401f 55%,
                    #8b542d 100%
                );
            box-shadow:
                0 18px 45px rgba(77, 42, 23, .17);
        }
        .profile-hero::before {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            right: -100px;
            top: -170px;
            background: rgba(255,255,255,.055);
        }
        .profile-hero::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: 180px;
            bottom: -170px;
            background: rgba(229,168,60,.09);
        }
        .coffee-decoration {
            position: absolute;
            right: 42px;
            bottom: 22px;
            font-size: 100px;
            color: rgba(255,255,255,.055);
            transform: rotate(-10deg);
        }
        .profile-hero-content {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 23px;
        }
        /* =========================
           AVATAR
        ========================= */
        .avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .avatar-circle {
            width: 92px;
            height: 92px;
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(
                    145deg,
                    #f7dfae,
                    #d69a4b
                );
            color: var(--brown-dark);
            font-size: 29px;
            font-weight: 900;
            letter-spacing: -1px;
            border: 4px solid rgba(255,255,255,.20);
            box-shadow:
                0 10px 25px rgba(0,0,0,.16);
        }
        .avatar-dot {
            position: absolute;
            width: 15px;
            height: 15px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: #69a55b;
            border: 3px solid var(--brown-dark);
        }
        /* =========================
           PROFILE NAME
        ========================= */
        .profile-main {
            color: white;
        }
        .welcome-text {
            font-size: 11px;
            color: #e9cfa9;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .profile-name {
            font-size: 29px;
            font-weight: 850;
            letter-spacing: -1px;
            line-height: 1.15;
        }
        .profile-username {
            margin-top: 7px;
            color: rgba(255,255,255,.68);
            font-size: 13px;
        }
        .profile-username i {
            margin-right: 4px;
        }
        /* =========================
           STATS
        ========================= */
        .profile-stats {
            position: relative;
            z-index: 4;
            display: grid;
            grid-template-columns:
                repeat(3, minmax(150px, 1fr));
            gap: 12px;
            margin-left: auto;
        }
        .profile-stat {
            min-width: 150px;
            padding: 16px 17px;
            border-radius: 16px;
            background: rgba(255,255,255,.095);
            border: 1px solid rgba(255,255,255,.11);
            backdrop-filter: blur(8px);
        }
        .profile-stat-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            margin-bottom: 10px;
            background: rgba(255,255,255,.10);
            color: #f6d28f;
            font-size: 14px;
        }
        .profile-stat-val {
            font-size: 20px;
            font-weight: 850;
            color: white;
            letter-spacing: -.5px;
        }
        .profile-stat-label {
            margin-top: 2px;
            font-size: 9px;
            color: rgba(255,255,255,.57);
            text-transform: uppercase;
            letter-spacing: .8px;
            font-weight: 700;
        }
        /* =========================
           ALERT
        ========================= */
        .custom-alert {
            border: none;
            border-radius: 13px;
            padding: 13px 16px;
            margin-bottom: 18px;
            font-size: 13px;
            box-shadow: 0 5px 15px rgba(0,0,0,.04);
        }
        .custom-alert.success {
            background: #edf7e9;
            color: #45683d;
        }
        .custom-alert.error {
            background: #fff0ed;
            color: #8c4236;
        }
        /* =========================
           SETTINGS LAYOUT
        ========================= */
        .settings-layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }
        /* =========================
           SETTINGS MENU
        ========================= */
        .settings-menu {
            padding: 9px;
            background: rgba(255,255,255,.80);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow:
                0 8px 25px rgba(77,42,23,.05);
        }
        .menu-title {
            padding: 13px 14px 9px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .settings-menu a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 13px;
            margin-bottom: 4px;
            border-radius: 12px;
            color: #6f6258;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .2s ease;
        }
        .settings-menu a:last-child {
            margin-bottom: 0;
        }
        .settings-menu a:hover {
            background: var(--cream-2);
            color: var(--brown-dark);
            transform: translateX(2px);
        }
        .settings-menu a.active {
            color: white;
            background:
                linear-gradient(
                    135deg,
                    var(--brown),
                    var(--brown-light)
                );
            box-shadow:
                0 7px 17px rgba(112,64,31,.18);
        }
        .settings-menu a.active::after {
            content: "";
            position: absolute;
            right: 12px;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #f3c76f;
        }
        .settings-menu a i {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }
        .settings-menu .logout {
            color: #a34d40;
        }
        .settings-menu .logout:hover {
            background: #fff0ed;
            color: #963d31;
        }
        .menu-divider {
            height: 1px;
            background: var(--border);
            margin: 8px 6px;
        }
        /* =========================
           FORM CARD
        ========================= */
        .card-box {
            position: relative;
            background: rgba(255,255,255,.94);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 30px 32px;
            box-shadow:
                0 9px 28px rgba(77,42,23,.055);
            overflow: hidden;
        }
        .card-box::before {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            right: -50px;
            top: -50px;
            background: var(--cream-2);
        }
        .card-top {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-bottom: 27px;
        }
        .card-icon {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--cream-2);
            color: var(--brown);
            font-size: 18px;
        }
        .card-heading h5 {
            margin: 0;
            font-size: 19px;
            font-weight: 850;
            color: var(--brown-dark);
            letter-spacing: -.4px;
        }
        .card-heading .subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }
        /* =========================
           FORM
        ========================= */
        .form-group {
            margin-bottom: 21px;
        }
        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 7px;
            font-size: 10px;
            font-weight: 800;
            color: #76685c;
            text-transform: uppercase;
            letter-spacing: .9px;
        }
        .form-label i {
            color: var(--brown);
        }
        .form-control {
            border: 1px solid #e4d7c6;
            border-radius: 11px;
            font-size: 13px;
            padding: 12px 14px;
            color: var(--text);
            background: #fffdf9;
            transition: .2s ease;
        }
        .form-control:hover {
            border-color: #d8c3aa;
        }
        .form-control:focus {
            border-color: var(--brown);
            background: white;
            box-shadow:
                0 0 0 3px rgba(112,64,31,.08);
        }
        .form-control::placeholder {
            color: #b6a99c;
        }
        .form-text {
            margin-top: 6px;
            color: #a19487;
            font-size: 10px;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 92px;
        }
        /* =========================
           SAVE BUTTON
        ========================= */
        .form-bottom {
            display: flex;
            justify-content: flex-end;
            padding-top: 5px;
        }
        .btn-save {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 11px;
            padding: 11px 20px;
            background:
                linear-gradient(
                    135deg,
                    var(--brown),
                    var(--brown-light)
                );
            color: white;
            font-size: 12px;
            font-weight: 750;
            box-shadow:
                0 7px 16px rgba(112,64,31,.18);
            transition: .2s ease;
        }
        .btn-save:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow:
                0 10px 20px rgba(112,64,31,.23);
        }
        /* =========================
           PASSWORD INFO
        ========================= */
        .security-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 23px;
            border-radius: 11px;
            background: #fff8e9;
            border: 1px solid #f2dfb8;
            color: #86683c;
            font-size: 11px;
            line-height: 1.5;
        }
        .security-note i {
            margin-top: 1px;
            color: var(--gold);
            font-size: 15px;
        }
        /* =========================
           FOOTER
        ========================= */
        footer {
            border-top: 1px solid var(--border);
            padding: 2rem 0;
            color: #999;
            font-size: .8rem;
        }
        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 1150px) {
            .profile-hero-content {
                flex-wrap: wrap;
            }
            .profile-stats {
                width: 100%;
                margin-left: 0;
            }
        }
        @media (max-width: 1000px) {
            .settings-layout {
                grid-template-columns: 230px minmax(0, 1fr);
            }
        }
        @media (max-width: 800px) {
            .home-container {
                padding: 22px 18px 45px;
            }
            .page-heading h1 {
                font-size: 28px;
            }
            .profile-hero {
                padding: 28px 24px;
                border-radius: 21px;
            }
            .profile-stats {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
            .profile-stat {
                min-width: 0;
            }
            .settings-layout {
                grid-template-columns: 1fr;
            }
            .settings-menu {
                display: grid;
                grid-template-columns:
                    repeat(2, 1fr);
                gap: 5px;
            }
            .menu-title,
            .menu-divider {
                display: none;
            }
            .settings-menu a {
                margin-bottom: 0;
            }
            .settings-menu .logout {
                grid-column: span 2;
            }
        }
        @media (max-width: 600px) {
            .profile-hero-content {
                align-items: flex-start;
                flex-direction: column;
            }
            .profile-name {
                font-size: 25px;
            }
            .profile-stats {
                grid-template-columns: 1fr;
                width: 100%;
            }
            .profile-stat {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 14px;
            }
            .profile-stat-icon {
                margin: 0;
            }
            .profile-stat-label {
                margin-top: 1px;
            }
            .profile-stat-val {
                margin-left: auto;
            }
            .card-box {
                padding: 25px 20px;
            }
            .settings-menu {
                grid-template-columns: 1fr;
            }
            .settings-menu .logout {
                grid-column: auto;
            }
            .form-bottom {
                justify-content: stretch;
            }
            .btn-save {
                width: 100%;
            }
        }
        @media (max-width: 380px) {
            .home-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .profile-hero {
                padding: 23px 18px;
            }
            .avatar-circle {
                width: 78px;
                height: 78px;
                border-radius: 23px;
                font-size: 24px;
            }
            .profile-name {
                font-size: 22px;
            }
            .card-box {
                padding: 22px 17px;
            }
        }
    </style>
</head>
<body>
<?php
$base = '../';
$activeNav = 'profil';
include '../includes/navbar.php';
?>
<div class="home-container">
    <!-- =========================
         PAGE HEADING
    ========================== -->
    <div class="page-heading">
        <div class="eyebrow">
            <i class="bi bi-person-circle"></i>
            Account
        </div>
        <h1>Profil Saya</h1>
        <p>
            Kelola informasi pribadi dan keamanan akun TokoKu kamu.
        </p>
    </div>
    <!-- =========================
         PROFILE HERO
    ========================== -->
    <div class="profile-hero">
        <i class="bi bi-cup-hot coffee-decoration"></i>
        <div class="profile-hero-content">
            <div class="avatar-wrap">
                <div class="avatar-circle">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="avatar-dot"></div>
            </div>
            <div class="profile-main">
                <div class="welcome-text">
                    Selamat datang kembali
                </div>
                <div class="profile-name">
                    <?= htmlspecialchars($namaUser) ?>
                </div>
                <div class="profile-username">
                    <i class="bi bi-at"></i>
                    <?= htmlspecialchars($currentUser['username']) ?>
                </div>
            </div>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="profile-stat-icon">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div>
                        <div class="profile-stat-val">
                            <?= (int) $stat['total_pesanan'] ?>
                        </div>
                        <div class="profile-stat-label">
                            Total Pesanan
                        </div>
                    </div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="profile-stat-val">
                            <?= (int) $totalSelesai ?>
                        </div>
                        <div class="profile-stat-label">
                            Pesanan Selesai
                        </div>
                    </div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="profile-stat-val"
                             style="font-size:16px">
                            <?= formatRupiah($stat['total_belanja']) ?>
                        </div>
                        <div class="profile-stat-label">
                            Total Belanja
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- =========================
         ALERT
    ========================== -->
    <?php if ($success): ?>
        <div class="custom-alert success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="custom-alert error">
            <?php foreach ($errors as $e): ?>
                <div>
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= htmlspecialchars($e) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <!-- =========================
         SETTINGS
    ========================== -->
    <div class="settings-layout">
        <!-- MENU -->
        <div class="settings-menu">
            <div class="menu-title">
                Pengaturan Akun
            </div>
            <a href="?tab=info"
               class="<?= $tab === 'info' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Informasi Akun</span>
            </a>
            <a href="?tab=password"
               class="<?= $tab === 'password' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock"></i>
                <span>Ubah Password</span>
            </a>
            <div class="menu-divider"></div>
            <a href="riwayat_pesanan.php">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Pesanan</span>
            </a>
            <a href="logoutUser.php"
               class="logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
        <!-- CONTENT -->
        <div>
            <?php if ($tab === 'info'): ?>
                <!-- INFORMASI AKUN -->
                <div class="card-box">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <div class="card-heading">
                            <h5>Informasi Akun</h5>
                            <p class="subtitle">
                                Perbarui nama, alamat, dan username akunmu.
                            </p>
                        </div>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-person"></i>
                                Nama Lengkap
                            </label>
                            <input
                                type="text"
                                name="nama"
                                class="form-control"
                                maxlength="25"
                                value="<?= htmlspecialchars($currentUser['nama']) ?>"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-geo-alt"></i>
                                Alamat
                            </label>
                            <textarea
                                name="alamat"
                                class="form-control"
                                rows="3"
                                maxlength="50"
                                required
                            ><?= htmlspecialchars($currentUser['alamat']) ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i>
                                Maksimal 50 karakter. Alamat ini akan otomatis muncul saat checkout.
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-at"></i>
                                Username
                            </label>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                maxlength="25"
                                value="<?= htmlspecialchars($currentUser['username']) ?>"
                                required
                            >
                            <div class="form-text">
                                Digunakan untuk login. Username harus unik.
                            </div>
                        </div>
                        <div class="form-bottom">
                            <button
                                type="submit"
                                name="update_profile"
                                class="btn-save"
                            >
                                <i class="bi bi-check2"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- PASSWORD -->
                <div class="card-box">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="card-heading">
                            <h5>Keamanan Akun</h5>
                            <p class="subtitle">
                                Perbarui password untuk menjaga akunmu tetap aman.
                            </p>
                        </div>
                    </div>
                    <div class="security-note">
                        <i class="bi bi-shield-check"></i>
                        <span>
                            Gunakan password minimal 6 karakter dan hindari menggunakan
                            password yang sama dengan akun lain.
                        </span>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-key"></i>
                                Password Saat Ini
                            </label>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-lock"></i>
                                Password Baru
                            </label>
                            <input
                                type="password"
                                name="new_password"
                                class="form-control"
                                placeholder="Minimal 6 karakter"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-lock-fill"></i>
                                Konfirmasi Password Baru
                            </label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="form-bottom">
                            <button
                                type="submit"
                                name="change_password"
                                class="btn-save"
                            >
                                <i class="bi bi-shield-check"></i>
                                Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>