-- Create dabestan_db database
CREATE DATABASE IF NOT EXISTS dabestan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE dabestan_db;

-- Users table
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roles table
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  `role_description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions table
CREATE TABLE `permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `permission_name` VARCHAR(100) NOT NULL UNIQUE,
  `permission_description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-Roles mapping table
CREATE TABLE `user_roles` (
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role-Permissions mapping table
CREATE TABLE `role_permissions` (
  `role_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments table
CREATE TABLE `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_name` VARCHAR(100) NOT NULL,
  `department_description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-Departments mapping table
CREATE TABLE `user_departments` (
  `user_id` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  PRIMARY KEY (`user_id`, `department_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Classes table
CREATE TABLE `classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Class-Teachers mapping table
CREATE TABLE `class_teachers` (
  `class_id` INT(11) NOT NULL,
  `teacher_id` INT(11) NOT NULL,
  PRIMARY KEY (`class_id`, `teacher_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dynamic Forms Structure --

-- Forms table
CREATE TABLE `forms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_name` VARCHAR(255) NOT NULL,
  `form_description` TEXT,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Module Table --

CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- The user who receives the notification
  `message` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255), -- Link to the relevant page (e.g., ticket)
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Form Fields table
CREATE TABLE `form_fields` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` INT(11) NOT NULL,
  `field_label` VARCHAR(255) NOT NULL,
  `field_type` ENUM('text', 'textarea', 'select', 'checkbox', 'radio', 'number', 'date') NOT NULL,
  `field_options` TEXT, -- For select, checkbox, radio (e.g., JSON format)
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `field_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Form Submissions table
CREATE TABLE `form_submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `class_id` INT(11) DEFAULT NULL, -- Optional: link submission to a class
  `related_to_id` INT(11) DEFAULT NULL, -- Optional: for linking to other items like meetings, etc.
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Form Submission Data table
CREATE TABLE `form_submission_data` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) NOT NULL,
  `field_id` INT(11) NOT NULL,
  `field_value` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`submission_id`) REFERENCES `form_submissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recruitment Module Tables --

CREATE TABLE `regions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `created_by` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `recruited_students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_name` VARCHAR(100) NOT NULL,
  `parent_name` VARCHAR(100),
  `phone_number` VARCHAR(20),
  `region_id` INT(11) NOT NULL,
  `recruiter_name` VARCHAR(100),
  `event_name` VARCHAR(100), -- e.g., "Ghadir", "Nime Shaban"
  `recruited_at` DATE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory/Rental Module Tables --

CREATE TABLE `inventory_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inventory_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `quantity` INT(11) NOT NULL DEFAULT 0,
  `category_id` INT(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `inventory_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `item_rentals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `rent_date` DATETIME NOT NULL,
  `return_date` DATETIME,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticketing System Tables --

CREATE TABLE `tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `user_id` INT(11) NOT NULL,
  `assigned_to_department_id` INT(11),
  `assigned_to_user_id` INT(11),
  `status` ENUM('open', 'in_progress', 'closed', 'urgent') NOT NULL DEFAULT 'open',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments`(`id`),
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ticket_replies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `reply_message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- In-Service Training (Zemn Khedmat) Module Tables --

CREATE TABLE `service_meetings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `meeting_date` DATETIME NOT NULL,
  `speaker` VARCHAR(255),
  `location` VARCHAR(255),
  `notes` TEXT,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `meeting_checklist_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` INT(11) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL, -- e.g., "Hماهنگی مکان", "دعوت تلگرامی"
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_by` INT(11),
  `completed_at` DATETIME,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`meeting_id`) REFERENCES `service_meetings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `meeting_attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `status` ENUM('present', 'absent', 'justified_absence') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_user_unique` (`meeting_id`, `user_id`),
  FOREIGN KEY (`meeting_id`) REFERENCES `service_meetings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Financial Module (Booklets) Tables --

CREATE TABLE `booklets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `booklet_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- The user (teacher) involved in the transaction
  `booklet_id` INT(11) DEFAULT NULL, -- Nullable for payments
  `quantity` INT(11), -- Number of booklets for debit transactions
  `transaction_type` ENUM('debit', 'credit') NOT NULL, -- debit for receiving booklets, credit for payment
  `amount` DECIMAL(10, 2) NOT NULL, -- Total transaction amount
  `notes` TEXT,
  `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) NOT NULL, -- The user who registered the transaction
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`booklet_id`) REFERENCES `booklets`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parents (Oliya) Module Tables --

CREATE TABLE `parent_meetings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `meeting_date` DATETIME NOT NULL,
  `location` VARCHAR(255),
  `speaker` VARCHAR(255),
  `status` ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  `teacher_report_submission_id` INT(11), -- Links to a submission in form_submissions
  `observer_report_submission_id` INT(11), -- Links to a submission in form_submissions
  `created_by` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`teacher_report_submission_id`) REFERENCES `form_submissions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`observer_report_submission_id`) REFERENCES `form_submissions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- General Events (Parvareshi) Module Tables --

CREATE TABLE `general_events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_name` VARCHAR(255) NOT NULL,
  `event_year` INT(4),
  `description` TEXT,
  `proposal` TEXT, -- For the "Propozal" field
  `required_workforce` TEXT, -- For "Niroo Ensani"
  `required_budget` DECIMAL(12, 2),
  `status` VARCHAR(50),
  `created_by` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
