-- =====================================================================
-- MENTALITY - 08_features.sql
--   1) appearance settings columns (custom colours + font)
--   2) many more habit categories (safe to run twice: it never adds duplicates)
--   3) STORED PROCEDURES #4 and #5 for the admin / moderator pages
-- Run AFTER 07_settings.sql. Use the IMPORT tab in phpMyAdmin.
-- (ADD COLUMN IF NOT EXISTS works on MariaDB, which is what XAMPP uses.)
-- =====================================================================
USE mentality_db;

-- 1) APPEARANCE --------------------------------------------------------
ALTER TABLE user_settings
    ADD COLUMN IF NOT EXISTS bg_color     CHAR(7) NULL,
    ADD COLUMN IF NOT EXISTS card_color   CHAR(7) NULL,
    ADD COLUMN IF NOT EXISTS text_color   CHAR(7) NULL,
    ADD COLUMN IF NOT EXISTS accent_color CHAR(7) NULL,
    ADD COLUMN IF NOT EXISTS font_choice  VARCHAR(20) NOT NULL DEFAULT 'modern';

-- 2) MORE CATEGORIES ---------------------------------------------------
DROP PROCEDURE IF EXISTS tmp_add_category;
DELIMITER $$
CREATE PROCEDURE tmp_add_category(IN p_parent VARCHAR(50), IN p_name VARCHAR(50))
BEGIN
    DECLARE v_pid INT UNSIGNED DEFAULT NULL;
    IF p_parent IS NULL THEN
        IF NOT EXISTS (SELECT 1 FROM habit_categories WHERE name = p_name AND parent_id IS NULL) THEN
            INSERT INTO habit_categories (name, parent_id) VALUES (p_name, NULL);
        END IF;
    ELSE
        SELECT category_id INTO v_pid FROM habit_categories WHERE name = p_parent AND parent_id IS NULL LIMIT 1;
        IF v_pid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM habit_categories WHERE name = p_name AND parent_id = v_pid) THEN
            INSERT INTO habit_categories (name, parent_id) VALUES (p_name, v_pid);
        END IF;
    END IF;
END$$
DELIMITER ;

-- new top-level categories
CALL tmp_add_category(NULL, 'Work');
CALL tmp_add_category(NULL, 'Social');
CALL tmp_add_category(NULL, 'Spiritual');
CALL tmp_add_category(NULL, 'Home');

-- Health
CALL tmp_add_category('Health', 'Hydration');
CALL tmp_add_category('Health', 'Hygiene');
CALL tmp_add_category('Health', 'Medication & Check-ups');
CALL tmp_add_category('Health', 'Stretching & Yoga');
-- Academic
CALL tmp_add_category('Academic', 'Studying');
CALL tmp_add_category('Academic', 'Writing');
CALL tmp_add_category('Academic', 'Language Learning');
CALL tmp_add_category('Academic', 'Math & Science');
CALL tmp_add_category('Academic', 'Research');
-- Hobby
CALL tmp_add_category('Hobby', 'Gaming');
CALL tmp_add_category('Hobby', 'Art & Drawing');
CALL tmp_add_category('Hobby', 'Photography');
CALL tmp_add_category('Hobby', 'Cooking');
CALL tmp_add_category('Hobby', 'Gardening');
CALL tmp_add_category('Hobby', 'Dance');
-- Personal
CALL tmp_add_category('Personal', 'Journaling');
CALL tmp_add_category('Personal', 'Self-care');
CALL tmp_add_category('Personal', 'Time Management');
CALL tmp_add_category('Personal', 'Digital Detox');
-- Work
CALL tmp_add_category('Work', 'Job Tasks');
CALL tmp_add_category('Work', 'Skills Training');
CALL tmp_add_category('Work', 'Networking');
CALL tmp_add_category('Work', 'Planning');
-- Social
CALL tmp_add_category('Social', 'Family');
CALL tmp_add_category('Social', 'Friends');
CALL tmp_add_category('Social', 'Volunteering');
-- Spiritual
CALL tmp_add_category('Spiritual', 'Prayer');
CALL tmp_add_category('Spiritual', 'Reflection');
CALL tmp_add_category('Spiritual', 'Gratitude');
-- Home
CALL tmp_add_category('Home', 'Cleaning');
CALL tmp_add_category('Home', 'Chores');
CALL tmp_add_category('Home', 'Organizing');

DROP PROCEDURE tmp_add_category;

-- 3) ADMIN / MODERATOR PROCEDURES -------------------------------------
DROP PROCEDURE IF EXISTS sp_set_user_status;
DROP PROCEDURE IF EXISTS sp_delete_user;

DELIMITER $$

-- STORED PROCEDURE #4: suspend or reactivate an account.
-- The permission rules live in the database itself, so they hold even if the PHP has a bug.
CREATE PROCEDURE sp_set_user_status(
    IN p_actor_id  INT UNSIGNED,
    IN p_target_id INT UNSIGNED,
    IN p_status    VARCHAR(10)
)
BEGIN
    DECLARE v_actor_role  VARCHAR(20) DEFAULT NULL;
    DECLARE v_target_role VARCHAR(20) DEFAULT NULL;

    IF p_status NOT IN ('active', 'suspended') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid status.';
    END IF;

    SELECT r.role_name INTO v_actor_role  FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_actor_id;
    SELECT r.role_name INTO v_target_role FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_target_id;

    IF v_actor_role IS NULL OR v_actor_role NOT IN ('admin', 'moderator') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You do not have permission to manage users.';
    END IF;
    IF v_target_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found.';
    END IF;
    IF p_actor_id = p_target_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot change the status of your own account.';
    END IF;
    IF v_target_role = 'admin' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administrator accounts cannot be suspended.';
    END IF;
    IF v_actor_role = 'moderator' AND v_target_role <> 'user' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Moderators can only manage regular users.';
    END IF;

    UPDATE users SET status = p_status WHERE user_id = p_target_id;
END$$

-- STORED PROCEDURE #5: delete an account (administrators only). All of the user's habits, goals, moods
-- and notifications are removed by the ON DELETE CASCADE keys; audit_logs rows stay (user_id becomes NULL).
CREATE PROCEDURE sp_delete_user(
    IN p_actor_id  INT UNSIGNED,
    IN p_target_id INT UNSIGNED
)
BEGIN
    DECLARE v_actor_role  VARCHAR(20) DEFAULT NULL;
    DECLARE v_target_role VARCHAR(20) DEFAULT NULL;

    SELECT r.role_name INTO v_actor_role  FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_actor_id;
    SELECT r.role_name INTO v_target_role FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_target_id;

    IF v_actor_role IS NULL OR v_actor_role <> 'admin' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only administrators can delete accounts.';
    END IF;
    IF v_target_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found.';
    END IF;
    IF p_actor_id = p_target_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot delete your own account here.';
    END IF;
    IF v_target_role = 'admin' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administrator accounts cannot be deleted.';
    END IF;

    DELETE FROM users WHERE user_id = p_target_id;
END$$

DELIMITER ;
