<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';

// =====================================================
// LOGIC
// =====================================================
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header("Location: user/loginUser.php?redirect=$redirect");
    exit;
}
// Ambil parameter
$q        = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sort     = $_GET['sort'] ?? 'terbaru';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;
// Filter
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(p.nama LIKE :q OR p.deskripsi LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($kategori !== '') {
    $where[] = "c.nama = :kategori";
    $params[':kategori'] = $kategori;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
// Sorting
$sortMap = [
    'terbaru'    => 'p.id DESC',
    'nama_asc'   => 'p.nama ASC',
    'nama_desc'  => 'p.nama DESC',
    'harga_asc'  => 'p.harga ASC',
    'harga_desc' => 'p.harga DESC'
];
$orderSQL = $sortMap[$sort] ?? $sortMap['terbaru'];
// Total data
$countSql = "
    SELECT COUNT(*)
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSQL
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalData = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalData / $perPage));
// Ambil produk
$sql = "
    SELECT 
        p.*,
        c.nama AS kategori
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSQL
    ORDER BY $orderSQL
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Ambil kategori
$categoryStmt = $pdo->query("
    SELECT DISTINCT nama
    FROM categories
    ORDER BY nama ASC
");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
// Build query
function buildQuery($overrides = [])
{
    $params = [
        'q'        => $_GET['q'] ?? '',
        'kategori' => $_GET['kategori'] ?? '',
        'sort'     => $_GET['sort'] ?? 'terbaru',
        'page'     => $_GET['page'] ?? 1
    ];
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null;
    });
    $qs = http_build_query($params);
    return htmlspecialchars(
        $qs === '' ? 'katalog.php' : '?' . $qs
    );
}
$base = '';
$activeNav = 'katalog';
include 'includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog — TokoKu</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* =====================================================
           CONTAINER UTAMA — SAMA SEPERTI BERANDA
        ===================================================== */
        .home-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 20px 35px 60px;
        }

        /* =====================================================
           HERO / BANNER KATALOG
        ===================================================== */
        .catalog-hero {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            border-radius: 18px;
            padding: 28px 34px;
            margin-bottom: 18px;
            background:
                linear-gradient(135deg,
                    #70401f 0%,
                    #8b542d 55%,
                    #a96f3f 100%);
            color: white;
            box-shadow:
                0 8px 24px rgba(112, 64, 31, 0.14);
        }

        .catalog-hero::before {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: -60px;
            top: -100px;
            background: rgba(255, 255, 255, 0.08);
        }

        .catalog-hero::after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            right: 130px;
            bottom: -100px;
            background: rgba(229, 168, 60, 0.15);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-small {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 11px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 12px;
            margin-bottom: 9px;
        }

        .catalog-hero h1 {
            margin: 0;
            font-size: 31px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .catalog-hero p {
            margin: 7px 0 0;
            color: #f9ead6;
            font-size: 14px;
        }

        .hero-coffee-icon {
            position: absolute;
            right: 45px;
            bottom: 25px;
            z-index: 2;
            font-size: 66px;
            color: rgba(255, 255, 255, 0.16);
        }

        /* =====================================================
           CONTAINER KATALOG
        ===================================================== */
        .catalog-shell {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 17px;
            padding: 20px;
            box-shadow:
                0 7px 25px rgba(87, 50, 25, 0.06);
        }

        /* =====================================================
           HEADER KATALOG
        ===================================================== */
        .catalog-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 15px;
            margin-bottom: 17px;
        }

        .catalog-heading h2 {
            margin: 0;
            color: var(--brown-dark);
            font-size: 23px;
            font-weight: 800;
        }

        .catalog-heading p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .result-info {
            padding: 7px 11px;
            border-radius: 9px;
            background: var(--cream);
            border: 1px solid var(--border);
            color: var(--brown);
            font-size: 12px;
            white-space: nowrap;
        }

        /* =====================================================
           TOOLBAR
        ===================================================== */
        .catalog-toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--brown-light);
            z-index: 2;
        }

        .search-box input {
            width: 100%;
            height: 43px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px 0 39px;
            outline: none;
            color: var(--text);
            background: #fff;
        }

        .search-box input:focus {
            border-color: var(--brown-light);
            box-shadow:
                0 0 0 3px rgba(112, 64, 31, 0.08);
        }

        .search-box input::placeholder {
            color: #aa9c8f;
        }

        /* =====================================================
           SORT BOX
        ===================================================== */
        .sort-box {
            height: 43px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 4px 9px 4px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            min-width: 210px;
        }

        .sort-box i {
            color: var(--brown);
            font-size: 15px;
        }

        .sort-box select {
            flex: 1;
            height: 100%;
            border: 0;
            outline: none;
            box-shadow: none !important;
            background: transparent;
            color: var(--text);
            font-size: 13px;
            cursor: pointer;
        }

        .sort-box:focus-within {
            border-color: var(--brown-light);
            box-shadow:
                0 0 0 3px rgba(112, 64, 31, 0.08);
        }

        .search-button {
            height: 43px;
            border: 0;
            border-radius: 10px;
            padding: 0 17px;
            background: var(--brown);
            color: white;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .search-button:hover {
            background: var(--brown-dark);
        }

        /* =====================================================
           KATEGORI
        ===================================================== */
        .category-area {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 11px;
            background: var(--cream);
            border: 1px solid var(--border);
        }

        .category-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--brown-dark);
            margin-right: 2px;
        }

        .category-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 31px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: white;
            color: var(--brown);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .category-pill:hover {
            border-color: var(--brown-light);
            background: var(--cream-2);
            color: var(--brown-dark);
        }

        .category-pill.active {
            background: var(--brown);
            border-color: var(--brown);
            color: white;
        }

        /* =====================================================
           PRODUCT GRID
        ===================================================== */
        .product-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .catalog-product-item {
            min-width: 0;
        }

        .product-card {
            height: 100%;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 7px;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-3px);
            border-color: #ddcdb7;
            box-shadow:
                0 10px 22px rgba(87, 50, 25, 0.10);
        }

        .product-thumb {
            position: relative;
            height: 180px;
            overflow: hidden;
            border-radius: 9px;
            background:
                linear-gradient(135deg,
                    #f5e8d4,
                    #fffaf2);
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-img {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #b09d8a;
            gap: 5px;
            font-size: 12px;
        }

        .no-img i {
            font-size: 31px;
        }

        .stock-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 5px 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--brown);
            font-size: 10px;
            font-weight: 700;
            border: 1px solid rgba(112, 64, 31, 0.10);
        }

        .product-body {
            padding: 10px 3px 3px;
        }

        .product-category {
            color: var(--muted);
            font-size: 10px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }

        .product-name {
            margin: 0 0 5px;
            color: var(--brown-dark);
            font-size: 14px;
            font-weight: 750;
            line-height: 1.35;
            min-height: 38px;
        }

        .product-price {
            color: var(--brown);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .quick-add {
            width: 100%;
            height: 35px;
            border: 0;
            border-radius: 8px;
            background: var(--brown);
            color: white;
            font-size: 11px;
            font-weight: 700;
            transition: 0.2s;
        }

        .quick-add:hover {
            background: var(--brown-dark);
        }

        .quick-add:disabled {
            background: #cfc3b7;
            cursor: not-allowed;
        }

        /* =====================================================
           EMPTY
        ===================================================== */
        .empty-products {
            text-align: center;
            padding: 55px 20px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: var(--cream);
        }

        .empty-products i {
            display: block;
            font-size: 42px;
            color: #b69a7e;
            margin-bottom: 10px;
        }

        .empty-products h5 {
            color: var(--brown-dark);
            font-weight: 800;
        }

        .empty-products p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 0;
        }

        .live-empty {
            display: none;
            text-align: center;
            padding: 40px 15px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: var(--cream);
        }

        .live-empty i {
            font-size: 35px;
            color: #b69a7e;
        }

        .live-empty p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* =====================================================
           PAGINATION
        ===================================================== */
        .pagination-area {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }

        .pagination {
            gap: 5px;
            margin: 0;
        }

        .page-link {
            border: 1px solid var(--border);
            border-radius: 8px !important;
            color: var(--brown);
            background: white;
            font-size: 12px;
            min-width: 34px;
            text-align: center;
        }

        .page-link:hover {
            background: var(--cream);
            color: var(--brown-dark);
            border-color: #d8c4aa;
        }

        .page-item.active .page-link {
            background: var(--brown);
            border-color: var(--brown);
            color: white;
        }

        /* =====================================================
           MODAL
        ===================================================== */
        .modal-content {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .modal-header {
            background: var(--cream);
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            color: var(--brown-dark);
            font-weight: 800;
        }

        .modal-product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            background: var(--cream-2);
        }

        #modalNoImg {
            height: 300px;
            border-radius: 10px;
            background: var(--cream-2);
            color: #b09d8a;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        #modalKategori {
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
        }

        #modalNama {
            color: var(--brown-dark);
            font-weight: 800;
        }

        #modalHarga {
            color: var(--brown);
            font-size: 20px;
            font-weight: 800;
        }

        #modalDeskripsi {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .qty-control {
            display: flex;
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 9px;
            width: fit-content;
            overflow: hidden;
        }

        .qty-control button {
            width: 38px;
            height: 38px;
            border: 0;
            background: var(--cream);
            color: var(--brown);
            font-weight: 800;
        }

        .qty-control input {
            width: 45px;
            height: 38px;
            border: 0;
            text-align: center;
            outline: none;
        }

        .modal-action-btn {
            border: 0;
            border-radius: 9px;
            background: var(--brown);
            color: white;
            font-weight: 700;
            padding: 10px 16px;
        }

        .modal-action-btn:hover {
            background: var(--brown-dark);
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */
        @media (max-width: 1100px) {
            .home-container {
                padding-left: 25px;
                padding-right: 25px;
            }

            .product-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .home-container {
                padding: 15px 18px 45px;
            }

            .catalog-hero {
                min-height: 140px;
                padding: 24px 25px;
            }

            .catalog-hero h1 {
                font-size: 27px;
            }

            .hero-coffee-icon {
                right: 25px;
                font-size: 52px;
            }

            .catalog-shell {
                padding: 15px;
            }

            .catalog-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .catalog-toolbar {
                flex-wrap: wrap;
            }

            .search-box {
                flex-basis: 100%;
            }

            .sort-box {
                flex: 1;
            }

            .search-button {
                flex: 0 0 auto;
            }

            .product-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .product-thumb {
                height: 155px;
            }
        }

        @media (max-width: 600px) {
            .catalog-hero {
                min-height: 125px;
                padding: 21px;
            }

            .catalog-hero h1 {
                font-size: 24px;
            }

            .catalog-hero p {
                font-size: 12px;
                max-width: 75%;
            }

            .hero-coffee-icon {
                right: 17px;
                font-size: 43px;
            }

            .catalog-shell {
                padding: 12px;
            }

            .sort-box {
                min-width: 0;
                flex: 1;
            }

            .search-button {
                padding-left: 13px;
                padding-right: 13px;
            }

            .product-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .product-thumb {
                height: 145px;
            }

            .product-name {
                font-size: 13px;
            }

            .product-price {
                font-size: 14px;
            }
        }

        @media (max-width: 380px) {
            .home-container {
                padding-left: 12px;
                padding-right: 12px;
            }

            .catalog-toolbar {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .search-box {
                grid-column: 1 / -1;
            }

            .sort-box {
                width: 100%;
            }

            .search-button {
                width: 100%;
            }

            .product-thumb {
                height: 125px;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="home-container">
            <!-- =================================================
             BANNER KATALOG
        ================================================== -->
            <section class="catalog-hero">
                <div class="hero-content">
                    <div class="hero-small">
                        <i class="bi bi-cup-hot-fill"></i>
                        TokoKu
                    </div>
                    <h1>Katalog Produk</h1>
                    <p>
                        Temukan kopi, minuman, dan makanan favoritmu.
                    </p>
                </div>
                <i class="bi bi-cup-hot-fill hero-coffee-icon"></i>
            </section>
            <!-- =================================================
             SEMUA PRODUK ADA DI CONTAINER INI
        ================================================== -->
            <section class="catalog-shell">
                <!-- HEADER -->
                <div class="catalog-heading">
                    <div>
                        <h2>
                            Pilihan Produk
                        </h2>
                        <p>
                            Pilih produk yang ingin kamu pesan.
                        </p>
                    </div>
                    <div class="result-info" id="resultInfo">
                        Menampilkan
                        <strong><?= count($products) ?></strong>
                        dari
                        <strong><?= $totalData ?></strong>
                        produk
                    </div>
                </div>
                <!-- =================================================
                 SEARCH + SORT
            ================================================== -->
                <form
                    method="GET"
                    action="katalog.php"
                    class="catalog-toolbar"
                    id="searchForm">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input
                            type="text"
                            name="q"
                            id="liveSearch"
                            value="<?= htmlspecialchars($q) ?>"
                            placeholder="Cari produk..."
                            autocomplete="off">
                    </div>
                    <div class="sort-box">
                        <i class="bi bi-filter"></i>
                        <select
                            name="sort"
                            onchange="this.form.submit()">
                            <option
                                value="terbaru"
                                <?= $sort === 'terbaru' ? 'selected' : '' ?>>
                                Terbaru
                            </option>
                            <option
                                value="nama_asc"
                                <?= $sort === 'nama_asc' ? 'selected' : '' ?>>
                                Nama A - Z
                            </option>
                            <option
                                value="nama_desc"
                                <?= $sort === 'nama_desc' ? 'selected' : '' ?>>
                                Nama Z - A
                            </option>
                            <option
                                value="harga_asc"
                                <?= $sort === 'harga_asc' ? 'selected' : '' ?>>
                                Harga Terendah
                            </option>
                            <option
                                value="harga_desc"
                                <?= $sort === 'harga_desc' ? 'selected' : '' ?>>
                                Harga Tertinggi
                            </option>
                        </select>
                    </div>
                    <button
                        type="submit"
                        class="search-button">
                        <i class="bi bi-search me-1"></i>
                        Cari
                    </button>
                    <?php if ($kategori !== ''): ?>
                        <input
                            type="hidden"
                            name="kategori"
                            value="<?= htmlspecialchars($kategori) ?>">
                    <?php endif; ?>
                </form>
                <!-- =================================================
                 KATEGORI
            ================================================== -->
                <div class="category-area">
                    <span class="category-title">
                        <i class="bi bi-grid me-1"></i>
                        Kategori
                    </span>
                    <a
                        href="<?= buildQuery([
                                    'kategori' => '',
                                    'page' => 1
                                ]) ?>"
                        class="category-pill <?= $kategori === '' ? 'active' : '' ?>">
                        Semua
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a
                            href="<?= buildQuery([
                                        'kategori' => $cat['nama'],
                                        'page' => 1
                                    ]) ?>"
                            class="category-pill <?= $kategori === $cat['nama'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['nama']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- =================================================
                 PRODUCT GRID
            ================================================== -->
                <?php if (count($products) > 0): ?>
                    <div
                        class="product-grid"
                        id="productGrid">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $gambar = $product['gambar'] ?? '';
                            $nama = $product['nama'] ?? '';
                            $deskripsi = $product['deskripsi'] ?? '';
                            $stok = (int)($product['stok'] ?? 0);
                            $harga = (float)($product['harga'] ?? 0);
                            $kategoriProduk = $product['kategori'] ?? '';
                            ?>
                            <div
                                class="catalog-product-item"
                                data-search="<?= htmlspecialchars(
                                                    $nama . ' ' . $kategoriProduk
                                                ) ?>"
                                data-category="<?= htmlspecialchars(
                                                    $kategoriProduk
                                                ) ?>">
                                <div
                                    class="product-card"
                                    data-bs-toggle="modal"
                                    data-bs-target="#produkModal"
                                    data-id="<?= (int)$product['id'] ?>"
                                    data-nama="<?= htmlspecialchars($nama) ?>"
                                    data-harga="<?= htmlspecialchars($harga) ?>"
                                    data-kategori="<?= htmlspecialchars(
                                                        $kategoriProduk
                                                    ) ?>"
                                    data-stok="<?= $stok ?>"
                                    data-deskripsi="<?= htmlspecialchars(
                                                        $deskripsi
                                                    ) ?>"
                                    data-gambar="<?= htmlspecialchars(
                                                        $gambar
                                                    ) ?>">
                                    <!-- IMAGE -->
                                    <div class="product-thumb">
                                        <?php if ($gambar): ?>
                                            <img
                                                src="uploads/products/<?= htmlspecialchars($gambar) ?>"
                                                alt="<?= htmlspecialchars($nama) ?>"
                                                onerror="
                                                this.style.display='none';
                                                this.nextElementSibling.style.display='flex';
                                            ">
                                        <?php endif; ?>
                                        <div
                                            class="no-img"
                                            style="<?= $gambar ? 'display:none;' : 'display:flex;' ?>">
                                            <i class="bi bi-cup-hot"></i>
                                            <span>
                                                Tidak ada gambar
                                            </span>
                                        </div>
                                        <?php if ($stok > 0): ?>
                                            <span class="stock-badge">
                                                Stok <?= $stok ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-badge">
                                                Habis
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- PRODUCT INFO -->
                                    <div class="product-body">
                                        <div class="product-category">
                                            <?= htmlspecialchars(
                                                $kategoriProduk ?: 'Produk'
                                            ) ?>
                                        </div>
                                        <h3 class="product-name">
                                            <?= htmlspecialchars($nama) ?>
                                        </h3>
                                        <div class="product-price">
                                            Rp <?= number_format(
                                                    $harga,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                        </div>
                                        <button
                                            type="button"
                                            class="quick-add"
                                            <?= $stok <= 0 ? 'disabled' : '' ?>
                                            onclick="
                                            event.stopPropagation();
                                            quickAdd(
                                                <?= (int)$product['id'] ?>,
                                                this
                                            );
                                        ">
                                            <i class="bi bi-cart-plus me-1"></i>
                                            <?= $stok > 0
                                                ? 'Tambah ke Keranjang'
                                                : 'Stok Habis'
                                            ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- LIVE SEARCH EMPTY -->
                    <div
                        class="live-empty"
                        id="liveEmpty">
                        <i class="bi bi-search"></i>
                        <p>
                            Produk yang kamu cari tidak ditemukan.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="empty-products">
                        <i class="bi bi-box-seam"></i>
                        <h5>
                            Produk tidak ditemukan
                        </h5>
                        <p>
                            Coba gunakan kata kunci atau kategori lain.
                        </p>
                    </div>
                <?php endif; ?>
                <!-- =================================================
                 PAGINATION
            ================================================== -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-area">
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a
                                            class="page-link"
                                            href="<?= buildQuery([
                                                        'page' => $page - 1
                                                    ]) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li
                                        class="page-item
                                    <?= $i == $page ? 'active' : '' ?>">
                                        <a
                                            class="page-link"
                                            href="<?= buildQuery([
                                                        'page' => $i
                                                    ]) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a
                                            class="page-link"
                                            href="<?= buildQuery([
                                                        'page' => $page + 1
                                                    ]) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <!-- =========================================================
     MODAL DETAIL PRODUK
