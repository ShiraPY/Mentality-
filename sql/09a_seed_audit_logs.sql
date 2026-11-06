-- =====================================================================
-- 09a - IMPORT this file (phpMyAdmin > mentality_db > Import). Wait for the green success message.
-- It creates 100,000 fake rows in audit_logs (all with username 'perf_test') so the table is big enough
-- for an index to make a visible difference. It may take 30-90 seconds. Part A of 09_performance.sql uses it.
-- =====================================================================
USE mentality_db;

DROP PROCEDURE IF EXISTS sp_seed_perf_data;
DELIMITER $$
CREATE PROCEDURE sp_seed_perf_data(IN p_rows INT)
BEGIN
    DECLARE i INT DEFAULT 0;
    SET autocommit = 0;
    WHILE i < p_rows DO
        INSERT INTO audit_logs (user_id, username, action, table_name, details, ip_address, created_at)
        VALUES (NULL, 'perf_test',
                ELT(1 + FLOOR(RAND() * 5), 'LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'LOGIN_FAILED'),
                'users', 'performance demo row', '127.0.0.1',
                DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 525600) MINUTE));
        SET i = i + 1;
        IF i MOD 10000 = 0 THEN COMMIT; END IF;
    END WHILE;
    COMMIT;
    SET autocommit = 1;
END$$
DELIMITER ;

CALL sp_seed_perf_data(100000);
DROP PROCEDURE IF EXISTS sp_seed_perf_data;
