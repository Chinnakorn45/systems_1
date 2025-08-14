-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Jul 03, 2025 at 01:12 AM
-- Server version: 8.0.42
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `borrowing_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `borrow_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'ID ผู้ยืม',
  `item_id` int NOT NULL COMMENT 'ID ครุภัณฑ์',
  `borrow_date` date NOT NULL COMMENT 'วันที่ยืม',
  `due_date` date DEFAULT NULL COMMENT 'วันที่ต้องคืน',
  `return_date` date DEFAULT NULL COMMENT 'วันที่คืนจริง',
  `quantity_borrowed` int NOT NULL COMMENT 'จำนวนที่ยืม',
  `status` enum('pending','borrowed','returned','return_pending','overdue','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'borrowed' COMMENT 'สถานะ',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'หมายเหตุ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่บันทึกการยืม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บันทึกการยืม-คืนครุภัณฑ์';

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`borrow_id`, `user_id`, `item_id`, `borrow_date`, `due_date`, `return_date`, `quantity_borrowed`, `status`, `notes`, `created_at`) VALUES
(29, 1, 2, '2025-07-01', '2025-07-31', '2025-07-01', 1, 'returned', NULL, '2025-07-01 02:40:05'),
(30, 1, 6, '2025-07-01', '2025-07-02', '2025-07-01', 1, 'returned', NULL, '2025-07-01 04:44:12'),
(31, 2, 6, '2025-07-01', '2025-07-02', '2025-07-01', 1, 'returned', NULL, '2025-07-01 07:40:49'),
(32, 1, 7, '2025-07-01', '2025-07-26', NULL, 1, 'cancelled', NULL, '2025-07-01 08:23:20'),
(33, 1, 5, '2025-07-01', '2025-07-02', NULL, 1, 'cancelled', NULL, '2025-07-01 08:23:48'),
(34, 1, 4, '2025-07-01', '2025-07-20', NULL, 1, 'cancelled', NULL, '2025-07-01 08:24:04'),
(35, 1, 11, '2025-07-01', '2025-07-03', NULL, 1, 'cancelled', NULL, '2025-07-01 08:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int NOT NULL,
  `brand_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`) VALUES
