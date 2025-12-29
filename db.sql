-- db.sql - SQL to create the apps table

CREATE DATABASE IF NOT EXISTS app_tracker;

USE app_tracker;

CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    latest_version VARCHAR(50) DEFAULT NULL,
    latest_version_norm VARCHAR(50) DEFAULT NULL,
    latest_download_url TEXT DEFAULT NULL,
    update_available TINYINT DEFAULT NULL,
    update_source VARCHAR(100) DEFAULT NULL,
    update_type VARCHAR(20) DEFAULT NULL,
    update_url VARCHAR(500) DEFAULT NULL,
    update_regex VARCHAR(255) DEFAULT NULL,
    last_checked DATETIME DEFAULT NULL,
    last_error TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS update_providers (
    provider_key VARCHAR(100) PRIMARY KEY,
    latest_url TEXT NOT NULL,
    latest_regex VARCHAR(255) NOT NULL,
    download_regex VARCHAR(255) DEFAULT NULL,
    download_url TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS update_provider_aliases (
    alias VARCHAR(100) PRIMARY KEY,
    provider_key VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If table already exists without new columns, run this:
-- ALTER TABLE apps ADD COLUMN latest_version_norm VARCHAR(50) DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN latest_download_url TEXT DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN update_available TINYINT DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN update_type VARCHAR(20) DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN update_url VARCHAR(500) DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN update_regex VARCHAR(255) DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN last_error TEXT DEFAULT NULL;
-- ALTER TABLE apps MODIFY last_checked DATETIME DEFAULT NULL;
-- ALTER TABLE apps ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Optional: seed common providers (edit regex to suit your needs)
-- INSERT INTO update_providers (provider_key, latest_url, latest_regex, download_url) VALUES
-- ('mozilla', 'https://product-details.mozilla.org/1.0/firefox_versions.json', '/\"LATEST_FIREFOX_VERSION\"\\s*:\\s*\"([^\"]+)\"/', 'https://www.mozilla.org/firefox/download/'),
-- ('nodejs', 'https://nodejs.org/dist/index.json', '/\"version\"\\s*:\\s*\"(v[^\"]+)\"/', 'https://nodejs.org/'),
-- ('python', 'https://www.python.org/api/v2/downloads/release/?is_published=yes&is_prerelease=false', '/\"name\"\\s*:\\s*\"Python\\s+([0-9\\.]+)\"/', 'https://www.python.org/downloads/'),
-- ('7zip', 'https://www.7-zip.org/', '/Download\\s+7-Zip\\s+([0-9\\.]+)/', 'https://www.7-zip.org/download.html'),
-- ('vlc', 'https://www.videolan.org/vlc/download-windows.html', '/vlc-([\\d\\.]+)-win32\\.exe/', 'https://www.videolan.org/vlc/download-windows.html'),
-- ('git', 'https://api.github.com/repos/git-for-windows/git/releases/latest', '/\"tag_name\"\\s*:\\s*\"([^\"]+)\"/', 'https://gitforwindows.org/');
