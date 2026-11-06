-- Limited database user for the app (replaces root). Import AFTER mentality_db.sql, in phpMyAdmin Import tab.
-- Password must match $DB_PASS in includes/db.php.
CREATE USER IF NOT EXISTS 'mentality_app'@'localhost' IDENTIFIED BY 'ad9bab214bdf348bd817a591';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, SHOW VIEW, TRIGGER ON `mentality_db`.* TO 'mentality_app'@'localhost';
FLUSH PRIVILEGES;
