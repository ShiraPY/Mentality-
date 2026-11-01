-- =====================================================================
-- MENTALITY - 02_habits.sql
-- Stored procedure #1, Trigger #1, View #1
-- Run AFTER 01_schema.sql. In phpMyAdmin use the IMPORT tab (it understands DELIMITER).
-- =====================================================================
USE mentality_db;

DROP PROCEDURE IF EXISTS sp_set_habit_log;
DROP TRIGGER   IF EXISTS trg_habit_added;
DROP VIEW      IF EXISTS v_habit_overview;

DELIMITER $$

-- STORED PROCEDURE #1: tick / untick a habit for one day.
-- Safety checks live in the database: the habit must belong to the user, and the date can't be in the future.
CREATE PROCEDURE sp_set_habit_log(
    IN p_user_id   INT UNSIGNED,
    IN p_habit_id  INT UNSIGNED,
    IN p_date      DATE,
    IN p_completed TINYINT
)
BEGIN
    DECLARE v_ok INT DEFAULT 0;

    SELECT COUNT(*) INTO v_ok
    FROM habits
    WHERE habit_id = p_habit_id AND user_id = p_user_id AND is_active = 1;

    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Habit not found for this user.';
    END IF;

    IF p_date > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot log a habit for a future date.';
    END IF;

    -- one row per habit per day: insert it, or update it if it already exists
    INSERT INTO habit_logs (habit_id, log_date, completed)
    VALUES (p_habit_id, p_date, p_completed)
    ON DUPLICATE KEY UPDATE completed = VALUES(completed);
END$$

-- TRIGGER #1: every time a habit is added, the database itself creates a notification.
CREATE TRIGGER trg_habit_added
AFTER INSERT ON habits
FOR EACH ROW
BEGIN
    INSERT INTO notifications (user_id, message)
    VALUES (NEW.user_id, CONCAT('New habit added: ', NEW.name));
END$$

DELIMITER ;

-- VIEW #1: each habit with its category (and parent category via the SELF-JOIN) and progress totals.
CREATE VIEW v_habit_overview AS
SELECT  h.habit_id, h.user_id, h.name, h.details, h.is_active, h.created_at, h.category_id,
        c.name AS category_name,
        p.name AS parent_category,
        COUNT(CASE WHEN l.completed = 1 THEN 1 END)        AS total_completed,
        MAX(CASE WHEN l.completed = 1 THEN l.log_date END) AS last_completed
FROM habits h
JOIN habit_categories c ON c.category_id = h.category_id
LEFT JOIN habit_categories p ON p.category_id = c.parent_id      -- self-referencing join
LEFT JOIN habit_logs l ON l.habit_id = h.habit_id
GROUP BY h.habit_id, h.user_id, h.name, h.details, h.is_active, h.created_at, h.category_id, c.name, p.name;
