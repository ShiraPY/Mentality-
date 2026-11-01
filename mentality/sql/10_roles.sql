-- =====================================================================
-- MENTALITY - 10_roles.sql
-- STORED PROCEDURE #6: change a user's role between 'user' and 'moderator'.
-- Run AFTER 08_features.sql. Use the IMPORT tab in phpMyAdmin.
--
-- Rules (enforced here, inside the database):
--   * Only admins and moderators can use it.
--   * Nobody can change their own role.
--   * Administrator accounts can never be changed here (admins are created in phpMyAdmin only).
--   * A moderator can promote a regular user to moderator, but can NOT change another moderator's role.
--   * Only an admin can turn a moderator back into a regular user.
-- =====================================================================
USE mentality_db;

DROP PROCEDURE IF EXISTS sp_set_user_role;

DELIMITER $$

CREATE PROCEDURE sp_set_user_role(
    IN p_actor_id  INT UNSIGNED,
    IN p_target_id INT UNSIGNED,
    IN p_role      VARCHAR(20)
)
BEGIN
    DECLARE v_actor_role  VARCHAR(20) DEFAULT NULL;
    DECLARE v_target_role VARCHAR(20) DEFAULT NULL;

    IF p_role NOT IN ('user', 'moderator') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid role. You can only choose user or moderator.';
    END IF;

    SELECT r.role_name INTO v_actor_role  FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_actor_id;
    SELECT r.role_name INTO v_target_role FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = p_target_id;

    IF v_actor_role IS NULL OR v_actor_role NOT IN ('admin', 'moderator') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You do not have permission to change roles.';
    END IF;
    IF v_target_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found.';
    END IF;
    IF p_actor_id = p_target_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot change your own role.';
    END IF;
    IF v_target_role = 'admin' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administrator roles cannot be changed here.';
    END IF;
    IF v_actor_role = 'moderator' AND v_target_role = 'moderator' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Moderators cannot change another moderator''s role.';
    END IF;
    IF v_target_role = p_role THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That account already has this role.';
    END IF;

    UPDATE users
    SET role_id = (SELECT role_id FROM roles WHERE role_name = p_role)
    WHERE user_id = p_target_id;
END$$

DELIMITER ;
