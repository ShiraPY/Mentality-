-- =====================================================================
-- MENTALITY - 09_performance.sql      INDEXES: BEFORE / AFTER
--
-- HOW TO USE THIS FILE (do NOT import it):
--   Open phpMyAdmin > mentality_db > SQL tab. Copy ONE numbered step at a time, paste it, click Go.
--   After each step write down the result and take a screenshot. phpMyAdmin prints the time above the
--   result, like:  "Showing rows 0 - 0 (1 total, Query took 0.0412 seconds.)"
--
-- PART A needs 09a_seed_audit_logs.sql imported first.
-- PART B needs 09b_seed_notifications.sql imported first.
-- =====================================================================

-- #####################################################################
-- PART A: audit_logs  (question: "how many LOGIN events in the last 30 days?")
-- #####################################################################

-- A1. BEFORE the index: run the query 3 times and write down the time.
SELECT COUNT(*) FROM audit_logs
WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- A2. BEFORE the index: the query plan. Screenshot it. Write down: type, key, rows, Extra.
EXPLAIN SELECT COUNT(*) FROM audit_logs
WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
--   Expect: type = ALL, key = NULL, rows = about 100000   -> a FULL TABLE SCAN: every row is read.

-- A3. Create the index.
CREATE INDEX idx_audit_action_created ON audit_logs (action, created_at);

-- A4. AFTER the index: run the query 3 times again and write down the time.
SELECT COUNT(*) FROM audit_logs
WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- A5. AFTER the index: the query plan. Screenshot it.
EXPLAIN SELECT COUNT(*) FROM audit_logs
WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
--   Expect: type = range, key = idx_audit_action_created, rows = only a few thousand, Extra = Using index
--   -> only the matching part of the index is read.

-- A6. Clean up the fake rows.
DELETE FROM audit_logs WHERE username = 'perf_test';

-- #####################################################################
-- PART B: notifications  (the query behind your notification bell)
-- #####################################################################

-- B1. BEFORE the index: run 3 times, write down the time.
SELECT notification_id, message, created_at FROM notifications
WHERE user_id = (SELECT MIN(user_id) FROM users WHERE role_id = 3) AND is_cleared = 0
ORDER BY created_at DESC LIMIT 10;

-- B2. BEFORE the index: the query plan. Screenshot it.
EXPLAIN SELECT notification_id, message, created_at FROM notifications
WHERE user_id = (SELECT MIN(user_id) FROM users WHERE role_id = 3) AND is_cleared = 0
ORDER BY created_at DESC LIMIT 10;
--   Expect: key = fk_notif_user (the index MySQL made automatically for the foreign key),
--           many rows, Extra = "Using where; Using filesort"  -> it finds all of that user's rows, then SORTS them.

-- B3. Create the index (user, cleared flag, date).
CREATE INDEX idx_notif_user_cleared ON notifications (user_id, is_cleared, created_at);

-- B4. AFTER the index: run the same query 3 times, write down the time.
SELECT notification_id, message, created_at FROM notifications
WHERE user_id = (SELECT MIN(user_id) FROM users WHERE role_id = 3) AND is_cleared = 0
ORDER BY created_at DESC LIMIT 10;

-- B5. AFTER the index: the query plan. Screenshot it.
EXPLAIN SELECT notification_id, message, created_at FROM notifications
WHERE user_id = (SELECT MIN(user_id) FROM users WHERE role_id = 3) AND is_cleared = 0
ORDER BY created_at DESC LIMIT 10;
--   Expect: key = idx_notif_user_cleared, very few rows, NO "Using filesort" (the index is already in date order).

-- B6. Clean up the fake rows.
DELETE FROM notifications WHERE message = 'perf demo notification';

-- #####################################################################
-- Two more indexes the real pages use (run once; no before/after needed)
-- #####################################################################
CREATE INDEX IF NOT EXISTS idx_goals_user_status ON goals (user_id, status, target_date);   -- goals page + reminders
CREATE INDEX IF NOT EXISTS idx_logs_date         ON habit_logs (log_date);                   -- stats by date range
