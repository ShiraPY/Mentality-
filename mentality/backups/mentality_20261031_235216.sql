-- Mentality database backup
-- Database: mentality_db
-- Created:  2026-10-31 23:52:16
-- Restore:  phpMyAdmin > select the database > Import tab, or double-click tools\restore.bat

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;

-- Table audit_logs
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(10) unsigned DEFAULT NULL,
  `details` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`audit_id`,`user_id`,`username`,`action`,`table_name`,`record_id`,`details`,`ip_address`,`created_at`) VALUES
('1','1','admin','LOGIN','users','1',NULL,'::1','2026-10-20 05:40:39'),
('2','1','admin','LOGOUT','users','1',NULL,'::1','2026-10-20 05:40:45'),
('3',NULL,'admin','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-20 05:40:52'),
('4','2','moderator','LOGIN','users','2',NULL,'::1','2026-10-20 05:41:17'),
('5','2','moderator','LOGOUT','users','2',NULL,'::1','2026-10-20 05:41:25'),
('6','3','alex','LOGIN','users','3',NULL,'::1','2026-10-20 05:41:43'),
('7','3','alex','LOGOUT','users','3',NULL,'::1','2026-10-20 05:41:49'),
('8','4','Shira','REGISTER','users','4','New account created','::1','2026-10-20 05:42:23'),
('9','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-20 05:42:35'),
('10','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-20 05:43:09'),
('11','4','Shira','RESET_REQUEST','password_resets','1','Password reset code requested','::1','2026-10-20 06:04:56'),
('12','4',NULL,'RESET_PIN_OK','password_resets','1','Reset code verified','::1','2026-10-20 06:05:06'),
('13','4','Shira','PASSWORD_RESET','users','4','Password changed via reset code','::1','2026-10-20 06:05:29'),
('14','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-20 06:05:41'),
('15','4','Shira','CREATE','habits','1','Habit added: Jogging 6AM','::1','2026-10-20 21:06:06'),
('16',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:39:10'),
('17',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:39:13'),
('18',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:39:20'),
('19',NULL,'jasoncaguicla1233@gmail.com','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:39:27'),
('20',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:40:05'),
('21',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:40:11'),
('22',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-21 06:40:14'),
('23','4','Shira','RESET_REQUEST','password_resets','2','Password reset code requested','::1','2026-10-21 06:40:29'),
('24','4',NULL,'RESET_PIN_OK','password_resets','2','Reset code verified','::1','2026-10-21 06:40:36'),
('25','4','Shira','PASSWORD_RESET','users','4','Password changed via reset code','::1','2026-10-21 06:40:46'),
('26','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-21 06:40:58'),
('27','4','Shira','CREATE','goals','1','Goal added: Be Fit before SDA Camp','::1','2026-10-21 06:42:25'),
('28','4','Shira','UPDATE','goals','1','Progress set to 0%','::1','2026-10-21 06:42:36'),
('29','4','Shira','UPDATE','goals','1','Progress set to 0%','::1','2026-10-21 06:42:40'),
('30','4','Shira','CREATE','goals','2','Goal added: Wake up Early / Sleep Early','::1','2026-10-21 06:44:09'),
('31','4','Shira','UPDATE','goals','2','Progress set to 0%','::1','2026-10-21 06:44:14'),
('32','4','Shira','UPDATE','goals','2','Progress set to 1%','::1','2026-10-21 06:44:18'),
('33','4','Shira','UPDATE','goals','2','Progress set to 100%','::1','2026-10-21 06:44:32'),
('34','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-21 07:11:25'),
('35','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-21 07:12:26'),
('36','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Great','::1','2026-10-21 07:13:36'),
('37',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:08'),
('38',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:13'),
('39',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:19'),
('40',NULL,'jasoncaguicla1233@gmail.com','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:24'),
('41',NULL,'jasoncaguicla1233@gmail.com','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:28'),
('42',NULL,'jasoncaguicla1233@gmail.com','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:34'),
('43',NULL,'jasoncaguicla1233@gmail.com','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:50:37'),
('44','4','Shira','RESET_REQUEST','password_resets','3','Password reset code requested','::1','2026-10-23 08:50:45'),
('45','4',NULL,'RESET_PIN_OK','password_resets','3','Reset code verified','::1','2026-10-23 08:50:50'),
('46',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 08:51:08'),
('47','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-23 08:51:11'),
('48','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Frustrated','::1','2026-10-23 08:52:00'),
('49','4','Shira','CREATE','habits','2','Habit added: Continuing Project','::1','2026-10-23 09:14:44'),
('50','4','Shira','CREATE','goals','3','Goal added: Help her','::1','2026-10-23 09:15:53'),
('51','4','Shira','CREATE','goals','4','Goal added: FINISHING THIS PROJECT TILL NOVEMBER 10','::1','2026-10-23 09:16:53'),
('52','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-23 09:32:27'),
('53',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-23 09:32:33'),
('54','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-23 09:32:36'),
('55','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:23'),
('56','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 09:40:27'),
('57','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:28'),
('58','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 09:40:29'),
('59','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:30'),
('60','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 09:40:32'),
('61','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:33'),
('62','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:34'),
('63','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:35'),
('64','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 09:40:36'),
('65','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 09:40:37'),
('66','4','Shira','PROFILE_UPDATE','users','4','Profile updated','::1','2026-10-23 09:41:28'),
('67','4','Shira','PROFILE_UPDATE','users','4','Profile updated','::1','2026-10-23 09:41:32'),
('68','4','Shira','PROFILE_UPDATE','users','4','Profile updated','::1','2026-10-23 13:23:38'),
('69','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 13:23:54'),
('70','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 13:23:55'),
('71','4','Shira','PROFILE_UPDATE','users','4','Profile updated','::1','2026-10-23 14:03:07'),
('72','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 14:07:46'),
('73','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 14:07:54'),
('74','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 14:13:19'),
('75','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-23 14:13:25'),
('76','4','Shira','CREATE','goals','5','Goal added: Testing','::1','2026-10-23 14:21:05'),
('77','4','Shira','UPDATE','goals','5','Goal edited: Testing','::1','2026-10-23 14:21:28'),
('78','4','Shira','UPDATE','goals','5','Goal edited: Testing','::1','2026-10-23 14:21:32'),
('79','4','Shira','UPDATE','goals','5','Goal edited: Testing','::1','2026-10-23 14:21:41'),
('80','4','Shira','UPDATE','goals','5','Goal edited: Testing','::1','2026-10-23 14:21:45'),
('81','4','Shira','UPDATE','habits','1','Habit edited: Jogging','::1','2026-10-23 14:40:14'),
('82','4','Shira','COMPLETE','goals','5','Goal marked as completed','::1','2026-10-23 15:06:26'),
('83','4','Shira','UPDATE','goals','5','Goal edited: Testing','::1','2026-10-23 15:06:30'),
('84','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-23 15:38:47'),
('85',NULL,'Shira','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-27 14:26:20'),
('86','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-27 14:26:22'),
('87','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-27 14:26:50'),
('88','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-27 15:03:25'),
('89','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-27 16:27:14'),
('90','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: dark','::1','2026-10-27 16:27:24'),
('91','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance: light','::1','2026-10-27 16:27:28'),
('92','4','Shira','UPDATE','goals','3','Goal edited: Help her','::1','2026-10-27 16:27:46'),
('93','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 16:48:41'),
('94','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 16:49:05'),
('95','4','Shira','CREATE','habits','3','Habit added: Workout','::1','2026-10-27 17:02:31'),
('96','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Great','::1','2026-10-27 17:07:41'),
('97','4','Shira','EDIT_MOOD','mood_logs',NULL,'Mood edited for 2026-10-27: Great','::1','2026-10-27 17:07:55'),
('98','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 17:08:21'),
('99','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 17:09:13'),
('100','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 17:09:30'),
('101','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-27 17:09:39'),
('102','2','moderator','LOGIN','users','2',NULL,'::1','2026-10-27 17:12:09'),
('103','2','moderator','LOGOUT','users','2',NULL,'::1','2026-10-27 17:14:46'),
('104','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-27 17:14:50'),
('105','4','Shira','CREATE','habits','4','Habit added: Lulu','::1','2026-10-27 17:15:38'),
('106','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-27 17:16:07'),
('107','4','Shira','UPDATE','goals','1','Goal edited: Be Fit before SDA Camp','::1','2026-10-27 17:17:03'),
('108','4','Shira','EXPORT','users','4','Exported habits','::1','2026-10-27 17:21:00'),
('109','4','Shira','EXPORT','users','4','Exported goals','::1','2026-10-27 17:21:05'),
('110','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-28 15:59:56'),
('111','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-28 16:09:00'),
('112','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-30 20:17:54'),
('113','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Great','::1','2026-10-30 20:20:43'),
('114','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Okay','::1','2026-10-30 20:21:20'),
('115','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-30 20:45:58'),
('116','5','Jason','REGISTER','users','5','New account created','::1','2026-10-30 20:46:24'),
('117','5','Jason','RESET_REQUEST','password_resets','4','Password reset code requested','::1','2026-10-30 20:46:38'),
('118','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-30 20:47:17'),
('119','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-31 16:33:13'),
('120','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-31 22:23:58'),
('121','4','Shira','CREATE','habits','5','Habit added: Umamusume Daily','::1','2026-10-31 22:24:34'),
('122','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-31 22:42:54'),
('123',NULL,'admin','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-31 22:42:58'),
('124','1','admin','RESET_REQUEST','password_resets','5','Password reset code requested','::1','2026-10-31 22:43:48'),
('125','1',NULL,'RESET_PIN_OK','password_resets','5','Reset code verified','::1','2026-10-31 22:43:54'),
('126','1','admin','PASSWORD_RESET','users','1','Password changed via reset code','::1','2026-10-31 22:44:00'),
('127','1','admin','LOGIN','users','1',NULL,'::1','2026-10-31 22:44:05'),
('128','1','admin','LOGOUT','users','1',NULL,'::1','2026-10-31 23:27:38'),
('129',NULL,'admin','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-31 23:27:47'),
('130',NULL,'admin','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-31 23:27:53'),
('131',NULL,'admin','LOGIN_FAILED','users',NULL,'Wrong credentials','::1','2026-10-31 23:27:56'),
('132','1','admin','LOGIN','users','1',NULL,'::1','2026-10-31 23:28:00'),
('133','1','admin','ROLE_CHANGE','users','4','Role of Shira: user -> moderator','::1','2026-10-31 23:28:06'),
('134','1','admin','LOGOUT','users','1',NULL,'::1','2026-10-31 23:28:20'),
('135','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-31 23:28:25'),
('136','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-31 23:36:34'),
('137','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-31 23:36:43'),
('138','4','Shira','SETTINGS_UPDATE','user_settings','4','Appearance changed','::1','2026-10-31 23:36:59'),
('139','4','Shira','LOGOUT','users','4',NULL,'::1','2026-10-31 23:37:45'),
('140','4','Shira','LOGIN','users','4',NULL,'::1','2026-10-31 23:37:51'),
('141','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Okay','::1','2026-10-31 23:41:20'),
('142','4','Shira','SAVE_MOOD','mood_logs',NULL,'Mood saved: Tired','::1','2026-10-31 23:41:38'),
('143','4','Shira','EXPORT','users','4','Exported habits','::1','2026-10-31 23:44:58');

-- Table goal_habits
DROP TABLE IF EXISTS `goal_habits`;
CREATE TABLE `goal_habits` (
  `goal_id` int(10) unsigned NOT NULL,
  `habit_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`goal_id`,`habit_id`),
  KEY `fk_gh_habit` (`habit_id`),
  CONSTRAINT `fk_gh_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`goal_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gh_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`habit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goal_habits` (`goal_id`,`habit_id`) VALUES
('1','1'),
('1','3'),
('3','2'),
('4','2'),
('5','1');

-- Table goals
DROP TABLE IF EXISTS `goals`;
CREATE TABLE `goals` (
  `goal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date NOT NULL,
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` enum('in_progress','completed') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`goal_id`),
  KEY `fk_goals_user` (`user_id`),
  KEY `fk_goals_category` (`category_id`),
  CONSTRAINT `fk_goals_category` FOREIGN KEY (`category_id`) REFERENCES `habit_categories` (`category_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_goal_progress` CHECK (`progress` between 0 and 100)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goals` (`goal_id`,`user_id`,`category_id`,`title`,`description`,`target_date`,`progress`,`status`,`created_at`) VALUES
('1','4','5','Be Fit before SDA Camp','To look fit and not like an idiotic looking guy','2026-11-28','5','in_progress','2026-10-21 06:42:25'),
('2','4','7','Wake up Early / Sleep Early','Sleepy','2026-10-21','100','completed','2026-10-21 06:44:09'),
('3','4','8','Help her','Projects and schoolwork and b w her','2026-11-28','16','in_progress','2026-10-23 09:15:53'),
('4','4','8','FINISHING THIS PROJECT TILL NOVEMBER 10',NULL,'2026-11-10','32','in_progress','2026-10-23 09:16:53'),
('5','4','11','Testing',NULL,'2026-11-10','100','completed','2026-10-23 14:21:05');

-- Table habit_categories
DROP TABLE IF EXISTS `habit_categories`;
CREATE TABLE `habit_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `fk_category_parent` (`parent_id`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `habit_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `habit_categories` (`category_id`,`name`,`parent_id`) VALUES
('1','Health',NULL),
('2','Academic',NULL),
('3','Hobby',NULL),
('4','Personal',NULL),
('5','Fitness','1'),
('6','Nutrition','1'),
('7','Sleep','1'),
('8','Programming','2'),
('9','Reading','2'),
('10','Music','3'),
('11','Sports','3'),
('12','Finance','4'),
('13','Mindfulness','4'),
('14','Work',NULL),
('15','Social',NULL),
('16','Spiritual',NULL),
('17','Home',NULL),
('18','Hydration','1'),
('19','Hygiene','1'),
('20','Medication & Check-ups','1'),
('21','Stretching & Yoga','1'),
('22','Studying','2'),
('23','Writing','2'),
('24','Language Learning','2'),
('25','Math & Science','2'),
('26','Research','2'),
('27','Gaming','3'),
('28','Art & Drawing','3'),
('29','Photography','3'),
('30','Cooking','3'),
('31','Gardening','3'),
('32','Dance','3'),
('33','Journaling','4'),
('34','Self-care','4'),
('35','Time Management','4'),
('36','Digital Detox','4'),
('37','Job Tasks','14'),
('38','Skills Training','14'),
('39','Networking','14'),
('40','Planning','14'),
('41','Family','15'),
('42','Friends','15'),
('43','Volunteering','15'),
('44','Prayer','16'),
('45','Reflection','16'),
('46','Gratitude','16'),
('47','Cleaning','17'),
('48','Chores','17'),
('49','Organizing','17');

-- Table habit_logs
DROP TABLE IF EXISTS `habit_logs`;
CREATE TABLE `habit_logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `habit_id` int(10) unsigned NOT NULL,
  `log_date` date NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `uq_habit_day` (`habit_id`,`log_date`),
  CONSTRAINT `fk_logs_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`habit_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `habit_logs` (`log_id`,`habit_id`,`log_date`,`completed`) VALUES
('1','1','2026-10-21','0'),
('2','1','2026-10-20','1'),
('3','1','2026-10-19','1'),
('4','1','2026-10-22','0'),
('5','1','2026-10-23','0'),
('10','2','2026-10-20','1'),
('11','2','2026-10-23','1'),
('21','2','2026-10-26','1'),
('24','2','2026-10-27','1'),
('25','3','2026-10-27','1'),
('26','4','2026-10-27','1'),
('27','2','2026-10-28','1'),
('28','3','2026-10-29','1'),
('30','3','2026-10-30','1'),
('32','2','2026-10-30','1'),
('35','1','2026-10-26','1'),
('36','2','2026-10-31','1'),
('37','5','2026-10-30','1'),
('38','5','2026-10-29','1'),
('39','5','2026-10-28','1'),
('40','4','2026-10-30','1'),
('41','4','2026-10-31','1'),
('44','4','2026-10-29','0'),
('45','4','2026-10-26','1'),
('46','4','2026-10-28','1');

-- Table habits
DROP TABLE IF EXISTS `habits`;
CREATE TABLE `habits` (
  `habit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`habit_id`),
  KEY `fk_habits_user` (`user_id`),
  KEY `fk_habits_category` (`category_id`),
  CONSTRAINT `fk_habits_category` FOREIGN KEY (`category_id`) REFERENCES `habit_categories` (`category_id`),
  CONSTRAINT `fk_habits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `habits` (`habit_id`,`user_id`,`category_id`,`name`,`details`,`is_active`,`created_at`) VALUES
('1','4','5','Jogging','For MORE STAMINAA','1','2026-10-20 21:06:06'),
('2','4','8','Continuing Project',NULL,'1','2026-10-23 09:14:44'),
('3','4','5','Workout',NULL,'1','2026-10-27 17:02:31'),
('4','4','5','Lulu','1x a Day','1','2026-10-27 17:15:38'),
('5','4','27','Umamusume Daily','Qouta','1','2026-10-31 22:24:34');

-- Table mood_logs
DROP TABLE IF EXISTS `mood_logs`;
CREATE TABLE `mood_logs` (
  `mood_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `mood` enum('Great','Good','Okay','Low','Frustrated','Tired') NOT NULL,
  `mood_note` text DEFAULT NULL,
  `log_date` date NOT NULL,
  PRIMARY KEY (`mood_id`),
  UNIQUE KEY `uq_mood_day` (`user_id`,`log_date`),
  CONSTRAINT `fk_mood_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mood_logs` (`mood_id`,`user_id`,`mood`,`mood_note`,`log_date`) VALUES
('1','4','Great','yhs7b8GY496Ni2LJZALKJflEafzzmq0rWYDqD3pKkGU7j2BT1nymOknUKN9jGJfPi9t7','2026-10-21'),
('2','4','Frustrated','daELpQFW2+WbKAuO4wJbt582oqkf0ZFrEzYblEXANN2u2hvPRluETEB2GlHmc10Mxi4cshZu49CD63RAp7eodU0gDrI/4UOpO9Xp+o92pKfkQhSO3SPTjRnZawwnoBceARbN/grG','2026-10-23'),
('3','4','Great','Z3NoXyMos46M+GMtaLnv3u7qzyvar96T3056iUWr3HGsievB1tyqqAJM5TjFtCZNU6o4/G7T0rT3BvGzqjyStiGRBPhkdPluJaM2Xgk3pmQTCrgb/ayI47HmzNS3OtFBg4Klv7jSWRMYHtjGvQ8N6w==','2026-10-27'),
('5','4','Okay','7Y+ST0EMR2KfVbWtggYtrgiZiB1Zhf6aBHVxrb7hGLp9dXyK5kioD+dmJ9NNG/dqOVIBAVgPnU4p3cTjYX7T8npOsjIBELaC/dldCl1+jalc/G5UzVPaPo31','2026-10-30'),
('7','4','Tired','6MJuC7ehQuAheSJmB+dhDk1ECX1m4nci2g9+A4qxnNz1BTi96N4fo1NK3PkBIyCucok=','2026-10-31');

-- Table notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `is_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`notification_id`,`user_id`,`message`,`is_cleared`,`created_at`) VALUES
('1','4','New habit added: Jogging 6AM','0','2026-10-20 21:06:06'),
('2','4','Goal completed: Wake up Early / Sleep Early','0','2026-10-21 06:44:32'),
('3','4','New habit added: Continuing Project','0','2026-10-23 09:14:44'),
('4','4','Goal completed: Testing','0','2026-10-23 15:06:26'),
('5','4','New habit added: Workout','0','2026-10-27 17:02:31'),
('6','4','New habit added: Lulu','0','2026-10-27 17:15:38'),
('7','4','New habit added: Umamusume Daily','0','2026-10-31 22:24:34');

-- Table password_resets
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `reset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  KEY `fk_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `password_resets` (`reset_id`,`user_id`,`pin_hash`,`expires_at`,`is_used`,`attempts`,`created_at`) VALUES
('1','4','$2y$10$IS4yq2m5/nyyxky1j/nbHOVG4LPw8ugW2gTHNOkcFaNOY2HC7vzoa','2026-10-20 06:14:56','1','0','2026-10-20 06:04:56'),
('2','4','$2y$10$9FkZkzCCjxJqo1BHahrmzOqZxzs0/09U5G8AQ2Lg1LImilEUvFqZG','2026-10-21 06:50:29','1','0','2026-10-21 06:40:29'),
('3','4','$2y$10$ZuVcz6N3BUzE4jTcIwP7hui4UT8rUhoiJUBh1dCAB.3/rTTVoY35m','2026-10-23 09:00:45','0','0','2026-10-23 08:50:45'),
('4','5','$2y$10$NUDAvVXyz4TArff5VhcOnuTq/pwkGmbnMZ9GDRmR6F1AYPX192hfC','2026-10-30 20:56:38','0','0','2026-10-30 20:46:38'),
('5','1','$2y$10$HFVGH0wgCQ5ZNcPwONxyFOcHBMkqxpfUkKUP5GEuyHDlQArm22ZnG','2026-10-31 22:53:48','1','0','2026-10-31 22:43:48');

-- Table roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(20) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`role_id`,`role_name`) VALUES
('1','admin'),
('2','moderator'),
('3','user');

-- Table user_settings
DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `user_id` int(10) unsigned NOT NULL,
  `appearance` enum('light','dark') NOT NULL DEFAULT 'dark',
  `notifications_on` tinyint(1) NOT NULL DEFAULT 1,
  `bg_color` char(7) DEFAULT NULL,
  `card_color` char(7) DEFAULT NULL,
  `text_color` char(7) DEFAULT NULL,
  `accent_color` char(7) DEFAULT NULL,
  `font_choice` varchar(20) NOT NULL DEFAULT 'modern',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_settings` (`user_id`,`appearance`,`notifications_on`,`bg_color`,`card_color`,`text_color`,`accent_color`,`font_choice`) VALUES
('1','dark','1',NULL,NULL,NULL,NULL,'modern'),
('2','dark','1',NULL,NULL,NULL,NULL,'modern'),
('3','dark','1',NULL,NULL,NULL,NULL,'modern'),
('4','dark','1',NULL,NULL,NULL,'#db2777','mono'),
('5','dark','1',NULL,NULL,NULL,NULL,'modern');

-- Table users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`user_id`,`full_name`,`username`,`email`,`password_hash`,`role_id`,`status`,`profile_picture`,`created_at`) VALUES
('1','Admin Account','admin','admin@mentality.test','$2y$10$k6yvvZBwgml12XZl0pM3j.vOFA3BV3wxQAT7L5E4ZfWdh3z17Uaxu','1','active',NULL,'2026-10-20 05:39:18'),
('2','Moderator Account','moderator','mod@mentality.test','$2y$10$gPdi9OvyxGiRzA94Nvfa0ua9d/fdCj0yHHrQcHjekyAF6bsM05V5G','2','active',NULL,'2026-10-20 05:39:19'),
('3','Alex Johnson','alex','alex@mentality.test','$2y$10$8rW2U7mPOXoB/sMZor/UYOBuPu3H0vNHtjaepXN.f.sQNeulaY.gm','3','active',NULL,'2026-10-20 05:39:19'),
('4','Jason','Shira','jasoncaguicla1233@gmail.com','$2y$10$kKH4lfu5H87tFMq72ZSAyermkRgUpeuZB0byoSIgZpA0QCz3G8g1e','2','active','u4_49cc473b42a4afd7.png','2026-10-20 05:42:23'),
('5','Jason','Jason','jasoncaguicla123@gmail.com','$2y$10$qYHtlM5jjg3196yPR5Z4O.oi2Y2LpttfmN7aMo5aTs/V7yurfIE96','3','active',NULL,'2026-10-30 20:46:24');

-- Function fn_goal_auto_progress
DROP FUNCTION IF EXISTS `fn_goal_auto_progress`;
DELIMITER $$
CREATE FUNCTION `fn_goal_auto_progress`(p_goal_id INT UNSIGNED, p_start DATE, p_end DATE) RETURNS int(11)
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
DELIMITER ;

-- Function fn_goal_habit_rate
DROP FUNCTION IF EXISTS `fn_goal_habit_rate`;
DELIMITER $$
CREATE FUNCTION `fn_goal_habit_rate`(p_goal_id INT UNSIGNED) RETURNS decimal(5,1)
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
DELIMITER ;

-- Procedure sp_delete_user
DROP PROCEDURE IF EXISTS `sp_delete_user`;
DELIMITER $$
CREATE PROCEDURE `sp_delete_user`(
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

-- Procedure sp_save_mood
DROP PROCEDURE IF EXISTS `sp_save_mood`;
DELIMITER $$
CREATE PROCEDURE `sp_save_mood`(
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

-- Procedure sp_set_habit_log
DROP PROCEDURE IF EXISTS `sp_set_habit_log`;
DELIMITER $$
CREATE PROCEDURE `sp_set_habit_log`(
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

-- Procedure sp_set_user_role
DROP PROCEDURE IF EXISTS `sp_set_user_role`;
DELIMITER $$
CREATE PROCEDURE `sp_set_user_role`(
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

-- Procedure sp_set_user_status
DROP PROCEDURE IF EXISTS `sp_set_user_status`;
DELIMITER $$
CREATE PROCEDURE `sp_set_user_status`(
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
DELIMITER ;

-- Procedure sp_update_goal_progress
DROP PROCEDURE IF EXISTS `sp_update_goal_progress`;
DELIMITER $$
CREATE PROCEDURE `sp_update_goal_progress`(
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
DELIMITER ;

-- View v_goal_overview
DROP VIEW IF EXISTS `v_goal_overview`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_goal_overview` AS select `g`.`goal_id` AS `goal_id`,`g`.`user_id` AS `user_id`,`g`.`title` AS `title`,`g`.`description` AS `description`,`g`.`target_date` AS `target_date`,`g`.`progress` AS `progress`,`g`.`status` AS `status`,`g`.`created_at` AS `created_at`,`c`.`name` AS `category_name`,`p`.`name` AS `parent_category`,count(`gh`.`habit_id`) AS `habit_count`,`fn_goal_habit_rate`(`g`.`goal_id`) AS `habit_rate_7d`,to_days(`g`.`target_date`) - to_days(curdate()) AS `days_left` from (((`goals` `g` left join `habit_categories` `c` on(`c`.`category_id` = `g`.`category_id`)) left join `habit_categories` `p` on(`p`.`category_id` = `c`.`parent_id`)) left join `goal_habits` `gh` on(`gh`.`goal_id` = `g`.`goal_id`)) group by `g`.`goal_id`,`g`.`user_id`,`g`.`title`,`g`.`description`,`g`.`target_date`,`g`.`progress`,`g`.`status`,`g`.`created_at`,`c`.`name`,`p`.`name`;

-- View v_habit_overview
DROP VIEW IF EXISTS `v_habit_overview`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_habit_overview` AS select `h`.`habit_id` AS `habit_id`,`h`.`user_id` AS `user_id`,`h`.`name` AS `name`,`h`.`details` AS `details`,`h`.`is_active` AS `is_active`,`h`.`created_at` AS `created_at`,`h`.`category_id` AS `category_id`,`c`.`name` AS `category_name`,`p`.`name` AS `parent_category`,count(case when `l`.`completed` = 1 then 1 end) AS `total_completed`,max(case when `l`.`completed` = 1 then `l`.`log_date` end) AS `last_completed` from (((`habits` `h` join `habit_categories` `c` on(`c`.`category_id` = `h`.`category_id`)) left join `habit_categories` `p` on(`p`.`category_id` = `c`.`parent_id`)) left join `habit_logs` `l` on(`l`.`habit_id` = `h`.`habit_id`)) group by `h`.`habit_id`,`h`.`user_id`,`h`.`name`,`h`.`details`,`h`.`is_active`,`h`.`created_at`,`h`.`category_id`,`c`.`name`,`p`.`name`;

-- View v_user_streaks
DROP VIEW IF EXISTS `v_user_streaks`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_user_streaks` AS select `grouped`.`user_id` AS `user_id`,min(`grouped`.`log_date`) AS `start_date`,max(`grouped`.`log_date`) AS `end_date`,count(0) AS `streak_len` from (select `numbered`.`user_id` AS `user_id`,`numbered`.`log_date` AS `log_date`,`numbered`.`log_date` - interval `numbered`.`rn` day AS `grp` from (select `active_days`.`user_id` AS `user_id`,`active_days`.`log_date` AS `log_date`,row_number() over ( partition by `active_days`.`user_id` order by `active_days`.`log_date`) AS `rn` from (select distinct `h`.`user_id` AS `user_id`,`l`.`log_date` AS `log_date` from (`habit_logs` `l` join `habits` `h` on(`h`.`habit_id` = `l`.`habit_id`)) where `l`.`completed` = 1) `active_days`) `numbered`) `grouped` group by `grouped`.`user_id`,`grouped`.`grp`;

-- Trigger trg_goal_before_insert
DROP TRIGGER IF EXISTS `trg_goal_before_insert`;
DELIMITER $$
CREATE TRIGGER trg_goal_before_insert
BEFORE INSERT ON goals
FOR EACH ROW
BEGIN
    IF NEW.target_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target date cannot be in the past.';
    END IF;
END$$
DELIMITER ;

-- Trigger trg_goal_before_update
DROP TRIGGER IF EXISTS `trg_goal_before_update`;
DELIMITER $$
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

-- Trigger trg_habit_added
DROP TRIGGER IF EXISTS `trg_habit_added`;
DELIMITER $$
CREATE TRIGGER trg_habit_added
AFTER INSERT ON habits
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT notifications_on FROM user_settings WHERE user_id = NEW.user_id), 1) = 1 THEN
        INSERT INTO notifications (user_id, message)
        VALUES (NEW.user_id, CONCAT('New habit added: ', NEW.name));
    END IF;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;
-- End of backup
