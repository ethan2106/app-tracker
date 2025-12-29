-- db.sql - SQL to create the apps table

CREATE DATABASE IF NOT EXISTS app_tracker;

USE app_tracker;

CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    latest_version VARCHAR(50) DEFAULT NULL,
    latest_version_norm VARCHAR(50) DEFAULT NULL,
    update_available TINYINT DEFAULT NULL,
    update_source VARCHAR(100) DEFAULT NULL,
    last_checked DATETIME DEFAULT NULL,
    last_error TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If table already exists without new columns, run this:
-- ALTER TABLE apps ADD COLUMN latest_version_norm VARCHAR(50) DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN update_available TINYINT DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN last_error TEXT DEFAULT NULL;
-- ALTER TABLE apps MODIFY last_checked DATETIME DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
