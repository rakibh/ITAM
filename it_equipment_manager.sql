-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 15, 2025 at 09:53 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it_equipment_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipments`
--

DROP TABLE IF EXISTS `equipments`;
CREATE TABLE IF NOT EXISTS `equipments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_type_id` int NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('In Use','Available','Under Repair','Retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Available',
  `condition_status` enum('New','Good','Needs Service','Damaged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Good',
  `seller_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `custom_label_1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_value_1` text COLLATE utf8mb4_unicode_ci,
  `custom_label_2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_value_2` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_equipment_type` (`equipment_type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_condition` (`condition_status`),
  KEY `idx_warranty_expiry` (`warranty_expiry_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipments`
--

INSERT INTO `equipments` (`id`, `label`, `equipment_type_id`, `brand`, `model_number`, `serial_number`, `location`, `floor_no`, `department`, `assigned_to`, `designation`, `status`, `condition_status`, `seller_company`, `purchase_date`, `warranty_expiry_date`, `remarks`, `custom_label_1`, `custom_value_1`, `custom_label_2`, `custom_value_2`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CCTV1', 1, 'Hikvision', 'DS-2CE76D0T-ITPF', '145789951801', 'Head Office', '3rd floor', 'News Room', NULL, NULL, 'Under Repair', 'Needs Service', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-11 13:35:13', '2025-11-11 13:35:13'),
(2, 'Lo', 3, '101225', 'LG2025', '254565878', 'Dhaka', '3rd', 'Admin', 'No', 'N/a', 'Available', 'Good', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-12 00:18:44', '2025-11-12 00:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_custom_values`
--

DROP TABLE IF EXISTS `equipment_custom_values`;
CREATE TABLE IF NOT EXISTS `equipment_custom_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `field_id` int NOT NULL,
  `field_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipment_id` (`equipment_id`),
  KEY `idx_field_id` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_revisions`
--

DROP TABLE IF EXISTS `equipment_revisions`;
CREATE TABLE IF NOT EXISTS `equipment_revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `change_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_equipment_id` (`equipment_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_revisions`
--

INSERT INTO `equipment_revisions` (`id`, `equipment_id`, `changed_by`, `change_description`, `changed_at`) VALUES
(1, 1, 1, 'Equipment created by Rakib Hasan', '2025-11-11 13:35:13'),
(2, 2, 1, 'Equipment created by Rakib Hasan', '2025-11-12 00:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_types`
--

DROP TABLE IF EXISTS `equipment_types`;
CREATE TABLE IF NOT EXISTS `equipment_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_custom` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_types`
--

INSERT INTO `equipment_types` (`id`, `type_name`, `is_custom`, `created_at`) VALUES
(1, 'Desktop PC', 0, '2025-11-08 20:11:39'),
(2, 'Laptop', 0, '2025-11-08 20:11:39'),
(3, 'Monitor', 0, '2025-11-08 20:11:39'),
(4, 'UPS', 0, '2025-11-08 20:11:39'),
(5, 'Web Camera', 0, '2025-11-08 20:11:39'),
(6, 'SSD', 0, '2025-11-08 20:11:39'),
(7, 'HDD', 0, '2025-11-08 20:11:39'),
(8, 'RAM', 0, '2025-11-08 20:11:39'),
(9, 'Printer', 0, '2025-11-08 20:11:39'),
(10, 'Scanner', 0, '2025-11-08 20:11:39'),
(11, 'Network Switch', 0, '2025-11-08 20:11:39'),
(12, 'WiFi Router', 0, '2025-11-08 20:11:39'),
(13, 'Server', 0, '2025-11-08 20:11:39'),
(14, 'KVM', 0, '2025-11-08 20:11:39'),
(15, 'Projector', 0, '2025-11-08 20:11:39'),
(16, 'Speaker / Headphones', 0, '2025-11-08 20:11:39'),
(17, 'CCTV Camera', 0, '2025-11-08 20:11:39'),
(18, 'NVR / DVR', 0, '2025-11-08 20:11:39');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_type_fields`
--

