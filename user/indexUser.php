<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart.php';
// ==================================================
// WAJIB LOGIN SEBAGAI USER
// ==================================================
if (!isset($_SESSION['user_id'])) {
    header('Location: loginUser.php');
    exit;
}
// ==================================================
// AMBIL DATA USER
// ==================================================
$stmtUser = $pdo->prepare("
    SELECT *
    FROM user
    WHERE id_user = ?
");
$stmtUser->execute([
    $_SESSION['user_id']
]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    session_destroy();
    header('Location: loginUser.php');
    exit;
}
$namaUser = $currentUser['nama'];
// ==================================================
// AMBIL PRODUK UNGGULAN
// 6 PRODUK TERBARU
// ==================================================
$stmt = $pdo->query("
    SELECT
        p.*,
        c.nama AS kategori
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();
// ==================================================
// AMBIL SEMUA KATEGORI
// ==================================================
$categories = $pdo->query("
    SELECT *
    FROM categories
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomieCozie — Belanja Mudah</title>
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
            background: #fffdf9;
            color: var(--text);
            font-family:
                'Segoe UI',
                Arial,
                sans-serif;
        }
        /* ==================================================
           CONTAINER
        ================================================== */
        .home-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding:
                20px 35px 60px;
        }
        /* ==================================================
           HERO
        ================================================== */
        .hero {
            position: relative;
            min-height: 305px;
            border-radius: 12px;
            overflow: hidden;
            background:
                linear-gradient(
                    90deg,
                    #fff0cf 0%,
                    #f8dfae 45%,
                    #c5894e 100%
                );
            display: flex;
            align-items: center;
            padding:
                45px 95px;
            margin-bottom: 28px;
        }
        .hero::before {
            content: "";
            position: absolute;
            width: 270px;
            height: 270px;
            left: -145px;
            top: -80px;
            border-radius: 50%;
            background:
                rgba(226, 163, 62, .45);
        }
        .hero::after {
            content: "";
            position: absolute;
            width: 400px;
            height: 250px;
            right: -100px;
            bottom: -150px;
            border-radius: 50%;
            background:
                rgba(255, 239, 201, .5);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }
        .hero-label {
            display: block;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--brown);
            text-transform: uppercase;
        }
        .hero h1 {
            margin: 0;
            color: #542d16;
            font-family: Georgia, serif;
            font-size:
                clamp(
                    40px,
                    4vw,
                    64px
                );
            line-height: 1.05;
            font-weight: 700;
        }
        .hero p {
            max-width: 520px;
            margin:
                16px 0 24px;
            color: #513a29;
            font-size: 15px;
            line-height: 1.6;
        }
        .hero-button {
            display: inline-flex;
            align-items: center;
            gap: 25px;
            padding:
                12px 20px;
            border-radius: 25px;
            border: none;
            background:
                var(--brown);
            color: white;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: .2s;
        }
        .hero-button:hover {
            background:
                var(--brown-dark);
            color: white;
            transform:
                translateY(-2px);
        }
        .hero-button i {
            font-size: 17px;
        }
        /* ==================================================
           DEKORASI HERO
        ================================================== */
        .hero-decoration {
            position: absolute;
            right: 40px;
            bottom: 0;
            width: 44%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        .hero-cup {
            width: 145px;
            height: 145px;
            border-radius: 50%;
            background:
                radial-gradient(
                    circle at 50% 45%,
                    #382117 0 28%,
                    #b8753e 29% 38%,
                    #e9c28c 39% 58%,
                    transparent 59%
                );
            box-shadow:
                0 20px 35px
                rgba(67, 36, 20, .25);
        }
        .hero-coffee-bag {
            position: absolute;
            right: 10%;
            width: 130px;
            height: 190px;
            border-radius: 8px;
            transform:
                rotate(4deg);
            background:
                linear-gradient(
                    110deg,
                    #8c532e,
                    #c28b52
                );
            box-shadow:
                0 20px 30px
                rgba(67, 36, 20, .2);
        }
        .hero-coffee-bag::after {
            content: "TOKOKU";
            position: absolute;
            top: 70px;
            left: 20px;
            color: #fff4dc;
            font-family: Georgia, serif;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        /* ==================================================
           MAIN CONTENT
        ================================================== */
        .catalog-layout {
            display: grid;
            grid-template-columns:
                250px minmax(0, 1fr);
            gap: 25px;
            align-items: start;
        }
        /* ==================================================
           SIDEBAR
        ================================================== */
        .category-sidebar {
            border:
                1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            background: #fffdf9;
        }
        .category-side-item {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding:
                14px 15px;
            margin-bottom: 4px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #5f5045;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
            transition: .2s;
        }
        .category-side-item i {
            width: 24px;
            font-size: 21px;
            color: var(--brown);
        }
        .category-side-item:hover {
            background:
                var(--cream);
            color:
                var(--brown);
        }
        .category-side-item.active {
            background:
                linear-gradient(
                    90deg,
                    #ffe8bd,
                    #ffefcf
                );
            color:
                var(--brown-dark);
            font-weight: 600;
        }
        .category-side-item:last-child {
            margin-bottom: 0;
        }
        /* ==================================================
           PRODUCT SECTION
        ================================================== */
        .product-section {
            min-width: 0;
        }
        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .section-heading-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }
        .section-heading h2 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 26px;
            font-weight: 700;
            color:
                var(--brown-dark);
        }
        /* ==================================================
           PRODUCT GRID
        ================================================== */
        .product-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );
            gap: 14px;
        }
        .product-item {
            min-width: 0;
        }
        /* ==================================================
           PRODUCT CARD
        ================================================== */
        .product-card {
            height: 100%;
            padding: 10px;
            border:
                1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            box-shadow:
                0 2px 8px
                rgba(82, 48, 23, .04);
            transition:
                transform .2s,
                box-shadow .2s;
        }
        .product-card:hover {
            transform:
                translateY(-3px);
            box-shadow:
                0 10px 25px
                rgba(82, 48, 23, .10);
        }
        /* ==================================================
           PRODUCT IMAGE
        ================================================== */
        .product-thumb {
            position: relative;
            width: 100%;
            height: 165px;
            overflow: hidden;
            border-radius: 9px;
            background:
                linear-gradient(
                    135deg,
                    #f8e5c3,
                    #eee0cc
                );
        }
        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition:
                transform .3s ease;
        }
        .product-card:hover
        .product-thumb img {
            transform:
                scale(1.04);
        }
        .product-thumb .no-img {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #bba994;
        }
        /* ==================================================
           HEART
        ================================================== */
        .product-heart {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background:
                rgba(255, 255, 255, .9);
            color:
                var(--brown);
            font-size: 19px;
            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, .08);
        }
        /* ==================================================
           PRODUCT INFO
        ================================================== */
        .product-body {
            padding:
                10px 3px 2px;
        }
        .product-cat {
            display: none;
        }
        .product-name {
            margin-bottom: 5px;
            color: #3e2a1e;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
        }
        .product-rating {
            margin-bottom: 5px;
            color: #8a7a6d;
            font-size: 11px;
        }
        .product-rating .star {
            color: #e2a334;
            margin-right: 3px;
        }
        .product-price {
            margin-bottom: 10px;
            color:
                var(--brown);
            font-size: 15px;
            font-weight: 700;
        }
        /* ==================================================
           BUTTON
        ================================================== */
        .btn-cart {
            width: 100%;
            padding:
                10px 8px;
            border: none;
            border-radius: 8px;
            background:
                var(--brown);
            color: white;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }
        .btn-cart:hover {
            background:
                var(--brown-dark);
        }
        /* ==================================================
           EMPTY
        ================================================== */
        .empty-product {
            grid-column:
                1 / -1;
            padding:
                60px 20px;
            color:
                var(--muted);
            text-align: center;
        }
        /* ==================================================
           FOOTER
        ================================================== */
        footer {
            border-top:
                1px solid var(--border);
            margin-top: 40px;
            padding:
                25px 0;
            color: #999;
            font-size: 12px;
        }
        /* ==================================================
           TABLET
        ================================================== */
        @media (max-width: 1100px) {
            .home-container {
                padding-left: 25px;
                padding-right: 25px;
            }
            .hero {
                padding-left: 55px;
            }
            .catalog-layout {
                grid-template-columns:
                    190px minmax(0, 1fr);
            }
            .product-grid {
                grid-template-columns:
                    repeat(
                        3,
                        minmax(0, 1fr)
                    );
            }
            .product-thumb {
                height: 155px;
            }
        }
        /* ==================================================
           MOBILE TABLET
        ================================================== */
        @media (max-width: 800px) {
            .home-container {
                padding:
                    15px 18px 45px;
            }
            .hero {
                min-height: 300px;
                padding:
                    40px 35px;
            }
            .hero-decoration {
                opacity: .35;
                width: 60%;
            }
            .catalog-layout {
                grid-template-columns: 1fr;
            }
            .category-sidebar {
                display: flex;
                overflow-x: auto;
                gap: 5px;
                padding: 8px;
            }
            .category-side-item {
                width: auto;
                min-width: max-content;
                margin: 0;
                padding:
                    10px 14px;
            }
            .product-grid {
                grid-template-columns:
                    repeat(
                        3,
                        minmax(0, 1fr)
                    );
            }
        }
        /* ==================================================
           MOBILE
        ================================================== */
        @media (max-width: 600px) {
            .hero {
                min-height: 310px;
                padding:
                    35px 25px;
            }
            .hero h1 {
                font-size: 38px;
            }
            .hero p {
                font-size: 13px;
                max-width: 300px;
            }
            .hero-decoration {
                right: -30px;
                width: 65%;
                opacity: .25;
            }
            .section-heading h2 {
                font-size: 22px;
            }
            .product-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
                gap: 12px;
            }
            .product-thumb {
                height: 145px;
            }
            .product-name {
                font-size: 12px;
            }
            .product-price {
                font-size: 13px;
            }
            .btn-cart {
                font-size: 9px;
                padding:
                    9px 4px;
            }
        }
        /* ==================================================
           SMALL MOBILE
        ================================================== */
        @media (max-width: 380px) {
            .home-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .hero {
                padding:
                    30px 20px;
            }
            .hero h1 {
                font-size: 32px;
            }
            .product-thumb {
                height: 125px;
            }
        }
    </style>
