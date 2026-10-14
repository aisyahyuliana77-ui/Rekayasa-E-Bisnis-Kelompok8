<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: user/loginUser.php');
    exit;
}
$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id_order = ? AND id_user = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: katalog.php');
    exit;
}
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE id_order = ?");
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil — TokoKu</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
        rel="stylesheet">
    <style>
        /* =====================================================
           THEME
        ===================================================== */
        :root {
            --brown: #70401f;
            --brown-dark: #4d2a17;
            --brown-light: #986039;
            --cream: #fff9ef;
            --cream-2: #f7ecda;
            --cream-3: #efe0c9;
            --gold: #e5a83c;
            --gold-light: #f6d58d;
            --text: #3b281c;
            --muted: #8b7b6d;
            --border: #eadfce;
            --white: #ffffff;
        }
        * {
            box-sizing: border-box;
        }
        html {
            scroll-behavior: smooth;
        }
        body {
            margin: 0;
            font-family:
                'Segoe UI',
                Arial,
                sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 0% 10%,
                    rgba(229, 168, 60, .10),
                    transparent 22%),
                radial-gradient(circle at 100% 80%,
                    rgba(112, 64, 31, .08),
                    transparent 25%),
                #fffdf9;
        }
        /* =====================================================
           MAIN CONTAINER
        ===================================================== */
        .success-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding:
                28px 35px 70px;
        }
        /* =====================================================
           SUCCESS HERO
        ===================================================== */
        .success-hero {
            position: relative;
            overflow: hidden;
            min-height: 255px;
            border-radius: 22px;
            margin-bottom: 25px;
            background:
                radial-gradient(circle at 83% 25%,
                    rgba(255, 255, 255, .18) 0 7%,
                    transparent 8%),
                radial-gradient(circle at 92% 72%,
                    rgba(255, 255, 255, .10) 0 13%,
                    transparent 14%),
                linear-gradient(110deg,
                    #633216 0%,
                    #7d421f 42%,
                    #b66f38 100%);
            box-shadow:
                0 15px 35px rgba(112, 64, 31, .15);
        }
        .success-hero::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            right: -180px;
            top: -310px;
            border-radius: 50%;
            background:
                rgba(255, 255, 255, .09);
        }
        .success-hero::after {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            right: 80px;
            bottom: -260px;
            border-radius: 50%;
            border:
                55px solid rgba(255, 255, 255, .07);
        }
        .hero-content {
            position: relative;
            z-index: 3;
            padding: 35px 45px;
            color: white;
        }
        .success-icon {
            width: 67px;
            height: 67px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background:
                rgba(255, 255, 255, .95);
            color: #6d9d54;
            font-size: 31px;
            margin-bottom: 17px;
            box-shadow:
                0 10px 25px rgba(50, 25, 10, .20);
        }
        .hero-small {
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color:
                rgba(255, 255, 255, .82);
        }
        .hero-title {
            margin: 0;
            font-family:
                Georgia,
                'Times New Roman',
                serif;
            font-size:
                clamp(35px, 5vw, 57px);
            line-height: 1;
            font-weight: 700;
            letter-spacing: -1.5px;
        }
        .hero-description {
            max-width: 620px;
            margin:
                14px 0 0;
            font-size: 15px;
            line-height: 1.6;
            color:
                rgba(255, 255, 255, .91);
        }
        .order-code {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 17px;
            padding:
                8px 13px;
            border:
                1px solid rgba(255, 255, 255, .25);
            border-radius: 9px;
            background:
                rgba(255, 255, 255, .10);
            font-size: 13px;
            color: white;
        }
        .order-code strong {
            color: #fff1c9;
        }
        /* =====================================================
           DECORATIVE CUP
        ===================================================== */
        .coffee-decoration {
            position: absolute;
            z-index: 2;
            right: 90px;
            top: 52px;
            width: 145px;
            height: 145px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background:
                rgba(255, 241, 216, .80);
            box-shadow:
                0 18px 35px rgba(72, 38, 18, .18);
        }
        .coffee-cup {
            position: relative;
            width: 78px;
            height: 61px;
            border-radius:
                0 0 38px 38px;
            background: #fff8eb;
            border:
                7px solid #f4e6d0;
            box-shadow:
                inset 0 -7px 0 rgba(112, 64, 31, .08);
        }
        .coffee-cup::before {
            content: "";
            position: absolute;
            width: 43px;
            height: 35px;
            left: 11px;
            top: 5px;
            border-radius: 50%;
            background: #5b3018;
        }
        .coffee-cup::after {
            content: "";
            position: absolute;
            width: 26px;
            height: 25px;
            right: -27px;
            top: 9px;
            border:
                7px solid #f4e6d0;
            border-left: 0;
            border-radius:
                0 50% 50% 0;
        }
        /* =====================================================
           CONTENT GRID
        ===================================================== */
        .content-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.55fr) minmax(340px, .85fr);
            gap: 25px;
            align-items: start;
        }
        /* =====================================================
           CARD
        ===================================================== */
        .order-card {
            background:
                rgba(255, 255, 255, .91);
            border:
                1px solid var(--border);
            border-radius: 18px;
            padding:
                28px 30px;
            box-shadow:
                0 8px 27px rgba(112, 64, 31, .06);
            backdrop-filter:
                blur(5px);
        }
        .card-section {
            padding-bottom: 23px;
            margin-bottom: 23px;
            border-bottom:
                1px solid var(--border);
        }
        .card-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 800;
            color:
                var(--brown-dark);
        }
        .section-title i {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background:
                var(--cream-2);
            color:
                var(--brown);
            font-size: 16px;
        }
        /* =====================================================
           ADDRESS
        ===================================================== */
        .receiver {
            font-size: 14px;
            font-weight: 700;
            color:
                #4d392b;
            margin-bottom: 5px;
        }
        .address {
            font-size: 13px;
            line-height: 1.65;
            color:
                var(--muted);
        }
        .note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 10px;
            padding:
                9px 11px;
            border-radius: 9px;
            background:
                var(--cream);
            font-size: 12px;
            color:
                var(--muted);
        }
        .note i {
            color:
                var(--brown);
            margin-top: 1px;
        }
        /* =====================================================
           INFO ROW
        ===================================================== */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 8px 0;
            font-size: 13px;
        }
        .info-label {
            color:
                var(--muted);
        }
        .info-value {
            font-weight: 600;
            color:
                #4d392b;
            text-align: right;
        }
        /* =====================================================
           STATUS
        ===================================================== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding:
                5px 10px;
            border-radius: 20px;
            background:
                #fff0c9;
            color:
                #91651d;
            font-size: 11px;
            font-weight: 700;
        }
        /* =====================================================
           PRODUCT
        ===================================================== */
        .product-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 9px 0;
            font-size: 13px;
        }
        .product-name {
            color:
                #66574c;
            line-height: 1.45;
        }
        .product-price {
            white-space: nowrap;
            font-weight: 600;
            color:
                #4d392b;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding:
                7px 0;
            font-size: 13px;
        }
        .summary-line span:first-child {
            color:
                var(--muted);
        }
        .summary-line span:last-child {
            color:
                #4d392b;
            font-weight: 600;
        }
        .summary-divider {
            border: 0;
            border-top:
                1px solid var(--border);
            margin:
                15px 0;
        }
        /* =====================================================
           TOTAL
        ===================================================== */
        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 7px;
            padding:
                15px 16px;
            border-radius: 12px;
            background:
                linear-gradient(100deg,
                    #fff5e6,
                    #fffaf3);
            border:
                1px solid #ead4b9;
        }
        .total-label {
            font-size: 13px;
            font-weight: 700;
            color:
                var(--brown-dark);
        }
        .total-label small {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            font-weight: 500;
            color:
                var(--muted);
        }
        .total-price {
            white-space: nowrap;
            font-size: 20px;
            font-weight: 800;
            color:
                var(--brown);
        }
        /* =====================================================
           RIGHT SIDE
        ===================================================== */
        .side-card {
            position: sticky;
            top: 90px;
            background:
                rgba(255, 255, 255, .94);
            border:
                1px solid var(--border);
            border-radius: 18px;
            padding:
                28px;
            box-shadow:
                0 9px 28px rgba(112, 64, 31, .07);
        }
        .side-heading {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 800;
            color:
                var(--brown-dark);
        }
        .side-heading i {
            width: 37px;
            height: 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background:
                var(--cream-2);
            color:
                var(--brown);
        }
        .success-message {
            padding:
                15px;
            margin-bottom: 20px;
            border-radius: 12px;
            background:
                #f5f9ef;
            border:
                1px solid #d9e7c9;
            color:
                #587440;
            font-size: 12px;
            line-height: 1.55;
        }
        .success-message i {
            margin-right: 5px;
        }
        /* =====================================================
           BUTTON
        ===================================================== */
        .btn-main {
            display: block;
            width: 100%;
            padding:
                13px;
            border: none;
            border-radius: 11px;
            background:
                linear-gradient(135deg,
                    #6f3517,
                    #914d25);
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            box-shadow:
                0 8px 18px rgba(112, 64, 31, .15);
            transition:
                .2s ease;
        }
        .btn-main:hover {
            color: white;
            transform:
                translateY(-1px);
            box-shadow:
                0 10px 23px rgba(112, 64, 31, .22);
        }
        .btn-secondary-custom {
            display: block;
            width: 100%;
            padding:
                12px;
            margin-top: 10px;
            border:
                1px solid #cfae8e;
            border-radius: 11px;
            background:
                transparent;
            color:
                var(--brown);
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition:
                .2s ease;
        }
        .btn-secondary-custom:hover {
            background:
                var(--cream-2);
            border-color:
                var(--brown);
            color:
                var(--brown-dark);
        }
        /* =====================================================
           FOOTER
        ===================================================== */
        footer {
            border-top:
                1px solid var(--border) !important;
            background:
                rgba(255, 249, 239, .7);
            color:
                #9a8979 !important;
            padding:
                25px 0 !important;
            font-size:
                12px !important;
        }
        /* =====================================================
           RESPONSIVE
        ===================================================== */
        @media (max-width: 1100px) {
            .success-container {
                padding-left: 25px;
                padding-right: 25px;
            }
            .content-grid {
                grid-template-columns:
                    minmax(0, 1.3fr) minmax(300px, .85fr);
            }
            .coffee-decoration {
                right: 45px;
            }
        }
        @media (max-width: 800px) {
            .success-container {
                padding:
                    18px 18px 50px;
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
            .side-card {
                position: static;
            }
            .success-hero {
                min-height: 245px;
            }
            .hero-content {
                padding:
                    30px 27px;
            }
            .coffee-decoration {
                right: 25px;
                width: 110px;
                height: 110px;
                opacity: .65;
            }
            .coffee-cup {
                transform:
                    scale(.76);
            }
        }
        @media (max-width: 600px) {
            .success-container {
                padding-left: 14px;
                padding-right: 14px;
            }
            .success-hero {
                min-height: 270px;
                border-radius: 17px;
            }
            .hero-content {
                padding:
                    27px 22px;
            }
            .hero-title {
                font-size: 39px;
            }
            .hero-description {
                max-width: 260px;
                font-size: 13px;
            }
            .coffee-decoration {
                right: -12px;
                top: 115px;
                opacity: .38;
            }
            .order-card,
            .side-card {
                padding:
                    21px 18px;
                border-radius: 15px;
            }
            .info-row,
            .summary-line,
            .product-row {
                font-size: 12px;
            }
            .total-price {
                font-size: 18px;
            }
        }
        @media (max-width: 380px) {
            .success-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .success-icon {
                width: 58px;
                height: 58px;
                font-size: 27px;
            }
            .hero-title {
                font-size: 35px;
            }
            .coffee-decoration {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php
    $base = '';
    $activeNav = '';
    include 'includes/navbar.php';
    ?>
    <main class="success-container">
        <!-- =================================================
             SUCCESS HERO
        ================================================== -->
        <section class="success-hero">
            <div class="hero-content">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="hero-small">
                    Pesanan Berhasil
                </div>
                <h1 class="hero-title">
                    Terima kasih!
                </h1>
                <p class="hero-description">
                    Pesananmu berhasil dibuat dan sedang menunggu
                    proses selanjutnya. Kurir akan menghubungi
                    nomor HP-mu sebelum pengiriman.
                </p>
                <div class="order-code">
                    <i class="bi bi-receipt"></i>
                    Kode Pesanan:
                    <strong>
                        <?= htmlspecialchars($order['kode_pesanan']) ?>
                    </strong>
                </div>
            </div>
            <div class="coffee-decoration">
                <div class="coffee-cup"></div>
            </div>
        </section>
        <!-- =================================================
             CONTENT
        ================================================== -->
        <div class="content-grid">
            <!-- =================================================
                 LEFT
            ================================================== -->
            <div class="order-card">
                <!-- ALAMAT -->
                <div class="card-section">
                    <div class="section-title">
                        <i class="bi bi-geo-alt-fill"></i>
                        Alamat Pengiriman
                    </div>
                    <div class="receiver">
                        <?= htmlspecialchars($order['nama_penerima']) ?>
                        —
                        <?= htmlspecialchars($order['no_hp']) ?>
                    </div>
                    <div class="address">
                        <?= nl2br(
                            htmlspecialchars(
                                $order['alamat_pengiriman']
                            )
                        ) ?>
                    </div>
                    <?php if (!empty($order['catatan'])): ?>
                        <div class="note">
                            <i class="bi bi-chat-left-text"></i>
                            <span>
                                <?= htmlspecialchars($order['catatan']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- PENGIRIMAN & PEMBAYARAN -->
                <div class="card-section">
                    <div class="section-title">
                        <i class="bi bi-truck"></i>
                        Pengiriman & Pembayaran
                    </div>
                    <div class="info-row">
                        <span class="info-label">
                            Metode pengiriman
                        </span>
                        <span class="info-value">
                            <?= htmlspecialchars(
                                $order['label_pengiriman']
                            ) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">
                            Metode pembayaran
                        </span>
                        <span class="info-value">
                            Bayar di Tempat (COD)
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">
                            Status pesanan
                        </span>
                        <span class="status-badge">
                            <i class="bi bi-clock-fill"></i>
                            <?= htmlspecialchars(
                                $order['status']
                            ) ?>
                        </span>
                    </div>
                </div>
                <!-- PRODUK -->
                <div class="card-section">
                    <div class="section-title">
                        <i class="bi bi-bag-fill"></i>
                        Rincian Produk
                    </div>
                    <?php foreach ($orderItems as $it): ?>
                        <div class="product-row">
                            <span class="product-name">
                                <?= htmlspecialchars(
                                    $it['nama_produk']
                                ) ?>
                                × <?= $it['qty'] ?>
                            </span>
                            <span class="product-price">
                                <?= formatRupiah(
                                    $it['subtotal']
                                ) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <hr class="summary-divider">
                    <div class="summary-line">
                        <span>
                            Subtotal produk
                        </span>
                        <span>
                            <?= formatRupiah(
                                $order['subtotal_produk']
                            ) ?>
                        </span>
                    </div>
                    <div class="summary-line">
                        <span>
                            Ongkos kirim
                        </span>
                        <span>
                            <?= formatRupiah(
                                $order['ongkir']
                            ) ?>
                        </span>
                    </div>
                    <div class="total-box">
                        <div class="total-label">
                            Total Bayar
                            <small>
                                Tunai ke kurir
                            </small>
                        </div>
                        <div class="total-price">
                            <?= formatRupiah(
                                $order['total_bayar']
                            ) ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- =================================================
                 RIGHT
            ================================================== -->
            <aside class="side-card">
                <div class="side-heading">
                    <i class="bi bi-check-circle-fill"></i>
                    Pesanan Berhasil
                </div>
                <div class="success-message">
                    <i class="bi bi-info-circle-fill"></i>
                    Pesananmu sudah tercatat.
                    Simpan kode pesanan jika ingin
                    mengecek status pesanan nanti.
                </div>
                <a
                    href="user/riwayat_pesanan.php"
                    class="btn-main">
                    <i class="bi bi-clock-history"></i>
                    &nbsp;
                    Lihat Riwayat Pesanan
                </a>
                <a
                    href="katalog.php"
                    class="btn-secondary-custom">
                    <i class="bi bi-shop"></i>
                    &nbsp;
                    Belanja Lagi
                </a>
            </aside>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>