<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';
require_once 'includes/order_functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: user/loginUser.php?redirect=' . urlencode('checkout.php'));
    exit;
}
$stmtUser = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    header('Location: user/loginUser.php');
    exit;
}
// --- "Beli Sekarang" langsung dari katalog (tanpa lewat keranjang) ---
if (isset($_GET['beli'])) {
    $pid = (int)$_GET['beli'];
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $produk = $stmt->fetch();
    if ($produk && (int)$produk['stok'] > 0) {
        $qty = min($qty, (int)$produk['stok']);
        $items = [[
            'id'       => (int)$produk['id'],
            'nama'     => $produk['nama'],
            'harga'    => (float)$produk['harga'],
            'gambar'   => $produk['gambar'],
            'stok'     => (int)$produk['stok'],
            'qty'      => $qty,
            'subtotal' => $qty * (float)$produk['harga'],
        ]];
    } else {
        $items = [];
    }
} else {
    // --- Checkout dari item keranjang yang dipilih ---
    $checkoutIds = $_SESSION['checkout_ids'] ?? null;
    $items = cartGetItems($pdo, $checkoutIds);
}
if (empty($items)) {
    header('Location: keranjang.php');
    exit;
}
$subtotal = cartTotal($items);
$totalQty = array_sum(array_column($items, 'qty'));
$shippingMethods = getShippingMethods($totalQty);
$defaultShipping = array_key_first($shippingMethods);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPenerima = trim($_POST['nama_penerima'] ?? '');
    $noHp         = trim($_POST['no_hp'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');
    $metodeKirim  = $_POST['metode_pengiriman'] ?? '';
    $catatan      = trim($_POST['catatan'] ?? '');
    $metodeBayar  = $_POST['metode_pembayaran'] ?? 'COD';
    if (!$namaPenerima) $errors[] = 'Nama penerima wajib diisi.';
    if (!$noHp || !preg_match('/^[0-9+\-\s]{9,15}$/', $noHp)) $errors[] = 'Nomor HP tidak valid.';
    if (!$alamat) $errors[] = 'Alamat pengiriman wajib diisi.';
    if (!isset($shippingMethods[$metodeKirim])) $errors[] = 'Metode pengiriman tidak valid.';
    if ($metodeBayar !== 'COD') $errors[] = 'Saat ini hanya metode pembayaran COD yang tersedia.';
    if (empty($errors)) {
        ensureOrderTables($pdo);
        $ongkir = $shippingMethods[$metodeKirim]['biaya'];
        $label  = $shippingMethods[$metodeKirim]['label'];
        $total  = $subtotal + $ongkir;
        $kode   = generateOrderCode();
        try {
            $pdo->beginTransaction();
            // Cek ulang stok sebelum insert
            foreach ($items as $it) {
                $cek = $pdo->prepare("SELECT stok FROM products WHERE id = ? FOR UPDATE");
                $cek->execute([$it['id']]);
                $stokSekarang = (int)$cek->fetchColumn();
                if ($stokSekarang < $it['qty']) {
                    throw new Exception('Stok "' . $it['nama'] . '" tidak mencukupi lagi.');
                }
            }
            $stmt = $pdo->prepare("
                INSERT INTO orders
                    (id_user, kode_pesanan, nama_penerima, no_hp, alamat_pengiriman,
                    metode_pengiriman, label_pengiriman, ongkir, metode_pembayaran,
                    catatan, subtotal_produk, total_bayar, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $kode,
                $namaPenerima,
                $noHp,
                $alamat,
                $metodeKirim,
                $label,
                $ongkir,
                $metodeBayar,
                $catatan,
                $subtotal,
                $total,
                'Menunggu Konfirmasi',
            ]);
            $orderId = $pdo->lastInsertId();
            $stmtItem = $pdo->prepare("
                INSERT INTO order_items
                    (id_order, id_produk, nama_produk, harga_satuan, qty, subtotal)
                VALUES (?,?,?,?,?,?)
            ");
            $stmtStok = $pdo->prepare(
                "UPDATE products SET stok = stok - ? WHERE id = ?"
            );
            foreach ($items as $it) {
                $stmtItem->execute([
                    $orderId,
                    $it['id'],
                    $it['nama'],
                    $it['harga'],
                    $it['qty'],
                    $it['subtotal']
                ]);
                $stmtStok->execute([
                    $it['qty'],
                    $it['id']
                ]);
                // Hapus dari keranjang jika item ini berasal dari keranjang
                cartRemove($it['id']);
            }
            $pdo->commit();
            unset($_SESSION['checkout_ids']);
            header('Location: pesanan_sukses.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage()
                ?: 'Gagal memproses pesanan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — TokoKu</title>
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
            --brown-light: #8b542d;
            --cream: #fff9ef;
            --cream-2: #f7ecda;
            --cream-3: #efe0c9;
            --gold: #e5a83c;
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
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 0% 15%,
                    rgba(229, 168, 60, .08),
                    transparent 22%),
                radial-gradient(circle at 100% 85%,
                    rgba(112, 64, 31, .07),
                    transparent 25%),
                #fffdf9;
        }
        /* =====================================================
           CONTAINER
        ===================================================== */
        .checkout-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 20px 35px 60px;
        }
        /* =====================================================
           DECORATIVE BACKGROUND
        ===================================================== */
        .page-wrapper {
            position: relative;
        }
        .page-wrapper::before {
            content: "";
            position: fixed;
            width: 220px;
            height: 220px;
            left: -120px;
            bottom: 80px;
            border-radius: 50%;
            background: rgba(229, 168, 60, .08);
            pointer-events: none;
        }
        .page-wrapper::after {
            content: "";
            position: fixed;
            width: 270px;
            height: 270px;
            right: -150px;
            bottom: -100px;
            border-radius: 50%;
            background: rgba(112, 64, 31, .06);
            pointer-events: none;
        }
        /* =====================================================
           HERO CHECKOUT
        ===================================================== */
        .checkout-hero {
            position: relative;
            min-height: 185px;
            margin: 20px 0 26px;
            border-radius: 18px;
            overflow: hidden;
            background:
                radial-gradient(circle at 82% 35%,
                    rgba(255, 255, 255, .16) 0 8%,
                    transparent 9%),
                radial-gradient(circle at 88% 70%,
                    rgba(255, 255, 255, .10) 0 13%,
                    transparent 14%),
                linear-gradient(105deg,
                    #713b1c 0%,
                    #98572f 46%,
                    #c8874d 100%);
            box-shadow:
                0 12px 30px rgba(112, 64, 31, .12);
        }
        .checkout-hero::before {
            content: "";
            position: absolute;
            width: 440px;
            height: 440px;
            right: -120px;
            top: -230px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .10);
        }
        .checkout-hero::after {
            content: "";
            position: absolute;
            width: 330px;
            height: 330px;
            right: 110px;
            bottom: -250px;
            border-radius: 50%;
            border: 55px solid rgba(255, 255, 255, .07);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 32px 46px;
            color: #fff;
        }
        .hero-small {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .88);
        }
        .hero-small i {
            font-size: 18px;
        }
        .hero-title {
            margin: 0;
            font-family:
                Georgia,
                'Times New Roman',
                serif;
            font-size: clamp(42px, 5vw, 64px);
            line-height: 1;
            font-weight: 700;
            letter-spacing: -2px;
        }
        .hero-description {
            margin: 12px 0 0;
            font-size: 15px;
            font-weight: 500;
            color: rgba(255, 255, 255, .92);
        }
        /* =====================================================
           COFFEE DECORATION
        ===================================================== */
        .coffee-decoration {
            position: absolute;
            z-index: 3;
            right: 75px;
            top: 20px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 241, 216, .82);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 15px 35px rgba(72, 38, 18, .16);
        }
        .coffee-cup {
            width: 85px;
            height: 65px;
            border-radius:
                0 0 42px 42px;
            background: #fff8eb;
            border: 7px solid #f4e6d0;
            position: relative;
            box-shadow:
                inset 0 -7px 0 rgba(112, 64, 31, .08);
        }
        .coffee-cup::before {
            content: "";
            position: absolute;
            width: 47px;
            height: 40px;
            left: 12px;
            top: 5px;
            border-radius: 50%;
            background: #5b3018;
        }
        .coffee-cup::after {
            content: "";
            position: absolute;
            width: 27px;
            height: 27px;
            right: -28px;
            top: 9px;
            border: 7px solid #f4e6d0;
            border-left: 0;
            border-radius:
                0 50% 50% 0;
        }
        /* =====================================================
           ERROR
        ===================================================== */
        .error-box {
            background: #fff1ed;
            border: 1px solid #edc9bd;
            color: #8b3f2f;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .error-box div+div {
            margin-top: 4px;
        }
        /* =====================================================
           GRID
        ===================================================== */
        .checkout-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.9fr) minmax(300px, .95fr);
            gap: 24px;
            align-items: start;
        }
        /* =====================================================
           CARD
        ===================================================== */
        .section-card,
        .summary-card {
            background: rgba(255, 255, 255, .86);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow:
                0 7px 25px rgba(112, 64, 31, .055);
            backdrop-filter: blur(4px);
        }
        .section-card {
            padding: 27px 32px;
            margin-bottom: 22px;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            font-size: 19px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .section-title i {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--cream-2);
            color: var(--brown);
            font-size: 17px;
        }
        /* =====================================================
           FORM
        ===================================================== */
        .form-label {
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #6e5848;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-control,
        .form-select {
            min-height: 47px;
            border: 1px solid #e6d8c5;
            border-radius: 10px;
            background: #fffdf9;
            color: var(--text);
            font-size: 14px;
            padding: 10px 14px;
            transition: .2s ease;
        }
        textarea.form-control {
            min-height: 105px;
            resize: vertical;
        }
        .form-control::placeholder {
            color: #aa9b8e;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #b87a48;
            background: #fff;
            box-shadow:
                0 0 0 3px rgba(184, 122, 72, .10);
        }
        .form-text {
            color: #968576 !important;
            margin-top: 7px;
        }
        /* =====================================================
           SHIPPING
        ===================================================== */
        .ship-option {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            background: #fffdf9;
            transition:
                .2s ease;
        }
        .ship-option:hover {
            border-color: #c8a17f;
            background: #fffaf3;
            transform: translateY(-1px);
        }
        .ship-option:has(input:checked) {
            border-color: var(--brown);
            background:
                linear-gradient(90deg,
                    #fff5e6,
                    #fffdf9);
            box-shadow:
                0 4px 12px rgba(112, 64, 31, .07);
        }
        .ship-option input {
            accent-color: var(--brown);
        }
        .ship-option .biaya {
            font-weight: 800;
            color: var(--brown);
            white-space: nowrap;
        }
        .shipping-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--brown-dark);
        }
        .shipping-name i {
            margin-right: 5px;
        }
        .shipping-desc {
            margin-top: 3px;
            font-size: 12px;
            color: var(--muted);
        }
        /* =====================================================
           PAYMENT
        ===================================================== */
        .pay-option {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            background: #fffdf9;
        }
        .pay-option.active {
            border-color: var(--brown);
            background:
                linear-gradient(90deg,
                    #fff5e6,
                    #fffdf9);
        }
        .pay-option.disabled {
            opacity: .5;
        }
        .pay-option input {
            accent-color: var(--brown);
        }
        .payment-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--brown-dark);
        }
        .payment-desc {
            font-size: 12px;
            color: var(--muted);
            margin-top: 3px;
        }
        /* =====================================================
           SUMMARY
        ===================================================== */
        .summary-card {
            padding: 27px 28px;
            position: sticky;
            top: 90px;
        }
        .summary-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 22px;
            font-size: 19px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .summary-title i {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--cream-2);
            color: var(--brown);
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .item-row .nama {
            color: #66574c;
            line-height: 1.45;
        }
        .item-row>span:last-child {
            color: #4d3b2d;
            white-space: nowrap;
        }
        .summary-divider {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 18px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            font-size: 19px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .total-price {
            color: var(--brown);
            white-space: nowrap;
        }
        /* =====================================================
           BUTTON
        ===================================================== */
        .btn-order {
            width: 100%;
            border: none;
            border-radius: 11px;
            padding: 13px;
            margin-top: 22px;
            background:
                linear-gradient(135deg,
                    #6f3517,
                    #8d4b24);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            box-shadow:
                0 8px 18px rgba(112, 64, 31, .16);
            transition: .2s ease;
        }
        .btn-order:hover {
            color: #fff;
            background:
                linear-gradient(135deg,
                    #5c2b13,
                    #793e1e);
            transform: translateY(-1px);
            box-shadow:
                0 10px 22px rgba(112, 64, 31, .22);
        }
        .btn-back {
            display: block;
            width: 100%;
            border: 1px solid #cfae8e;
            border-radius: 11px;
            padding: 11px;
            margin-top: 18px;
            background: transparent;
            color: var(--brown);
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            box-sizing: border-box;
            clear: both;
            transition: .2s ease;
        }
        .btn-back:hover {
            background: var(--cream-2);
            border-color: var(--brown);
            color: var(--brown-dark);
        }
        /* =====================================================
           INFO NOTE
        ===================================================== */
        .shipping-info {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 12px;
            font-size: 12px;
            color: var(--muted);
        }
        .shipping-info i {
            color: var(--brown);
            margin-top: 1px;
        }
        /* =====================================================
           FOOTER
        ===================================================== */
        footer {
            border-top: 1px solid var(--border) !important;
            background: rgba(255, 249, 239, .7);
            color: #9a8979 !important;
            padding: 25px 0 !important;
            font-size: 12px !important;
        }
        /* =====================================================
           RESPONSIVE
        ===================================================== */
        @media (max-width: 1100px) {
            .checkout-container {
                padding-left: 25px;
                padding-right: 25px;
            }
            .checkout-grid {
                grid-template-columns:
                    minmax(0, 1.55fr) minmax(280px, .9fr);
            }
            .coffee-decoration {
                right: 45px;
            }
        }
        @media (max-width: 800px) {
            .checkout-container {
                padding:
                    15px 18px 45px;
            }
            .checkout-hero {
                min-height: 165px;
                margin-top: 15px;
            }
            .hero-content {
                padding: 28px 27px;
            }
            .hero-title {
                font-size: 45px;
            }
            .coffee-decoration {
                right: 20px;
                width: 115px;
                height: 115px;
            }
            .coffee-cup {
                transform: scale(.78);
            }
            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .summary-card {
                position: static;
                margin-bottom: 20px;
            }
            .section-card {
                padding: 23px;
            }
        }
        @media (max-width: 600px) {
            .checkout-container {
                padding-left: 14px;
                padding-right: 14px;
            }
            .checkout-hero {
                min-height: 185px;
            }
            .hero-content {
                padding: 27px 23px;
            }
            .hero-small {
                font-size: 11px;
                letter-spacing: 2px;
            }
            .hero-title {
                font-size: 40px;
            }
            .hero-description {
                max-width: 250px;
                font-size: 13px;
            }
            .coffee-decoration {
                opacity: .5;
                right: -10px;
                top: 48px;
            }
            .section-card {
                padding: 20px 17px;
            }
            .summary-card {
                padding: 22px 19px;
            }
            .section-title,
            .summary-title {
                font-size: 17px;
            }
            .shipping-desc {
                max-width: 220px;
            }
            .ship-option {
                align-items: flex-start !important;
            }
            .ship-option .biaya {
                font-size: 12px;
            }
        }
        @media (max-width: 380px) {
            .checkout-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .hero-title {
                font-size: 36px;
            }
            .coffee-decoration {
                display: none;
            }
            .section-card {
                padding: 18px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php
        $base = '';
        $activeNav = 'keranjang';
        include 'includes/navbar.php';
        ?>
        <main class="checkout-container">
            <!-- =================================================
             HERO
        ================================================== -->
            <section class="checkout-hero">
                <div class="hero-content">
                    <div class="hero-small">
                        <i class="bi bi-credit-card"></i>
                        Pembayaran
                    </div>
                    <h1 class="hero-title">
                        Checkout
                    </h1>
                    <p class="hero-description">
                        Lengkapi alamat, pilih metode pengiriman,
                        lalu selesaikan pesanan.
                    </p>
                </div>
                <div class="coffee-decoration">
                    <div class="coffee-cup"></div>
                </div>
            </section>
            <!-- =================================================
             ERROR
        ================================================== -->
            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $e): ?>
                        <div>
                            <i class="bi bi-exclamation-circle"></i>
                            <?= htmlspecialchars($e) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!-- =================================================
             CHECKOUT CONTENT
        ================================================== -->
            <form method="POST">
                <div class="checkout-grid">
                    <!-- =========================================
                     LEFT
                ========================================== -->
                    <div>
                        <!-- ALAMAT PENGIRIMAN -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="bi bi-geo-alt-fill"></i>
                                Alamat Pengiriman
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Nama Penerima
                                    </label>
                                    <input
                                        type="text"
                                        name="nama_penerima"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                                    $_POST['nama_penerima']
                                                        ?? $currentUser['nama']
                                                ) ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Nomor HP
                                    </label>
                                    <input
                                        type="text"
                                        name="no_hp"
                                        class="form-control"
                                        placeholder="08xxxxxxxxxx"
                                        value="<?= htmlspecialchars(
                                                    $_POST['no_hp'] ?? ''
                                                ) ?>"
                                        required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">
                                        Alamat Lengkap
                                    </label>
                                    <textarea
                                        name="alamat"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Nama jalan, no rumah, RT/RW, kelurahan, kecamatan, kota, kode pos"
                                        required><?= htmlspecialchars(
                                                        $_POST['alamat']
                                                            ?? $currentUser['alamat']
                                                    ) ?></textarea>
                                    <div class="form-text">
                                        Alamat ini diambil dari profil akunmu.
                                        Kamu bisa mengubahnya khusus untuk pesanan ini.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">
                                        Catatan untuk kurir (opsional)
                                    </label>
                                    <input
                                        type="text"
                                        name="catatan"
                                        class="form-control"
                                        placeholder="Contoh: rumah pagar hijau, titip di security"
                                        value="<?= htmlspecialchars(
                                                    $_POST['catatan'] ?? ''
                                                ) ?>">
                                </div>
                            </div>
                        </div>
                        <!-- METODE PENGIRIMAN -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="bi bi-truck"></i>
                                Metode Pengiriman
                            </div>
                            <?php foreach ($shippingMethods as $key => $m): ?>
                                <label
                                    class="ship-option d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <input
                                            type="radio"
                                            name="metode_pengiriman"
                                            value="<?= $key ?>"
                                            <?= (
                                                ($_POST['metode_pengiriman']
                                                    ?? $defaultShipping)
                                                === $key
                                            ) ? 'checked' : '' ?>
                                            required>
                                        <div>
                                            <div class="shipping-name">
                                                <i class="bi <?= $m['icon'] ?>"></i>
                                                <?= htmlspecialchars($m['label']) ?>
                                            </div>
                                            <div class="shipping-desc">
                                                <?= htmlspecialchars($m['deskripsi']) ?>
                                                · Estimasi
                                                <?= htmlspecialchars($m['estimasi']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="biaya">
                                        <?= formatRupiah($m['biaya']) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <?php if ($totalQty >= 5): ?>
                                <div class="shipping-info">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>
                                        Pesananmu berjumlah
                                        <?= $totalQty ?> item —
                                        Kargo bisa jadi pilihan paling hemat
                                        untuk muatan besar.
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- METODE PEMBAYARAN -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="bi bi-wallet2"></i>
                                Metode Pembayaran
                            </div>
                            <label
                                class="pay-option active d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <input
                                        type="radio"
                                        name="metode_pembayaran"
                                        value="COD"
                                        checked
                                        required>
                                    <div>
                                        <div class="payment-name">
                                            <i class="bi bi-cash-coin"></i>
                                            Bayar di Tempat (COD)
                                        </div>
                                        <div class="payment-desc">
                                            Bayar tunai ke kurir saat paket
                                            sampai di alamatmu.
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <div
                                class="pay-option disabled d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <input
                                        type="radio"
                                        disabled>
                                    <div>
                                        <div class="payment-name">
                                            <i class="bi bi-credit-card"></i>
                                            Transfer Bank / E-Wallet
                                        </div>
                                        <div class="payment-desc">
                                            Segera hadir.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- =========================================
                     RIGHT - SUMMARY
                ========================================== -->
                    <div>
                        <div class="summary-card">
                            <div class="summary-title">
                                <i class="bi bi-receipt"></i>
                                Ringkasan Pesanan
                            </div>
                            <?php foreach ($items as $it): ?>
                                <div class="item-row">
                                    <span class="nama">
                                        <?= htmlspecialchars($it['nama']) ?>
                                        ×
                                        <?= $it['qty'] ?>
                                    </span>
                                    <span>
                                        <?= formatRupiah($it['subtotal']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <div class="item-row">
                                <span class="nama">
                                    Subtotal produk
                                </span>
                                <span>
                                    <?= formatRupiah($subtotal) ?>
                                </span>
                            </div>
                            <div class="item-row">
                                <span class="nama">
                                    Ongkos kirim
                                </span>
                                <span id="ongkirDisplay">
                                    <?= formatRupiah(
                                        $shippingMethods[$_POST['metode_pengiriman']
                                            ?? $defaultShipping]['biaya']
                                    ) ?>
                                </span>
                            </div>
                            <hr class="summary-divider">
                            <div class="total-row">
                                <span>
                                    Total Bayar
                                </span>
                                <span
                                    class="total-price"
                                    id="totalDisplay">
                                    <?= formatRupiah(
                                        $subtotal
                                            +
                                            $shippingMethods[$_POST['metode_pengiriman']
                                                ?? $defaultShipping]['biaya']
                                    ) ?>
                                </span>
                            </div>
                            <button
                                type="submit"
                                class="btn-order">
                                <i class="bi bi-check-circle-fill"></i>
                                Buat Pesanan (COD)
                            </button>
                            <a
                                href="keranjang.php"
                                class="btn-back">
                                <i class="bi bi-arrow-left"></i>
                                Kembali ke keranjang
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ongkirData =
            <?= json_encode(
                array_map(
                    fn($m) => $m['biaya'],
                    $shippingMethods
                )
            ) ?>;
        const subtotal =
            <?= (int)$subtotal ?>;
        function formatRupiah(n) {
            return 'Rp ' +
                n.toLocaleString('id-ID');
        }
        document
            .querySelectorAll(
                'input[name="metode_pengiriman"]'
            )
            .forEach(radio => {
                radio.addEventListener(
                    'change',
                    function() {
                        const ongkir =
                            ongkirData[this.value] || 0;
                        document
                            .getElementById('ongkirDisplay')
                            .textContent =
                            formatRupiah(ongkir);
                        document
                            .getElementById('totalDisplay')
                            .textContent =
                            formatRupiah(
                                subtotal + ongkir
                            );
                    }
                );
            });
    </script>
</body>
</html>