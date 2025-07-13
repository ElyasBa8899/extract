-- Add region_id to classes table
ALTER TABLE `classes` ADD `region_id` INT(11) NULL DEFAULT NULL AFTER `status`, ADD INDEX `region_id` (`region_id`);
ALTER TABLE `classes` ADD FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Seed self-assessment form fields
-- This script will create the self-assessment form and its fields if they don't exist.

-- 1. Create the Form container
INSERT INTO `forms` (`id`, `form_name`, `form_description`, `created_by`)
SELECT * FROM (SELECT 1 AS `id`, 'فرم خوداظهاری هفتگی' AS `form_name`, 'این فرم به صورت هفتگی توسط مدرسان برای ارزیابی عملکرد کلاس پر می‌شود.' AS `form_description`, 1 AS `created_by`) AS tmp
WHERE NOT EXISTS (
    SELECT `id` FROM `forms` WHERE `id` = 1
) LIMIT 1;

-- 2. Add fields to the form
-- Note: The IDs are hardcoded for simplicity. In a real application, these would be auto-incremented.
-- Make sure to run this only once or handle potential duplicate entries if IDs are not fixed.

-- Delete existing fields for form 1 to avoid duplicates on re-run
DELETE FROM `form_fields` WHERE `form_id` = 1;

-- Re-insert all fields
INSERT INTO `form_fields` (`id`, `form_id`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`) VALUES
(1, 1, 'نوع کلاس برگزار شده', 'select', '[\"عادی\", \"جبرانی\", \"فوق‌العاده\"]', 1, 1),
(2, 1, 'تاریخ روز جلسه', 'number', NULL, 1, 2),
(3, 1, 'تاریخ ماه جلسه', 'select', '[\"فروردین\", \"اردیبهشت\", \"خرداد\", \"تیر\", \"مرداد\", \"شهریور\", \"مهر\", \"آبان\", \"آذر\", \"دی\", \"بهمن\", \"اسفند\"]', 1, 3),
(4, 1, 'تاریخ سال جلسه', 'number', NULL, 1, 4),
(5, 1, 'توضیحات', 'textarea', NULL, 0, 5);

-- You can add more fields here following the same pattern
-- (6, 1, 'موضوع اصلی جلسه', 'text', NULL, 1, 6),
-- (7, 1, 'تعداد حاضرین', 'number', NULL, 1, 7),
-- ...
