-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 05, 2025 at 07:33 AM
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
  `username` int(11) NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('stocker', 'menajemen stock di toko');

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
(1, 1, 'Gantungan Kunci Anime', 'Gantungan kunci karakter anime dari akrilik.', 0, 0, NULL, 10000, 50, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(2, 2, 'Casing HP iPhone 13', 'Casing transparan anti-selip untuk iPhone 13.', 0, 1, 35000, 50000, 30, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(3, 3, 'Tempered Glass Universal', 'Tempered Glass untuk berbagai ukuran layar.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(4, 4, 'Phone Holder Mobil', 'Holder HP untuk mobil, bisa putar 360 derajat.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(5, 5, 'Headphone Gaming', 'Headphone over-ear untuk gaming dengan mic.', 1, 1, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL
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
(6, 1, 'https://ik.imagekit.io/digiberkat/empty_QnMeqANY6', 'https://ik.imagekit.io/digiberkat/tr:n-ik_ml_thumbnail/empty_QnMeqANY6');

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
(1, 3, 'Tempered Glass 5.5 inch', 20000, 0, NULL, 40, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(2, 3, 'Tempered Glass 6.1 inch', 22000, 0, NULL, 30, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(3, 4, 'Holder Dashboard', 30000, 1, 25000, 20, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(4, 4, 'Holder AC Vent', 28000, 0, NULL, 25, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(5, 5, 'Headphone Warna Hitam', 150000, 1, 125000, 15, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(6, 5, 'Headphone Warna Merah', 150000, 0, NULL, 10, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36');

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `username` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID wajib', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`digiberkat`@`localhost` EVENT `auto_expire_status` ON SCHEDULE EVERY 1 HOUR STARTS '2025-06-05 12:32:28' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE orders
SET status = 'expired'
WHERE status = 'pending' AND created_at <= NOW() - INTERVAL 24 HOUR$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
