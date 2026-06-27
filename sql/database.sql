-- Samangile Energy Solutions Database
CREATE DATABASE IF NOT EXISTS samangile_energy;
USE samangile_energy;

-- Chat Messages Table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'replied', 'archived') DEFAULT 'pending',
    INDEX idx_email (sender_email),
    INDEX idx_created (created_at)
);

-- Quote Requests Table
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    service_type ENUM('solar', 'wind', 'hydro', 'consulting') NOT NULL,
    estimated_amount DECIMAL(12, 2),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'quoted', 'accepted', 'rejected') DEFAULT 'pending',
    quote_amount DECIMAL(12, 2),
    quoted_at TIMESTAMP NULL,
    INDEX idx_email (client_email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Admin Users Table (for future dashboard)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'support') DEFAULT 'support',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Service Packages Table
CREATE TABLE IF NOT EXISTS service_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_type ENUM('solar', 'wind', 'hydro', 'consulting') NOT NULL,
    base_price DECIMAL(12, 2) NOT NULL,
    description TEXT,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Responses Log Table
CREATE TABLE IF NOT EXISTS response_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_message_id INT,
    quote_request_id INT,
    response_text TEXT,
    response_type ENUM('auto', 'manual') DEFAULT 'auto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (quote_request_id) REFERENCES quote_requests(id) ON DELETE CASCADE
);

-- Insert sample data

-- Sample admin user (password: admin123 - hashed)
INSERT INTO admin_users (username, email, password, role) VALUES 
('admin', 'admin@samangileenergy.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'admin');

-- Sample service packages
INSERT INTO service_packages (service_name, service_type, base_price, description, features) VALUES 
('Residential Solar Package', 'solar', 15000.00, 'Complete solar installation for homes', '["5kW capacity", "25-year warranty", "Free maintenance"]'),
('Commercial Wind Energy', 'wind', 50000.00, 'Wind turbine installation for businesses', '["20kW capacity", "30-year warranty", "24/7 monitoring"]'),
('Hydroelectric Consulting', 'hydro', 5000.00, 'Expert consultation for hydro projects', '["Site assessment", "Feasibility study", "Implementation plan"]'),
('Energy Audit Service', 'consulting', 2000.00, 'Complete energy efficiency audit', '["Building assessment", "Usage analysis", "Recommendations"]');

-- Sample chat message
INSERT INTO chat_messages (sender_name, sender_email, message, status) VALUES 
('John Doe', 'john@example.com', 'Hello, I need help with my energy bill', 'pending');

-- Sample quote request
INSERT INTO quote_requests (client_name, client_email, phone_number, service_type, estimated_amount, status) VALUES 
('Jane Smith', 'jane@example.com', '1234567890', 'solar', 15000.00, 'pending');
