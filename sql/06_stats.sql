-- =====================================================================
-- MENTALITY - 06_stats.sql
-- View #3 using a WINDOW FUNCTION (ROW_NUMBER) to find streaks ("gaps and islands").
-- Run AFTER 05_mood.sql. Use the IMPORT tab in phpMyAdmin.
-- Needs MariaDB 10.2+ or MySQL 8+ (any recent XAMPP is fine).
-- =====================================================================
USE mentality_db;

DROP VIEW IF EXISTS v_user_streaks;

-- A "streak" = a run of consecutive days on which the user completed at least one habit.
-- Idea: number the active days 1,2,3... with ROW_NUMBER(). For consecutive days,
-- (date minus row number) is the same value, so that value identifies the streak.
CREATE VIEW v_user_streaks AS
SELECT  user_id,
        MIN(log_date) AS start_date,
        MAX(log_date) AS end_date,
        COUNT(*)      AS streak_len
FROM (
        SELECT user_id, log_date, DATE_SUB(log_date, INTERVAL rn DAY) AS grp
        FROM (
                SELECT user_id, log_date,
                       ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY log_date) AS rn
                FROM (
                        SELECT DISTINCT h.user_id, l.log_date
                        FROM habit_logs l
                        JOIN habits h ON h.habit_id = l.habit_id
                        WHERE l.completed = 1
                     ) AS active_days
             ) AS numbered
     ) AS grouped
GROUP BY user_id, grp;
