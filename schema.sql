-- ==============================================================================
-- IIIT Kota Hostel Management System
-- Database Schema (MySQL / MariaDB - InnoDB, utf8mb4)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `hostel_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hostel_db`;

-- ------------------------------------------------------------------------------
-- 1. Students Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `college_id` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `gender` ENUM('male', 'female') NOT NULL,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `branch` VARCHAR(100) NOT NULL,
    `room_no` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_students_hostel_status` (`hostel_type`, `status`),
    INDEX `idx_students_college_id` (`college_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Staff Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_username` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('warden', 'caretaker') NOT NULL,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `contact_no` VARCHAR(20) NOT NULL,
    INDEX `idx_staff_hostel_role` (`hostel_type`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Login Attempts Table (Rate limiting / Brute force protection)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(100) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_attempts_lookup` (`identifier`, `ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Complaints Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `category` ENUM('Electrical', 'Plumbing', 'Carpentry', 'Discipline') NOT NULL,
    `description` TEXT NOT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'in-progress', 'resolved') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_complaints_hostel_status` (`hostel_type`, `status`),
    INDEX `idx_complaints_student` (`student_id`),
    CONSTRAINT `fk_complaints_student`
        FOREIGN KEY (`student_id`)
        REFERENCES `students` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Notices Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `message` TEXT NOT NULL,
    `posted_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notices_hostel` (`hostel_type`),
    INDEX `idx_notices_posted_by` (`posted_by`),
    CONSTRAINT `fk_notices_staff`
        FOREIGN KEY (`posted_by`)
        REFERENCES `staff` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Mess Menu Table (SHARED across hostels, no hostel column)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mess_menu` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `day` ENUM('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') NOT NULL,
    `meal_type` ENUM('breakfast', 'lunch', 'dinner') NOT NULL,
    `items` TEXT NOT NULL,
    UNIQUE KEY `unique_day_meal` (`day`, `meal_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Mess Feedback Table (Anonymized - NO student reference)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mess_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meal_id` INT NOT NULL,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `feedback_date` DATE NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mess_feedback_hostel_date` (`hostel_type`, `feedback_date`),
    INDEX `idx_mess_feedback_meal` (`meal_id`),
    CONSTRAINT `fk_mess_feedback_meal`
        FOREIGN KEY (`meal_id`)
        REFERENCES `mess_menu` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. Feedback Votes Table (Prevents duplicate voting, never joined to mess_feedback)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback_votes` (
    `student_id` INT NOT NULL,
    `meal_id` INT NOT NULL,
    `feedback_date` DATE NOT NULL,
    PRIMARY KEY (`student_id`, `meal_id`, `feedback_date`),
    INDEX `idx_feedback_votes_meal` (`meal_id`),
    CONSTRAINT `fk_feedback_votes_student`
        FOREIGN KEY (`student_id`)
        REFERENCES `students` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_feedback_votes_meal`
        FOREIGN KEY (`meal_id`)
        REFERENCES `mess_menu` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. Lost & Found Table
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lost_found` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `hostel_type` ENUM('boys', 'girls') NOT NULL,
    `type` ENUM('lost', 'found') NOT NULL,
    `item_description` TEXT NOT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `date_logged` DATE NOT NULL,
    `status` ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
    `resolved_at` DATETIME NULL DEFAULT NULL,
    `resolved_by_role` ENUM('student', 'warden', 'caretaker') NULL DEFAULT NULL,
    INDEX `idx_lost_found_hostel_status` (`hostel_type`, `status`),
    INDEX `idx_lost_found_student` (`student_id`),
    CONSTRAINT `fk_lost_found_student`
        FOREIGN KEY (`student_id`)
        REFERENCES `students` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- SEED DATA: Shared Weekly Menu (7 days x 3 meals = 21 rows)
-- (Staff are seeded separately via seed_staff.php)
-- ------------------------------------------------------------------------------
INSERT INTO `mess_menu` (`day`, `meal_type`, `items`) VALUES
    ('Mon', 'breakfast', 'Poha, Jalebi, Sprouts, Boiled Eggs / Milk, Tea'),
    ('Mon', 'lunch', 'Dal Tadka, Seasonal Veg (Aloo Gobi), Jeera Rice, Phulka, Curd, Salad'),
    ('Mon', 'dinner', 'Paneer Butter Masala, Chapati, Veg Pulao, Dal Fry, Gulab Jamun'),

    ('Tue', 'breakfast', 'Aloo Paratha, Curd, Butter, Pickle, Sprouts, Tea / Coffee'),
    ('Tue', 'lunch', 'Rajma Masala, Steamed Basmati Rice, Phulka, Aloo Baingan, Boondi Raita, Papad'),
    ('Tue', 'dinner', 'Mix Vegetable Curry, Yellow Dal, Chapati, Steamed Rice, Fruit Custard'),

    ('Wed', 'breakfast', 'Idli, Medu Vada, Sambar, Coconut Chutney, Banana, Tea / Coffee'),
    ('Wed', 'lunch', 'Kadhi Pakoda, Steamed Rice, Bhindi Do Pyaza, Phulka, Salad, Papad'),
    ('Wed', 'dinner', 'Egg Curry / Shahi Paneer, Jeera Rice, Tawa Roti, Dal Tadka, Sewai Kheer'),

    ('Thu', 'breakfast', 'Methi Thepla, Chana Masala, Curd, Pickle, Tea / Milk'),
    ('Thu', 'lunch', 'Chana Dal, Aloo Shimla Mirch, Steamed Rice, Phulka, Green Salad'),
    ('Thu', 'dinner', 'Hyderabadi Veg Biryani, Mirchi Ka Salan, Veg Raita, Rasgulla'),

    ('Fri', 'breakfast', 'Masala Dosa, Sambar, Tomato & Coconut Chutney, Boiled Egg / Milk, Tea'),
    ('Fri', 'lunch', 'Amritsari Chole, Bhature, Jeera Rice, Onion Mint Salad, Sweet Lassi'),
    ('Fri', 'dinner', 'Dal Makhani, Dum Aloo, Butter Roti, Peas Pulao, Moong Dal Halwa'),

    ('Sat', 'breakfast', 'Pav Bhaji, Sprouts Chaat, Butter, Lemon, Masala Chai'),
    ('Sat', 'lunch', 'Gujarati Dal, Khichdi, Sev Tamatar Sabzi, Phulka, Masala Chaas, Papad'),
    ('Sat', 'dinner', 'Chicken Curry / Matar Paneer, Steamed Rice, Tawa Roti, Dal Fry, Vanilla Ice Cream'),

    ('Sun', 'breakfast', 'Bedmi Puri, Aloo Bhaji, Suji Halwa, Sprouts, Tea / Coffee'),
    ('Sun', 'lunch', 'Veg Pulao, Dal Tadka, Dum Aloo, Phulka, Cucumber Raita, Roasted Papad'),
    ('Sun', 'dinner', 'Malai Kofta, Kashmiri Pulao, Tandoori Roti / Chapati, Dal Makhani, Rabri Kheer')
ON DUPLICATE KEY UPDATE `items` = VALUES(`items`);