(5, 'AOC'),
(1, 'Asus'),
(6, 'BenQ'),
(12, 'Brother'),
(10, 'Canon'),
(2, 'Dell'),
(11, 'Epson'),
(13, 'Fuji Xerox'),
(8, 'HP'),
(3, 'LG'),
(9, 'Logitech'),
(7, 'MSI'),
(4, 'Samsung');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อหมวดหมู่'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='หมวดหมู่ครุภัณฑ์/อุปกรณ์';

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(11, 'computer (All in One)'),
(13, 'laptop computer'),
(12, 'Personal computer'),
(5, 'กล้องเว็บแคม (Webcam)'),
(2, 'คีย์บอร์ด (Keyboard)'),
(7, 'จอภาพ (Monitor)'),
(9, 'ลำโพง (Speaker)'),
(4, 'สแกนเนอร์ (Scanner)'),
(8, 'เครื่องพิมพ์ (Printer)'),
(3, 'เมาส์ (Mouse)'),
(10, 'โปรเจคเตอร์ (Projector)'),
(6, 'ไมโครโฟน (Microphone)');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int NOT NULL,
  `department_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลแผนก/ฝ่าย';

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `parent_id`) VALUES
(16, 'ภารกิจด้านอำนวยการ', NULL),
(17, 'ภารกิจด้านวิชาการแพทย์', NULL),
(18, 'ภารกิจด้านการพยาบาล', NULL),
(19, 'ภารกิจด้านการพัฒนาระบบสุขภาพ', NULL),
(20, 'งานบริหารทั่วไป', 16),
(21, 'งานทรัพยากรบุคคล', 16),
(22, 'งานยุทธศาสตร์และแผนงาน', 16),
(23, 'งานการเงิน', 16),
(24, 'งานบัญชี', 16),
(25, 'งานพัสดุและบำรุงรักษา', 16),
(26, 'กลุ่มงานมะเร็งนารีเวช', 17),
(27, 'กลุ่มงานศัลยศาสตร์', 17),
(28, 'กลุ่มงานวิสัญญีวิทยา', 17),
(29, 'กลุ่มงานอายุรศาสตร์', 17),
(30, 'กลุ่มงานเคมีบำบัด', 17),
(31, 'กลุ่มงานโสต ศอ นาสิก', 17),
(32, 'กลุ่มงานรังสีรักษา', 17),
(33, 'กลุ่มงานรังสีวินิจฉัย', 17),
(34, 'กลุ่มงานพยาธิวิทยากายวิภาค', 17),
(35, 'กลุ่มงานพยาธิวิทยาคลินิคฯ', 17),
(36, 'กลุ่มงานเวชศาสตร์ประคับประคอง', 17),
(37, 'กลุ่มงานทันตกรรม', 17),
(38, 'กลุ่มงานเภสัชกรรม', 17),
(39, 'กลุ่มงานโภชนาศาสตร์', 17),
(40, 'งานการพยาบาลป้องกันและควบคุมการติดเชื้อ', 18),
(41, 'งานวิจัยและพัฒนาการพยาบาล', 18),
(42, 'งานถ่ายทอดการพยาบาล', 18),
(45, 'งานการพยาบาลส่งเสริมคุณภาพชีวิต', 18),
(46, 'งานการพยาบาลผู้ป่วยนอกรังสีรักษา', 18),
(47, 'งานการพยาบาลผู้ป่วยนอกเคมีบำบัด', 18),
(48, 'งานการพยาบาลผู้ป่วยนอกศัลกรรม', 18),
(49, 'งานการพยาบาลผู้ป่วยหนัก', 18),
(50, 'งานการพยาบาลผู้ป่วยผ่าตัด', 18),
(51, 'งานการพยาบาลผู้ป่วยวิสัญญี', 18),
(52, 'งานการพยาบาลผู้ป่วยใน', 18),
(53, 'งานการพยาบาลผู้ป่วยนอก', 18),
(54, 'งานการพยาบาลตรวจรักษาพิเศษ', 18),
(55, 'กลุ่มงานประกันสุขภาพ', 19),
(56, 'กลุ่มงานดิจิทัลการแพทย์', 19),
(57, 'กลุ่มงานวิจัย ถ่ายทอดและสนับสนุนวิชาการ', 19),
(58, 'กลุ่มงานพัฒนาคุณภาพ', 19),
(59, 'กลุ่มงานพัฒนานโยบายและยุทธศาสตร์การแพทย์', 19),
(60, 'อื่น ๆ', NULL),
(61, 'งานห้องสมุด', 60),
(62, 'งานสังคมสงเคราะห์', 60),
(63, 'งานวิชาการพยาบาล', 60),
(64, 'กลุ่มงานผู้ป่วยนอก', 60),
(66, 'งานการพยาบาลผู้ป่วยในศัลกรรม', 60);

-- --------------------------------------------------------

--
-- Table structure for table `dispensations`
--

CREATE TABLE `dispensations` (
  `dispense_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'ID ผู้เบิก',
  `supply_id` int NOT NULL COMMENT 'ID วัสดุ',
  `dispense_date` date NOT NULL COMMENT 'วันที่เบิก',
  `quantity_dispensed` int NOT NULL COMMENT 'จำนวนที่เบิก',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'หมายเหตุ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บันทึกการเบิกจ่ายวัสดุสำนักงาน';

--
-- Dumping data for table `dispensations`
--

