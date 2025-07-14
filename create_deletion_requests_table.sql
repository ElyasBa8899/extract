CREATE TABLE IF NOT EXISTS `student_deletion_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(255) COLLATE utf8mb4_persian_ci NOT NULL,
  `student_source` varchar(20) COLLATE utf8mb4_persian_ci NOT NULL COMMENT 'Source table: class_students or recruited_students',
  `class_id` int(11) NOT NULL,
  `reason` text COLLATE utf8mb4_persian_ci NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_persian_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `student_deletion_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_deletion_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
