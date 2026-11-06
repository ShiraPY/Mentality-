-- =====================================================================
-- MENTALITY - 07_settings.sql
-- The "Notifications" setting now controls whether the triggers create notifications.
-- Run AFTER 06_stats.sql. Use the IMPORT tab in phpMyAdmin.
-- =====================================================================
USE mentality_db;

DROP TRIGGER IF EXISTS trg_habit_added;
DROP TRIGGER IF EXISTS trg_goal_before_update;

DELIMITER $$

-- TRIGGER (updated): notify about a new habit only if the user has notifications turned on.
CREATE TRIGGER trg_habit_added
AFTER INSERT ON habits
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT notifications_on FROM user_settings WHERE user_id = NEW.user_id), 1) = 1 THEN
        INSERT INTO notifications (user_id, message)
        VALUES (NEW.user_id, CONCAT('New habit added: ', NEW.name));
    END IF;
END$$

-- TRIGGER (updated): progress 100 completes the goal; the notification respects the setting.
CREATE TRIGGER trg_goal_before_update
BEFORE UPDATE ON goals
FOR EACH ROW
BEGIN
    IF NEW.progress = 100 AND OLD.status <> 'completed' THEN
        SET NEW.status = 'completed';
        IF COALESCE((SELECT notifications_on FROM user_settings WHERE user_id = NEW.user_id), 1) = 1 THEN
            INSERT INTO notifications (user_id, message)
            VALUES (NEW.user_id, CONCAT('Goal completed: ', NEW.title));
        END IF;
    ELSEIF NEW.progress < 100 AND OLD.status = 'completed' THEN
        SET NEW.status = 'in_progress';
    END IF;
END$$

DELIMITER ;