INSERT INTO `dispensations` (`dispense_id`, `user_id`, `supply_id`, `dispense_date`, `quantity_dispensed`, `notes`, `created_at`) VALUES
(2, 1, 1, '2025-06-26', 1, '', '2025-06-26 03:43:06'),
(3, 2, 1, '2025-06-26', 18, '', '2025-06-26 04:11:17'),
(4, 2, 1, '2025-06-26', 89, '', '2025-06-26 06:55:05'),
(5, 3, 1, '2025-06-26', 10, 'เอาไปใช้ส่วนตัว', '2025-06-26 06:55:36');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_movements`
--

CREATE TABLE `equipment_movements` (
  `movement_id` int NOT NULL,
  `item_id` int NOT NULL COMMENT 'ID ครุภัณฑ์',
  `movement_type` enum('borrow','return','transfer','maintenance','disposal','purchase','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ประเภทการเคลื่อนไหว',
  `from_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่งเดิม',
  `to_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่งใหม่',
  `from_user_id` int DEFAULT NULL COMMENT 'ผู้ใช้เดิม',
  `to_user_id` int DEFAULT NULL COMMENT 'ผู้ใช้ใหม่',
  `quantity` int NOT NULL DEFAULT '1' COMMENT 'จำนวนที่เคลื่อนไหว',
  `movement_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เคลื่อนไหว',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'หมายเหตุ',
  `created_by` int NOT NULL COMMENT 'ผู้บันทึกการเคลื่อนไหว',
  `borrow_id` int DEFAULT NULL COMMENT 'ID การยืม (ถ้ามี)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการเคลื่อนไหวของครุภัณฑ์';

--
-- Dumping data for table `equipment_movements`
--

