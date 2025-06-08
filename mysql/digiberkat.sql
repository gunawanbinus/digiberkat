-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 08 Jun 2025 pada 21.12
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
-- Database: `digiberkat`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `auto_expire_orders` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE order_id INT;
    DECLARE cart_item_id INT;
    DECLARE product_id INT;
    DECLARE product_variant_id INT;
    DECLARE quantity INT;

    -- Cursor untuk mengambil order yang memenuhi syarat
    DECLARE order_cursor CURSOR FOR
        SELECT id FROM orders WHERE status = 'pending' AND created_at < NOW() - INTERVAL 24 HOUR;
    -- Cursor untuk mengembalikan stok untuk setiap item dalam order
     DECLARE item_cursor CURSOR FOR
        SELECT id, product_id, product_variant_id, quantity FROM order_items WHERE order_id = order_id;

    -- Handler untuk mengakhiri cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Mulai loop untuk memproses setiap order
    OPEN order_cursor;

    read_loop: LOOP
        FETCH order_cursor INTO order_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Ubah status order menjadi 'expired'
        UPDATE orders SET status = 'expired' WHERE id = order_id;

        -- Mengembalikan stok untuk setiap item dalam order
        OPEN item_cursor;

        item_loop: LOOP
            FETCH item_cursor INTO cart_item_id, product_id, product_variant_id, quantity;
            IF done THEN
                LEAVE item_loop;
            END IF;

            -- Jika item tidak memiliki varian
            IF product_variant_id IS NULL THEN
                UPDATE products SET stock = stock + quantity WHERE id = product_id;
            ELSE
                -- Jika item memiliki varian
                UPDATE product_variants SET stock = stock + quantity WHERE id = product_variant_id;
            END IF;
        END LOOP item_loop;

        CLOSE item_cursor;
    END LOOP read_loop;

    CLOSE order_cursor;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `username`, `thumbnail_url`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$MS5krNtkY30VwugIrbdGruw/U3JB7tSdNnXnom.0Rw4sfqB8PIsZK', '2025-06-08 14:14:59', '2025-06-08 14:14:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `total_price`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2025-06-08 22:20:24', '2025-06-09 01:09:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart_items`
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
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nama kategori produk, maksimal 100 karakter',
  `description` text DEFAULT NULL COMMENT 'Deskripsi kategori produk'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
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
-- Struktur dari tabel `employees`
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
-- Struktur dari tabel `notifications`
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
-- Struktur dari tabel `orders`
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
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `cart_user_id`, `status`, `total_price`, `expired_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'done', 60000, '2025-06-08 15:25:40', '2025-06-08 15:25:40', '2025-06-08 18:13:28'),
(2, 1, 1, 'pending', 194000, '2025-06-08 18:11:02', '2025-06-08 18:11:02', '2025-06-08 18:11:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
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
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_variant_id`, `quantity`, `price_at_purchase`, `total_price`) VALUES
(1, 1, 1, NULL, 2, 10000, 20000),
(2, 1, 3, 1, 2, 20000, 40000),
(3, 2, 3, 2, 2, 22000, 44000),
(4, 2, 5, 6, 1, 150000, 150000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `position`
--

CREATE TABLE `position` (
  `position_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Deskripsi posisi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `position`
--

INSERT INTO `position` (`position_name`, `description`) VALUES
('cashier', 'melayani customer saat di toko'),
('manager', 'menghandle dan memantau toko'),
('stocker', 'menajemen stock di toko');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
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
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `is_varians`, `is_discounted`, `discount_price`, `price`, `stock`, `search_vector`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gantungan Kunci Anime', 'Gantungan kunci karakter anime dari akrilik.', 0, 0, NULL, 10000, 48, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(2, 2, 'Casing HP iPhone 13', 'Casing transparan anti-selip untuk iPhone 13.', 0, 1, 35000, 50000, 30, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(3, 3, 'Tempered Glass Universal', 'Tempered Glass untuk berbagai ukuran layar.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(4, 4, 'Phone Holder Mobil', 'Holder HP untuk mobil, bisa putar 360 derajat.', 1, 0, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40'),
(5, 5, 'Headphone Gaming', 'Headphone over-ear untuk gaming dengan mic.', 1, 1, NULL, NULL, NULL, NULL, '2025-05-26 16:58:40', '2025-05-26 16:58:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `product_images`
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
-- Struktur dari tabel `product_variants`
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
-- Dumping data untuk tabel `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `name`, `price`, `is_discounted`, `discount_price`, `stock`, `search_vector`, `created_at`, `updated_at`) VALUES
(1, 3, 'Tempered Glass 5.5 inch', 20000, 0, NULL, 38, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(2, 3, 'Tempered Glass 6.1 inch', 22000, 0, NULL, 28, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(3, 4, 'Holder Dashboard', 30000, 1, 25000, 20, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(4, 4, 'Holder AC Vent', 28000, 0, NULL, 25, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(5, 5, 'Headphone Warna Hitam', 150000, 1, 125000, 15, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36'),
(6, 5, 'Headphone Warna Merah', 150000, 0, NULL, 9, NULL, '2025-05-26 16:59:36', '2025-05-26 16:59:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `restock_requests`
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

--
-- Dumping data untuk tabel `restock_requests`
--

INSERT INTO `restock_requests` (`id`, `user_id`, `product_id`, `product_variant_id`, `message`, `status`, `created_at`) VALUES
(1, 1, 5, 6, 'hmm', 'unread', '2025-06-09 01:39:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `thumbnail_url`, `password`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'aa@gm.com', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', '$2a$10$YTdgaIu29GcJWd8FJlrWPOtI0hI4vr0zV3xWcYtqYd5zWcYuYaOJS', NULL, '2025-06-08 22:20:24', '2025-06-08 22:20:24');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carts_user` (`user_id`);

--
-- Indeks untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cart_items_cart` (`cart_id`),
  ADD KEY `fk_cart_items_product` (`product_id`),
  ADD KEY `fk_cart_items_variant` (`product_variant_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`username`),
  ADD KEY `fk_employee_position` (`position_name`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_cart` (`cart_user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `fk_order_items_variant` (`product_variant_id`);

--
-- Indeks untuk tabel `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`position_name`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indeks untuk tabel `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_images_product` (`product_id`);

--
-- Indeks untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_variants_product` (`product_id`);

--
-- Indeks untuk tabel `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_restock_user` (`user_id`),
  ADD KEY `fk_restock_product` (`product_id`),
  ADD KEY `fk_restock_variant` (`product_variant_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID wajib', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_position` FOREIGN KEY (`position_name`) REFERENCES `position` (`position_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_cart` FOREIGN KEY (`cart_user_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD CONSTRAINT `fk_restock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_restock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_restock_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Event
--
CREATE DEFINER=`digiberkat`@`localhost` EVENT `run_auto_expire_orders` ON SCHEDULE EVERY 1 HOUR STARTS '2025-06-08 19:03:57' ON COMPLETION NOT PRESERVE ENABLE DO CALL auto_expire_orders()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
