-- SnapVault Database Schema
-- -------------------------------------------------------------
-- HOW TO IMPORT (Hostinger / shared hosting):
--   1. Open phpMyAdmin
--   2. Click your database on the left (e.g. u456613426_snapvault)
--   3. Open the Import tab and choose this file
--   Do NOT create a new database named snap_vault — your host
--   already gave you a database; tables are created inside it.
--
-- Local XAMPP:
--   1. Create an empty database named snap_vault (or any name)
--   2. Select it, then Import this file
-- -------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=enabled, 0=disabled',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE uploads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    flag ENUM('normal', 'important', 'urgent', 'confidential') NOT NULL DEFAULT 'normal',
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uploads_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_uploads_user (user_id),
    INDEX idx_uploads_flag (flag),
    INDEX idx_uploads_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username=admin password=Admin@123
INSERT INTO users (name, username, password, profile, role, status, created_at)
VALUES (
    'System Admin',
    'admin',
    '$2y$10$LidDHRnyHhnEdbVO3v7/suk2Kvvw7BRoz6A1QD5Mtck2lISGTV1.O',
    NULL,
    'admin',
    1,
    NOW()
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    assigned_to INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,
    deadline DATETIME NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') NOT NULL DEFAULT 'pending',
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    completion_note TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tasks_assigned_to (assigned_to),
    INDEX idx_tasks_assigned_by (assigned_by),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_deadline (deadline),
    CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task_files_task (task_id),
    CONSTRAINT fk_task_files_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_task_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT UNSIGNED NOT NULL,
    to_user_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    INDEX idx_messages_from (from_user_id),
    INDEX idx_messages_to (to_user_id),
    INDEX idx_messages_pair (from_user_id, to_user_id),
    INDEX idx_messages_created (created_at),
    CONSTRAINT fk_messages_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
