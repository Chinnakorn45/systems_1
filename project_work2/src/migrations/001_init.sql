-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 05:22 AM
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
-- Database: `borrowing_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `borrow_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID ผู้ยืม',
  `item_id` int(11) NOT NULL COMMENT 'ID ครุภัณฑ์',
  `borrow_date` date NOT NULL COMMENT 'วันที่ยืม',
  `due_date` date DEFAULT NULL COMMENT 'วันที่ต้องคืน',
  `return_date` date DEFAULT NULL COMMENT 'วันที่คืนจริง',
  `quantity_borrowed` int(11) NOT NULL COMMENT 'จำนวนที่ยืม',
  `status` enum('pending','borrowed','returned','return_pending','overdue','cancelled') DEFAULT 'borrowed' COMMENT 'สถานะ',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่บันทึกการยืม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บันทึกการยืม-คืนครุภัณฑ์';

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`borrow_id`, `user_id`, `item_id`, `borrow_date`, `due_date`, `return_date`, `quantity_borrowed`, `status`, `notes`, `created_at`) VALUES
(42, 3, 23, '2025-08-05', '2025-08-06', NULL, 1, 'borrowed', NULL, '2025-08-05 03:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `brand_name` varchar(255) DEFAULT NULL
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
(14, 'Lenovo'),
(3, 'LG'),
(9, 'Logitech'),
(7, 'MSI'),
(15, 'Philips'),
(4, 'Samsung'),
(17, 'YAMAHA');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL COMMENT 'ชื่อหมวดหมู่'
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
(14, 'อุปกรณ์ประกอบชุดเครื่องเสียง'),
(8, 'เครื่องพิมพ์ (Printer)'),
(3, 'เมาส์ (Mouse)'),
(10, 'โปรเจคเตอร์ (Projector)'),
(6, 'ไมโครโฟน (Microphone)');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
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
-- Table structure for table `discord_logs`
--

CREATE TABLE `discord_logs` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discord_logs`
--

INSERT INTO `discord_logs` (`id`, `repair_id`, `sent_at`, `status`, `response`) VALUES
(1, 45, '2025-07-30 11:57:38', 'success', ''),
(2, 46, '2025-07-30 11:59:37', 'success', ''),
(3, 47, '2025-07-30 14:46:15', 'success', ''),
(4, 48, '2025-07-30 14:46:27', 'success', ''),
(5, 49, '2025-07-31 10:26:41', 'success', ''),
(6, 50, '2025-07-31 11:21:49', 'success', ''),
(7, 51, '2025-07-31 11:26:22', 'success', ''),
(8, 52, '2025-08-01 13:37:32', 'success', ''),
(9, 53, '2025-08-04 13:40:22', 'success', ''),
(10, 54, '2025-08-05 10:04:52', 'success', ''),
(11, 55, '2025-08-05 10:21:33', 'success', '');

-- --------------------------------------------------------

--
-- Table structure for table `dispensations`
--

CREATE TABLE `dispensations` (
  `dispense_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID ผู้เบิก',
  `supply_id` int(11) NOT NULL COMMENT 'ID วัสดุ',
  `dispense_date` date NOT NULL COMMENT 'วันที่เบิก',
  `quantity_dispensed` int(11) NOT NULL COMMENT 'จำนวนที่เบิก',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บันทึกการเบิกจ่ายวัสดุสำนักงาน';

-- --------------------------------------------------------

--
-- Table structure for table `equipment_movements`
--

CREATE TABLE `equipment_movements` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'ID ครุภัณฑ์',
  `movement_type` enum('borrow','return','transfer','maintenance','disposal','purchase','adjustment') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว',
  `from_location` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่งเดิม',
  `to_location` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่งใหม่',
  `from_user_id` int(11) DEFAULT NULL COMMENT 'ผู้ใช้เดิม',
  `to_user_id` int(11) DEFAULT NULL COMMENT 'ผู้ใช้ใหม่',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'จำนวนที่เคลื่อนไหว',
  `movement_date` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่เคลื่อนไหว',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `created_by` int(11) NOT NULL COMMENT 'ผู้บันทึกการเคลื่อนไหว',
  `borrow_id` int(11) DEFAULT NULL COMMENT 'ID การยืม (ถ้ามี)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการเคลื่อนไหวของครุภัณฑ์';

--
-- Dumping data for table `equipment_movements`
--

INSERT INTO `equipment_movements` (`movement_id`, `item_id`, `movement_type`, `from_location`, `to_location`, `from_user_id`, `to_user_id`, `quantity`, `movement_date`, `notes`, `created_by`, `borrow_id`) VALUES
(52, 23, 'borrow', 'องค์กรแพทย์ ชั้น 4', NULL, NULL, 3, 1, '2025-08-05 03:01:11', 'สร้างการยืมโดย admin1', 3, 42);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `model_name` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `item_number` varchar(255) NOT NULL COMMENT 'หมายเลขครุภัณฑ์',
  `serial_number` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'รายละเอียด',
  `note` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL COMMENT 'ID หมวดหมู่',
  `total_quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนรวมทั้งหมด',
  `price_per_unit` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่งที่เก็บ',
  `purchase_date` date DEFAULT NULL COMMENT 'วันที่จัดซื้อ',
  `budget_year` varchar(4) DEFAULT NULL,
  `budget_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่เพิ่ม',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'อัปเดตล่าสุด',
  `available_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลครุภัณฑ์และอุปกรณ์';

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `model_name`, `brand`, `item_number`, `serial_number`, `image`, `description`, `note`, `category_id`, `total_quantity`, `price_per_unit`, `total_price`, `location`, `purchase_date`, `budget_year`, `budget_amount`, `created_at`, `updated_at`, `available_quantity`) VALUES
(23, 'MIXER YAMAHA MG10XU', 'YAMAHA', '5820-001-0003/66', '00000000000', '', '6 ตัว องค์การแพทย์ ชั้น 4', '', 14, 6, 0.00, 0.00, 'องค์กรแพทย์ ชั้น 4', '2024-07-04', '2566', NULL, '2025-08-04 06:53:01', '2025-08-05 03:01:11', -1);

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `model_id` int(11) NOT NULL,
  `model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`model_id`, `model_name`, `brand_id`) VALUES
(2, 'MONITOR (จอมอนิเตอร์) ASUS VA24', 1),
(3, 'จอมอนิเตอร์ HP V24v G5 (VA 75Hz)', 8),
(4, 'Philips878', 15),
(5, 'HP link Tank 315', 8),
(6, 'MIXER YAMAHA MG10XU', 17);

-- --------------------------------------------------------

--
-- Table structure for table `office_supplies`
--

