CREATE TABLE subadmins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for subadmin classification change requests
CREATE TABLE IF NOT EXISTS subadmin_classification_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subadmin_id INT NOT NULL,
    requested_software_classification VARCHAR(100) DEFAULT NULL,
    requested_hardware_classification VARCHAR(100) DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decision_date TIMESTAMP NULL DEFAULT NULL,
    admin_comment VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (subadmin_id) REFERENCES subadmins(id) ON DELETE CASCADE
); 