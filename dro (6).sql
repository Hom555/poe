-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 01, 2025 at 06:06 PM
-- Server version: 8.0.17
-- PHP Version: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dro`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_slip` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'รอการชำระเงิน',
  `shipping_name` varchar(100) DEFAULT NULL COMMENT 'ชื่อผู้รับ',
  `shipping_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรผู้รับ',
  `shipping_address` text COMMENT 'ที่อยู่จัดส่ง',
  `shipping_method` varchar(50) DEFAULT NULL COMMENT 'วิธีการจัดส่ง',
  `tracking_number` varchar(100) DEFAULT NULL COMMENT 'เลขติดตามพัสดุ',
  `shipping_date` datetime DEFAULT NULL COMMENT 'วันที่จัดส่ง',
  `delivery_date` datetime DEFAULT NULL COMMENT 'วันที่ส่งมอบ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `payment_slip`, `payment_date`, `status`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_method`, `tracking_number`, `shipping_date`, `delivery_date`) VALUES
(52, 7, '2025-09-25 04:53:02', '290.00', '1758775982_68d4caaecee45.png', '2025-09-25 11:52:50', 'รอตรวจสอบการชำระเงิน', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 2, '2025-10-01 18:00:14', '340.00', '1759341614_68dd6c2e8bf9a.png', '2025-10-02 01:00:07', 'รอตรวจสอบการชำระเงิน', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `size` varchar(10) DEFAULT NULL COMMENT 'ไซส์สินค้าที่ซื้อ',
  `color` varchar(50) DEFAULT NULL COMMENT 'สีสินค้าที่ซื้อ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`, `total`, `size`, `color`) VALUES
(67, 52, 17, 1, '170.00', '170.00', '3XL', 'ส้ม'),
(68, 52, 17, 1, '120.00', '120.00', 'S', 'เขียวอ่อน'),
(69, 53, 16, 1, '140.00', '140.00', 'L', 'ดำ'),
(70, 53, 18, 1, '200.00', '200.00', 'XXL', 'แดง');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'ชื่อสินค้า',
  `description` text COMMENT 'คำอธิบายสั้น',
  `type_id` int(3) UNSIGNED ZEROFILL NOT NULL COMMENT 'รหัสประเภทสินค้า',
  `color` varchar(50) DEFAULT NULL COMMENT 'สีของสินค้า',
  `image` varchar(255) NOT NULL COMMENT 'รูปภาพ',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `type_id`, `color`, `image`, `created_at`) VALUES