</head>
<body>
<?php
$base = '../';
$activeNav = 'beranda';
include '../includes/navbar.php';
?>
<main class="home-container">
    <!-- ==================================================
         HERO
    ================================================== -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-label">
                Selamat datang di HomieCozie
            </span>
            <h1>
                Halo, <?= htmlspecialchars($namaUser) ?>
            </h1>
            <p>
                Temukan berbagai pilihan produk dengan
                cita rasa terbaik dari produk pilihan.
            </p>
            <a
                href="../katalog.php"
                class="hero-button">
                Lihat katalog
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="hero-decoration">
            <div class="hero-cup"></div>
            <div class="hero-coffee-bag"></div>
        </div>
    </section>
    <!-- ==================================================
         CONTENT
    ================================================== -->
    <section class="catalog-layout">
        <!-- SIDEBAR -->
        <aside class="category-sidebar">
            <button
                class="category-side-item active"
                data-cat="">
                <i class="bi bi-grid"></i>
                <span>
                    Semua
                </span>
            </button>
            <?php foreach ($categories as $cat): ?>
                <?php
                $namaKategori =
                    strtolower(
                        trim($cat['nama'])
                    );
                if (
                    $namaKategori === 'coffe' ||
                    $namaKategori === 'coffee'
                ) {
                    $icon = 'bi-cup-hot';
                } elseif (
                    $namaKategori === 'non-coffe' ||
                    $namaKategori === 'non-coffee'
                ) {
                    $icon = 'bi-cup-straw';
                } elseif (
                    $namaKategori === 'food'
                ) {
                    $icon = 'bi-egg-fried';
                } elseif (
                    $namaKategori === 'desert' ||
                    $namaKategori === 'dessert'
                ) {
                    $icon = 'bi-cake2';
                } else {
                    $icon = 'bi-grid';
                }
                ?>
                <button
                    class="category-side-item"
                    data-cat="<?= $cat['id'] ?>">
                    <i class="bi <?= $icon ?>"></i>
                    <span>
                        <?= htmlspecialchars(
                            $cat['nama']
                        ) ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </aside>
        <!-- PRODUCTS -->
        <section class="product-section">
            <div class="section-heading">
                <div class="section-heading-left">
                    <h2>
                        Produk Unggulan
                    </h2>
                </div>
            </div>
            <div
                class="product-grid"
                id="product-grid">
                <?php foreach ($products as $p): ?>
                    <div
                        class="product-item"
                        data-cat="<?= $p['category_id'] ?>">
                        <div class="product-card">
                            <!-- IMAGE -->
                            <div class="product-thumb">
                                <?php if (!empty($p['gambar'])): ?>
                                    <img
                                        src="../uploads/products/<?= htmlspecialchars($p['gambar']) ?>"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <i
                                        class="bi bi-box-seam no-img"
                                        style="display:none">
                                    </i>
                                <?php else: ?>
                                    <i
                                        class="bi bi-box-seam no-img">
                                    </i>
                                <?php endif; ?>
                                <div class="product-heart">
                                    ♡
                                </div>
                            </div>
                            <!-- PRODUCT INFO -->
                            <div class="product-body">
                                <div class="product-cat">
                                    <?= htmlspecialchars(
                                        $p['kategori'] ?? '-'
                                    ) ?>
                                </div>
                                <div class="product-name">
                                    <?= htmlspecialchars(
                                        $p['nama']
                                    ) ?>
                                </div>
                                <div class="product-rating">
                                    <span class="star">
                                        ★
                                    </span>
                                    4.7 (120)
                                </div>
                                <div class="product-price">
                                    <?= formatRupiah(
                                        $p['harga']
                                    ) ?>
                                </div>
                                <button
                                    class="btn-cart"
                                    type="button">
                                    Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <div class="empty-product">
                        Belum ada produk.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>
<?php
include '../footer.php';
?>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
<script>
document
    .querySelectorAll('.category-side-item')
    .forEach(btn => {
        btn.addEventListener('click', () => {
            document
                .querySelectorAll('.category-side-item')
                .forEach(b => {
                    b.classList.remove('active');
                });
            btn.classList.add('active');
            const cat =
                btn.dataset.cat;
            document
                .querySelectorAll('.product-item')
                .forEach(item => {
                    item.style.display =
                        (
                            !cat ||
                            item.dataset.cat === cat
                        )
                        ? ''
                        : 'none';
                });
        });
    });
</script>
</body>
</html>