CREATE TABLE `office_supplies` (
  `supply_id` int(11) NOT NULL,
  `supply_name` varchar(255) NOT NULL COMMENT 'ชื่อวัสดุสำนักงาน',
  `description` text DEFAULT NULL COMMENT 'รายละเอียด',
  `unit` varchar(50) DEFAULT NULL COMMENT 'หน่วยนับ',
  `current_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนสต็อก',
  `min_stock_level` int(11) DEFAULT 0 COMMENT 'ระดับสต็อกต่ำสุด',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่เพิ่ม',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'อัปเดตล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลวัสดุสำนักงาน';

-- --------------------------------------------------------

--
-- Table structure for table `repairs`
--

CREATE TABLE `repairs` (
  `repair_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `issue_description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','done','delivered','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `fix_description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `asset_number` varchar(50) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repairs`
--

INSERT INTO `repairs` (`repair_id`, `item_id`, `reported_by`, `issue_description`, `image`, `status`, `assigned_to`, `fix_description`, `created_at`, `updated_at`, `asset_number`, `serial_number`, `location_name`, `brand`, `model_name`) VALUES
(53, NULL, 3, '222', '', '', 3, '', '2025-08-04 13:40:21', '2025-08-05 10:03:55', 'fvfg652626', '154545dvf', 'งานถ่ายทอดการพยาบาล', 'dell', 'dell2655'),
(54, 23, 3, '45465', '', 'pending', NULL, NULL, '2025-08-05 10:04:51', '2025-08-05 10:04:51', '5820-001-0003/66', '00000000000', 'งานถ่ายทอดการพยาบาล', 'YAMAHA', 'MIXER YAMAHA MG10XU'),
(55, NULL, 509, '789', '', 'pending', NULL, NULL, '2025-08-05 10:21:32', '2025-08-05 10:21:32', '0101124', '000112120', 'งานวิจัยและพัฒนาการพยาบาล', 'dell', 'dell12');

-- --------------------------------------------------------

--
-- Table structure for table `repair_logs`
--

CREATE TABLE `repair_logs` (
  `log_id` int(11) NOT NULL,
  `repair_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `detail` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_logs`
--

INSERT INTO `repair_logs` (`log_id`, `repair_id`, `status`, `detail`, `updated_at`, `updated_by`) VALUES
(154, 53, 'received', '', '2025-08-05 10:03:55', 3);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('hospital_name_en', 'Suratthani Cancer Hospital'),
('hospital_name_th', 'โรงพยาบาลมะเร็งสุราษฎร์ธานี (ศูนย์มะเร็งสุราษฎร์ธานี)'),
('system_intro', 'ระบบบงาน-ปัจจัยอ้างอิง (วัสดุ/ครุภัณฑ์) (สำหรับเจ้าหน้าที่การพัสดุ) เป็นระบบนำเข้าข้อมูลดิบ จัดทำขึ้นเพื่อเครื่องอำนวยความสะดวก สำหรับเจ้าหน้าที่การพัสดุในการบริหารจัดการข้อมูลด้านวัสดุ-ครุภัณฑ์ที่อยู่ภายในคณะ ทั้งในส่วนของแต่ละส่วนพัสดุที่ ประกอบไปด้วย ทะเบียนครุภัณฑ์, การซ่อมแซมครุภัณฑ์, การจัดจำหน่ายครุภัณฑ์และ การโอนย้ายครุภัณฑ์รวมถึงส่วนที่ในการจัดการส่วนของวัสดุ ประกอบไปด้วย การจัดการข้อมูลทะเบียนวัสดุ, การเบิกวัสดุ, การยืมคืนวัสดุ, การจัดจำหน่ายวัสดุ ให้สามารถ ติดตาม ตรวจสอบ และปรับปรุงข้อมูลได้สะดวกมากยิ่งขึ้น รวมถึงสามารถนำข้อมูลด้านวัสดุ-ครุภัณฑ์ ไปประมวลผลเพื่อแสดงในรายงานสรุปข้อมูล (Dashboard) สำหรับผู้ บริหารงานและผู้บริหารได้'),
('system_logo', 'uploads/system_logo.png'),
('system_name', ''),
('system_title', 'ระบบงานพัสดุ-ครุภัณฑ์');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้งาน',
  `password_hash` varchar(255) NOT NULL COMMENT 'แฮชรหัสผ่าน',
  `full_name` varchar(100) DEFAULT NULL COMMENT 'ชื่อ-นามสกุลเต็ม',
  `email` varchar(100) DEFAULT NULL COMMENT 'อีเมลติดต่อ',
  `department` varchar(100) DEFAULT NULL COMMENT 'แผนก/ฝ่าย',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `role` enum('admin','staff','procurement') DEFAULT 'staff' COMMENT 'บทบาท',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้างบัญชี',
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลผู้ใช้งานระบบ';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `department`, `position`, `role`, `created_at`, `force_password_change`) VALUES
(2, 'admin', '$2y$10$QLuV02A0tCqBlMaqiL2rkeFjElM3d/F9lKqmBKUL9dLMACPRB1rpe', 'ซากีหนะต์ ปรังเจะ', '6504305001354@student.sru.ac.th', 'งานยุทธศาสตร์และแผนงาน', '-', 'admin', '2025-06-25 03:10:50', 0),
(3, 'admin1', '$2y$10$aKHr2GYgddgHRYoSDxZoD.FbbrV3FGh7fHUlSldCZVO6wf6eEl4bG', 'ชินกร ทองสอาด', '6504305001gb2g317@student.sru.ac.th', 'งานถ่ายทอดการพยาบาล', '-', 'admin', '2025-06-27 03:14:19', 0),
(4, 'LogisticsOfficer', '$2y$10$6VkTBEuzntGAuAzZ5MS6GucCCpekbSBq.6w9mfKy7nEf99hLT6pgG', 'ยศกร มาก่อน', '6504305001gb2g5488317@student.sru.ac.th', 'งานการเงิน', '-', 'procurement', '2025-06-30 04:01:45', 0),
(15, 'staff12', '$2y$10$ZTCwHuNq3wPJ6srLESDQwuHPF48Pf0DJpcjTHaBLHZP.QM7JI60u2', 'ยุวดี รักงาม', 'sqkeenqh@gmail.coim', 'งานบัญชี', 'หัวหน้า', 'staff', '2025-07-25 04:05:22', 0),
(17, 'sur', '$2y$10$etYev0RgCgfyyO8qrzYum.yg.SJjz26bB5kmFXKx6OOgoNYoDI81K', 'สุฤดี สุภาผล', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(18, 'Permission', '$2y$10$eKbzGyN3omBq0fjV7ktfF.04uOloL1p1/tlYWz9rgwPfHRJ17vDFS', 'Permission Manager', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(19, 'Account', '$2y$10$CWU74.3a9cUd0prdO/hqYexqhK3NIBdZw1tSiFaMVYz5BrfJNwEqC', 'Account Manager', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(20, 'Administrator', '$2y$10$V7z96nRbd3C4cVtH0NfdTe1igZWN8289byRbZyY8V3wPekTeLm/Iu', 'Administrator', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(21, 'p', '$2y$10$Awp0mnVP.XxKasZXQGVBMupG9obWR0T7Uow18bTkdbC7bHaRf58W2', 'admin 130', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(22, 'admin129', '$2y$10$YUMOO/XOo.kghTtTvAMk4ep8Abzq5nl57roCYqCpFHUmRxxU3SrZi', 'admin 129', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(23, 'admin128', '$2y$10$W/2ttSGUqh40OwaLa5vRdOqRonzxzAuOIAPeSEYmG.J3L2ewzP.Di', 'admin 128', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(24, 'admin127', '$2y$10$8M5lDzHorZ9yqS..kv8WZOXrn61TVsaDjCs.qaxQw3p1XacgFktIG', 'admin 127', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(25, 'admin126', '$2y$10$1Eypdo3InUaTLyUc6blG8.G8xkbW4AHt5l9Jd/bCrqZRc/iiHisdS', 'admin 126', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(26, 'admin117', '$2y$10$KXa3QufYT/paQIAWF9ALMeLvi1iZzcjvyt4fLxsmu06UU7c2z9.BC', 'admin 117', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(27, 'Sukanya', '$2y$10$djoXRasVm6IuIN7GHtCYVe/SHG03RsHqAxvxCBZVI1JhUPT.2zVA.', 'สุกัญญา ยังหอกิจไพศาล', '', '', '', 'staff', '2025-07-30 07:09:57', 1),
(28, 'admin103', '$2y$10$hR8wYGu8eHns47y6bkXjXusiTy.FwJDaC.wnyehBoSAmaEwwifqd.', 'admin 103', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(29, 'NciAdmin02', '$2y$10$gzmWK/o.s0ZUfikllgiOYemCEztK7wf.ubESKlqiw09C.kgGf8Ocy', 'NCI Admin02', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(30, 'NciAdmin01', '$2y$10$6N2Grd7oOtOkWsyLZalw9eCHl33I7tmqOoxsdjRvV0.e85jeP5tGi', 'NCI Admin01', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(31, 'NciAdmin03', '$2y$10$1VDB/bO5oxjK2kcvuYEcte7/ycSwMf.oCQ0FwhXQyikD8lhjBQQXi', 'NCI Admin03', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(32, 'SchAdmin', '$2y$10$J2CSEkESY96qLTJp9RIyjOHelZZm0b4UKYGDEjrT4ePIVKUIlPlTm', 'โรงพยาบาลมะเร็งสุราษฎร์ธานี', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(33, 'admin2', '$2y$10$57I0px67bU766YPeHLxiveJBw1b0rVGVpmbs/6z6filb76qDqMMbS', 'จักรพันธุ์ ชาตะเวที', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(34, 'AmazingTanoat', '$2y$10$9YxGE1xaEzbk0dSG58aWQuq.D5.UkJnPBLWPTd6rZWLSp0q23tQbG', 'ศศิพงศ์ เขตอนันต์ (Administrator)', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(35, 'admin3', '$2y$10$NvizmSTvDGcBgZfsNaR4u.qsYb3T14cwirpY.ra7PNH8VodEbKrGO', 'วสันต์ จันทร์สอาด', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(36, 'admin6', '$2y$10$pfovS7jUfR8QerDJ.xeXc.KaWJZRhyS9hndHOExP7C1cl2HmtA8VO', 'จักรพงษ์  ณ รังษี ', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(37, 'admin7', '$2y$10$6Ql/w.2hJdUZMMFJukbDBuk92LbQRVLL30tGF7VfCAgza/zCUR8Vy', 'ธิราภรณ์ ถั่วตุ้น', '', '', '', 'staff', '2025-07-30 07:09:58', 1),
(38, 'angcana', '$2y$10$fX2d/oDAm42/pYlNR9Ll3e/p1VpfR3CI3Qq/InosbeahEtLKL5WGy', 'อังคณา พิมลศรี', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(39, 'anucha', '$2y$10$e6r8bK9JySYwoSwcKzQ.TOMR4Oum2oZjyEcIs/iHgo84DbJXx0WJ.', 'อนุชา ลีกิดาการกุล', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(40, 'anusorn1408', '$2y$10$6sHzAA2o0UaOjQ9Q7YKnK.XiTzGWj4ZcL7Bt.iM.WmsSErR6loV4G', 'อนุสรณ์ ทองใหญ่', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(41, 'apws', '$2y$10$gGf3J7cU37edkBA2fChCWe7gxlRZphVRlu2ByDXiWmPVy6kRgELiu', 'อาพร วรศรี', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(42, 'arr', '$2y$10$dDGheeFW6f6V2bka3h4ynuIUldbFFrkSXQCfGMytCCVVFthZCFtKi', 'อารีรัตน์ แพวิเศษ', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(43, 'arss', '$2y$10$sVZKWa5HyWRdD4kP5ojnMeXhr/Yk6RQXevSkkXs.xIO4K4SGny6Nq', 'อารยา แสงศรี', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(44, 'Phatlita', '$2y$10$BJaGzX3Mv8NHQ5MIE3nAtuv7QfTEDcbzMPUtP59k8QUDtUyTMlsQi', 'พัชร์ลิตา เนติสุริยวัชร์', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(45, 'atw', '$2y$10$FXO.1I1lBgXaCcoJX6dIp.EG98yTUq1zOcZ//Zs.DOypZsB6jsTRi', 'อรรถวิทย์ พานิชกุล', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(46, 'ayms', '$2y$10$lmUW8YeVDwXl8Vsd9W618.wC8lmVUSZeZ3ONT75/C27qeIPOTlelS', 'อัญญารัตน์ มุสิกะ', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(47, 'benj', '$2y$10$qXqURlQbapxhU97D90yyduwllVXafaA1QEfbqx696iNSk6qS2Rb0a', 'เบญจมาศ วูวงศ์', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(48, 'bmt', '$2y$10$VCv7IT7g6Y4rVCbdJyz6sOcoQ9WMlTRsFtnXWJ5.TOl0HihCm1p4W', 'บุษยมาศ ทิพย์ศรี', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(49, 'bpdd', '$2y$10$eWbSJTaD7OXVkbRqb7Byl.izdqmQq8yTDFsMShj4tRVMNCcDyYw5G', 'บุปผาผกา โดยดี', '', '', '', 'staff', '2025-07-30 07:09:59', 1),
(50, 'bpph', '$2y$10$ORIE2TmSevTSpQ8gG5kH7e2AZSV3g4GXV3uOek1euGRlbHUQojZD.', 'บุปผา พรหมหิตาทร', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(51, 'chayanit', '$2y$10$41IFyZgO2O7IqKqgMO8Ur.QVamWkKZNLkWwsPUzUVqzhUm.oLfPE6', 'ชญานิศ มามีวัฒนะ', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(52, 'chopaka', '$2y$10$ZdmDIwhqwKMo9tsdk3fIuujgrPsK5qGqCPpF.PgFqwKgdcAe3/fPK', 'ช่อผกา คงกระพันธ์', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(53, 'chtk', '$2y$10$S/b0pQ9iwDe8GbRNkT6oKuL2l0vzu6VdGmRJAvtuC4/sLC/HbEG5q', 'ช่อผกา ทองก้านเหลือง', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(54, 'chur', '$2y$10$ZVZa.6qvzNm4Pjc1bZsPEuGUmTyec4V9Frm3xk/ZIXvQeiMe8LF0C', 'จุรี สุขกิจ', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(55, 'chw', '$2y$10$C0ElHiwNyNFxTIvWQ5O3Y.yyobB0yhNpO9xKPFuVYNiLaPavHKeXq', 'ชูชัย วงศ์วิวัฒน์ไชย', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(56, 'chym', '$2y$10$dN90VGcr3ewwDI86iagwLuN47o/4q/9QAfT6lNZpmFWZoXuGT/xIa', 'จันจิรา ยศเมฆ', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(57, 'cnrp', '$2y$10$.D9oow2vtsRSpPLZvQTX4eggImECMMzYRiKdeDDTSE73BqyKJW7XO', 'ชนสรณ์ รอดเพชร์', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(58, 'cny', '$2y$10$mYMphcp8gxH20KEIR8zwA.dexRrsOmIDwoH39mlRo6JmKEHat.xJi', 'ชนัญญา เอื้อพูนวิริยะ', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(59, 'dara', '$2y$10$qnB.Wl9R5GF2T0/j76UehO2y5FTL7ObxOl9Gd.ylHWCuluBwmoYqe', 'สุดารัตน์ ศรีจรัส', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(60, 'doctor110', '$2y$10$IZGhMJ2BuxvExyJ9KMG7EOCxN6DZhfxX/T0kZmhgS5N1R7VnbI4vW', 'พชรดล ทวีราษฎร์', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(61, 'dons', '$2y$10$/cRJuREWR506NuoFAeGMjusmKr46y9.JB5aYSHxCNwlkua5AOXULi', 'ดลนภา สารสุวรรณ', '', '', '', 'staff', '2025-07-30 07:10:00', 1),
(62, 'dort', '$2y$10$khVsyuV3061BoI5mi/OkeOIYeImoiPc6pbth0Bv33sOTHoWyCmGfy', 'ดรุณี สวนพลู', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(63, 'drrp', '$2y$10$aymGuV4BleZkUdRh9BW1EevAg.lOau5xEXZXqRPnBoSu96sdSdaAO', 'ดารารัตน์ พันธ์ศิริ', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(64, 'drsak', '$2y$10$15S7DAnvDfXFh.ei8UCcsumN18Bxoe944KAeujNqx/wOVLJMg19OK', 'น.พ.ทรงศักดิ์ เสรีโรดม', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(65, 'Farsai', '$2y$10$fOOOfeo.rAveFMSMDhs2NOU1fIzgoZMWyRTU4gVnKti4OnLHpIJ6K', 'ฟ้าใส ภักดิกมล', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(66, 'gup', '$2y$10$CwbBOZyyYS1xEth4CbABteEOmhhSQgbTGL5l5e9UugLaAKy7q79L2', 'สุวนิตย์ กลิ่นเกลี้ยม', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(67, 'hrws', '$2y$10$Wr3xMRK5zD9C64YScc01iuz4mw0BDkPGu5AhqnRb3Qr5StWVLZBBu', 'หรรษธร วงศ์ภักดี', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(68, 'htcn', '$2y$10$scGajUgCS8d/o5IVK4X21esuB9eehAZY0o/akOEHlKM761orU.BSW', 'หทัยชนก แท่นเหมือน', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(69, 'james', '$2y$10$UmeamxGa7lOMA2iHSTSRZuF7f7J1zUeKzy.74y82Eo.zbcyxxGbWu', 'ชัชพิชัย ธรรมชาติ', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(70, 'jariya', '$2y$10$ZygRNP3rIO4.mHkHWsyxNedmk9Bf2nlsWop2crk38Tawy1uKmEBOG', 'จริยา มาสุข', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(71, 'jeab', '$2y$10$oCToWg1aUfad6qd//aZJcOzTyMHNr8Fwj7hp/J7WuvAyhzRFzAQX6', 'พรนภัส ศรีประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(72, 'jint', '$2y$10$wdqwFzaU7cH/Ql7j1obOC.t2hKU5/jgInHjFGEs/OUsSpTwEAwq3y', 'จินตนา  ณ รังษี', '', '', '', 'staff', '2025-07-30 07:10:01', 1),
(73, 'jjbp', '$2y$10$y.fY3mjuL2mk0dM95LsdJuDNrRlxRoAupWamBa1SY2YlW6Zx6EH6e', 'จันทน์เจ้า บุญพูล', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(74, 'jkit', '$2y$10$n4O4mdkTAaBQuSe0Q4gADOH4LyDQUg4hYkg.xNc6F93JEj2dGEb5u', 'จงกล อิทธิขจรกุล', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(75, 'jrds', '$2y$10$JW6kHVP9XX.E8BEdD6Ewfe0lf5gC65x.GP3PbmY0Xu5KLQWnyA15.', 'จเร ดุษฎีศุภการย์', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(76, 'jro', '$2y$10$OIcLatfo05iLSdt8o/oYqu0tWuutPE4Rf1DYaxr47UfP//dQA7rO.', 'จรูญ ชาสวัสดิ์', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(77, 'jrp', '$2y$10$yEP4Wgq4sk9gpbhQClQgVuuPELJe86Bl5Wm9hsmkPedW2Z/U123UK', 'จิราภรณ์ แก้วกอง', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(78, 'jrs', '$2y$10$zImLT5pJFRgm8jRMJdlUterkNkZmSmckaQIM9Np50B38rSe197PvW', 'จรัสศรี แซ่เจี่ย', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(79, 'jtm', '$2y$10$QNSd7YuyJkVlr40CW/PHNutp9h0MlvRNMPGaX2FDDGC0wsAA82W4O', 'จุฑามาส จารุโภคาวัฒน์', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(80, 'jtna', '$2y$10$PJl51pMAVppdekPNF/9FbeHhgc5LI.haSn/LVmGgZhq.8JJO/uSYa', 'จินตนา หนูอินทร์', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(81, 'jtym', '$2y$10$Fnp6KIflGPANklj/GsnWFOgXbUJ01jy5Vov3YqZZmlZdAG1ngSHk6', 'จิตรา ยศเมฆ', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(82, 'julee', '$2y$10$gN4Eg4seVzaUDPV8oNFiGOsyTetWh7KuRxOEDLUuqf13B0OSpEs2W', 'จุรี สุขกิจ', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(83, 'jutha1903', '$2y$10$mE0JZKGs.p6Y9Ax8SO0a2uK/xYLsqSeoddm/wj/e/e7ExSOsAfFOS', 'จุฑามาศ สุทธิรักษ์', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(84, 'kan', '$2y$10$rhdvUUPp2n8cK8D0ZFNTve7QRv7UGHQgknwbC9POKE0.JOYRy05Au', 'กาญจนี เจนวนิชสถาพร', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(85, 'kasa', '$2y$10$dZGacHNBEayVHDeSKeZM8.1i.JSs08wc6K/jRVeXuOBda596OnDU2', 'กษมาพร เที่ยงธรรม', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(86, 'kit', '$2y$10$TOlNrKLO9/ayt5SISY6eAORhBWKjqatx3/1VLZkT4fJQzuz9oYskO', 'กฤตยภาส หวังรังสิมากุล', '', '', '', 'staff', '2025-07-30 07:10:02', 1),
(87, 'kitti', '$2y$10$iP0KrUUIbgiib59flPfjCej2ayAiJ4gu8g6iS7/39ZaJ3gWdKh99m', 'กิตติ อุบลกาญ', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(88, 'kittiphat', '$2y$10$qAXvcIg.FcvnrzdKf5SgmOgPpoW3W0IlSNucq0lhRpIWSrAEqnZDC', 'กิตตภัฏ ศรีเมือง', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(89, 'kjsk', '$2y$10$8WfoYUnBQZp.G.nOoetAoOucmTbjW34PMGetttMGQ13PNO6fAkHFy', 'กาญจนี สกุลอ่อน', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(90, 'kkan', '$2y$10$EBrBqu6t7UzQod0WNNo0U./eLto7UWpuPz6x4zOOqVKGQas6mfaxy', 'กาญจนา กันธิยะ', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(91, 'klbl', '$2y$10$kYtojlw2spcJ3mtCyhQd4O4IojicQqPc.dCJCO8Fe7IQscbWeYEmK', 'กัลยา ไชยโย', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(92, 'klkk', '$2y$10$s9X0J7qJwQZvkjhSyocnOOp.iesbD9QS5Xore0IQgW.jqnCmAHElC', 'กัลยา คำคง', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(93, 'klw', '$2y$10$I2vzmNyhQUszdm8XexaEqeeX7FhMR3ztxENwVf8xJ9mDZnJFy0S1y', 'กุลวัต บุญเกตุ', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(94, 'kntb', '$2y$10$oWEac5x8HlLqoRItzX8NXelM9gAzbKOTVhfENlm4W1cl1InOawRSO', 'ขนิษฐา บุญรุ่ง', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(95, 'komol', '$2y$10$2asVbM0Fe8quN5wV9yWX5OnnD99ayFXVXr9p2/8TFVVZTWOoqGWNy', 'โกมล ชัยวณิชยา', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(96, 'LAB', '$2y$10$ygW.0RnMmKkhFZ8SOFSpeupK2rYOm4aIyvS/RwiKB/.8JONc8w3RC', 'ข้อมูล ', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(97, 'lab2', '$2y$10$FNAb8GzU6OqTmLHSDCAjku8KdY73PFk48qb7TQVQyq72dsntzHt2u', 'ศิริเขมภัสสร์ ทองยวน (ทน.5635)', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(98, 'lab3', '$2y$10$JwsT8EcFUcwvYmGB.Fg78ukxnxqrv4R6chlRMjWpyKtqZ7bp6oqWO', 'ทนพญ.วิยรรดา จรมูสิก(ทน.12690)', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(99, 'lab4', '$2y$10$kFV3v6wsFD8ZARMoWTVtg.VyDDmW.xJYI8IDoisJOBuQ8dM6.yDjy', 'พิมพ์พัชร ขาวบาง', '', '', '', 'staff', '2025-07-30 07:10:03', 1),
(100, 'lab5', '$2y$10$N/CbDre7jmDDrlk9rGI/puPN7xKGxaFWBsmsimebDeBTnZv3kbi8m', 'สมพร ถั่วตุ้น', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(101, 'lab6', '$2y$10$m9CyWosV7a9XYoHnBUtvKejJHe675EkuNk1y4UBAaIp6iLUnc.jSm', 'รัชนี เพชรขุ้ม', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(102, 'lab7', '$2y$10$jiU58luqwcDrwg2eRg7M2uuy2tn3gJ9R8XoLmtezsxZxmFyYTkLxC', 'ปวรวรรชน์ ภูริคัมภีร์ (ทน.13915)', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(103, 'lab8', '$2y$10$2jyBCJ9/tMNbkEMR1bPhHOCQCMOugrZqE/553HreH0amgaheuOWBG', 'ทนพญ.สมฤดี บัณฑุสุวิมล (ทน.15837)', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(104, 'lek', '$2y$10$MLd/PcrgUWIzW8gZ8AdvueA4QxaHbZNgN9jQrR90h3PcieFwOhYdK', 'ชลารักษ์ สิทธิศักดิ์', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(105, 'lita', '$2y$10$2JADmCwJnJcrFItekyoNaer8T6SKJq9BHTjurz3NWXkYnBXme3fXW', 'ลลิตา นิลสม', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(106, 'mans', '$2y$10$5cRm5SvRuZZb/NQtks2iw.H4n3SwlMJ17KM0oeLp/F7vcK7Q0COcy', 'สมานพันธ์ พัฒนประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(107, 'metee', '$2y$10$Nfy.upTE4Hg6jhZCvYeCperLdqFdJ73muqe3CxDW0O.km9Yj8UMk2', 'เมธี วงศ์เสนา', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(108, 'msayan', '$2y$10$mLSjY/cMLuTTG3PI7ungXuyAS7oi9eylFZe3fH5uuLHQTekRKeVAi', 'สายัณห์ เมืองสว่าง', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(109, 'nack', '$2y$10$W0zf1pGe9QhVM6uudT9rnunfAToHKsa0.wGCFeqIcRW9p16atAtAi', 'กุลวัต บุญเกตุ', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(110, 'nantarat', '$2y$10$9nsyL41wE/csOwi3s1EjtuBkvZKnu1RbKul5AusPs7ekZzGkOertq', 'นันทารัตน์ อ๋องผู้ดี', '', '', '', 'staff', '2025-07-30 07:10:04', 1),
(111, 'nanuty', '$2y$10$kf64eJEdPNXrvtgOpKzOE.7h4TG2P6TJR37PdnR7e3dbAtCIcmolO', 'จันทิมา อ่อนทองหลาง', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(112, 'natchana', '$2y$10$XVjYj/PJTV65kqA8l4lYzehpnd1dzp0YcI0OBaSFwJAfjXuwUUJKS', 'ณัฐชนา วงศ์อินทร์', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(113, 'nath', '$2y$10$0iXk2xlOQS742jWP0TJEf.4J8UGgtpgVBDS/BBSq9LId7Rthvw6Iq', 'นงค์นาถ คงชนะ', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(114, 'natvn', '$2y$10$PoPDxmNMyDjUcPNJaVL68.0eXCJ7eobtyIbBVzbeiS7RpYdhM9s9G', 'ณัฎฐวินี พรรณขาม', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(115, 'nid', '$2y$10$NLM26KibsfnTaUiUy.07tOb9rsCWjZiC4HZuK3TZI/TwWS/04sEzK', 'สุนิสา สุขค้ม', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(116, 'nitatip', '$2y$10$3Esd9OBlnyTloub/w7YGwujcGgs.6F5PXhTNRbACuyz8Ci.WLM43e', 'นิตาทิพย์ เอกเกษม', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(117, 'niyo', '$2y$10$L.ObF2xxnA8BsNKLKmbx2OkslzU39XvJvtezfPX6905sXAtH6kiea', 'นิยม เตชะเลิศไพศาล', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(118, 'nlcn', '$2y$10$Nz45cej6xxe89kkyp2jwsO/6yTAXaE4lxzlGp08cJorM4jBvNT8wC', 'นิลวรรณ จำเนียร', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(119, 'nndl', '$2y$10$eaU9KRa8YG5Q4EFu/Bwe9eCcNWRIFLtwTGcQ.SaJsR5BiAHnzH.7e', 'นงนภัส เด็กหลี', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(120, 'nok', '$2y$10$208w.xAqmgbkG7NSTdffEOiGHZSTR1rJQO2XfRQ44yKTr.J9lrDC2', 'นงเยาว์ ทิมาบุตร', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(121, 'nony', '$2y$10$g8dR4vlSwShA1xiXeGONOuf1SULaTzHFHgo/mPChYt/DNIzh07dwW', 'นงเยาว์ ทิมาบุตร', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(122, 'noparat', '$2y$10$ShgG/bmA/kyihYBw5isdIegAbDPXTQ02vm6UF95d99wJbVmT.XhSC', 'นพรัตน์ จงจิตต์', '', '', '', 'staff', '2025-07-30 07:10:05', 1),
(123, 'nple', '$2y$10$R9ISVKAKKAnfBQ0EIuAZdOCteeQLuO3w3IvdA.twHU8Ymc2kXOZEK', 'อมรรัตน์ เซี่ยงฉิน', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(124, 'npss', '$2y$10$.AimazK3WJBn2WIOjSiTE.mpHzjR4DpPWDKv.8duHDfat0SAPlwRq', 'นภาภรณ์ ศรีสิทธิพรหม', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(125, 'nrkr', '$2y$10$Ru8OmuzEuKSRroT2CGnClecuekxyshyrnZ9jDHd64J1IH1ylY1tha', 'ภัทรวดี กิจรุ่งวัฒนากร', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(126, 'ntsk', '$2y$10$BZB3BfTqdxSplIlAgoW8ee4f4QFFBEFAac8k5JLpHxOCAd1abjtvq', 'นิธิมา ศรีเกตุ', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(127, 'nttp', '$2y$10$inRCQ92k9v4tx9WwzUkLXOo/nkVLI8qA0hiinj7Ce.A5qTIQ8EA/e', 'นนท์ธิญา ประทัง', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(128, 'onpk', '$2y$10$ukGPyz/aZgpZ0rSMBHEaO.kFsD5onjomWvFpRd9sNlELbY2K9gdNe', 'อรณภัค สมเสถียร', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(129, 'or_sma', '$2y$10$bcdzbExpqPH4m9kuxMPbp.p7kLrr.8qpEsGDN1ufLnM/aXLPD1BI6', 'สุมาลี แก้วหยอด', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(130, 'orpk', '$2y$10$q/xRql2L91VZj1.D.1ak5.p8BVA2bngXBNRCkJF.zOErY7y/.1iGW', 'อรอุมา พรหมคุ้ม', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(131, 'owmk', '$2y$10$yaZEz91.wo1juVUC36OA6uQvNzgI.mX3wgCV064xYLCqHJDtIJIAm', 'อรววรรณ มากแก้ว', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(132, 'pais', '$2y$10$4u/r15rCxdeknI4gb825/eulFIKpfX8/7z6sfOC.5tosAMUP130kC', 'ประไพ เบญจานนท์', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(133, 'pakay', '$2y$10$dFxS.GgbABdePM8nO3WiceKTpxZ991lvY3NO.qH888PF6JU1ta2Y.', 'ผกายวรรณ ไทยเกิด', '', '', '', 'staff', '2025-07-30 07:10:06', 1),
(134, 'part', '$2y$10$4FG7aM2Wba.utBa3rP.3se52AFYQIvMJcNMp5DHJAxwI.OT5zTL7G', 'พัชราภรณ์ ศิริปโชติ', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(135, 'pattarachanok', '$2y$10$jecyq7CRL0ICbrZlnpr3fukJw3nd2Xp8Zh0osU2TD4pFLx0bv.aU.', 'ภัทรชนก แซ่อึ้ง', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(136, 'pattr', '$2y$10$mBvc390Ti0LvKVAMji3lpeeXrchiKoxu3cctSe5nHwUxMF3gzyMxC', 'ภัทรา ฤทธิขาบ', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(137, 'pcch', '$2y$10$bRtRRt0qU8g.L3q2EvgWJ.C0OrplIDStaYQroMF0Q4jkP1ULrNNaK', 'somchai ', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(138, 'pornchai', '$2y$10$WdWAT2t2coPWum3X2TiVQOTrTSv0HqM9Qu5Du6oFu4wqFvXqLr1KW', 'พรชัย รักชื่อ', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(139, 'peerawit', '$2y$10$XP9tKwyBYEf7ymi.M4xt/ex79J1KkH5sSUy2CL0I2pWOYLAYlXm3q', 'พีรวิชญ์ ชินบุตร', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(140, 'phyo', '$2y$10$msBEoTSsQBcJajScoNgdMu8m3I7N5d4vm1LNZtbCAoTf4oNjtX/Ue', 'พวงทิพย์ ย้อยด้วง', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(141, 'pjsk', '$2y$10$0M8SaeJiVHt7U8djJAJcyO6ZCvlYJs7c2pFtUhK/Sfa42pj7BjHGS', 'พจนีย์ สกุลวิโรจน์', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(142, 'pkby', '$2y$10$DElLYHcrUrGWnGWC5e2DI./eV52LX2Xy024VoBsqJGR2ACENwHKT.', 'ผกาทิพย์ บุณยะตุลานนท', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(143, 'pkps', '$2y$10$G8i6J2oXN8XTCOrO7IwBrOkIAbwDD3j0d787NmuJh5dNd708a/X56', 'ประกาศิต พฤกษารัตน์', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(144, 'pmdn', '$2y$10$3KBujXS222/Cm4jCuUC8O.OryPDOs93I8Mdn4.TFGTfOA7vEM7HqW', 'พิมลลักขณ์ ดำเนียร', '', '', '', 'staff', '2025-07-30 07:10:07', 1),
(145, 'pmn', '$2y$10$iC8oGzqdms9CxqNJ9uaCTuGTJDlQyG8fDGn5Loy27VdQlfdJKlSZa', 'ปิยมาศ นุ่มทิพย์', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(146, 'pmtp', '$2y$10$SHcguA0jPzB.PsOnvIfCheW5sSKr8oTxayMMnifqNB9kxI1NQiC96', 'เปรมฤดี ทิพย์ชิต', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(147, 'pnkk', '$2y$10$kllTRsoajkeiVok8vHzo5eOVab3/yfVxczIJKP0E/PeEAEzdhPhBW', 'พาณิชย์ เกตุแก้ว', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(148, 'Natty', '$2y$10$B36WjJHTu6Qqu9mSKnzAPusDHPJysR.kn0z51nSaI3xvsn91F/vFi', 'ปนัดดา กลับรินทร์', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(149, 'pon', '$2y$10$h4e/PLpxx30FwfpFe1WiMOW.KMNDRsVvW.35JBfy5yHmYw9OsRAkm', 'พลวรรธก์ ทวีราษฎร์', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(150, 'poonsri', '$2y$10$S42nRamUXOJqexe2J94aLeBVSPsIjbBGZp1.41weV.NTFr63iVJhW', 'พูลศรี ชัยเชื้อ', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(151, 'por', '$2y$10$iqWbfgk8MljdDvCSunoG5e73ytO45JrdqGK.9bn/cZTBSmfyRHpwK', 'พรทิพย์ สุวรรณกลาง', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(152, 'pptyn', '$2y$10$YC7DvPGi6CFFxYJfet9spusMH3H2phxKmh1SLi2p2tgrKhNfUbq7a', 'พรทิพย์ เยาว์นุ่น', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(153, 'pri', '$2y$10$U3zIzAXF/Lp34IKtPYoPU.v87yTGm8ldATs.50ngxbDY8VkhYxxuC', 'ปาริชาติ จิตรัตน์', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(154, 'prrw', '$2y$10$LjZrEZH4oTlllj5a/Y7CbuXulekIYVzXmmBuNseP6Z/PISWjbqMeO', 'พรรณวดี เกตราช', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(155, 'pst', '$2y$10$L0Z15kYD1cEqs3bCDw9fMe5GrIyEZWPGEExb7/JXIJCSeeGcc/4SK', 'พิสิทธิ์ สุวรรณประทีป', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(156, 'ptm', '$2y$10$2RlH85QBIei5w8lzERHFhuVMuB31Vyf5ulr0LJ8XBN1Dtc5OnXKoy', 'ปัทมา แสงเดช', '', '', '', 'staff', '2025-07-30 07:10:08', 1),
(157, 'kiaw', '$2y$10$QNA1QIz4IGVT8M9YNzqnsO/DI9HINqZyvpSBY0DEeM8H1cV03kNju', 'พรรณธิภา หนูเหมือน', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(158, 'pyd', '$2y$10$4kE.MBOD.rkpzRKWgsnNkuPUCScIhbRJXUIRj3gBDqpU4rUTY7Anq', 'ปิยธิดา ดีทอง', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(159, 'pypt', '$2y$10$HBObYmYt2oNzgmGR.9N6bewsEoTEzE2EKEJKRkEC6TLNRtHrJNbLe', 'พรยศ ปิติ', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(160, 'rung', '$2y$10$k5pEcp7kUnhlhHoXmRJ0f.Kq5.aTU9wpjl8YdfFpnpjlQfB1BZUe6', 'รุ่งตะวัน ภูริคัมภีร์', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(161, 'saowanit', '$2y$10$09fHtbAoTSxzNeCXRwcLK.lRb8YEwE2n41eM2dvqa8P6rZpVRVDFS', 'เสาวนิตย์ วงศ์ศรี', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(162, 'sarapee', '$2y$10$1SJJQjyKELVpjWjE.EloOOb6IPhlySjxCIMqXwYpptCCJqtDFCSTO', 'สารภี รัตนแก้ว', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(163, 'sarapee1', '$2y$10$Tknt8HK7RDZcqLTXwEEmiuWMj4egTmHT/uzoM1JmA33ZJQAXAnf7m', 'สารภี รัตนแก้ว', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(164, 'sarika', '$2y$10$VKKMo6Y9ytLreJNEdPk5eu8vQIr5TRmlusX9Z7ABj1TC.tSjlNYv2', 'สาริกา วัชระวราภรณ์', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(165, 'savi', '$2y$10$RpS8iUvOzP7sfQm/0hKhzOOxfWcoM.AsS5W2phJC6MfWhGxEJNbom', 'สาวิณี ตั้งทรงสวัสดิ์', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(166, 'sctr', '$2y$10$Z9zF75trHfq/J3zedKDQY.U3bajDIKGL9aouPlPYYwNX.6WCJuz5O', 'สุชาดา ธราพร', '', '', '', 'staff', '2025-07-30 07:10:09', 1),
(167, 'sir', '$2y$10$w6SR9qP4JKtjYdwPgGZ54.S27u.bf3fbk4I.NPNqAbwn.HgVO6iQy', 'สิริศักดิ์ เจนวนิชสถาพร', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(168, 'sire', '$2y$10$5sscy1Hf66jA1AxUVDxm9eCw/sUoZVWmIfVTvOoxBgHgMtBVzp0JC', 'สิริโสภา เพชรพรหม', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(169, 'smc', '$2y$10$FbgVN.jmeXREvjfNPkjhEePEiCmT9PVKl6mRGdLQkellRMbW2YgCG', 'สมชาย ทองสม', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(170, 'songkiat', '$2y$10$xr64gKdCg7nggut50pJCX.OQKRMS7PaMqGsmudDWGopRmngZ5B0uW', 'ทรงเกียรติ อยู่คง', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(171, 'sopita', '$2y$10$UJy34iLvfA1EXi7oYtkImOdzU3KjJDYqFYVG.Epi7ri97UyM0S/gm', 'โสภิตา นาวารัตน์', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(172, 'sppd', '$2y$10$oi9vqRpi6MRDBIU94Wx.p.5QTgn0JPWciYGS5zjMXYH.fBSN4qHZq', 'สุพรรณี ปานดี', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(173, 'sppp', '$2y$10$wMLQc2dqUelIqum0PWjsjO1arOYhCfY9diZlYFfq3Gk.Jghl1sSZC', 'สายพิมพ์ พงษ์ประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(174, 'sriney', '$2y$10$7V27D.zi9DtjW4FmoFA8/OYrxhiWmEqIX/vpnVPlxgSH9q5co1H0e', 'ศรีประไพ ไชยวิศิษฎ์กุล', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(175, 'srmd', '$2y$10$.40GNcxTe6oldmsf.DMDtOhwKiIqiSPI4d7QxGVy8Z04a13Zej2MO', 'สุรารักษ์ หมกแดง', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(176, 'srp', '$2y$10$vlJU5RVzU0BJbYuC9lHhJ.lfQ7RGtt9I.pNCLb3gq4JhBlDIbjZpS', 'เสรีภาพ แซ่อึ้ง', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(177, 'srsr', '$2y$10$2IP0xY2Rnuoz2OE4rENG...t.wSiuQunbijHRYgvWFPSfW0g6OvvK', 'ศิริพร ศรีแผ้ว', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(178, 'srts', '$2y$10$.QU4Z78kksSdXjwOMnz4fu7i3HxaMvddEtb1TkLpeGE5GwDIh.CKq', 'ศิริพร ทองสร้อย', '', '', '', 'staff', '2025-07-30 07:10:10', 1),
(179, 'suchanar', '$2y$10$Yyx0NR/16vJEevHNs8X6ieBtw5fDWDBGoytmQl85AQoVPRIH/o3nG', 'สุชนา รุ่งโรจนารักษ์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(180, 'sudarat', '$2y$10$Uso3KDoX..nu6KGXg2W4uuDM3v8uf8Pr97yVFUpUC4D99sTE4IWcK', 'สุดารัตน์ นันตสินธุ์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(181, 'sumalee', '$2y$10$PTCORvfA8Am59netX23Obevf7z4YK/2Narvk3hcQhQpeeliNZxXMK', 'สุมาลี แก้วหยอด', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(182, 'suttiruk', '$2y$10$EreCWI.KzuG4ghIDCCtvrejmz1t./FzqUMm8gctqtzMbNeFbz2Pre', 'สุทธิรักษ์ ปานแป้น', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(183, 'suv', '$2y$10$JjB/2pcE0DOQNzAbUEOHReglGAFDpuceM4Pxn/JuoPVU1roRsan4a', 'สรวัจน์ เจนวณิสถาพร', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(184, 'suwapong', '$2y$10$6s1QZu4jDX4hJC5oxwqpCOW2Yr/GcJUC75PxUBZFa2eyIPAh15lPS', 'ศุวพงษ์ ตันทสุทธานนท์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(185, 'suwit', '$2y$10$DBxqVdao.gxB0hptqvrhNureNhUB0vRN7nGD4BLmCPx5HKDgCyz4C', 'สุวิช อักษกิตติ์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(186, 'swkn', '$2y$10$dX.E3K.4wWngzEQKyP9BkuIiqhsY6xekhLbQ4cFxBw1GoJt9cmIeK', 'สุวรรณา แก้วณรงค์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(187, 'swlk', '$2y$10$hz9gRSwZbIK5U5tHJEBy7uu23Gi.cSR9Al4avlxUBvF9NOWzOuHNG', 'เสาวลักษณ์ ปานศรีทอง', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(188, 'swnk', '$2y$10$Zdf/B/Ecto.opWXc4gWfhevKQXDJDrUVwa.PyO8jOfId6TyMvr96i', 'สุวรรณา แก้วณรงค์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(189, 'swpe', '$2y$10$XWQaXyU9H0KN5j6GDeCqTOJudn3HikAjEv8qE2CgrzHF7oKLsoG4.', 'เสาวลักษณ์ เปรมอิสระพันธุ์', '', '', '', 'staff', '2025-07-30 07:10:11', 1),
(190, 'swst', '$2y$10$FMa1gPMzDA1RfRLKKKC9h.VaW9CN8iyBNOdb2sVpr2kUebyhn2hP6', 'สุวิมล สังข์ทอง', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(191, 'tai', '$2y$10$SzfdXdMWU4PYQ2XQK5oY/.DM3K8MhtIxg8GxopW6d/rdMx4XMf9MC', 'สุจิรา ลิ้มสุวรรณ', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(192, 'tanog', '$2y$10$eRT5IB2Us4w9e7i5PhPFBeByIc/NpIJhK3Ez9odMVdGNAe1pZXkBy', 'ทนงศักดิ์ หทัยวสีวงศ์', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(193, 'Tapanee', '$2y$10$YFs6xfNgDhbHmAXTPZgOMOY5YEEMrPtuYbtIPpwLC7aKHybgBuiNC', 'ฐาปณีย์  ไสสุวรรณ', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(194, 'taraporn', '$2y$10$Ls8y.Uos.xPyWdJkGDHSTOqBqzfE1BRLbd78kbPfb4IazSZJ8rXgi', 'ธราพร พัชรินทร์ไพจิต', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(195, 'tatchanon', '$2y$10$.Zku8z1F44L08Ld0U7N/SeepsBP2fJ0DLrxxbjpnoQ4jepSql0LpK', 'ธัชนนท์ ศรีทอง', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(196, 'thidarat', '$2y$10$N3oCmuiR54FA/a7g7VPZXe4wQLQ8fe7CTLU..DVDeT9tZPks.i0BW', 'ธิดารัตน์ เสริมเกียรติวัฒน์', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(197, 'tipparat', '$2y$10$IbjRjfxO1hihEwZj4rG1PuVYlxruiZDnHv5N4cBhtALkvpB0js.fy', 'ทิพรัตน์ นนทฤทธิ์', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(198, 'thitinan', '$2y$10$wDj0mMbfJMM3GKDGhUHw4.1Q1mEEZOQotvlJGMqmdjd8GViV6T/Ha', 'ฐิตินันท์ บุญทอง', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(199, 'tims', '$2y$10$YPKyBTIYDlZhV4CMT.J72ej3aqZiRx2zjUbt2czWoVH5TwHepsoGu', 'อรอนงค์  วงษ์บุญ', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(200, 'toon', '$2y$10$tySKKvXOWNsVsaUzzUSrgO.Npa/hVtysr1.o7tmAWeXwYHNgYCw9O', 'ประภาวรรณ บุญช่วย', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(201, 'toys', '$2y$10$IXWN69QTookqRrM2ZvuFFeDzlxRV5CtyTf.H3zsMbrqN58PUgg6J2', 'เพลินจิตร ช่วยบำรุง', '', '', '', 'staff', '2025-07-30 07:10:12', 1),
(202, 'trpj', '$2y$10$.P15DW6y18Nv4evoPLsgAO3h06pu3WG3.fgWlCXpBWobAJ.sT90JS', 'ธีรพจน์ ชัยชนะ', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(203, 'trpn', '$2y$10$jetCAR.vokW0L8ETQCs/TOsCRf5r2aWTHZ8VZAx8ZHsUForo9gdki', 'ธีรพล ก่อธรรมนิเวศ', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(204, 'tumaoyvan', '$2y$10$dGzzHlGusx4AsOclYIi/1eVhHTmOXRUuvz5ENXhmAdKHNGed5sWlu', 'ศิริลักษณ์ ปลัดรักษา', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(205, 'tung', '$2y$10$0N9Kgwoee5j2e0ifhLqcDOvUdktNtL88/NtFjJfP81e9Fe6db8uK6', 'ธัญศิริ ศิริกาญจนสงค์', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(206, 'tussanee', '$2y$10$3DTvJeBS4B3J/kq4OD/DJOzr5/80ViuoTTpVDrYO7HUA.nispFZly', 'ทัศนีย์ สนิทวงศ์', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(207, 'ubsn', '$2y$10$T9tXtJUOwC4LVx9YQF.2VeZEE5jC1IR1M7.ERGV4nvg4KazVzbLc2', 'อุบล ศรีนวลดี', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(208, 'udss', '$2y$10$dhy97gT6BuuZEJcNucVjFe6STqj49cyrmJkZbly0SWDD7lWR4yu5S', 'อุดม ศุภศิลป์', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(209, 'urss', '$2y$10$NNgsOpqjGyMXrX2zZbjDmOQeSR9NnIbqdCqVft8SMcFQvVYOhBT9.', 'อุไรวรรณ ศุภศิลป์', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(210, 'wanrudee', '$2y$10$Ke/nJCEYg3DfD53LTPd4ROLEjf1dRKFM1.QCrzmSQMz0ncWML6tKO', 'วรรณฤดี มณีน้อย', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(211, 'wardma', '$2y$10$h1HTHcKEPefAf8F8JNRj/usG1ng527SvPYvR/vtL7ugmkWDpF6kfK', 'เสมียนผู้ป่วยชาย1 เสมียนผู้ป่วยชาย1', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(212, 'wardsp', '$2y$10$Bp2zTrnrN9MA9OSkepmazeRRuWYq6p.T.QdW7EQfHeGXKr7fmKWsK', 'เสมียนผู้ป่วยพิเศษ1 เสมียนผู้ป่วยพิเศษ1', '', '', '', 'staff', '2025-07-30 07:10:13', 1),
(213, 'wardwo', '$2y$10$JlqNDZoVoxeqxtA8ELlt2OmJheirrCRjPgs/xMYnwFHdGzjZS6UBi', 'เสมียนสามัญหญิง เสมียนสามัญหญิง', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(214, 'wcyd', '$2y$10$oxzBeirDFSTnIPS49y.GB.NWSTSxqYJK9G6Ydab1kuBFwFWrt8Mwm', 'วิชาฎา อยู่ดวง', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(215, 'wlpt', '$2y$10$/1Cj97G9VBUGtSwaPJDzN.v5IAEBhEA9kkDibqG7LPdaB50BTMnt.', 'วิไล พรหมทองรักษ์', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(216, 'wpsl', '$2y$10$5ytB7QCODcNjHfurQNF7metxmhHZXAdSVx6mnkBxPurIFvojI.LG2', 'วันเพ็ญ แซ่โล่', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(217, 'wrrs', '$2y$10$doUhfVCRTiFE4./pOhrMpuHLQi2RsEtfHUL5dd8HDKnad9DFk1I0y', 'วีรภัทรา  รักษาศรี', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(218, 'wrsj', '$2y$10$YRGcfwqMuraDo6bRQYe0i.cgzUWthvEm7yGaGlr7hH4tVtozDQgWG', 'วรัญญา โสมจันทร์', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(219, 'wrsr', '$2y$10$JGX.eGIm/bAfDXN87SBSX.K9blGpV43yKB.5psAigNBmR0Olq3PbS', 'วรดา สาระผล (เสวกทรัพย์)', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(220, 'wry', '$2y$10$9DE4SOX3hyX5z8ELeHDCNeRypmv.miXMSrdl3iuXIieLx08Ebz7f.', 'วรัญญา อติศักดิ์กุล', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(221, 'wwhk', '$2y$10$uoxW3TgJJpVUnX7YCBno3uvZ/Rl4ZrnGOtwGFa/ANja9y7qdSJDiu', 'พัฒนวัฒน์ หาญกล้า (ทันตภิบาล)', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(222, 'yah', '$2y$10$GzkHOCNVIhm.C5rx2AdTX.g8i2UWqWU0cOL/i2YsTrnhAUDG48rQO', 'กัลยา ศรีบุญเรือง', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(223, 'butrat', '$2y$10$my6c5Fi7ib9qbIBInX299ecP64z0BcPQ6AHZoBpc4uF1sfsrrCZ2O', 'บุตรัตน์ โกสิทธิ์', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(224, 'pplj', '$2y$10$Vi3cTBnQd5vjVRzKvEWNBOv9FlWoEkuYBfoWWfi0njiPNVRPHN.Cq', 'พิมพ์พรรณ ล่อใจ', '', '', '', 'staff', '2025-07-30 07:10:14', 1),
(225, 'supa', '$2y$10$rFsYI6uEnI8IhigaitkYweiZWmwOL38odTC/EtKeXcc/tSXdvuO1C', 'สุภาวิณี  ศรีเมือง', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(226, 'rat', '$2y$10$S.EL3OOw8rjx1YRkft36DuwxVkNlYY3K.Vh4jBR0c2e2cXAwuyojC', 'รัชนีกรณ์   พัฒนประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(227, 'chu', '$2y$10$bIDWoIs3Riiefjs0tA8Zm.mHYP9acxBTYBg/LZpGDGScPah6ECcA.', 'ชุติมา  ท้าวเม้ย', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(228, 'supattra', '$2y$10$MiiYpuzJeU5c4OEKddnOXuycfXcO4cHk0IEP4650uVy93xRSCY20q', 'สุภัตรา  ทศกาญจน์', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(229, 'ousa', '$2y$10$8YTgJSgAnRVOyNezIOlbMOyS8.Yr1TFKmPb0CmvIn/eBFECzcIcRy', 'อุษณีย์  นุ้ยมัย', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(230, 'ninlawan', '$2y$10$.BpinSSfuNysYQofT3MyCualhrCC.FFyvSYXmjQGJSlDvQ1YtbUuu', 'นิลวรรณ  จำเนียร', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(231, 'lovename', '$2y$10$iIAQgICczs6oadc3vzvhZe97KOeoEB2jq3Y1RUJU4KREJXTOq33p.', 'พรชัย  รักชื่อ', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(232, 'pannisa.ws', '$2y$10$U7g35ZkXuHwLv9Cbs1XDLunxYEj6s3NgZo/zTqnZhaBfNzsS.Wrsm', 'พัณณิศา  วงศ์ศรี', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(233, 'admin131', '$2y$10$V6NGkpgzeBqfr70NFio/judLvHE9jTx1phrM8vwJ4U1AIeMG6AUi6', 'admin 131', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(234, 'admin133', '$2y$10$FTKGcSilng.KPlWDAOl44.T/F4YQksCDXmSrhHdZ2HRxSKeXz234W', 'admin 133', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(235, 'down', '$2y$10$HnQJR8V7mjx0lA5gjlLyBubAm9rxSdFN.OcdA6XGIBkrnslvkYcdG', 'รัชนี คงกะแดะ', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(236, 'disney', '$2y$10$4jR9URNIIbaym9DyCROYR.OErRt5mqTlRPA1TtvqxN9ObeQlXESyi', 'ซิลเดอเรลล่า', '', '', '', 'staff', '2025-07-30 07:10:15', 1),
(237, 'Dora', '$2y$10$O5IR0lXk8swfWZeNEfX3TeHGr7k/xwyd9iZxeB4ZoT9NpORYaiBvq', 'โดราเอม่อน ทดสอบ', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(238, 'admin134', '$2y$10$NlsPKwP/yq5Qevy4Tn25deDxyp4Tui9DOIJ16LPV5nLmW.wbtWl2.', 'admin 134', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(239, 'pwdc', '$2y$10$nDSOH6tqEt3xw.hXwkJKZuMHD6lBD2Q05VTIrIrOht6sXiLVi6y72', 'ปิยวัตร  ดำชื่น', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(240, 'mai882488', '$2y$10$J8WMzWOzY4PdTAGMEll3tO1PRjTDUGZ1WF.SOFrhKK.emcW5UVHn2', 'จุฑาภรณ์  กลิ่นม่วง', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(241, 'a', '$2y$10$c1NkjLsQZ6YymxRBemxKzusWymoQYkr.Aon9abZtQZK33wYyOvbb6', 'ศิริลักษณ์ ราชรักษ์', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(242, 'cat', '$2y$10$ulZhQkJ3.7W6kaCpPLQ1h.NlF2uSkmxQda2fe8Lvkg1K0oQHXKWPm', 'เรณู คงปาน', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(243, 'kung', '$2y$10$IvMjEedoiUMrXhHLbRu0sueY22/Syy8z.f1l13xVFgLEeAE/n1UEm', 'สุวรรณศรี ศรีสมโภชน์', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(244, 'su', '$2y$10$aTTvEvlIe2pEruF74gs/2.WrONqRMvHqJF5NfUX0TqhGJcknYj1t2', 'สุธีพร พาลเสือ', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(245, 'kanitta', '$2y$10$1yL0/w6GXwgPQ3ZcA7h1LOfRo2BgqfVsgiTyhjexWdiMXwG8.frGK', 'ขนิษฐา  เกตุเอียด', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(246, 'trp', '$2y$10$ya968GwyNwnZ3iSew4SpS.S.9TkFvpyOXmKmLV8FhIXERZeW9wzCG', 'ธีราพร  สุชาตานนท์', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(247, 'wipa', '$2y$10$f.AQ4yABSoYr0pDB7jIE8./T/XtTbejdzPHx2AfS7zxzkFhChwzzS', 'วิภาภรณ์ ศรีประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:16', 1),
(248, 'chutarat', '$2y$10$jGZfzJnLGsIZakfzCytRQuspihFuOrWUajE3QUF5FzAnT6lPIE5Ny', 'จุฑารัตน์  เรืองชุม', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(249, 'ptrp', '$2y$10$JFhP59aGBZPLbYB5620HJ.QKVcO8wp1DDfKeYDdWRkLn2r0Ez6SwC', 'นางสาวภัทราพร ชนะคช', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(250, 'skkj', '$2y$10$PjsfK6KcplW6nzLer5xgbO7GywCugh.wIOT9a1ot3JG7dPTF4dUcG', 'นายแพทย์สุขเกษม   กุลจิตสำราญ', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(251, 'wanphen', '$2y$10$5YsjGnkxLT.A3LJD9ILz5.selKn41KuuTRcRcQ3e8WJFppteYLr.S', 'วันเพ็ญ  บัวบางกรูด', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(252, 'jtna1', '$2y$10$DMXWyFQtqqixl7Zwku6wc.RRlZhU1FAOwRdfhr7A3uctcqHSP00LW', 'จินตนา  หนูอินทร์', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(253, 'kyk', '$2y$10$pCGv7GRNJ29iktAPmlWr9OlA7ExwOY04esYFgrUjNtTmiNj0UPHhi', 'กัลยา แก้วหยอด', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(254, 'ctp', '$2y$10$cFuu3gXVT8w0KViI3ANlueXX2cwTpTTPGi0O2Z4IdPYlE6.60Hv2C', 'จุฑามาศ เพิ่มพูน', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(255, 'ppt', '$2y$10$/IzV4qYAYxLgBWY5yGD3..ongbESzsI.CnPhbroRZNsKK3masVFCy', 'พัชรภา ทิพย์มี', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(256, 'htk', '$2y$10$7Yhr4J.kfDiStu19J4AIHektqrODN6ID1qmjKouJy5HS1CPf2TY6q', 'หฤทัย ขาวเรือง', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(257, 'ark', '$2y$10$g3A0uWeN90Jg5pL60DmkgO3UHzdx.D/XzIdVnVvyOZq3J4g1RI8Im', 'อรุณกมล แพวิเศษ', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(258, 'spt', '$2y$10$hzjjWH5gYBb8z8bfJxF7FunUfDzvXqC9ghvagii6ojCwaIHItNMOm', 'สุภัตรา  ทศกาญจน์', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(259, 'tnp', '$2y$10$Bs7LbVioYHnM8cZ6ol/01eQoZ1bQLK2R4H3hdaCytYs3nFrk7gEie', 'ธนพร  เสสิตัง', '', '', '', 'staff', '2025-07-30 07:10:17', 1),
(260, 'wlr', '$2y$10$Ou1vomi5yYJ0EJVW2KbkEOprmV32pe8vx4oGM9EsL5vcvO3JVOUJq', 'วิไลรัตน์   ทิพย์คำ', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(261, 'ntrk', '$2y$10$JVnjRF9mz6J2LhREc5w.meheaF9Qt/LGqMhgLPRVxh6RoZV/gMfeq', 'ณัฐริกา   แจ้งศรี', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(262, 'stk', '$2y$10$wduTVX5CAIK2xSP.KclpMedCvXsSoE5d9T6tO24owSBYW1ONDh0sK', 'สุธากาญจน์   เรืองจินดา', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(263, 'wlk', '$2y$10$oa9DJhpvE6MWurFeJ5lUuOYgT2UKS7IATLKQQxZCRcvEydpj0g0oi', 'วาลินี  เกิดสบาย', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(264, 'ncn', '$2y$10$SFiSJ1kOyrEn/eMy/Ylr9uhZjkrq.SZQKOv14T.kvgCFDPkDgeovS', 'นุชนารถ  ล้ำเลิศ ', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(265, 'pyp', '$2y$10$thit2Z4Z0RcMxBL/gfpFiuHlL54sWhekY97fwJuYVpZJWlbKckIlu', 'พรยศ  ปิติ', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(266, 'kwln', '$2y$10$.B5ulqnA4HlR91Fu3OyL3Olmam6kE.ovm4XysjGvVxVCELJK41W/q', 'เกวลิน  นวลเศษ', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(267, 'hrcn', '$2y$10$hippctuknWzp1RaOdWNrceT5PnUs40JozHVKQX7vlZQDarK76NEyq', 'หฤทัยชนก  แท่นเหมือน', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(268, 'wlwc', '$2y$10$qfFzFY9L9GTKcJ1SwUchg.domIzJ2cFOMpjdFHtgCmvP9XRyl/G3m', 'วิลาวัลย์   เชตุทอง', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(269, 'srrm', '$2y$10$UR.B2rvNRQGmYiIHNjC0LubJpptvo.lh3C0WRHCUyHTV1JxnzJOyW', 'นางสาวศิริรัตน์   มากเกลี้ยง', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(270, 'drwd', '$2y$10$bwM0/3Pm3MLl/KATO42r1.1IETtp7bYRGEYQ3uIxs8KOuyqIUSiSW', 'ดาราวดี   คำทอง', '', '', '', 'staff', '2025-07-30 07:10:18', 1),
(271, 'spdw', '$2y$10$C7JuZr.sJKTCKbnV09xoYO.zWi5zrCsUfC9BnAZ89ToczS2T7svUS', 'โสภิดา   วรรณเต็ม', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(272, 'Aunt', '$2y$10$5sgxTh.p524/SgNhqyN0NeuzX75E9QTsMU3bHwrJhTIZ.3wferYQa', 'อัญชลี ไตรระเบียบ', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(273, 'Surn', '$2y$10$hir6WV12EVd7RRSeEP7jg.aYMlvTMSlgBp7ecfMgnmVJD6GSBvDxC', 'สุรางรัตน์ นวลเศรษ', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(274, 'plp', '$2y$10$GZ34jc3tgEn7uql0iNQW0u2a8WQPcQdBMqdCgQgLJupI.DZZNRu66', 'พิลัยพร รัตนะ', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(275, 'changnoi', '$2y$10$pLlpoax/OgiwYEDARxTHfeExCnErfdM1zKGGJZU8vS4OUjgn5/q9y', 'กุลฉัตร   วรรณบวร', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(276, 'nrs', '$2y$10$eeusi5SspJPPu1lWEO6NrOFF1cva7N4nSFEwG3re/knrd8k6ukFRm', 'ณรงค์   สมศักดิ์', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(277, 'spds', '$2y$10$A1y/PR0cTdcZBB5AKpxCt.TFbgjhv34Ym8oMNW0pnm2FlpBL6zFuu', 'โสภิดา  สุดประเสริฐ', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(278, 'skrh', '$2y$10$G5sbCnXQRGZaIzkn2YUmG.0zYfm.dcXvVmgl1vJku250erZx6dK/y', 'สกุลรัตน์  หงษะ', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(279, 'tynp', '$2y$10$bY5Z7maSLNxKH3DO7PHImuv2T89xIb3ohnQv8DeRoDoXZKf9ruq2m', 'ธันยพร   พลกำเนิด', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(280, 'prcc', '$2y$10$WAzdqWZisoHcbiJMLz4/IuCCIadcNxLjrXsuSvwS0VwT2iWWIE2b2', 'ปรีชา   ไชยตรี', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(281, 'twch', '$2y$10$Jh21.m5/uxJuAPrLsctRQewAfRw5Td/bn1XHjjMnF6ckRkVUeagPu', 'ธวัชชัย  มีปากแพรก', '', '', '', 'staff', '2025-07-30 07:10:19', 1),
(282, 'ncss', '$2y$10$lXuKDFI3WDsl2qHBbnvRE.Cy75cpjRyF6GSJkYNKSk6G6VL6fMMIy', 'เนตรชนก   แซ่ซิ้ม', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(283, 'dang', '$2y$10$X4N135LxJuw/87HMsuIWOe5oKkBckQCk6sgxUc7eFV7SRXmAD2Xmu', 'ครองขวัญรุจี  เสวกทรัพย์', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(284, 'wiparat', '$2y$10$ODHBMkcYyfR8tJdtsKW/UOVKqojXmmmQubSZlgrpBYK1bqBpKtpNO', 'วิภารัตน์ คงมณี', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(285, 'pookpick', '$2y$10$mqIMPpaTLvMx4T8oxjZ0EeB2RuUUMbqHyCjmzyGItq9Rw7cUmT6mi', 'กุลณัฐ นวลแก้ว', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(286, 'ntkt', '$2y$10$5XYBpDuJNH5RZinJEkVmEu5pTulfCgVe1kMGYfglsCRvIM1pOw2fa', 'ณัฐกฤตา   กาญจนสงค์', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(287, 'mod', '$2y$10$w38x63nXftEmoGTd6ZvB9.l42ClsgEgPzHsX3jlN8DWtu4njC9rxG', 'พิชญภรณ์   ยิ้มพัฒน์', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(288, 'wannisa', '$2y$10$Lyb0i86rq3YE8ENL5OVuP.xDeXs/eLBcLM5HTHzkx4vHO6s6Calgm', 'วรรณิสา  โพธิ์โพ้น', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(289, 'park', '$2y$10$Da2NbTqcRvWI18GU9TWwoeUGEZ1hlUj9ORCEfmtGM1AbZAGbS51RK', 'สิริรัตน์  เพ็งบุญ', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(290, 'yokel', '$2y$10$CmEi4GIYyrVYvOv9JjKdc.eXu8JdzE2K5pecdQssFQGPBsq1smRrO', 'yokel', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(291, 'kiosk1', '$2y$10$KBRWYvCXvmOS71sWvKZ6G.2A0TObPPMUJtCBp363qUTFb1uPvXGr6', 'kiosk1', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(292, 'kiosk2', '$2y$10$kfRnR2r1IhW93xlLrQbVMO/wgvyu2uYdplaw1svoxEy9cZfKgr.7G', 'kiosk2', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(293, 'ynln', '$2y$10$DljPy33WTrNS7iJV4c3DRuNIFjlz0P59ji3n1x4V.e6VCS6G8OEBW', 'ญาณี  ลีนะนิธิกุล', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(294, 'wcll', '$2y$10$y6PkKiXSk8/TeADvjXuSB./eVG6vfrTCHA.o48QAXZ/c1nFAAhwMS', 'วัชลาวลี  แย้มแก้ว', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(295, 'trnr', '$2y$10$ZwS2jJG0WaUhHSZYn7oJauKYTWkQLK2BZaFrDhYz51BKhTEjwW26y', 'ธาริน  ณรงค์ทิพย์', '', '', '', 'staff', '2025-07-30 07:10:20', 1),
(296, 'tlrb', '$2y$10$EkKi6P9ntgy8.e/f/EhjuOzP9v7qsbUNFsdAk9.4Xb7SriN3Yt3ti', 'ธัญญลักษณ์  รัตนบุรี', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(297, 'ktmn', '$2y$10$vEgv/LRCsuacEgSwapXDp.1HJi4kIlmEZMLSzCYnzMtN4soWB3X4C', 'กันธิมา  หนูศรีแก้ว', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(298, 'mew', '$2y$10$st91OhSpzuab47iUVx4nNuM4UoRkJDznCGNCrcvNJCeR6lQrYnflK', 'วรรณนิสา   โพธิ์โพ้น', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(299, 'astr', '$2y$10$4eyilaV7b6ikpAQDY476SODbLLEqIN.nWyBVQcgjovyXFBDqIHMii', 'อสิธารา   มุขนาค', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(300, 'phraw', '$2y$10$ownRxNQCKmkZmy4kofUZL.EDkIx146saMAeLcLtERWM4CZF1mhjJK', 'กฤติยา ทองรอด', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(301, 'orps', '$2y$10$WuvWaT4LHr7QpTWfM86Vy.MoxIdXwBV700FJ3unXpNfC.GOorB4/i', 'นางสาวอรพิน  สุทธินุ้ย', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(302, 'ntkr', '$2y$10$mFaqykKX5djwvHx1rRFI6em1Vzkvi8On2vFF9I3hJB.slZvq32zw2', 'นายแพทย์ ณัฐกิตติ์  รัตนฉายา', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(303, 'supaporn', '$2y$10$L7QfkAlMerlXJHhBThmSa.qtawnVp470imHKAkCT8bYEvVIau5L3.', 'สุภาพร วิรัตน์', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(304, 'orx', '$2y$10$Eu6LovoSJvwZCIV1PymtRO5AXFPghXyN9Rwzqq3YykJX86Mao1ggK', 'วีรชัย จันทร์แดง', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(305, 'kcpj', '$2y$10$aQvV00yZoZ4w6vwONH29B.HDhv4TLEIgM8Gf4Ty3DT08eB/PS703u', 'กาญจนาพร  จิรกิตติกูล', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(306, 'knpk', '$2y$10$OqN0IhOC3hVdyYvKjq6p/udUhmo6KbQb.sYQOeXoD.voFtaZJv8OK', 'กนกพงษ์  ก่ออ้อ', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(307, 'pkmp', '$2y$10$bFBb7TulQD1fVDV5n8jIv.0tt9PaYkUlpmqfkhbeekh7uH0qY2WaS', 'ผกามาศ  ปั่นเถื่อน', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(308, 'tdrs', '$2y$10$m6vUSxciC8LDQ/ScS3845.ugFKxk9X/Y.kE9vKyKgQ8HAl76Eif/O', 'ธิดารัตน์   ศรีเทพ', '', '', '', 'staff', '2025-07-30 07:10:21', 1),
(309, 'lsnd', '$2y$10$8JecnPbNJogr6mY.HBphvunooK1tdFiM/jaMGKR9xXuT0i9fC9pNi', 'ลักษณาวดี  ขำใจ', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(310, 'but', '$2y$10$/nwySfMzNIcB2hg8omXxC.4RccKmMlIPcpTr6oy9vfUGb6LJOfCly', 'บุษบา โภคากร', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(311, 'tippawan', '$2y$10$6VkjVX42zKCDj5dbZCufJeXd1OMxc582l/gv/CwJcQb6eS8StgxGu', 'ทิพวรรณ รักทอง', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(312, 'piyawat', '$2y$10$iX1dw0YipuFAv7DO8BGPP.pSM0cxV73Sh8PdU5yMgDESaO3/uob0S', 'นายแพทย์ปิยวัฒน์ เลาวหุตานนท์', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(313, 'Thanutcha', '$2y$10$3dwZBNoI6y59ck94unJQle5jIjM8Tp.lCua15micT6.cWn677t3yS', 'นักศึกษาฝึกงาน โภชนบำบัด', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(314, 'admin135', '$2y$10$pdyLkfn5z4UZsNON/M2by.rO7TlDkK8Id50UmU8734FeoRHS.q.4C', 'admin 135', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(315, 'paweena', '$2y$10$dAb7bs7az9dILbOBWk9sSOi6ssRy/IzhdXRP6/BJnZJ90es8cnL4a', 'นางสาวปวีณา คงเหล่', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(316, 'mr', '$2y$10$CilgDf..KlZEQaKqfxe5xOBAH2j.5QXPNIpsQCptPVePFnsZMANI.', 'เวชระเบียน', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(317, 'gippy', '$2y$10$2DSKdo02PLcIGbVh3hKO4.EsA2IAATYq.X9ER2/3gYfjm7C/.iX02', 'นางสาวอรทัย วงค์พิพันธ์', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(318, 'pimmy', '$2y$10$tj.6rZESlTd3INH2/jsc9u1zcmHq.Eqj5YSz6hsA11taErjs9PH8m', 'นางสาวปาลิรัฐ สิงขรณ์', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(319, 'tnpp', '$2y$10$FLybjS4p25tGCVD/yDfgBOBVo3mqItZ.Z.vxOMeQr.hSc1UAB.xVS', 'ธนาพฤกษ์  ไพรภิมุข', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(320, 'psp', '$2y$10$mHnBEJgMCK88u.G1QkL3T.FmROa1TMXYsPDlhYZ2acWGy9qLWjETu', 'นางสาวเพ็ญศรี พรหมหนู', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(321, 'stj', '$2y$10$uby/XZfkr2RgFzEUjdBTOezFTBB1k5h9joCsNuW9oinyVzvG1CQcm', 'นางสาวสุทิสา จันทรังษี', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(322, 'pt', '$2y$10$losHiWvRIIIclvnC07CuHepvQLU7KQi1lLpS/kzbBNP1DVSOTHIU.', 'นางสาวปัฐมาวดี ทองคำ', '', '', '', 'staff', '2025-07-30 07:10:22', 1),
(323, '0514', '$2y$10$ZK4fVsVB1Bl3aKtSZEsOgOw0KmShxiiMhIf./SZOLvPqUpX0hvpx.', 'นพ.โสฬส อนุชปรีดา', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(324, '0925', '$2y$10$JvxgfNC3TfbicK4Af..VLegcvBR0CsnmiVxOdelehRGRVgCsHbofG', 'พญ.หทัยวรรณ ม่วงตาด', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(325, 'mtrt', '$2y$10$ZnCvBJ6aZ68scy5FAOBmi.F3NRm8YiwrKBZ6AbR3cMsNSSis6gnxe', 'นางมณธิรา  ทวิชสังข์', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(326, 'dhtw', '$2y$10$JULkB1Bys7zCvMltiJ74Iu14NL3Co5ksAWt7XNJ.rcVMN9b7tVWn2', 'นางสาว ดลฤทัย  วงศเล็ก', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(327, 'kjrr', '$2y$10$2TmDw5LFa/KtZxJcN8OEdO9SruajR3A4fFByhWeq9jww3o.4wk8ae', 'นางสาว คมจิตต์   รุ่งเรือง', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(328, 'npkw', '$2y$10$hUreYf1ViNVrfqx7Yua./uYiqRCbegRCIj4xKg/M0aMwE6bdMdJAq', 'นางสาว นภัสกร  วงศ์ศุภชาติ', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(329, 'Jantima', '$2y$10$.2bTQZHz.8p3Z0T0SOkowug10FUdE2h0G9KWnsSnxTZIZ02XEb3fi', 'จันทิมา ศรีพลับ', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(330, 'wtcp', '$2y$10$8pzkk/cxOHDwKpl70Dfw7uiC9yywSaxmo4triHohG98GL3E3CPXhy', 'วัชราภรณ์ เนตังวาส', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(331, 'sntn', '$2y$10$N6c6xx.2luYtsY3L4QVn6eZJ.0bKBBkrvLD1dxBN3VmqzMoisHOj6', 'นางสาว ศานันทินี  ทองน้อย', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(332, 'jtms', '$2y$10$S9asmHSAKAyaubU7Tq2aBuWgeT.xGd.wcCcnUbXMCPGUdzGRRDWse', 'จันทิมา  ศรีพลับ', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(333, 'Wachiraporn', '$2y$10$pXNHo1vTmp4wT3r3FoJWruHNq6JSnEPsK2oGGVXqYoKrq6b2YoBjC', 'วชิราภรณ์ แก้วนก', '', '', '', 'staff', '2025-07-30 07:10:23', 1),
(334, 'Pan', '$2y$10$SEACW7iZVeaQbHsUkRM14.lwe5IfYG1dQVVqhIm2Osjyq5IkEyH8y', 'Pan', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(335, 'acry', '$2y$10$EolgDWCdnc1YitaUFwikr.9H2Jy.5PhENembpd.Jk0FSI7fVdEJEa', 'อัจฉรัตน์  ยั่งยืน', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(336, 'Twci', '$2y$10$XWLzutIN2OwOpShr6B.QRe1vjU2B3uxPIK/2K4UjEpw/ebq2sD87u', 'ธวัชชัย พัฒนศักดิ์', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(337, 'urap ', '$2y$10$KBKmIdA9E77D4TBmU5wuU.z1.QRfQbQDsRL5pa3zwFgLYVX5E/dvm', 'อรพินท์ สุขประเสริฐ', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(338, 'rnpk', '$2y$10$VMryABgAUr/4.ZMZihYBdeCpz4W3wmbLlvKzotu0jCiZOontC096C', 'ว่าที่ ร.ต.หญิง รุ่งนภา  คงด้วง', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(339, 'sake', '$2y$10$hQXaQQwS5eB2fsNEXAVNh.ylO4aV15L/OL7i3Q0f8sEZ0zTqUDFbW', 'พิสิฏฐ์  โอมี', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(340, 'Twsk', '$2y$10$qWYOR70ejJNc1svfXNvP1uZq2Zpz0J856D/Ht/MPag0onbfzhXVlm', 'ทวีศักดิ์  อารีกุล', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(341, 'som', '$2y$10$npKXqtOeBx3yQ2jFyVo58OVN/PY2s9BiRCTQ3ge7g9BJQmjjNay.G', 'นางสาวสุณีนาถ หนูศรี', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(342, 'boo', '$2y$10$xIBunQ0GDdtJkr7sX54JVOljpOv7RYoOtdFhosmOqpN8Xvlpafpue', 'นางสาวเกศินี โพธิ์เพชร', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(343, 'mermaid.muayz', '$2y$10$87KY9toI9p00HxHnkXC.iuHYWu.B3rUBsqy7tRBEnzhJh3jWwxOsC', 'นางสาว มณีทรัพย์ ไตรพรชัยกิจ', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(344, 'putd', '$2y$10$ASk/8Ncarru3pmhrT4NHFuL8spIYBlXfggAaIemjGxENJM0kuQxSa', 'พุทธิดา ทองวัฒนกุล', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(345, 'Fatima', '$2y$10$wmKMSOQpnHOn1Sw9O/aB3OmEQSGFoO1wEzU1LYuZ22slUdlrr6.3O', 'ฟาติมา ทองรัตน์', '', '', '', 'staff', '2025-07-30 07:10:24', 1),
(346, 'orasa', '$2y$10$3O8dXhaw3T3beUUonlsMsOyBPpWf9NbV6SoWYRNIGeqNiks55RUHS', 'นางสาวอรสา สุขราช', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(347, 'Malika', '$2y$10$wMPGozT6k74OIPobv7fCT.wXKgT5IQfQQY6IJyC370I5cT58hcGCG', 'นางสาวมัลลิกา เขามีทอง', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(348, 'sasiya', '$2y$10$zMszso5LHPvYof3F7QEgDORR1uDCcQKftOw5h7Dw58PdhcLPnOOi2', 'พญ.ศศิยา ศิริรัตนวรางกูร', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(349, 'nutn', '$2y$10$IQmH0TL8uAC43tLKn9vvcOG.ONcqM8oislT5QfimK6klkUkBcADUW', 'นางสาวณัฏฐ์ชนก นุมาศ', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(350, 'nantawut', '$2y$10$7OmKeQOs/8kxff4KTsOySe2MAbntCDyECnXH2RW4EV.IkOIwf.4qi', 'นายนันทวุฒิ สู่สม', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(351, 'jrsy', '$2y$10$GPjakbmHgOQIpPtisTePFe6M42TiIPnnYBFM4vlyc6Vix.ZPptExG', 'พญ.จิรัศยา ชูอ่องสกุล', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(352, 'sasy', '$2y$10$xumE3OVbFnqKMXRiDrFCgusMHrw3qUsIkYKRRNwkClymsxj9VPN42', 'พญ.ศศิยา ศิริรัตนวรางกูล', '', '', '', 'staff', '2025-07-30 07:10:25', 1);
INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `department`, `position`, `role`, `created_at`, `force_password_change`) VALUES
(353, 'jspa', '$2y$10$gshnSwAYW03FlMVZGwTmCuR1sXGjgtZ2AlrcftbZVDPzkCX7fBH42', 'พญ.จินต์สุภา อภิญญาณกุล', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(354, 'piyw', '$2y$10$n5G.1Jwya3Thtx9kTWAZr.fhcA6z7f4U06XdOS.Ck/I9Qv/eSQ0w.', 'นพ.ปิยะวุฒิ คำทิพย์', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(355, 'pjnd', '$2y$10$HVj.kTTqaeEgdxo5Wm3YveGUI8UmRtoHTNqunae0fhZSHxf6xwpAe', 'พญ.พจนารถ ใจงาม', '', '', '', 'staff', '2025-07-30 07:10:25', 1),
(356, 'ptna', '$2y$10$Uv7ov4avaNg5T.a0IxmSRe6hvzgalAZPtUs/Ib.TXgLvzsbz/XQ46', 'พญ.ปรารถนา นิลพงศ์', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(357, 'msya', '$2y$10$ehYUkpqcGLttnd3gWFhvn.xrYjd6yBNPVYLilhw4cxFGFT4nlNESu', 'พญ.เมษยา แสนเฉย', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(358, 'jnjr', '$2y$10$hfxOZPyYCmOgm9RPLl8k..Nq/B87BVWjKFChl8nFX2kTVoJjIra4S', 'พญ.เจนจิรา สุวรรณวิธี', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(359, 'sakuna', '$2y$10$Pe0X7wzOCNg79YnA.hPRpOIU4.y5CPF57IJl0VU4VHdjmw7v5V.BG', 'น.ส. สกุณา  คงเพชร', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(360, 'lab9', '$2y$10$y5IqqPnZms/46f7HKcMwteGuM9fv3dAyVlZ6LGvtbS2yNvUbyq02S', 'ทนพ.ปิยะวิทย์ ทองตะกุก  (ท.น.22791) (lab9)', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(361, 'pwcyp', '$2y$10$VEYKmDz85NfMrtagpCvZPukL3/MhlfBjug.ELHPQxJGtA6bl4ODgq', 'พิชญาภา  ช่างสลัก', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(362, 'cynp', '$2y$10$lTHoBZztPOPHI/A.tW5lj.6TJNq1rhDhBYvKS48rlYdIJ4Lv3TuJS', 'ชญานี  ปัญญา', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(363, 'sjr', '$2y$10$VwSoIrUCvWRqKNx50Zd7vuMCmPMuboc7F464VUWSV3hTQBzjNKJUS', 'โสจิรัตน์ อินทร์แดง', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(364, 'fard', '$2y$10$GppeJUwK4qpGZirPLeMH8.JK0kWgG9pHU2sQ2ZM7egshl6IdDhc4G', 'ฟาร์ริดาห์ ทองแกมแก้ว', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(365, 'Surd', '$2y$10$a6Ae/QwwH1TEU5yeiIuZhe9EtLsOURAVdsnnPFKY7Bb4aTgpr0bQ2', 'สุฤดี สุภาผล', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(366, 'Sara', '$2y$10$wBFZN2ZTSxuVJ26.BEBb.eoLGNsM46skj6qD1NG4WMGQozT5MSkPS', 'ศรันยู หลางหวอด', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(367, 'teera', '$2y$10$Rtu713SF3wj6pL0tOMHpuuPBX6FPxfGql/lBoQBzoH9UOzw0Z9CJ2', 'ธีระ  บัวแต่ง', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(368, 'jjr', '$2y$10$O8hr9MzcnRIz3PRSVkLhJuc93yZ.RrMsrY3AYe.Vn/jo.dsjJf8mq', 'เจนจิรา เพชรสุวรรณ', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(369, 'nopv', '$2y$10$Y4JoTM.12noJ4crx1fOJ2uX7cCfZQ3ktJuz5DBhJzMktmFwKaUjxm', 'นพวรรณ เพ็ชระ', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(370, 'sowl', '$2y$10$80SSruj0tUlRdQeqQAMs5ekDdIMc7CCZLiPElNXIDiwwHCN7O.0uq', 'เสาวลักษณ์ สุวรรณวิเชียร', '', '', '', 'staff', '2025-07-30 07:10:26', 1),
(371, 'sril', '$2y$10$QTFyQcE29JJ5kFvHAELyvOec.HDaMa7OKGATC8V9Mr98B5MQYiCAG', 'ศิริลักษณ์ สุวรรณวิเชียร', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(372, 'Daranee', '$2y$10$8G7B6wDpq22wY2iL0JY7U.7HQpaxuziI8e63Fv27IMmNBKERNPFHK', 'ดารณี  ประสมรอด', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(373, 'Dear', '$2y$10$.bvHLtCIP/OTJxWLk/dr.OJYuIWhNtJ8Y7PFYDyZoYyYefAOLfkZ2', 'อาทิตยา ฤกษ์อวยพร', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(374, 'Kik', '$2y$10$WIAdQr5pekEOdz2yjuy4luFrSrzsz1dfHCYlPFxdGkGD7yWa50oWW', 'สุมาลี แก้วหยอด', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(375, 'Nad', '$2y$10$CaZMrD/MJ/2WD4keTSc2x.S39B.ugK9EDWp/BfCWXkOtgael4bzze', 'นงค์นาถ คงชนะ', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(376, 'outlab1', '$2y$10$injj4OF98fLxgHUEOkbdauJ/lnIndANE2NaQL3NjJ7aKoAAMhweFa', 'Outlab1', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(377, 'outlab2', '$2y$10$Xr4iv/hyXDYddvDB.PwTmO7cU9bLLRI2OZi.i4BRo/bOB0kfgNCly', 'Outlab2', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(378, 'Mork', '$2y$10$S9WDPGkGhui.GuiU1Uj1BeNNC8FFuZm4/XFvS.u3rDu3TUbFGykg.', 'กษมน ประกอบผล', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(379, 'atittaya', '$2y$10$weZauln/eHF0wgqAPEAOrOYFZXn3r4zJKleSHRsLft/.pfwSTFddq', 'อธิตญา มีเพียร', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(380, 'aphiram', '$2y$10$J.2DOvrSkLNzfEtp.E7GMeq6kxqAI0k.2LsK7y..pWb5pk6W9.n7q', 'อภิราม ตรีสงค์', '', '', '', 'staff', '2025-07-30 07:10:27', 1),
(381, 'ntsk2', '$2y$10$RaYMkot9Sjl0nHVAgp.4U.yElHhYabQj.daQ2CkGkIWeBfla1TdOi', 'แพทย์หญิงนิธิมา ศรีเกตุ(HI)', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(382, 'wypb', '$2y$10$hdpLkdTgeLU.ZGrhs3hS2.nzRPJK9D3lM//QY1XHpy5/iG16oiwBG', 'วิชญาพร  บุญทรัพย์', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(383, 'sswd', '$2y$10$njIiv0m66w3ZIRkiR2sJsegzQm7YccZUH3rZ.sDMETR6NyReazB0.', 'พญ.ศศิวดี  พูลผล', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(384, 'Test1', '$2y$10$QLIRDMP.9tpuH0z8iLgFrOLZWYfAXvagbgXYKNop1DrfWERCgRNXK', 'ทดสอบระบบ ', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(385, 'admin10', '$2y$10$hZBgjPiU6iynSoeg8flHT.ISX3NnwT089xtzcsecPPtiJD0eMXMIy', 'ชัชพล  จันทร์ทอง [ IT ]', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(386, 'spty', '$2y$10$b1EM6kpuZ1otDBzr/WUYM.1RVkP9Uq3ybCpI0C9NuwiVvsRULHybi', 'สุพัตรา ยลธรรม์ธรรม', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(387, 'Praewporn', '$2y$10$hSFNuTJMLE7o33FLhOlMtO40TwqeeTE7/GTJP.ogMP/qetU8ji8jC', 'แพรวพร โชติช่วง', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(388, 'pond', '$2y$10$Brh02b70KYK7Ort5KDOf4.hiuGyxoTZhFl9ayJyWhyh14cboMG1P2', 'กิตติพงษ์ คงศรี', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(389, 'srsk', '$2y$10$HxHgCvcgqL8DoZTNLZ0CJ.1UqMO8ybk5iXLGaTsagLGzpJpba914e', 'ศิรกาญจน์ กองสุข', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(390, 'q', '$2y$10$mj2pHVUERTZDG16lXmEgZO/dXLEg.NDO6l9DKKhvSgZwokv4kTQhe', 'Queue', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(391, 'phanthita', '$2y$10$J.sUaJQnyMgDkfcMP/zel.70rO0vD/dzK3iZAeSGIdoRym1H7slFa', 'นางสาวพัณฑิตา เพ็ชรเจริญ', '', '', '', 'staff', '2025-07-30 07:10:28', 1),
(392, 'h313907', '$2y$10$J5i/GyNCCrHgHBikfqEOEeB7FJhxodCHyb95Rh73VXRuf.oCkvBGW', 'หงนวล มากเกลี้ยง', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(393, 'sch@outlab1', '$2y$10$qldWlIymK77Iv0DolVELEOlmqKGpxBP8WQ/KAcVV9sRSt2CPu2yw6', 'sch@outlab1', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(394, 'sch@outlab2', '$2y$10$W580mGELF2Zn2bKpsmYZA.VYYf6lKgS.V0i9OeIupboEJB.33DYfG', 'sch@outlab2', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(395, 'rjko', '$2y$10$BPkGfkmTnfdzWO3MygW/B.GhjpJCYB.LwNGtbLpkYesX6vpxGv0ra', 'รวงข้าว ทองศรี', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(396, 'stpn', '$2y$10$C51d.j2WaVGxAv8BI9v2ROghWOe2izCWATv3xT4q0bk5oerUZ1Jxq', 'เศรษฐพร', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(397, 'ntpn', '$2y$10$cjnjNx2rt07t2DRSy.vI0ugHKTKjC8Na1792qRXRvQ7sRHUxFZeYK', 'นัทธภรณ์', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(398, 'duangjai', '$2y$10$4rbrYK1FZe6VPUAdj5USI.s5KMTwdetheu1mTBXAUwrt52fZ/IJkS', 'ดวงใจ นิลสดใส', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(399, 'praphan', '$2y$10$sK/njfQ8fS.a6AfYekBrruhCBz4/0nOzMuJiFxlQdcAu0h4YhkJUe', 'ประพันธ์  เลขวัต', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(400, 'sch@outlab3', '$2y$10$nbQ8oP/MHdmXPem2IWCude6YGAAYl3oqDAAEThTinXwYk5l8GUG7S', 'sch@outlab3', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(401, 'mint2536', '$2y$10$kzjOpPc4tbgbtuehkeyX1OTUyGlaNJuJ7lcNby.cOFOOGcKosUW42', 'อรอุมา รักษายศ', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(402, 'nurh', '$2y$10$YA9RVlrDbwZUva0irumbFOvDMsuciL.KbDF94QVVvNKxyxRqWhatm', 'นูรฮายาตี ลาเตะ', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(403, 'swry', '$2y$10$9jtpM99uIYh8Xy6QuKYbE.gsmeBnu0hQ.IGAN0Cmse9pGckkLKk02', 'สุวีรยา ศรีทวี', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(404, 'phda', '$2y$10$Mck2iwDCKPp06G6i388ew.xfQrD8WiPGY95/0rC5vdUgzsuJSJlwu', 'พัชริดา เหลืองอ่อน', '', '', '', 'staff', '2025-07-30 07:10:29', 1),
(405, 'prhd', '$2y$10$LERWwBHTTSgX9xmH9Sf2tujR7CznyvDiRWvQi8fBVaS/el3Aw.kjm', 'ประภาวดี เพชรชู', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(406, 'cmpn', '$2y$10$x1.vmPe4beMse5HzoH2gwOcprhpuAg3zPbAco5eQdhOOdFgaCKsiK', 'ชไมพร พรหมขำ', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(407, 'orpn', '$2y$10$48tXEJOOOCVA.SX2ejA.ZeFNNy2MOfrR4ywvRTWWIwqTFqt6Ua5pm', 'อรพิน จิ้วสิ้ว', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(408, 'kmmn', '$2y$10$6EK3jGLSoeVPDHHHt1yNeuLmxoq3sWTPPsD.cB3My/KyHp08JiqNS', 'กมลมาลย์ ทองปลอด', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(409, 'chnn', '$2y$10$zVXYpWdPobyKU7DxTyWF7.8PKtZObMS7kHLg8Ea2Ss/XtoUf4ok.S', 'ชนกนันทน์ บุรีรักษ์', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(410, 'phongphan', '$2y$10$XeXc3iTYqHYtn1YAwhzoReRer7HqrhIKMM80E1zH2BEEcjvjehJQ.', 'ผ่องพรรณ  มณีน้อย', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(411, 'arisa', '$2y$10$fC3jiao0DhG603hMeRnP1etbb.wIFTdoBdAnPykltJg2GCP46fmOu', 'อริสา   ศุภผุชต์', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(412, 'chalisa', '$2y$10$Oatsjza0gO/ea/wQ1Wzq7.Uu2Zj7NAypEwNPILZ7W81z3u0I6XoK2', 'ชาลิสา เทอดอุดมธรรม', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(413, 'sahp', '$2y$10$iquCSBcZ.ls8x.K3MPymJ.3MhhZh6ZxbzHfGVESN6vOIP8NPKlgBS', 'สายทิพ สมไทย', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(414, 'saic', '$2y$10$5Qo/1pDN/Y0ZCSYXawwfROtxE3xHv5SpvG4fxn4IAPeGU96nzuB3K', 'สายใจ โกละกะ', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(415, 'Sirip', '$2y$10$GyrHoRE94v74E69dsUTxL.sU1Zh7U4TL1oDYOBE0Tc4obL0W67zlq', 'สิริภา วัฒนพฤกษ์', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(416, 'snns', '$2y$10$jeNzbDSok5pb8nactlSCleDF4BOlSf27DP2UEmicQDY0hf6zega7i', 'สุนิสา อินทนะนก', '', '', '', 'staff', '2025-07-30 07:10:30', 1),
(417, 'jeksu', '$2y$10$zTtm3NElANHM1JvL4PDbFOOomfuH.D/6k2HI1aWWtDo2tpVZ1qAbq', 'เจ๊ะซูรอกีเย๊าะ มะแซ', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(418, 'sppn', '$2y$10$kivMNhiLjEcGb5mVAACm6OOr3pKK1IcANbPKPKllFFggiddnPuhpu', 'สุภาภรณ์ ทันยุภัก', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(419, 'testit', '$2y$10$KRZL265F1n8BXxxj/gEt3.I57ajTNg8YqYIw/KBaapRkNATmt9egi', 'test1', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(420, 'clk', '$2y$10$u3OLWH5sTU.TZgvNIkauxO3mH0CoAeOLmib5dvbEtaoJSArGWaKLq', 'เฉลิมขวัญ   หนูศรีแก้ว', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(421, 'anrtp', '$2y$10$xaJJRtkCCv5zBszZHzitXO0cri0FbPtWOi98UHjwoxJsVXxTVa1bO', 'อนุสสรา เอี่ยมสกุล', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(422, 'stdis', '$2y$10$6.S1Lc2Djgsf3s/eb/RrFuiiW.06.y9EgJqg0e52LA8BX3P3xuc1e', 'สุธิดา อินทร์สุทธิ์', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(423, 'wcdbl', '$2y$10$Wfdbqob0wPM1cUp/4t3PI.VQMs56iETytmCfBRHpujYtnFngG4AVa', 'วิชดา บุญเหลือ', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(424, 'nonglux', '$2y$10$BhKOXlhR5CJEU0iBa6iw4OTeqL.ijiBUsp217zTwad7DQjrX.ViEG', 'นงลักษณ์ เดชนะ', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(425, 'nyao', '$2y$10$hOLh/v0iydq3bMv7XWvYEuKgvOApaleyVZxm./BvBqXDy6.81P3ue', 'น.ส.นงเยาว์ ชิลวรรณ์', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(426, 'snr', '$2y$10$BURAq/Fntf/GN2WtiGcFQuQcG.Eu.Msmxc3pWV7wIgOznzervRc7W', 'สุนารี จันทร์แก้ว', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(427, 'ktt', '$2y$10$ARCGnvx8fHsLDUW0uTmlXuo4HzAPnzjHn64iCFwAQ19xfwGNuRg0i', 'คฑาเทพ หมวดเพชร', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(428, 'sns', '$2y$10$VDBgsCCy0iTiO64Xsa65BOuDLwH4Mwk0vyjxsjpZODbXaYT8NdVSm', 'สุนิสา เสนขวัญแก้ว', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(429, 'Phetldw', '$2y$10$FvHpDSMfDlezLtxaxjYmj.ZYkYMdMcr3f1RD0Dzy/GwFu8bQrzjbG', 'ลัดดาวรรณ  เพชรรัตน์', '', '', '', 'staff', '2025-07-30 07:10:31', 1),
(430, 'rsma', '$2y$10$9xTk.Nt5roXfme78lXYRUeGEbHjfTZrsRD/qR5NeODYJK5Bc7PFk6', 'รัตน์สิมา อินทเยาว์', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(431, 'ptd', '$2y$10$gnuKduxo9.9opWkNsGGuh.QQ4xfl4Dvs.5M/Z/ohbAg0CtJYbdkVa', 'ภัทราวดี แซ่ลี', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(432, 'farinda', '$2y$10$LiDttWK.2mpxYI4nn29qseuMyvF47u1YwxeFDPTdeChXY/8jIPozi', 'ฟาริณดา มะแอเคียน', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(433, 'pcn', '$2y$10$5zl7291Wf9VvmO.ekSa9Nu5lbcbHnc6IXO0tf5fgw0fhVK89xBEHa', 'พิมพ์ชนก พุฒทอง', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(434, 'hty', '$2y$10$muBIjbhotNws2r3exx2oAe6.N/ypvw3EGeYzQY9FkLRbHEa9iwALW', 'หัทยา กาฬภักดี', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(435, 'Ket', '$2y$10$tw3aOQ4P.9/kGTkFTfXB9.d4GTYnKMZIxU9T7tYJfmCO/aZDsj4lm', 'เกษรารัตน์ นวลเศษ', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(436, 'jrpy', '$2y$10$mehZTEWp0852bBGCxl6wtepPqS5ZZ8F2E8qq64Fs3b6U38MniSYe2', 'จิราภรณ์ แย้มกลัด', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(437, 'kat', '$2y$10$L0PD1t6EnrtnMRmliZDO8.wE68wQoiiQLghBSfPMPtXf0IZlOdNM.', 'ชิดชนก  วิสุทธิวัชรกุล', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(438, 'apinat', '$2y$10$GVme59DJfVE5Vsq6hJTAmuft06GNANmx1yV6evwIZiqj.nVsM05Uy', 'อภิณัฐ คล้ายสถิตย์', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(439, 'slp', '$2y$10$.yHaKxUre4HnuNSpCd6wf.qd2Q8j1JNsEhCSjfpbxGKtA8ABLADAS', 'สุลีภรณ์ รักบำรุง', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(440, 'rpss', '$2y$10$AMkk.PfJxw.NvUyLX803k.tyQlpWlPQRrz6mekJQ1ctmZwzrfbHJu', 'แพทย์หญิงรวมพร ศรีสวัสดิ์', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(441, 'pank', '$2y$10$XPK3S.UUR9KI8GazsEeKBOxoIL/nVOFbZJA9NrCfiIwAYS/t.fKcy', 'นพ.ป่าน ขัมภลิขิต', '', '', '', 'staff', '2025-07-30 07:10:32', 1),
(442, 'supasuta', '$2y$10$/e1h/vjGvHXkYQPYg9jNE.euD2V3JNZHBOEjmlMWX2CLYb7VFf9le', 'แพทย์หญิงศุภสุตา มั่นสัมฤทธิ์', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(443, 'chn', '$2y$10$nVZFC6/6MIGHkF4eAvUhl.uZOQRxGtGDXSkfyGb9SoUG07z44o91i', 'ฌอชนา วิเชียร', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(444, 'pkp', '$2y$10$86UuqjJ5FX4MwOlpsB58q.Xj1L8w/IxwYbfFBKOyXDczwEvV7NW.a', 'ภคพล วัฒนธรรม', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(445, 'PtrpB', '$2y$10$yIPgifZ4kMEXfrVqjFSKGuZMNRLg.n3euXVowoWlA7PHnrvwJabuG', 'นส.ภัทรภร บุญรอด', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(446, 'JtmJ', '$2y$10$cq1qcv4QdYdYQrsE.q2DBuZh68.NSREpR6D.bRZ6RW9xvOnKZ9Ype', 'นส.จุฑามาศ เจริญขุน', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(447, 'arrt', '$2y$10$PUFdgp49IC5buDgaPVU//.MxouckhTCqg33fXKbTpwU13pU8ySt2e', 'อารีย์รัตน์ อ่อนคำ', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(448, 'angt', '$2y$10$D0zO2LPxLcPQjypKPcdRC.H0q75bUOLXyevkk28m8LJBg8VbbCOTq', 'อังธิมา สงสุข', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(449, 'ctyn', '$2y$10$Fq3B1VA55Ttrzf5JGE8MkucJnc87dVlWOrRgT9rZya8Af.P1.7zea', 'ชัชญานันท์ ผุดเผือก', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(450, 'swnn', '$2y$10$fZX8H615ce9SQoTgMrQN1eVhWyQBycUnsgbL4v13EoOhmvZFqcC/6', 'สุวนันท์ ทิศลูน', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(451, 'Oil21279', '$2y$10$tBICBiEVyX3q./HsPRTKwe9FIVvdZ4yTLr7L.8XOQcs9TXj7l2euG', 'จินตนา เรืองแก้ว', '', '', '', 'staff', '2025-07-30 07:10:33', 1),
(452, 'rchr', '$2y$10$hkO3IhMEvexQ0EzB4cBmnu9srFadky5C5CG1DlMe3eit6706a6lNu', 'รัชรัฐ บุญสุขขัง', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(453, 'Jiraphat', '$2y$10$0YUsMEo16oFnF.haygLCMetEglQpYY3gp2lzsBiH/BGUuJnWdUgci', 'น.ส. จิราพัชร  นาคคง', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(454, 'swak', '$2y$10$Q89iSffE5GTqBti/VzmCKePOD6wXphqN7cFyf7BqccWM0TfRinKhq', 'เสาวลักษณ์ ชาญพล', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(455, 'mtvc', '$2y$10$WENknduxOHfFsHZipkgG8u7yQPk/9tBax.ddzEVeZC5HHSEeDNfI2', 'มัทวัน ศรีมาลานนท์', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(456, 'dsrp', '$2y$10$iXzLLulYc8eyBKlwrUYrZeoCbnA9C2CgI3Kq3jx3a7HCIOZgED9zy', 'ดิศราพร ยอดโมรา', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(457, 'srwn', '$2y$10$fj5Gq6lYylAN2cexpXLA3ezx8D9H5LmVmCFioXfOvRRoC164mNkF2', 'สิริวรรณ เจริญรูป', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(458, 'user14203', '$2y$10$.8tACe/h4Y8YAEdCJ04o8OYCPwbmf4SvXY194Qly6tPgswkBrFngu', 'Admin14203', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(459, 'Benjawan', '$2y$10$zHsOqp/3phoIrRSbJWSwDu3.I0dT0iJpZXMYR/FhLrJw6fKYgvAba', 'น.ส.เบญจวรรณ หวานฉ่ำ', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(460, 'ntm', '$2y$10$GqGISPx/fOcN9KKpisBfY.5O0DtXyOJHFibF/N04LtceiUPsgnpTG', 'ณัฐมล ศักดิ์จันทร์', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(461, 'Sopita01', '$2y$10$0W0T4O6cnjolNzyZ1u5gSuq2fdEQNsq8KtjwczVZyIVnZJU7ZEJr2', 'โสภิตา วังบุญคง', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(462, 'Tass', '$2y$10$iZ7iZ0addRMumkixAPSK8u.Qufgb6juEx8PfELNEpGNYYUFcLYG9O', 'นพ.ธรรศ ตู้บรรเทิง', '', '', '', 'staff', '2025-07-30 07:10:34', 1),
(463, 'Kritsana', '$2y$10$9JluCE9NEvDG/HiLF3.AmugKrC7Uo2doBZC1LFC9SjKf9WywN8QrC', 'กฤษณา รักตรง', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(464, 'ing', '$2y$10$cHg17QqgLPkfjyRgf4bBuekuGCq7RHt3HAKQLq12bY3ssDciVVkYu', 'นางสาวธนภรณ์ ชูสิทธิ์', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(465, 'ldw', '$2y$10$dfCYg69yhELAxjWvVWw.mumIivIQ4jyHhPY/ALTN1ywSv9nlIwQeq', 'ลดาวัลย์ เพ็ชรสุข', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(466, 'admin 147', '$2y$10$.qucpM4YSrzFTVD3l.JcUeURzsTFA/jA8/u6Mch2BP.HRLPsvjxrq', 'admin147', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(467, 'pns', '$2y$10$9gi3eFUZKmucmkVgffRE5OsmzBLizssELwtfTf26icgilRLHJ1/P.', 'ปาณิสรา ', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(468, 'pnp', '$2y$10$pcmMj3UZz8RPE5XMMmtAwOhihlYK8i4Fqfs9vK8dtIXz6WFNrsFme', 'ปุณณพร', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(469, 'walk', '$2y$10$TyHiToRzzPLTuclxwTiqWe7vh8M0vDHyPgYZPVUbLVkAkSdeqc9se', 'วัลย์ลิกา ปราบปัญจะ', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(470, 'bbr', '$2y$10$0NSJM5uVRcg5T5XaIcJs8e6hksQ9FDv7IrF/ZwCbU9G2TTZUpT1za', 'เบญจรัตน์  จิรานันท์', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(471, 'pnk', '$2y$10$7rGiQx6/.K9PBIKkl0stRuutIBJeitEuVAHGu4RBhFiY70EIorXHq', 'นพ.พนากร วรรณวงศ์', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(472, 'Purikorn', '$2y$10$98Uj.c9pTA4sBZOoQyC.TuL/H5SBvoGUpsjG0pHi3efcoewc/Qdle', 'นายแพทย์ภูริกร พรหมมณี', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(473, 'rttw', '$2y$10$Yi8n8MvH0WID/lsKZJLnzuLq7LBpEHMpEDm4HnNYqYbSQ8aJDsDSa', 'นายแพทย์รัฏฐวัส  วิชัยธวัช', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(474, 'prowprow21', '$2y$10$urqt.QTnp3jWfmJmd1c/Suevjh95CC.IqvkHb6/eKfpjE9.oKX7vW', 'ปราถนา รักษายศ', '', '', '', 'staff', '2025-07-30 07:10:35', 1),
(475, 'Kls', '$2y$10$X7YPlIk9.cHbELO3GHdMMu4WO614MmY6y0mpw4hrnLJJuYeH7ORXq', 'ขวัญลิษา บุญมี', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(476, 'mtrp', '$2y$10$NzzG2KDshXcLYh8GRDtOLOuc0VUHfryBszjdzLspWyXb86tkpK.ee', 'มลธิรา ปรางทอง', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(477, 'ktd', '$2y$10$zoGl5sNjf8w0xK4AmB7byuF56nCeyLqO5dhvxH2.tPvGeRhzOaJpu', 'กานต์ธิดา สอนประสม', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(478, 'tjsw', '$2y$10$tMavdxS8fvnaMq2jx2AuH.ET9tSif07qsio8TnjetRTUOuaUiq4Le', 'นพ.เทพ  จู่สุวรรณ์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(479, 'src', '$2y$10$jVvp93zwhxETBST2DkQJD.Md/J6vD4WGZddvHRlBVUl65CdsTT75G', 'สโรชา คงทรัพย์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(480, 'know', '$2y$10$EQEp.if/hZqbnnqQaRpsJ.bDys5N4R8LEWyUZQMn4/4uUCS3tHEUW', 'กนกวรรณ กรดจำนงค์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(481, 'jtmm', '$2y$10$mAG3V6alurjHXz22ELlNzu.zrWUH/DJeB6cOCTGYQgRbmSlL75Uy.', 'จุฑามาศ ยอดสุรางค์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(482, 'rtnp', '$2y$10$Atqe2ec6KYwc5KjvZtPiZeiUslbvUfY7.D28UM/tmt.nSsy7WT36u', 'รัตนภรณ์ พรหมรุ่ง', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(483, 'sym', '$2y$10$9qBPZChzL1Q1eAMWFc9A7u4BtEcmnxtLvh5WOhoUwYtqVA3iD44S2', 'ศยามล ลิ่มมณี', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(484, 'admin149', '$2y$10$vtiEcF3goDyZjAJGbPLJ0ump4Js4MuzaVnaphoViKhxqpEo5txTzW', 'admin149', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(485, 'arpt', '$2y$10$nuX1TM8DCOo/PIaVP0LHDOgt1e8noYCJ67kSn6ZLkK8xlQRCRhk5q', 'นางสาวอัจฉราภรณ์ ธนาคุณ', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(486, 'stsn', '$2y$10$E9YsVLy6Y4bJnEvyW/AVqeZfWcd/0DVpDJy2Dcxb0nkf7CkhKI6B2', 'สุธาศิณี ชัยสวัสดิ์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(487, 'mrk', '$2y$10$uk0Q5t9FAPeuhuTxhONZzOYvGT7S0SiRFXcobWIkLN1Mz77FDJ.l6', 'มุกริกา จันทร์หอม', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(488, 'Boonsiri02', '$2y$10$ZBfu7K.CmUTV/LhtkRhvC.KdyvGDphwoFRsHlYi1mL9aduLceDd.u', 'บุญศิริ วงศ์ประดิษฐ์', '', '', '', 'staff', '2025-07-30 07:10:36', 1),
(489, 'Prai', '$2y$10$kb.9ScssIWQS3X8HFXnDTuV.O0Ttr4AwxlOnxf5K8eWz/hi0V43Cy', 'ปรายขวัญ จันทร์พุ่ม', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(490, 'jupn', '$2y$10$F55aPKPL.9anb9x20yI85.q4fvWqdNp5bZ7K.qqyYh2tpn03MemuK', 'จุฑาภรณ์ เกลี้ยงกลม', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(491, 'nrar', '$2y$10$U0qXV3vbzTLSt28DqnPu2.8D1eH.X2cZx3kktt4uRtHrPVJPUoSji', 'เนรัญชรา แก้ววัดปริง', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(492, 'jtmm1', '$2y$10$tdf/uRZKxuQD4Ej/XIyf.uYVrCE11Q4eUIHhFNhrt/MmvClmGcsfW', 'จุฑามาศ จิตรา', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(493, 'pnnp', '$2y$10$WqUZsLETo1e8fqd7KaKaK.iMKVwZFmC8cfChWGZLHyhzOo3sBBVzy', 'พรนภัส วิจะสิกะ', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(494, 'sdrt', '$2y$10$DHikxIVBQG2lOkTOXlOrF.DeTd2HAS.7tY7Aa7vQGyd1CxqVCMg5i', 'สุดารัตน์ ขวัญชุม', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(495, 'Palita', '$2y$10$XUOxXWxo5aQ4hiS4oEwx1uYMY5DXSV4L6Awi6a6ZL4aFA6Aexphmi', 'ปาลิตา ประทุม', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(496, 'Natchanon', '$2y$10$q6R59EkqsGYXW4osCeNNqOVyZr2gQ3bVsDFdT3BaoA/jGjwLuvFxe', 'ณัฐชนน สําลี', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(497, 'kanda', '$2y$10$ePzTtJfySdDbCiMrblwKr.hVJZ4/ukSpH.mtsQ1RroZdCYfXsvRz.', 'น.ส.กานดา  สันติมิตร', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(498, 'jttr', '$2y$10$c4W3lqpbMEoIQcAbl3xllubqqQHSiG1zaUsElAV/XYSsE.S4YBnOe', 'จิตรา แสงบุญ', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(499, 'Rmdn', '$2y$10$q393tMQyEHPd5iRz7b22F.k5IYYZcL9R8vpUiRcfwJQVN0aOx8LlK', 'รมิดา น่วมสุวรรณ', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(500, 'Ncpr', '$2y$10$iwgQ16N3tQiHxtk7cyhYROF.rEuwCFKp6eTbgm.3Sk0B2v5RNr6AO', 'นิชาภา ร่มโพธิ์ทอง', '', '', '', 'staff', '2025-07-30 07:10:37', 1),
(501, 'Pynr', '$2y$10$h9lBHeYg.n.nXoruMJhjoOr4KL2r7wDsngLezB.ddjmjFXTOMLNeq', 'ปรียานุช ราชหวัง', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(502, 'wann', '$2y$10$kiVcjvDmyUArClp9pg1o4eetg9I98jMz7fCt1d6olULCbjuE/ZwM2', 'วรรณา ขาวทอง', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(503, 'srwnn', '$2y$10$QD2ZhxkgJlYGhTeY0L6abe0Gj71uhrK3l1kQ9EoZah6NeqaKZO9IO', 'สุรีวัล อนุกูล', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(504, 'Nrngl', '$2y$10$i6apR.SrDZ4EMKpYNt9gRupw0o0iuPbN8k8BdgC4UWmd6Lb98.nxq', 'ณรงค์ ลักษมาณา', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(505, 'jrw', '$2y$10$kMBxN7Hm5kwTAjTzgymjx.s3aR6jmi0ZGsu8SnxpOwHHazDyBS0GS', 'จารุวรรณ ไตรระเบียบ', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(506, 'wpw', '$2y$10$s2L9Wvof0jkZYtlT2V.DtecVBMtTRI3VirSyS63DDoTJXNSidk3ka', 'วิภาวี ขำคม', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(507, 'PonchayaP', '$2y$10$Byq7Vz.EPM3gWEEBRTahxeINoGMjnMIS7NSkCnhyk64nXFkG7tPTG', 'พรชญา ภิญโญ', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(508, 'KanyaphatP', '$2y$10$6pgCWH0e9ZmoOh07nbQhpOYm2RyWbUo84rlb/3SE8y/tncX3gWGAa', 'กัญญาภัทร พรหมน้อย', '', '', '', 'staff', '2025-07-30 07:10:38', 1),
(509, 'staff22', '$2y$10$dMBimQoyE2B.LQ9JXHzaTucdWTU/RI6R1DcwEs.bVbJ.jezexzPfa', 'ขิน', 'Fghkfjvk@gmail.com', 'งานวิจัยและพัฒนาการพยาบาล', '-', 'staff', '2025-08-05 03:15:03', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `event_detail` text DEFAULT NULL,
  `event_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `username`, `event_type`, `event_detail`, `event_time`) VALUES