INSERT INTO `equipment_movements` (`movement_id`, `item_id`, `movement_type`, `from_location`, `to_location`, `from_user_id`, `to_user_id`, `quantity`, `movement_date`, `notes`, `created_by`, `borrow_id`) VALUES
(8, 2, 'borrow', 'แผนกไอที', NULL, NULL, 3, 1, '2025-07-01 02:40:05', 'สร้างการยืมโดย admin1', 3, 29),
(10, 2, 'transfer', 'แผนกไอที', 'แผนกบัญชี', NULL, 2, 1, '2025-07-01 02:49:40', 'ทดสอบการโอนย้ายจากหน้ายืม-คืน', 1, NULL),
(11, 2, 'transfer', 'งานพัสดุและบำรุงรักษา', 'งานถ่ายทอดการพยาบาล', 4, 5, 1, '2025-07-01 02:51:24', 'โอนจาก ยศกร มาก่อน ไปยัง ยศกร กลับก่อน', 3, 29),
(12, 2, 'transfer', 'งานถ่ายทอดการพยาบาล', 'งานยุทธศาสตร์และแผนงาน', 5, 2, 1, '2025-07-01 03:04:07', 'โอนจาก ยศกร กลับก่อน ไปยัง ซากีหนะต์ ปรังเจะ', 3, 29),
(13, 2, 'transfer', 'งานยุทธศาสตร์และแผนงาน', 'กลุ่มงานโสต ศอ นาสิก', 2, 1, 1, '2025-07-01 03:10:58', 'โอนจาก ซากีหนะต์ ปรังเจะ ไปยัง ชินกร ทองสอาด', 3, 29),
(14, 2, 'adjustment', NULL, NULL, 1, NULL, 1, '2025-07-01 04:29:43', 'รอยืนยันการคืนโดย ชินกร ทองสอาด', 1, 29),
(15, 2, 'return', NULL, 'แผนกไอที', 1, NULL, 1, '2025-07-01 04:30:21', 'ยืนยันการคืนโดย admin1', 3, 29),
(16, 6, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 04:44:12', 'ส่งคำขอยืมโดย staff', 1, 30),
(17, 6, 'borrow', 'แผนกไอที', NULL, NULL, 1, 1, '2025-07-01 04:44:27', 'อนุมัติการยืมโดย admin1', 3, 30),
(18, 6, 'adjustment', NULL, NULL, 1, NULL, 1, '2025-07-01 04:44:42', 'รอยืนยันการคืนโดย ชินกร ทองสอาด', 1, 30),
(19, 6, 'return', NULL, 'แผนกไอที', 1, NULL, 1, '2025-07-01 04:44:50', 'ยืนยันการคืนโดย admin1', 3, 30),
(20, 6, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 07:40:49', 'ส่งคำขอยืมโดย SAKII', 6, 31),
(21, 6, 'borrow', 'แผนกไอที', NULL, NULL, 6, 1, '2025-07-01 07:42:25', 'อนุมัติการยืมโดย admin', 2, 31),
(22, 6, 'transfer', 'กลุ่มงานประกันสุขภาพ', 'งานยุทธศาสตร์และแผนงาน', 6, 2, 1, '2025-07-01 08:06:57', 'โอนจาก กัณภัทร ปรังเจะ ไปยัง ซากีหนะต์ ปรังเจะ', 3, 31),
(23, 7, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:23:20', 'ส่งคำขอยืมโดย staff', 1, 32),
(24, 5, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:23:48', 'ส่งคำขอยืมโดย staff', 1, 33),
(25, 4, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:24:04', 'ส่งคำขอยืมโดย staff', 1, 34),
(26, 11, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:24:35', 'ส่งคำขอยืมโดย staff', 1, 35),
(27, 11, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:36:45', 'ยกเลิกการยืมโดย admin', 2, 35),
(28, 4, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:36:47', 'ยกเลิกการยืมโดย admin', 2, 34),
(29, 5, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:36:49', 'ยกเลิกการยืมโดย admin', 2, 33),
(30, 7, 'adjustment', NULL, NULL, NULL, NULL, 1, '2025-07-01 08:36:51', 'ยกเลิกการยืมโดย admin', 2, 32),
(31, 6, 'adjustment', NULL, NULL, 2, NULL, 1, '2025-07-01 08:42:16', 'รอยืนยันการคืนโดย ซากีหนะต์ ปรังเจะ', 3, 31),
(32, 6, 'return', NULL, 'แผนกไอที', 2, NULL, 1, '2025-07-01 08:42:22', 'ยืนยันการคืนโดย admin1', 3, 31);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int NOT NULL,
  `item_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อครุภัณฑ์/อุปกรณ์',
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'รายละเอียด',
  `note` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL COMMENT 'ID หมวดหมู่',
  `total_quantity` int NOT NULL DEFAULT '0' COMMENT 'จำนวนรวมทั้งหมด',
  `price_per_unit` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่งที่เก็บ',
  `purchase_date` date DEFAULT NULL COMMENT 'วันที่จัดซื้อ',
  `budget_year` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เพิ่ม',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'อัปเดตล่าสุด',
  `available_quantity` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลครุภัณฑ์และอุปกรณ์';

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `item_title`, `brand`, `item_name`, `serial_number`, `image`, `description`, `note`, `category_id`, `total_quantity`, `price_per_unit`, `total_price`, `status`, `location`, `purchase_date`, `budget_year`, `budget_amount`, `created_at`, `updated_at`, `available_quantity`) VALUES
(2, 'Nitro v 15', 'Asus', '12154-555-880', '85646556225', 'uploads/item_1750836374_3229.png', '-', 'ไม่มี', 13, 1, 50000.00, 50000.00, 'available', 'แผนกไอที', '2025-06-12', '2568', 23000.00, '2025-06-25 06:34:37', '2025-07-01 04:30:21', 2),
(3, 'Aoc 1985', 'AOC', '12154-555-888', '85646556278', 'uploads/item_1750836678_3221.jpg', '-', 'ไม่มี', 7, 2, 32000.00, 64000.00, 'available', 'แผนกไอที', '2025-07-04', '2568', 28000.00, '2025-06-25 07:31:18', '2025-07-01 04:26:20', 2),
(4, 'HP Series 3 Pro', 'HP', '12154-555-888', '1254512', 'uploads/item_1750838155_6301.jpg', '', 'ไม่มี', 7, 3, 49000.00, 147000.00, 'available', 'แผนกไอที', '2025-06-25', '2568', 5500.00, '2025-06-25 07:55:55', '2025-07-01 04:26:32', 3),
(5, 'HP Laserjet 108a', 'HP', '12154-555-889', '12545124433', 'uploads/item_1750907183_1843.png', '-', '-', 8, 2, 28000.00, 56000.00, 'available', 'แผนกไอที', '2025-06-26', '2568', 3900.00, '2025-06-26 03:05:46', '2025-07-01 04:26:43', 2),
(6, 'PRO X SUPERLIGHT 2 DEX', 'Logitech', '444-55-6552', '85646557458', 'uploads/item_1750907161_4304.png', 'PRO X SUPERLIGHT 2 DEX เป็นเมาส์อสมมาตรขนาด 60 กรัมที่มีเซ็นเซอร์ HERO 2 ขั้นสูง ระบบไร้สาย LIGHTSPEED ที่แข็งแกร่ง และสวิตช์ LIGHTFORCE พร้อมทั้งอายุการใช้งานแบตเตอรี่สูงสุด 95 ชั่วโมง.', '-', 3, 1, 23000.00, 23000.00, 'available', 'แผนกไอที', '2025-06-11', '2568', 5290.00, '2025-06-26 03:06:01', '2025-07-01 08:42:22', 0),
(7, 'HP Smart Tank 580', 'HP', '12154-555-882', '12545125883', 'uploads/item_1750908301_3197.png', '', '', 8, 2, 4900.00, 9800.00, 'available', 'แผนกไอที', '2025-06-26', '2568', 4490.00, '2025-06-26 03:16:08', '2025-07-01 04:27:18', 4),
(8, 'Aoc 1986', 'AOC', '12154-555-89', '856465562266', 'uploads/item_1750920848_9412.png', '-', '-', 11, 3, 4900.00, 14700.00, 'available', 'แผนกไอที', '2025-06-10', '2568', 23000.00, '2025-06-26 06:54:08', '2025-07-01 04:27:41', 3),
(9, '55556998696', 'Asus', '12154-555-8956', '85646556226655', 'uploads/item_1751013394_1684.jpg', '-', '-', 11, 1, 1990.00, 1990.00, 'available', 'แผนกไอที', '2025-06-10', '2568', 23000.00, '2025-06-27 08:36:34', '2025-07-01 04:27:56', 1),
(11, 'DELL DESKTOP TW INSPIRON', 'Dell', '12154-5545-8956', '85646556226655', 'uploads/item_1751343857_2154.png', 'DELL DESKTOP TW INSPIRON 3030SFF มาพร้อมโปรเซสเซอร์ Intel Core ล่าสุด ทั้งหมดนี้มาในดีไซน์กะทัดรัดประหยัดพื้นที่ รับมือกับงานต่างๆ ได้อย่างง่ายดายมาพร้อมกับการออกแบบที่ทันสมัยที่จะช่วยให้คุณทำงานเสร็จเร็วขึ้น ง่ายขึ้นและมีสไตล์', '-', 12, 5, 1499.00, 7495.00, 'available', 'แผนกไอที', '2025-07-01', '2568', NULL, '2025-07-01 04:24:17', '2025-07-01 04:28:30', 0),
(12, 'Aoc 1987', 'AOC', '12154-555-883', '8564655622566', 'uploads/item_1751349936_1002.png', '-', '-', 13, 2, 3600.00, 7200.00, 'available', 'แผนกไอที', '2025-07-01', '2565', NULL, '2025-07-01 06:05:36', '2025-07-01 06:05:36', 0);

-- --------------------------------------------------------

--
-- Table structure for table `office_supplies`
--

CREATE TABLE `office_supplies` (
  `supply_id` int NOT NULL,
  `supply_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อวัสดุสำนักงาน',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'รายละเอียด',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'หน่วยนับ',
  `current_stock` int NOT NULL DEFAULT '0' COMMENT 'จำนวนสต็อก',
  `min_stock_level` int DEFAULT '0' COMMENT 'ระดับสต็อกต่ำสุด',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เพิ่ม',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'อัปเดตล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลวัสดุสำนักงาน';

--
-- Dumping data for table `office_supplies`
--

INSERT INTO `office_supplies` (`supply_id`, `supply_name`, `description`, `unit`, `current_stock`, `min_stock_level`, `created_at`, `updated_at`) VALUES
(1, 'กระดาษ A4 / A3', 'กระดาษขนาด A4 จะมีขนาดพื้นที่ 21.0 x 29.7 cm หรือคิดเป็น', 'รีม (500 แผ่น)', 1, 10, '2025-06-26 03:15:53', '2025-07-01 04:38:56'),
(2, 'ปากกาลูกลื่น', '-', 'ด้าม', 100, 10, '2025-06-27 06:30:38', '2025-06-27 06:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อผู้ใช้งาน',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'แฮชรหัสผ่าน',
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ชื่อ-นามสกุลเต็ม',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'อีเมลติดต่อ',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'แผนก/ฝ่าย',
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่ง',
  `role` enum('admin','staff','procurement') COLLATE utf8mb4_unicode_ci DEFAULT 'staff' COMMENT 'บทบาท',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้างบัญชี'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลผู้ใช้งานระบบ';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `department`, `position`, `role`, `created_at`) VALUES
(1, 'staff', '$2y$10$CgzPkwcZdxHgsr32K3Xms.YdcJCilB8nlcJpxMAJk/4NVOoZ5wRGC', 'ชินกร ทองสอาด', 'chinnakorntongsaard@gmail.com', 'กลุ่มงานโสต ศอ นาสิก', '-', 'staff', '2025-06-25 02:32:10'),
(2, 'admin', '$2y$10$QLuV02A0tCqBlMaqiL2rkeFjElM3d/F9lKqmBKUL9dLMACPRB1rpe', 'ซากีหนะต์ ปรังเจะ', '6504305001354@student.sru.ac.th', 'งานยุทธศาสตร์และแผนงาน', '-', 'admin', '2025-06-25 03:10:50'),
(3, 'admin1', '$2y$10$aKHr2GYgddgHRYoSDxZoD.FbbrV3FGh7fHUlSldCZVO6wf6eEl4bG', 'ชินกร ทองสอาด', '6504305001gb2g317@student.sru.ac.th', 'กลุ่มงานพัฒนาคุณภาพ', '-', 'admin', '2025-06-27 03:14:19'),
(4, 'LogisticsOfficer', '$2y$10$scsgrQnSb8a6GtZmdw5kjeFqGZrLRSOuPXqaQgJsxCRYXmuojNOSu', 'ยศกร มาก่อน', '6504305001gb2g5488317@student.sru.ac.th', 'งานพัสดุและบำรุงรักษา', '-', 'procurement', '2025-06-30 04:01:45'),
(5, 'staff1', '$2y$10$UuNtqWxFqXNkuTntXD2gYeGNboWFnaumnY4tpaEzIIEAIzcaZQF2W', 'ยศกร กลับก่อน', '6504305005551gb2g5488317@student.sru.ac.th', 'งานถ่ายทอดการพยาบาล', '-', 'staff', '2025-06-30 08:26:23'),
(6, 'SAKII', '$2y$10$IA6GZq50lpDr86fK6m.9aOD9eltXiPLF3.o5VG1Iz1NtWOOmCzMsi', 'กัณภัทร ปรังเจะ', 'chin@gmail.com', 'กลุ่มงานประกันสุขภาพ', '-', 'staff', '2025-07-01 07:35:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`borrow_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `dispensations`
--
ALTER TABLE `dispensations`
  ADD PRIMARY KEY (`dispense_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `supply_id` (`supply_id`);

--
-- Indexes for table `equipment_movements`
--
ALTER TABLE `equipment_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `borrow_id` (`borrow_id`),
  ADD KEY `idx_equipment_movements_item_id` (`item_id`),
  ADD KEY `idx_equipment_movements_movement_date` (`movement_date`),
  ADD KEY `idx_equipment_movements_movement_type` (`movement_type`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `office_supplies`
--
ALTER TABLE `office_supplies`
  ADD PRIMARY KEY (`supply_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `borrow_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `dispensations`
--
ALTER TABLE `dispensations`
  MODIFY `dispense_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `equipment_movements`
--
ALTER TABLE `equipment_movements`
  MODIFY `movement_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `office_supplies`
--
ALTER TABLE `office_supplies`
  MODIFY `supply_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `dispensations`
--
ALTER TABLE `dispensations`
  ADD CONSTRAINT `dispensations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dispensations_ibfk_2` FOREIGN KEY (`supply_id`) REFERENCES `office_supplies` (`supply_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `equipment_movements`
--
ALTER TABLE `equipment_movements`
  ADD CONSTRAINT `equipment_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipment_movements_ibfk_2` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `equipment_movements_ibfk_3` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `equipment_movements_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipment_movements_ibfk_5` FOREIGN KEY (`borrow_id`) REFERENCES `borrowings` (`borrow_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;