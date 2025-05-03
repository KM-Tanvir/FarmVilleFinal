-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 30, 2025 at 05:26 PM
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
-- Database: `farmville`
--

-- --------------------------------------------------------

--
-- Table structure for table `agricultureofficer_t`
--

CREATE TABLE `agricultureofficer_t` (
  `OfficerID` int(7) NOT NULL,
  `OfficerName` varchar(20) NOT NULL,
  `Address` varchar(50) NOT NULL,
  `Contact` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_t`
--

CREATE TABLE `customer_t` (
  `CustomerID` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `address_line1` varchar(100) DEFAULT NULL,
  `address_line2` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_t`
--

INSERT INTO `customer_t` (`first_name`, `last_name`, `email`, `phone`, `password_hash`, `address_line1`, `address_line2`, `city`) VALUES
('Sinthia', 'Rahman', 'sinthia@example.com', '01234567890', 'hashed_password_here', '123 Sunshine Street', 'Apt 4B', 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_t`
--

CREATE TABLE `farmer_t` (
  `FarmerID` int(7) NOT NULL,
  `FarmerNamer` varchar(20) NOT NULL,
  `Address` varchar(50) NOT NULL,
  `Contact` int(11) NOT NULL,
  PRIMARY KEY (`FarmerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_t`
--

INSERT INTO `farmer_t` (`FarmerID`, `FarmerNamer`, `Address`, `Contact`) VALUES
(1, 'Rahim', 'SS Road,Sirajganj', 0),
(2, 'Karim', 'Satmatha,Bagura', 0),
(3, 'Jolil', 'Saurapara,Mirpur', 0),
(5, 'Jobbar', 'Gazipur Chourasta,Gazipur', 0),
(7, 'Hashem', 'Tangail Bazar,Tangail', 0);

-- --------------------------------------------------------

--
-- Table structure for table `harvest_t`
--

CREATE TABLE `harvest_t` (
  `BatchNo` int(8) NOT NULL,
  `Harvest_Date` date NOT NULL,
  `Yield(per acore)` int(20) NOT NULL,
  `Acreage` int(50) NOT NULL,
  `Cost(per kg)` decimal(7,2) NOT NULL,
  `Location` varchar(20) NOT NULL,
  `FarmerID` int(10) NOT NULL,
  `VendorID` int(10) NOT NULL,
  PRIMARY KEY (`BatchNo`),
  KEY `fk07` (`FarmerID`),
  KEY `fk08` (`VendorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_t`
--

CREATE TABLE `product_t` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(50) NOT NULL,
  `product_type` varchar(20) NOT NULL,
  `variety` varchar(20) DEFAULT NULL,
  `seasonality` enum('In Season','Off Season','Peak Season','Year-round') NOT NULL,
  `current_price` decimal(10,2) NOT NULL,
  `stock_status` enum('In Stock','Low Stock','Almost Gone') NOT NULL,
  `last_updated` date NOT NULL,
  `delivery_estimate` varchar(50) DEFAULT NULL,
  `bulk_discount` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_t`
--

INSERT INTO `product_t` (
  `product_id`, 
  `product_name`, 
  `product_type`, 
  `variety`, 
  `seasonality`, 
  `current_price`, 
  `stock_status`, 
  `last_updated`, 
  `delivery_estimate`, 
  `bulk_discount`
) VALUES
(1, 'Coarse Rice', 'Grain', 'Local', 'In Season', 53.00, 'In Stock', '2025-04-17', '2-3 Days', 'Available'),
(2, 'Fine Rice', 'Grain', 'Premium', 'In Season', 67.00, 'Low Stock', '2025-04-17', '3 Days', 'Available'),
(3, 'Wheat Flour (Atta)', 'Flour', 'Standard', 'In Season', 64.00, 'In Stock', '2025-04-17', '1-2 Days', 'Contact for Offer'),
(4, 'Fine Wheat Flour (Maida)', 'Flour', 'Refined', 'In Season', 54.00, 'In Stock', '2025-04-17', '2-3 Days', 'Available'),
(5, 'Lentils (Masoor dal)', 'Pulse', 'Split', 'In Season', 130.00, 'Low Stock', '2025-04-17', '2 Days', 'Contact for Offer'),
(6, 'Green grams (Moong dal)', 'Pulse', 'Whole', 'Peak Season', 120.00, 'In Stock', '2025-04-17', '2-3 Days', 'Available'),
(7, 'Soybean Oil', 'Oil', 'Refined', 'Year-round', 180.00, 'Almost Gone', '2025-04-17', '3-4 Days', 'Limited Offer'),
(8, 'Mustard Oil', 'Oil', 'Cold Pressed', 'Year-round', 230.00, 'Low Stock', '2025-04-17', '3 Days', 'Contact for Offer'),
(9, 'Potatoes', 'Vegetable', 'Local', 'In Season', 35.00, 'In Stock', '2025-04-17', '1-2 Days', 'Available'),
(10, 'Onions', 'Vegetable', 'Red', 'Off Season', 110.00, 'Almost Gone', '2025-04-17', '2-4 Days', 'Not Available'),
(11, 'Garlic', 'Vegetable', 'Local', 'Off Season', 220.00, 'Low Stock', '2025-04-17', '3 Days', 'Contact for Offer');

-- --------------------------------------------------------

--
-- Table structure for table `order_t`
--

CREATE TABLE `order_t` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_status` enum('Pending','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `harvest_date` date DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_t`
--

INSERT INTO `order_t` (`CustomerID`, `total_amount`, `delivery_fee`, `payment_method`, `delivery_address`, `order_status`, `harvest_date`) VALUES
(1, 150.00, 10.00, 'Credit Card', '123 Main St, Cityville', 'Delivered', '2023-05-15'),
(1, 75.50, 5.00, 'PayPal', '456 Oak Ave, Townsville', 'Pending', '2023-05-20'),
(1, 200.25, 15.00, 'Bank Transfer', '789 Pine Rd, Villageton', 'Pending', '2023-05-25');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_id`, `product_id`, `qty_kg`, `price_per_kg`) VALUES
(1, 1, 5.00, 53.00),
(1, 2, 3.00, 67.00),
(2, 3, 2.50, 64.00),
(3, 1, 8.00, 53.00),
(3, 2, 2.00, 67.00);

-- --------------------------------------------------------

--
-- Table structure for table `storage_t`
--

CREATE TABLE `storage_t` (
  `Location` varchar(50) NOT NULL,
  `StorageNo` int(12) NOT NULL,
  `Capacity(Ton)` int(20) NOT NULL,
  `Temperature(C)` int(5) NOT NULL,
  PRIMARY KEY (`StorageNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage_t`
--

INSERT INTO `storage_t` (`Location`, `StorageNo`, `Capacity(Ton)`, `Temperature(C)`) VALUES
('ECB Chottor,Dhaka', 101, 100, 22),
('Shaheb Bazar', 103, 50, 21);

-- --------------------------------------------------------

--
-- Table structure for table `storage_unit`
--

CREATE TABLE `storage_unit` (
  `storage_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `store_date` date DEFAULT NULL,
  `collection_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`storage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage_unit`
--

INSERT INTO `storage_unit` (`storage_id`, `product_id`, `product_name`, `type`, `location`, `quantity`, `store_date`, `collection_date`, `notes`, `created_at`) VALUES
(33, 1, 'Rice', 'Grain', 'Rajshahi', 500.00, NULL, NULL, '', '2025-04-30 00:56:30'),
(35, 0, 'rice', 'Grain', 'Khustia', 20500.00, NULL, NULL, '', '2025-04-30 00:56:30'),
(37, 9, 'Mango', 'Fruit', 'Dhaka', 18000.00, NULL, NULL, '', '2025-04-30 00:56:30'),
(38, 1111, 'onion', 'vegetabl', 'Merul Badda', 21000.00, NULL, NULL, '', '2025-04-30 00:56:30'),
(41, 11, 'Potato', 'Vegetable', 'Kuril', 23000.00, NULL, NULL, '', '2025-04-30 00:56:30'),
(42, 0, 'rice', 'Grain', 'khulna', 7000.00, '2025-04-10', NULL, '', '2025-04-30 00:56:30'),
(43, 0, 'onion', 'vegetable', 'Merul Badda', -17000.00, '2025-04-30', '2025-04-17', 'Outgoing stock to vendor', '2025-04-30 00:56:30'),
(44, 0, 'mango', 'fruit', 'Dhaka', -7000.00, '2025-04-30', '2025-04-26', 'Outgoing stock to vendor', '2025-04-30 00:56:30'),
(45, 0, 'potato', 'vegetable', 'Bhola', 7800.00, '2025-04-21', NULL, '', '2025-04-30 00:56:30'),
(46, 101, 'Rice', 'QMT', 'Dhaka', 5000.00, '2025-04-30', NULL, 'Morning harvest', '2025-04-30 01:52:06'),
(47, 102, 'Wheat', 'QMT', 'Chittagong', 3000.00, '2025-04-30', NULL, 'Afternoon collection', '2025-04-30 01:52:06'),
(53, 0, 'rice', 'grain', 'Rajshahi', 7000.00, '2025-04-21', NULL, '', '2025-04-30 02:26:54'),
(54, 0, 'rice', 'grain', 'Khustia', -19000.00, '2025-04-30', '2025-04-21', 'Outgoing stock to vendor', '2025-04-30 02:27:47'),
(55, 0, 'potato', 'vegetable', 'Khustia', 2000.00, '2025-04-28', NULL, 'added', '2025-04-30 02:32:01'),
(56, 99, 'apple', 'fruits', 'Badd', 5000.00, NULL, NULL, '', '2025-04-30 02:42:45'),
(57, 0, 'rice', 'grain', 'Khustia', 5000.00, '2025-04-30', NULL, '', '2025-04-30 02:44:00'),
(58, 0, 'potato', 'vegetable', 'Kuril', -10000.00, '2025-04-30', '2025-04-30', 'Outgoing stock to vendor', '2025-04-30 02:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_t`
--

CREATE TABLE `vendor_t` (
  `VendorID` int(7) NOT NULL,
  `Name` varchar(20) NOT NULL,
  `Address` varchar(50) NOT NULL,
  `Contact` int(7) NOT NULL,
  PRIMARY KEY (`VendorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_t`
--

INSERT INTO `vendor_t` (`VendorID`, `Name`, `Address`, `Contact`) VALUES
(1, 'Asif', 'Block-D,Raod - 01,Basundahra R/A', 0),
(2, 'Raju', 'Notun Bazar', 0),
(3, 'Rafi', 'Golap Gram,.Savar', 1738573621);

--
-- Indexes for dumped tables
--

--
-- Foreign keys for table `harvest_t`
--
ALTER TABLE `harvest_t`
  ADD CONSTRAINT `harvest_t_ibfk_1` FOREIGN KEY (`FarmerID`) REFERENCES `farmer_t` (`FarmerID`),
  ADD CONSTRAINT `harvest_t_ibfk_2` FOREIGN KEY (`VendorID`) REFERENCES `vendor_t` (`VendorID`);

--
-- Foreign keys for table `order_t`
--
ALTER TABLE `order_t`
  ADD CONSTRAINT `order_t_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer_t` (`CustomerID`);

--
-- Foreign keys for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_t` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product_t` (`product_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;