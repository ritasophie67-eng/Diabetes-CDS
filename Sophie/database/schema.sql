-- =====================================================================
-- Clinical Decision-Support & Self-Management System for Diabetes Mellitus
-- CASE STUDY: Sir Albert Cook Hospital, Sentema Road, Kampala, Uganda
-- Presenter: Alaba Rita Sophie | Supervisor: Mr. Tobias Kakooza
-- Database: diabetes_cdss (MySQL 8.x / MariaDB compatible)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `diabetes_cdss` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `diabetes_cdss`;

-- 1. USERS (Authentication & Role Management)
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('patient', 'clinician', 'admin') NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CLINICIANS (Staff Profile linked to Users)
CREATE TABLE IF NOT EXISTS `clinicians` (
    `clinician_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `specialization` VARCHAR(100) NOT NULL,
    `license_no` VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT `fk_clinicians_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PATIENTS (Patient Demographics linked to User and assigned Clinician)
CREATE TABLE IF NOT EXISTS `patients` (
    `patient_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `clinician_id` INT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patients_clinician` FOREIGN KEY (`clinician_id`) 
        REFERENCES `clinicians` (`clinician_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PATIENT THRESHOLDS (Doctor-configured Target Ranges per Patient)
CREATE TABLE IF NOT EXISTS `patient_thresholds` (
    `threshold_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL UNIQUE,
    `target_fasting_min` DECIMAL(5,2) DEFAULT 80.00,
    `target_fasting_max` DECIMAL(5,2) DEFAULT 130.00,
    `target_postprandial_max` DECIMAL(5,2) DEFAULT 180.00,
    `hypo_threshold` DECIMAL(5,2) DEFAULT 70.00,
    `severe_hypo_threshold` DECIMAL(5,2) DEFAULT 54.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_thresholds_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. GLUCOSE READINGS (Daily Patient Self-Monitoring Logs)
CREATE TABLE IF NOT EXISTS `glucose_readings` (
    `reading_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `glucose_value` DECIMAL(5,2) NOT NULL,
    `meal_context` ENUM('fasting', 'pre_meal', 'post_meal', 'bedtime', 'nocturnal') NOT NULL,
    `symptoms` VARCHAR(255) NULL,
    `logged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_readings_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
    INDEX `idx_patient_logged` (`patient_id`, `logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CLINICAL ALERTS (Automated CDS Triggered Notifications)
CREATE TABLE IF NOT EXISTS `clinical_alerts` (
    `alert_id` INT AUTO_INCREMENT PRIMARY KEY,
    `reading_id` INT NULL,
    `patient_id` INT NOT NULL,
    `clinician_id` INT NULL,
    `risk_level` ENUM('low', 'moderate', 'high', 'critical') NOT NULL,
    `alert_message` TEXT NOT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL,
    CONSTRAINT `fk_alerts_reading` FOREIGN KEY (`reading_id`) 
        REFERENCES `glucose_readings` (`reading_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_alerts_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_alerts_clinician` FOREIGN KEY (`clinician_id`) 
        REFERENCES `clinicians` (`clinician_id`) ON DELETE SET NULL,
    INDEX `idx_patient_risk` (`patient_id`, `risk_level`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. VITALS (Pre-Prescription Clinical Vitals Verification)
CREATE TABLE IF NOT EXISTS `vitals` (
    `vital_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `recorded_by_user_id` INT NOT NULL,
    `systolic_bp` INT NOT NULL,
    `diastolic_bp` INT NOT NULL,
    `heart_rate` INT NOT NULL,
    `weight_kg` DECIMAL(5,2) NULL,
    `temperature_c` DECIMAL(4,2) NULL,
    `notes` VARCHAR(255) NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vitals_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vitals_recorder` FOREIGN KEY (`recorded_by_user_id`) 
        REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PRESCRIPTIONS (Clinician Issued Prescriptions)
CREATE TABLE IF NOT EXISTS `prescriptions` (
    `prescription_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `clinician_id` INT NOT NULL,
    `medication_name` VARCHAR(100) NOT NULL,
    `dosage` VARCHAR(50) NOT NULL,
    `frequency` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `status` ENUM('active', 'completed', 'discontinued') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prescriptions_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prescriptions_clinician` FOREIGN KEY (`clinician_id`) 
        REFERENCES `clinicians` (`clinician_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. AUDIT LOGS (Immutable System Activity Trail)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `target_table` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL,
    INDEX `idx_audit_action` (`target_table`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- INITIAL SEED DATA (For Academic Demonstration & Initial Login)
-- Default Password for all initial accounts: "Password123!"
-- Hash generated via password_hash('Password123!', PASSWORD_BCRYPT)
-- =====================================================================

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `email`) VALUES
(1, 'admin', '$2y$10$idHHkPeZdZEvYMgckK2vT.tvqoAgoA9keR.3zRSJ67Qu/7YkB3KSy', 'admin', 'admin@sir-albert-cook.org'),
(2, 'dr_kakooza', '$2y$10$idHHkPeZdZEvYMgckK2vT.tvqoAgoA9keR.3zRSJ67Qu/7YkB3KSy', 'clinician', 'tobias.kakooza@sir-albert-cook.org'),
(3, 'dr_namubiru', '$2y$10$idHHkPeZdZEvYMgckK2vT.tvqoAgoA9keR.3zRSJ67Qu/7YkB3KSy', 'clinician', 'mary.namubiru@sir-albert-cook.org'),
(4, 'sophie_patient', '$2y$10$idHHkPeZdZEvYMgckK2vT.tvqoAgoA9keR.3zRSJ67Qu/7YkB3KSy', 'patient', 'sophie.alaba@isbat.ac.ug'),
(5, 'john_mukasa', '$2y$10$idHHkPeZdZEvYMgckK2vT.tvqoAgoA9keR.3zRSJ67Qu/7YkB3KSy', 'patient', 'john.mukasa@example.com')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed Clinicians
INSERT INTO `clinicians` (`clinician_id`, `user_id`, `full_name`, `specialization`, `license_no`) VALUES
(1, 2, 'Dr. Tobias Kakooza', 'Endocrinology & Diabetology', 'UMDPC-2018-0492'),
(2, 3, 'Dr. Mary Namubiru', 'Internal Medicine', 'UMDPC-2020-1184')
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

-- Seed Patients
INSERT INTO `patients` (`patient_id`, `user_id`, `clinician_id`, `full_name`, `date_of_birth`, `gender`, `phone`) VALUES
(1, 4, 1, 'Alaba Rita Sophie', '2001-08-14', 'female', '+256701234567'),
(2, 5, 1, 'John Mukasa', '1985-03-22', 'male', '+256772345678')
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

-- Seed Initial Thresholds
INSERT INTO `patient_thresholds` (`patient_id`, `target_fasting_min`, `target_fasting_max`, `target_postprandial_max`, `hypo_threshold`, `severe_hypo_threshold`) VALUES
(1, 80.00, 130.00, 180.00, 70.00, 54.00),
(2, 80.00, 130.00, 180.00, 70.00, 54.00)
ON DUPLICATE KEY UPDATE `patient_id`=`patient_id`;

-- Seed Initial Audit Log
INSERT INTO `audit_logs` (`user_id`, `target_table`, `action`, `details`) VALUES
(1, 'system', 'INITIALIZE', 'Database schema and demo seed accounts initialized.');
