-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2026 at 02:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evac_plus_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อประกาศ',
  `content` text NOT NULL COMMENT 'เนื้อหารายละเอียด',
  `type` enum('info','warning','danger','success') NOT NULL DEFAULT 'info' COMMENT 'ระดับความสำคัญ (ทั่วไป, เตือน, วิกฤต, สำเร็จ)',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `target_shelter_id` int(11) DEFAULT NULL COMMENT 'เป้าหมายศูนย์พักพิง (NULL = ประกาศทั่วทุกศูนย์)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการแสดงผล (1=แสดง, 0=ซ่อน)',
  `created_by` int(11) NOT NULL COMMENT 'ID ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `type`, `status`, `target_shelter_id`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'ทดสอบ', 'ทดสอบ..................', 'info', 'Active', 0, 1, 0, '2025-12-31 08:46:14', '2025-12-31 08:46:14');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_attachments`
--

CREATE TABLE `announcement_attachments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `file_type` enum('image','file','link') NOT NULL COMMENT 'ประเภท: รูป, ไฟล์, ลิ้งค์',
  `file_path` text DEFAULT NULL COMMENT 'ที่อยู่ไฟล์ หรือ URL',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์ที่แสดง',
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_attachments`
--

INSERT INTO `announcement_attachments` (`id`, `announcement_id`, `file_type`, `file_path`, `file_name`, `uploaded_at`) VALUES
(5, 3, 'image', '6954e2d625b26_1767170774.jpg', 'กีฬา อบจ. (2).jpg', '2025-12-31 15:46:14'),
(6, 3, 'image', '6954e2d626a2d_1767170774.jpg', 'กีฬา อบจ. (1).jpg', '2025-12-31 15:46:14'),
(7, 3, 'image', '6954e2d627b0d_1767170774.jpg', 'กีฬา อบจ..jpg', '2025-12-31 15:46:14');

-- --------------------------------------------------------

--
-- Table structure for table `caretakers`
--