========================================================= -->
    <div
        class="modal fade"
        id="produkModal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Detail Produk
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- IMAGE -->
                        <div class="col-md-6">
                            <img
                                id="modalImg"
                                class="modal-product-image"
                                src=""
                                alt="">
                            <div
                                id="modalNoImg"
                                style="display:none;">
                                <i
                                    class="bi bi-image"
                                    style="font-size:45px;">
                                </i>
                                Tidak ada gambar
                            </div>
                        </div>
                        <!-- INFO -->
                        <div class="col-md-6">
                            <div id="modalKategori"></div>
                            <h3
                                id="modalNama"
                                class="mt-1 mb-2">
                            </h3>
                            <div
                                id="modalHarga"
                                class="mb-2">
                            </div>
                            <div
                                id="modalStok"
                                class="mb-3">
                            </div>
                            <p id="modalDeskripsi"></p>
                            <!-- BELI AREA -->
                            <div
                                id="modalBeliArea"
                                style="display:none;">
                                <div class="mb-2 fw-semibold">
                                    Jumlah
                                </div>
                                <div class="qty-control mb-3">
                                    <button
                                        type="button"
                                        id="modalQtyMinus">
                                        −
                                    </button>
                                    <input
                                        type="number"
                                        id="modalQty"
                                        value="1"
                                        min="1">
                                    <button
                                        type="button"
                                        id="modalQtyPlus">
                                        +
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <button
                                        type="button"
                                        id="btnTambahKeranjang"
                                        class="modal-action-btn">
                                        <i class="bi bi-cart-plus me-1"></i>
                                        Tambah Keranjang
                                    </button>
                                    <button
                                        type="button"
                                        id="btnBeliSekarang"
                                        class="modal-action-btn">
                                        <i class="bi bi-bag-check me-1"></i>
                                        Beli Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* =========================================================
   LIVE SEARCH
   ========================================================= */
        const liveSearch = document.getElementById('liveSearch');
        const productItems = Array.from(
            document.querySelectorAll('.catalog-product-item')
        );
        const liveEmpty = document.getElementById('liveEmpty');
        const resultInfo = document.getElementById('resultInfo');

        function filterProductsLive() {
            if (!liveSearch) return;
            const keyword = liveSearch.value
                .trim()
                .toLowerCase();
            let visibleCount = 0;
            productItems.forEach(function(item) {
                const searchText = (
                    item.dataset.search || ''
                ).toLowerCase();
                const category = (
                    item.dataset.category || ''
                ).toLowerCase();
                const match =
                    keyword === '' ||
                    searchText.includes(keyword) ||
                    category.includes(keyword);
                if (match) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            if (liveEmpty) {
                liveEmpty.style.display =
                    productItems.length > 0 &&
                    visibleCount === 0 ?
                    'block' :
                    'none';
            }
            if (resultInfo) {
                if (keyword === '') {
                    resultInfo.innerHTML =
                        `Menampilkan
                <strong><?= count($products) ?></strong>
                dari
                <strong><?= $totalData ?></strong>
                produk`;
                } else {
                    resultInfo.innerHTML =
                        `Menampilkan
                <strong>${visibleCount}</strong>
                produk`;
                }
            }
        }
        if (liveSearch) {
            liveSearch.addEventListener(
                'input',
                filterProductsLive
            );
            filterProductsLive();
        }
        /* =========================================================
           QUICK ADD
           ========================================================= */
        function quickAdd(productId, btn) {
            if (!btn) return;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Menambahkan...';
            fetch('cart_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=add' +
                        '&product_id=' +
                        encodeURIComponent(productId) +
                        '&qty=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        updateCartBadge(data.count);
                        btn.innerHTML =
                            '<i class="bi bi-check-lg me-1"></i> Ditambahkan';
                        setTimeout(function() {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 1200);
                    } else {
                        alert(
                            data.message ||
                            'Gagal menambahkan produk.'
                        );
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert(
                        'Terjadi kesalahan. Silakan coba lagi.'
                    );
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }
        /* =========================================================
           CART BADGE
           ========================================================= */
        function updateCartBadge(count) {
            const badges =
                document.querySelectorAll(
                    '.cart-badge, #cartBadge'
                );
            badges.forEach(function(badge) {
                badge.textContent = count;
                badge.style.display =
                    count > 0 ?
                    'inline-block' :
                    'none';
            });
        }
        /* =========================================================
           MODAL PRODUK
           ========================================================= */
        let modalProductId = null;
        let modalStock = 0;
        const produkModal =
            document.getElementById('produkModal');
        produkModal.addEventListener(
            'show.bs.modal',
            function(event) {
                const card =
                    event.relatedTarget;
                if (!card) return;
                modalProductId =
                    card.dataset.id;
                modalStock =
                    parseInt(
                        card.dataset.stok || '0'
                    );
                const nama =
                    card.dataset.nama || '';
                const harga =
                    parseFloat(
                        card.dataset.harga || '0'
                    );
                const kategori =
                    card.dataset.kategori || '';
                const stok =
                    parseInt(
                        card.dataset.stok || '0'
                    );
                const deskripsi =
                    card.dataset.deskripsi || '';
                const gambar =
                    card.dataset.gambar || '';
                document.getElementById(
                    'modalNama'
                ).textContent = nama;
                document.getElementById(
                        'modalKategori'
                    ).textContent =
                    kategori || 'Produk';
                document.getElementById(
                        'modalHarga'
                    ).textContent =
                    'Rp ' +
                    new Intl.NumberFormat(
                        'id-ID'
                    ).format(harga);
                document.getElementById(
                        'modalStok'
                    ).innerHTML =
                    stok > 0 ?
                    '<span class="badge text-bg-success">' +
                    'Stok tersedia: ' +
                    stok +
                    '</span>' :
                    '<span class="badge text-bg-danger">' +
                    'Stok habis' +
                    '</span>';
                document.getElementById(
                        'modalDeskripsi'
                    ).textContent =
                    deskripsi ||
                    'Tidak ada deskripsi produk.';
                const modalImg =
                    document.getElementById(
                        'modalImg'
                    );
                const modalNoImg =
                    document.getElementById(
                        'modalNoImg'
                    );
                if (gambar) {
                    modalImg.src =
                        'uploads/products/' +
                        gambar;
                    modalImg.style.display =
                        'block';
                    modalNoImg.style.display =
                        'none';
                    modalImg.onerror =
                        function() {
                            this.style.display =
                                'none';
                            modalNoImg.style.display =
                                'flex';
                        };
                } else {
                    modalImg.style.display =
                        'none';
                    modalNoImg.style.display =
                        'flex';
                }
                const beliArea =
                    document.getElementById(
                        'modalBeliArea'
                    );
                if (stok > 0) {
                    beliArea.style.display =
                        'block';
                } else {
                    beliArea.style.display =
                        'none';
                }
                document.getElementById(
                    'modalQty'
                ).value = 1;
            }
        );
        /* =========================================================
           QTY
           ========================================================= */
        document.getElementById(
            'modalQtyMinus'
        ).addEventListener(
            'click',
            function() {
                const input =
                    document.getElementById(
                        'modalQty'
                    );
                let qty =
                    parseInt(input.value || '1');
                qty--;
                if (qty < 1) {
                    qty = 1;
                }
                input.value = qty;
            }
        );
        document.getElementById(
            'modalQtyPlus'
        ).addEventListener(
            'click',
            function() {
                const input =
                    document.getElementById(
                        'modalQty'
                    );
                let qty =
                    parseInt(input.value || '1');
                qty++;
                if (modalStock > 0 &&
                    qty > modalStock) {
                    qty = modalStock;
                }
                input.value = qty;
            }
        );
        /* =========================================================
           MODAL ADD CART
           ========================================================= */
        document.getElementById(
            'btnTambahKeranjang'
        ).addEventListener(
            'click',
            function() {
                const qty =
                    parseInt(
                        document.getElementById(
                            'modalQty'
                        ).value || '1'
                    );
                if (!modalProductId) return;
                fetch('cart_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=add' +
                            '&product_id=' +
                            encodeURIComponent(
                                modalProductId
                            ) +
                            '&qty=' +
                            encodeURIComponent(qty)
                    })
                    .then(response =>
                        response.json()
                    )
                    .then(data => {
                        if (data.ok) {
                            updateCartBadge(
                                data.count
                            );
                            const modal =
                                bootstrap.Modal.getInstance(
                                    produkModal
                                );
                            if (modal) {
                                modal.hide();
                            }
                        } else {
                            alert(
                                data.message ||
                                'Gagal menambahkan produk.'
                            );
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        alert(
                            'Terjadi kesalahan.'
                        );
                    });
            }
        );
        /* =========================================================
           BUY NOW
           ========================================================= */
        document.getElementById(
            'btnBeliSekarang'
        ).addEventListener(
            'click',
            function() {
                if (!modalProductId) return;
                const qty =
                    parseInt(
                        document.getElementById(
                            'modalQty'
                        ).value || '1'
                    );
                window.location.href =
                    'checkout.php?beli=' +
                    encodeURIComponent(
                        modalProductId
                    ) +
                    '&qty=' +
                    encodeURIComponent(qty);
            }
        );
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>