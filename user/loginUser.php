<?php
require_once '../includes/session.php';
include '../includes/db.php';
include '../includes/functions.php';
// ---- Tentukan tujuan redirect setelah login (default: indexUser.php) ----
// Hanya izinkan path relatif di dalam folder toko_online untuk mencegah open redirect.
function resolveRedirect($raw)
{
    $raw = $raw ?? '';
    if ($raw === '' || preg_match('#^(https?:)?//#i', $raw) || str_starts_with($raw, '\\\\')) {
        return 'indexUser.php';
    }
    return $raw;
}
$redirect = resolveRedirect($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if (isLoggedIn()) {
    header('Location: indexUser.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $login = $pdo->prepare("SELECT * FROM user WHERE username = ?");
        $login->execute([$username]);
        $user = $login->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // PENTING: kolom primary key tabel `user` adalah `id_user`, bukan `id`.
            $_SESSION['user_id']       = $user['id_user'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_nama']     = $user['nama'];
            
header('Location: indexUser.php');
exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan — TokoKu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            background: #f5f1eb;
            font-family: Arial, Helvetica, sans-serif;
            display: grid;
            place-items: center;
            padding: 25px;
        }
        /* =================================
           CONTAINER UTAMA
        ================================= */
        .container {
            width: 100%;
            max-width: 1400px;
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow:
                0 10px 35px rgba(0, 0, 0, 0.08);
            display: grid;
            grid-template-rows: 85px 1fr;
            min-height: 700px;
        }
        /* =================================
           NAVBAR
        ================================= */
        /* .navbar {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            padding: 0 45px;
            border-bottom: 1px solid #eeeeee;
        } */
        /* LOGO */
        /* .logo {
            display: grid;
            grid-template-columns: auto auto;
            justify-content: start;
            align-items: center;
            gap: 10px;
            color: #4c2c1d;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: .3px;
        } */
        /* .logo-icon {
            font-size: 27px;
        } */
        /* MENU */
        /* .menu {
            display: grid;
            grid-auto-flow: column;
            justify-content: center;
            gap: 45px;
        } */
        /* .menu a {
            text-decoration: none;
            color: #555555;
            font-size: 14px;
            transition: .2s;
        } */
        /* .menu a:hover {
            color: #4c2c1d;
        } */
        /* NAV RIGHT */
        /* .nav-right {
            display: grid;
            grid-template-columns: auto auto;
            justify-content: end;
            align-items: center;
            gap: 28px;
        } */
        /* .search {
            font-size: 25px;
            color: #555555;
        } */
        /* .nav-login {
            border: none;
            background: #63391f;
            color: white;
            padding: 13px 30px;
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
        } */
        /* =================================
           CONTENT
        ================================= */
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 715px;
        }
        /* =================================
           BAGIAN KIRI / GAMBAR
        ================================= */
        .hero {
            position: relative;
            overflow: hidden;
            background: #5b3927;
        }
        .hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 70%;
            display: block;
        }
        /* Overlay supaya teks lebih jelas */
        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(40, 20, 10, .28);
        }
        /* TEKS DI ATAS GAMBAR */
        .hero-content {
            position: absolute;
            z-index: 2;
            top: 29%;
            left: 70px;
            color: white;
        }
        .hero-content h2 {
            font-family: Georgia, serif;
            font-size: 35px;
            line-height: 1.35;
            font-weight: 600;
        }
        .hero-line {
            width: 50px;
            height: 3px;
            background: white;
            margin-top: 25px;
        }
        /* =================================
           BAGIAN KANAN / LOGIN
        ================================= */
        .login-section {
            display: grid;
            place-items: center;
            padding: 60px;
        }
        .login-box {
            width: 100%;
            max-width: 520px;
        }
        /* JUDUL */
        .login-box h1 {
            font-family: Georgia, serif;
            font-size: 35px;
            color: #171717;
            margin-bottom: 14px;
        }
        .subtitle {
            color: #999999;
            font-size: 16px;
            margin-bottom: 35px;
        }
        /* =================================
           ALERT
        ================================= */
        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-warning {
            background: #fff7df;
            border: 1px solid #f1dfaa;
            color: #806b2c;
        }
        .alert-danger {
            background: #fff0f0;
            border: 1px solid #eccaca;
            color: #9b3c3c;
        }
        /* =================================
           FORM
        ================================= */
        form {
            display: grid;
            gap: 20px;
        }
        /* INPUT GROUP */
        .form-group {
            display: grid;
            gap: 8px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #555555;
        }
        .input-box {
            display: grid;
            grid-template-columns: 35px 1fr;
            align-items: center;
            height: 58px;
            border: 1px solid #dedbd6;
            border-radius: 8px;
            padding: 0 16px;
            transition: .2s;
            background: white;
        }
        .input-box:focus-within {
            border-color: #63391f;
            box-shadow: 0 0 0 2px rgba(99, 57, 31, .08);
        }
        .input-icon {
            font-size: 18px;
            color: #777777;
        }
        .input-box input {
            width: 100%;
            height: 100%;
            border: none;
            outline: none;
            font-size: 15px;
            color: #333333;
            background: transparent;
        }
        .input-box input::placeholder {
            color: #aaaaaa;
        }
        /* =================================
           LOGIN BUTTON
        ================================= */
        .btn-login {
            width: 100%;
            height: 58px;
            border: none;
            border-radius: 8px;
            background: #63391f;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 5px;
            transition: .2s;
        }
        .btn-login:hover {
            background: #4e2c19;
        }
        /* =================================
           LINK
        ================================= */
        .register {
            text-align: center;
            margin-top: 30px;
            color: #999999;
            font-size: 14px;
        }
        .register a {
            color: #63391f;
            font-weight: 600;
            text-decoration: none;
        }
        .register a:hover {
            text-decoration: underline;
        }
        .back {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
        }
        .back a {
            color: #888888;
            text-decoration: none;
        }
        .back a:hover {
            color: #63391f;
        }
        /* =================================
           RESPONSIVE
        ================================= */
        @media (max-width: 900px) {
            body {
                padding: 15px;
            }
            .container {
                min-height: auto;
            }
            .navbar {
                grid-template-columns: 1fr auto;
                padding: 0 25px;
            }
            .menu {
                display: none;
            }
            .content {
                grid-template-columns: 1fr;
            }
            .hero {
                min-height: 450px;
            }
            .login-section {
                padding: 50px 30px;
            }
        }
        @media (max-width: 500px) {
            .navbar {
                padding: 0 18px;
            }
            .logo span {
                display: none;
            }
            .nav-right {
                gap: 12px;
            }
            .nav-login {
                padding: 10px 18px;
            }
            .hero-content {
                left: 35px;
                top: 25%;
            }
            .hero-content h2 {
                font-size: 27px;
            }
            .hero-content span {
                color: #c5965a;
                font-weight: 700;
            }
            .login-box h1 {
                font-size: 28px;
            }
            .login-section {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- =================================
         NAVBAR
    ================================= -->
        <!-- <header class="navbar">
            <div class="logo">
                <span class="logo-icon">☕</span>
                <span>TOKOKU</span>
            </div>
            <nav class="menu">
                <a href="../index.php">Beranda</a>
                <a href="indexUser.php">Katalog</a>
                <a href="#">Tentang Kami</a>
                <a href="#">Kontak</a>
            </nav>
            <div class="nav-right">
                <span class="search">⌕</span>
                <button class="nav-login">
                    Login
                </button>
            </div>
        </header> -->
        <!-- =================================
         CONTENT
    ================================= -->
        <main class="content">
            <!-- =================================
             GAMBAR KIRI
        ================================= -->
            <section class="hero">
                <!--
                GANTI PATH GAMBAR INI
                sesuai lokasi gambar kopi kamu.
            -->
                <img
                    src="img/gambarHeader.jpeg"
                    alt="Kopi">
                <div class="hero-content">
                    <h2>
                        Nikmati kopi <span>favoritmu</span>,<br>
                        kapan saja, <span>di mana saja</span>.
                    </h2>
                    <div class="hero-line"></div>
                </div>
            </section>
            <!-- =================================
             LOGIN KANAN
        ================================= -->
            <section class="login-section">
                <div class="login-box">
                    <h1>
                        Selamat Datang Kembali!
                    </h1>
                    <p class="subtitle">
                        Login untuk melanjutkan pesananmu.
                    </p>
                    <!-- =================================
                     REDIRECT ALERT
                ================================= -->
                    <?php if ($redirect !== 'indexUser.php'): ?>
                        <div class="alert alert-warning">
                            Silakan login terlebih dahulu
                            untuk membuka halaman katalog.
                        </div>
                    <?php endif; ?>
                    <!-- =================================
                     ERROR LOGIN
                ================================= -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <!-- =================================
                     FORM LOGIN
                ================================= -->
                    <form method="POST">
                        <!-- Tetap dipertahankan -->
                        <input
                            type="hidden"
                            name="redirect"
                            value="<?= htmlspecialchars($redirect) ?>">
                        <!-- USERNAME -->
                        <div class="form-group">
                            <label class="form-label">
                                Username
                            </label>
                            <div class="input-box">
                                <span class="input-icon">
                                    👤
                                </span>
                                <input
                                    type="text"
                                    name="username"
                                    placeholder="Masukkan username"
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                    required>
                            </div>
                        </div>
                        <!-- PASSWORD -->
                        <div class="form-group">
                            <label class="form-label">
                                Password
                            </label>
                            <div class="input-box">
                                <span class="input-icon">
                                    🔒
                                </span>
                                <input
                                    type="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    required>
                            </div>
                        </div>
                        <!-- BUTTON -->
                        <button
                            type="submit"
                            class="btn-login">
                            Login
                        </button>
                    </form>
                    <!-- =================================
                     REGISTER
                ================================= -->
                    <p class="register">
                        Belum punya akun?
                        <a href="daftar.php">
                            Daftar Sekarang
                        </a>
                    </p>
                    <!-- =================================
                     BACK
                ================================= -->
                    <p class="back">
                        <a href="../index.php">
                            ← Kembali ke toko
                        </a>
                    </p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
