-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 07:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dabestan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `booklets`
--

CREATE TABLE `booklets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booklet_transactions`
--

CREATE TABLE `booklet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booklet_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `transaction_type` enum('debit','credit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','archived','disbanded','setup') NOT NULL DEFAULT 'active',
  `region_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `description`, `status`, `region_id`) VALUES
(1, '601', '', 'active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `added_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_students`
--

INSERT INTO `class_students` (`id`, `class_id`, `student_name`, `added_by_user_id`, `created_at`) VALUES
(1, 1, 'سوسن', 789654123, '2025-07-13 23:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `class_teachers`
--

CREATE TABLE `class_teachers` (
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_teachers`
--

INSERT INTO `class_teachers` (`class_id`, `teacher_id`) VALUES
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `department_description`) VALUES
(1, 'نظارت', 'بخش نظارت بر عملکرد کلاس‌ها'),
(2, 'پرورشی', 'بخش امور پرورشی و فرهنگی'),
(3, 'ضمن خدمت', 'بخش آموزش‌های ضمن خدمت مدرسان'),
(4, 'اولیا', 'بخش ارتباط با اولیای دانش‌آموزان'),
(5, 'امید تدریس', 'بخش آموزش مدرسان جدید'),
(6, 'منابع انسانی', 'بخش مدیریت امور پرسنل'),
(7, 'مالی', 'بخش امور مالی و پشتیبانی'),
(8, 'جذب و راه اندازی', 'بخش جذب دانش‌آموزان جدید');

-- --------------------------------------------------------

--
-- Table structure for table `department_permissions`
--

CREATE TABLE `department_permissions` (
  `department_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `form_name`, `form_description`, `created_by`, `created_at`) VALUES
(1, 'فرم خوداظهاری هفتگی مدرس', 'این فرم به صورت هفتگی توسط مدرسین برای گزارش وضعیت کلاس تکمیل می‌شود.', 1, '2025-07-14 02:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','select','checkbox','radio','number','date') NOT NULL,
  `field_options` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `field_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_fields`
--

INSERT INTO `form_fields` (`id`, `form_id`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`) VALUES
(1, 1, 'نوع کلاس برگزار شده', 'select', 'عادی,فوق برنامه,تشکیل نشده', 1, 0),
(2, 1, 'تاریخ روز جلسه', 'select', '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31', 1, 1),
(3, 1, 'تاریخ ماه جلسه', 'select', '1,2,3,4,5,6,7,8,9,10,11,12', 1, 2),
(4, 1, 'تاریخ سال جلسه', 'select', '1403,1404,1405,1406,1407,1408,1409,1410,1411,1412,1413,1414,1415,1416,1417,1418,1419,1420,1421,1422,1423,1424,1425,1426,1427,1428,1429,1430,1431,1432,1433,1434,1435,1436,1437,1438,1439,1440,1441,1442,1443,1444,1445,1446,1447,1448,1449,1450,1451,1452,1453,1454,1455,1456,1457,1458,1459,1460,1461,1462,1463,1464,1465,1466,1467,1468,1469,1470,1471,1472,1473,1474,1475,1476,1477,1478,1479,1480,1481,1482,1483,1484,1485,1486,1487,1488,1489,1490,1491,1492,1493,1494,1495,1496,1497,1498,1499,1500,1501,1502,1503', 1, 3),
(5, 1, 'مدرسین قبل از جلسه هماهنگی داشته اند؟', 'radio', 'بله,خیر', 1, 4),
(6, 1, 'زمان هماهنگی قبل از جلسه', 'select', 'کمتر از نیم ساعت,بین نیم تا دو ساعت,بیش از دو ساعت,نداشتیم', 1, 5),
(7, 1, 'مدرسین قبل از جلسه توسل داشته اند', 'radio', 'بله,خیر', 1, 6),
(8, 1, 'وضعیت حضور مدرس اول', 'select', 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت', 1, 7),
(9, 1, 'وضعیت حضور مدرس دوم', 'select', 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت', 1, 8),
(10, 1, 'وضعیت حضور مدرس سوم', 'select', 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت', 0, 9),
(11, 1, 'تعداد غائبین این جلسه', 'number', '', 1, 10),
(12, 1, 'اسامی غایبین این جلسه', 'textarea', '', 0, 11),
(13, 1, 'با غائبین بدون اطلاع تماس گرفته شده', 'select', 'بله,خیر,غایب بدون اطلاع نداشتیم', 1, 12),
(14, 1, 'جزوه و داستان', 'select', 'آخرین بازمانده,ماهنامه,داستان با هماهنگی,داستان بدون هماهنگی,عدم اجرا', 1, 13),
(15, 1, 'زمان جزوه', 'select', 'کمتر از 15 دقیقه,بین 15 تا 30 دقیقه,بیش از 30 دقیقه,عدم اجرا', 1, 14),
(16, 1, 'اجرای جزوه', 'select', 'مدرس اول,مدرس دوم,مدرس سوم,به صورت مشترک,عدم اجرا', 1, 15),
(17, 1, 'کدام درس از جزوه اخرین بازمانده رو تدریس کردید', 'select', 'درس اول,درس دوم,درس سوم,درس چهارم,درس پنجم,درس ششم,درس هفتم,درس هشتم,درس نهم,درس دهم,درس یازدهم,درس دوازدهم,درس سیزدهم,درس چهاردهم', 1, 16),
(18, 1, 'کدام جلد از جزوه ماهنامه را تدریس کردید', 'select', 'محرم,صفر,ربیع الاول,ربیع الثانی,جمادی الاول,جمادی الثانی,رجب,شعبان,رمضان,شوال,ذی القعده,ذی الحجه', 1, 17),
(19, 1, 'درس چندم جزوه ماهنامه را تدریس کردید', 'select', 'درس اول,درس دوم,درس سوم,درس چهارم', 1, 18),
(20, 1, 'عنوان داستان گفته شده', 'text', '', 1, 19),
(21, 1, 'نوع یادحضرت', 'select', 'طبق چارت,با هماهنگی,بدون هماهنگی,عدم اجرا', 1, 20),
(22, 1, 'زمان یادحضرت', 'select', 'کمتر از 15 دقیقه,بین 15 تا 30 دقیقه,بیش از 30 دقیقه,عدم اجرا', 1, 21),
(23, 1, 'عنوان یاد حضرت', 'text', '', 1, 22),
(24, 1, 'نوع بازی', 'select', 'کانال بازی,بازی جدید,عدم اجرا', 1, 23),
(25, 1, 'زمان بازی', 'select', 'کمتر از 30 دقیقه,بین 30 تا 45 دقیقه,بیش از 45 دقیقه,عدم اجرا', 1, 24),
(26, 1, 'اجرا بازی', 'select', 'مدرس اول,مدرس دوم,مدرس سوم,به صورت مشترک,عدم اجرا', 1, 25),
(27, 1, 'محتوای دیگر ارائه شده', 'select', 'احکام,قرآن,مناسبت,نداشتیم', 1, 26),
(28, 1, 'در ارائه محتوا خلاقیت داشتید؟', 'radio', 'بله (لطفا در بخش توضیحات شرح دهید),خیر', 1, 27),
(29, 1, 'توضیحات', 'textarea', '', 0, 28);

-- --------------------------------------------------------

--
-- Table structure for table `form_submissions`
--

CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `related_to_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_submission_data`
--

CREATE TABLE `form_submission_data` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_events`
--

CREATE TABLE `general_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_year` int(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `proposal` text DEFAULT NULL,
  `required_workforce` text DEFAULT NULL,
  `required_budget` decimal(15,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_rentals`
--

CREATE TABLE `item_rentals` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rent_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendance`
--

CREATE TABLE `meeting_attendance` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('present','absent','justified_absence') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_checklist_items`
--

CREATE TABLE `meeting_checklist_items` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `related_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, '', NULL, 'متربی \'سوسن\' که در لیست جذب بود، توسط مدرس به کلاس اضافه شد. لطفاً وضعیت او را در لیست جذب بررسی کنید.', 'admin/view_region_students.php?region_id=1', 1, '2025-07-14 02:41:07'),
(2, 2, '', NULL, 'پاسخ جدیدی برای تیکت شما با عنوان \"صشظلی\" ثبت شد.', 'user/view_ticket.php?id=4', 1, '2025-07-15 02:18:29'),
(4, 1, '', NULL, 'وظیفه جدیدی با عنوان \'تست وظظیفه فوری\' برای شما ثبت شد.', '/user/view_task.php?id=1', 1, '2025-07-15 03:16:05'),
(5, 1, '', NULL, 'وظیفه جدیدی با عنوان \'تست وظیفه بالا\' برای شما ثبت شد.', '/user/view_task.php?id=2', 1, '2025-07-15 03:16:20'),
(6, 1, '', NULL, 'وظیفه جدیدی با عنوان \'تست وظیفه عادی\' برای شما ثبت شد.', '/user/view_task.php?id=3', 1, '2025-07-15 03:16:31'),
(7, 2, '', NULL, 'پاسخ جدیدی برای تیکت شما با عنوان \"یوهو\" ثبت شد.', 'user/view_ticket.php?id=3', 1, '2025-07-15 03:17:01'),
(8, 1, '', NULL, 'وظیفه جدیدی با عنوان \'تست وظیفه ادمین\' برای شما ثبت شد.', 'user/view_task.php?id=4', 1, '2025-07-16 02:52:12'),
(9, 2, '', NULL, 'وظیفه جدیدی با عنوان \'تست وظیفه ادمین\' برای شما ثبت شد.', 'user/view_task.php?id=4', 1, '2025-07-16 02:52:12'),
(11, 2, '', NULL, 'وظیفه جدیدی با عنوان \'وظیفه ادمین\' برای شما ثبت شد.', 'user/view_task.php?id=5', 1, '2025-07-16 04:01:41'),
(12, 2, 'reassignment_request', 1, 'کاربر admin درخواست محول کردن وظیفه \'تست وظظیفه فوری\' را دارد.', NULL, 1, '2025-07-16 18:40:50'),
(13, 2, '', NULL, 'وظیفه جدیدی با عنوان \'تست محول\' برای شما ثبت شد.', 'user/view_task.php?id=6', 1, '2025-07-16 18:55:58'),
(14, 1, 'reassignment_request', 6, 'کاربر elyas درخواست محول کردن وظیفه \'تست محول\' را دارد.', NULL, 1, '2025-07-16 18:59:03'),
(15, 1, 'reassignment_request', 4, 'کاربر elyas درخواست محول کردن وظیفه \'تست وظیفه ادمین\' را دارد.', 'user/view_task.php?id=4', 1, '2025-07-16 19:50:52'),
(16, 2, 'reassignment_approved', 4, 'درخواست شما برای محول کردن وظیفه \'تست وظیفه ادمین\' تایید شد.', NULL, 1, '2025-07-17 10:57:16'),
(17, 3, 'new_task_assigned', 4, 'وظیفه جدیدی با عنوان \'تست وظیفه ادمین\' به شما محول شد.', NULL, 1, '2025-07-17 10:57:16'),
(18, 2, '', NULL, 'پاسخ جدیدی برای تیکت شما با عنوان \"تست عادی\" ثبت شد.', 'user/view_ticket.php?id=2', 0, '2025-07-17 12:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `parent_meetings`
--

CREATE TABLE `parent_meetings` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `speaker` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `teacher_report_submission_id` int(11) DEFAULT NULL,
  `observer_report_submission_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `permission_description`) VALUES
(1, 'submit_ticket', 'اجازه ثبت تیکت جدید'),
(2, 'view_own_financials', 'مشاهده وضعیت مالی خود'),
(3, 'fill_self_assessment', 'پر کردن فرم خوداظهاری'),
(4, 'edit_own_class_info', 'ویرایش اطلاعات کلاس‌های خود'),
(5, 'view_department_menu', 'مشاهده منوهای بخش‌های سازمانی'),
(6, 'manage_users', 'مدیریت کامل کاربران (ایجاد، ویرایش، حذف)'),
(7, 'manage_roles', 'مدیریت نقش‌ها و مجوزها'),
(8, 'manage_classes', 'مدیریت تمام کلاس‌ها'),
(9, 'manage_forms', 'ایجاد و مدیریت فرم‌های پویا'),
(10, 'manage_inventory', 'مدیریت انبار و اموال'),
(11, 'manage_financials', 'مدیریت امور مالی کلی (جزوات، تراکنش‌ها)'),
(12, 'manage_donations', 'مدیریت کمک‌های مالی (صله)'),
(13, 'manage_recruitment', 'مدیریت بخش جذب و راه‌اندازی'),
(14, 'view_all_submissions', 'مشاهده تمام فرم‌های ثبت شده توسط دیگران'),
(15, 'view_analytics', 'مشاهده تحلیل‌ها و گزارشات'),
(16, 'manage_meetings', 'مدیریت جلسات (اولیا، ضمن خدمت و...)'),
(17, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(18, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(19, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(20, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(21, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(22, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(23, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(24, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(25, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(26, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(27, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(28, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(29, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(30, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(31, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(32, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(33, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(34, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(35, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(36, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(37, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(38, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(39, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(40, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(41, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(42, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(43, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(44, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(45, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(46, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(47, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(48, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(49, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(50, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(51, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(52, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(53, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(54, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(55, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(56, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(57, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(58, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(59, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(60, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(61, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(62, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(63, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(64, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(65, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(66, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(67, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(68, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(69, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(70, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(71, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(72, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(73, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(74, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(75, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(76, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(77, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(78, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(79, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(80, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(81, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(82, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(83, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(84, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(85, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(86, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(87, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(88, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(89, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(90, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(91, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(92, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(93, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(94, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(95, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(96, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(97, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(98, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(99, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(100, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(101, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(102, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(103, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(104, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(105, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(106, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(107, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(108, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(109, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(110, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(111, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(112, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(113, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(114, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(115, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(116, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(117, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(118, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(119, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(120, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(121, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(122, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(123, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(124, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(125, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(126, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(127, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(128, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(129, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(130, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(131, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(132, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(133, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(134, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(135, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(136, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(137, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(138, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(139, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(140, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(141, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(142, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(143, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(144, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(145, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(146, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(147, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(148, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(149, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(150, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(151, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(152, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(153, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(154, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(155, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(156, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(157, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(158, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(159, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(160, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(161, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(162, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(163, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(164, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(165, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(166, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(167, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(168, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(169, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(170, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(171, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(172, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(173, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(174, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(175, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(176, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(177, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(178, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(179, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(180, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(181, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(182, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(183, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(184, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(185, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(186, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(187, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(188, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(189, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(190, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(191, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(192, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(193, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(194, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(195, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(196, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(197, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(198, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(199, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(200, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(201, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(202, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(203, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(204, 'manage_tasks', 'توانایی مدیریت وظایف'),
(205, 'manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'),
(206, 'manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'),
(207, 'manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'),
(208, 'view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'),
(209, 'manage_inventory', 'توانایی مدیریت انبار و اقلام'),
(210, 'manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'),
(211, 'view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'),
(212, 'manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'),
(213, 'manage_events', 'توانایی مدیریت رویدادهای عمومی'),
(214, 'submit_ticket', 'توانایی ارسال تیکت جدید'),
(215, 'view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم'),
(216, 'manage_tasks', 'توانایی مدیریت وظایف');

-- --------------------------------------------------------

--
-- Table structure for table `recruited_students`
--

CREATE TABLE `recruited_students` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `region_id` int(11) NOT NULL,
  `recruiter_name` varchar(100) DEFAULT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `recruited_at` date DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `name`, `created_by`) VALUES
(1, 'احمد اباد', 1);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_description`) VALUES
(1, 'مدیر دبستان', 'دسترسی کامل به تمام بخش‌های مدیریتی.'),
(2, 'معاون دبستان', 'دسترسی به بخش‌های مدیریتی مشخص شده.'),
(6, 'مدرس', 'دسترسی‌های استاندارد برای مدرسان.'),
(7, 'مسئول پرورشی', '');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 41),
(1, 42),
(1, 46),
(1, 51),
(1, 56),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 8),
(2, 14),
(2, 16),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 16),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(6, 1),
(6, 2),
(6, 3),
(6, 4),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 45),
(7, 46),
(7, 47),
(7, 48),
(7, 49),
(7, 50),
(7, 51),
(7, 52),
(7, 53),
(7, 54),
(7, 55),
(7, 56),
(7, 57);

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
('001_alter_tables', '2025-07-14 21:47:57'),
('002_update_tickets_table', '2025-07-14 22:19:23'),
('003_create_tasks_tables', '2025-07-14 23:10:01'),
('004_update_ticket_replies_table', '2025-07-14 23:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `service_meetings`
--

CREATE TABLE `service_meetings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `meeting_date` datetime NOT NULL,
  `speaker` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_deletion_requests`
--

CREATE TABLE `student_deletion_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_source` varchar(20) NOT NULL COMMENT 'Source table: class_students or recruited_students',
  `class_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('normal','high','urgent') NOT NULL DEFAULT 'normal',
  `deadline` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `status`, `priority`, `deadline`, `created_by`, `created_at`, `completed_at`) VALUES
(1, 'تست وظظیفه فوری', 'سشبلسلصقذش852069841302.', 'in_progress', 'urgent', '0000-00-00 00:00:00', 2, '2025-07-14 23:46:05', NULL),
(2, 'تست وظیفه بالا', 'یبلاتن', 'in_progress', 'high', NULL, 2, '2025-07-14 23:46:20', NULL),
(3, 'تست وظیفه عادی', 'حیخبحخش', 'pending', '', '0000-00-00 00:00:00', 2, '2025-07-14 23:46:31', NULL),
(4, 'تست وظیفه ادمین', '74126987563201', 'pending', 'urgent', '0000-00-00 00:00:00', 1, '2025-07-15 23:22:12', NULL),
(5, 'وظیفه ادمین', '79530', 'pending', 'urgent', '0000-00-00 00:00:00', 1, '2025-07-16 00:31:41', NULL),
(6, 'تست محول', '8754321.', 'pending', 'urgent', '0000-00-00 00:00:00', 1, '2025-07-16 15:25:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `assigned_to_department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `assigned_to_user_id`, `assigned_to_department_id`) VALUES
(1, 1, 1, NULL),
(2, 2, 2, NULL),
(3, 3, 1, NULL),
(4, 4, 3, NULL),
(5, 4, 3, NULL),
(6, 5, 1, NULL),
(7, 5, 2, NULL),
(8, 6, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `task_comments`
--

INSERT INTO `task_comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 4, 2, 'hsshshr', '2025-07-16 00:32:27'),
(2, 4, 2, 'hsshshr', '2025-07-16 00:32:53'),
(3, 2, 1, 'تست نظر', '2025-07-16 14:00:31'),
(4, 2, 1, 'اسقاصقاص', '2025-07-16 14:08:49'),
(5, 4, 2, 'jcgjk778997997799987', '2025-07-16 16:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `task_history`
--

CREATE TABLE `task_history` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `task_history`
--

INSERT INTO `task_history` (`id`, `task_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 2, 1, 'تغییر وضعیت به in_progress', NULL, '2025-07-16 14:08:29'),
(2, 2, 1, 'محول کردن وظیفه به elyas', NULL, '2025-07-16 14:09:00'),
(3, 1, 1, 'وضعیت وظیفه را به \'تکمیل شده\' تغییر داد.', NULL, '2025-07-16 14:27:51'),
(4, 1, 1, 'وضعیت وظیفه را به \'در حال انجام\' تغییر داد.', NULL, '2025-07-16 14:27:58'),
(5, 4, 1, 'وظیفه را به sosan محول کرد.', NULL, '2025-07-17 07:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `task_reassignment_requests`
--

CREATE TABLE `task_reassignment_requests` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `requested_by_id` int(11) NOT NULL,
  `requested_to_id` int(11) NOT NULL,
  `new_user_id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_reassignment_requests`
--

INSERT INTO `task_reassignment_requests` (`id`, `task_id`, `requested_by_id`, `requested_to_id`, `new_user_id`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 3, 'لصبثبص', 'pending', '2025-07-16 15:04:38', '2025-07-16 15:04:38'),
(2, 1, 1, 2, 3, 'لصبثبص', 'pending', '2025-07-16 15:10:13', '2025-07-16 15:10:13'),
(3, 1, 1, 2, 3, 'ظزبشب', 'pending', '2025-07-16 15:10:50', '2025-07-16 15:10:50'),
(4, 6, 2, 1, 3, 'قلشلذسش', 'pending', '2025-07-16 15:29:03', '2025-07-16 15:29:03'),
(5, 4, 2, 1, 3, 'gzszaerg', 'approved', '2025-07-16 16:20:52', '2025-07-17 07:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_to_department_id` int(11) DEFAULT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `status` enum('open','in_progress','closed') NOT NULL DEFAULT 'open',
  `priority` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `title`, `message`, `user_id`, `assigned_to_department_id`, `assigned_to_user_id`, `status`, `priority`, `created_at`) VALUES
(1, 'تست پیام فوری', 'پیام فوووووووووووووری', 2, NULL, 1, 'open', 'urgent', '2025-07-15 01:50:15'),
(2, 'تست عادی', 'عااااادی', 2, NULL, 1, 'in_progress', 'normal', '2025-07-15 01:50:53'),
(3, 'یوهو', 'شسبش', 2, 4, NULL, 'in_progress', 'urgent', '2025-07-15 02:08:01'),
(4, 'صشظلی', 'اسثشق', 2, 1, NULL, 'in_progress', 'urgent', '2025-07-15 02:17:54');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply_message` text NOT NULL,
  `is_log` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `reply_message`, `is_log`, `created_at`) VALUES
(1, 4, 1, 'f fa', 0, '2025-07-15 02:18:29'),
(2, 4, 1, 'یسبذدی', 0, '2025-07-15 02:50:00'),
(3, 3, 1, 'ssvavdsdvav', 0, '2025-07-15 03:17:01'),
(4, 2, 1, 'cfm,jh', 0, '2025-07-17 12:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `is_admin`, `telegram_chat_id`, `created_at`) VALUES
(1, 'ادمین', 'اصلی', 'admin', '$2y$10$FxpDz4JQyw0iBCW3GlcmC.QxvTk3L1H0jNLiZ0YtbQOB4.qLVIdCK', 1, NULL, '2025-07-14 02:22:27'),
(2, 'الیاس', '', 'elyas', '$2y$10$UsJdUaCoKjhwUvLTzx2VCO8.qU9VBGOk8rrYMTjl8oC7VWc55oDHe', 0, NULL, '2025-07-14 02:38:32'),
(3, 'سوسن', '', 'sosan', '$2y$10$NsIM0iVcxsLlqfXMSry4Z.WCapUQHKs22bI7MqqRa6k7jZPruaod2', 0, NULL, '2025-07-16 17:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_departments`
--

CREATE TABLE `user_departments` (
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(2, 6),
(2, 7);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booklets`
--
ALTER TABLE `booklets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booklet_transactions`
--
ALTER TABLE `booklet_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_teachers`
--
ALTER TABLE `class_teachers`
  ADD PRIMARY KEY (`class_id`,`teacher_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_permissions`
--
ALTER TABLE `department_permissions`
  ADD PRIMARY KEY (`department_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_fields_ibfk_1` (`form_id`);

--
-- Indexes for table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_submissions_ibfk_1` (`form_id`),
  ADD KEY `form_submissions_ibfk_2` (`user_id`);

--
-- Indexes for table `form_submission_data`
--
ALTER TABLE `form_submission_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_submission_data_ibfk_1` (`submission_id`),
  ADD KEY `form_submission_data_ibfk_2` (`field_id`);

--
-- Indexes for table `general_events`
--
ALTER TABLE `general_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `item_rentals`
--
ALTER TABLE `item_rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meeting_user_unique` (`meeting_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `meeting_checklist_items`
--
ALTER TABLE `meeting_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meeting_id` (`meeting_id`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parent_meetings`
--
ALTER TABLE `parent_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `teacher_report_submission_id` (`teacher_report_submission_id`),
  ADD KEY `observer_report_submission_id` (`observer_report_submission_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recruited_students`
--
ALTER TABLE `recruited_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `service_meetings`
--
ALTER TABLE `service_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `student_deletion_requests`
--
ALTER TABLE `student_deletion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `assigned_to_department_id` (`assigned_to_department_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_history`
--
ALTER TABLE `task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_reassignment_requests`
--
ALTER TABLE `task_reassignment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `requested_by_id` (`requested_by_id`),
  ADD KEY `new_user_id` (`new_user_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD PRIMARY KEY (`user_id`,`department_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booklets`
--
ALTER TABLE `booklets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booklet_transactions`
--
ALTER TABLE `booklet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_students`
--
ALTER TABLE `class_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `form_submissions`
--
ALTER TABLE `form_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_submission_data`
--
ALTER TABLE `form_submission_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_events`
--
ALTER TABLE `general_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_rentals`
--
ALTER TABLE `item_rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_checklist_items`
--
ALTER TABLE `meeting_checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `parent_meetings`
--
ALTER TABLE `parent_meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `recruited_students`
--
ALTER TABLE `recruited_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_meetings`
--
ALTER TABLE `service_meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_deletion_requests`
--
ALTER TABLE `student_deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_reassignment_requests`
--
ALTER TABLE `task_reassignment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_permissions`
--
ALTER TABLE `department_permissions`
  ADD CONSTRAINT `department_permissions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD CONSTRAINT `form_fields_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD CONSTRAINT `form_submissions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_submission_data`
--
ALTER TABLE `form_submission_data`
  ADD CONSTRAINT `form_submission_data_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form_submission_data_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `general_events`
--
ALTER TABLE `general_events`
  ADD CONSTRAINT `general_events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `item_rentals`
--
ALTER TABLE `item_rentals`
  ADD CONSTRAINT `item_rentals_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `item_rentals_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD CONSTRAINT `meeting_attendance_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `service_meetings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meeting_attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meeting_checklist_items`
--
ALTER TABLE `meeting_checklist_items`
  ADD CONSTRAINT `meeting_checklist_items_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `service_meetings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meeting_checklist_items_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `parent_meetings`
--
ALTER TABLE `parent_meetings`
  ADD CONSTRAINT `parent_meetings_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_meetings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `parent_meetings_ibfk_3` FOREIGN KEY (`teacher_report_submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `parent_meetings_ibfk_4` FOREIGN KEY (`observer_report_submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_meetings`
--
ALTER TABLE `service_meetings`
  ADD CONSTRAINT `service_meetings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_deletion_requests`
--
ALTER TABLE `student_deletion_requests`
  ADD CONSTRAINT `student_deletion_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_deletion_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_history`
--
ALTER TABLE `task_history`
  ADD CONSTRAINT `task_history_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD CONSTRAINT `user_departments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
