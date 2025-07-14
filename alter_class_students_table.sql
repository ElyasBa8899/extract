ALTER TABLE `class_students`
CHANGE COLUMN `phone_number` `added_by_user_id` INT(11) NULL DEFAULT NULL,
ADD CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`added_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