DROP TABLE IF EXISTS `equipment_type_fields`;
CREATE TABLE IF NOT EXISTS `equipment_type_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_type_id` int NOT NULL,
  `field_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` enum('text','number','textarea','select','multiple') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `field_options` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) DEFAULT '0',
  `display_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type_id` (`equipment_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_type_fields`
--

INSERT INTO `equipment_type_fields` (`id`, `equipment_type_id`, `field_name`, `field_type`, `field_options`, `is_required`, `display_order`) VALUES
(1, 1, 'Motherboard', 'text', NULL, 0, 1),
(2, 1, 'CPU', 'text', NULL, 0, 2),
(3, 1, 'RAM', 'multiple', NULL, 0, 3),
(4, 1, 'SSD', 'multiple', NULL, 0, 4),
(5, 1, 'HDD', 'multiple', NULL, 0, 5),
(6, 1, 'GPU', 'text', NULL, 0, 6),
(7, 1, 'OS', 'text', NULL, 0, 7),
(8, 1, 'Monitor', 'text', NULL, 0, 8),
(9, 2, 'Motherboard', 'text', NULL, 0, 1),
(10, 2, 'CPU', 'text', NULL, 0, 2),
(11, 2, 'RAM', 'multiple', NULL, 0, 3),
(12, 2, 'SSD', 'multiple', NULL, 0, 4),
(13, 2, 'HDD', 'multiple', NULL, 0, 5),
(14, 2, 'GPU', 'text', NULL, 0, 6),
(15, 2, 'OS', 'text', NULL, 0, 7),
(16, 3, 'Display Size', 'text', NULL, 0, 1),
(17, 3, 'Color', 'text', NULL, 0, 2),
(18, 3, 'Ports', 'text', NULL, 0, 3),
(19, 4, 'Battery Capacity', 'text', NULL, 0, 1),
(20, 6, 'Storage Capacity', 'text', NULL, 0, 1),
(21, 7, 'Storage Capacity', 'text', NULL, 0, 1),
(22, 9, 'Output Color', 'select', NULL, 0, 1),
(23, 11, 'Type', 'text', NULL, 0, 1),
(24, 11, 'Ports', 'text', NULL, 0, 2),
(25, 11, 'Data Transmission Rate', 'text', NULL, 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_identifier_ip` (`login_identifier`,`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `login_identifier`, `ip_address`, `attempt_time`, `success`) VALUES
(1, 'ADMIN-001', '127.0.0.1', '2025-11-08 20:13:44', 0),
(2, 'ADMIN-001', '127.0.0.1', '2025-11-08 20:14:39', 1),
(3, 'admin', '::1', '2025-11-08 20:14:58', 1),
(4, 'T2001', '::1', '2025-11-09 02:49:23', 0),
(5, 'T20001', '::1', '2025-11-09 02:49:32', 0),
(6, 'admin', '127.0.0.1', '2025-11-09 14:49:10', 1),
(7, 'admin', '::1', '2025-11-09 14:49:25', 1),
(8, 'admin', '127.0.0.1', '2025-11-09 14:51:00', 1),
(9, 'admin', '127.0.0.1', '2025-11-10 14:43:56', 1),
(10, 'admin', '::1', '2025-11-10 14:44:08', 1),
(11, 'admin', '::1', '2025-11-10 16:27:33', 1),
(12, 'admin', '::1', '2025-11-10 23:30:19', 1),
(13, 'admin', '127.0.0.1', '2025-11-11 01:03:41', 1),
(14, 'admin', '::1', '2025-11-11 01:04:15', 1),
(15, 'admin', '::1', '2025-11-11 01:36:31', 1),
(16, 'admin', '127.0.0.1', '2025-11-11 02:58:37', 1),
(17, 'admin', '::1', '2025-11-11 02:58:52', 1),
(18, 'admin', '127.0.0.1', '2025-11-11 03:09:05', 1),
(19, 'admin', '127.0.0.1', '2025-11-11 11:17:45', 1),
(20, 'admin', '::1', '2025-11-11 11:17:59', 1),
(21, 'admin', '::1', '2025-11-11 11:22:40', 1),
(22, 'admin', '127.0.0.1', '2025-11-11 13:47:48', 1),
(23, 'admin', '::1', '2025-11-11 14:20:05', 1),
(24, 'admin', '::1', '2025-11-11 16:17:54', 1),
(25, 'admin', '::1', '2025-11-11 17:32:41', 1),
(26, 'admin', '127.0.0.1', '2025-11-11 21:45:20', 1),
(27, 'admin', '127.0.0.1', '2025-11-12 00:05:18', 1),
(28, 'admin', '127.0.0.1', '2025-11-12 11:47:26', 1),
(29, 'admin', '127.0.0.1', '2025-11-12 17:36:04', 1),
(30, 'admin', '127.0.0.1', '2025-11-12 21:27:59', 1),
(31, 'TEST-1762970303', '127.0.0.1', '2025-11-13 00:04:06', 0),
(32, 'U0002', '127.0.0.1', '2025-11-13 00:04:31', 1),
(33, 'admin', '127.0.0.1', '2025-11-13 15:54:37', 1),
(34, 'admin', '127.0.0.1', '2025-11-14 01:48:26', 1),
(35, 'admin', '127.0.0.1', '2025-11-16 03:43:58', 1),
(36, 'admin', '127.0.0.1', '2025-11-16 03:45:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `network_info`
--

DROP TABLE IF EXISTS `network_info`;
CREATE TABLE IF NOT EXISTS `network_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cable_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patch_panel_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patch_panel_port` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patch_panel_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `switch_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `switch_port` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `switch_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `created_by` (`created_by`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_mac_address` (`mac_address`),
  KEY `idx_equipment_id` (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `network_info`
--

INSERT INTO `network_info` (`id`, `equipment_id`, `ip_address`, `mac_address`, `cable_number`, `patch_panel_number`, `patch_panel_port`, `patch_panel_location`, `switch_number`, `switch_port`, `switch_location`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, '192.168.12.53', 'N/A', '101', '2', '12', 'CER room', '3', '12', 'CER room', NULL, 1, '2025-11-11 03:00:01', '2025-11-11 03:00:01'),
(2, NULL, '192.168.12.54', 'N/A', '101', '2', '13', 'CER room', '3', '14', 'CER room', NULL, 1, '2025-11-11 11:18:34', '2025-11-11 11:18:34'),
(3, NULL, '192.168.12.58', 'N/A', '105', '2', '18', 'CER room', '3', '25', 'CER room', NULL, 1, '2025-11-11 11:31:14', '2025-11-11 12:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `network_revisions`
--

DROP TABLE IF EXISTS `network_revisions`;
CREATE TABLE IF NOT EXISTS `network_revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `network_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `change_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_network_id` (`network_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `network_revisions`
--

INSERT INTO `network_revisions` (`id`, `network_id`, `changed_by`, `change_description`, `changed_at`) VALUES
(1, 1, 1, 'Network info created by Rakib Hasan', '2025-11-11 03:00:01'),
(2, 2, 1, 'Network info created by Rakib Hasan', '2025-11-11 11:18:34'),
(3, 3, 1, 'Network info created by Rakib Hasan', '2025-11-11 11:31:14'),
(4, 3, 1, 'Updated by Rakib Hasan', '2025-11-11 12:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('Equipment','Network','User','Todo','System','Warranty') COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` enum('add','update','delete','assign','unassign','status_change','role_change','expiring','expired') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_json` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `event`, `title`, `message`, `context_json`, `created_by`, `created_at`) VALUES
(1, 'User', 'update', 'User Profile Updated', 'System Administrator updated profile: System (ADMIN-001)', '{\"user_id\":1}', 'System Administrator', '2025-11-10 14:46:59'),
(2, 'User', 'update', 'User Profile Updated', 'System Administrator updated profile: Rakib (ADMIN-001)', '{\"user_id\":1}', 'System Administrator', '2025-11-10 18:42:47'),
(3, 'Network', 'add', 'New Network Info Added', 'Rakib Hasan added new network info for IP: 192.168.12.53', '{\"network_id\":\"1\",\"ip_address\":\"192.168.12.53\"}', 'Rakib Hasan', '2025-11-11 03:00:01'),
(4, 'Network', 'add', 'New Network Info Added', 'Rakib Hasan added new network info for IP: 192.168.12.54', '{\"network_id\":\"2\",\"ip_address\":\"192.168.12.54\"}', 'Rakib Hasan', '2025-11-11 11:18:34'),
(5, 'Network', 'add', 'New Network Info Added', 'Rakib Hasan added new network info for IP: 192.168.12.58', '{\"network_id\":\"3\",\"ip_address\":\"192.168.12.58\"}', 'Rakib Hasan', '2025-11-11 11:31:14'),
(6, 'Todo', 'assign', 'New Task Assigned', 'Rakib Hasan assigned you a task: Form Test Task 11:54:33 (Due: 2025-11-12 11:59 PM)', '{\"todo_id\":\"3\"}', 'Rakib Hasan', '2025-11-11 11:54:39'),
(7, 'Network', 'update', 'Network Info Updated', 'Rakib Hasan updated network info (IP: 192.168.12.58)', '{\"network_id\":3,\"ip_address\":\"192.168.12.58\"}', 'Rakib Hasan', '2025-11-11 12:25:39'),
(8, 'Equipment', 'add', 'New Equipment Added', 'Rakib Hasan added new equipment: CCTV1 (145789951801)', '{\"equipment_id\":\"1\"}', 'Rakib Hasan', '2025-11-11 13:35:13'),
(9, 'Equipment', 'add', 'New Equipment Added', 'Rakib Hasan added new equipment: Lo (254565878)', '{\"equipment_id\":\"2\"}', 'Rakib Hasan', '2025-11-12 00:18:44'),
(10, 'User', 'update', 'User Profile Updated', 'Rakib Hasan updated profile: User (U0002)', '{\"user_id\":3}', 'Rakib Hasan', '2025-11-12 23:59:27'),
(11, 'User', 'update', 'User Profile Updated', 'User Test updated profile: User (U0002)', '{\"user_id\":3}', 'User Test', '2025-11-13 00:06:14'),
(12, 'Todo', 'update', 'Task Updated', 'Rakib Hasan updated task: Test Task - 2025-11-11 11:53:42', '{\"todo_id\":1}', 'Rakib Hasan', '2025-11-13 02:54:36'),
(13, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Rakib Test', '{\"todo_id\":\"5\"}', 'Rakib Hasan', '2025-11-13 02:55:32'),
(14, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:53:42\' to Ongoing', '{\"todo_id\":1,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-13 03:15:41'),
(15, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:53:42\' to Completed', '{\"todo_id\":1,\"new_status\":\"Completed\"}', 'Rakib Hasan', '2025-11-13 03:15:55'),
(16, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:54:33\' to Ongoing', '{\"todo_id\":2,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-13 08:55:39'),
(17, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:54:33\' to Completed', '{\"todo_id\":2,\"new_status\":\"Completed\"}', 'Rakib Hasan', '2025-11-13 09:05:14'),
(18, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Cancel test', '{\"todo_id\":\"6\"}', 'Rakib Hasan', '2025-11-13 09:29:34'),
(19, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Cancel test\' to Cancelled', '{\"todo_id\":6,\"new_status\":\"Cancelled\"}', 'Rakib Hasan', '2025-11-13 09:30:25'),
(20, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Ajax (AJAX-1763027704)', '{\"user_id\":\"4\"}', 'Rakib Hasan', '2025-11-13 15:55:11'),
(21, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: V1 (U0003)', '{\"user_id\":\"5\"}', 'Rakib Hasan', '2025-11-13 16:06:07'),
(22, 'User', 'update', 'User Profile Updated', 'Rakib Hasan updated profile: V1 (U0003)', '{\"user_id\":5}', 'Rakib Hasan', '2025-11-13 16:07:29'),
(23, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: V3 (U0004)', '{\"user_id\":\"6\"}', 'Rakib Hasan', '2025-11-13 16:29:57'),
(24, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: V2 (A0002)', '{\"user_id\":\"7\"}', 'Rakib Hasan', '2025-11-13 16:30:57'),
(25, 'User', 'update', 'User Profile Updated', 'Rakib Hasan updated profile: Rakib (ADMIN-001)', '{\"user_id\":1}', 'Rakib Hasan', '2025-11-13 18:42:25'),
(26, 'User', 'update', 'User Profile Updated', 'Rakib Hasan updated profile: Rakib (ADMIN-001)', '{\"user_id\":1}', 'Rakib Hasan', '2025-11-13 18:42:28'),
(27, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Test', '{\"todo_id\":\"7\"}', 'Rakib Hasan', '2025-11-13 18:42:52'),
(28, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test\' to Ongoing', '{\"todo_id\":7,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-13 18:43:01'),
(29, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix user roal (f001)', '{\"user_id\":\"8\"}', 'Rakib Hasan', '2025-11-13 20:18:55'),
(30, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix user role (f002)', '{\"user_id\":\"9\"}', 'Rakib Hasan', '2025-11-13 20:19:37'),
(31, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix user role 3 (f003)', '{\"user_id\":\"10\"}', 'Rakib Hasan', '2025-11-13 20:20:07'),
(32, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix admin role 1 (f004)', '{\"user_id\":\"11\"}', 'Rakib Hasan', '2025-11-13 20:20:53'),
(33, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix admin role 3 (U0055)', '{\"user_id\":\"12\"}', 'Rakib Hasan', '2025-11-13 20:26:11'),
(34, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: test task', '{\"todo_id\":\"8\"}', 'Rakib Hasan', '2025-11-13 22:50:14'),
(35, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Test\' to Completed', '{\"todo_id\":7,\"new_status\":\"Completed\"}', 'Rakib Hasan', '2025-11-13 22:50:34'),
(36, 'Todo', 'update', 'Task Updated', 'Rakib Hasan updated task: Test Task - 2025-11-11 11:54:33', '{\"todo_id\":2}', 'Rakib Hasan', '2025-11-13 22:52:47'),
(37, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Rakib Test\' to Ongoing', '{\"todo_id\":5,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-13 22:53:18'),
(38, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'test task\' to Ongoing', '{\"todo_id\":8,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-13 22:53:31'),
(39, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Rakib Test\' to Cancelled', '{\"todo_id\":5,\"new_status\":\"Cancelled\"}', 'Rakib Hasan', '2025-11-13 22:54:31'),
(40, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'test task\' to Completed', '{\"todo_id\":8,\"new_status\":\"Completed\"}', 'Rakib Hasan', '2025-11-13 22:54:43'),
(41, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Task 1', '{\"todo_id\":\"9\"}', 'Rakib Hasan', '2025-11-13 23:21:01'),
(42, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Task 2', '{\"todo_id\":\"10\"}', 'Rakib Hasan', '2025-11-13 23:21:34'),
(43, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Task 3', '{\"todo_id\":\"11\"}', 'Rakib Hasan', '2025-11-13 23:22:53'),
(44, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Task 4', '{\"todo_id\":\"12\"}', 'Rakib Hasan', '2025-11-13 23:23:52'),
(45, 'Todo', 'add', 'New Task Assigned', 'Rakib Hasan assigned a new task: Task 5', '{\"todo_id\":\"13\"}', 'Rakib Hasan', '2025-11-14 01:08:54'),
(46, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1763062560)', '{\"user_id\":17}', 'Rakib Hasan', '2025-11-14 01:41:51'),
(47, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1763062536)', '{\"user_id\":16}', 'Rakib Hasan', '2025-11-14 01:41:57'),
(48, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix admin role 30 (U005530)', '{\"user_id\":\"18\"}', 'Rakib Hasan', '2025-11-14 01:42:32'),
(49, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix admin role 30 (U005531)', '{\"user_id\":\"19\"}', 'Rakib Hasan', '2025-11-14 01:43:05'),
(50, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix admin role 30 (U005531)', '{\"user_id\":19}', 'Rakib Hasan', '2025-11-14 01:43:56'),
(51, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix admin role 30 (U005530)', '{\"user_id\":18}', 'Rakib Hasan', '2025-11-14 01:44:00'),
(52, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1763062530)', '{\"user_id\":15}', 'Rakib Hasan', '2025-11-14 01:44:05'),
(53, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1763062515)', '{\"user_id\":14}', 'Rakib Hasan', '2025-11-14 01:44:09'),
(54, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1763062511)', '{\"user_id\":13}', 'Rakib Hasan', '2025-11-14 01:44:14'),
(55, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix admin role 3 (U0055)', '{\"user_id\":12}', 'Rakib Hasan', '2025-11-14 01:44:19'),
(56, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix admin role 1 (f004)', '{\"user_id\":11}', 'Rakib Hasan', '2025-11-14 01:44:30'),
(57, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix user role 3 (f003)', '{\"user_id\":10}', 'Rakib Hasan', '2025-11-14 01:44:35'),
(58, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix user role (f002)', '{\"user_id\":9}', 'Rakib Hasan', '2025-11-14 01:44:40'),
(59, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: V3 (U0004)', '{\"user_id\":6}', 'Rakib Hasan', '2025-11-14 01:44:42'),
(60, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Fix user roal (f001)', '{\"user_id\":8}', 'Rakib Hasan', '2025-11-14 01:44:43'),
(61, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: V2 (A0002)', '{\"user_id\":7}', 'Rakib Hasan', '2025-11-14 01:44:45'),
(62, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: V1 (U0003)', '{\"user_id\":5}', 'Rakib Hasan', '2025-11-14 01:44:49'),
(63, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Ajax (AJAX-1763027704)', '{\"user_id\":4}', 'Rakib Hasan', '2025-11-14 01:44:52'),
(64, 'User', 'delete', 'User Deleted', 'Rakib Hasan deleted user: Test (TEST-1762970295)', '{\"user_id\":2}', 'Rakib Hasan', '2025-11-14 01:45:14'),
(65, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 1 (FX001)', '{\"user_id\":\"20\"}', 'Rakib Hasan', '2025-11-14 01:46:29'),
(66, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 2 (FX002)', '{\"user_id\":\"21\"}', 'Rakib Hasan', '2025-11-14 01:46:52'),
(67, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 3 (FX006)', '{\"user_id\":\"22\"}', 'Rakib Hasan', '2025-11-14 01:47:24'),
(68, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 4 (FX004)', '{\"user_id\":\"23\"}', 'Rakib Hasan', '2025-11-14 01:47:49'),
(69, 'Todo', 'update', 'Task Updated', 'Rakib Hasan updated task: Task 1', '{\"todo_id\":9}', 'Rakib Hasan', '2025-11-14 01:54:24'),
(70, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Task 1\' to Ongoing', '{\"todo_id\":9,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-14 02:54:14'),
(71, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Task 1\' to Completed', '{\"todo_id\":9,\"new_status\":\"Completed\"}', 'Rakib Hasan', '2025-11-14 02:54:40'),
(72, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Task 3\' to Cancelled', '{\"todo_id\":11,\"new_status\":\"Cancelled\"}', 'Rakib Hasan', '2025-11-14 02:54:55'),
(73, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Task 2\' to Ongoing', '{\"todo_id\":10,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-14 02:55:25'),
(74, 'Todo', 'status_change', 'Task Status Changed', 'Rakib Hasan changed task \'Task 5\' to Ongoing', '{\"todo_id\":13,\"new_status\":\"Ongoing\"}', 'Rakib Hasan', '2025-11-14 02:55:36'),
(75, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 5 (FX004=5)', '{\"user_id\":\"24\"}', 'Rakib Hasan', '2025-11-16 03:51:29'),
(76, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 7 (FX007)', '{\"user_id\":\"25\"}', 'Rakib Hasan', '2025-11-16 03:52:16'),
(77, 'User', 'add', 'New User Created', 'Rakib Hasan created new user: Fix User 8 (U00558888)', '{\"user_id\":\"26\"}', 'Rakib Hasan', '2025-11-16 03:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `notification_user_status`
--

DROP TABLE IF EXISTS `notification_user_status`;
CREATE TABLE IF NOT EXISTS `notification_user_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_acknowledged` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_notification` (`notification_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_acknowledged` (`is_acknowledged`)
) ENGINE=InnoDB AUTO_INCREMENT=516 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_user_status`
--

INSERT INTO `notification_user_status` (`id`, `notification_id`, `user_id`, `is_read`, `is_acknowledged`, `read_at`, `acknowledged_at`) VALUES
(1, 1, 1, 0, 1, NULL, '2025-11-10 20:48:53'),
(2, 2, 1, 0, 1, NULL, '2025-11-10 20:48:51'),
(3, 3, 1, 0, 0, NULL, NULL),
(4, 4, 1, 0, 1, NULL, '2025-11-11 12:26:51'),
(5, 5, 1, 0, 1, NULL, '2025-11-11 12:26:48'),
(6, 6, 1, 0, 1, NULL, '2025-11-11 12:26:45'),
(7, 7, 1, 0, 1, NULL, '2025-11-11 12:26:43'),
(8, 8, 1, 0, 1, NULL, '2025-11-12 00:19:15'),
(9, 9, 1, 0, 1, NULL, '2025-11-12 00:19:12'),
(10, 10, 1, 0, 0, NULL, NULL),
(12, 10, 3, 0, 0, NULL, NULL),
(13, 11, 1, 0, 0, NULL, NULL),
(15, 11, 3, 0, 0, NULL, NULL),
(16, 12, 1, 0, 0, NULL, NULL),
(18, 12, 3, 0, 0, NULL, NULL),
(19, 13, 1, 0, 0, NULL, NULL),
(21, 13, 3, 0, 0, NULL, NULL),
(22, 14, 1, 0, 0, NULL, NULL),
(24, 14, 3, 0, 0, NULL, NULL),
(25, 15, 1, 0, 0, NULL, NULL),
(27, 15, 3, 0, 0, NULL, NULL),
(28, 16, 1, 0, 0, NULL, NULL),
(30, 16, 3, 0, 0, NULL, NULL),
(31, 17, 1, 0, 0, NULL, NULL),
(33, 17, 3, 0, 0, NULL, NULL),
(34, 18, 1, 0, 0, NULL, NULL),
(36, 18, 3, 0, 0, NULL, NULL),
(37, 19, 1, 1, 0, '2025-11-13 18:26:10', NULL),
(39, 19, 3, 0, 0, NULL, NULL),
(40, 20, 1, 0, 0, NULL, NULL),
(42, 20, 3, 0, 0, NULL, NULL),
(44, 21, 1, 1, 0, '2025-11-13 18:26:18', NULL),
(46, 21, 3, 0, 0, NULL, NULL),
(48, 22, 1, 1, 0, '2025-11-13 18:26:07', NULL),
(50, 22, 3, 0, 0, NULL, NULL),
(53, 23, 1, 0, 0, NULL, NULL),
(55, 23, 3, 0, 0, NULL, NULL),
(58, 24, 1, 1, 1, '2025-11-13 18:26:44', '2025-11-13 18:26:41'),
(60, 24, 3, 0, 0, NULL, NULL),
(63, 25, 1, 0, 0, NULL, NULL),
(65, 25, 3, 0, 0, NULL, NULL),
(68, 26, 1, 0, 0, NULL, NULL),
(70, 26, 3, 0, 0, NULL, NULL),
(73, 27, 1, 0, 0, NULL, NULL),
(75, 27, 3, 0, 0, NULL, NULL),
(78, 28, 1, 0, 0, NULL, NULL),
(80, 28, 3, 0, 0, NULL, NULL),
(83, 29, 1, 0, 0, NULL, NULL),
(85, 29, 3, 0, 0, NULL, NULL),
(90, 30, 1, 0, 0, NULL, NULL),
(92, 30, 3, 0, 0, NULL, NULL),
(97, 31, 1, 0, 0, NULL, NULL),
(99, 31, 3, 0, 0, NULL, NULL),
(104, 32, 1, 0, 0, NULL, NULL),
(106, 32, 3, 0, 0, NULL, NULL),
(111, 33, 1, 0, 0, NULL, NULL),
(113, 33, 3, 0, 0, NULL, NULL),
(122, 34, 1, 0, 0, NULL, NULL),
(124, 34, 3, 0, 0, NULL, NULL),
(133, 35, 1, 0, 0, NULL, NULL),
(135, 35, 3, 0, 0, NULL, NULL),
(144, 36, 1, 0, 0, NULL, NULL),
(146, 36, 3, 0, 0, NULL, NULL),
(155, 37, 1, 0, 0, NULL, NULL),
(157, 37, 3, 0, 0, NULL, NULL),
(166, 38, 1, 0, 0, NULL, NULL),
(168, 38, 3, 0, 0, NULL, NULL),
(177, 39, 1, 0, 0, NULL, NULL),
(179, 39, 3, 0, 0, NULL, NULL),
(188, 40, 1, 0, 0, NULL, NULL),
(190, 40, 3, 0, 0, NULL, NULL),
(199, 41, 1, 0, 0, NULL, NULL),
(201, 41, 3, 0, 0, NULL, NULL),
(210, 42, 1, 0, 0, NULL, NULL),
(212, 42, 3, 0, 0, NULL, NULL),
(221, 43, 1, 0, 0, NULL, NULL),
(223, 43, 3, 0, 0, NULL, NULL),
(232, 44, 1, 0, 0, NULL, NULL),
(234, 44, 3, 0, 0, NULL, NULL),
(243, 45, 1, 0, 0, NULL, NULL),
(245, 45, 3, 0, 0, NULL, NULL),
(254, 46, 1, 0, 0, NULL, NULL),
(256, 46, 3, 0, 0, NULL, NULL),
(268, 47, 1, 0, 0, NULL, NULL),
(270, 47, 3, 0, 0, NULL, NULL),
(281, 48, 1, 0, 0, NULL, NULL),
(283, 48, 3, 0, 0, NULL, NULL),
(295, 49, 1, 0, 0, NULL, NULL),
(297, 49, 3, 0, 0, NULL, NULL),
(310, 50, 1, 0, 0, NULL, NULL),
(312, 50, 3, 0, 0, NULL, NULL),
(324, 51, 1, 0, 0, NULL, NULL),
(326, 51, 3, 0, 0, NULL, NULL),
(337, 52, 1, 0, 0, NULL, NULL),
(339, 52, 3, 0, 0, NULL, NULL),
(350, 53, 1, 0, 0, NULL, NULL),
(352, 53, 3, 0, 0, NULL, NULL),
(362, 54, 1, 0, 0, NULL, NULL),
(364, 54, 3, 0, 0, NULL, NULL),
(373, 55, 1, 0, 0, NULL, NULL),
(375, 55, 3, 0, 0, NULL, NULL),
(384, 56, 1, 0, 0, NULL, NULL),
(386, 56, 3, 0, 0, NULL, NULL),
(394, 57, 1, 0, 0, NULL, NULL),
(396, 57, 3, 0, 0, NULL, NULL),
(403, 58, 1, 0, 0, NULL, NULL),
(405, 58, 3, 0, 0, NULL, NULL),
(411, 59, 1, 0, 0, NULL, NULL),
(413, 59, 3, 0, 0, NULL, NULL),
(418, 60, 1, 0, 0, NULL, NULL),
(420, 60, 3, 0, 0, NULL, NULL),
(424, 61, 1, 0, 0, NULL, NULL),
(426, 61, 3, 0, 0, NULL, NULL),
(429, 62, 1, 0, 0, NULL, NULL),
(431, 62, 3, 0, 0, NULL, NULL),
(433, 63, 1, 0, 0, NULL, NULL),
(435, 63, 3, 0, 0, NULL, NULL),
(436, 64, 1, 0, 0, NULL, NULL),
(437, 64, 3, 0, 0, NULL, NULL),
(438, 65, 1, 0, 0, NULL, NULL),
(439, 65, 3, 0, 0, NULL, NULL),
(440, 65, 20, 0, 0, NULL, NULL),
(441, 66, 1, 0, 0, NULL, NULL),
(442, 66, 3, 0, 0, NULL, NULL),
(443, 66, 20, 0, 0, NULL, NULL),
(444, 66, 21, 0, 0, NULL, NULL),
(445, 67, 1, 0, 0, NULL, NULL),
(446, 67, 3, 0, 0, NULL, NULL),
(447, 67, 20, 0, 0, NULL, NULL),
(448, 67, 21, 0, 0, NULL, NULL),
(449, 67, 22, 0, 0, NULL, NULL),
(450, 68, 1, 0, 0, NULL, NULL),
(451, 68, 3, 0, 0, NULL, NULL),
(452, 68, 20, 0, 0, NULL, NULL),
(453, 68, 21, 0, 0, NULL, NULL),
(454, 68, 22, 0, 0, NULL, NULL),
(455, 68, 23, 0, 0, NULL, NULL),
(456, 69, 1, 0, 0, NULL, NULL),
(457, 69, 3, 0, 0, NULL, NULL),
(458, 69, 20, 0, 0, NULL, NULL),
(459, 69, 21, 0, 0, NULL, NULL),
(460, 69, 22, 0, 0, NULL, NULL),
(461, 69, 23, 0, 0, NULL, NULL),
(462, 70, 1, 0, 0, NULL, NULL),
(463, 70, 3, 0, 0, NULL, NULL),
(464, 70, 20, 0, 0, NULL, NULL),
(465, 70, 21, 0, 0, NULL, NULL),
(466, 70, 22, 0, 0, NULL, NULL),
(467, 70, 23, 0, 0, NULL, NULL),
(468, 71, 1, 0, 0, NULL, NULL),
(469, 71, 3, 0, 0, NULL, NULL),
(470, 71, 20, 0, 0, NULL, NULL),
(471, 71, 21, 0, 0, NULL, NULL),
(472, 71, 22, 0, 0, NULL, NULL),
(473, 71, 23, 0, 0, NULL, NULL),
(474, 72, 1, 0, 0, NULL, NULL),
(475, 72, 3, 0, 0, NULL, NULL),
(476, 72, 20, 0, 0, NULL, NULL),
(477, 72, 21, 0, 0, NULL, NULL),
(478, 72, 22, 0, 0, NULL, NULL),
(479, 72, 23, 0, 0, NULL, NULL),
(480, 73, 1, 0, 0, NULL, NULL),
(481, 73, 3, 0, 0, NULL, NULL),
(482, 73, 20, 0, 0, NULL, NULL),
(483, 73, 21, 0, 0, NULL, NULL),
(484, 73, 22, 0, 0, NULL, NULL),
(485, 73, 23, 0, 0, NULL, NULL),
(486, 74, 1, 0, 0, NULL, NULL),
(487, 74, 3, 0, 0, NULL, NULL),
(488, 74, 20, 0, 0, NULL, NULL),
(489, 74, 21, 0, 0, NULL, NULL),
(490, 74, 22, 0, 0, NULL, NULL),
(491, 74, 23, 0, 0, NULL, NULL),
(492, 75, 1, 0, 0, NULL, NULL),
(493, 75, 3, 0, 0, NULL, NULL),
(494, 75, 20, 0, 0, NULL, NULL),
(495, 75, 21, 0, 0, NULL, NULL),
(496, 75, 22, 0, 0, NULL, NULL),
(497, 75, 23, 0, 0, NULL, NULL),
(498, 75, 24, 0, 0, NULL, NULL),
(499, 76, 1, 0, 0, NULL, NULL),
(500, 76, 3, 0, 0, NULL, NULL),
(501, 76, 20, 0, 0, NULL, NULL),
(502, 76, 21, 0, 0, NULL, NULL),
(503, 76, 22, 0, 0, NULL, NULL),
(504, 76, 23, 0, 0, NULL, NULL),
(505, 76, 24, 0, 0, NULL, NULL),
(506, 76, 25, 0, 0, NULL, NULL),
(507, 77, 1, 0, 0, NULL, NULL),
(508, 77, 3, 0, 0, NULL, NULL),
(509, 77, 20, 0, 0, NULL, NULL),
(510, 77, 21, 0, 0, NULL, NULL),
(511, 77, 22, 0, 0, NULL, NULL),
(512, 77, 23, 0, 0, NULL, NULL),
(513, 77, 24, 0, 0, NULL, NULL),
(514, 77, 25, 0, 0, NULL, NULL),
(515, 77, 26, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `log_type` enum('Info','Warning','Error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Info',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `log_type`, `module`, `action`, `description`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '127.0.0.1', '2025-11-08 20:14:39'),
(2, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '::1', '2025-11-08 20:14:58'),
(3, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '127.0.0.1', '2025-11-09 14:49:10'),
(4, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '::1', '2025-11-09 14:49:25'),
(5, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '127.0.0.1', '2025-11-09 14:51:00'),
(6, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '127.0.0.1', '2025-11-10 14:43:56'),
(7, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '::1', '2025-11-10 14:44:08'),
(8, 'Info', 'User Management', 'Update User', 'Updated user: System (ADMIN-001)', 1, '::1', '2025-11-10 14:46:59'),
(9, 'Info', 'Authentication', 'Login', 'System logged in successfully', 1, '::1', '2025-11-10 16:27:33'),
(10, 'Info', 'User Management', 'Update User', 'Updated user: Rakib (ADMIN-001)', 1, '::1', '2025-11-10 18:42:47'),
(11, 'Info', 'Authentication', 'Logout', 'System Administrator logged out', 1, '::1', '2025-11-10 23:30:10'),
(12, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-10 23:30:19'),
(13, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 01:03:41'),
(14, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 01:04:15'),
(15, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 01:36:31'),
(16, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 02:58:37'),
(17, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 02:58:52'),
(18, 'Info', 'Network', 'Add Network Info', 'Created network info: IP 192.168.12.53', 1, '::1', '2025-11-11 03:00:01'),
(19, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 03:09:05'),
(20, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 11:17:45'),
(21, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 11:17:59'),
(22, 'Info', 'Network', 'Add Network Info', 'Created network info: IP 192.168.12.54', 1, '::1', '2025-11-11 11:18:34'),
(23, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 11:22:40'),
(24, 'Info', 'Network', 'Add Network Info', 'Created network info: IP 192.168.12.58', 1, '::1', '2025-11-11 11:31:14'),
(25, 'Info', 'Tasks', 'Create Task', 'Created task: Form Test Task 11:54:33, assigned to Rakib Hasan', 1, '::1', '2025-11-11 11:54:39'),
(26, 'Info', 'Network', 'Update Network Info', 'Updated network info (IP: 192.168.12.58)', 1, '::1', '2025-11-11 12:25:39'),
(27, 'Info', 'Equipment', 'Add Equipment', 'Created equipment: CCTV1 (145789951801)', 1, '::1', '2025-11-11 13:35:13'),
(28, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 13:47:48'),
(29, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 14:20:05'),
(30, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 16:17:54'),
(31, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '::1', '2025-11-11 17:32:41'),
(32, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-11 21:45:20'),
(33, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-12 00:05:18'),
(34, 'Info', 'Equipment', 'Add Equipment', 'Created equipment: Lo (254565878)', 1, '127.0.0.1', '2025-11-12 00:18:44'),
(35, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-12 11:47:26'),
(36, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-12 17:36:04'),
(37, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-12 21:27:59'),
(38, 'Info', 'User Management', 'Update User', 'Updated user: User (U0002)', 1, '127.0.0.1', '2025-11-12 23:59:27'),
(39, 'Info', 'Authentication', 'Login', 'User logged in successfully', 3, '127.0.0.1', '2025-11-13 00:04:31'),
(40, 'Info', 'User Management', 'Update User', 'Updated user: User (U0002)', 3, '127.0.0.1', '2025-11-13 00:06:14'),
(41, 'Info', 'Tasks', 'Update Task', 'Rakib Hasan updated task: Test Task - 2025-11-11 11:53:42', 1, '127.0.0.1', '2025-11-13 02:54:36'),
(42, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Rakib Test', 1, '127.0.0.1', '2025-11-13 02:55:32'),
(43, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:53:42\' status to Ongoing', 1, '127.0.0.1', '2025-11-13 03:15:41'),
(44, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:53:42\' status to Completed', 1, '127.0.0.1', '2025-11-13 03:15:55'),
(45, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:54:33\' status to Ongoing', 1, '127.0.0.1', '2025-11-13 08:55:39'),
(46, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Form Test Task 11:54:33', 1, '127.0.0.1', '2025-11-13 08:59:12'),
(47, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Test Task 17:51:12', 1, '127.0.0.1', '2025-11-13 09:02:09'),
(48, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test Task - 2025-11-11 11:54:33\' status to Completed', 1, '127.0.0.1', '2025-11-13 09:05:14'),
(49, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Cancel test', 1, '127.0.0.1', '2025-11-13 09:29:34'),
(50, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Cancel test\' status to Cancelled', 1, '127.0.0.1', '2025-11-13 09:30:25'),
(51, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-13 15:54:37'),
(52, 'Info', 'User Management', 'Add User', 'Created user: Ajax (AJAX-1763027704)', 1, '127.0.0.1', '2025-11-13 15:55:11'),
(53, 'Info', 'User Management', 'Add User', 'Created user: V1 (U0003)', 1, '127.0.0.1', '2025-11-13 16:06:07'),
(54, 'Info', 'User Management', 'Update User', 'Updated user: V1 (U0003)', 1, '127.0.0.1', '2025-11-13 16:07:29'),
(55, 'Info', 'User Management', 'Add User', 'Created user: V3 (U0004)', 1, '127.0.0.1', '2025-11-13 16:29:57'),
(56, 'Info', 'User Management', 'Add User', 'Created user: V2 (A0002)', 1, '127.0.0.1', '2025-11-13 16:30:57'),
(57, 'Info', 'User Management', 'Update User', 'Updated user: Rakib (ADMIN-001)', 1, '127.0.0.1', '2025-11-13 18:42:25'),
(58, 'Info', 'User Management', 'Update User', 'Updated user: Rakib (ADMIN-001)', 1, '127.0.0.1', '2025-11-13 18:42:28'),
(59, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Test', 1, '127.0.0.1', '2025-11-13 18:42:52'),
(60, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test\' status to Ongoing', 1, '127.0.0.1', '2025-11-13 18:43:01'),
(61, 'Info', 'User Management', 'Add User', 'Created user: Fix user roal (f001)', 1, '127.0.0.1', '2025-11-13 20:18:55'),
(62, 'Info', 'User Management', 'Add User', 'Created user: Fix user role (f002)', 1, '127.0.0.1', '2025-11-13 20:19:37'),
(63, 'Info', 'User Management', 'Add User', 'Created user: Fix user role 3 (f003)', 1, '127.0.0.1', '2025-11-13 20:20:07'),
(64, 'Info', 'User Management', 'Add User', 'Created user: Fix admin role 1 (f004)', 1, '127.0.0.1', '2025-11-13 20:20:53'),
(65, 'Info', 'User Management', 'Add User', 'Created user: Fix admin role 3 (U0055)', 1, '127.0.0.1', '2025-11-13 20:26:11'),
(66, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: test task', 1, '127.0.0.1', '2025-11-13 22:50:14'),
(67, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Test\' status to Completed', 1, '127.0.0.1', '2025-11-13 22:50:34'),
(68, 'Info', 'Tasks', 'Update Task', 'Rakib Hasan updated task: Test Task - 2025-11-11 11:54:33', 1, '127.0.0.1', '2025-11-13 22:52:47'),
(69, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Rakib Test\' status to Ongoing', 1, '127.0.0.1', '2025-11-13 22:53:18'),
(70, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'test task\' status to Ongoing', 1, '127.0.0.1', '2025-11-13 22:53:31'),
(71, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Rakib Test\' status to Cancelled', 1, '127.0.0.1', '2025-11-13 22:54:31'),
(72, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'test task\' status to Completed', 1, '127.0.0.1', '2025-11-13 22:54:43'),
(73, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Cancel test', 1, '127.0.0.1', '2025-11-13 22:55:46'),
(74, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Rakib Test', 1, '127.0.0.1', '2025-11-13 22:55:48'),
(75, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: test task', 1, '127.0.0.1', '2025-11-13 22:55:52'),
(76, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Test', 1, '127.0.0.1', '2025-11-13 22:55:55'),
(77, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Test Task - 2025-11-11 11:54:33', 1, '127.0.0.1', '2025-11-13 22:55:58'),
(78, 'Warning', 'Tasks', 'Delete Task', 'Rakib Hasan deleted task: Test Task - 2025-11-11 11:53:42', 1, '127.0.0.1', '2025-11-13 22:56:00'),
(79, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Task 1', 1, '127.0.0.1', '2025-11-13 23:21:01'),
(80, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Task 2', 1, '127.0.0.1', '2025-11-13 23:21:34'),
(81, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Task 3', 1, '127.0.0.1', '2025-11-13 23:22:53'),
(82, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Task 4', 1, '127.0.0.1', '2025-11-13 23:23:52'),
(83, 'Info', 'Tasks', 'Add Task', 'Rakib Hasan created task: Task 5', 1, '127.0.0.1', '2025-11-14 01:08:54'),
(84, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1763062560)', 1, '127.0.0.1', '2025-11-14 01:41:51'),
(85, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1763062536)', 1, '127.0.0.1', '2025-11-14 01:41:57'),
(86, 'Info', 'User Management', 'Add User', 'Created user: Fix admin role 30 (U005530)', 1, '127.0.0.1', '2025-11-14 01:42:32'),
(87, 'Info', 'User Management', 'Add User', 'Created user: Fix admin role 30 (U005531)', 1, '127.0.0.1', '2025-11-14 01:43:05'),
(88, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix admin role 30 (U005531)', 1, '127.0.0.1', '2025-11-14 01:43:56'),
(89, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix admin role 30 (U005530)', 1, '127.0.0.1', '2025-11-14 01:44:00'),
(90, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1763062530)', 1, '127.0.0.1', '2025-11-14 01:44:05'),
(91, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1763062515)', 1, '127.0.0.1', '2025-11-14 01:44:09'),
(92, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1763062511)', 1, '127.0.0.1', '2025-11-14 01:44:14'),
(93, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix admin role 3 (U0055)', 1, '127.0.0.1', '2025-11-14 01:44:19'),
(94, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix admin role 1 (f004)', 1, '127.0.0.1', '2025-11-14 01:44:30'),
(95, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix user role 3 (f003)', 1, '127.0.0.1', '2025-11-14 01:44:35'),
(96, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix user role (f002)', 1, '127.0.0.1', '2025-11-14 01:44:40'),
(97, 'Warning', 'User Management', 'Delete User', 'Deleted user: V3 (U0004)', 1, '127.0.0.1', '2025-11-14 01:44:42'),
(98, 'Warning', 'User Management', 'Delete User', 'Deleted user: Fix user roal (f001)', 1, '127.0.0.1', '2025-11-14 01:44:43'),
(99, 'Warning', 'User Management', 'Delete User', 'Deleted user: V2 (A0002)', 1, '127.0.0.1', '2025-11-14 01:44:45'),
(100, 'Warning', 'User Management', 'Delete User', 'Deleted user: V1 (U0003)', 1, '127.0.0.1', '2025-11-14 01:44:49'),
(101, 'Warning', 'User Management', 'Delete User', 'Deleted user: Ajax (AJAX-1763027704)', 1, '127.0.0.1', '2025-11-14 01:44:52'),
(102, 'Warning', 'User Management', 'Delete User', 'Deleted user: Test (TEST-1762970295)', 1, '127.0.0.1', '2025-11-14 01:45:14'),
(103, 'Info', 'User Management', 'Add User', 'Created user: Fix User 1 (FX001)', 1, '127.0.0.1', '2025-11-14 01:46:29'),
(104, 'Info', 'User Management', 'Add User', 'Created user: Fix User 2 (FX002)', 1, '127.0.0.1', '2025-11-14 01:46:52'),
(105, 'Info', 'User Management', 'Add User', 'Created user: Fix User 3 (FX006)', 1, '127.0.0.1', '2025-11-14 01:47:24'),
(106, 'Info', 'User Management', 'Add User', 'Created user: Fix User 4 (FX004)', 1, '127.0.0.1', '2025-11-14 01:47:49'),
(107, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-14 01:48:26'),
(108, 'Info', 'User Management', 'Reset Password', 'Reset password for: Fix User 4 (FX004)', 1, '127.0.0.1', '2025-11-14 01:52:29'),
(109, 'Info', 'Tasks', 'Update Task', 'Rakib Hasan updated task: Task 1', 1, '127.0.0.1', '2025-11-14 01:54:24'),
(110, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Task 1\' status to Ongoing', 1, '127.0.0.1', '2025-11-14 02:54:14'),
(111, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Task 1\' status to Completed', 1, '127.0.0.1', '2025-11-14 02:54:40'),
(112, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Task 3\' status to Cancelled', 1, '127.0.0.1', '2025-11-14 02:54:55'),
(113, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Task 2\' status to Ongoing', 1, '127.0.0.1', '2025-11-14 02:55:25'),
(114, 'Info', 'Tasks', 'Status Change', 'Rakib Hasan changed task \'Task 5\' status to Ongoing', 1, '127.0.0.1', '2025-11-14 02:55:36'),
(115, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-16 03:43:58'),
(116, 'Info', 'Authentication', 'Login', 'Rakib logged in successfully', 1, '127.0.0.1', '2025-11-16 03:45:01'),
(117, 'Info', 'User Management', 'Add User', 'Created user: Fix User 5 (FX004=5)', 1, '127.0.0.1', '2025-11-16 03:51:29'),
(118, 'Info', 'User Management', 'Add User', 'Created user: Fix User 7 (FX007)', 1, '127.0.0.1', '2025-11-16 03:52:16'),
(119, 'Info', 'User Management', 'Add User', 'Created user: Fix User 8 (U00558888)', 1, '127.0.0.1', '2025-11-16 03:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('last_optimization_time', 'NULL', '2025-11-08 20:11:41'),
('notification_refresh', '30', '2025-11-08 20:11:41'),
('records_per_page', '100', '2025-11-08 20:11:41'),
('system_name', 'IT Equipment Manager', '2025-11-08 20:11:41'),
('timezone', 'Asia/Dhaka', '2025-11-08 20:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
CREATE TABLE IF NOT EXISTS `todos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `deadline_date` date NOT NULL,
  `deadline_time` time NOT NULL,
  `created_by` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_backup` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('To Do','Doing','Past Due','Done','Dropped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'To Do',
  PRIMARY KEY (`id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_deadline` (`deadline_date`,`deadline_time`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `todos`
--

INSERT INTO `todos` (`id`, `title`, `description`, `tags`, `priority`, `deadline_date`, `deadline_time`, `created_by`, `display_order`, `created_at`, `updated_at`, `status_backup`, `status`) VALUES
(9, 'Task 1', 'Task 1', NULL, 'Urgent', '2025-11-13', '23:59:00', 1, 9, '2025-11-13 23:21:01', '2025-11-15 01:40:20', 'Done', 'To Do'),
(10, 'Task 2', 'Task 2', 'Task 2', 'High', '2025-11-14', '23:59:00', 1, 10, '2025-11-13 23:21:34', '2025-11-15 01:40:20', 'Doing', 'To Do'),
(11, 'Task 3', 'Task 3', 'Task 3', 'Medium', '2025-11-14', '23:59:00', 1, 11, '2025-11-13 23:22:53', '2025-11-15 01:40:20', 'Dropped', 'To Do'),
(12, 'Task 4', 'Task 4', 'Task 4', 'Low', '2025-11-16', '23:59:00', 1, 12, '2025-11-13 23:23:52', '2025-11-15 01:40:20', 'To Do', 'To Do'),
(13, 'Task 5', 'Task 5', 'Task 5', 'Medium', '2025-11-15', '23:59:00', 1, 13, '2025-11-14 01:08:54', '2025-11-15 01:40:20', 'Doing', 'To Do');

-- --------------------------------------------------------

--
-- Table structure for table `todos_backup_temp`
--

DROP TABLE IF EXISTS `todos_backup_temp`;
CREATE TABLE IF NOT EXISTS `todos_backup_temp` (
  `id` int NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `deadline_date` date NOT NULL,
  `deadline_time` time NOT NULL,
  `created_by` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('To Do','Doing','Past Due','Done','Dropped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'To Do'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `todos_backup_temp`
--

INSERT INTO `todos_backup_temp` (`id`, `title`, `description`, `tags`, `priority`, `deadline_date`, `deadline_time`, `created_by`, `display_order`, `created_at`, `updated_at`, `status`) VALUES
(9, 'Task 1', 'Task 1', NULL, 'Urgent', '2025-11-13', '23:59:00', 1, 0, '2025-11-13 23:21:01', '2025-11-14 03:21:06', 'Done'),
(10, 'Task 2', 'Task 2', 'Task 2', 'High', '2025-11-14', '23:59:00', 1, 0, '2025-11-13 23:21:34', '2025-11-14 03:21:06', 'Doing'),
(11, 'Task 3', 'Task 3', 'Task 3', 'Medium', '2025-11-14', '23:59:00', 1, 0, '2025-11-13 23:22:53', '2025-11-14 03:21:06', 'Dropped'),
(12, 'Task 4', 'Task 4', 'Task 4', 'Low', '2025-11-16', '23:59:00', 1, 0, '2025-11-13 23:23:52', '2025-11-14 03:21:06', 'To Do'),
(13, 'Task 5', 'Task 5', 'Task 5', 'Medium', '2025-11-15', '23:59:00', 1, 0, '2025-11-14 01:08:54', '2025-11-14 03:21:06', 'Doing');

-- --------------------------------------------------------

--
-- Table structure for table `todo_assignments`
--

DROP TABLE IF EXISTS `todo_assignments`;
CREATE TABLE IF NOT EXISTS `todo_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `todo_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`todo_id`,`user_id`),
  KEY `idx_todo_id` (`todo_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `todo_assignments`
--

INSERT INTO `todo_assignments` (`id`, `todo_id`, `user_id`, `assigned_at`) VALUES
(19, 9, 22, '2025-11-14 01:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `todo_comments`
--

DROP TABLE IF EXISTS `todo_comments`;
CREATE TABLE IF NOT EXISTS `todo_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `todo_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_todo_id` (`todo_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_1` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_2` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `profile_photo`, `first_name`, `last_name`, `employee_id`, `username`, `email`, `phone_1`, `phone_2`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'file_6915d2348353e3.76746825.jpeg', 'Rakib', 'Hasan', 'ADMIN-001', 'admin', 'admin@system.local', NULL, NULL, '$2y$10$g6m13v7hTUnTKS12Cri4POgKzSmtJucwpOm9G0pKJTKXdwVxij2P2', 'admin', 'Active', '2025-11-08 20:11:41', '2025-11-13 18:42:28'),
(3, 'file_6914cc96a780b7.94703717.jpeg', 'User', 'Test', 'U0002', NULL, NULL, NULL, NULL, '$2y$10$v6bL8E1DZ7GJKoEMhQvODOoZCl5NRQ8W51LQOiBYlq8GRZv.N0862', 'user', 'Active', '2025-11-12 23:58:23', '2025-11-13 00:06:14'),
(20, NULL, 'Fix User 1', '', 'FX001', 'FX001', NULL, NULL, NULL, '$2y$10$Cb/3Qom1F/Sz9Vzw/wBy7.D12hdwoAbDRR0gnVIgOjWDPs8JqdDsa', 'user', 'Active', '2025-11-14 01:46:29', '2025-11-14 01:46:29'),
(21, NULL, 'Fix User 2', '', 'FX002', 'FX002', NULL, NULL, NULL, '$2y$10$rrc9OxPLr20BrxI.qqGyfeKPwbibTIp73nvPQveh/OTKbNVKdBsUS', 'user', 'Active', '2025-11-14 01:46:52', '2025-11-14 01:46:52'),
(22, NULL, 'Fix User 3', '', 'FX006', 'FX003', NULL, NULL, NULL, '$2y$10$0eDhl6LdrtMnNlycoWG7VOwCoyNsL6KHe8nuJuxr4hH3Ri7I9PVPy', 'user', 'Active', '2025-11-14 01:47:24', '2025-11-14 01:47:24'),
(23, NULL, 'Fix User 4', '', 'FX004', 'FX004', NULL, NULL, NULL, '$2y$10$HIcyixQI9hGfZ8NBcRmRY.MxYj6yA7PxTr4J1L1qhfBCs0bGn1/Vi', 'user', 'Active', '2025-11-14 01:47:49', '2025-11-14 01:52:29'),
(24, NULL, 'Fix User 5', '', 'FX004=5', 'FX005', NULL, NULL, NULL, '$2y$10$u7Jt3lXey.xKgFJAepy9qeEgbxOIHvtvf/ddDhDEqclaQUm7sEKim', 'user', 'Active', '2025-11-16 03:51:29', '2025-11-16 03:51:29'),
(25, NULL, 'Fix User 7', '', 'FX007', 'FX007', NULL, NULL, NULL, '$2y$10$EZkSTHMdXjArkJtkUBeHkOljncMD39P.BEi0wMi3il.DIZ37gSIha', 'user', 'Active', '2025-11-16 03:52:16', '2025-11-16 03:52:16'),
(26, NULL, 'Fix User 8', 'User', 'U00558888', 'User355555', NULL, NULL, NULL, '$2y$10$83NPF7XNVFGVNfnM955xBuhC2J61r.VuvhtEGGbATKFTn9/ZNf.8G', 'user', 'Active', '2025-11-16 03:52:50', '2025-11-16 03:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_revisions`
--

DROP TABLE IF EXISTS `user_revisions`;
CREATE TABLE IF NOT EXISTS `user_revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `change_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_revisions`
--

INSERT INTO `user_revisions` (`id`, `user_id`, `changed_by`, `change_description`, `changed_at`) VALUES
(1, 3, 1, 'Profile updated by Rakib Hasan', '2025-11-12 23:59:27'),
(2, 3, 3, 'Profile updated by User Test', '2025-11-13 00:06:14'),
(8, 1, 1, 'Profile updated by Rakib Hasan', '2025-11-13 18:42:25'),
(9, 1, 1, 'Profile updated by Rakib Hasan', '2025-11-13 18:42:28'),
(17, 20, 1, 'User created by Rakib Hasan', '2025-11-14 01:46:29'),
(18, 21, 1, 'User created by Rakib Hasan', '2025-11-14 01:46:52'),
(19, 22, 1, 'User created by Rakib Hasan', '2025-11-14 01:47:24'),
(20, 23, 1, 'User created by Rakib Hasan', '2025-11-14 01:47:49'),
(21, 23, 1, 'Password reset by admin', '2025-11-14 01:52:29'),
(22, 24, 1, 'User created by Rakib Hasan', '2025-11-16 03:51:29'),
(23, 25, 1, 'User created by Rakib Hasan', '2025-11-16 03:52:16'),
(24, 26, 1, 'User created by Rakib Hasan', '2025-11-16 03:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `warranty_documents`
--

DROP TABLE IF EXISTS `warranty_documents`;
CREATE TABLE IF NOT EXISTS `warranty_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_equipment_id` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipments`
--
ALTER TABLE `equipments`
  ADD CONSTRAINT `equipments_ibfk_1` FOREIGN KEY (`equipment_type_id`) REFERENCES `equipment_types` (`id`),
  ADD CONSTRAINT `equipments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `equipment_custom_values`
--
ALTER TABLE `equipment_custom_values`
  ADD CONSTRAINT `equipment_custom_values_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_custom_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `equipment_type_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_revisions`
--
ALTER TABLE `equipment_revisions`
  ADD CONSTRAINT `equipment_revisions_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_revisions_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `equipment_type_fields`
--
ALTER TABLE `equipment_type_fields`
  ADD CONSTRAINT `equipment_type_fields_ibfk_1` FOREIGN KEY (`equipment_type_id`) REFERENCES `equipment_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `network_info`
--
ALTER TABLE `network_info`
  ADD CONSTRAINT `network_info_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `network_info_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `network_revisions`
--
ALTER TABLE `network_revisions`
  ADD CONSTRAINT `network_revisions_ibfk_1` FOREIGN KEY (`network_id`) REFERENCES `network_info` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `network_revisions_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notification_user_status`
--
ALTER TABLE `notification_user_status`
  ADD CONSTRAINT `notification_user_status_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_user_status_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `todos`
--
ALTER TABLE `todos`
  ADD CONSTRAINT `todos_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `todo_assignments`
--
ALTER TABLE `todo_assignments`
  ADD CONSTRAINT `todo_assignments_ibfk_1` FOREIGN KEY (`todo_id`) REFERENCES `todos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `todo_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `todo_comments`
--
ALTER TABLE `todo_comments`
  ADD CONSTRAINT `todo_comments_ibfk_1` FOREIGN KEY (`todo_id`) REFERENCES `todos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `todo_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_revisions`
--
ALTER TABLE `user_revisions`
  ADD CONSTRAINT `user_revisions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_revisions_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warranty_documents`
--
ALTER TABLE `warranty_documents`
  ADD CONSTRAINT `warranty_documents_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