(46, 2, 'admin', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:46:38'),
(47, 2, 'admin', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:48:43'),
(48, 2, 'admin', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:49:13'),
(49, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 08:50:10'),
(50, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 08:50:14'),
(51, 2, 'admin', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 08:50:49'),
(52, 3, 'admin1', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 08:54:17'),
(53, 14, 'admin4', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:55:14'),
(54, 2, 'admin', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:55:26'),
(55, 2, 'admin', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 08:55:32'),
(56, 14, 'admin4', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:56:16'),
(57, 14, 'admin4', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:56:20'),
(58, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 08:56:46'),
(59, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 08:56:52'),
(60, 3, 'admin1', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 08:59:32'),
(61, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:01:16'),
(62, 3, 'admin1', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 09:03:14'),
(63, 3, 'admin1', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:06:01'),
(64, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:06:04'),
(65, 3, 'admin1', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:06:16'),
(66, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:07:41'),
(67, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:08:13'),
(68, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:09:48'),
(69, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:09:57'),
(70, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:10:00'),
(71, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 09:10:08'),
(72, 3, 'ชินกร ทองสอาด', 'logout_success', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:11:16'),
(73, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:11:22'),
(74, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:11:25'),
(75, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 09:11:37'),
(76, 3, 'ชินกร ทองสอาด', 'logout_success', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:11:39'),
(77, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:13:29'),
(78, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 09:13:32'),
(79, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 09:13:53'),
(80, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 09:13:56'),
(81, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 09:14:13'),
(82, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-21 09:14:15'),
(83, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:10:11'),
(84, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:11:45'),
(85, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:18:56'),
(86, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:19:05'),
(87, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:20:09'),
(88, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:20:12'),
(89, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:21:25'),
(90, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:21:28'),
(91, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:22:29'),
(92, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:22:31'),
(93, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:22:42'),
(94, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:22:48'),
(95, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 10:30:13'),
(96, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 10:34:35'),
(97, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 10:35:25'),
(98, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-21 10:35:39'),
(99, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:35:48'),
(100, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:44:50'),
(101, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:58:53'),
(102, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 10:59:02'),
(103, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 11:00:26'),
(104, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 11:00:51'),
(105, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 11:02:24'),
(106, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 11:02:28'),
(107, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 11:02:37'),
(108, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 12:44:32'),
(109, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:07:04'),
(110, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:07:07'),
(111, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:09:11'),
(112, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:10:11'),
(113, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:10:19'),
(114, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:11:34'),
(115, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:11:41'),
(116, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:13:39'),
(117, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:16:48'),
(118, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-21 14:29:20'),
(119, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:29:26'),
(120, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:29:29'),
(121, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 14:29:35'),
(122, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-21 14:40:41'),
(123, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 14:40:45'),
(124, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-21 14:41:27'),
(125, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 14:41:35'),
(126, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-21 14:42:55'),
(127, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-21 14:43:05'),
(128, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-21 14:43:31'),
(129, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-21 14:43:41'),
(130, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 08:21:26'),
(131, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-22 13:59:48'),
(132, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-22 14:00:01'),
(133, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 14:00:55'),
(134, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 14:08:31'),
(135, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 14:10:16'),
(136, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-22 14:10:39'),
(137, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-22 14:10:53'),
(138, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-22 14:10:58'),
(139, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-22 14:14:26'),
(140, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 14:33:51'),
(141, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-22 14:34:04'),
(142, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-22 14:34:09'),
(143, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-22 14:34:14'),
(144, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:34:32'),
(145, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-23 13:41:32'),
(146, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:41:55'),
(147, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:42:08'),
(148, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:42:10'),
(149, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:42:23'),
(150, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:42:47'),
(151, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 13:43:30'),
(152, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 14:05:58'),
(153, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 14:06:32'),
(154, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-23 14:06:44'),
(155, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:34:27'),
(156, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:40:09'),
(157, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:40:21'),
(158, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:41:04'),
(159, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:42:37'),
(160, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:42:45'),
(161, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:45:31'),
(162, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:45:42'),
(163, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:45:55'),
(164, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:46:04'),
(165, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:46:25'),
(166, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:46:34'),
(167, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:53:19'),
(168, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:56:22'),
(169, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 09:56:36'),
(170, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-24 10:18:44'),
(171, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 10:20:32'),
(172, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 10:20:49'),
(173, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-24 10:21:04'),
(174, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 10:21:09'),
(175, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 12:58:30'),
(176, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 12:58:37'),
(177, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:11:54'),
(178, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:12:07'),
(179, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:13:30'),
(180, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:13:36'),
(181, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:19:06'),
(182, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:19:21'),
(183, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:47:15'),
(184, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:47:30'),
(185, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:47:41'),
(186, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-24 13:47:51'),
(187, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-24 13:48:32'),
(188, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 13:48:41'),
(189, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-24 15:53:54'),
(190, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-24 15:54:01'),
(191, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-24 15:54:21'),
(192, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 08:30:02'),
(193, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 09:22:38'),
(194, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 09:23:36'),
(195, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:23:41'),
(196, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:23:58'),
(197, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 09:24:02'),
(198, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 09:24:25'),
(199, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 09:24:35'),
(200, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 09:26:38'),
(201, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:26:48'),
(202, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:26:51'),
(203, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:27:01'),
(204, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:37:26'),
(205, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:37:32'),
(206, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:37:35'),
(207, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:37:46'),
(208, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:40:39'),
(209, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:41:02'),
(210, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:42:08'),
(211, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:42:18'),
(212, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 09:52:05'),
(213, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:52:09'),
(214, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:53:36'),
(215, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 09:53:40'),
(216, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 10:48:31'),
(217, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 10:48:37'),
(218, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 10:49:02'),
(219, 3, 'admin1', 'edit_user', 'แก้ไขผู้ใช้: ธนโชตฺ คงไม่ดี (staff)', '2025-07-25 10:49:24'),
(220, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 10:49:37'),
(221, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 10:55:39'),
(222, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 10:55:43'),
(223, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 10:59:17'),
(224, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-25 10:59:24'),
(225, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 10:59:53'),
(226, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:00:49'),
(227, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:01:00'),
(228, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:01:21'),
(229, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:01:33'),
(230, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:01:36'),
(231, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:02:00'),
(232, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:02:12'),
(233, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:03:02'),
(234, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:03:07'),
(235, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:05:27'),
(236, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:05:42'),
(237, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:08:28'),
(238, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:08:39'),
(239, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:12:38'),
(240, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:12:47'),
(241, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:12:49'),
(242, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-25 11:13:00'),
(243, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:14:51'),
(244, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:14:58'),
(245, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:18:20'),
(246, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:18:30'),
(247, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:18:59'),
(248, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-25 11:19:05'),
(249, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-30 08:55:41'),
(250, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-30 08:58:12'),
(251, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 08:58:18'),
(252, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:27:10'),
(253, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 09:28:22'),
(254, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 09:34:43'),
(255, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 09:34:55'),
(256, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 09:35:59'),
(257, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:39:20'),
(258, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:40:54'),
(259, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 09:40:58'),
(260, 16, 'ฟ้าใส ภัก', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:42:51'),
(261, 16, 'ฟ้าใส ภัก', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:43:31'),
(262, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:44:06'),
(263, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 09:44:22'),
(264, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 09:44:32'),
(265, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 10:28:54'),
(266, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:29:02'),
(267, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:35:22'),
(268, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:35:32'),
(269, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:37:55'),
(270, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:38:37'),
(271, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 10:40:30'),
(272, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:05:24'),
(273, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:07:22'),
(274, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:07:29'),
(275, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:11:11'),
(276, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 11:11:45'),
(277, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 11:12:35'),
(278, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:13:08'),
(279, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 11:14:06'),
(280, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 11:38:44'),
(281, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 11:38:50'),
(282, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 12:02:35'),
(283, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 12:02:45'),
(284, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 12:02:59'),
(285, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-30 12:03:03'),
(286, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-30 12:03:36'),
(287, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 12:03:44'),
(288, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 14:14:06'),
(289, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 14:18:17'),
(290, 334, 'Pan', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:18:27'),
(291, 334, 'Pan', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:19:08'),
(292, 474, 'ปราถนา รักษายศ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:19:24'),
(293, 474, 'ปราถนา รักษายศ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:19:28'),
(294, 474, 'ปราถนา รักษายศ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:19:39'),
(295, 474, 'ปราถนา รักษายศ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:19:43'),
(296, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-30 14:19:48'),
(297, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-30 14:20:55'),
(298, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:21:10'),
(299, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:23:31'),
(300, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:23:47'),
(301, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:28:08'),
(302, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:28:20'),
(303, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:32:31'),
(304, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:32:36'),
(305, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:34:00'),
(306, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:34:02'),
(307, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-30 14:34:09'),
(308, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 14:34:11'),
(309, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-30 14:36:47'),
(310, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:36:51'),
(311, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 14:47:52'),
(312, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:47:55'),
(313, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 14:48:09'),
(314, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-30 15:33:23'),
(315, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 15:40:43'),
(316, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 15:52:25'),
(317, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 16:01:51'),
(318, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-30 16:03:24'),
(319, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 08:26:06'),
(320, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 08:32:48'),
(321, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:04:00'),
(322, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:04:55'),
(323, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:06:19'),
(324, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:18:20'),
(325, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:18:47'),
(326, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:18:53'),
(327, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:19:01'),
(328, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:20:10'),
(329, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-31 09:20:22'),
(330, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:21:51'),
(331, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:22:02'),
(332, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-07-31 09:30:46'),
(333, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:31:14'),
(334, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:37:25'),
(335, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:38:57'),
(336, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:38:59'),
(337, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:39:10'),
(338, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:40:58'),
(339, 19, 'Account Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:41:12'),
(340, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:42:53'),
(341, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:43:18'),
(342, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:43:22'),
(343, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:43:32'),
(344, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:43:50'),
(345, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:44:43'),
(346, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:46:49'),
(347, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:47:34'),
(348, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:51:28'),
(349, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:52:02'),
(350, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:52:06'),
(351, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:54:35'),
(352, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 09:56:21'),
(353, 1, 'ธนโชตฺ คงไม่ดี', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:57:30'),
(354, 1, 'ธนโชตฺ คงไม่ดี', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 09:59:11'),
(355, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-07-31 09:59:23'),
(356, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-07-31 09:59:33'),
(357, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:01:42'),
(358, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 10:03:04'),
(359, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 10:03:09'),
(360, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:05:48'),
(361, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:05:56'),
(362, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:08:38'),
(363, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:08:43'),
(364, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:10:00'),
(365, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:10:37'),
(366, 15, 'ยุวดี รักงาม', 'เปลี่ยนรหัสผ่าน', 'เปลี่ยนรหัสผ่านสำเร็จ (บังคับเปลี่ยนครั้งแรก)', '2025-07-31 10:11:01'),
(367, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:11:05'),
(368, 15, 'ยุวดี รักงาม', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:11:13'),
(369, 15, 'ยุวดี รักงาม', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:11:15'),
(370, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 10:13:21'),
(371, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:19:23'),
(372, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 10:21:04'),
(373, 18, 'Permission Manager', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:21:14'),
(374, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-07-31 10:22:06'),
(375, 18, 'Permission Manager', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:24:53'),
(376, 2, 'ซากีหนะต์ ปรังเจะ', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-07-31 10:25:14'),
(377, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 10:44:40'),
(378, 4, 'ยศกร มาก่อน', 'เปลี่ยนรหัสผ่าน', 'เปลี่ยนรหัสผ่านสำเร็จ (บังคับเปลี่ยนครั้งแรก)', '2025-07-31 10:44:54'),
(379, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 11:14:18'),
(380, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 11:14:22'),
(381, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 11:53:09'),
(382, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 11:53:19'),
(383, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 15:24:37'),
(384, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 15:24:55'),
(385, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 15:25:26'),
(386, 4, 'ยศกร มาก่อน', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 15:43:48'),
(387, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-07-31 15:43:52'),
(388, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 10:30:34'),
(389, 4, 'ยศกร มาก่อน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 10:31:39'),
(390, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 10:42:17'),
(391, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 11:43:58'),
(392, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 11:44:09'),
(393, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-01 15:24:29'),
(394, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-01 15:27:04'),
(395, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-08-01 15:27:17'),
(396, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-08-01 15:27:42'),
(397, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-08-01 15:27:56'),
(398, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-04 13:18:00'),
(399, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-04 13:27:01'),
(400, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 08:51:54'),
(401, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 08:59:55'),
(402, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบบันทึกฐานข้อมูลพนักงาน สำเร็จ', '2025-08-05 09:08:07'),
(403, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบฐานข้อมูลพนักงาน สำเร็จ', '2025-08-05 09:09:26'),
(404, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-08-05 09:09:33'),
(405, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-08-05 09:09:55'),
(406, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 09:10:01'),
(407, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 09:20:02'),
(408, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 09:57:31'),
(409, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 09:59:39'),
(410, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 10:02:33'),
(411, 3, 'ชินกร ทองสอาด', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 10:02:40'),
(412, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 10:10:21'),
(413, 3, 'ชินกร ทองสอาด', 'login_success', 'เข้าสู่ระบบ admin control สำเร็จ', '2025-08-05 10:10:29'),
(414, 3, 'ชินกร ทองสอาด', 'ออกจากระบบ', 'ออกจากระบบ admin control สำเร็จ', '2025-08-05 10:13:21'),
(415, 509, 'ขิน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 10:15:08'),
(416, 509, 'ขิน', 'ออกจากระบบ', 'ออกจากระบบระบบบันทึกคลังครุภัณฑ์ สำเร็จ', '2025-08-05 10:18:24'),
(417, 509, 'ขิน', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 10:18:29'),
(418, 509, 'ขิน', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 10:44:54'),
(419, 2, 'ซากีหนะต์ ปรังเจะ', 'เข้าสู่ระบบ', 'เข้าสู่ระบบระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 13:40:27'),
(420, 2, 'ซากีหนะต์ ปรังเจะ', 'ออกจากระบบ', 'ออกจากระบบแจ้งซ่อมครุภัณฑ์ สำเร็จ', '2025-08-05 14:35:31');

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
-- Indexes for table `discord_logs`
--
ALTER TABLE `discord_logs`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `models`
--
ALTER TABLE `models`
  ADD PRIMARY KEY (`model_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `office_supplies`
--
ALTER TABLE `office_supplies`
  ADD PRIMARY KEY (`supply_id`);

--
-- Indexes for table `repairs`
--
ALTER TABLE `repairs`
  ADD PRIMARY KEY (`repair_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `repair_logs`
--
ALTER TABLE `repair_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `repair_id` (`repair_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `borrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `discord_logs`
--
ALTER TABLE `discord_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `dispensations`
--
ALTER TABLE `dispensations`
  MODIFY `dispense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `equipment_movements`
--
ALTER TABLE `equipment_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `model_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `office_supplies`
--
ALTER TABLE `office_supplies`
  MODIFY `supply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `repairs`
--
ALTER TABLE `repairs`
  MODIFY `repair_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `repair_logs`
--
ALTER TABLE `repair_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=510;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=421;

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

--
-- Constraints for table `models`
--
ALTER TABLE `models`
  ADD CONSTRAINT `models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `repairs`
--
ALTER TABLE `repairs`
  ADD CONSTRAINT `repairs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  ADD CONSTRAINT `repairs_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `repairs_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `repair_logs`
--
ALTER TABLE `repair_logs`
  ADD CONSTRAINT `repair_logs_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`repair_id`),
  ADD CONSTRAINT `repair_logs_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
