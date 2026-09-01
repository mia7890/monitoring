<?php

$host = "localhost";
$dbname = "monitoring";
$username = "root";
$password = "";

try {
    // Connect to MySQL server first
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Create the database if it doesn't exist
    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS `$dbname`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    // Connect to the monitoring database
    $pdo->exec("USE `$dbname`");

    // Auto-create appointments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `appointments` (
            `id`               INT AUTO_INCREMENT PRIMARY KEY,
            `fae_id`           INT           DEFAULT NULL,
            `user_name`        VARCHAR(150)  NOT NULL,
            `appointment_date` DATE          NOT NULL,
            `reason`           TEXT          NOT NULL,
            `status`           ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            `admin_comment`    TEXT          DEFAULT NULL,
            `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Auto-create admin_events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_events` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `title`       VARCHAR(255)  NOT NULL,
            `event_date`  DATE          NOT NULL,
            `description` TEXT          DEFAULT NULL,
            `category`    ENUM('meeting','busy','reminder','other') NOT NULL DEFAULT 'other',
            `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Auto-create fae_users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `fae_users` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255)  NOT NULL,
            `fae_code`   VARCHAR(100)  NOT NULL UNIQUE,
            `email`      VARCHAR(255)  DEFAULT NULL,
            `department` VARCHAR(255)  DEFAULT NULL,
            `phone`      VARCHAR(100)  DEFAULT NULL,
            `profile_image` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Auto-create tasks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tasks` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `fae_id`      INT           DEFAULT NULL,
            `region`      VARCHAR(100)  DEFAULT NULL,
            `course`      VARCHAR(255)  DEFAULT NULL,
            `task_name`   VARCHAR(255)  NOT NULL,
            `description` TEXT          DEFAULT NULL,
            `deadline`    DATE          DEFAULT NULL,
            `status`      ENUM('Pending','In Progress','Completed','Overdue') DEFAULT 'Pending',
            `progress`    INT           DEFAULT 0,
            `priority`    ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
            `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`fae_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Auto-create task_updates table (activity log & work reports)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `task_updates` (
            `id`                 INT AUTO_INCREMENT PRIMARY KEY,
            `task_id`            INT           NOT NULL,
            `fae_id`             INT           DEFAULT NULL,
            `author_role`        VARCHAR(50)   NOT NULL DEFAULT 'fae',
            `author_name`        VARCHAR(255)  NOT NULL,
            `message`            TEXT          NOT NULL,
            `attachment`         VARCHAR(255)  DEFAULT NULL,
            `progress_at_update` INT           DEFAULT NULL,
            `status_at_update`   VARCHAR(50)   DEFAULT NULL,
            `created_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            INDEX (`task_id`),
            INDEX (`fae_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Safe column additions if tables existed from older schema
    try {
        $pdo->exec("ALTER TABLE `appointments` ADD COLUMN IF NOT EXISTS `fae_id` INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE `fae_users` ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE `fae_users` ADD COLUMN IF NOT EXISTS `department` VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE `fae_users` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(100) DEFAULT NULL");
        $pdo->exec("ALTER TABLE `fae_users` ADD COLUMN IF NOT EXISTS `profile_image` VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE `tasks` ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE `tasks` ADD COLUMN IF NOT EXISTS `priority` ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium'");
    } catch (Exception $ex) {
        // Ignore if already present or MySQL syntax difference
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>