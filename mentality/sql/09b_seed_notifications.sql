-- =====================================================================
-- 09b - IMPORT this file (same way as 09a). It creates 100,000 fake notifications for one regular user
-- (all with the message 'perf demo notification'). Part B of 09_performance.sql uses it.
-- =====================================================================
USE mentality_db;

DROP PROCEDURE IF EXISTS sp_seed_perf_notifications;
DELIMITER $$
CREATE PROCEDURE sp_seed_perf_notifications(IN p_rows INT)
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE v_uid INT UNSIGNED;
    SELECT MIN(user_id) INTO v_uid FROM users WHERE role_id = 3;      -- a regular user (alex)
    SET autocommit = 0;
    WHILE i < p_rows DO
        INSERT INTO notifications (user_id, message, is_cleared, created_at)
        VALUES (v_uid, 'perf demo notification', IF(RAND() < 0.9, 1, 0),
                DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 525600) MINUTE));
        SET i = i + 1;
        IF i MOD 10000 = 0 THEN COMMIT; END IF;
    END WHILE;
    COMMIT;
    SET autocommit = 1;
END$$
DELIMITER ;

CALL sp_seed_perf_notifications(100000);
DROP PROCEDURE IF EXISTS sp_seed_perf_notifications;
