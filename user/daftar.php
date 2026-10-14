<?php
require_once '../includes/session.php';
include '../includes/db.php';
include '../includes/functions.php';
if (isLoggedIn()) {
    header('Location: indexUser.php');
    exit;
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = trim($_POST['username'] ?? '');
    $nama           = trim($_POST['nama'] ?? '');
    $alamat         = trim($_POST['alamat'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm        = $_POST['confirm_password'] ?? '';
    if (!$username || !$nama || !$alamat || !$password || !$confirm) {
        $error = 'Harap isi semua field.';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetch()) {
            $error = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO user (username, nama, alamat, password)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $username,
                $nama,
                $alamat,
                $hash
            ]);
            $success = 'Akun berhasil dibuat. Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>Daftar — TokoKu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            background: #f5f1eb;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            display: grid;
            place-items: center;
            padding: 30px;
        }
        /* =========================================
           CONTAINER UTAMA
        ========================================= */
        .register-container {
            width: 100%;
            max-width: 1350px;
            max-height: 900px;
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.08);
        }
        /* =========================================
           BAGIAN GAMBAR
        ========================================= */
        .image-section {
            position: relative;
            overflow: hidden;
            background: #5b3927;
        }
        h2 span {
            color: #c5965a;
            font-weight: 700;
        }
        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0.7;
            display: block;
        }
        /*
            INNER SHADOW BAGIAN BAWAH
            Shadow masuk ke dalam gambar,
            bukan keluar dari gambar.
        */
        .image-section::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 230px;
            pointer-events: none;
            background:
                linear-gradient(to top,
                    rgba(35, 18, 10, 0.55),
                    rgba(35, 18, 10, 0.20) 45%,
                    transparent);
        }
        /*
            Kalau ingin shadow lebih terasa
            seperti bayangan masuk ke gambar,
            bisa memakai inset ini juga.
        */
        .image-section {
            box-shadow:
                inset 0 -90px 100px -55px rgba(30, 15, 8, 0.85);
        }
        /* =========================================
           TEKS DI ATAS GAMBAR
        ========================================= */
        .image-text {
            position: absolute;
            z-index: 2;
            left: 75px;
            top: 20%;
            color: white;
        }
        .image-text h2 {
            font-family: Georgia, serif;
            font-size: 38px;
            line-height: 1.25;
            font-weight: 600;
            text-shadow:
                0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .image-line {
            width: 55px;
            height: 3px;
            background: white;
            margin-top: 28px;
        }
        /* =========================================
           BAGIAN FORM
        ========================================= */
        .form-section {
            display: grid;
            align-items: start;
            padding: 65px 75px;
        }
        .form-wrapper {
            width: 100%;
            max-width: 610px;
            margin: 0 auto;
        }
        /* =========================================
           JUDUL
        ========================================= */
        .form-wrapper h1 {
            font-family: Georgia, serif;
            font-size: 38px;
            color: #171717;
            margin-bottom: 12px;
        }
        .subtitle {
            color: #999;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
            max-width: 500px;
        }
        /* =========================================
           ALERT
        ========================================= */
        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fff0f0;
            border: 1px solid #eccaca;
            color: #9b3c3c;
        }
        .alert-success {
            background: #eef9f0;
            border: 1px solid #c9e5ce;
            color: #397044;
        }
        .alert-success a {
            color: #63391f;
            font-weight: 600;
        }
        /* =========================================
           FORM
        ========================================= */
        form {
            display: grid;
            gap: 16px;
        }
        .form-group {
            display: grid;
            gap: 7px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        /* =========================================
           INPUT
        ========================================= */
        .input-box {
            height: 55px;
            display: grid;
            grid-template-columns: 35px 1fr;
            align-items: center;
            border: 1px solid #dedbd6;
            border-radius: 8px;
            padding: 0 15px;
            background: #fff;
            transition: .2s;
        }
        .input-box:focus-within {
            border-color: #63391f;
            box-shadow:
                0 0 0 2px rgba(99, 57, 31, 0.07);
        }
        .input-icon {
            color: #777;
            font-size: 17px;
        }
        .input-box input {
            width: 100%;
            height: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-size: 14px;
            color: #333;
        }
        .input-box input::placeholder {
            color: #aaa;
        }
        /* =========================================
           TEXTAREA ALAMAT
        ========================================= */
        .textarea-box {
            display: grid;
            grid-template-columns: 35px 1fr;
            align-items: start;
            border: 1px solid #dedbd6;
            border-radius: 8px;
            padding: 14px 15px;
            background: #fff;
            min-height: 85px;
            transition: .2s;
        }
        .textarea-box:focus-within {
            border-color: #63391f;
            box-shadow:
                0 0 0 2px rgba(99, 57, 31, 0.07);
        }
        .textarea-box textarea {
            width: 100%;
            min-height: 55px;
            resize: vertical;
            border: none;
            outline: none;
            background: transparent;
            font-family: inherit;
            font-size: 14px;
            color: #333;
        }
        .textarea-box textarea::placeholder {
            color: #aaa;
        }
        /* =========================================
           BUTTON DAFTAR
        ========================================= */
        .btn-register {
            width: 100%;
            height: 56px;
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
        .btn-register:hover {
            background: #4e2c19;
        }
        /* =========================================
           LINK LOGIN
        ========================================= */
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 14px;
        }
        .login-link a {
            color: #63391f;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        /* =========================================
           KEMBALI
        ========================================= */
        .back-link {
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
        }
        .back-link a {
            color: #999;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #63391f;
        }
        /* =========================================
           RESPONSIVE TABLET
        ========================================= */
        @media (max-width: 950px) {
            body {
                padding: 15px;
            }
            .register-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .image-section {
                min-height: 450px;
            }
            .form-section {
                padding: 50px 40px;
            }
            .form-wrapper {
                max-width: 650px;
            }
        }
        /* =========================================
           RESPONSIVE HP
        ========================================= */
        @media (max-width: 550px) {
            body {
                padding: 0;
            }
            .register-container {
                width: 100%;
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
            }
            .image-section {
                min-height: 350px;
            }
            .image-text {
                left: 35px;
                top: 25%;
            }
            .image-text h2 {
                font-size: 29px;
            }
            .form-section {
                padding: 40px 22px;
            }
            .form-wrapper h1 {
                font-size: 30px;
            }
            .subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- =========================================
         GAMBAR
    ========================================= -->
        <section class="image-section">
            <img
                src="img/gambarDaftar.jpeg"
                alt="Kopi">
            <div class="image-text">
                <h2>
                    <span>Kopi baik</span>,<br>
                    untuk hari yang<br>
                    lebih baik.
                </h2>
                <div class="image-line"></div>
            </div>
        </section>
        <!-- =========================================
         FORM DAFTAR
    ========================================= -->
        <section class="form-section">
            <div class="form-wrapper">
                <h1>
                    Buat Akun Baru
                </h1>
                <p class="subtitle">
                    Daftar dan nikmati pengalaman pesan kopi
                    dengan lebih mudah.
                </p>
                <!-- ERROR -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <!-- SUCCESS -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <a href="loginUser.php">
                            Login sekarang →
                        </a>
                    </div>
                <?php endif; ?>
                <!-- FORM -->
                <form method="POST">
                    <!-- NAMA -->
                    <div class="form-group">
                        <label class="form-label">
                            Nama Lengkap
                        </label>
                        <div class="input-box">
                            <span class="input-icon">
                                👤
                            </span>
                            <input
                                type="text"
                                name="nama"
                                placeholder="Masukkan nama lengkap"
                                value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                required>
                        </div>
                    </div>
                    <!-- ALAMAT -->
                    <div class="form-group">
                        <label class="form-label">
                            Alamat
                        </label>
                        <div class="textarea-box">
                            <span class="input-icon">
                                📍
                            </span>
                            <textarea
                                name="alamat"
                                placeholder="Masukkan alamat lengkap"
                                required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
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
                                placeholder="Minimal 6 karakter"
                                required>
                        </div>
                    </div>
                    <!-- KONFIRMASI PASSWORD -->
                    <div class="form-group">
                        <label class="form-label">
                            Konfirmasi Password
                        </label>
                        <div class="input-box">
                            <span class="input-icon">
                                🔒
                            </span>
                            <input
                                type="password"
                                name="confirm_password"
                                placeholder="Ulangi password"
                                required>
                        </div>
                    </div>
                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="btn-register">
                        Daftar
                    </button>
                </form>
                <!-- LOGIN -->
                <p class="login-link">
                    Sudah punya akun?
                    <a href="loginUser.php">
                        Login
                    </a>
                </p>
                <!-- KEMBALI -->
                <p class="back-link">
                    <a href="../index.php">
                        ← Kembali ke toko
                    </a>
                </p>
            </div>
        </section>
    </div>
</body>
</html>
