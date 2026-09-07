-- Create Database
CREATE DATABASE IF NOT EXISTS `flat_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `flat_management`;

-- Table structure for table `admin`
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `flats`
CREATE TABLE IF NOT EXISTS `flats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `flat_no` VARCHAR(50) NOT NULL,
  `block` VARCHAR(50) NOT NULL,
  `city` ENUM('Chennai', 'Bengaluru', 'Coimbatore', 'Hyderabad', 'Dubai', 'Pune', 'Delhi') NOT NULL DEFAULT 'Chennai',
  `bhk` INT NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Available', 'Booked') NOT NULL DEFAULT 'Available',
  `type` ENUM('Residential', 'Commercial', 'Industrial', 'NRI', 'Ventures') NOT NULL DEFAULT 'Residential',
  `floor` INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `residents` (repurposed as customers)
CREATE TABLE IF NOT EXISTS `residents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `bookings`
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `resident_id` INT NOT NULL,
  `flat_id` INT NOT NULL,
  `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `amount_paid` DECIMAL(12,2) NOT NULL,
  `payment_status` ENUM('Paid', 'Pending') NOT NULL DEFAULT 'Paid',
  FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `notices`
CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `inquiries`
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `settings`
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(50) PRIMARY KEY,
  `key_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `payments`
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT DEFAULT NULL,
  `resident_id` INT NOT NULL,
  `transaction_id` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('Credit Card', 'Net Banking', 'UPI', 'Bank Transfer', 'Cheque') NOT NULL DEFAULT 'Credit Card',
  `payment_status` ENUM('Paid', 'Pending', 'Failed', 'Refunded') NOT NULL DEFAULT 'Paid',
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `invoices`
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `booking_id` INT NOT NULL,
  `resident_id` INT NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL,
  `issued_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Paid', 'Unpaid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Paid',
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `activity_logs`
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_username` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `maintenance`
CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `resident_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `month` VARCHAR(50) NOT NULL,
  `payment_date` DATE DEFAULT NULL,
  `status` ENUM('Paid', 'Unpaid', 'Overdue') NOT NULL DEFAULT 'Unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `complaints`
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `resident_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `status` ENUM('Pending', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `buildings`
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `total_floors` INT NOT NULL DEFAULT 10,
  `total_units` INT NOT NULL DEFAULT 40,
  `status` ENUM('Completed', 'Under Construction', 'Pre-Launch') NOT NULL DEFAULT 'Completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin account (username: admin, password: admin123)
INSERT INTO `admin` (`username`, `password`) VALUES
('admin', '$2y$10$wKxN74bO10d0qU1R36mE7O7mG/xXfWjG3wW.3qH54f260qS/gRjB.')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed default brand name
INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('system_name', 'Jarvis')
ON DUPLICATE KEY UPDATE `key_value`=`key_value`;

-- Seed apartments and properties in different cities with types
INSERT INTO `flats` (`flat_no`, `block`, `city`, `bhk`, `price`, `description`, `image_url`, `status`, `type`, `floor`) VALUES
('101', 'Tower A (Casagrand Elan)', 'Chennai', 3, 145000.00, 'Premium 3 BHK luxury suite with modular kitchen, private balcony, access to swimming pool and health club.', 'images/living.png', 'Available', 'Residential', 1),
('204', 'Block B (Casagrand Woodside)', 'Chennai', 2, 85000.00, 'Comfortable 2 BHK flat situated near public park, featuring spacious rooms and dynamic parking facilities.', 'images/bedroom.png', 'Available', 'Residential', 2),
('C01', 'Casagrand Tech Plaza', 'Bengaluru', 0, 450000.00, 'Premium commercial office floor space equipped with conference units, centralized server rooms, and double lifts.', 'images/living.png', 'Available', 'Commercial', 1),
('C02', 'Commercial Hub', 'Chennai', 0, 380000.00, 'Strategic shopping and corporate suites located in a bustling business district, offering great visibility.', 'images/living.png', 'Available', 'Commercial', 2),
('I04', 'Casagrand Logipark', 'Coimbatore', 0, 620000.00, 'Modern heavy-industry warehouse facility with dock gates, high-capacity loading floors, and heavy security.', 'images/kitchen.png', 'Available', 'Industrial', 0),
('NRI-12', 'Marina Bay Penthouse', 'Dubai', 4, 980000.00, 'Sea-view elite sky penthouse customized for NRI buyers, offering premium custom gold finishes and smart controls.', 'images/living.png', 'Available', 'NRI', 12),
('NRI-02', 'Burj Vista Heights', 'Dubai', 3, 750000.00, 'Exquisite high-rise suite with dynamic view, private terrace, and access to premium health services.', 'images/bedroom.png', 'Available', 'NRI', 2),
('505', 'Tower C (Casagrand Zenith)', 'Bengaluru', 2, 110000.00, 'Elegant 2 BHK luxury apartment close to IT hubs, private garden access and designer bath systems.', 'images/bedroom.png', 'Available', 'Residential', 5),
('102', 'Oasis Venture Phase I', 'Pune', 3, 125000.00, 'Elite township venture block featuring community parklands, continuous backup generators, and clean water feeds.', 'images/kitchen.png', 'Available', 'Ventures', 1);

-- Seed default admin account
INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$wKxN74bO10d0qU1R36mE7O7mG/xXfWjG3wW.3qH54f260qS/gRjB.')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed default customer account
INSERT INTO `residents` (`id`, `name`, `phone`, `email`, `password`) VALUES
(1, 'John Doe', '9876543210', 'john@example.com', '$2y$10$wKxN74bO10d0qU1R36mE7O7mG/xXfWjG3wW.3qH54f260qS/gRjB.')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Seed announcements notices
INSERT INTO `notices` (`title`, `description`) VALUES
('Casagrand Launch Event', 'Join us this weekend for the launch of Casagrand Woodside Phase II in Chennai. Special launch discounts available for booking on-spot!'),
('NRI Investment Seminar', 'Learn about lucrative NRI real estate investment options in Bengaluru and Hyderabad during our online webinar this Friday.');

-- Seed inquiries
INSERT INTO `inquiries` (`name`, `phone`, `city`) VALUES
('Sarah Jenkins', '+91 99998 88887', 'Chennai'),
('Michael Ross', '+91 88887 77776', 'Bengaluru');

-- Seed sample bookings
INSERT INTO `bookings` (`id`, `resident_id`, `flat_id`, `booking_date`, `amount_paid`, `payment_status`) VALUES
(1, 1, 1, NOW() - INTERVAL 5 DAY, 145000.00, 'Paid'),
(2, 1, 6, NOW() - INTERVAL 2 DAY, 980000.00, 'Paid');

-- Seed sample payments
INSERT INTO `payments` (`id`, `booking_id`, `resident_id`, `transaction_id`, `amount`, `payment_method`, `payment_status`, `payment_date`) VALUES
(1, 1, 1, 'TXN-98421045', 145000.00, 'Credit Card', 'Paid', NOW() - INTERVAL 5 DAY),
(2, 2, 1, 'TXN-74102938', 980000.00, 'Net Banking', 'Paid', NOW() - INTERVAL 2 DAY),
(3, NULL, 1, 'TXN-10928374', 110000.00, 'UPI', 'Pending', NOW() - INTERVAL 1 DAY);

-- Seed sample invoices
INSERT INTO `invoices` (`id`, `invoice_no`, `booking_id`, `resident_id`, `subtotal`, `tax_amount`, `discount_amount`, `grand_total`, `issued_date`, `due_date`, `status`) VALUES
(1, 'INV-2026-001', 1, 1, 145000.00, 26100.00, 5000.00, 166100.00, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 10 DAY, 'Paid'),
(2, 'INV-2026-002', 1, 1, 85000.00, 15300.00, 0.00, 100300.00, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 12 DAY, 'Unpaid');

-- Seed sample activity logs
INSERT INTO `activity_logs` (`admin_username`, `action`, `module`, `details`) VALUES
('admin', 'Logged In', 'Authentication', 'Admin logged in successfully from IP 127.0.0.1'),
('admin', 'Added New Flat', 'Inventory', 'Added Flat #101 Tower A (Casagrand Elan) in Chennai'),
('admin', 'Updated Booking', 'Bookings', 'Confirmed booking deposit for Resident #1 (John Doe)'),
('admin', 'Generated Invoice', 'Invoices', 'Created PDF Invoice INV-2026-001 for John Doe');

-- Seed buildings hierarchy
INSERT INTO `buildings` (`name`, `city`, `total_floors`, `total_units`, `status`) VALUES
('Tower A (Casagrand Elan)', 'Chennai', 15, 60, 'Completed'),
('Block B (Casagrand Woodside)', 'Chennai', 10, 40, 'Completed'),
('Tower C (Casagrand Zenith)', 'Bengaluru', 20, 80, 'Completed'),
('Marina Bay Penthouse Tower', 'Dubai', 35, 120, 'Completed'),
('Jarvis Sky Mansions Phase 2', 'Chennai', 25, 100, 'Under Construction');

-- Seed sample maintenance fees
INSERT INTO `maintenance` (`id`, `resident_id`, `amount`, `month`, `payment_date`, `status`) VALUES
(1, 1, 3500.00, 'August 2026', CURDATE() - INTERVAL 2 DAY, 'Paid'),
(2, 1, 3500.00, 'September 2026', NULL, 'Unpaid');

-- Seed sample complaints
INSERT INTO `complaints` (`id`, `resident_id`, `title`, `description`, `category`, `status`) VALUES
(1, 1, 'Elevator Service Check', 'Elevator B making slight squeaking noise on floor 5.', 'Lift Maintenance', 'In Progress'),
(2, 1, 'Balcony Light Fixture Replace', 'Balcony LED fixture replacement requested.', 'Electrical', 'Pending');



