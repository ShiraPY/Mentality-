-- =====================================================================
-- MENTALITY - 04_goal_progress.sql
-- Goal progress now comes from the supporting habits.
-- Adds FUNCTION fn_goal_auto_progress and replaces sp_set_habit_log so that
-- every tick also updates the progress of the goals linked to that habit.
-- Run AFTER 03_goals.sql. Use the IMPORT tab in phpMyAdmin.
-- =====================================================================
USE mentality_db;

DROP FUNCTION  IF EXISTS fn_goal_auto_progress;
DROP PROCEDURE IF EXISTS sp_set_habit_log;

DELIMITER $$

-- FUNCTION #2: % of the plan followed = completed ticks of the linked habits between the goal's
-- start date and its target date, divided by (number of linked habits x number of days in that period).
-- Returns NULL when the goal has no linked habits.
CREATE FUNCTION fn_goal_auto_progress(p_goal_id INT UNSIGNED, p_start DATE, p_end DATE)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE v_habits INT DEFAULT 0;
    DECLARE v_done   INT DEFAULT 0;
    DECLARE v_days   INT DEFAULT 1;

    SELECT COUNT(*) INTO v_habits FROM goal_habits WHERE goal_id = p_goal_id;
    IF v_habits = 0 THEN
        RETURN NULL;
    END IF;

    SET v_days = GREATEST(DATEDIFF(p_end, p_start) + 1, 1);

    SELECT COUNT(*) INTO v_done
    FROM goal_habits gh
    JOIN habit_logs l ON l.habit_id = gh.habit_id
    WHERE gh.goal_id = p_goal_id AND l.completed = 1
      AND l.log_date BETWEEN p_start AND p_end;

    RETURN LEAST(100, ROUND(v_done / (v_habits * v_days) * 100));
END$$

-- STORED PROCEDURE #1 (updated): tick / untick a habit, then refresh the progress of its goals.
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

    INSERT INTO habit_logs (habit_id, log_date, completed)
    VALUES (p_habit_id, p_date, p_completed)
    ON DUPLICATE KEY UPDATE completed = VALUES(completed);

    -- Goals that are not completed yet get their progress recalculated from their linked habits.
    -- (If a goal reaches 100, the trigger trg_goal_before_update marks it completed.)
    UPDATE goals
    SET progress = fn_goal_auto_progress(goal_id, DATE(created_at), target_date)
    WHERE status <> 'completed'
      AND goal_id IN (SELECT goal_id FROM goal_habits WHERE habit_id = p_habit_id);
END$$

DELIMITER ;

-- One-time: recalculate progress for goals that already have linked habits.
UPDATE goals
SET progress = fn_goal_auto_progress(goal_id, DATE(created_at), target_date)
WHERE status <> 'completed'
  AND goal_id IN (SELECT goal_id FROM goal_habits);
