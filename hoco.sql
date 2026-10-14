-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 14 Okt 2026 pada 10.21
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kopikita`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$hAeEmD.5ozpAMmNl0zd0xOUbyoEnCtX3EAPA.d7K0ildLAmj4dUwW', '2026-05-31 12:58:11'),
(2, 'admin2', '$2y$10$JUJ7WSpsWlmBybgZ2TX/UOcRVU9AyXekZghHY6t7FEQku/QiiIf3u', '2026-05-31 15:51:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `nama`, `created_at`) VALUES
(1, 'Coffe', '2026-05-31 12:58:11'),
(2, 'Non-Coffe\r\n', '2026-05-31 12:58:11'),
(3, 'Desert', '2026-05-31 12:58:11'),
(4, 'Food', '2026-05-31 12:58:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `kode_pesanan` varchar(20) NOT NULL,
  `nama_penerima` varchar(150) NOT NULL,
  `no_hp` varchar(25) NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `metode_pengiriman` varchar(30) NOT NULL,
  `label_pengiriman` varchar(100) NOT NULL,
  `ongkir` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metode_pembayaran` varchar(20) NOT NULL DEFAULT 'COD',
  `catatan` text DEFAULT NULL,
  `subtotal_produk` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id_order`, `id_user`, `kode_pesanan`, `nama_penerima`, `no_hp`, `alamat_pengiriman`, `metode_pengiriman`, `label_pengiriman`, `ongkir`, `metode_pembayaran`, `catatan`, `subtotal_produk`, `total_bayar`, `status`, `created_at`) VALUES
(1, 8, 'INV261008267B50', 'triya', '087872727272', 'pepeufoihgdf', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', 15000.00, 'COD', '', 64000.00, 79000.00, 'Menunggu Konfirmasi', '2026-10-08 15:51:14'),
(2, 8, 'INV261008B49893', 'triya', '0838348738', 'pepeufoihgdf', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', 15000.00, 'COD', '', 32000.00, 47000.00, 'Menunggu Konfirmasi', '2026-10-08 18:06:19'),
(3, 8, 'INV26100820376C', 'triya', '0838348738', 'pepeufoihgdf', 'instan', 'Instan / Same Day', 25000.00, 'COD', '', 20000.00, 45000.00, 'Menunggu Konfirmasi', '2026-10-08 18:38:26'),
(4, 9, 'INV261009C5F920', 'farpih', '0838348738', 'afjgfkgj', 'kargo', 'Kargo', 12000.00, 'COD', '', 70000.00, 82000.00, 'Menunggu Konfirmasi', '2026-10-09 22:11:40'),
(5, 10, 'INV2610106DAC00', 'farhansayangtriya', '08571234567', 'pondokkribok', 'instan', 'Instan / Same Day', 25000.00, 'COD', 'ponbhammmm', 84000.00, 109000.00, 'Menunggu Konfirmasi', '2026-10-10 09:41:58'),
(6, 10, 'INV26101316F3B1', 'farhansayangtriya', '087689780987', 'pondokkribok', 'kargo', 'Kargo', 12000.00, 'COD', '', 20000.00, 32000.00, 'Menunggu Konfirmasi', '2026-10-13 13:22:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id_item` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id_item`, `id_order`, `id_produk`, `nama_produk`, `harga_satuan`, `qty`, `subtotal`) VALUES
(1, 1, 5, 'Croisant', 32000.00, 2, 64000.00),
(2, 2, 9, 'Salted-Coffe', 32000.00, 1, 32000.00),
(3, 3, 7, 'Americano', 20000.00, 1, 20000.00),
(4, 4, 3, 'Matcha', 35000.00, 2, 70000.00),
(5, 5, 7, 'Americano', 20000.00, 1, 20000.00),
(6, 5, 9, 'Salted-Coffe', 32000.00, 2, 64000.00),
(7, 6, 13, 'Donut', 20000.00, 1, 20000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `category_id`, `nama`, `harga`, `stok`, `gambar`, `deskripsi`, `created_at`) VALUES
(2, 1, 'Capuccino', 30000.00, 34, 'coffe2.jpeg', 'Perpaduan espresso yang kaya rasa, susu creamy, dan busa lembut di atasnya, menciptakan cita rasa kopi yang seimbang, nikmat, dan memanjakan setiap tegukan.', '2026-05-31 12:58:11'),
(3, 2, 'Matcha', 35000.00, 48, 'non-coffe1.jpg', 'Matcha creamy dengan rasa teh hijau yang khas, sedikit pahit, dan menyegarkan.', '2026-05-31 12:58:11'),
(4, 2, 'Chocolate', 25000.00, 30, 'non-coffe2.jpeg', 'Es cokelat dingin dengan rasa cokelat manis dan creamy, cocok untuk menyegarkan hari.', '2026-05-31 12:58:11'),
(5, 3, 'Croisant', 32000.00, 38, 'kue1.jpeg', 'Croissant renyah di luar, lembut dan berlapis di dalam, dengan rasa buttery yang nikmat.', '2026-05-31 12:58:11'),
(6, 4, 'Fettuccine', 55000.00, 60, 'makanan.jpeg', 'Fettuccine lembut dengan saus creamy gurih yang lezat, cocok untuk pencinta pasta dengan cita rasa klasik.', '2026-05-31 12:58:11'),
(7, 1, 'Americano', 20000.00, 48, 'coffe1.jpeg', 'coffe americano yang sangat nikmat', '2026-05-31 14:04:43'),
(9, 1, 'Salted-Coffe', 32000.00, 7, 'coffe3.jpeg', 'triya sayang', '2026-10-05 18:55:47'),
(10, 1, 'Coffe-Latte', 34000.00, 27, 'coffe4.jpeg', 'Coffee latte adalah minuman kopi yang dibuat dari espresso dan susu steamed, dengan rasa kopi yang lembut, creamy, dan sedikit manis. Cocok untuk dinikmati kapan saja.', '2026-10-06 09:04:19'),
(11, 2, 'Thai-Tea', 20000.00, 25, 'non-coffe4.jpeg', 'Thai Tea adalah minuman teh khas Thailand dengan rasa teh yang kuat, manis, dan creamy, disajikan dingin dengan es sehingga terasa segar dan nikmat.', '2026-10-06 09:04:19'),
(12, 3, 'Waffle', 50000.00, 19, 'kue2.jpeg', 'Waffle adalah camilan manis dengan tekstur renyah di luar dan lembut di dalam, cocok disajikan dengan berbagai topping seperti cokelat, keju, buah, atau es krim.', '2026-10-06 09:04:19'),
(13, 3, 'Donut', 20000.00, 45, 'kue3.jpeg', 'Donut adalah camilan manis dengan tekstur lembut dan empuk, tersedia dalam berbagai rasa dan topping yang menarik, cocok untuk teman ngopi atau sebagai camilan ringan', '2026-10-06 09:04:19'),
(14, 3, 'Salt-Bread', 25000.00, 0, 'kue4.jpeg', 'Salt Bread adalah roti lembut dengan rasa gurih dan sedikit asin, memiliki aroma butter yang khas serta tekstur luar yang ringan dan renyah. Cocok dinikmati sebagai camilan atau teman minum kopi.', '2026-10-06 09:04:19'),
(15, 4, 'Cordon-Bleu', 57000.00, 23, 'makanan2.jpeg', 'Cordon Bleu adalah hidangan ayam yang diisi dengan keju dan smoked beef, lalu dilapisi tepung hingga renyah di luar dan lembut serta gurih di dalam.', '2026-10-06 09:04:19'),
(16, 4, 'Spaghetti-Bolognese', 46000.00, 36, 'makanan3.jpeg', 'Spaghetti Bolognese adalah pasta spaghetti dengan saus tomat yang gurih dan kaya rasa, dipadukan dengan daging cincang serta bumbu khas Italia. Cocok untuk menu makan yang lezat dan mengenyangkan.', '2026-10-06 09:04:19'),
(17, 4, 'Nachos with cheese', 27000.00, 49, 'makanan4.jpeg', 'Nachos with Cheese adalah camilan renyah berupa nachos yang disajikan dengan saus keju creamy dan gurih. Cocok untuk teman santai atau sharing bersama.', '2026-10-06 09:04:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(25) NOT NULL,
  `alamat` varchar(50) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `nama`, `alamat`, `username`, `password`) VALUES
(1, 'rakha', 'london', 'rakha', '$2y$10$qMDe'),
(2, 'alien', 'Cijantung', 'Alien', '$2y$10$ZvNZ'),
(3, 'jeki', 'Cilodong', 'Jeki', 'Jeki123'),
(5, 'Ghani', 'london', 'ghani', '$2y$10$faRP'),
(6, 'Lionel messi', 'london', 'messi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(7, 'jaka', 'cilodong', 'Zaka', '$2y$10$4DphpVXQT05hqty9m7AkTOP7FlN2pPuwk8VbD0c8M7gWPYAtb3fFa'),
(8, 'triya', 'pepeufoihgdf', 'tribon', '$2y$10$Ny7SXZlCotQUZm/KdbYAuOTUKHRYL2lhQEtLOgXPOmS/fSqR3hxla'),
(9, 'farpih', 'afjgfkgj', 'pihfar', '$2y$10$X0BIGW5N7Kt76LfdlJPpOeI4Whos0Q9TF4WqjB1omUw19H3nMSuQC'),
(10, 'farhansayangtriya', 'pondokkribok', 'farhantriya', '$2y$10$pymUJUaGYeImSfpOSQG/XO50rhESqlUN.ouTiHBeMvLzZrvHU3gTa');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id_item`),
  ADD KEY `id_order` (`id_order`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
