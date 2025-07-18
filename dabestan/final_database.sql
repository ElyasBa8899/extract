-- Dabestan Database Schema for SQLite

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `password` TEXT NOT NULL,
  `full_name` TEXT,
  `is_admin` INTEGER NOT NULL DEFAULT 0,
  `telegram_user_id` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `role_name` TEXT NOT NULL UNIQUE,
  `role_description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `permission_name` TEXT NOT NULL UNIQUE,
  `permission_description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` INTEGER NOT NULL,
  `permission_id` INTEGER NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` INTEGER NOT NULL,
  `role_id` INTEGER NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `user_departments`
--

CREATE TABLE `user_departments` (
  `user_id` INTEGER NOT NULL,
  `department_id` INTEGER NOT NULL,
  PRIMARY KEY (`user_id`, `department_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `class_name` TEXT NOT NULL,
  `teacher_user_id` INTEGER,
  `description` TEXT,
  `parent_meeting_status` TEXT CHECK( `parent_meeting_status` IN ('completed','pending','not_held') ) NOT NULL DEFAULT 'not_held',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `general_events`
--

CREATE TABLE `general_events` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `event_name` TEXT NOT NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `parent_info` TEXT,
  `contact_number` TEXT,
  `region_id` INTEGER,
  `introducer` TEXT,
  `recruited_from_event_id` INTEGER,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recruited_from_event_id`) REFERENCES `general_events` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `student_event_attendance`
--

CREATE TABLE `student_event_attendance` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `student_id` INTEGER NOT NULL,
    `event_id` INTEGER NOT NULL,
    `attendance_date` TEXT NOT NULL,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `general_events`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `class_id` INTEGER NOT NULL,
  `student_id` INTEGER NOT NULL,
  PRIMARY KEY (`class_id`, `student_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `meeting_type` TEXT CHECK( `meeting_type` IN ('parent_meeting','in_service_training','other') ) NOT NULL,
  `related_class_id` INTEGER,
  `title` TEXT NOT NULL,
  `meeting_date` DATETIME NOT NULL,
  `location` TEXT,
  `status` TEXT CHECK( `status` IN ('planned','completed','cancelled') ) NOT NULL DEFAULT 'planned',
  `created_by` INTEGER NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`related_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `meeting_checklist_items`
--

CREATE TABLE `meeting_checklist_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `meeting_id` INTEGER NOT NULL,
  `item_description` TEXT NOT NULL,
  `is_completed` INTEGER NOT NULL DEFAULT 0,
  `responsible_user_id` INTEGER,
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendance`
--

CREATE TABLE `meeting_attendance` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `meeting_id` INTEGER NOT NULL,
  `user_id` INTEGER,
  `attendee_name` TEXT, -- For non-users
  `is_present` INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `form_type` TEXT CHECK( `form_type` IN ('self_assessment','class_observation','parent_meeting','other') ) NOT NULL,
  `created_by` INTEGER NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);


-- --------------------------------------------------------

--
-- Table structure for table `form_fields`
--

CREATE TABLE `form_fields` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `form_id` INTEGER NOT NULL,
  `label` TEXT NOT NULL,
  `field_type` TEXT CHECK( `field_type` IN ('text','textarea','select','checkbox','radio','number') ) NOT NULL,
  `options` TEXT, -- JSON encoded for select, checkbox, radio
  `is_required` INTEGER NOT NULL DEFAULT 0,
  `field_order` INTEGER DEFAULT 0,
  FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `form_submissions`
--

CREATE TABLE `form_submissions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `form_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `related_class_id` INTEGER,
  `related_meeting_id` INTEGER,
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_meeting_id`) REFERENCES `meetings` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `form_submission_data`
--

CREATE TABLE `form_submission_data` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `submission_id` INTEGER NOT NULL,
  `field_id` INTEGER NOT NULL,
  `field_value` TEXT NOT NULL,
  FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `title` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `status` TEXT CHECK( `status` IN ('open','in_progress','closed','urgent') ) NOT NULL DEFAULT 'open',
  `priority` TEXT CHECK( `priority` IN ('low','medium','high') ) NOT NULL DEFAULT 'medium',
  `assigned_to_user_id` INTEGER,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `ticket_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `status` TEXT CHECK( `status` IN ('pending','in_progress','completed','cancelled') ) NOT NULL DEFAULT 'pending',
  `priority` TEXT CHECK( `priority` IN ('low','medium','high','urgent') ) NOT NULL DEFAULT 'medium',
  `deadline` DATETIME,
  `created_by` INTEGER NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `task_id` INTEGER NOT NULL,
  `assigned_to_user_id` INTEGER,
  `assigned_to_department_id` INTEGER,
  FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `message` TEXT NOT NULL,
  `link` TEXT,
  `is_read` INTEGER NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `category_id` INTEGER,
  `quantity` INTEGER NOT NULL DEFAULT 0,
  `description` TEXT,
  FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `item_rentals`
--

CREATE TABLE `item_rentals` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `item_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `quantity` INTEGER NOT NULL,
  `rental_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `return_date` DATETIME,
  `notes` TEXT,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `contact_info` TEXT,
  `status` TEXT CHECK( `status` IN ('active','inactive','potential') ) NOT NULL DEFAULT 'potential'
);


-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `donor_id` INTEGER NOT NULL,
  `amount` REAL NOT NULL,
  `donation_date` TEXT NOT NULL,
  `project_name` TEXT,
  `notes` TEXT,
  FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`)
);


-- --------------------------------------------------------

--
-- Table structure for table `booklets`
--

CREATE TABLE `booklets` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `price` REAL NOT NULL,
  `description` TEXT
);


-- --------------------------------------------------------

--
-- Table structure for table `booklet_transactions`
--

CREATE TABLE `booklet_transactions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `booklet_id` INTEGER,
  `transaction_type` TEXT CHECK( `transaction_type` IN ('debit','credit') ) NOT NULL,
  `amount` REAL NOT NULL,
  `quantity` INTEGER,
  `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booklet_id`) REFERENCES `booklets` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------

--
-- Table structure for table `content_projects`
--

CREATE TABLE `content_projects` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `status` TEXT DEFAULT 'idea',
  `roadmap` TEXT,
  `created_by` INTEGER NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);

-- --------------------------------------------------------
--                                INDICES
-- --------------------------------------------------------

CREATE INDEX `idx_classes_teacher` ON `classes` (`teacher_user_id`);
CREATE INDEX `idx_students_region` ON `students` (`region_id`);
CREATE INDEX `idx_meetings_class` ON `meetings` (`related_class_id`);
CREATE INDEX `idx_submissions_form` ON `form_submissions` (`form_id`);
CREATE INDEX `idx_submissions_user` ON `form_submissions` (`user_id`);
CREATE INDEX `idx_tasks_created_by` ON `tasks` (`created_by`);
CREATE INDEX `idx_task_assignments_user` ON `task_assignments` (`assigned_to_user_id`);
CREATE INDEX `idx_task_assignments_dept` ON `task_assignments` (`assigned_to_department_id`);
CREATE INDEX `idx_notifications_user` ON `notifications` (`user_id`);
CREATE INDEX `idx_rentals_item` ON `item_rentals` (`item_id`);
CREATE INDEX `idx_rentals_user` ON `item_rentals` (`user_id`);
CREATE INDEX `idx_donations_donor` ON `donations` (`donor_id`);
CREATE INDEX `idx_booklet_trans_user` ON `booklet_transactions` (`user_id`);
