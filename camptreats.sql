-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 01:26 PM
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
-- Database: `camptreats`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `buyer_id`, `menu_item_id`, `quantity`, `date_added`) VALUES
(76, 16, 57, 1, '2025-12-03 00:33:45'),
(77, 16, 58, 1, '2025-12-03 02:11:08');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Drinks'),
(2, 'Snacks'),
(3, 'Meals');

-- --------------------------------------------------------

--
-- Table structure for table `item_images`
--

CREATE TABLE `item_images` (
  `image_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_images`
--

INSERT INTO `item_images` (`image_id`, `menu_item_id`, `image`) VALUES
(51, 53, '53_1763485038_1c1f7d622633.png'),
(52, 54, '54_1763486701_1c295ea8e2ac.jpg'),
(53, 55, '55_1763487340_8ee2213ba677.jpg'),
(54, 56, '56_1763487541_35c87ab1a053.jpg'),
(55, 57, '57_1763618234_473e756c85.jpeg'),
(56, 57, '57_1763618234_be3d127d10.jpeg'),
(57, 57, '57_1763618234_e1647389f7.jpeg'),
(58, 58, '58_1764208597_74aa0f16aa.jpeg'),
(59, 59, '59_1764660141_9a0a95bcf1.jpg'),
(66, 62, '62_1764681634_519492ecbe.jpeg'),
(67, 62, '62_1764681634_b14f54b63e.jpeg'),
(68, 62, '62_1764681634_7c0040a60d.jpeg'),
(69, 63, '63_1764721742_89cda99fb6.jpeg'),
(70, 64, '64_1764728259_29dfbebf20.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `menu_item_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','unavailable') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`menu_item_id`, `seller_id`, `name`, `description`, `price`, `category_id`, `quantity`, `created_at`, `status`) VALUES
(53, 4, 'asdasad', 'asdasjfidsdvio', 1.00, 3, 0, '2025-11-18 16:57:18', 'unavailable'),
(54, 8, 'sdfs', 'adfdfsv', 1.00, 3, 0, '2025-11-18 17:25:01', 'unavailable'),
(55, 8, 'geafs', 'dsfgsdg', 2.00, 1, 1, '2025-11-18 17:35:40', 'rejected'),
(56, 7, 'fhgfhf', 'dhsfdh', 4.00, 3, 1, '2025-11-18 17:39:01', 'unavailable'),
(57, 1, 'Fried Chicken', 'Yummyyyyyyyyyyyyyyyyyyyyyy', 150.00, 3, 1, '2025-11-20 05:57:14', 'approved'),
(58, 15, 'chicken', 'yemmm', 50.00, 3, 0, '2025-11-27 01:56:37', 'unavailable'),
(59, 17, 'hotdog', 'dfgfh', 20.00, 3, 17, '2025-12-02 07:22:21', 'approved'),
(62, 1, 'Lemon Juice w/ free citirizen', 'idunnooo na talagaaaa mennnn. Basta may lemon/ suha ni sya. allergy pa kamo ka daa???OA??', 35.00, 1, 5, '2025-12-02 13:20:33', 'approved'),
(63, 17, 'siomai', 'yummy', 5.00, 2, 8, '2025-12-03 00:29:02', 'approved'),
(64, 17, 'cake', 'jakcnv', 10.00, 2, 10, '2025-12-03 02:17:39', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sender` enum('buyer','seller') NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `buyer_id`, `seller_id`, `message`, `created_at`, `sender`, `is_read`) VALUES
(17, 5, 1, 'xcacaaaaaaaaacas', '2025-11-30 06:02:35', 'buyer', 1),
(18, 5, 1, 'hehehehe', '2025-11-30 06:03:39', 'buyer', 1),
(19, 5, 1, 'csac', '2025-11-30 06:09:16', 'buyer', 1),
(20, 5, 15, 'dsfsafds', '2025-11-30 06:33:43', 'buyer', 0),
(21, 5, 15, 'tama na ta fleece', '2025-11-30 06:37:28', 'buyer', 0),
(22, 5, 1, 'heheehe', '2025-11-30 06:38:04', 'seller', 1),
(23, 5, 1, 'ano to daad?', '2025-11-30 06:38:09', 'seller', 1),
(24, 5, 1, 'poodsss heheh', '2025-11-30 11:33:58', 'buyer', 1),
(25, 5, 1, 'poodsss heheh', '2025-11-30 11:34:01', 'buyer', 1),
(26, 5, 1, 'poodsss heheh', '2025-11-30 11:37:25', 'buyer', 1),
(27, 5, 1, 'indi ko ah', '2025-11-30 11:40:27', 'seller', 1),
(28, 16, 1, 'gya ko sa ict building', '2025-12-02 07:16:57', 'buyer', 0),
(29, 16, 17, 'vhdcsksfkgfa', '2025-12-02 07:24:56', 'buyer', 1),
(30, 16, 17, 'jcdjddki', '2025-12-02 07:26:53', 'seller', 1),
(31, 16, 17, 'ict building room 6', '2025-12-03 00:20:10', 'buyer', 1),
(32, 16, 17, 'oki', '2025-12-03 00:32:11', 'seller', 0),
(33, 16, 17, 'ict building room 6', '2025-12-03 02:14:32', 'buyer', 1),
(34, 16, 17, 'otw', '2025-12-03 02:18:10', 'seller', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('buyer','seller') NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'seller', 'Buyer #5 purchased your item \'ewq\' (3 pcs).', 0, '2025-10-15 08:37:26'),
(2, 1, 'seller', 'Buyer #5 purchased your item \'isa oa\' (2 pcs).', 0, '2025-10-15 08:37:27'),
(3, 1, 'seller', 'Buyer #5 purchased your item \'last nangani\' (3 pcs).', 0, '2025-10-15 08:37:27'),
(4, 1, 'seller', 'Buyer #5 purchased your item \'yahhh\' (1 pcs).', 0, '2025-10-15 08:37:27'),
(5, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 08:37:27'),
(6, 1, 'seller', 'Buyer #5 purchased your item \'awa nalang\' (1 pcs).', 0, '2025-10-15 08:37:27'),
(7, 5, 'buyer', 'You successfully checked out order #5 with total ₱225.00', 1, '2025-10-15 08:37:27'),
(8, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 08:43:11'),
(9, 5, 'buyer', 'You successfully checked out your \'Snacks\' order #6 with total ₱5.00', 1, '2025-10-15 08:43:11'),
(10, 1, 'seller', 'Buyer #5 purchased your item \'isa oa\' (1 pcs).', 0, '2025-10-15 08:43:11'),
(11, 5, 'buyer', 'You successfully checked out your \'Meals\' order #7 with total ₱4.00', 1, '2025-10-15 08:43:11'),
(12, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 08:43:26'),
(13, 5, 'buyer', 'You successfully checked out your \'Snacks\' order #8 with total ₱5.00', 1, '2025-10-15 08:43:26'),
(14, 1, 'seller', 'Buyer #5 purchased your item \'isa oa\' (1 pcs).', 0, '2025-10-15 08:43:26'),
(15, 5, 'buyer', 'You successfully checked out your \'Meals\' order #9 with total ₱4.00', 1, '2025-10-15 08:43:26'),
(16, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 08:47:30'),
(17, 5, 'buyer', 'You successfully checked out your \'Snacks\' order #10 with total ₱5.00', 1, '2025-10-15 08:47:30'),
(18, 1, 'seller', 'Buyer #5 purchased your item \'isa oa\' (1 pcs).', 0, '2025-10-15 08:47:30'),
(19, 5, 'buyer', 'You successfully checked out your \'Meals\' order #11 with total ₱4.00', 1, '2025-10-15 08:47:30'),
(20, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 08:59:49'),
(21, 5, 'buyer', 'You successfully checked out your \'Snacks\' order #12 totaling ₱5.00', 1, '2025-10-15 08:59:49'),
(22, 1, 'seller', 'Buyer #5 purchased your item \'isa oa\' (1 pcs).', 0, '2025-10-15 08:59:49'),
(23, 5, 'buyer', 'You successfully checked out your \'Meals\' order #13 totaling ₱4.00', 1, '2025-10-15 08:59:50'),
(24, 1, 'seller', 'Buyer #5 purchased your item \'last nangani\' (1 pcs).', 0, '2025-10-15 08:59:50'),
(25, 5, 'buyer', 'You successfully checked out your \'Drinks\' order #14 totaling ₱4.00', 1, '2025-10-15 08:59:50'),
(26, 1, 'seller', 'Buyer #5 purchased your item \'ewq\' (1 pcs).', 0, '2025-10-15 09:30:45'),
(27, 1, 'seller', 'Buyer #5 purchased your item \'last nangani\' (1 pcs).', 0, '2025-10-15 09:30:45'),
(28, 5, 'buyer', 'You successfully checked out order #18 with total ₱38.00', 1, '2025-10-15 09:30:45'),
(29, 1, 'seller', 'Buyer #5 purchased your item \'cscncsncsd\' (1 pcs).', 0, '2025-10-15 09:32:08'),
(30, 5, 'buyer', 'You successfully checked out order #19 with total ₱5.00', 1, '2025-10-15 09:32:08'),
(31, 8, 'buyer', 'You have applied to become a seller. Waiting for admin approval.', 0, '2025-11-18 11:54:08'),
(32, 8, 'seller', 'Buyer #7 purchased your item \'sadaa\' (1 pcs).', 0, '2025-11-18 13:04:24'),
(33, 7, 'buyer', 'You successfully checked out order #20 with total ₱2.00', 1, '2025-11-18 13:04:24'),
(34, 7, 'seller', 'Buyer #7 purchased your item \'asad\' (1 pcs).', 1, '2025-11-18 14:04:08'),
(35, 7, 'buyer', 'You successfully checked out order #21 with total ₱3.00', 1, '2025-11-18 14:04:08'),
(36, 4, 'buyer', 'Your item \'asda\' has been approved and is now available for buyers.', 0, '2025-11-18 16:16:11'),
(37, 8, 'seller', 'Buyer #7 purchased your item \'sdfs\' (3 pcs).', 0, '2025-11-18 18:52:33'),
(38, 4, 'seller', 'Buyer #7 purchased your item \'asdasad\' (2 pcs).', 0, '2025-11-18 18:52:33'),
(39, 7, 'buyer', 'You successfully checked out order #22 with total ₱5.00', 0, '2025-11-18 18:52:33'),
(40, 8, 'seller', 'Buyer #5 purchased your item \'sdfs\' (2 pcs).', 0, '2025-11-21 00:31:34'),
(41, 8, 'seller', 'Buyer #5 purchased your item \'sdfs\' (2 pcs).', 0, '2025-11-21 00:31:47'),
(42, 8, 'seller', 'Buyer #5 purchased your item \'sdfs\' (2 pcs).', 0, '2025-11-21 00:32:01'),
(43, 8, 'seller', 'Buyer #5 purchased your item \'sdfs\' (1 pcs).', 0, '2025-11-21 00:33:08'),
(44, 5, 'buyer', 'You successfully checked out order #26 with total ₱1.00', 1, '2025-11-21 00:33:08'),
(45, 7, 'seller', 'Buyer #5 purchased your item \'fhgfhf\' (2 pcs).', 0, '2025-11-21 00:45:32'),
(46, 7, 'seller', 'Buyer #5 purchased your item \'fhgfhf\' (1 pcs).', 0, '2025-11-21 00:45:43'),
(47, 7, 'seller', 'Buyer #5 purchased your item \'fhgfhf\' (1 pcs).', 0, '2025-11-21 00:45:57'),
(48, 7, 'seller', 'Buyer #5 purchased your item \'fhgfhf\' (1 pcs).', 0, '2025-11-21 00:46:27'),
(49, 4, 'seller', 'Buyer #5 purchased your item \'asdasad\' (1 pcs).', 0, '2025-11-21 00:54:27'),
(50, 4, 'seller', 'Buyer #5 purchased your item \'asdasad\' (1 pcs).', 0, '2025-11-21 00:55:01'),
(51, 5, 'buyer', 'You have applied to become a seller. Waiting for admin approval.', 1, '2025-11-26 21:38:11'),
(52, 5, 'seller', 'Congratulations! Your seller application has been approved.', 1, '2025-11-26 22:49:21'),
(53, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:09'),
(54, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:15'),
(55, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:17'),
(56, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:19'),
(57, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:20'),
(58, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:50:21'),
(59, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:52:56'),
(60, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-26 22:53:16'),
(61, 15, 'buyer', 'You have applied to become a seller. Waiting for admin approval.', 0, '2025-11-27 01:54:07'),
(62, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-30 01:16:16'),
(63, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-30 01:27:55'),
(64, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-30 01:28:28'),
(65, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-30 01:29:48'),
(66, 5, 'seller', 'Your seller application has been rejected.', 1, '2025-11-30 01:35:14'),
(67, 5, 'seller', 'Your seller application has been rejected. You may continue using your buyer account.', 1, '2025-11-30 01:49:24'),
(68, 5, 'seller', 'Your seller application has been rejected. You may continue using your buyer account.', 1, '2025-11-30 01:49:37'),
(69, 5, 'seller', 'Your seller application has been rejected. You may continue using your buyer account.', 1, '2025-11-30 01:52:28'),
(70, 5, 'seller', 'Your seller application has been rejected. You may continue using your buyer account.', 1, '2025-11-30 01:54:34'),
(71, 17, 'buyer', 'You have applied to become a seller. Waiting for admin approval.', 0, '2025-12-02 07:19:57'),
(72, 17, 'seller', 'Congratulations! Your seller application has been approved.', 0, '2025-12-02 07:20:32');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('pending','preparing','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `category`, `status`, `created_at`) VALUES
(27, 5, 8.00, NULL, 'pending', '2025-11-21 00:45:31'),
(28, 5, 4.00, NULL, 'pending', '2025-11-21 00:45:43'),
(29, 5, 4.00, NULL, 'pending', '2025-11-21 00:45:57'),
(30, 5, 4.00, NULL, 'pending', '2025-11-21 00:46:27'),
(31, 5, 1.00, NULL, 'pending', '2025-11-21 00:54:27'),
(32, 5, 1.00, NULL, 'pending', '2025-11-21 00:55:01'),
(33, 5, 1.00, NULL, 'pending', '2025-11-21 01:10:49'),
(34, 5, 1.00, NULL, 'pending', '2025-11-23 08:11:25'),
(35, 5, 300.00, NULL, 'cancelled', '2025-11-23 12:13:22'),
(36, 5, 300.00, NULL, 'preparing', '2025-11-23 12:33:28'),
(37, 5, 450.00, NULL, 'preparing', '2025-11-23 12:56:51'),
(38, 13, 300.00, NULL, 'preparing', '2025-11-27 01:30:42'),
(39, 14, 150.00, NULL, 'preparing', '2025-11-27 01:44:30'),
(40, 15, 150.00, NULL, 'preparing', '2025-11-27 01:52:42'),
(41, 5, 150.00, NULL, 'completed', '2025-11-30 11:33:24'),
(42, 16, 100.00, NULL, 'pending', '2025-12-02 07:15:27'),
(43, 16, 150.00, NULL, 'completed', '2025-12-02 07:17:09'),
(44, 16, 20.00, NULL, 'completed', '2025-12-02 07:25:04'),
(45, 16, 40.00, NULL, 'completed', '2025-12-03 00:20:28'),
(46, 16, 10.00, NULL, 'completed', '2025-12-03 02:14:53'),
(47, 5, 50.00, NULL, 'pending', '2025-12-04 01:37:05');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `menu_item_id`, `quantity`, `price`) VALUES
(37, 27, 56, 2, 4.00),
(38, 28, 56, 1, 4.00),
(39, 29, 56, 1, 4.00),
(40, 30, 56, 1, 4.00),
(41, 31, 53, 1, 1.00),
(42, 32, 53, 1, 1.00),
(43, 33, 53, 1, 1.00),
(44, 34, 54, 1, 1.00),
(45, 35, 57, 2, 150.00),
(46, 36, 57, 2, 150.00),
(47, 37, 57, 3, 150.00),
(48, 38, 57, 2, 150.00),
(49, 39, 57, 1, 150.00),
(50, 40, 57, 1, 150.00),
(51, 41, 57, 1, 150.00),
(52, 42, 58, 2, 50.00),
(53, 43, 57, 1, 150.00),
(54, 44, 59, 1, 20.00),
(55, 45, 59, 2, 20.00),
(56, 46, 63, 2, 5.00),
(57, 47, 58, 1, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_documents`
--

CREATE TABLE `seller_documents` (
  `doc_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_documents`
--

INSERT INTO `seller_documents` (`doc_id`, `seller_id`, `file_name`, `file_type`, `uploaded_at`) VALUES
(1, 5, '1764193091_permit_37df14dc-7158-4fc5-9d5b-fc1840ed9973.jpeg', 'permit', '2025-11-27 05:38:11'),
(2, 5, '1764193091_valid_id_8354cc45-41a0-4fb3-931f-c07e6e9948b9.jpeg', 'valid_id', '2025-11-27 05:38:11'),
(3, 5, '1764193091_schedule_b93d78850c44649655e7e55347eb99db-removebg-preview.png', 'schedule', '2025-11-27 05:38:11'),
(4, 15, '1764208447_permit_b93d78850c44649655e7e55347eb99db-removebg-preview.png', 'permit', '2025-11-27 09:54:07'),
(5, 15, '1764208447_valid_id_Unique Logo Templates_ Restaurant Logo Design.jpeg', 'valid_id', '2025-11-27 09:54:07'),
(6, 15, '1764208447_schedule_Hinampang2025.png', 'schedule', '2025-11-27 09:54:07'),
(7, 17, '1764659997_permit_K K K.jpg', 'permit', '2025-12-02 15:19:57'),
(8, 17, '1764659997_valid_id_6017605eb2bc451e8f8545f2c9da406d.jpg', 'valid_id', '2025-12-02 15:19:57'),
(9, 17, '1764659997_schedule_ad.jpg', 'schedule', '2025-12-02 15:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `seller_notifications`
--

CREATE TABLE `seller_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_notifications`
--

INSERT INTO `seller_notifications` (`notification_id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'Your item \'asdasad\' has been approved and is now available for buyers.', 0, '2025-11-18 16:58:12'),
(2, 8, 'Your item \'sdfs\' has been approved and is now available for buyers.', 1, '2025-11-18 17:25:40'),
(3, 8, 'Your item \'geafs\' has been rejected by admin.', 1, '2025-11-18 17:36:33'),
(4, 7, 'Your item \'fhgfhf\' has been approved and is now available for buyers.', 0, '2025-11-18 17:39:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','seller','admin') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_seller` tinyint(1) DEFAULT 0,
  `department` varchar(150) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `email`, `password`, `role`, `is_active`, `approval_status`, `description`, `created_at`, `updated_at`, `profile_picture`, `phone`, `is_seller`, `department`, `course`, `section`) VALUES
(1, 'katelene ann', 'katelin123', 'lilamcathlene@outlook.com', '$2y$10$VaFduJLuF.THxQhfXz3dF.4TbGsnw1at8tfhqi.B6y.k2XxBffvAy', 'seller', 1, 'approved', 'dsasfsgh', '2025-09-28 10:45:56', '2025-11-26 21:41:38', 'uploads/691e61330563e_7fee0b51-a21a-45aa-9565-4adc102cc7d5.jpeg', '09850243362', 1, 'SOICT', 'BS InfoTech', '3-a'),
(4, 'admindaw', 'admin123', 'admin123@gmail.com', '$2y$10$pgmso9o.ZwO0lKaB1gqXku2ytxvP1ikzjrK3FnpIXn5FvCTvlvGb2', 'admin', 1, 'pending', NULL, '2025-09-28 11:33:26', '2025-09-28 11:34:45', NULL, '09850243362', 0, NULL, NULL, NULL),
(5, 'Cathlene Ann lilam Arana', 'cath123', 'cathlene@gmail.com', '$2y$10$yoa.Q6zmp8eCFJQv1O0dX.P09ObTQL5eVzEW0CD2TPtxiONz6yJb.', 'buyer', 1, 'rejected', 'kafoiiii na ngani ang fersonnnn', '2025-09-28 12:18:12', '2025-11-30 01:49:24', 'uploads/68ef02935c42c_IMG_9825.JPG', '09850243362', 1, 'SOICT', 'BS InfoTech', '3-A'),
(7, 'Ashe Paul Laru-an Naranja', '123', 'ashenaranja@gmail.com', '$2y$10$d6XWzyFr.tSPlOUx3QZWRuYq.cm/hJ.cKsHuEk8W65UKnuwn7xOVy', 'buyer', 1, 'pending', '', '2025-11-17 12:23:41', '2025-11-18 18:50:17', 'uploads/691cbfe921e2b_78-786207_user-avatar-png-user-avatar-icon-png-transparent.png', '09292939394', 0, '', '', ''),
(8, 'Asheng pung', '24-1111', 'ashong@gmail.com', '$2y$10$Yd7dEortbmGJiTNln1M1T.BJWk1H6BwHBpBVngRGhmSyq4XVEh9CW', 'seller', 1, 'approved', 'ASDADADASDKOKCOSKCOP', '2025-11-17 12:33:04', '2025-11-18 18:33:30', 'pfp_83e7c5278f986130.jpg', '983828382', 1, 'SOICT', 'BSINFOTECH', '1'),
(13, 'Mary Angelyn Aragones', 'rhyperior', 'aragonesmaryangelyn@gmail.com', '$2y$10$nEftSiEVms/3FAXnWH.GneKKpyEpYk.z5sWQLJziQAXk2qjJRZBw.', 'buyer', 1, 'pending', NULL, '2025-11-27 01:29:27', '2025-11-27 01:29:27', NULL, '09619473586', 0, NULL, NULL, NULL),
(14, 'charity chavez', 'charie', 'chavez@gmail.com', '$2y$10$k6fc/OWnppULXlruaXGdjuXroG8zmBozlz65uNgt3W4qA99MjDisG', 'buyer', 1, 'pending', NULL, '2025-11-27 01:43:32', '2025-11-27 01:43:32', NULL, '09641918959', 0, NULL, NULL, NULL),
(15, 'shania grace', 'shania grace', 'shaniagracecastroverde9@gmail.com', '$2y$10$6U91O01A.KlC3odBaN./TuSIdTbgUvHeZEaImauKfq.H3ibpsQ2We', 'seller', 1, 'approved', 'bjhjhjhg', '2025-11-27 01:51:41', '2025-11-27 01:54:36', NULL, '09500074036', 1, 'SOIT', 'foodtech', '4'),
(16, 'ronaly paragon', 'rona', 'ronalyn@gmail.com', '$2y$10$ZTS2KiNnyqCu2GB5yHXzHuXjMxLf.khuXPx1LQasg8PiVtRqBSQOO', 'buyer', 1, 'pending', NULL, '2025-12-02 07:13:58', '2025-12-02 07:13:58', NULL, '09063348395', 0, NULL, NULL, NULL),
(17, 'pia consumo', '24-1042', 'piaconsumo@gmail.com', '$2y$10$avLmkG0m51K5G.ipQz9KeOPkpUaPwDrNVrfQm9u3Bu5XEEJJi6ii.', 'seller', 1, 'approved', 'jcjcdbfjdwf', '2025-12-02 07:18:27', '2025-12-02 07:20:32', NULL, '09454767677', 1, 'SOICT', 'BS InfoTech', '3-A');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `item_images`
--
ALTER TABLE `item_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_menu_item_image` (`menu_item_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`menu_item_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `item_images`
--
ALTER TABLE `item_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `menu_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_documents`
--
ALTER TABLE `seller_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `item_images`
--
ALTER TABLE `item_images`
  ADD CONSTRAINT `fk_menu_item_image` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_images_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD CONSTRAINT `seller_documents_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD CONSTRAINT `seller_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
