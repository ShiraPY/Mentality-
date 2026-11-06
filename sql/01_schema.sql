-- =====================================================================
-- MENTALITY - Habit & Mood Tracker
-- File: 01_schema.sql  (tables, keys, constraints, seed data)
-- Run in phpMyAdmin: Import tab, or paste into the SQL tab.
-- Engine: InnoDB (required for foreign keys and transactions)
-- =====================================================================

DROP DATABASE IF EXISTS mentality_db;
CREATE DATABASE mentality_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mentality_db;

-- 1. ROLES (3 roles: admin, moderator, user) ---------------------------
CREATE TABLE roles (
    role_id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_name  VARCHAR(20) NOT NULL,
    PRIMARY KEY (role_id),
    UNIQUE KEY uq_role_name (role_name)
) ENGINE=InnoDB;

-- 2. USERS --------------------------------------------------------------
CREATE TABLE users (
    user_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name        VARCHAR(100) NOT NULL,
    username         VARCHAR(50)  NOT NULL,
    email            VARCHAR(100) NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,          -- bcrypt via password_hash()
    role_id          TINYINT UNSIGNED NOT NULL DEFAULT 3,
    status           ENUM('active','suspended') NOT NULL DEFAULT 'active',
    profile_picture  VARCHAR(255) NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- 3. USER_SETTINGS (1-to-1 with users) ---------------------------------
CREATE TABLE user_settings (
    user_id          INT UNSIGNED NOT NULL,
    appearance       ENUM('light','dark') NOT NULL DEFAULT 'dark',
    notifications_on TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. PASSWORD_RESETS (6-digit PIN, stored hashed) ----------------------
CREATE TABLE password_resets (
    reset_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    pin_hash    VARCHAR(255) NOT NULL,
    expires_at  DATETIME NOT NULL,
    is_used     TINYINT(1) NOT NULL DEFAULT 0,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reset_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. HABIT_CATEGORIES (SELF-REFERENCING: parent_id -> category_id) -----
CREATE TABLE habit_categories (
    category_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(50) NOT NULL,
    parent_id    INT UNSIGNED NULL,
    PRIMARY KEY (category_id),
    CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES habit_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. HABITS (1 user -> many habits) -------------------------------------
CREATE TABLE habits (
    habit_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    name         VARCHAR(100) NOT NULL,
    details      TEXT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (habit_id),
    CONSTRAINT fk_habits_user     FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_habits_category FOREIGN KEY (category_id) REFERENCES habit_categories(category_id)
) ENGINE=InnoDB;

-- 7. HABIT_LOGS (one row per habit per day) -----------------------------
CREATE TABLE habit_logs (
    log_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    habit_id   INT UNSIGNED NOT NULL,
    log_date   DATE NOT NULL,
    completed  TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (log_id),
    UNIQUE KEY uq_habit_day (habit_id, log_date),
    CONSTRAINT fk_logs_habit FOREIGN KEY (habit_id) REFERENCES habits(habit_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. GOALS ---------------------------------------------------------------
CREATE TABLE goals (
    goal_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NULL,
    title        VARCHAR(100) NOT NULL,
    description  TEXT NULL,
    target_date  DATE NOT NULL,
    progress     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status       ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (goal_id),
    CONSTRAINT chk_goal_progress CHECK (progress BETWEEN 0 AND 100),
    CONSTRAINT fk_goals_user     FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_goals_category FOREIGN KEY (category_id) REFERENCES habit_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. GOAL_HABITS (JUNCTION TABLE: many goals <-> many habits) ----------
CREATE TABLE goal_habits (
    goal_id   INT UNSIGNED NOT NULL,
    habit_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (goal_id, habit_id),
    CONSTRAINT fk_gh_goal  FOREIGN KEY (goal_id)  REFERENCES goals(goal_id)   ON DELETE CASCADE,
    CONSTRAINT fk_gh_habit FOREIGN KEY (habit_id) REFERENCES habits(habit_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. MOOD_LOGS (mood_note is encrypted by PHP before saving) ----------
CREATE TABLE mood_logs (
    mood_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    mood       ENUM('Great','Good','Okay','Sad','Frustrated','Tired') NOT NULL,
    mood_note  TEXT NULL,                            -- encrypted (AES-256, openssl) - sensitive field
    log_date   DATE NOT NULL,
    PRIMARY KEY (mood_id),
    UNIQUE KEY uq_mood_day (user_id, log_date),
    CONSTRAINT fk_mood_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. NOTIFICATIONS -----------------------------------------------------
CREATE TABLE notifications (
    notification_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED NOT NULL,
    message          TEXT NOT NULL,
    is_cleared       TINYINT(1) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. AUDIT_LOGS (who did what, when) ----------------------------------
-- user_id is SET NULL on delete so the history survives; username is copied in.
CREATE TABLE audit_logs (
    audit_id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NULL,
    username     VARCHAR(50) NULL,
    action       VARCHAR(30) NOT NULL,               -- e.g. CREATE, UPDATE, DELETE, LOGIN, SUSPEND
    table_name   VARCHAR(50) NOT NULL,
    record_id    INT UNSIGNED NULL,
    details      VARCHAR(255) NULL,
    ip_address   VARCHAR(45) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================
INSERT INTO roles (role_id, role_name) VALUES (1,'admin'), (2,'moderator'), (3,'user');

-- Top-level categories
INSERT INTO habit_categories (category_id, name, parent_id) VALUES
 (1,'Health',NULL), (2,'Academic',NULL), (3,'Hobby',NULL), (4,'Personal',NULL);
-- Sub-categories (this is the hierarchy)
INSERT INTO habit_categories (name, parent_id) VALUES
 ('Fitness',1), ('Nutrition',1), ('Sleep',1),
 ('Programming',2), ('Reading',2),
 ('Music',3), ('Sports',3),
 ('Finance',4), ('Mindfulness',4);
