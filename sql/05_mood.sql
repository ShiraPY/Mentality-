-- =====================================================================
-- MENTALITY - 05_mood.sql
-- Mood names match the design mockup (Low instead of Sad) + STORED PROCEDURE #3
-- Run AFTER 04_goal_progress.sql. Use the IMPORT tab in phpMyAdmin.
-- =====================================================================
USE mentality_db;

ALTER TABLE mood_logs
    MODIFY mood ENUM('Great','Good','Okay','Low','Frustrated','Tired') NOT NULL;

DROP PROCEDURE IF EXISTS sp_save_mood;

DELIMITER $$

-- STORED PROCEDURE #3: save (or change) the mood for one day. One row per user per day.
-- p_note arrives ALREADY ENCRYPTED by PHP, so the database never sees the private text.
CREATE PROCEDURE sp_save_mood(
    IN p_user_id INT UNSIGNED,
    IN p_mood    VARCHAR(20),
    IN p_note    TEXT,
    IN p_date    DATE
)
BEGIN
    IF p_date > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot log a mood for a future date.';
    END IF;

    IF p_mood NOT IN ('Great','Good','Okay','Low','Frustrated','Tired') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid mood.';
    END IF;

    INSERT INTO mood_logs (user_id, mood, mood_note, log_date)
    VALUES (p_user_id, p_mood, p_note, p_date)
    ON DUPLICATE KEY UPDATE mood = VALUES(mood), mood_note = VALUES(mood_note);
END$$

DELIMITER ;
