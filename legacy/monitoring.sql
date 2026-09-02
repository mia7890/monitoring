-- Monitoring System database schema
-- Paste this file into phpMyAdmin's SQL tab and click Go.

CREATE DATABASE IF NOT EXISTS `monitoring`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `monitoring`;

CREATE TABLE IF NOT EXISTS `fae_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `fae_code` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `department` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(100) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fae_code` (`fae_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fae_id` INT UNSIGNED DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `course` VARCHAR(255) DEFAULT NULL,
    `task_name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `deadline` DATE DEFAULT NULL,
    `status` ENUM('Pending', 'In Progress', 'Completed', 'Overdue') NOT NULL DEFAULT 'Pending',
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_fae_id` (`fae_id`),
    CONSTRAINT `fk_tasks_fae`
        FOREIGN KEY (`fae_id`) REFERENCES `fae_users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fae_id` INT UNSIGNED DEFAULT NULL,
    `user_name` VARCHAR(150) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    `admin_comment` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_appointments_date` (`appointment_date`),
    KEY `idx_appointments_fae_id` (`fae_id`),
    CONSTRAINT `fk_appointments_fae`
        FOREIGN KEY (`fae_id`) REFERENCES `fae_users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `event_date` DATE NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` ENUM('meeting', 'busy', 'reminder', 'other') NOT NULL DEFAULT 'other',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_updates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `fae_id` INT UNSIGNED DEFAULT NULL,
    `author_role` VARCHAR(50) NOT NULL DEFAULT 'fae',
    `author_name` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `attachment` VARCHAR(255) DEFAULT NULL,
    `progress_at_update` INT DEFAULT NULL,
    `status_at_update` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_updates_task_id` (`task_id`),
    KEY `idx_task_updates_fae_id` (`fae_id`),
    CONSTRAINT `fk_task_updates_task`
        FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_task_updates_fae`
        FOREIGN KEY (`fae_id`) REFERENCES `fae_users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional starter FAE records. Uncomment and edit before running if needed.
-- INSERT INTO `fae_users` (`name`, `fae_code`, `email`, `department`, `phone`) VALUES
-- ('Jane Doe', 'FAE-001', 'jane@example.com', 'Field Engineering', '0123456789');
