<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: loginUser.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id_user = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE id_order = ?");
function statusBadge($status) {
    $map = [
        'Menunggu Konfirmasi' => 'bg-warning-subtle text-warning-emphasis',
        'Dikemas'             => 'bg-info-subtle text-info-emphasis',
        'Dikirim'             => 'bg-primary-subtle text-primary-emphasis',
        'Selesai'             => 'bg-success-subtle text-success-emphasis',
        'Dibatalkan'          => 'bg-danger-subtle text-danger-emphasis',
    ];
    $class = $map[$status] ?? 'bg-secondary-subtle text-secondary-emphasis';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan — TokoKu</title>
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
            background:
                radial-gradient(circle at 10% 10%, rgba(229, 168, 60, .10), transparent 28%),
                radial-gradient(circle at 90% 20%, rgba(112, 64, 31, .08), transparent 30%),
                var(--cream);
            color: var(--text);
        }
        /* =========================
           CONTAINER
        ========================= */
        .home-container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 35px 35px 70px;
        }
        /* =========================
           PAGE HEADER
        ========================= */
        .history-header {
            background: linear-gradient(
                135deg,
                var(--brown-dark),
                var(--brown)
            );
            border-radius: 24px;
            padding: 38px 42px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 35px rgba(77, 42, 23, .14);
        }
        .history-header::before {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            right: -70px;
            top: -90px;
        }
        .history-header::after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(229,168,60,.10);
            right: 130px;
            bottom: -90px;
        }
        .history-header-content {
            position: relative;
            z-index: 2;
        }
        .history-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            color: #f5d59b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .history-header h1 {
            margin: 0;
            color: white;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -1px;
        }
        .history-header p {
            margin: 9px 0 0;
            color: rgba(255,255,255,.72);
            font-size: 14px;
        }
        /* =========================
           ORDER LIST
        ========================= */
        .orders-wrapper {
            width: 100%;
        }
        .orders-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .orders-title i {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--cream-2);
            color: var(--brown);
        }
        /* =========================
           ORDER CARD
        ========================= */
        .order-card {
            width: 100%;
            background: rgba(255,255,255,.94);
            border: 1px solid var(--border);
            border-radius: 20px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(77,42,23,.06);
            transition: .2s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(77,42,23,.10);
        }
        /* =========================
           ORDER TOP
        ========================= */
        .order-top {
            padding: 22px 25px;
            background: linear-gradient(
                180deg,
                #fffdf9,
                #fffaf3
            );
            border-bottom: 1px solid var(--border);
        }
        .order-code {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }
        .order-code-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: var(--cream-2);
            color: var(--brown);
            flex-shrink: 0;
        }
        .order-code strong {
            display: block;
            font-size: 15px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .order-date {
            font-size: 12px;
            color: var(--muted);
        }
        .order-status {
            margin-top: 14px;
        }
        .order-status .badge {
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        /* =========================
           PRODUCT SECTION
        ========================= */
        .order-products {
            padding: 22px 25px;
        }
        .section-label {
            display: block;
            margin-bottom: 13px;
            font-size: 11px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .product-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 13px 15px;
            margin-bottom: 9px;
            background: #fffaf3;
            border: 1px solid #f0e4d3;
            border-radius: 12px;
        }
        .product-left {
            min-width: 0;
        }
        .product-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 3px;
        }
        .product-qty {
            font-size: 12px;
            color: var(--muted);
        }
        .product-price {
            flex-shrink: 0;
            font-size: 13px;
            font-weight: 800;
            color: var(--brown);
        }
        /* =========================
           ORDER INFO
        ========================= */
        .order-info {
            padding: 0 25px 24px;
        }
        .info-box {
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 9px 0;
            font-size: 13px;
        }
        .info-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
        }
        .info-label i {
            color: var(--brown);
            font-size: 15px;
        }
        .info-value {
            text-align: right;
            font-weight: 600;
            color: var(--text);
        }
        .total-row {
            margin-top: 7px;
            padding-top: 15px;
            border-top: 1px dashed var(--border);
        }
        .total-row .info-label {
            color: var(--brown-dark);
            font-weight: 700;
        }
        .total-row .info-value {
            color: var(--brown);
            font-size: 18px;
            font-weight: 800;
        }
        /* =========================
           EMPTY STATE
        ========================= */
        .empty-card {
            background: rgba(255,255,255,.95);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 65px 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(77,42,23,.05);
        }
        .empty-icon {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            border-radius: 22px;
            background: var(--cream-2);
            color: var(--brown);
            font-size: 32px;
        }
        .empty-card h3 {
            margin-bottom: 7px;
            font-size: 20px;
            font-weight: 800;
            color: var(--brown-dark);
        }
        .empty-card p {
            margin-bottom: 23px;
            font-size: 13px;
            color: var(--muted);
        }
        .btn-shop {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border: none;
            border-radius: 11px;
            background: var(--brown);
            color: white;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }
        .btn-shop:hover {
            background: var(--brown-dark);
            color: white;
            transform: translateY(-1px);
        }
        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 1100px) {
            .home-container {
                padding-left: 25px;
                padding-right: 25px;
            }
        }
        @media (max-width: 800px) {
            .home-container {
                padding: 20px 18px 45px;
            }
            .history-header {
                padding: 30px 25px;
                border-radius: 20px;
            }
            .history-header h1 {
                font-size: 28px;
            }
            .order-top,
            .order-products {
                padding-left: 20px;
                padding-right: 20px;
            }
            .order-info {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
        @media (max-width: 550px) {
            .history-header {
                padding: 25px 20px;
            }
            .history-header h1 {
                font-size: 24px;
            }
            .history-header p {
                font-size: 12px;
            }
            .order-card {
                border-radius: 16px;
            }
            .product-line {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }
            .product-price {
                font-size: 12px;
            }
            .info-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
            .info-value {
                text-align: left;
            }
            .total-row {
                flex-direction: row;
                align-items: center;
            }
            .total-row .info-value {
                text-align: right;
            }
        }
        @media (max-width: 380px) {
            .home-container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .history-header h1 {
                font-size: 22px;
            }
            .order-top,
            .order-products {
                padding-left: 16px;
                padding-right: 16px;
            }
            .order-info {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
</head>
<body>
<?php
$base = '../';
$activeNav = 'riwayat';
include '../includes/navbar.php';
?>
<div class="home-container">
    <!-- HEADER -->
    <div class="history-header">
        <div class="history-header-content">
            <div class="history-label">
                <i class="bi bi-clock-history"></i>
                Pesanan Kamu
            </div>
            <h1>Riwayat Pesanan</h1>
            <p>
                Lihat kembali semua pesanan yang pernah kamu buat di TokoKu.
            </p>
        </div>
    </div>
    <!-- ORDERS -->
    <div class="orders-wrapper">
        <?php if (empty($orders)): ?>
            <!-- EMPTY -->
            <div class="empty-card">
                <div class="empty-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3>Belum Ada Pesanan</h3>
                <p>
                    Kamu belum memiliki riwayat pesanan.
                    Yuk cari makanan dan minuman favoritmu.
                </p>
                <a href="../katalog.php" class="btn-shop">
                    <i class="bi bi-bag"></i>
                    Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <div class="orders-title">
                <i class="bi bi-receipt"></i>
                <span>Pesanan Terbaru</span>
            </div>
            <?php foreach ($orders as $o): ?>
                <?php
                    $stmtItems->execute([$o['id_order']]);
                    $its = $stmtItems->fetchAll();
                ?>
                <div class="order-card">
                    <!-- ORDER HEADER -->
                    <div class="order-top">
                        <div class="order-code">
                            <div class="order-code-icon">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div>
                                <strong>
                                    <?= htmlspecialchars($o['kode_pesanan']) ?>
                                </strong>
                                <div class="order-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?= date(
                                        'd M Y, H:i',
                                        strtotime($o['created_at'])
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="order-status">
                            <?= statusBadge($o['status']) ?>
                        </div>
                    </div>
                    <!-- PRODUCTS -->
                    <div class="order-products">
                        <span class="section-label">
                            Detail Produk
                        </span>
                        <?php foreach ($its as $it): ?>
                            <div class="product-line">
                                <div class="product-left">
                                    <div class="product-name">
                                        <?= htmlspecialchars(
                                            $it['nama_produk']
                                        ) ?>
                                    </div>
                                    <div class="product-qty">
                                        Jumlah:
                                        <?= $it['qty'] ?>
                                    </div>
                                </div>
                                <div class="product-price">
                                    <?= formatRupiah(
                                        $it['subtotal']
                                    ) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- ORDER INFORMATION -->
                    <div class="order-info">
                        <div class="info-box">
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="bi bi-truck"></i>
                                    <span>Pengiriman</span>
                                </div>
                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $o['label_pengiriman']
                                    ) ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Pembayaran</span>
                                </div>
                                <div class="info-value">
                                    COD
                                </div>
                            </div>
                            <div class="info-row total-row">
                                <div class="info-label">
                                    <span>Total Pesanan</span>
                                </div>
                                <div class="info-value">
                                    <?= formatRupiah(
                                        $o['total_bayar']
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>