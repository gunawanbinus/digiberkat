-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 12:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `digiberkat`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `username` varchar(50) NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`username`, `thumbnail_url`, `password`, `created_at`, `updated_at`) VALUES
('michel@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$qYfLnwltO2kva.jeAun//exszK0pfFfZvfdiMQ0plzlLQng9cXHNe', '2025-06-06 16:42:47', '2025-06-06 16:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `total_price`, `created_at`, `updated_at`) VALUES
(1, 1, 48000, '2025-06-04 18:01:44', '2025-06-25 17:43:35'),
(2, 2, 0, '2025-06-05 16:59:59', '2025-06-05 16:59:59'),
(3, 3, 0, '2025-06-06 16:51:52', '2025-06-06 16:51:52'),
(4, 4, 0, '2025-06-09 11:03:34', '2025-06-09 11:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL COMMENT 'Relasi ke user_id di carts',
  `product_id` int(11) NOT NULL,
  `product_variant_id` int(11) DEFAULT NULL COMMENT 'NULL jika tidak ada varian',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_per_item` int(11) NOT NULL,
  `total_price` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `product_variant_id`, `quantity`, `price_per_item`, `total_price`, `created_at`, `updated_at`) VALUES
(18, 1, 4, 3, 1, 25000, 25000, '2025-06-21 22:52:04', '2025-06-25 17:43:35'),
(21, 1, 10, NULL, 1, 23000, 23000, '2025-06-25 10:37:25', '2025-06-25 10:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nama kategori produk, maksimal 100 karakter',
  `description` text DEFAULT NULL COMMENT 'Deskripsi kategori produk'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Gantungan Kunci', 'Kategori produk gantungan kunci untuk berbagai model dan desain.'),
(2, 'Casing HP', 'Kategori casing pelindung untuk berbagai merek dan tipe HP.'),
(3, 'Tempered Glass', 'Kategori pelindung layar tempered glass berkualitas tinggi.'),
(4, 'Phone Holder', 'Kategori alat penyangga HP untuk mobil, meja, dan lainnya.'),
(5, 'Earphone', 'Kategori earphone berkabel dan nirkabel dengan kualitas suara jernih.'),
(6, 'Headphone', 'Kategori headphone dengan fitur noise cancelling dan kenyamanan maksimal.'),
(7, 'Pulsa', 'Kategori untuk pembelian pulsa elektronik semua operator.'),
(8, 'Kuota', 'Kategori untuk pembelian paket data internet dari berbagai provider.');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL COMMENT 'Email harus unik dan valid',
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `position_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `username`, `thumbnail_url`, `position_name`, `password`, `created_at`, `updated_at`) VALUES
(1, 'odelia@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'stocker', '$2a$10$r9/Vyqf04gIBiaUVul95duY5JXE9/M1CJrieaIXHa0z3kezG1Ccmi', '2025-06-05 10:05:24', '2025-06-05 10:05:24'),
(2, 'emm@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'cashier', '$2a$10$M7bJy0sIWcRQYrEtSIOAJO6JzSP9jx5wRRkxADVnhgn3izlOed4eu', '2025-06-08 11:13:59', '2025-06-08 11:13:59'),
(4, 'o@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'cashier', '$2a$10$vEvzl4yoC6sAvvgQUErJHuXMeRScrdRH7wFK7TJI1wKtJ7OX4GdpK', '2025-06-08 11:35:01', '2025-06-08 11:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cart_user_id` int(11) DEFAULT NULL COMMENT 'Opsional, untuk pelacakan',
  `status` varchar(50) NOT NULL COMMENT 'e.g. "pending", "done", "cancelled", "expired"',
  `total_price` int(11) NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `cart_user_id`, `status`, `total_price`, `expired_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'pending', 40000, '2025-06-10 12:42:42', '2025-06-10 12:42:42', '2025-06-10 12:42:42'),
(2, 1, 1, 'cancelled', 150000, '2025-06-10 22:58:53', '2025-06-10 22:58:53', '2025-06-10 23:19:38'),
(3, 1, 1, 'cancelled', 50000, '2025-06-11 02:53:54', '2025-06-11 02:53:54', '2025-06-11 04:06:39'),
(4, 1, 1, 'pending', 190000, '2025-06-11 05:13:47', '2025-06-11 05:13:47', '2025-06-11 05:13:47'),
(5, 1, 1, 'cancelled', 65000, '2025-06-12 12:32:07', '2025-06-12 12:32:07', '2025-06-12 12:32:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_variant_id` int(11) DEFAULT NULL COMMENT 'null jika tidak pakai varian',
  `quantity` int(11) NOT NULL,
  `price_at_purchase` int(11) NOT NULL COMMENT 'Harga yang dibekukan saat checkout',
  `total_price` int(11) NOT NULL COMMENT 'quantity * price_at_purchase'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_variant_id`, `quantity`, `price_at_purchase`, `total_price`) VALUES
(1, 1, 1, NULL, 2, 10000, 20000),
(2, 1, 3, 1, 1, 20000, 20000),
(3, 2, 5, 6, 1, 150000, 150000),
(4, 3, 3, 1, 2, 20000, 40000),
(5, 3, 1, NULL, 1, 10000, 10000),
(6, 4, 2, NULL, 2, 35000, 70000),
(7, 4, 1, NULL, 12, 10000, 120000),
(8, 5, 1, NULL, 4, 10000, 40000),
(9, 5, 4, 3, 1, 25000, 25000);

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `position_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Deskripsi posisi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`position_name`, `description`) VALUES
('cashier', 'melayani customer saat di toko'),
('manager', 'menghandle dan memantau toko'),
('stocker', 'stocker mengatur stock');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL COMMENT 'ID wajib',
  `category_id` int(11) DEFAULT NULL COMMENT 'Setiap produk harus punya kategori',
  `name` varchar(100) NOT NULL COMMENT 'Nama produk wajib',
  `description` text NOT NULL COMMENT 'Deskripsi wajib (bisa pendek)',
  `is_varians` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Harus ada, default tidak punya varian',
  `is_discounted` tinyint(1) DEFAULT 0 COMMENT 'Harus ada, default tidak diskon',
  `discount_price` int(11) DEFAULT NULL COMMENT 'Boleh NULL kalau tidak diskon',
  `price` int(11) DEFAULT NULL COMMENT 'Harga utama wajib',
  `stock` int(11) DEFAULT NULL COMMENT 'Stok produk',
  `search_vector` text DEFAULT NULL COMMENT 'Boleh NULL, diisi nanti oleh AI',
  `created_at` datetime NOT NULL COMMENT 'Tanggal dibuat wajib',
  `updated_at` datetime NOT NULL COMMENT 'Tanggal update terakhir wajib'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `is_varians`, `is_discounted`, `discount_price`, `price`, `stock`, `search_vector`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gantungan Kunci Anime', 'Gantungan kunci karakter anime dari akrilik.', 0, 0, NULL, 10000, 36, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(2, 2, 'Casing HP iPhone 13', 'Casing transparan anti-selip untuk iPhone 13.', 0, 1, 35000, 50000, 28, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(3, 3, 'Tempered Glass Universal', 'Tempered Glass untuk berbagai ukuran layar.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(4, 4, 'Phone Holder Mobil', 'Holder HP untuk mobil, bisa putar 360 derajat.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(5, 5, 'Headphone Gaming', 'Headphone over-ear untuk gaming dengan mic.', 1, 1, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(6, 1, 'Gantungan Kunci Rajutan Lebah', 'Gantungan kunci lucu berbentuk lebah rajutan, cocok untuk hadiah atau koleksi.', 0, 1, 20000, 25000, 10, NULL, '2025-06-25 02:26:49', '2025-06-25 02:26:49'),
(7, 1, 'Gantungan Kunci Rajutan Gurita', 'Gantungan kunci rajutan berbentuk gurita imut, cocok untuk hiasan tas atau hadiah unik.', 0, 1, 22000, 27000, 12, NULL, '2025-06-25 02:31:05', '2025-06-25 02:31:05'),
(8, 7, 'Top Up Pulsa 5.000', 'Isi ulang pulsa Rp5.000 berlaku kelipatan (misalnya Rp10.000, Rp15.000, dst). Tersedia untuk berbagai operator.', 1, 0, NULL, NULL, NULL, NULL, '2025-06-25 02:38:33', '2025-06-25 02:38:33'),
(9, 2, 'Casing HP Infinix Hot 10S', 'Casing pelindung untuk Infinix Hot 10S, ringan, kuat, dan nyaman digenggam.', 0, 0, NULL, 25000, 20, NULL, '2025-06-25 02:42:45', '2025-06-25 02:42:45'),
(10, 1, 'Gantungan Kunci Rajutan Paus', 'Gantungan kunci rajutan berbentuk paus (whale) yang lucu dan unik, cocok untuk hadiah atau koleksi.', 0, 1, 23000, 28000, 15, NULL, '2025-06-25 02:48:39', '2025-06-25 02:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `thumbnail_url`) VALUES
(1, 1, 'https://ik.imagekit.io/digiberkat/ganci-boneka_kQvckopJL', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/ganci-boneka_kQvckopJL'),
(2, 2, 'https://ik.imagekit.io/digiberkat/Casing_HP_iPhone_13_b-C4Aor96', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/Casing_HP_iPhone_13_b-C4Aor96'),
(3, 3, 'https://ik.imagekit.io/digiberkat/Tempered_Glass_Universal_qDNwFQ2XS', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/Tempered_Glass_Universal_qDNwFQ2XS'),
(4, 4, 'https://ik.imagekit.io/digiberkat/Phone_Holder_Mobil_WQpTNSqlQ', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/Phone_Holder_Mobil_WQpTNSqlQ'),
(5, 5, 'https://ik.imagekit.io/digiberkat/Headphone_Gaming_CxBsWC2Ay', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/Headphone_Gaming_CxBsWC2Ay'),
(6, 1, 'https://ik.imagekit.io/digiberkat/empty_QnMeqANY6', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/empty_QnMeqANY6'),
(7, 6, 'https://ik.imagekit.io/digiberkat/bee-0_-KOzmOIvS', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/bee-0_-KOzmOIvS'),
(8, 6, 'https://ik.imagekit.io/digiberkat/bee-0_seNNbv3j8', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/bee-0_seNNbv3j8'),
(9, 7, 'https://ik.imagekit.io/digiberkat/octopus-1_B7rrayUp-', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/octopus-1_B7rrayUp-'),
(10, 7, 'https://ik.imagekit.io/digiberkat/octopus-2_g6ZoJLEO3', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/octopus-2_g6ZoJLEO3'),
(11, 8, 'https://ik.imagekit.io/digiberkat/pulsa_GwU9E36Hx', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/pulsa_GwU9E36Hx'),
(12, 9, 'https://ik.imagekit.io/digiberkat/casing-infinix_T-HdumlTe', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/casing-infinix_T-HdumlTe'),
(13, 10, 'https://ik.imagekit.io/digiberkat/paus-whale_8Ihi3orvu', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/paus-whale_8Ihi3orvu');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL COMMENT 'Referensi produk induk',
  `name` varchar(100) NOT NULL COMMENT 'Nama varian produk',
  `price` int(11) NOT NULL COMMENT 'Harga varian',
  `is_discounted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Status diskon varian',
  `discount_price` int(11) DEFAULT NULL COMMENT 'Harga diskon varian',
  `stock` int(11) NOT NULL COMMENT 'Stok varian produk',
  `search_vector` text DEFAULT NULL COMMENT 'Boleh NULL, diisi nanti oleh AI',
  `created_at` datetime NOT NULL COMMENT 'Tanggal dibuat wajib',
  `updated_at` datetime NOT NULL COMMENT 'Tanggal update terakhir wajib'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `name`, `price`, `is_discounted`, `discount_price`, `stock`, `search_vector`, `created_at`, `updated_at`) VALUES
(1, 3, 'Tempered Glass 5.5 inch', 20000, 0, NULL, 39, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(2, 3, 'Tempered Glass 6.1 inch', 22000, 0, NULL, 30, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(3, 4, 'Holder Dashboard', 30000, 1, 25000, 20, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(4, 4, 'Holder AC Vent', 28000, 0, NULL, 25, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(5, 5, 'Headphone Warna Hitam', 150000, 1, 125000, 15, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(6, 5, 'Headphone Warna Merah', 150000, 0, NULL, 10, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(7, 8, 'Telkomsel', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34'),
(8, 8, 'XL', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34'),
(9, 8, 'Tri', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34'),
(10, 8, 'Indosat', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34'),
(11, 8, 'Smartfren', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34'),
(12, 8, 'Axis', 6000, 0, NULL, 100, NULL, '2025-06-25 02:38:34', '2025-06-25 02:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_variant_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','done') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_stock_details`
--

CREATE TABLE `temp_stock_details` (
  `id` int(11) NOT NULL,
  `temp_reservation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_stock_reservations`
--

CREATE TABLE `temp_stock_reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL COMMENT 'Nama unik untuk login, maksimal 50 karakter',
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Nomor telepon dengan kode negara, opsional',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `thumbnail_url`, `password`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'budi@example.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$Wby7caLFS74tSsQozWBEEOVECNaEfOG/UsGCCibHqiPJaBKsna.3i', NULL, '2025-06-04 18:01:44', '2025-06-04 18:01:44'),
(2, 'mitul@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$oM/aSyEnkRhMnA4YkA6AyOgaLEzm6VkcSSHzwg4eCc7pT9El3C3TW', NULL, '2025-06-05 16:59:59', '2025-06-05 16:59:59'),
(3, 'odel@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$eiZieS8cP.pl0MXo/5A9He4nvxjTRQdbWY5VrhgXTYEFfYR6A.jTy', NULL, '2025-06-06 16:51:52', '2025-06-06 16:51:52'),
(4, 'test@gmail.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$tMy2EEMWDRTemw3RsaHm/uoxmyPy3VwZDyk/0b9stdmk9Ait.y51W', NULL, '2025-06-09 11:03:34', '2025-06-09 11:03:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carts_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cart_items_cart` (`cart_id`),
  ADD KEY `fk_cart_items_product` (`product_id`),
  ADD KEY `fk_cart_items_variant` (`product_variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`username`),
  ADD KEY `fk_employee_position` (`position_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_cart` (`cart_user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `fk_order_items_variant` (`product_variant_id`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`position_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_images_product` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_variants_product` (`product_id`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_restock_user` (`user_id`),
  ADD KEY `fk_restock_product` (`product_id`),
  ADD KEY `fk_restock_variant` (`product_variant_id`);

--
-- Indexes for table `temp_stock_details`
--
ALTER TABLE `temp_stock_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_temp_stock_details_reservation` (`temp_reservation_id`),
  ADD KEY `fk_temp_stock_details_product` (`product_id`),
  ADD KEY `fk_temp_stock_details_variant` (`product_variant_id`);

--
-- Indexes for table `temp_stock_reservations`
--
ALTER TABLE `temp_stock_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_temp_reservations_user` (`user_id`),
  ADD KEY `fk_temp_reservations_order` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID wajib', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `temp_stock_details`
--
ALTER TABLE `temp_stock_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_stock_reservations`
--
ALTER TABLE `temp_stock_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_position` FOREIGN KEY (`position_name`) REFERENCES `position` (`position_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_cart` FOREIGN KEY (`cart_user_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD CONSTRAINT `fk_restock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_restock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_restock_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `temp_stock_details`
--
ALTER TABLE `temp_stock_details`
  ADD CONSTRAINT `fk_temp_stock_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_temp_stock_details_reservation` FOREIGN KEY (`temp_reservation_id`) REFERENCES `temp_stock_reservations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_temp_stock_details_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `temp_stock_reservations`
--
ALTER TABLE `temp_stock_reservations`
  ADD CONSTRAINT `fk_temp_reservations_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_temp_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
  
  DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `auto_expire_orders` ON SCHEDULE EVERY 1 HOUR STARTS '2025-06-10 14:25:19' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
  -- Ambil ID order yang harus di-expire
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_order_id INT;

  DECLARE order_cursor CURSOR FOR
    SELECT id FROM orders
    WHERE status = 'pending' AND created_at < NOW() - INTERVAL 12 HOUR;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN order_cursor;

  read_loop: LOOP
    FETCH order_cursor INTO v_order_id;
    IF done THEN
      LEAVE read_loop;
    END IF;

    -- Update status order menjadi expired
    UPDATE orders SET status = 'expired' WHERE id = v_order_id;

    -- Kembalikan stok ke product
    UPDATE products p
    JOIN order_items oi ON oi.product_id = p.id
    SET p.stock = p.stock + oi.quantity
    WHERE oi.order_id = v_order_id AND oi.product_variant_id IS NULL;

    -- Kembalikan stok ke product_variants jika ada
    UPDATE product_variants pv
    JOIN order_items oi ON oi.product_variant_id = pv.id
    SET pv.stock = pv.stock + oi.quantity
    WHERE oi.order_id = v_order_id AND oi.product_variant_id IS NOT NULL;

  END LOOP;

  CLOSE order_cursor;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
