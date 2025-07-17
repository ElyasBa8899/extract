-- This file contains SQL statements to update the database schema.
-- It's designed to be run multiple times without causing errors.

-- Create the `task_reminders` table if it doesn't already exist.
CREATE TABLE IF NOT EXISTS `task_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reminder_type` enum('upcoming','overdue_user','overdue_manager') NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the `task_reassignment_requests` table if it doesn't already exist.
CREATE TABLE IF NOT EXISTS `task_reassignment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `requested_by_id` int(11) NOT NULL,
  `requested_to_id` int(11) NOT NULL,
  `new_user_id` int(11) DEFAULT NULL,
  `new_department_id` int(11) DEFAULT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `requested_by_id` (`requested_by_id`),
  KEY `new_user_id` (`new_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: Foreign key constraints are not added here to avoid errors if the script is run multiple times.
-- If you need to add them, you should first check if they exist.
-- Example of checking and adding a foreign key:
--
-- DELIMITER $$
-- CREATE PROCEDURE AddForeignKey()
-- BEGIN
--   IF NOT EXISTS(SELECT * FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'task_reminders' AND constraint_name = 'fk_task_reminders_task' AND constraint_type = 'FOREIGN KEY') THEN
--     ALTER TABLE `task_reminders` ADD CONSTRAINT `fk_task_reminders_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;
--   END IF;
--   IF NOT EXISTS(SELECT * FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'task_reminders' AND constraint_name = 'fk_task_reminders_user' AND constraint_type = 'FOREIGN KEY') THEN
--     ALTER TABLE `task_reminders` ADD CONSTRAINT `fk_task_reminders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
--   END IF;
-- END$$
-- DELIMITER ;
--
-- CALL AddForeignKey();
-- DROP PROCEDURE AddForeignKey;
