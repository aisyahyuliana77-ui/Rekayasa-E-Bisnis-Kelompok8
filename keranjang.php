<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';
if (!isLoggedIn()) {
    header('Location: user/loginUser.php?redirect=' . urlencode('keranjang.php'));
    exit;
}
// Hapus item dari keranjang
if (isset($_GET['remove'])) {
    cartRemove((int)$_GET['remove']);
    header('Location: keranjang.php');
    exit;
}
// Update qty (dari input number di halaman ini, tanpa AJAX, biar simpel & tetap jalan tanpa JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'update_qty') {
    foreach ($_POST['qty'] as $pid => $qty) {
        cartSetQty((int)$pid, (int)$qty);
    }
    header('Location: keranjang.php');
    exit;
}
// Lanjut ke checkout dengan item yang dicentang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'checkout') {
    $selected = $_POST['selected'] ?? [];
    if (empty($selected)) {
        header('Location: keranjang.php?err=nopick');
        exit;
    }
    $_SESSION['checkout_ids'] = array_map('intval', $selected);
    header('Location: checkout.php');
    exit;
}
$items = cartGetItems($pdo);
$total = cartTotal($items);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja — TokoKu</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
        rel="stylesheet">
    <style>
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
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family:
                'Segoe UI',
                Arial,
                sans-serif;
            background: #fffdf9;
            color: var(--text);
        }
        /* =====================================================
           CONTAINER UTAMA
        ===================================================== */
        .home-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 20px 35px 60px;
        }
        /* =====================================================
           HERO KERANJANG
        ===================================================== */
        .cart-hero {
            position: relative;
            overflow: hidden;
            min-height: 165px;
            display: flex;
            align-items: center;
            padding: 28px 38px;
            margin-bottom: 20px;
            border-radius: 18px;
            background:
                linear-gradient(110deg,
                    #70401f 0%,
                    #8b542d 48%,
                    #b77a42 100%);
            color: white;
            box-shadow:
                0 8px 24px rgba(112, 64, 31, 0.13);
        }
        .cart-hero::before {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: -50px;
            top: -110px;
            background:
                rgba(255, 255, 255, 0.08);
        }
        .cart-hero::after {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            right: 210px;
            bottom: -115px;
            background:
                rgba(229, 168, 60, 0.16);
        }
        .cart-hero-content {
            position: relative;
            z-index: 2;
        }
        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 11px;
            margin-bottom: 8px;
            border-radius: 999px;
            background:
                rgba(255, 255, 255, 0.13);
            border:
                1px solid rgba(255, 255, 255, 0.17);
            color: #fff4e4;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .cart-hero h1 {
            margin: 0;
            font-size: 31px;
            font-weight: 800;
            letter-spacing: -0.6px;
        }
        .cart-hero p {
            margin: 7px 0 0;
            color: #f8e8d5;
            font-size: 14px;
        }
        .hero-icon {
            position: absolute;
            right: 70px;
            bottom: 20px;
            z-index: 2;
            font-size: 76px;
            color:
                rgba(255, 255, 255, 0.15);
        }
        /* =====================================================
           ERROR
        ===================================================== */
        .cart-alert {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 15px;
            padding: 11px 14px;
            border-radius: 10px;
            border:
                1px solid #ead6b9;
            background:
                #fff8e9;
            color: #79552d;
            font-size: 13px;
        }
        /* =====================================================
           MAIN CART AREA
        ===================================================== */
        .cart-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr) 360px;
            gap: 18px;
            align-items: start;
        }
        /* =====================================================
           CART CONTAINER
        ===================================================== */
        .cart-container {
            background: #fff;
            border:
                1px solid var(--border);
            border-radius: 17px;
            padding: 18px;
            box-shadow:
                0 7px 25px rgba(87, 50, 25, 0.05);
        }
        /* =====================================================
           CART TOP
        ===================================================== */
        .cart-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .select-all {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--brown-dark);
            font-size: 13px;
            font-weight: 650;
        }
        .select-all .form-check-input {
            margin: 0;
            width: 18px;
            height: 18px;
            border:
                1px solid #cbb9a5;
            cursor: pointer;
        }
        .select-all .form-check-input:checked {
            background-color:
                var(--brown);
            border-color:
                var(--brown);
        }
        .clear-cart {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #b64b3d;
            font-size: 12px;
            text-decoration: none;
            transition: 0.2s;
        }
        .clear-cart:hover {
            color: #8d2f25;
        }
        /* =====================================================
           ITEM
        ===================================================== */
        .cart-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px;
            margin-bottom: 10px;
            border:
                1px solid var(--border);
            border-radius: 12px;
            background:
                #fffdf9;
            transition: 0.2s;
        }
        .cart-item:hover {
            border-color:
                #dcc8af;
            box-shadow:
                0 5px 15px rgba(87, 50, 25, 0.05);
        }
        .item-check {
            width: 17px;
            height: 17px;
            margin: 0 !important;
            flex-shrink: 0;
            cursor: pointer;
        }
        .item-check:checked {
            background-color:
                var(--brown);
            border-color:
                var(--brown);
        }
        /* =====================================================
           PRODUCT IMAGE
        ===================================================== */
        .cart-thumb {
            width: 82px;
            height: 82px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 10px;
            background:
                linear-gradient(135deg,
                    #f5e8d4,
                    #fffaf2);
            border:
                1px solid var(--border);
        }
        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .cart-thumb i {
            color: #bca993;
        }
        /* =====================================================
           PRODUCT INFO
        ===================================================== */
        .cart-product-info {
            min-width: 0;
            flex: 1;
        }
        .cart-product-name {
            color:
                var(--brown-dark);
            font-size: 14px;
            line-height: 1.4;
            font-weight: 750;
            margin-bottom: 4px;
        }
        .cart-product-price {
            color:
                var(--muted);
            font-size: 12px;
        }
        /* =====================================================
           QTY
        ===================================================== */
        .qty-wrap {
            flex-shrink: 0;
        }
        .qty-input {
            width: 72px;
            height: 38px;
            border:
                1px solid var(--border);
            border-radius: 8px;
            color:
                var(--text);
            background: white;
            font-size: 13px;
            text-align: center;
        }
        .qty-input:focus {
            border-color:
                var(--brown-light);
            box-shadow:
                0 0 0 3px rgba(112, 64, 31, 0.08);
        }
        /* =====================================================
           SUBTOTAL
        ===================================================== */
        .cart-subtotal {
            min-width: 115px;
            text-align: right;
            flex-shrink: 0;
        }
        .item-subtotal {
            color:
                var(--brown-dark);
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 3px;
        }
        .remove-item {
            color:
                #c45145;
            font-size: 11px;
            text-decoration: none;
        }
        .remove-item:hover {
            color:
                #8d2f25;
        }
        /* =====================================================
           UPDATE BUTTON
        ===================================================== */
        .update-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            padding: 8px 13px;
            border:
                1px solid #cdbca9;
            border-radius: 9px;
            background: white;
            color:
                var(--brown);
            font-size: 12px;
            transition: 0.2s;
        }
        .update-button:hover {
            background:
                var(--cream);
            border-color:
                var(--brown-light);
            color:
                var(--brown-dark);
        }
        /* =====================================================
           SUMMARY
        ===================================================== */
        .summary-card {
            position: sticky;
            top: 90px;
            padding: 20px;
            border:
                1px solid var(--border);
            border-radius: 17px;
            background:
                linear-gradient(180deg,
                    #fffdf9 0%,
                    #fff9ef 100%);
            box-shadow:
                0 7px 25px rgba(87, 50, 25, 0.06);
        }
        .summary-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            padding-bottom: 13px;
            border-bottom:
                1px solid var(--border);
            color:
                var(--brown-dark);
            font-size: 18px;
            font-weight: 800;
        }
        .summary-title i {
            color:
                var(--brown);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            color:
                var(--text);
            font-size: 13px;
        }
        .summary-price {
            color:
                var(--brown-dark);
            font-weight: 800;
        }
        .summary-note {
            margin: 12px 0 17px;
            color:
                var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }
        /* =====================================================
           BUTTON CHECKOUT
        ===================================================== */
        .btn-checkout {
            width: 100%;
            min-height: 42px;
            border: 0;
            border-radius: 10px;
            background:
                var(--brown);
            color: white;
            font-size: 13px;
            font-weight: 750;
            transition: 0.2s;
        }
        .btn-checkout:hover {
            background:
                var(--brown-dark);
            color: white;
            transform: translateY(-1px);
        }
        .btn-add-product {
            width: 100%;
            min-height: 40px;
            margin-top: 9px;
            border:
                1px solid #cdbca9;
            border-radius: 10px;
            background: white;
            color:
                var(--brown);
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .btn-add-product:hover {
            background:
                var(--cream);
            border-color:
                var(--brown-light);
            color:
                var(--brown-dark);
        }
        /* =====================================================
           EMPTY CART
        ===================================================== */
        .empty-cart {
            min-height: 365px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 35px;
            border:
                1px solid var(--border);
            border-radius: 17px;
            background:
                linear-gradient(180deg,
                    #fffdf9,
                    #fff9ef);
            box-shadow:
                0 7px 25px rgba(87, 50, 25, 0.05);
        }
        .empty-icon {
            width: 78px;
            height: 78px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            border-radius: 50%;
            background:
                var(--cream-2);
            color:
                var(--brown);
            font-size: 40px;
        }
        .empty-cart h3 {
            margin: 0 0 6px;
            color:
                var(--brown-dark);
            font-size: 21px;
            font-weight: 800;
        }
        .empty-cart p {
            margin: 0 0 17px;
            color:
                var(--muted);
            font-size: 13px;
        }
        .btn-start-shopping {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 18px;
            border-radius: 10px;
            background:
                var(--brown);
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }
        .btn-start-shopping:hover {
            background:
                var(--brown-dark);
            color: white;
            transform: translateY(-1px);
        }
        /* =====================================================
           FOOTER
        ===================================================== */
        footer {
            border-top:
                1px solid var(--border);
            margin-top: 15px;
            padding: 28px 20px;
            background:
                #fffdf9;
            color:
                #9a8979;
            font-size: 11px;
            text-align: center;
        }
        /* =====================================================
           RESPONSIVE
        ===================================================== */
        @media (max-width: 1100px) {
            .home-container {
                padding-left: 25px;
                padding-right: 25px;
            }
            .cart-layout {
                grid-template-columns:
                    minmax(0, 1fr) 320px;
            }
        }
        @media (max-width: 850px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .summary-card {
                position: static;
            }
            .hero-icon {
                right: 35px;
            }
        }
        @media (max-width: 800px) {
            .home-container {
                padding:
                    15px 18px 45px;
            }
            .cart-hero {
                min-height: 145px;
                padding:
                    23px 25px;
            }
            .cart-hero h1 {
                font-size: 27px;
            }
            .hero-icon {
                font-size: 55px;
                right: 25px;
                bottom: 18px;
            }
            .cart-container {
                padding: 14px;
            }
        }
        @media (max-width: 650px) {
            .cart-item {
                flex-wrap: wrap;
                gap: 10px;
            }
            .cart-product-info {
                width:
                    calc(100% - 120px);
            }
            .qty-wrap {
                margin-left: 28px;
            }
            .cart-subtotal {
                margin-left: auto;
            }
        }
        @media (max-width: 500px) {
            .cart-hero {
                min-height: 135px;
                padding: 20px;
            }
            .cart-hero h1 {
                font-size: 23px;
            }
            .cart-hero p {
                font-size: 12px;
                max-width: 75%;
            }
            .hero-icon {
                font-size: 43px;
                right: 18px;
            }
            .cart-top {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }
            .cart-thumb {
                width: 68px;
                height: 68px;
            }
            .cart-product-info {
                width:
                    calc(100% - 95px);
            }
            .qty-wrap {
                margin-left: 0;
            }
            .cart-subtotal {
                min-width: 90px;
            }
        }
        @media (max-width: 380px) {
            .home-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .cart-container,
            .summary-card {
                padding: 13px;
            }
        }
    </style>
</head>
<body>
    <?php
    $base = '';
    $activeNav = 'keranjang';
    include 'includes/navbar.php';
    ?>
    <main>
        <div class="home-container">
            <!-- =================================================
             HERO
        ================================================== -->
            <section class="cart-hero">
                <div class="cart-hero-content">
                    <div class="hero-label">
                        <i class="bi bi-cart3"></i>
                        Keranjang Belanja
                    </div>
                    <h1>
                        Keranjang Belanja
                    </h1>
                    <p>
                        Periksa kembali produk sebelum lanjut ke checkout.
                    </p>
                </div>
                <i class="bi bi-cup-hot-fill hero-icon"></i>
            </section>
            <?php if (isset($_GET['err']) && $_GET['err'] === 'nopick'): ?>
                <div class="cart-alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>
                        Pilih minimal 1 produk untuk lanjut ke checkout.
                    </span>
                </div>
            <?php endif; ?>
            <?php if (empty($items)): ?>
                <!-- =================================================
                 EMPTY CART
            ================================================== -->
                <div class="empty-cart">
                    <div class="empty-icon">
                        <i class="bi bi-cart-x"></i>
                    </div>
                    <h3>
                        Keranjang kamu masih kosong.
                    </h3>
                    <p>
                        Yuk, tambahkan produk favoritmu sekarang!
                    </p>
                    <a
                        href="katalog.php"
                        class="btn-start-shopping">
                        <i class="bi bi-bag-plus"></i>
                        Mulai belanja
                        <span>→</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- =================================================
             CART CONTENT
        ================================================== -->
                <form
                    method="POST"
                    id="cartForm">
                    <input
                        type="hidden"
                        name="form"
                        value="checkout">
                    <div class="cart-layout">
                        <!-- =================================================
                     LEFT — PRODUCT
                ================================================== -->
                        <div class="cart-container">
                            <div class="cart-top">
                                <div class="select-all">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="pilihSemua"
                                        checked>
                                    <label
                                        for="pilihSemua">
                                        Pilih semua
                                    </label>
                                </div>
                                <a
                                    href="cart_action.php?clear=1"
                                    onclick="
                                event.preventDefault();
                                clearCart();
                            "
                                    class="clear-cart">
                                    <i class="bi bi-trash3"></i>
                                    Kosongkan keranjang
                                </a>
                            </div>
                            <?php foreach ($items as $it): ?>
                                <div class="cart-item">
                                    <!-- CHECKBOX -->
                                    <input
                                        class="form-check-input item-check"
                                        type="checkbox"
                                        name="selected[]"
                                        value="<?= $it['id'] ?>"
                                        checked>
                                    <!-- IMAGE -->
                                    <div class="cart-thumb">
                                        <?php if (!empty($it['gambar'])): ?>
                                            <img
                                                src="uploads/products/<?= htmlspecialchars($it['gambar']) ?>"
                                                onerror="
                                            this.style.display='none';
                                        "
                                                alt="<?= htmlspecialchars($it['nama']) ?>">
                                        <?php else: ?>
                                            <i
                                                class="bi bi-box-seam"
                                                style="font-size:1.7rem;">
                                            </i>
                                        <?php endif; ?>
                                    </div>
                                    <!-- INFO -->
                                    <div class="cart-product-info">
                                        <div class="cart-product-name">
                                            <?= htmlspecialchars($it['nama']) ?>
                                        </div>
                                        <div class="cart-product-price">
                                            <?= formatRupiah($it['harga']) ?>
                                            / item
                                            · stok <?= $it['stok'] ?>
                                        </div>
                                    </div>
                                    <!-- QTY -->
                                    <div class="qty-wrap">
                                        <input
                                            type="number"
                                            name="qty[<?= $it['id'] ?>]"
                                            class="form-control qty-input item-qty"
                                            data-harga="<?= $it['harga'] ?>"
                                            min="1"
                                            max="<?= $it['stok'] ?>"
                                            value="<?= $it['qty'] ?>">
                                    </div>
                                    <!-- SUBTOTAL -->
                                    <div class="cart-subtotal">
                                        <div
                                            class="item-subtotal">
                                            <?= formatRupiah($it['subtotal']) ?>
                                        </div>
                                        <a
                                            href="keranjang.php?remove=<?= $it['id'] ?>"
                                            class="remove-item"
                                            onclick="
                                        return confirm(
                                            'Hapus produk ini dari keranjang?'
                                        )
                                    ">
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <!-- UPDATE -->
                            <button
                                type="button"
                                id="btnUpdateQty"
                                class="update-button">
                                <i class="bi bi-arrow-repeat"></i>
                                Perbarui jumlah
                            </button>
                        </div>
                        <!-- =================================================
                     RIGHT — SUMMARY
                ================================================== -->
                        <div class="summary-card">
                            <div class="summary-title">
                                <i class="bi bi-receipt"></i>
                                Ringkasan Belanja
                            </div>
                            <div class="summary-row">
                                <span>
                                    Subtotal
                                    (<span id="subtotalCount">
                                        <?= count($items) ?>
                                    </span>
                                    produk)
                                </span>
                                <span
                                    id="subtotalValue"
                                    class="summary-price">
                                    <?= formatRupiah($total) ?>
                                </span>
                            </div>
                            <p class="summary-note">
                                Ongkos kirim & metode pembayaran
                                ditentukan di halaman checkout.
                            </p>
                            <button
                                type="submit"
                                class="btn-checkout">
                                <i class="bi bi-bag-check me-1"></i>
                                Lanjut ke Checkout
                                <span class="ms-1">→</span>
                            </button>
                            <a
                                href="katalog.php"
                                class="btn-add-product">
                                <i class="bi bi-plus-circle me-1"></i>
                                Tambah produk lain
                            </a>
                        </div>
                    </div>
                </form>
                <!-- =================================================
             FORM UPDATE QTY
        ================================================== -->
                <form
                    method="POST"
                    id="updateQtyForm"
                    style="display:none">
                    <input
                        type="hidden"
                        name="form"
                        value="update_qty">
                </form>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* =========================================================
   CLEAR CART
   ========================================================= */
        function clearCart() {
            if (!confirm(
                    'Kosongkan semua produk di keranjang?'
                )) return;
            fetch(
                    'cart_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=clear'
                    }
                )
                .then(() => location.reload());
        }
        /* =========================================================
           PILIH SEMUA
           ========================================================= */
        document
            .getElementById('pilihSemua')
            ?.addEventListener(
                'change',
                function() {
                    document
                        .querySelectorAll('.item-check')
                        .forEach(
                            c => c.checked = this.checked
                        );
                }
            );
        /* =========================================================
           UPDATE QTY
           ========================================================= */
        document
            .getElementById('btnUpdateQty')
            ?.addEventListener(
                'click',
                function() {
                    const targetForm =
                        document.getElementById(
                            'updateQtyForm'
                        );
                    document
                        .querySelectorAll('.item-qty')
                        .forEach(
                            inp => {
                                const name =
                                    inp.getAttribute(
                                        'name'
                                    );
                                const hidden =
                                    document.createElement(
                                        'input'
                                    );
                                hidden.type =
                                    'hidden';
                                hidden.name =
                                    name;
                                hidden.value =
                                    inp.value;
                                targetForm.appendChild(
                                    hidden
                                );
                            }
                        );
                    targetForm.submit();
                }
            );
        /* =========================================================
           UPDATE SUBTOTAL LIVE
           ========================================================= */
        document
            .querySelectorAll('.item-qty')
            .forEach(
                inp => {
                    inp.addEventListener(
                        'input',
                        function() {
                            const harga =
                                parseFloat(
                                    this.dataset.harga
                                );
                            const qty =
                                parseInt(
                                    this.value || '1',
                                    10
                                );
                            const row =
                                this.closest(
                                    '.cart-item'
                                );
                            const subtotalEl =
                                row.querySelector(
                                    '.item-subtotal'
                                );
                            subtotalEl.textContent =
                                'Rp ' +
                                (
                                    harga * qty
                                ).toLocaleString(
                                    'id-ID'
                                );
                        }
                    );
                }
            );
    </script>
</body>
</html>