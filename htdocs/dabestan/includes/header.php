<?php
// Ensure core functions are loaded
require_once __DIR__ . '/../core/functions.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Main CSS File -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

    <!-- You can add more CSS files here -->
</head>
<body>

<div id="main-container">
    <header class="main-header">
        <div class="header-content">
            <div class="logo-container">
                <a href="<?php echo BASE_URL; ?>/user/dashboard.php">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            <div class="header-details">
                <div id="live-clock"></div>
                <div id="jalali-date"></div>
            </div>
            <div class="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <?php include 'sidebar.php'; ?>

    <main id="main-content">
        <div class="content-wrapper">
            <!-- Page content will be loaded here -->
