CREATE DATABASE IF NOT EXISTS parish_registry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parish_registry;

CREATE TABLE staff_users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(150) NOT NULL, username VARCHAR(80) NOT NULL UNIQUE,
 email VARCHAR(150) UNIQUE, password_hash VARCHAR(255) NOT NULL, role ENUM('admin','staff','viewer') NOT NULL DEFAULT 'staff',
 is_active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE persons (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, first_name VARCHAR(100) NOT NULL, middle_name VARCHAR(100), last_name VARCHAR(100) NOT NULL,
 gender ENUM('Male','Female') NOT NULL, date_of_birth DATE, father_name VARCHAR(200), mother_maiden_name VARCHAR(200), spouse_name VARCHAR(200) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_person_name (last_name, first_name), INDEX idx_person_dob (date_of_birth)
);
CREATE TABLE sacramental_records (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, person_id INT UNSIGNED NOT NULL, sacrament_type ENUM('Baptism','Communion','Confirmation','Marriage','Death') NOT NULL,
 event_date DATE NOT NULL, book_number VARCHAR(20) NOT NULL, page_number VARCHAR(20) NOT NULL, line_number VARCHAR(20) NOT NULL,
 minister_name VARCHAR(200), sponsors TEXT, margin_notes TEXT, source ENUM('manual_entry','bulk_import') NOT NULL DEFAULT 'manual_entry',
 created_by INT UNSIGNED, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES staff_users(id) ON DELETE SET NULL,
 UNIQUE KEY uq_book_page_line_sacrament (sacrament_type, book_number, page_number, line_number), INDEX idx_sacrament_type (sacrament_type), INDEX idx_event_date (event_date)
);
CREATE TABLE certificate_issuance_logs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sacramental_record_id INT UNSIGNED NOT NULL, requestor_name VARCHAR(200) NOT NULL, purpose VARCHAR(150) NOT NULL,
 issued_by INT UNSIGNED NOT NULL, issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, pdf_path VARCHAR(255) NULL,
 FOREIGN KEY (sacramental_record_id) REFERENCES sacramental_records(id) ON DELETE RESTRICT, FOREIGN KEY (issued_by) REFERENCES staff_users(id) ON DELETE RESTRICT, INDEX idx_issued_at (issued_at)
);
CREATE TABLE import_batches (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL, uploaded_by INT UNSIGNED NOT NULL, total_rows INT UNSIGNED NOT NULL,
 valid_rows INT UNSIGNED NOT NULL, committed_rows INT UNSIGNED NOT NULL DEFAULT 0, status ENUM('staged','committed','discarded') NOT NULL DEFAULT 'staged',
 uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (uploaded_by) REFERENCES staff_users(id) ON DELETE RESTRICT
);
CREATE TABLE import_staged_rows (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id INT UNSIGNED NOT NULL, row_number INT UNSIGNED NOT NULL, payload JSON NOT NULL, validation_errors JSON NOT NULL,
 FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);