CREATE TABLE `caretakers` (
  `id` int(11) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `shelter_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `distribution`
--

CREATE TABLE `distribution` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL COMMENT 'ID ผู้อพยพ',
  `item_id` int(11) NOT NULL COMMENT 'ID สินค้าในคลัง',
  `quantity` int(11) NOT NULL COMMENT 'จำนวนที่แจก',
  `distributed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่เวลาที่แจก',
  `note` varchar(255) DEFAULT NULL COMMENT 'หมายเหตุ',
  `distributed_by` int(11) DEFAULT NULL COMMENT 'ID เจ้าหน้าที่ผู้บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `distribution`
--

INSERT INTO `distribution` (`id`, `evacuee_id`, `item_id`, `quantity`, `distributed_at`, `note`, `distributed_by`) VALUES
(1, 0, 7, 1, '2025-12-26 09:35:30', 'อนุมัติจากคำร้องเลขที่ #1 (ศูนย์: วัดเจียงอีศรีมงคลวราราม)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `distributions`
--

CREATE TABLE `distributions` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL COMMENT 'รหัสผู้อพยพ',
  `item_id` int(11) NOT NULL COMMENT 'รหัสพัสดุในคลัง',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'จำนวนที่แจก',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recipient_type` enum('evacuee','general') DEFAULT 'evacuee',
  `recipient_group` varchar(255) DEFAULT NULL,
  `proxy_receiver` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributions`
--

INSERT INTO `distributions` (`id`, `evacuee_id`, `item_id`, `quantity`, `created_at`, `recipient_type`, `recipient_group`, `proxy_receiver`, `note`, `created_by`) VALUES
(1, 1, 13, 1, '2025-12-27 16:04:20', 'evacuee', NULL, NULL, '', 2),
(2, 1, 14, 1, '2025-12-27 16:04:20', 'evacuee', NULL, NULL, '', 2),
(3, 1, 13, 1, '2025-12-27 16:08:17', 'evacuee', NULL, NULL, '', 2);

-- --------------------------------------------------------

--
-- Table structure for table `distribution_logs`
--

CREATE TABLE `distribution_logs` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `given_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `given_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evacuees`
--

CREATE TABLE `evacuees` (
  `id` int(11) NOT NULL,
  `id_type` enum('thai_id','passport','no_doc') DEFAULT 'thai_id' COMMENT 'ประเภทบัตร',
  `id_card` varchar(20) NOT NULL COMMENT 'เลขบัตรประชาชน',
  `prefix` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `addr_no` varchar(50) DEFAULT NULL,
  `addr_moo` varchar(50) DEFAULT NULL,
  `addr_sub` varchar(100) DEFAULT NULL,
  `addr_dis` varchar(100) DEFAULT NULL,
  `addr_prov` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL COMMENT 'ที่อยู่รวม (Auto generated)',
  `curr_addr_no` varchar(50) DEFAULT NULL,
  `curr_addr_moo` varchar(50) DEFAULT NULL,
  `curr_addr_sub` varchar(100) DEFAULT NULL,
  `curr_addr_dis` varchar(100) DEFAULT NULL,
  `curr_addr_prov` varchar(100) DEFAULT NULL,
  `current_address` text DEFAULT NULL COMMENT 'ที่อยู่ปัจจุบันรวม (Auto generated)',
  `shelter_id` int(11) NOT NULL,
  `stay_type` enum('shelter','outside') DEFAULT 'shelter' COMMENT 'ประเภทการพัก',
  `stay_detail` text DEFAULT NULL COMMENT 'รายละเอียดที่พัก (กรณีพักนอกศูนย์)',
  `status` enum('active','returned','hospitalized') DEFAULT 'active',
  `chronic_disease` text DEFAULT NULL,
  `medication` text DEFAULT NULL,
  `vulnerable_group` varchar(255) DEFAULT NULL COMMENT 'กลุ่มเปราะบาง CSV',
  `family_head_id` int(11) DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `check_out_date` datetime DEFAULT NULL COMMENT 'วันที่ออกจากศูนย์',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evacuees`
--

INSERT INTO `evacuees` (`id`, `id_type`, `id_card`, `prefix`, `first_name`, `last_name`, `birth_date`, `age`, `gender`, `phone`, `addr_no`, `addr_moo`, `addr_sub`, `addr_dis`, `addr_prov`, `address`, `curr_addr_no`, `curr_addr_moo`, `curr_addr_sub`, `curr_addr_dis`, `curr_addr_prov`, `current_address`, `shelter_id`, `stay_type`, `stay_detail`, `status`, `chronic_disease`, `medication`, `vulnerable_group`, `family_head_id`, `registered_at`, `check_out_date`, `updated_at`) VALUES
(9, 'thai_id', '1332000000946', 'นาย', 'ปฐวีกานต์', 'ศรีคราม', '1994-12-07', 31, 'male', '0981051534', '119', '1', 'พยุห์', 'พยุห์', 'ศรีสะเกษ', 'บ้านเลขที่ 119 หมู่ 1 ต.พยุห์ อ.พยุห์ จ.ศรีสะเกษ', '119', '1', 'พยุห์', 'พยุห์', 'ศรีสะเกษ', 'บ้านเลขที่ 119 หมู่ 1 ต.พยุห์ อ.พยุห์ จ.ศรีสะเกษ', 10, 'shelter', '', 'returned', '', '', '', NULL, '2026-01-05 15:43:34', '2026-04-10 10:51:58', '2026-04-10 10:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `evacuee_history`
--

CREATE TABLE `evacuee_history` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL,
  `shelter_id` int(11) NOT NULL,
  `action` enum('check_in','check_out','transfer','undo_checkout') NOT NULL,
  `action_date` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'เจ้าหน้าที่ที่ทำรายการ',
  `details` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evacuee_history`
--

INSERT INTO `evacuee_history` (`id`, `evacuee_id`, `shelter_id`, `action`, `action_date`, `user_id`, `details`) VALUES
(14, 9, 5, 'transfer', '2025-12-31 15:23:18', 1, 'ย้ายจาก วัดเจียงอีศรีมงคลวราราม ไปยัง ศูนย์อาคารพละวีสมหมาย (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(15, 9, 2, 'transfer', '2025-12-31 15:23:36', 1, 'ย้ายจาก ศูนย์อาคารพละวีสมหมาย ไปยัง วัดเจียงอีศรีมงคลวราราม (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(16, 9, 4, 'transfer', '2025-12-31 15:30:13', 1, 'ย้ายจาก วัดเจียงอีศรีมงคลวราราม ไปยัง ศาลากลางหมู่บ้านหนองแคน (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(17, 9, 4, 'check_out', '2026-01-04 23:35:39', 1, 'จบภารกิจ (จำหน่ายอัตโนมัติ)'),
(18, 9, 0, 'undo_checkout', '2026-01-05 00:00:39', 3, 'ยกเลิกการจำหน่าย (ดึงรายชื่อกลับมา Active)'),
(19, 9, 0, 'check_out', '2026-01-05 00:00:44', 3, 'กลับ ณ บ้านเลขที่ 119 หมู่ 1 ต.พยุห์ อ.พยุห์ จ.ศรีสะเกษ จาก ศูนย์พักพิง'),
(20, 9, 1, 'transfer', '2026-01-05 00:01:54', 3, 'ย้ายจาก ศาลากลางหมู่บ้านหนองแคน ไปยัง โรงเรียนสตรีสิริเกศ (พักมาแล้ว 4 วัน 8 ชม.)'),
(21, 9, 2, 'transfer', '2026-01-05 00:04:05', 1, 'ย้ายจาก โรงเรียนสตรีสิริเกศ ไปยัง วัดเจียงอีศรีมงคลวราราม (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(22, 9, 0, 'transfer', '2026-01-05 00:27:37', 1, 'ย้ายจาก วัดเจียงอีศรีมงคลวราราม ไปยัง ไม่ระบุ (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(23, 9, 9, 'transfer', '2026-01-05 00:27:50', 1, 'ย้ายจาก ไม่ระบุ ไปยัง ศูนย์ ม.กีฬา (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(24, 9, 0, 'transfer', '2026-01-05 00:28:01', 1, 'ย้ายจาก ศูนย์ ม.กีฬา ไปยัง ไม่ระบุ (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(25, 9, 9, 'transfer', '2026-01-05 00:28:09', 1, 'ย้ายจาก ไม่ระบุ ไปยัง ศูนย์ ม.กีฬา (พักมาแล้ว น้อยกว่า 1 ชม.)'),
(26, 9, 10, 'transfer', '2026-01-05 15:43:34', 1, 'ย้ายจาก ศูนย์ ม.กีฬา ไปยัง วัดเจียงอีศรีมงคลวราราม (พักมาแล้ว 15 ชม.)'),
(27, 9, 0, 'check_out', '2026-04-10 10:51:58', 2, 'กลับ ณ บ้านเลขที่ 119 หมู่ 1 ต.พยุห์ อ.พยุห์ จ.ศรีสะเกษ จาก ศูนย์พักพิง');

-- --------------------------------------------------------

--
-- Table structure for table `evacuee_needs`
--

CREATE TABLE `evacuee_needs` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL,
  `need_type` enum('pregnant','disabled','elderly','infant','chronic_disease') NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL COMMENT 'ชื่อเหตุการณ์ (เช่น น้ำท่วมปี 67)',
  `type` varchar(50) NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_scope` varchar(255) DEFAULT NULL COMMENT 'ขอบเขตพื้นที่ (เช่น อ.เมือง)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `name`, `type`, `description`, `start_date`, `end_date`, `status`, `created_at`, `location_scope`) VALUES
(2, 'วาตภัย พายุฤดูร้อน (เม.ย. 67)', 'storm', 'พายุฝนฟ้าคะนอง บ้านเรือนเสียหาย 50 หลังคาเรือน', '2024-04-10', '2026-01-04', 'closed', '2025-12-22 07:12:37', ''),
(3, 'เหตุปะทะไทย–กัมพูชา ธ.ค. 2568', 'other', '', '2025-12-22', NULL, 'active', '2025-12-22 07:41:33', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `shelter_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL COMMENT 'in=รับบริจาค/ซื้อ, out=แจกจ่าย/เสียหาย',
  `quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ผู้ทำรายการ',
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `evacuee_id` int(11) NOT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medication` text DEFAULT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `shelter_id` int(11) NOT NULL COMMENT 'ID ศูนย์พักพิงที่ขอ',
  `item_id` int(11) NOT NULL COMMENT 'ID สินค้าจากตาราง inventory',
  `quantity` int(11) NOT NULL COMMENT 'จำนวนที่ขอ',
  `priority` enum('low','medium','high') DEFAULT 'low',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `shelter_id`, `item_id`, `quantity`, `priority`, `status`, `reason`, `created_at`, `updated_at`) VALUES
(1, 2, 7, 1, 'low', 'approved', '', '2025-12-26 09:27:38', '2025-12-26 09:35:30'),
(2, 1, 6, 100, 'high', 'pending', '', '2025-12-27 16:00:13', '2025-12-27 16:00:13');

-- --------------------------------------------------------

--
-- Table structure for table `shelters`
--

CREATE TABLE `shelters` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL COMMENT 'ผูกกับเหตุการณ์ไหน',
  `name` varchar(200) NOT NULL COMMENT 'ชื่อศูนย์',
  `location` text NOT NULL COMMENT 'ที่ตั้ง',
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0 COMMENT 'ความจุสูงสุด',
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('open','full','closed') NOT NULL DEFAULT 'open',
  `last_updated` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelters`
--

INSERT INTO `shelters` (`id`, `incident_id`, `name`, `location`, `district`, `province`, `latitude`, `longitude`, `capacity`, `contact_person`, `contact_phone`, `status`, `last_updated`, `created_at`) VALUES
(9, 3, 'ศูนย์ ม.กีฬา', '111', '', 'ศรีสะเกษ', 15.09878100, 104.33997000, 3000, 'นายปฐวีกานต์  ศรีคราม', '0981051534', 'open', '2025-12-26 11:05:23', '2025-12-26 11:05:23'),
(10, 3, 'วัดเจียงอีศรีมงคลวราราม', 'ดดดด', 'ศรีสะเกษ', 'ศรีสะเกษ', 15.10784800, 104.33269000, 3000, 'นายปฐวีกานต์  ศรีคราม', '0981051534', 'open', '2025-12-26 11:06:11', '2025-12-26 11:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `shelter_requests`
--

CREATE TABLE `shelter_requests` (
  `id` int(11) NOT NULL,
  `shelter_id` int(11) NOT NULL,
  `category` enum('supplies','medical','manpower','transport','other') NOT NULL COMMENT 'หมวดหมู่',
  `urgency` enum('normal','high','critical') DEFAULT 'normal' COMMENT 'ความเร่งด่วน',
  `detail` text NOT NULL COMMENT 'รายละเอียดสิ่งที่ขอ',
  `quantity` int(11) DEFAULT 1,
  `status` enum('pending','approved','in_progress','completed','rejected') DEFAULT 'pending',
  `response_note` text DEFAULT NULL COMMENT 'บันทึกการตอบรับจาก Admin',
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'ผู้ส่งคำร้อง',
  `item_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อรายการที่ขอ',
  `unit` varchar(50) DEFAULT NULL COMMENT 'หน่วยนับ',
  `description` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'ประเภทการกระทำ (Login, Add, Edit)',
  `description` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '127.0.0.1', '2025-12-22 07:12:37'),
(2, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '127.0.0.1', '2025-12-22 07:12:37'),
(3, 2, 'Add Evacuee', 'ลงทะเบียน: สมศักดิ์ รักชาติ (Shelter ID: 1)', '127.0.0.1', '2025-12-22 07:12:37'),
(4, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 07:12:47'),
(5, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 07:13:12'),
(6, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 07:22:42'),
(7, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 1', '::1', '2025-12-22 07:40:54'),
(8, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 2', '::1', '2025-12-22 07:40:59'),
(9, 1, 'Add Incident', 'เปิดภารกิจใหม่: เหตุปะทะไทย–กัมพูชา ธ.ค. 2568 (other)', '::1', '2025-12-22 07:41:33'),
(10, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: 2)', '::1', '2025-12-22 08:34:03'),
(11, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: 2)', '::1', '2025-12-22 08:40:55'),
(12, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: 2)', '::1', '2025-12-22 08:41:16'),
(13, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: 2)', '::1', '2025-12-22 08:56:09'),
(14, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: 2)', '::1', '2025-12-22 08:56:26'),
(15, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายปฐวีกานต์ ศรีคราม', '::1', '2025-12-22 09:13:51'),
(16, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายปฐวีกานต์ ศรีคราม', '::1', '2025-12-22 09:13:57'),
(17, 1, 'Add Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: )', '::1', '2025-12-22 09:15:33'),
(18, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (Shelter ID: )', '::1', '2025-12-22 09:16:23'),
(19, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (ID: 4)', '::1', '2025-12-22 09:54:55'),
(20, 1, 'Edit Evacuee', 'ชื่อ: วิชัย ใจดี (ID: 4)', '::1', '2025-12-22 09:55:16'),
(21, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 1', '::1', '2025-12-22 10:00:07'),
(22, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายปฐวีกานต์ ศรีคราม', '::1', '2025-12-22 10:00:30'),
(23, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 10:03:32'),
(24, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 10:08:41'),
(25, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 3', '::1', '2025-12-22 10:10:19'),
(26, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 1', '::1', '2025-12-22 10:10:22'),
(27, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 1', '::1', '2025-12-22 10:10:31'),
(28, 1, 'Edit Incident', 'แก้ไขภารกิจ ID: 1', '::1', '2025-12-22 10:10:36'),
(29, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายปฐวีกานต์ ศรีคราม', '::1', '2025-12-22 10:29:36'),
(30, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายปฐวีกานต์ ศรีคราม', '::1', '2025-12-22 10:53:28'),
(31, 1, 'Edit User', 'แก้ไขผู้ใช้งาน: staff01', '::1', '2025-12-22 11:22:04'),
(32, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:22:07'),
(33, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 11:22:11'),
(34, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:30:55'),
(35, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 11:30:57'),
(36, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:30:59'),
(37, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 11:31:00'),
(38, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:31:07'),
(39, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 11:31:11'),
(40, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:31:24'),
(41, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-22 11:31:26'),
(42, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-22 11:33:08'),
(43, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-23 01:58:27'),
(44, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-23 03:45:15'),
(45, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-23 03:45:16'),
(46, 1, 'Edit Incident', 'แก้ไขเหตุการณ์: เหตุปะทะไทย–กัมพูชา ธ.ค. 2568', '::1', '2025-12-23 03:52:37'),
(47, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายนายปฐวีกานต์ ศรีคราม', '::1', '2025-12-23 08:37:14'),
(48, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-23 09:48:41'),
(49, 1, 'Add Shelter', 'ชื่อศูนย์: ศูนย์อาคารพละวีสมหมาย (Lat: 15.101142, Lng: 104.34057)', '::1', '2025-12-23 09:51:07'),
(50, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายนายปฐวีกานต์ ศรีคราม', '::1', '2025-12-23 09:54:20'),
(51, 1, 'Edit Shelter', 'ชื่อศูนย์: ศูนย์อาคารพละวีสมหมาย (Lat: 15.101142, Lng: 104.34057)', '::1', '2025-12-23 09:54:29'),
(52, 1, 'Edit Caretaker', 'แก้ไขผู้ดูแล: นายนายปฐวีกานต์ ศรีคราม', '::1', '2025-12-23 11:20:37'),
(53, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-23 11:31:51'),
(54, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 03:02:43'),
(55, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 03:15:31'),
(56, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 03:27:27'),
(57, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 07:09:43'),
(58, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 07:15:02'),
(59, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-24 07:15:08'),
(60, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-24 07:37:24'),
(61, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-24 10:29:42'),
(62, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 03:35:13'),
(63, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 04:23:51'),
(64, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 07:17:47'),
(65, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 07:17:50'),
(66, 3, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 07:19:27'),
(67, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 07:19:29'),
(68, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 07:26:02'),
(69, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 07:26:08'),
(70, 3, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 07:34:30'),
(71, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 07:34:41'),
(72, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 08:21:40'),
(73, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 08:21:44'),
(74, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 08:24:18'),
(75, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 08:24:21'),
(76, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 10:33:24'),
(77, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 10:33:28'),
(78, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 11:44:37'),
(79, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 11:44:40'),
(80, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 11:47:11'),
(81, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 11:47:16'),
(82, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-25 11:48:05'),
(83, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-25 13:36:11'),
(84, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-26 02:31:08'),
(85, 2, 'Add Shelter', 'ชื่อศูนย์: วัดเจียงอีศรีมงคลวราราม (Lat: 15.107877, Lng: 104.332682)', '::1', '2025-12-26 03:40:01'),
(86, 2, 'Add Shelter', 'ชื่อศูนย์: วัดเจียงอีศรีมงคลวราราม (Lat: 15.119493, Lng: 104.283232)', '::1', '2025-12-26 03:40:33'),
(87, 2, 'Add Shelter', 'ชื่อศูนย์: วัดเจียงอีศรีมงคลวราราม (Lat: 15.119493, Lng: 104.283232)', '::1', '2025-12-26 03:40:37'),
(88, 2, 'Logout', 'ออกจากระบบ', '::1', '2025-12-26 03:54:50'),
(89, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-26 03:54:53'),
(90, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-26 04:14:50'),
(91, 1, 'Logout', 'ออกจากระบบ', '::1', '2025-12-26 04:29:19'),
(92, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-26 04:29:23'),
(93, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-26 04:31:51'),
(94, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 14:26:13'),
(95, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 14:26:39'),
(96, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 14:33:54'),
(97, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 14:44:59'),
(98, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 15:51:51'),
(99, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 15:54:20'),
(100, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 15:54:38'),
(101, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 17:25:35'),
(102, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-27 18:10:10'),
(103, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-28 07:31:25'),
(104, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-28 15:47:31'),
(105, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-28 15:54:03'),
(106, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-28 16:47:51'),
(107, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-30 03:04:53'),
(108, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-30 03:17:00'),
(109, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-31 06:41:37'),
(110, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-31 08:04:04'),
(111, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2025-12-31 08:31:04'),
(112, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-04 15:38:52'),
(113, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-04 16:57:01'),
(114, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-04 17:02:52'),
(115, 3, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-04 17:04:27'),
(116, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-04 17:09:17'),
(117, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-05 06:43:17'),
(118, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-01-05 07:22:42'),
(119, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-02-17 09:57:20'),
(120, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-02-17 10:01:34'),
(121, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-03-02 02:13:04'),
(122, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-03-06 10:04:18'),
(123, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-03-27 08:51:04'),
(124, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-04-10 03:48:46'),
(125, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-04-10 03:50:08'),
(126, 2, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-04-10 03:50:35'),
(127, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-05-26 07:45:23'),
(128, 1, 'Login', 'เข้าสู่ระบบสำเร็จ', '::1', '2026-07-02 00:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES
('line_notify_active', '0', 'สถานะการเปิดใช้งาน (1=เปิด, 0=ปิด)', '2025-12-26 08:02:30'),
('line_notify_token', '', 'Token สำหรับแจ้งเตือนผ่าน LINE', '2025-12-26 08:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้',
  `password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (Hashed)',
  `first_name` varchar(100) DEFAULT '',
  `last_name` varchar(100) DEFAULT '',
  `full_name` varchar(100) NOT NULL COMMENT 'ชื่อ-นามสกุล',
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `shelter_id` int(11) DEFAULT NULL COMMENT 'สังกัดศูนย์พักพิง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะผู้ใช้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `full_name`, `role`, `shelter_id`, `created_at`, `last_login`, `status`) VALUES
(1, 'admin', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'ปฐวีกานต์', 'ศรีคราม', 'ผู้ดูแลระบบสูงสุด', 'admin', 0, '2025-12-22 07:12:37', NULL, 'active'),
(2, 'staff01', '$2y$10$XX/dDDI.jsExOMHlVopO/.0fkEAYGJdIwKivLIgChpRtdwIXm4oUS', 'ปฐวีกานต์', 'ศรีคราม', 'เจ้าหน้าที่ สมชาย ใจดี', 'staff', 10, '2025-12-22 07:12:37', NULL, 'active'),
(3, 'staff02', '$2y$10$KA83yGsOxdUgcjB0WO5WNOKiCbQP.uF3Gyhw.UaTapbPT/5YZ/dJW', 'ปฐวีกานต์', 'ศรีคราม', 'เจ้าหน้าที่ สมหญิง รักงาน', 'donation_officer', 1, '2025-12-22 07:12:37', NULL, 'active'),
(4, 'volunteer01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', '', 'อาสาสมัคร ก.', 'volunteer', NULL, '2025-12-22 07:12:37', NULL, 'active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_shelter_stats`
-- (See below for the actual view)
--
CREATE TABLE `view_shelter_stats` (
`shelter_id` int(11)
,`shelter_name` varchar(200)
,`capacity` int(11)
,`incident_id` int(11)
,`status` enum('open','full','closed')
,`current_occupancy` bigint(21)
,`vacancy` bigint(22)
,`occupancy_rate` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_shelter_stats`
--
DROP TABLE IF EXISTS `view_shelter_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_shelter_stats`  AS SELECT `s`.`id` AS `shelter_id`, `s`.`name` AS `shelter_name`, `s`.`capacity` AS `capacity`, `s`.`incident_id` AS `incident_id`, `s`.`status` AS `status`, count(`e`.`id`) AS `current_occupancy`, `s`.`capacity`- count(`e`.`id`) AS `vacancy`, round(count(`e`.`id`) / `s`.`capacity` * 100,2) AS `occupancy_rate` FROM (`shelters` `s` left join `evacuees` `e` on(`s`.`id` = `e`.`shelter_id` and `e`.`check_out_date` is null)) GROUP BY `s`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_shelter_id` (`target_shelter_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement` (`announcement_id`);

--
-- Indexes for table `caretakers`
--
ALTER TABLE `caretakers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shelter_caretaker` (`shelter_id`);

--
-- Indexes for table `distribution`
--
ALTER TABLE `distribution`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evacuee_id` (`evacuee_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `distributions`
--
ALTER TABLE `distributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evacuee_id` (`evacuee_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `distribution_logs`
--
ALTER TABLE `distribution_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evacuees`
--
ALTER TABLE `evacuees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shelter` (`shelter_id`),
  ADD KEY `idx_id_card` (`id_card`);

--
-- Indexes for table `evacuee_history`
--
ALTER TABLE `evacuee_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evacuee` (`evacuee_id`);

--
-- Indexes for table `evacuee_needs`
--
ALTER TABLE `evacuee_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_needs_evacuee` (`evacuee_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inventory_shelter` (`shelter_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory` (`inventory_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evacuee_id` (`evacuee_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shelters`
--
ALTER TABLE `shelters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_incident_id` (`incident_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `shelter_requests`
--
ALTER TABLE `shelter_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shelter_id` (`shelter_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `caretakers`
--
ALTER TABLE `caretakers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `distribution`
--
ALTER TABLE `distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `distributions`
--
ALTER TABLE `distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `distribution_logs`
--
ALTER TABLE `distribution_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evacuees`
--
ALTER TABLE `evacuees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `evacuee_history`
--
ALTER TABLE `evacuee_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `evacuee_needs`
--
ALTER TABLE `evacuee_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shelters`
--
ALTER TABLE `shelters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `shelter_requests`
--
ALTER TABLE `shelter_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `caretakers`
--
ALTER TABLE `caretakers`
  ADD CONSTRAINT `fk_caretakers_shelter` FOREIGN KEY (`shelter_id`) REFERENCES `shelters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evacuee_needs`
--
ALTER TABLE `evacuee_needs`
  ADD CONSTRAINT `evacuee_needs_ibfk_1` FOREIGN KEY (`evacuee_id`) REFERENCES `evacuees` (`id`),
  ADD CONSTRAINT `fk_needs_evacuee` FOREIGN KEY (`evacuee_id`) REFERENCES `evacuees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_shelter` FOREIGN KEY (`shelter_id`) REFERENCES `shelters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_trans_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`evacuee_id`) REFERENCES `evacuees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shelters`
--
ALTER TABLE `shelters`
  ADD CONSTRAINT `fk_shelters_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shelter_requests`
--
ALTER TABLE `shelter_requests`
  ADD CONSTRAINT `shelter_requests_ibfk_1` FOREIGN KEY (`shelter_id`) REFERENCES `shelters` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
