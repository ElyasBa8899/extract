-- Dabestan Project - Database Update v2
-- This script applies necessary changes to fix recent errors.
-- Always backup your database before running this script.

-- Step 1: Add 'region_id' to the 'classes' table
-- This links a class to a specific recruitment region.
ALTER TABLE `classes`
ADD COLUMN `region_id` INT(11) NULL DEFAULT NULL AFTER `status`,
ADD INDEX `idx_region_id` (`region_id`);

-- Add a foreign key constraint to ensure data integrity.
-- This assumes you have a 'regions' table with an 'id' column.
-- If a region is deleted, the link in 'classes' will become NULL.
ALTER TABLE `classes`
ADD CONSTRAINT `fk_class_region`
FOREIGN KEY (`region_id`)
REFERENCES `regions`(`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Step 2: Seed the Self-Assessment Form and its Fields
-- This ensures the form exists and has the required fields, preventing "Field not found" errors.

-- First, ensure the main form container exists.
-- It uses INSERT IGNORE to avoid errors if a form with ID 1 already exists.
INSERT IGNORE INTO `forms` (`id`, `form_name`, `form_description`, `created_by`)
VALUES (1, 'فرم خوداظهاری هفتگی', 'این فرم به صورت هفتگی توسط مدرسان برای ارزیابی عملکرد کلاس پر می‌شود.', 1);

-- Second, delete any existing fields for form_id = 1 to prevent duplicates.
-- This makes the script safe to re-run.
DELETE FROM `form_fields` WHERE `form_id` = 1;

-- Finally, insert all the required fields for the self-assessment form.
INSERT INTO `form_fields` (`form_id`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`) VALUES
(1, 'نوع کلاس برگزار شده', 'select', 'عادی,جبرانی,فوق‌العاده', 1, 1),
(1, 'تاریخ روز جلسه', 'number', NULL, 1, 2),
(1, 'تاریخ ماه جلسه', 'select', 'فروردین,اردیبهشت,خرداد,تیر,مرداد,شهریور,مهر,آبان,آذر,دی,بهمن,اسفند', 1, 3),
(1, 'تاریخ سال جلسه', 'number', NULL, 1, 4),
(1, 'مدرسین قبل از جلسه هماهنگی داشته اند؟', 'radio', 'بله,خیر', 1, 5),
(1, 'زمان هماهنگی قبل از جلسه', 'text', NULL, 0, 6),
(1, 'مدرسین قبل از جلسه توسل داشته اند', 'radio', 'بله,خیر', 1, 7),
(1, 'وضعیت حضور مدرس اول', 'select', 'حاضر,غایب موجه,غایب غیرموجه', 1, 8),
(1, 'وضعیت حضور مدرس دوم', 'select', 'حاضر,غایب موجه,غایب غیرموجه,مدرس ندارد', 1, 9),
(1, 'وضعیت حضور مدرس سوم', 'select', 'حاضر,غایب موجه,غایب غیرموجه,مدرس ندارد', 1, 10),
(1, 'تعداد غائبین این جلسه', 'number', NULL, 1, 11),
(1, 'اسامی غایبین این جلسه', 'textarea', NULL, 0, 12),
(1, 'با غائبین بدون اطلاع تماس گرفته شده', 'radio', 'بله,خیر,موردی نبود', 1, 13),
(1, 'جزوه و داستان', 'select', 'آخرین بازمانده,ماهنامه,هر دو,هیچکدام,داستان', 1, 14),
(1, 'زمان جزوه', 'text', NULL, 0, 15),
(1, 'اجرای جزوه', 'select', 'عالی,خوب,متوسط,ضعیف', 0, 16),
(1, 'کدام درس از جزوه اخرین بازمانده رو تدریس کردید', 'text', NULL, 0, 17),
(1, 'کدام جلد از جزوه ماهنامه را تدریس کردید', 'text', NULL, 0, 18),
(1, 'درس چندم جزوه ماهنامه را تدریس کردید', 'text', NULL, 0, 19),
(1, 'عنوان داستان گفته شده', 'text', NULL, 0, 20),
(1, 'نوع یادحضرت', 'select', 'روضه,سخنرانی,کلیپ,مولودی,سایر', 1, 21),
(1, 'زمان یادحضرت', 'text', NULL, 1, 22),
(1, 'عنوان یاد حضرت', 'text', NULL, 1, 23),
(1, 'نوع بازی', 'select', 'فکری,تحرکی,هر دو', 1, 24),
(1, 'زمان بازی', 'text', NULL, 1, 25),
(1, 'اجرا بازی', 'select', 'عالی,خوب,متوسط,ضعیف', 1, 26),
(1, 'محتوای دیگر ارائه شده', 'textarea', NULL, 0, 27),
(1, 'در ارائه محتوا خلاقیت داشتید؟', 'radio', 'بله,تاحدودی,خیر', 1, 28),
(1, 'توضیحات', 'textarea', NULL, 0, 99);

-- End of script
-- Your database should now be up-to-date.
-- Remember to delete this file from your server after running it.