(15, 'เสื้อยืด', '', 005, NULL, '1758704238_68d3b26e19523.png', '2025-09-24 08:57:18'),
(16, 'เสื้อกันฝน', '', 002, NULL, '1758704312_68d3b2b8d923c.png', '2025-09-24 08:58:32'),
(17, 'เสื้อกันฝน', '', 002, NULL, '1758704408_68d3b31858539.webp', '2025-09-24 09:00:08'),
(18, 'เสื้อกันหนาว', '', 001, NULL, '1758704471_68d3b357603c7.webp', '2025-09-24 09:01:11'),
(19, 'เสื้อกันหนาว', '', 001, NULL, '1758704574_68d3b3be7a5e8.jpg', '2025-09-24 09:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_base_id` int(11) NOT NULL COMMENT 'รหัสสินค้าหลัก',
  `size` varchar(10) NOT NULL COMMENT 'ไซส์ (XS, S, M, L, XL, XXL, 3XL)',
  `color` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'สี',
  `image` varchar(255) DEFAULT NULL COMMENT 'รูปภาพสำหรับสีนี้',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคาตามไซส์',
  `amount` int(11) NOT NULL COMMENT 'จำนวนตามไซส์',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_base_id`, `size`, `color`, `image`, `price`, `amount`, `created_at`) VALUES
(70, 15, 'S', 'ดำ', '', '100.00', 20, '2025-09-24 08:57:18'),
(71, 15, 'S', 'น้ำเงิน', '1758704238_68d3b26e1aa29_s_น้ำเงิน.png', '100.00', 13, '2025-09-24 08:57:18'),
(72, 15, 'M', 'ขาว', '1758704238_68d3b26e1af3e_m_ขาว.png', '120.00', 20, '2025-09-24 08:57:18'),
(73, 15, 'L', 'น้ำเงิน', '1758704238_68d3b26e1b392_l_น้ำเงิน.png', '140.00', 10, '2025-09-24 08:57:18'),
(74, 16, 'L', 'ดำ', '1758736233_68d42f69bfab7_l_ดำ.jfif', '140.00', 9, '2025-09-24 08:58:32'),
(75, 16, 'L', 'เหลือง', '1758735752_68d42d88b2945_l_เหลือง.png', '140.00', 12, '2025-09-24 08:58:32'),
(76, 17, 'XS', 'ส้ม', '', '100.00', 20, '2025-09-24 09:00:08'),
(77, 17, 'S', 'ส้ม', '1758704408_68d3b31859639_s_ส้ม.webp', '120.00', 4, '2025-09-24 09:00:08'),
(78, 17, 'S', 'เขียวอ่อน', '1758704408_68d3b31859a3f_s_เขียวอ่อน.webp', '120.00', 3, '2025-09-24 09:00:08'),
(79, 17, '3XL', 'ส้ม', '1758704408_68d3b31859c58_3xl_ส้ม.webp', '170.00', 0, '2025-09-24 09:00:08'),
(80, 18, 'L', 'ดำ', '', '200.00', 20, '2025-09-24 09:01:11'),
(81, 18, 'XL', 'ดำ', '1758704471_68d3b357617ae_xl_ดำ.webp', '200.00', 20, '2025-09-24 09:01:11'),
(82, 18, 'XXL', 'ดำ', '1758704471_68d3b35761fa5_xxl_ดำ.webp', '200.00', 20, '2025-09-24 09:01:11'),
(83, 18, 'XXL', 'แดง', '1758736397_68d4300d7bcb5_xxl_แดง.webp', '200.00', 19, '2025-09-24 09:01:11'),
(84, 19, 'S', 'เขียว', '', '150.00', 10, '2025-09-24 09:02:54'),
(85, 19, 'M', 'เขียว', '1758704574_68d3b3be7af98_m_เขียว.jpg', '150.00', 5, '2025-09-24 09:02:54'),
(86, 19, 'XL', 'เขียว', '1758704574_68d3b3be7b551_xl_เขียว.jpg', '150.00', 10, '2025-09-24 09:02:54'),
(87, 19, 'XXL', 'เขียว', '1758704574_68d3b3be7b9ac_xxl_เขียว.jpg', '150.00', 10, '2025-09-24 09:02:54'),
(88, 16, 'XL', 'ดำ', '1758736233_68d42f69c06ad_xl_ดำ.jfif', '140.00', 5, '2025-09-24 14:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `type_id` int(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'รหัสสินค้า',
  `type_name` varchar(50) NOT NULL COMMENT 'ชื่อสินค้า'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `type`
--

INSERT INTO `type` (`type_id`, `type_name`) VALUES
(00001, 'เสื้อกันหนาว'),
(00002, 'เสื้อกันฝน'),
(00005, 'เสื้อยืด');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  `status` tinyint(1) DEFAULT '2' COMMENT '0=ระงับ, 1=admin, 2=user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `subdistrict` varchar(100) DEFAULT NULL,
  `zipcode` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `phone`, `address`, `status`, `created_at`, `name`, `province`, `district`, `subdistrict`, `zipcode`) VALUES
(1, 'root@gmail.com', '123456', '0987456321', NULL, 1, '2024-11-19 08:29:06', NULL, NULL, NULL, NULL, NULL),
(2, 'test@gmail.com', '123456', '0987456789', '12/7 หมู่ 2', 2, '2025-01-24 01:22:12', 'สมชาย ดำ', 'กรุงเทพมหานคร', 'บางนา', 'บางนาใต้', '10260'),
(7, 'test1@gmail.com', '123456', '0933355555', 'ชัยภูมิ', 2, '2025-08-20 08:05:14', 'สน ใจดี', 'ชัยภูมิ', 'เมืองชัยภูมิ', 'ท่าหินโงม', '36000'),
(8, 'test2@gmail.com', '123456', '0922554654', '-', 2, '2025-08-25 12:55:23', 'ไก่ ดำ', 'ชัยภูมิ', 'เมืองชัยภูมิ', 'ท่าหินโงม', '36000');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_products_with_sizes`
-- (See below for the actual view)
--
CREATE TABLE `v_products_with_sizes` (
`amount` int(11)
,`description` text
,`image` varchar(255)
,`price` decimal(10,2)
,`product_id` int(11)
,`product_name` varchar(255)
,`size` varchar(10)
,`size_id` int(11)
,`type_id` int(3) unsigned zerofill
,`type_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure for view `v_products_with_sizes`
--
DROP TABLE IF EXISTS `v_products_with_sizes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_products_with_sizes`  AS  select `p`.`id` AS `product_id`,`p`.`name` AS `product_name`,`p`.`description` AS `description`,`p`.`type_id` AS `type_id`,`t`.`type_name` AS `type_name`,`p`.`image` AS `image`,`ps`.`size` AS `size`,`ps`.`price` AS `price`,`ps`.`amount` AS `amount`,`ps`.`id` AS `size_id` from ((`products` `p` left join `type` `t` on((`p`.`type_id` = `t`.`type_id`))) left join `product_sizes` `ps` on((`p`.`id` = `ps`.`product_base_id`))) order by `p`.`name`,(case `ps`.`size` when 'XS' then 1 when 'S' then 2 when 'M' then 3 when 'L' then 4 when 'XL' then 5 when 'XXL' then 6 when '3XL' then 7 else 8 end) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_order_details_size_color` (`size`,`color`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_base_id` (`product_base_id`),
  ADD KEY `size` (`size`);

--
-- Indexes for table `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `type`
--
ALTER TABLE `type`
  MODIFY `type_id` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'รหัสสินค้า', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
