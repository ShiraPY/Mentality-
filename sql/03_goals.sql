-- =====================================================================
-- MENTALITY - 03_goals.sql
-- Function #1, Stored procedure #2, Triggers #2 and #3, View #2
-- Run AFTER 02_habits.sql. In phpMyAdmin use the IMPORT tab.
-- =====================================================================
USE mentality_db;

DROP VIEW      IF EXISTS v_goal_overview;
DROP FUNCTION  IF EXISTS fn_goal_habit_rate;
DROP PROCEDURE IF EXISTS sp_update_goal_progress;
DROP TRIGGER   IF EXISTS trg_goal_before_insert;
DROP TRIGGER   IF EXISTS trg_goal_before_update;

DELIMITER $$

-- FUNCTION #1: how active were the habits linked to a goal over the last 7 days? (0-100, NULL if none linked)
CREATE FUNCTION fn_goal_habit_rate(p_goal_id INT UNSIGNED)
RETURNS DECIMAL(5,1)
READS SQL DATA
BEGIN
    DECLARE v_habits INT DEFAULT 0;
    DECLARE v_done   INT DEFAULT 0;

    SELECT COUNT(*) INTO v_habits
    FROM goal_habits gh JOIN habits h ON h.habit_id = gh.habit_id
    WHERE gh.goal_id = p_goal_id AND h.is_active = 1;

    IF v_habits = 0 THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(*) INTO v_done
    FROM goal_habits gh
    JOIN habits h      ON h.habit_id = gh.habit_id
    JOIN habit_logs l  ON l.habit_id = gh.habit_id
    WHERE gh.goal_id = p_goal_id AND h.is_active = 1 AND l.completed = 1
      AND l.log_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE();

    RETURN ROUND(v_done / (v_habits * 7) * 100, 1);
END$$

-- STORED PROCEDURE #2: update a goal's progress (checks ownership and the 0-100 range inside the database).
CREATE PROCEDURE sp_update_goal_progress(
    IN p_user_id  INT UNSIGNED,
    IN p_goal_id  INT UNSIGNED,
    IN p_progress INT
)
BEGIN
    DECLARE v_ok INT DEFAULT 0;

    SELECT COUNT(*) INTO v_ok FROM goals WHERE goal_id = p_goal_id AND user_id = p_user_id;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Goal not found for this user.';
    END IF;

    IF p_progress < 0 OR p_progress > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progress must be between 0 and 100.';
    END IF;

    UPDATE goals SET progress = p_progress WHERE goal_id = p_goal_id;   -- fires trg_goal_before_update
END$$

-- TRIGGER #2: a new goal can't have a target date in the past (data integrity).
CREATE TRIGGER trg_goal_before_insert
BEFORE INSERT ON goals
FOR EACH ROW
BEGIN
    IF NEW.target_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target date cannot be in the past.';
    END IF;
END$$

-- TRIGGER #3: progress 100 automatically marks the goal completed and notifies the user.
CREATE TRIGGER trg_goal_before_update
BEFORE UPDATE ON goals
FOR EACH ROW
BEGIN
    IF NEW.progress = 100 AND OLD.status <> 'completed' THEN
        SET NEW.status = 'completed';
        INSERT INTO notifications (user_id, message)
        VALUES (NEW.user_id, CONCAT('Goal completed: ', NEW.title));
    ELSEIF NEW.progress < 100 AND OLD.status = 'completed' THEN
        SET NEW.status = 'in_progress';
    END IF;
END$$

DELIMITER ;

-- VIEW #2: goals with category, parent category (self-join), number of linked habits, 7-day habit activity, days left.
CREATE VIEW v_goal_overview AS
SELECT  g.goal_id, g.user_id, g.title, g.description, g.target_date, g.progress, g.status, g.created_at,
        c.name AS category_name,
        p.name AS parent_category,
        COUNT(gh.habit_id)            AS habit_count,
        fn_goal_habit_rate(g.goal_id) AS habit_rate_7d,
        DATEDIFF(g.target_date, CURDATE()) AS days_left
FROM goals g
LEFT JOIN habit_categories c ON c.category_id = g.category_id
LEFT JOIN habit_categories p ON p.category_id = c.parent_id
LEFT JOIN goal_habits gh     ON gh.goal_id = g.goal_id
GROUP BY g.goal_id, g.user_id, g.title, g.description, g.target_date, g.progress, g.status, g.created_at, c.name, p.name;
