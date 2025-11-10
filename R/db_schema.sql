-- File: database_schema.sql
-- Purpose: Complete database schema for IT Equipment Manager System
-- All tables with proper indexes, foreign keys, and constraints

CREATE DATABASE IF NOT EXISTS it_equipment_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_equipment_manager;

-- =====================================================
-- USERS MODULE TABLES
-- =====================================================

-- Main users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_photo VARCHAR(255) DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    username VARCHAR(50) DEFAULT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL UNIQUE,
    phone_1 VARCHAR(20) DEFAULT NULL,
    phone_2 VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User revision history
CREATE TABLE user_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    changed_by INT NOT NULL,
    change_description TEXT NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts tracking
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_identifier VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_identifier_ip (login_identifier, ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EQUIPMENT MODULE TABLES
-- =====================================================

-- Equipment types master table
CREATE TABLE equipment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default equipment types
INSERT INTO equipment_types (type_name, is_custom) VALUES
('Desktop PC', FALSE),
('Laptop', FALSE),
('Monitor', FALSE),
('UPS', FALSE),
('Web Camera', FALSE),
('SSD', FALSE),
('HDD', FALSE),
('RAM', FALSE),
('Printer', FALSE),
('Scanner', FALSE),
('Network Switch', FALSE),
('WiFi Router', FALSE),
('Server', FALSE),
('KVM', FALSE),
('Projector', FALSE),
('Speaker / Headphones', FALSE),
('CCTV Camera', FALSE),
('NVR / DVR', FALSE);

-- Type-based field definitions
CREATE TABLE equipment_type_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_type_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'textarea', 'select', 'multiple') NOT NULL DEFAULT 'text',
    field_options TEXT DEFAULT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id) ON DELETE CASCADE,
    INDEX idx_type_id (equipment_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert type-based fields
INSERT INTO equipment_type_fields (equipment_type_id, field_name, field_type, is_required, display_order) VALUES
-- Desktop PC fields
(1, 'Motherboard', 'text', FALSE, 1),
(1, 'CPU', 'text', FALSE, 2),
(1, 'RAM', 'multiple', FALSE, 3),
(1, 'SSD', 'multiple', FALSE, 4),
(1, 'HDD', 'multiple', FALSE, 5),
(1, 'GPU', 'text', FALSE, 6),
(1, 'OS', 'text', FALSE, 7),
(1, 'Monitor', 'text', FALSE, 8),
-- Laptop fields
(2, 'Motherboard', 'text', FALSE, 1),
(2, 'CPU', 'text', FALSE, 2),
(2, 'RAM', 'multiple', FALSE, 3),
(2, 'SSD', 'multiple', FALSE, 4),
(2, 'HDD', 'multiple', FALSE, 5),
(2, 'GPU', 'text', FALSE, 6),
(2, 'OS', 'text', FALSE, 7),
-- Monitor fields
(3, 'Display Size', 'text', FALSE, 1),
(3, 'Color', 'text', FALSE, 2),
(3, 'Ports', 'text', FALSE, 3),
-- UPS fields
(4, 'Battery Capacity', 'text', FALSE, 1),
-- SSD/HDD fields
(6, 'Storage Capacity', 'text', FALSE, 1),
(7, 'Storage Capacity', 'text', FALSE, 1),
-- Printer fields
(9, 'Output Color', 'select', FALSE, 1),
-- Network Switch fields
(11, 'Type', 'text', FALSE, 1),
(11, 'Ports', 'text', FALSE, 2),
(11, 'Data Transmission Rate', 'text', FALSE, 3);

-- Main equipments table
CREATE TABLE equipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    equipment_type_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model_number VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    floor_no VARCHAR(50) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    assigned_to VARCHAR(100) DEFAULT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    status ENUM('In Use', 'Available', 'Under Repair', 'Retired') NOT NULL DEFAULT 'Available',
    condition_status ENUM('New', 'Good', 'Needs Service', 'Damaged') NOT NULL DEFAULT 'Good',
    seller_company VARCHAR(255) DEFAULT NULL,
    purchase_date DATE DEFAULT NULL,
    warranty_expiry_date DATE DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    custom_label_1 VARCHAR(100) DEFAULT NULL,
    custom_value_1 TEXT DEFAULT NULL,
    custom_label_2 VARCHAR(100) DEFAULT NULL,
    custom_value_2 TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_serial_number (serial_number),
    INDEX idx_equipment_type (equipment_type_id),
    INDEX idx_status (status),
    INDEX idx_condition (condition_status),
    INDEX idx_warranty_expiry (warranty_expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warranty documents
CREATE TABLE warranty_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    INDEX idx_equipment_id (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment custom field values (for type-based fields)
CREATE TABLE equipment_custom_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT NOT NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES equipment_type_fields(id) ON DELETE CASCADE,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_field_id (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment revision history
CREATE TABLE equipment_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    changed_by INT NOT NULL,
    change_description TEXT NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NETWORK MODULE TABLES
-- =====================================================

CREATE TABLE network_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    mac_address VARCHAR(17) DEFAULT NULL,
    cable_number VARCHAR(50) DEFAULT NULL,
    patch_panel_number VARCHAR(50) DEFAULT NULL,
    patch_panel_port VARCHAR(50) DEFAULT NULL,
    patch_panel_location VARCHAR(255) DEFAULT NULL,
    switch_number VARCHAR(50) DEFAULT NULL,
    switch_port VARCHAR(50) DEFAULT NULL,
    switch_location VARCHAR(255) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_mac_address (mac_address),
    INDEX idx_equipment_id (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Network info revision history
CREATE TABLE network_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_id INT NOT NULL,
    changed_by INT NOT NULL,
    change_description TEXT NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES network_info(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_network_id (network_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TODO MODULE TABLES
-- =====================================================

CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
    status ENUM('Assigned', 'Ongoing', 'Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Assigned',
    deadline_date DATE NOT NULL,
    deadline_time TIME NOT NULL,
    created_by INT NOT NULL,
    display_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_deadline (deadline_date, deadline_time),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Todo assignments (many-to-many)
CREATE TABLE todo_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    todo_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (todo_id, user_id),
    INDEX idx_todo_id (todo_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Todo comments/activity log
CREATE TABLE todo_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    todo_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_todo_id (todo_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS MODULE TABLES
-- =====================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Equipment', 'Network', 'User', 'Todo', 'System', 'Warranty') NOT NULL,
    event ENUM('add', 'update', 'delete', 'assign', 'unassign', 'status_change', 'role_change', 'expiring', 'expired') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    context_json TEXT DEFAULT NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user notification status
CREATE TABLE notification_user_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    read_at DATETIME DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_notification (notification_id, user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_is_acknowledged (is_acknowledged)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM SETTINGS & TOOLS TABLES
-- =====================================================

CREATE TABLE system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('system_name', 'IT Equipment Manager'),
('timezone', 'Asia/Dhaka'),
('records_per_page', '100'),
('notification_refresh', '30'),
('last_optimization_time', 'NULL');

-- System logs
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('Info', 'Warning', 'Error') NOT NULL DEFAULT 'Info',
    module VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_type (log_type),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CREATE DEFAULT ADMIN USER
-- =====================================================
-- Note: You need to generate the password hash on YOUR server
-- Run this PHP code to generate hash:
-- <?php echo password_hash('Admin@123', PASSWORD_DEFAULT); ?>
-- Then replace the hash below with YOUR generated hash

-- For now, create admin without password (you'll update it via fix_admin.php)
INSERT INTO users (first_name, last_name, employee_id, username, email, password, role, status) 
VALUES ('System', 'Administrator', 'ADMIN-001', 'admin', 'admin@system.local', 
        'TEMP_PASSWORD_UPDATE_REQUIRED', 'admin', 'Active');

-- IMPORTANT: After importing this SQL, run one of these:
-- Option 1: Run fix_admin.php to generate proper password hash
-- Option 2: Run this SQL with YOUR server's generated hash:
--          UPDATE users SET password = 'YOUR_HASH_HERE' WHERE employee_id = 'ADMIN-001';