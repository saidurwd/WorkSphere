/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: worksphere
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `old_value` longtext DEFAULT NULL,
  `new_value` longtext DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_module_record_index` (`module_name`,`record_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  KEY `activity_subject_idx` (`subject_type`,`subject_id`),
  KEY `activity_legacy_idx` (`module_name`,`record_id`),
  KEY `activity_action_idx` (`action`,`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `activity_logs` VALUES
(1,1,'User',1,'updated','{\"remember_token\":\"g7dyPkS0eN8CKWc4XJgw4g6sqJk65iJVkexF4w91sY2dJZz4lfo4ySSN10c5\"}','{\"remember_token\":\"GAOx64XGwTV5XVP8LQ8LCJzNe5rv1CZwtcjnG5t02QjhEdBLJiq9dgABwvyg\"}','127.0.0.1','2026-10-01 13:11:21','2026-10-01 13:11:21','App\\Models\\User',1,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(2,NULL,'User',39,'created',NULL,'{\"name\":\"Theodore Lindgren\",\"email\":\"altenwerth.brenden@example.net\",\"email_verified_at\":\"2026-10-01T13:18:55.000000Z\",\"employee_id\":7,\"updated_at\":\"2026-10-01T13:18:55.000000Z\",\"created_at\":\"2026-10-01T13:18:55.000000Z\",\"id\":39}','127.0.0.1','2026-10-01 13:18:55','2026-10-01 13:18:55','App\\Models\\User',39,'Symfony'),
(3,1,'Task',51,'created',NULL,'{\"title\":\"test\",\"description\":null,\"priority\":\"medium\",\"status\":\"pending\",\"due_date\":\"2026-10-01T18:00:00.000000Z\",\"responsible_user_id\":\"1\",\"project_id\":null,\"user_id\":1,\"updated_at\":\"2026-10-02T06:04:15.000000Z\",\"created_at\":\"2026-10-02T06:04:15.000000Z\",\"id\":51}','127.0.0.1','2026-10-02 06:04:15','2026-10-02 06:04:15','Modules\\Tasks\\Models\\Task',51,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(4,1,'Task',51,'updated','{\"status\":\"pending\",\"updated_at\":\"2026-10-02 12:04:15\"}','{\"status\":\"postponed\",\"updated_at\":\"2026-10-02 12:04:32\"}','127.0.0.1','2026-10-02 06:04:32','2026-10-02 06:04:32','Modules\\Tasks\\Models\\Task',51,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(5,1,'Todo',901,'completed','{\"status\":\"in_progress\"}','{\"status\":\"completed\",\"completed_at\":\"2026-10-02 12:05:33\",\"completed_by\":1}','127.0.0.1','2026-10-02 06:05:33','2026-10-02 06:05:33','Modules\\Todos\\Models\\Todo',901,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(6,1,'Todo',901,'reopened','{\"status\":\"completed\",\"completed_at\":\"2026-10-02 12:05:33\",\"completed_by\":1}','{\"status\":\"in_progress\",\"completed_at\":null,\"completed_by\":null}','127.0.0.1','2026-10-02 06:05:40','2026-10-02 06:05:40','Modules\\Todos\\Models\\Todo',901,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(7,1,'Todo',903,'updated','{\"title\":\"another\",\"description\":null,\"priority\":\"medium\",\"visibility\":\"personal\",\"department_id\":null,\"start_date\":null,\"due_date\":null,\"due_time\":null,\"estimated_minutes\":null,\"actual_minutes\":null}','{\"title\":\"another\",\"description\":null,\"priority\":\"medium\",\"visibility\":\"personal\",\"department_id\":null,\"start_date\":null,\"due_date\":null,\"due_time\":null,\"estimated_minutes\":null,\"actual_minutes\":null}','127.0.0.1','2026-10-02 06:05:53','2026-10-02 06:05:53','Modules\\Todos\\Models\\Todo',903,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(8,1,'Todo',903,'commented',NULL,'{\"comment_id\":1}','127.0.0.1','2026-10-02 06:06:12','2026-10-02 06:06:12','Modules\\Todos\\Models\\Todo',903,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attachable_type` varchar(255) NOT NULL,
  `attachable_id` bigint(20) unsigned NOT NULL,
  `disk` varchar(40) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `attachment_subject_idx` (`attachable_type`,`attachable_id`),
  KEY `attachments_checksum_index` (`checksum`),
  CONSTRAINT `attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cache` VALUES
('worksphere-cache-dashboard:widget:completion_rate:u1:v1:0e9e3acfcd30','a:1:{i:0;a:4:{s:8:\"assignee\";N;s:7:\"created\";i:1;s:9:\"completed\";i:0;s:4:\"rate\";i:0;}}',1790922150),
('worksphere-cache-dashboard:widget:critical_deadlines:u1:v1:0e9e3acfcd30','a:0:{}',1790922150),
('worksphere-cache-dashboard:widget:department_performance:u1:v1:0e9e3acfcd30','a:0:{}',1790922150),
('worksphere-cache-dashboard:widget:my_tasks:u1:v1:0e9e3acfcd30','a:1:{i:0;a:8:{s:2:\"id\";i:51;s:5:\"title\";s:4:\"test\";s:8:\"due_date\";s:12:\"Oct 02, 2026\";s:6:\"status\";s:9:\"Postponed\";s:14:\"status_variant\";s:7:\"warning\";s:8:\"priority\";s:6:\"Medium\";s:16:\"priority_variant\";s:4:\"info\";s:3:\"url\";s:31:\"http://worksphere.test/tasks/51\";}}',1790921910),
('worksphere-cache-dashboard:widget:my_todos:u1:v1:0e9e3acfcd30','a:1:{i:0;a:8:{s:2:\"id\";i:901;s:5:\"title\";s:14:\"Nemo porro id.\";s:8:\"due_date\";s:12:\"Sep 29, 2026\";s:6:\"status\";s:11:\"In Progress\";s:14:\"status_variant\";s:7:\"primary\";s:8:\"priority\";s:6:\"Medium\";s:16:\"priority_variant\";s:4:\"info\";s:3:\"url\";s:32:\"http://worksphere.test/todos/901\";}}',1790921910),
('worksphere-cache-dashboard:widget:obligation_expiry:u1:v1:0e9e3acfcd30','a:7:{s:8:\"typeBars\";a:0:{}s:13:\"priorityDonut\";a:0:{}s:5:\"total\";i:0;s:6:\"active\";i:0;s:5:\"due_7\";i:0;s:6:\"due_30\";i:0;s:7:\"expired\";i:0;}',1790922150),
('worksphere-cache-dashboard:widget:overdue_items:u1:v1:0e9e3acfcd30','a:1:{i:0;a:5:{s:6:\"source\";s:5:\"To-Do\";s:5:\"title\";s:14:\"Nemo porro id.\";s:3:\"due\";s:12:\"Sep 29, 2026\";s:12:\"overdue_days\";i:3;s:3:\"url\";s:32:\"http://worksphere.test/todos/901\";}}',1790921910),
('worksphere-cache-dashboard:widget:personal_stats:u1:v1:0e9e3acfcd30','a:4:{s:10:\"weeklyBars\";a:7:{i:0;a:3:{s:5:\"label\";s:3:\"Mon\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}i:1;a:3:{s:5:\"label\";s:3:\"Tue\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}i:2;a:3:{s:5:\"label\";s:3:\"Wed\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}i:3;a:3:{s:5:\"label\";s:3:\"Thu\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}i:4;a:3:{s:5:\"label\";s:3:\"Fri\";s:5:\"value\";i:1;s:3:\"pct\";i:100;}i:5;a:3:{s:5:\"label\";s:3:\"Sat\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}i:6;a:3:{s:5:\"label\";s:3:\"Sun\";s:5:\"value\";i:0;s:3:\"pct\";i:0;}}s:5:\"tasks\";a:4:{s:5:\"total\";i:1;s:9:\"completed\";i:0;s:4:\"open\";i:1;s:7:\"overdue\";i:0;}s:5:\"todos\";a:4:{s:5:\"total\";i:3;s:9:\"completed\";i:0;s:4:\"open\";i:3;s:7:\"overdue\";i:1;}s:8:\"meetings\";a:4:{s:5:\"total\";i:0;s:9:\"completed\";i:0;s:4:\"open\";i:0;s:8:\"upcoming\";i:0;}}',1790921910),
('worksphere-cache-dashboard:widget:task_distribution:u1:v1:0e9e3acfcd30','a:1:{i:0;a:7:{s:6:\"status\";E:34:\"App\\Enums\\WorkItemStatus:Postponed\";s:5:\"label\";s:9:\"Postponed\";s:7:\"variant\";s:7:\"warning\";s:5:\"color\";s:17:\"var(--bs-warning)\";s:5:\"value\";i:1;s:5:\"count\";i:1;s:3:\"pct\";i:100;}}',1790922030),
('worksphere-cache-dashboard:widget:team_workload:u1:v1:0e9e3acfcd30','a:1:{i:0;a:3:{s:8:\"assignee\";N;s:4:\"open\";i:1;s:7:\"overdue\";i:0;}}',1790922030),
('worksphere-cache-dashboard:widget:todays_activity:u1:v1:0e9e3acfcd30','a:6:{i:0;a:3:{s:6:\"module\";s:4:\"Todo\";s:6:\"action\";s:9:\"commented\";s:4:\"when\";s:14:\"11 minutes ago\";}i:1;a:3:{s:6:\"module\";s:4:\"Todo\";s:6:\"action\";s:7:\"updated\";s:4:\"when\";s:14:\"11 minutes ago\";}i:2;a:3:{s:6:\"module\";s:4:\"Todo\";s:6:\"action\";s:8:\"reopened\";s:4:\"when\";s:14:\"11 minutes ago\";}i:3;a:3:{s:6:\"module\";s:4:\"Todo\";s:6:\"action\";s:9:\"completed\";s:4:\"when\";s:14:\"11 minutes ago\";}i:4;a:3:{s:6:\"module\";s:4:\"Task\";s:6:\"action\";s:7:\"updated\";s:4:\"when\";s:14:\"12 minutes ago\";}i:5;a:3:{s:6:\"module\";s:4:\"Task\";s:6:\"action\";s:7:\"created\";s:4:\"when\";s:14:\"13 minutes ago\";}}',1790921910),
('worksphere-cache-dashboard:widget:upcoming_deadlines:u1:v1:0e9e3acfcd30','a:1:{i:0;a:6:{s:6:\"source\";s:4:\"Task\";s:5:\"title\";s:4:\"test\";s:3:\"due\";s:12:\"Oct 02, 2026\";s:4:\"days\";i:0;s:4:\"sort\";s:10:\"2026-10-02\";s:3:\"url\";s:31:\"http://worksphere.test/tasks/51\";}}',1790921970),
('worksphere-cache-dashboard:widget:upcoming_meetings:u1:v1:0e9e3acfcd30','a:0:{}',1790921970),
('worksphere-cache-system:settings:v','a:13:{s:10:\"app.locale\";s:2:\"en\";s:12:\"app.timezone\";s:5:\"GMT+6\";s:15:\"app.date_format\";s:5:\"Y-m-d\";s:18:\"app.week_starts_on\";i:1;s:25:\"security.session_lifetime\";i:120;s:28:\"security.password_min_length\";i:12;s:30:\"security.password_expires_days\";i:0;s:28:\"security.max_failed_attempts\";i:5;s:35:\"notifications.reminder_default_lead\";i:30;s:25:\"notifications.digest_hour\";i:8;s:26:\"branding.organisation_name\";s:10:\"WorkSphere\";s:22:\"branding.support_email\";N;s:25:\"compliance.retention_days\";i:2555;}',1790922168);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `mentions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentions`)),
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comment_subject_idx` (`commentable_type`,`commentable_id`,`created_at`),
  KEY `comments_parent_id_index` (`parent_id`),
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `comments` VALUES
(1,'Modules\\Todos\\Models\\Todo',903,1,NULL,'@saidur',NULL,NULL,'2026-10-02 06:06:12','2026-10-02 06:06:12',NULL);
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_company_code_unique` (`company_code`),
  KEY `companies_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(255) NOT NULL,
  `department_code` varchar(255) NOT NULL,
  `head_of_department_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_department_code_unique` (`department_code`),
  KEY `departments_head_of_department_id_foreign` (`head_of_department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  UNIQUE KEY `employees_email_unique` (`email`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_location_id_foreign` (`location_id`),
  KEY `employees_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `employees` VALUES
(1,'EMP-U0001','Saidur Rahman','saidurwd@gmail.com',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(2,'EMP-U0034','Zion Kiehn','vrau@example.net',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(3,'EMP-U0035','Darian Little','sgleason@example.org',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(4,'EMP-U0036','Prof. Elwin Spencer','derrick.schiller@example.net',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(5,'EMP-U0037','Casandra Dibbert III','jacobs.hilario@example.org',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(6,'EMP-U0038','Lura Mosciski','nia42@example.net',NULL,NULL,NULL,NULL,'2026-10-01','active','2026-10-01 11:50:51','2026-10-01 11:50:51'),
(7,'EMP-6322','Prof. Darron Turner III','regan.johns@example.org','+8801004929459','Recreation and Fitness Studies Teacher',NULL,NULL,'2024-05-20','active','2026-10-01 13:18:55','2026-10-01 13:18:55');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `escalation_rules`
--

DROP TABLE IF EXISTS `escalation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `escalation_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_type_id` bigint(20) unsigned DEFAULT NULL,
  `days_before_expiry` int(11) DEFAULT NULL,
  `days_after_expiry` int(11) DEFAULT NULL,
  `escalation_level` varchar(255) NOT NULL,
  `recipient_type` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'IN_APP',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escalation_rules_obligation_type_id_foreign` (`obligation_type_id`),
  KEY `escalation_rules_department_id_foreign` (`department_id`),
  KEY `escalation_rules_company_id_foreign` (`company_id`),
  CONSTRAINT `escalation_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `escalation_rules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `escalation_rules`
--

LOCK TABLES `escalation_rules` WRITE;
/*!40000 ALTER TABLE `escalation_rules` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `escalation_rules` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `feature_flags`
--

DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'boolean',
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `rollout_percentage` tinyint(3) unsigned NOT NULL DEFAULT 100,
  `variants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variants`)),
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_roles`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`),
  KEY `feature_flags_created_by_foreign` (`created_by`),
  KEY `feature_flags_updated_by_foreign` (`updated_by`),
  KEY `feature_flags_is_enabled_index` (`is_enabled`),
  CONSTRAINT `feature_flags_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feature_flags_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_flags`
--

LOCK TABLES `feature_flags` WRITE;
/*!40000 ALTER TABLE `feature_flags` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `feature_flags` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `invitation_links`
--

DROP TABLE IF EXISTS `invitation_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitation_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `hash` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitation_links_hash_unique` (`hash`),
  KEY `invitation_links_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invitation_links`
--

LOCK TABLES `invitation_links` WRITE;
/*!40000 ALTER TABLE `invitation_links` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `invitation_links` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `invitation_referrals`
--

DROP TABLE IF EXISTS `invitation_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitation_referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invitation_link_id` bigint(20) unsigned NOT NULL,
  `referred_user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invitation_referrals_invitation_link_id_index` (`invitation_link_id`),
  KEY `invitation_referrals_referred_user_id_index` (`referred_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invitation_referrals`
--

LOCK TABLES `invitation_referrals` WRITE;
/*!40000 ALTER TABLE `invitation_referrals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `invitation_referrals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `jobs` VALUES
(19,'default','{\"uuid\":\"35cbff04-edea-4761-8c4e-8b532b7ff7e8\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:43:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:34:\\\"Modules\\\\Todos\\\\Events\\\\TodoCompleted\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790836234,\"delay\":null}',0,NULL,1790836234,1790836234),
(20,'default','{\"uuid\":\"be145b38-dc87-43fc-a367-cb5c18fbe9dc\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:42:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:33:\\\"Modules\\\\Todos\\\\Events\\\\TodoReopened\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790836240,\"delay\":null}',0,NULL,1790836240,1790836240),
(21,'default','{\"uuid\":\"a089c18e-dc96-470e-a970-f9a172da3ff3\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:43:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:34:\\\"Modules\\\\Todos\\\\Events\\\\TodoCompleted\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790853393,\"delay\":null}',0,NULL,1790853393,1790853393),
(22,'default','{\"uuid\":\"3f7d75dd-9047-4a42-91d3-40f9b1af8ad8\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:42:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:33:\\\"Modules\\\\Todos\\\\Events\\\\TodoReopened\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790853396,\"delay\":null}',0,NULL,1790853396,1790853396),
(23,'default','{\"uuid\":\"38621161-ddf6-4e6c-8a26-cba2d03f6b16\",\"displayName\":\"Modules\\\\Tasks\\\\Listeners\\\\SendTaskAssignedNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:52:\\\"Modules\\\\Tasks\\\\Listeners\\\\SendTaskAssignedNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:32:\\\"Modules\\\\Tasks\\\\Events\\\\TaskCreated\\\":1:{s:4:\\\"task\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Tasks\\\\Models\\\\Task\\\";s:2:\\\"id\\\";i:51;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790921055,\"delay\":null}',0,NULL,1790921055,1790921055),
(24,'default','{\"uuid\":\"37ad679f-28bf-4ed9-b3f6-d219b933b39c\",\"displayName\":\"Modules\\\\Tasks\\\\Listeners\\\\SendTaskUpdateNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:50:\\\"Modules\\\\Tasks\\\\Listeners\\\\SendTaskUpdateNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:32:\\\"Modules\\\\Tasks\\\\Events\\\\TaskUpdated\\\":1:{s:4:\\\"task\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Tasks\\\\Models\\\\Task\\\";s:2:\\\"id\\\";i:51;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790921072,\"delay\":null}',0,NULL,1790921072,1790921072),
(25,'default','{\"uuid\":\"405889e1-1e41-429b-8cf4-66b0ff11fe1e\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:43:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCompleted\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:34:\\\"Modules\\\\Todos\\\\Events\\\\TodoCompleted\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790921133,\"delay\":null}',0,NULL,1790921133,1790921133),
(26,'default','{\"uuid\":\"400aba24-ff87-4eca-a5f4-cd8b1188fbef\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:42:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoReopened\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:33:\\\"Modules\\\\Todos\\\\Events\\\\TodoReopened\\\":2:{s:4:\\\"todo\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"Modules\\\\Todos\\\\Models\\\\Todo\\\";s:2:\\\"id\\\";i:901;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790921140,\"delay\":null}',0,NULL,1790921140,1790921140),
(27,'default','{\"uuid\":\"350b1284-059e-4133-b02d-24353da5a316\",\"displayName\":\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCommented\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":30:{s:5:\\\"class\\\";s:43:\\\"Modules\\\\Todos\\\\Listeners\\\\NotifyTodoCommented\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:34:\\\"Modules\\\\Todos\\\\Events\\\\TodoCommented\\\":2:{s:7:\\\"comment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Comment\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"actorId\\\";i:1;}}s:5:\\\"tries\\\";N;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";N;s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:23:\\\"deleteWhenMissingModels\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:10:\\\"debounceId\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:13:\\\"debounceOwner\\\";s:0:\\\"\\\";s:15:\\\"uniqueLockOwner\\\";s:0:\\\"\\\";s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790921172,\"delay\":null}',0,NULL,1790921172,1790921172);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) NOT NULL,
  `location_code` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_location_code_unique` (`location_code`),
  KEY `locations_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `locations` VALUES
(1,'Head Office','HQ','12 Gulshan Avenue','Dhaka','Bangladesh','active','2026-09-29 20:40:53','2026-09-29 20:40:53'),
(2,'Uttara Branch','UTT','45 Uttara','Dhaka','Bangladesh','active','2026-09-29 20:40:53','2026-09-29 20:40:53'),
(3,'Chittagong Hub','CTG','8 Agrabad','Chittagong','Bangladesh','active','2026-09-29 20:40:53','2026-09-29 20:40:53'),
(4,'Remote / WFH','REM',NULL,NULL,NULL,'active','2026-09-29 20:40:53','2026-09-29 20:40:53');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `event` varchar(255) NOT NULL DEFAULT 'login',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_logs_user_id_attempted_at_index` (`user_id`,`attempted_at`),
  KEY `login_logs_event_index` (`event`),
  KEY `login_logs_email_index` (`email`),
  KEY `login_logs_ip_address_index` (`ip_address`),
  KEY `login_logs_attempted_at_index` (`attempted_at`),
  CONSTRAINT `login_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `login_logs` VALUES
(1,1,'saidurwd@gmail.com','login','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0',NULL,'2026-10-01 04:21:13','2026-10-01 04:21:13','2026-10-01 04:21:13'),
(2,1,'saidurwd@gmail.com','login','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0',NULL,'2026-10-01 06:30:18','2026-10-01 06:30:18','2026-10-01 06:30:18'),
(3,1,'saidurwd@gmail.com','logout','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0',NULL,'2026-10-01 13:11:21','2026-10-01 13:11:21','2026-10-01 13:11:21'),
(4,1,'saidurwd@gmail.com','login','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0',NULL,'2026-10-01 13:11:29','2026-10-01 13:11:29','2026-10-01 13:11:29'),
(5,1,'saidurwd@gmail.com','login','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0',NULL,'2026-10-02 03:23:46','2026-10-02 03:23:46','2026-10-02 03:23:46');
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_action_items`
--

DROP TABLE IF EXISTS `meeting_action_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_action_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `agenda_id` bigint(20) unsigned DEFAULT NULL,
  `discussion_id` bigint(20) unsigned DEFAULT NULL,
  `decision_id` bigint(20) unsigned DEFAULT NULL,
  `action_no` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `assigned_department_id` bigint(20) unsigned DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('open','in_progress','on_hold','completed','cancelled') NOT NULL DEFAULT 'open',
  `completion_percentage` int(10) unsigned NOT NULL DEFAULT 0,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_action_items_agenda_id_foreign` (`agenda_id`),
  KEY `meeting_action_items_discussion_id_foreign` (`discussion_id`),
  KEY `meeting_action_items_decision_id_foreign` (`decision_id`),
  KEY `meeting_action_items_completed_by_foreign` (`completed_by`),
  KEY `meeting_action_items_created_by_foreign` (`created_by`),
  KEY `meeting_action_items_updated_by_foreign` (`updated_by`),
  KEY `meeting_action_items_meeting_id_index` (`meeting_id`),
  KEY `meeting_action_items_assigned_to_index` (`assigned_to`),
  KEY `meeting_action_items_assigned_department_id_index` (`assigned_department_id`),
  KEY `meeting_action_items_due_date_index` (`due_date`),
  KEY `meeting_action_items_status_index` (`status`),
  KEY `meeting_action_items_task_id_index` (`task_id`),
  KEY `meeting_action_items_priority_index` (`priority`),
  KEY `meeting_action_items_action_no_index` (`action_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_action_items`
--

LOCK TABLES `meeting_action_items` WRITE;
/*!40000 ALTER TABLE `meeting_action_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_action_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_agendas`
--

DROP TABLE IF EXISTS `meeting_agendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_agendas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `agenda_no` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `presented_by` bigint(20) unsigned DEFAULT NULL,
  `estimated_minutes` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','in_progress','completed','skipped') NOT NULL DEFAULT 'pending',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_agendas_presented_by_foreign` (`presented_by`),
  KEY `meeting_agendas_meeting_id_index` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_agendas`
--

LOCK TABLES `meeting_agendas` WRITE;
/*!40000 ALTER TABLE `meeting_agendas` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_agendas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_attachments`
--

DROP TABLE IF EXISTS `meeting_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned DEFAULT NULL,
  `discussion_id` bigint(20) unsigned DEFAULT NULL,
  `decision_id` bigint(20) unsigned DEFAULT NULL,
  `action_item_id` bigint(20) unsigned DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `meeting_attachments_meeting_id_index` (`meeting_id`),
  KEY `meeting_attachments_discussion_id_index` (`discussion_id`),
  KEY `meeting_attachments_decision_id_index` (`decision_id`),
  KEY `meeting_attachments_action_item_id_index` (`action_item_id`),
  CONSTRAINT `meeting_attachments_action_item_id_foreign` FOREIGN KEY (`action_item_id`) REFERENCES `meeting_action_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_attachments_decision_id_foreign` FOREIGN KEY (`decision_id`) REFERENCES `meeting_decisions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_attachments_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `meeting_discussions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_attachments_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_attachments`
--

LOCK TABLES `meeting_attachments` WRITE;
/*!40000 ALTER TABLE `meeting_attachments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_attachments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_decisions`
--

DROP TABLE IF EXISTS `meeting_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `agenda_id` bigint(20) unsigned DEFAULT NULL,
  `discussion_id` bigint(20) unsigned DEFAULT NULL,
  `decision_no` int(10) unsigned NOT NULL,
  `decision_title` varchar(255) NOT NULL,
  `decision_description` text DEFAULT NULL,
  `decision_type` enum('approved','rejected','deferred','noted','further_discussion_required') NOT NULL DEFAULT 'approved',
  `decision_status` enum('active','superseded','cancelled') NOT NULL DEFAULT 'active',
  `decision_date` date DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_decisions_agenda_id_foreign` (`agenda_id`),
  KEY `meeting_decisions_discussion_id_foreign` (`discussion_id`),
  KEY `meeting_decisions_approved_by_foreign` (`approved_by`),
  KEY `meeting_decisions_created_by_foreign` (`created_by`),
  KEY `meeting_decisions_updated_by_foreign` (`updated_by`),
  KEY `meeting_decisions_meeting_id_index` (`meeting_id`),
  KEY `meeting_decisions_decision_status_index` (`decision_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_decisions`
--

LOCK TABLES `meeting_decisions` WRITE;
/*!40000 ALTER TABLE `meeting_decisions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_decisions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_discussions`
--

DROP TABLE IF EXISTS `meeting_discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_discussions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `agenda_id` bigint(20) unsigned NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `discussion` text DEFAULT NULL,
  `key_points` text DEFAULT NULL,
  `discussion_by` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_discussions_discussion_by_foreign` (`discussion_by`),
  KEY `meeting_discussions_created_by_foreign` (`created_by`),
  KEY `meeting_discussions_updated_by_foreign` (`updated_by`),
  KEY `meeting_discussions_meeting_id_index` (`meeting_id`),
  KEY `meeting_discussions_agenda_id_index` (`agenda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_discussions`
--

LOCK TABLES `meeting_discussions` WRITE;
/*!40000 ALTER TABLE `meeting_discussions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_discussions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_minutes_approvals`
--

DROP TABLE IF EXISTS `meeting_minutes_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_minutes_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `step_no` int(10) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected','returned','skipped') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_minutes_approvals_approver_id_foreign` (`approver_id`),
  KEY `meeting_minutes_approvals_meeting_id_index` (`meeting_id`),
  CONSTRAINT `meeting_minutes_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_minutes_approvals`
--

LOCK TABLES `meeting_minutes_approvals` WRITE;
/*!40000 ALTER TABLE `meeting_minutes_approvals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_minutes_approvals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_notification_logs`
--

DROP TABLE IF EXISTS `meeting_notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned DEFAULT NULL,
  `action_item_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `notification_type` varchar(255) NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PENDING',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_notification_logs_meeting_id_index` (`meeting_id`),
  KEY `meeting_notification_logs_action_item_id_index` (`action_item_id`),
  KEY `meeting_notification_logs_user_id_index` (`user_id`),
  KEY `meeting_notification_logs_status_index` (`status`),
  KEY `meeting_notification_logs_channel_index` (`channel`),
  KEY `meeting_notification_logs_notification_type_index` (`notification_type`),
  KEY `meeting_notification_logs_scheduled_at_index` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_notification_logs`
--

LOCK TABLES `meeting_notification_logs` WRITE;
/*!40000 ALTER TABLE `meeting_notification_logs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_notification_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_participants`
--

DROP TABLE IF EXISTS `meeting_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `participant_type` enum('organizer','chairperson','member','guest','presenter','observer') NOT NULL DEFAULT 'member',
  `attendance_status` enum('invited','accepted','declined','present','absent','apology') NOT NULL DEFAULT 'invited',
  `invited_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_participants_meeting_id_user_id_unique` (`meeting_id`,`user_id`),
  KEY `meeting_participants_meeting_id_index` (`meeting_id`),
  KEY `meeting_participants_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_participants`
--

LOCK TABLES `meeting_participants` WRITE;
/*!40000 ALTER TABLE `meeting_participants` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_participants` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_recurrences`
--

DROP TABLE IF EXISTS `meeting_recurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_recurrences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `recurrence_type` enum('daily','weekly','biweekly','monthly','quarterly','yearly','custom') NOT NULL DEFAULT 'weekly',
  `recurrence_interval` int(10) unsigned NOT NULL DEFAULT 1,
  `day_of_week` varchar(255) DEFAULT NULL,
  `day_of_month` int(10) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `occurrences` int(10) unsigned DEFAULT NULL,
  `next_occurrence` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_recurrences_meeting_id_foreign` (`meeting_id`),
  KEY `meeting_recurrences_next_occurrence_index` (`next_occurrence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_recurrences`
--

LOCK TABLES `meeting_recurrences` WRITE;
/*!40000 ALTER TABLE `meeting_recurrences` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_recurrences` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_tag_map`
--

DROP TABLE IF EXISTS `meeting_tag_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_tag_map` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_tag_map_meeting_id_tag_id_unique` (`meeting_id`,`tag_id`),
  KEY `meeting_tag_map_meeting_id_index` (`meeting_id`),
  KEY `meeting_tag_map_tag_id_index` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_tag_map`
--

LOCK TABLES `meeting_tag_map` WRITE;
/*!40000 ALTER TABLE `meeting_tag_map` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_tag_map` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_tags`
--

DROP TABLE IF EXISTS `meeting_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_tags_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_tags`
--

LOCK TABLES `meeting_tags` WRITE;
/*!40000 ALTER TABLE `meeting_tags` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_tags` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_template_agendas`
--

DROP TABLE IF EXISTS `meeting_template_agendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_template_agendas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_template_agendas_template_id_index` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_template_agendas`
--

LOCK TABLES `meeting_template_agendas` WRITE;
/*!40000 ALTER TABLE `meeting_template_agendas` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_template_agendas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_templates`
--

DROP TABLE IF EXISTS `meeting_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `meeting_type_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `default_duration` int(10) unsigned DEFAULT NULL,
  `default_location` varchar(255) DEFAULT NULL,
  `default_priority` enum('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_templates_meeting_type_id_foreign` (`meeting_type_id`),
  KEY `meeting_templates_created_by_foreign` (`created_by`),
  KEY `meeting_templates_updated_by_foreign` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_templates`
--

LOCK TABLES `meeting_templates` WRITE;
/*!40000 ALTER TABLE `meeting_templates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_templates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_types`
--

DROP TABLE IF EXISTS `meeting_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_types_code_unique` (`code`),
  KEY `meeting_types_created_by_foreign` (`created_by`),
  KEY `meeting_types_updated_by_foreign` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_types`
--

LOCK TABLES `meeting_types` WRITE;
/*!40000 ALTER TABLE `meeting_types` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_versions`
--

DROP TABLE IF EXISTS `meeting_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `version_no` int(10) unsigned NOT NULL,
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_data`)),
  `change_summary` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_versions_created_by_foreign` (`created_by`),
  KEY `meeting_versions_meeting_id_index` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_versions`
--

LOCK TABLES `meeting_versions` WRITE;
/*!40000 ALTER TABLE `meeting_versions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meeting_versions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_no` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `meeting_type_id` bigint(20) unsigned NOT NULL,
  `organizer_id` bigint(20) unsigned DEFAULT NULL,
  `chairperson_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `status` enum('scheduled','in_progress','completed','cancelled','postponed') NOT NULL DEFAULT 'scheduled',
  `priority` enum('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `description` text DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `minutes_status` enum('draft','prepared','submitted','under_review','approved','published') NOT NULL DEFAULT 'draft',
  `minutes_prepared_by` bigint(20) unsigned DEFAULT NULL,
  `minutes_prepared_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meetings_meeting_no_unique` (`meeting_no`),
  KEY `meetings_chairperson_id_foreign` (`chairperson_id`),
  KEY `meetings_minutes_prepared_by_foreign` (`minutes_prepared_by`),
  KEY `meetings_approved_by_foreign` (`approved_by`),
  KEY `meetings_created_by_foreign` (`created_by`),
  KEY `meetings_updated_by_foreign` (`updated_by`),
  KEY `meetings_meeting_no_index` (`meeting_no`),
  KEY `meetings_meeting_date_index` (`meeting_date`),
  KEY `meetings_meeting_type_id_index` (`meeting_type_id`),
  KEY `meetings_organizer_id_index` (`organizer_id`),
  KEY `meetings_department_id_index` (`department_id`),
  KEY `meetings_status_index` (`status`),
  KEY `meetings_minutes_status_index` (`minutes_status`),
  KEY `meetings_location_id_foreign` (`location_id`),
  KEY `meetings_template_id_foreign` (`template_id`),
  FULLTEXT KEY `meetings_fulltext_idx` (`title`,`description`),
  CONSTRAINT `meetings_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meetings_organizer_id_foreign` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meetings_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `meeting_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2022_05_17_181447_create_roles_table',2),
(5,'2022_05_17_181456_create_user_roles_table',2),
(6,'2024_01_01_000000_create_social_accounts_table',2),
(7,'2024_01_01_000001_add_two_factor_columns_to_users_table',2),
(8,'2024_01_01_000002_create_invitation_system_tables',2),
(9,'2025_01_01_000001_create_media_table',2),
(10,'2025_01_01_000001_create_privileges_table',2),
(11,'2025_01_01_000002_create_privilege_role_table',2),
(12,'2025_01_01_000002_create_starred_import_images_table',2),
(13,'2025_01_01_000003_add_suspension_columns_to_users_table',2),
(14,'2025_02_08_000000_add_profile_photo_to_users_table',2),
(15,'2026_02_15_000000_create_tyro_audit_logs_table',2),
(16,'2026_06_17_054050_create_personal_access_tokens_table',2),
(17,'2026_06_17_120000_create_tasks_table',3),
(18,'2026_06_17_130000_add_responsible_user_id_to_tasks_table',4),
(19,'2026_07_09_000001_create_departments_table',5),
(20,'2026_07_09_000002_create_locations_table',5),
(21,'2026_07_09_000003_create_employees_table',5),
(22,'2026_07_09_000004_create_asset_categories_table',5),
(23,'2026_07_09_000005_create_asset_sub_categories_table',5),
(24,'2026_07_09_000006_create_vendors_table',5),
(25,'2026_07_09_000007_create_assets_table',5),
(26,'2026_07_09_000008_create_asset_assignments_table',5),
(27,'2026_07_09_000009_create_asset_transfers_table',5),
(28,'2026_07_09_000010_create_purchase_orders_table',5),
(29,'2026_07_09_000011_create_purchase_order_details_table',5),
(30,'2026_07_09_000012_create_goods_receipts_table',5),
(31,'2026_07_09_000013_create_goods_receipt_details_table',5),
(32,'2026_07_09_000014_create_software_products_table',5),
(33,'2026_07_09_000015_create_software_licenses_table',5),
(34,'2026_07_09_070842_alter_departments_head_and_status',6),
(35,'2026_07_09_074111_alter_locations_status_enum',7),
(36,'2026_07_09_081849_alter_employees_status_enum',8),
(37,'2026_07_09_092609_add_timestamps_to_asset_categories_table',9),
(38,'2026_07_09_154214_add_timestamps_to_asset_sub_categories_table',10),
(39,'2026_07_09_000016_create_software_installations_table',11),
(40,'2026_07_09_000017_create_maintenance_requests_table',12),
(41,'2026_07_09_000018_create_maintenance_history_table',12),
(42,'2026_07_09_000019_create_asset_audits_table',12),
(43,'2026_07_09_000020_create_asset_audit_details_table',12),
(44,'2026_07_09_000021_create_asset_disposals_table',12),
(45,'2026_07_09_000022_create_asset_documents_table',12),
(46,'2026_07_09_000023_add_employee_id_and_status_to_users_table',12),
(47,'2026_07_09_000024_create_roles_table',13),
(50,'2026_07_09_000027_create_user_roles_table',16),
(52,'2026_07_10_090046_update_assets_status_enums',18),
(53,'2026_07_10_110816_add_category_code_to_asset_categories_table',19),
(54,'2026_07_10_141336_add_timestamps_to_asset_documents_table',20),
(55,'2026_07_11_173611_add_timestamps_to_software_tables',21),
(56,'2026_07_11_175813_change_installed_by_to_user_foreign_on_software_installations',22),
(58,'2026_07_11_183640_add_timestamps_to_maintenance_history_table',23),
(59,'2026_07_11_184302_change_completed_by_to_user_foreign_on_maintenance_history',24),
(60,'2026_07_11_185152_change_auditor_name_to_user_foreign_on_asset_audits',25),
(61,'2026_07_11_185740_add_timestamps_to_asset_audits_table',26),
(62,'2026_07_11_190658_add_timestamps_to_asset_audit_details_table',27),
(63,'2026_07_11_191218_add_timestamps_to_asset_disposals_table',28),
(64,'2026_07_11_191218_change_approved_by_to_user_foreign_on_asset_disposals',28),
(65,'2026_07_09_000025_create_permissions_table',29),
(66,'2026_07_09_000026_create_role_permissions_table',29),
(67,'2026_07_09_000028_create_activity_logs_table',29),
(68,'2026_07_11_192212_add_timestamps_to_permissions_table',30),
(69,'2026_07_11_192454_make_role_permissions_surrogate_key_and_timestamps',31),
(70,'2026_07_11_194336_add_updated_at_to_activity_logs_table',32),
(71,'2026_07_11_195432_change_created_and_approved_by_to_user_foreign_on_purchase_orders',33),
(72,'2026_07_11_200151_add_timestamps_to_purchase_order_details_table',34),
(73,'2026_07_11_200639_improve_goods_receipts_schema',35),
(74,'2026_07_11_201213_add_timestamps_to_goods_receipt_details_table',36),
(75,'2026_07_14_000002_add_checked_by_and_verified_by_to_maintenance_history_table',37),
(76,'2026_07_15_133000_create_task_transfers_table',38),
(77,'2026_07_15_170200_create_estate_residence_types_table',39),
(78,'2026_07_15_170100_create_estate_divisions_table',40),
(79,'2026_07_15_170000_create_estates_table',41),
(80,'2026_07_15_170300_create_estate_staff_table',42),
(81,'2026_07_20_110000_create_task_projects_table',43),
(82,'2026_07_20_110100_add_project_id_to_tasks_table',43),
(83,'2026_07_20_140000_add_attachment_to_tasks_table',44),
(84,'2026_07_21_144401_create_task_remarks_table',45),
(85,'2026_08_10_105247_update_estate_divisions_unique_constraint',46),
(86,'2026_08_11_072021_add_composite_unique_to_estate_staff',47),
(87,'2026_08_24_104503_create_tbl_account_info_table',48),
(88,'2026_08_24_182838_add_hazira_column_to_tbl_account_info_table',49),
(89,'2026_08_25_000001_create_companies_table',50),
(90,'2026_08_25_000002_create_obligation_types_table',50),
(91,'2026_08_25_000003_create_obligation_categories_table',50),
(92,'2026_08_25_000004_create_obligations_table',50),
(93,'2026_08_25_000005_create_obligation_responsibilities_table',50),
(94,'2026_08_25_000006_create_obligation_renewals_table',50),
(95,'2026_08_25_000007_create_obligation_documents_table',50),
(96,'2026_08_25_000008_create_obligation_activity_logs_table',50),
(97,'2026_08_25_000009_create_notification_rules_table',50),
(98,'2026_08_25_000010_create_notification_logs_table',50),
(99,'2026_08_25_000011_create_escalation_rules_table',50),
(100,'2026_08_25_000012_create_approval_workflows_table',50),
(101,'2026_08_25_000013_create_approval_workflow_steps_table',50),
(102,'2026_08_25_000014_add_obligation_id_to_tasks_table',50),
(103,'2026_08_28_132030_create_meeting_types_table',51),
(104,'2026_08_28_132032_create_meetings_table',51),
(105,'2026_08_28_132033_create_meeting_participants_table',51),
(106,'2026_08_28_132034_create_meeting_agendas_table',51),
(107,'2026_08_28_132036_create_meeting_discussions_table',51),
(108,'2026_08_28_132037_create_meeting_decisions_table',51),
(109,'2026_08_28_132038_create_meeting_action_items_table',51),
(110,'2026_08_28_132040_create_meeting_attachments_table',51),
(111,'2026_08_28_132041_create_meeting_recurrences_table',51),
(112,'2026_08_28_132043_create_meeting_templates_table',51),
(113,'2026_08_28_132044_create_meeting_template_agendas_table',51),
(114,'2026_08_28_132045_create_meeting_tags_table',51),
(115,'2026_08_28_132047_create_meeting_tag_map_table',51),
(116,'2026_08_28_132048_create_meeting_versions_table',51),
(117,'2026_08_28_132049_create_meeting_minutes_approvals_table',51),
(118,'2026_08_29_000001_create_meeting_notification_logs_table',51),
(119,'2026_08_29_000002_create_task_notification_logs_table',52),
(120,'2026_08_30_121723_update_priority_enum_in_meeting_action_items_table',53),
(121,'2026_09_29_092354_drop_itam_tables',54),
(122,'2026_09_29_093458_drop_estate_tables',55),
(123,'2026_09_29_093652_drop_gate_passes_table',55),
(124,'2026_09_29_100000_create_password_reset_tokens_table',56),
(125,'2026_09_29_152053_create_roles_table',57),
(126,'2026_09_29_152054_create_user_roles_table',57),
(127,'2026_09_29_152055_create_login_logs_table',57),
(128,'2026_09_29_152056_create_tyro_audit_logs_table',57),
(129,'2026_09_30_140730_add_avatar_to_users_table',58),
(130,'2026_09_30_231022_add_ip_and_user_agent_to_tyro_audit_logs_table',59),
(131,'2026_09_30_231127_add_indexes_to_existing_tables',59),
(132,'2026_09_30_231336_soften_ownership_foreign_keys_to_null_on_delete',59),
(133,'2026_09_30_231712_add_timestamps_to_role_permissions_table',59),
(134,'2026_10_01_000001_create_todos_table',60),
(135,'2026_10_01_000002_create_todo_watchers_table',60),
(136,'2026_10_01_000003_create_todo_checklist_items_table',60),
(137,'2026_10_01_000004_create_todo_links_table',60),
(138,'2026_10_01_000005_create_comments_table',60),
(139,'2026_10_01_000006_create_attachments_table',60),
(140,'2026_10_01_000007_create_tags_and_taggables_tables',60),
(141,'2026_10_01_000008_create_taggables_table',60),
(142,'2026_10_01_000009_create_reminders_table',60),
(143,'2026_10_01_000010_consolidate_notification_logs_for_polymorphic_subjects',60),
(144,'2026_10_01_000011_create_notification_preferences_table',60),
(145,'2026_10_01_000012_create_notifications_table',60),
(146,'2026_10_01_000013_add_polymorphic_columns_to_activity_logs_table',60),
(147,'2026_10_01_000014_create_work_items_view',60),
(148,'2026_10_02_000001_widen_tasks_status_enum',61),
(149,'2026_10_02_000002_make_tasks_due_date_nullable',61),
(150,'2026_10_02_000003_add_parent_id_to_tasks_table',61),
(151,'2026_10_02_000004_create_task_watchers_table',61),
(152,'2026_10_02_000005_create_time_entries_table',61),
(153,'2026_10_02_000006_add_minutes_to_tasks_table',61),
(154,'2026_10_02_000007_add_location_id_to_meetings_table',61),
(158,'2026_10_04_000001_add_scope_to_escalation_rules',63),
(159,'2026_10_02_000008_stop_meeting_attachments_cascading',64),
(160,'2026_10_02_000009_add_template_id_to_meetings_table',64),
(161,'2026_10_03_000001_add_fulltext_indexes_for_search',64),
(162,'2026_10_04_000002_drop_approval_workflow_tables',65),
(163,'2026_10_04_000003_make_users_employee_id_not_null',66),
(164,'2026_10_05_000001_create_settings_table',67),
(165,'2026_10_05_000002_create_feature_flags_table',67),
(167,'2026_10_05_000003_add_description_to_roles_table',68),
(169,'2026_10_02_115814_add_postponed_to_tasks_status_enum',69);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notification_logs`
--

DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `notification_rule_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `notification_type` varchar(255) NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PENDING',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `dedupe_key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_logs_dedupe_key_unique` (`dedupe_key`),
  KEY `notification_logs_obligation_id_index` (`obligation_id`),
  KEY `notification_logs_user_id_index` (`user_id`),
  KEY `notification_logs_notification_rule_id_index` (`notification_rule_id`),
  KEY `notification_logs_status_index` (`status`),
  KEY `notification_logs_channel_index` (`channel`),
  KEY `notification_logs_notification_type_index` (`notification_type`),
  KEY `notification_logs_scheduled_at_index` (`scheduled_at`),
  KEY `notiflog_subject_idx` (`subject_type`,`subject_id`),
  KEY `notiflog_dispatch_idx` (`status`,`scheduled_at`),
  KEY `notiflog_user_idx` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_logs`
--

LOCK TABLES `notification_logs` WRITE;
/*!40000 ALTER TABLE `notification_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `notification_logs` VALUES
(17,NULL,1,NULL,'database,mail','todo.overdue','2026-10-01 04:45:40',NULL,'PENDING',NULL,NULL,0,NULL,NULL,'2026-10-01 04:45:40','2026-10-01 04:45:40','Modules\\Todos\\Models\\Todo',901,'todo.overdue:901:2026-10-01');
/*!40000 ALTER TABLE `notification_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `notification_type` varchar(60) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notif_pref_unique` (`user_id`,`notification_type`,`channel`),
  CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notification_rules`
--

DROP TABLE IF EXISTS `notification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_type_id` bigint(20) unsigned DEFAULT NULL,
  `days_before_expiry` int(11) NOT NULL,
  `notification_level` varchar(255) NOT NULL,
  `recipient_type` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'IN_APP',
  `subject_template` varchar(255) DEFAULT NULL,
  `message_template` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_rules_obligation_type_id_foreign` (`obligation_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_rules`
--

LOCK TABLES `notification_rules` WRITE;
/*!40000 ALTER TABLE `notification_rules` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `notification_rules` VALUES
(1,NULL,90,'SPECIFIC_USER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(2,NULL,60,'MANAGER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(3,NULL,30,'SPECIFIC_USER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(4,NULL,15,'SPECIFIC_USER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(5,NULL,7,'SPECIFIC_USER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(6,NULL,3,'MANAGER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(7,NULL,1,'SPECIFIC_USER','OWNER','IN_APP','Reminder: {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(8,NULL,90,'OWNER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(9,NULL,60,'MANAGER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(10,NULL,30,'DEPARTMENT_HEAD','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(11,NULL,15,'OWNER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(12,NULL,7,'OWNER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(13,NULL,3,'BACKUP_OWNER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43'),
(14,NULL,1,'MANAGER','OWNER','EMAIL','[Reminder] {obligation_title} expires in {days_remaining} days','Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.',1,'2026-08-27 15:35:43','2026-08-27 15:35:43');
/*!40000 ALTER TABLE `notification_rules` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_unread_index` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `notifications` VALUES
('6fc6771b-97cf-4086-a8c5-dc6218921e5e','Modules\\Todos\\Notifications\\TodoNotification','App\\Models\\User',1,'{\"todo_id\":901,\"title\":\"Nemo porro id.\",\"type\":\"todo.overdue\",\"message\":\"\\\"Nemo porro id.\\\" is overdue.\"}','2026-10-01 11:16:23','2026-10-01 04:45:40','2026-10-01 11:16:23'),
('97dcf0a8-78b1-4632-8943-5131909cf9ac','Modules\\Todos\\Notifications\\TodoNotification','App\\Models\\User',34,'{\"todo_id\":1,\"title\":\"Deserunt pariatur autem dolor et.\",\"type\":\"todo.overdue\",\"message\":\"\\\"Deserunt pariatur autem dolor et.\\\" is overdue.\"}',NULL,'2026-10-01 04:44:25','2026-10-01 04:44:25'),
('e9f3d9e8-04b0-4f3c-9bca-153aa0ab0960','Modules\\Todos\\Notifications\\TodoNotification','App\\Models\\User',36,'{\"todo_id\":900,\"title\":\"Voluptatem eos odio omnis unde voluptas.\",\"type\":\"todo.overdue\",\"message\":\"\\\"Voluptatem eos odio omnis unde voluptas.\\\" is overdue.\"}',NULL,'2026-10-01 04:44:28','2026-10-01 04:44:28');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_activity_logs`
--

DROP TABLE IF EXISTS `obligation_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `obligation_activity_logs_obligation_id_index` (`obligation_id`),
  KEY `obligation_activity_logs_user_id_index` (`user_id`),
  KEY `obligation_activity_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_activity_logs`
--

LOCK TABLES `obligation_activity_logs` WRITE;
/*!40000 ALTER TABLE `obligation_activity_logs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_categories`
--

DROP TABLE IF EXISTS `obligation_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `obligation_categories_category_name_unique` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_categories`
--

LOCK TABLES `obligation_categories` WRITE;
/*!40000 ALTER TABLE `obligation_categories` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_documents`
--

DROP TABLE IF EXISTS `obligation_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `document_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `obligation_documents_obligation_id_foreign` (`obligation_id`),
  KEY `obligation_documents_uploaded_by_foreign` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_documents`
--

LOCK TABLES `obligation_documents` WRITE;
/*!40000 ALTER TABLE `obligation_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_renewals`
--

DROP TABLE IF EXISTS `obligation_renewals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_renewals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_id` bigint(20) unsigned NOT NULL,
  `previous_expiry_date` date NOT NULL,
  `new_start_date` date NOT NULL,
  `new_expiry_date` date NOT NULL,
  `renewal_date` date NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'BDT',
  `purchase_reference` varchar(255) DEFAULT NULL,
  `invoice_reference` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `renewed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `obligation_renewals_obligation_id_foreign` (`obligation_id`),
  KEY `obligation_renewals_vendor_id_foreign` (`vendor_id`),
  KEY `obligation_renewals_renewed_by_foreign` (`renewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_renewals`
--

LOCK TABLES `obligation_renewals` WRITE;
/*!40000 ALTER TABLE `obligation_renewals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_renewals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_responsibilities`
--

DROP TABLE IF EXISTS `obligation_responsibilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_responsibilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `responsibility_type` varchar(255) NOT NULL,
  `escalation_level` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `obligation_resp_unique` (`obligation_id`,`user_id`,`responsibility_type`),
  KEY `obligation_responsibilities_obligation_id_index` (`obligation_id`),
  KEY `obligation_responsibilities_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_responsibilities`
--

LOCK TABLES `obligation_responsibilities` WRITE;
/*!40000 ALTER TABLE `obligation_responsibilities` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_responsibilities` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligation_types`
--

DROP TABLE IF EXISTS `obligation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligation_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `default_reminder_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_reminder_days`)),
  `default_priority` varchar(255) NOT NULL DEFAULT 'medium',
  `default_recurrence_type` varchar(255) DEFAULT NULL,
  `default_recurrence_interval` int(11) DEFAULT NULL,
  `default_risk_level` varchar(255) NOT NULL DEFAULT 'medium',
  `approval_required` tinyint(1) NOT NULL DEFAULT 0,
  `renewal_required` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `obligation_types_type_name_unique` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligation_types`
--

LOCK TABLES `obligation_types` WRITE;
/*!40000 ALTER TABLE `obligation_types` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligation_types` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `obligations`
--

DROP TABLE IF EXISTS `obligations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `obligations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `obligation_no` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `obligation_type_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `backup_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewer_user_id` bigint(20) unsigned DEFAULT NULL,
  `approver_user_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `renewal_required` tinyint(1) NOT NULL DEFAULT 1,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_type` varchar(255) DEFAULT NULL,
  `recurrence_interval` int(11) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `risk_level` varchar(255) NOT NULL DEFAULT 'medium',
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'BDT',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `obligations_obligation_no_unique` (`obligation_no`),
  KEY `obligations_category_id_foreign` (`category_id`),
  KEY `obligations_backup_user_id_foreign` (`backup_user_id`),
  KEY `obligations_reviewer_user_id_foreign` (`reviewer_user_id`),
  KEY `obligations_approver_user_id_foreign` (`approver_user_id`),
  KEY `obligations_created_by_foreign` (`created_by`),
  KEY `obligations_obligation_no_index` (`obligation_no`),
  KEY `obligations_obligation_type_id_index` (`obligation_type_id`),
  KEY `obligations_department_id_index` (`department_id`),
  KEY `obligations_company_id_index` (`company_id`),
  KEY `obligations_location_id_index` (`location_id`),
  KEY `obligations_owner_user_id_index` (`owner_user_id`),
  KEY `obligations_vendor_id_index` (`vendor_id`),
  KEY `obligations_expiry_date_index` (`expiry_date`),
  KEY `obligations_status_index` (`status`),
  KEY `obligations_priority_index` (`priority`),
  KEY `obligations_risk_level_index` (`risk_level`),
  FULLTEXT KEY `obligations_fulltext_idx` (`title`,`description`),
  CONSTRAINT `obligations_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `obligations`
--

LOCK TABLES `obligations` WRITE;
/*!40000 ALTER TABLE `obligations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `obligations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_permission_name_unique` (`permission_name`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `permissions` VALUES
(3,'obligation.view','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(4,'obligation.create','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(5,'obligation.update','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(6,'obligation.delete','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(7,'obligation.assign','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(8,'obligation.renew','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(9,'obligation.approve','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(10,'obligation.manage_documents','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(11,'obligation.manage_rules','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(12,'obligation.manage_settings','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(13,'obligation.view_reports','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(14,'obligation.view_all_departments','2026-08-27 15:35:43','2026-08-27 15:35:43'),
(15,'meeting.view','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(16,'meeting.create','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(17,'meeting.edit','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(18,'meeting.delete','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(19,'meeting.manage_participants','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(20,'meeting.manage_agenda','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(21,'meeting.manage_discussion','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(22,'meeting.manage_decision','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(23,'meeting.create_action','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(24,'meeting.assign_action','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(25,'meeting.view_all_actions','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(26,'meeting.view_own_actions','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(27,'meeting.manage_minutes','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(28,'meeting.submit_minutes','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(29,'meeting.approve_minutes','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(30,'meeting.publish_minutes','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(31,'meeting.manage_templates','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(32,'meeting.manage_types','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(33,'meeting.manage_tags','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(34,'meeting.view_reports','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(35,'meeting.export','2026-08-29 18:57:32','2026-08-29 18:57:32'),
(36,'report.view','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(37,'user.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(38,'role.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(39,'privilege.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(40,'invitation.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(41,'system.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(42,'database.backup','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(43,'media.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(44,'activity.view','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(45,'project.view','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(46,'project.create','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(47,'project.update','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(48,'project.delete','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(49,'task.view','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(50,'task.create','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(51,'task.update','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(52,'task.manage','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(53,'task.delete','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(54,'task.transfer','2026-09-29 07:11:09','2026-09-29 07:11:09'),
(55,'system.health','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(56,'system.settings','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(57,'system.queue','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(58,'system.schedule','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(59,'system.flags','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(60,'system.tokens','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(61,'task.view_all','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(62,'task.view_notification_logs','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(63,'meeting.view_notification_logs','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(64,'obligation.view_notification_logs','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(65,'todos.view','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(66,'todos.view_all','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(67,'todos.create','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(68,'todos.create_for_others','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(69,'todos.update_own','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(70,'todos.update_any','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(71,'todos.complete','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(72,'todos.delete','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(73,'todos.restore','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(74,'todos.assign','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(75,'todos.comment','2026-10-02 05:23:36','2026-10-02 05:23:36'),
(76,'todos.manage_recurrence','2026-10-02 05:23:36','2026-10-02 05:23:36');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `privilege_role`
--

DROP TABLE IF EXISTS `privilege_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `privilege_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `privilege_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `privilege_role_role_id_privilege_id_unique` (`role_id`,`privilege_id`),
  KEY `privilege_role_privilege_id_foreign` (`privilege_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_role`
--

LOCK TABLES `privilege_role` WRITE;
/*!40000 ALTER TABLE `privilege_role` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `privilege_role` VALUES
(1,1,1,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(2,6,1,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(3,1,2,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(4,6,2,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(5,6,3,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(6,1,4,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(8,5,5,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(9,1,6,'2026-07-16 13:09:00','2026-07-16 13:09:00'),
(10,6,6,'2026-07-16 13:09:00','2026-07-16 13:09:00'),
(11,1,7,'2026-07-16 13:09:40','2026-07-16 13:09:40'),
(12,6,7,'2026-07-16 13:09:40','2026-07-16 13:09:40'),
(13,1,8,'2026-07-16 13:09:59','2026-07-16 13:09:59'),
(14,6,8,'2026-07-16 13:09:59','2026-07-16 13:09:59'),
(15,1,9,'2026-07-16 13:10:17','2026-07-16 13:10:17'),
(16,6,9,'2026-07-16 13:10:17','2026-07-16 13:10:17'),
(17,1,10,'2026-07-16 13:10:33','2026-07-16 13:10:33'),
(18,6,10,'2026-07-16 13:10:33','2026-07-16 13:10:33'),
(19,1,11,'2026-07-16 13:11:04','2026-07-16 13:11:04'),
(20,6,11,'2026-07-16 13:11:04','2026-07-16 13:11:04'),
(21,1,12,'2026-07-16 13:11:23','2026-07-16 13:11:23'),
(22,6,12,'2026-07-16 13:11:23','2026-07-16 13:11:23'),
(23,1,13,'2026-07-16 13:11:37','2026-07-16 13:11:37'),
(24,6,13,'2026-07-16 13:11:37','2026-07-16 13:11:37'),
(25,1,14,'2026-07-16 13:13:21','2026-07-16 13:13:21'),
(26,6,14,'2026-07-16 13:13:21','2026-07-16 13:13:21'),
(27,1,15,'2026-07-16 13:13:44','2026-07-16 13:13:44'),
(28,6,15,'2026-07-16 13:13:44','2026-07-16 13:13:44'),
(29,1,16,'2026-07-16 13:14:07','2026-07-16 13:14:07'),
(30,6,16,'2026-07-16 13:14:07','2026-07-16 13:14:07'),
(31,1,17,'2026-07-16 13:14:37','2026-07-16 13:14:37'),
(32,6,17,'2026-07-16 13:14:37','2026-07-16 13:14:37'),
(33,2,17,'2026-07-16 13:14:37','2026-07-16 13:14:37'),
(34,1,18,'2026-07-16 13:15:11','2026-07-16 13:15:11'),
(35,6,18,'2026-07-16 13:15:11','2026-07-16 13:15:11'),
(36,2,18,'2026-07-16 13:15:11','2026-07-16 13:15:11'),
(37,1,19,'2026-07-16 13:15:40','2026-07-16 13:15:40'),
(38,6,19,'2026-07-16 13:15:40','2026-07-16 13:15:40'),
(39,2,19,'2026-07-16 13:15:40','2026-07-16 13:15:40'),
(40,1,20,'2026-07-16 13:20:17','2026-07-16 13:20:17'),
(41,6,20,'2026-07-16 13:20:17','2026-07-16 13:20:17'),
(42,1,21,'2026-07-16 13:20:47','2026-07-16 13:20:47'),
(43,6,21,'2026-07-16 13:20:47','2026-07-16 13:20:47'),
(44,1,22,'2026-07-16 13:21:10','2026-07-16 13:21:10'),
(45,6,22,'2026-07-16 13:21:10','2026-07-16 13:21:10'),
(46,1,23,'2026-07-16 13:21:28','2026-07-16 13:21:28'),
(47,6,23,'2026-07-16 13:21:28','2026-07-16 13:21:28'),
(48,1,24,'2026-07-16 13:22:04','2026-07-16 13:22:04'),
(49,6,24,'2026-07-16 13:22:04','2026-07-16 13:22:04'),
(50,1,25,'2026-07-16 13:22:21','2026-07-16 13:22:21'),
(51,6,25,'2026-07-16 13:22:21','2026-07-16 13:22:21'),
(52,1,26,'2026-07-16 13:23:12','2026-07-16 13:23:12'),
(53,6,26,'2026-07-16 13:23:12','2026-07-16 13:23:12'),
(54,1,27,'2026-07-16 13:23:27','2026-07-16 13:23:27'),
(55,6,27,'2026-07-16 13:23:27','2026-07-16 13:23:27'),
(58,1,29,'2026-07-16 13:23:56','2026-07-16 13:23:56'),
(59,6,29,'2026-07-16 13:23:57','2026-07-16 13:23:57'),
(60,1,30,'2026-07-16 13:24:14','2026-07-16 13:24:14'),
(61,6,30,'2026-07-16 13:24:14','2026-07-16 13:24:14'),
(62,1,31,'2026-07-16 13:24:30','2026-07-16 13:24:30'),
(63,6,31,'2026-07-16 13:24:30','2026-07-16 13:24:30'),
(64,1,32,'2026-07-16 13:24:42','2026-07-16 13:24:42'),
(65,6,32,'2026-07-16 13:24:42','2026-07-16 13:24:42'),
(66,1,33,'2026-07-16 13:24:57','2026-07-16 13:24:57'),
(67,6,33,'2026-07-16 13:24:58','2026-07-16 13:24:58'),
(68,1,34,'2026-07-16 13:25:10','2026-07-16 13:25:10'),
(69,6,34,'2026-07-16 13:25:10','2026-07-16 13:25:10'),
(70,1,35,'2026-07-16 13:25:24','2026-07-16 13:25:24'),
(71,6,35,'2026-07-16 13:25:24','2026-07-16 13:25:24'),
(72,1,36,'2026-07-16 13:25:37','2026-07-16 13:25:37'),
(73,6,36,'2026-07-16 13:25:37','2026-07-16 13:25:37'),
(74,6,37,'2026-07-16 13:26:04','2026-07-16 13:26:04'),
(75,6,38,'2026-07-16 13:26:48','2026-07-16 13:26:48'),
(77,2,24,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(78,2,32,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(79,2,31,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(80,2,33,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(81,2,13,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(82,2,25,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(83,2,12,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(84,2,15,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(85,2,16,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(86,2,14,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(87,2,23,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(88,2,21,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(89,2,30,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(90,2,29,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(91,2,34,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(92,2,22,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(93,2,20,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(95,2,27,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(96,2,26,'2026-07-23 10:01:44','2026-07-23 10:01:44'),
(97,1,37,'2026-07-23 10:02:28','2026-07-23 10:02:28'),
(98,6,5,'2026-07-23 10:02:47','2026-07-23 10:02:47'),
(99,6,4,'2026-07-23 10:02:47','2026-07-23 10:02:47'),
(100,3,18,'2026-07-23 10:03:10','2026-07-23 10:03:10');
/*!40000 ALTER TABLE `privilege_role` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `privileges`
--

DROP TABLE IF EXISTS `privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `privileges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `privileges_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privileges`
--

LOCK TABLES `privileges` WRITE;
/*!40000 ALTER TABLE `privileges` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `privileges` VALUES
(1,'Generate Reports','report.generate','Allows generating system-wide reports.','2026-06-17 09:41:00','2026-06-17 09:41:00'),
(2,'Manage Users','users.manage','Allows creating, editing, and deleting users.','2026-06-17 09:41:00','2026-06-17 09:41:00'),
(3,'Manage Roles','roles.manage','Allows editing Tyro roles.','2026-06-17 09:41:00','2026-06-17 09:41:00'),
(4,'View Billing','billing.view','Allows viewing billing statements.','2026-06-17 09:41:00','2026-06-17 09:41:00'),
(5,'Wildcard','*','Grants every privilege.','2026-06-17 09:41:00','2026-06-17 09:41:00'),
(6,'Departments','departments',NULL,'2026-07-16 13:09:00','2026-07-16 13:09:00'),
(7,'Locations','locations',NULL,'2026-07-16 13:09:40','2026-07-16 13:09:40'),
(8,'Employees','employees',NULL,'2026-07-16 13:09:59','2026-07-16 13:09:59'),
(9,'Asset Categories','asset-categories',NULL,'2026-07-16 13:10:17','2026-07-16 13:10:17'),
(10,'Asset Sub Categories','asset-sub-categories',NULL,'2026-07-16 13:10:33','2026-07-16 13:10:33'),
(11,'Vendors','vendors',NULL,'2026-07-16 13:11:04','2026-07-16 13:11:04'),
(12,'Assets','assets',NULL,'2026-07-16 13:11:23','2026-07-16 13:11:23'),
(13,'Asset Documents','asset-documents',NULL,'2026-07-16 13:11:37','2026-07-16 13:11:37'),
(14,'Estates','estates',NULL,'2026-07-16 13:13:21','2026-07-16 13:13:21'),
(15,'Estate Divisions','estate-divisions',NULL,'2026-07-16 13:13:44','2026-07-16 13:13:44'),
(16,'Estate Residence Types','estate-residence-types',NULL,'2026-07-16 13:14:07','2026-07-16 13:14:07'),
(17,'Estate Staffs','estate-staffs',NULL,'2026-07-16 13:14:37','2026-07-16 13:14:37'),
(18,'Tasks','tasks',NULL,'2026-07-16 13:15:11','2026-07-16 13:15:11'),
(19,'Gate Passes','gate-passes',NULL,'2026-07-16 13:15:40','2026-07-16 13:15:40'),
(20,'Purchase Orders','purchase-orders',NULL,'2026-07-16 13:20:17','2026-07-16 13:20:17'),
(21,'Goods Receipts','goods-receipts',NULL,'2026-07-16 13:20:47','2026-07-16 13:20:47'),
(22,'Purchase Order Details','purchase-order-details',NULL,'2026-07-16 13:21:10','2026-07-16 13:21:10'),
(23,'Goods Receipt Details','goods-receipt-details',NULL,'2026-07-16 13:21:28','2026-07-16 13:21:28'),
(24,'Asset Assignments','asset-assignments',NULL,'2026-07-16 13:22:04','2026-07-16 13:22:04'),
(25,'Asset Transfers','asset-transfers',NULL,'2026-07-16 13:22:21','2026-07-16 13:22:21'),
(26,'Software Products','software-products',NULL,'2026-07-16 13:23:12','2026-07-16 13:23:12'),
(27,'Software Licenses','software-licenses',NULL,'2026-07-16 13:23:27','2026-07-16 13:23:27'),
(29,'Maintenance Requests','maintenance-requests',NULL,'2026-07-16 13:23:56','2026-07-16 13:23:56'),
(30,'Maintenance Histories','maintenance-histories',NULL,'2026-07-16 13:24:14','2026-07-16 13:24:14'),
(31,'Asset Audits','asset-audits',NULL,'2026-07-16 13:24:30','2026-07-16 13:24:30'),
(32,'Asset Audit Details','asset-audit-details',NULL,'2026-07-16 13:24:42','2026-07-16 13:24:42'),
(33,'Asset Disposals','asset-disposals',NULL,'2026-07-16 13:24:57','2026-07-16 13:24:57'),
(34,'Permissions','permissions',NULL,'2026-07-16 13:25:10','2026-07-16 13:25:10'),
(35,'Role Permissions','role-permissions',NULL,'2026-07-16 13:25:24','2026-07-16 13:25:24'),
(36,'Activity Logs','activity-logs',NULL,'2026-07-16 13:25:37','2026-07-16 13:25:37'),
(37,'Media Library','dashboard.media',NULL,'2026-07-16 13:26:04','2026-07-16 13:36:24'),
(38,'System Settings','system-settings',NULL,'2026-07-16 13:26:48','2026-07-16 13:26:48');
/*!40000 ALTER TABLE `privileges` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(255) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `remind_at` timestamp NOT NULL,
  `channel` varchar(20) NOT NULL DEFAULT 'in_app',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reminder_unique` (`subject_type`,`subject_id`,`remind_at`),
  KEY `reminders_created_by_foreign` (`created_by`),
  KEY `reminder_dispatch_idx` (`status`,`remind_at`),
  CONSTRAINT `reminders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `role_permissions` VALUES
(1,36,244,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,55,245,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,56,246,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,57,247,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,58,248,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,59,249,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,60,250,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,37,251,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,38,252,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,39,253,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,40,254,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,41,255,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,42,256,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,43,257,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,44,258,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,45,259,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,46,260,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,47,261,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,48,262,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,49,263,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,61,264,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,50,265,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,51,266,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,52,267,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,53,268,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,54,269,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,62,270,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,15,271,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,16,272,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,17,273,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,18,274,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,31,275,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,19,276,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,20,277,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,21,278,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,22,279,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,23,280,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,24,281,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,25,282,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,26,283,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,27,284,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,28,285,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,29,286,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,30,287,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,32,288,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,33,289,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,34,290,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,63,291,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,35,292,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,3,293,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,4,294,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,5,295,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,6,296,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,7,297,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,8,298,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,9,299,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,10,300,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,11,301,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,12,302,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,13,303,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,14,304,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,64,305,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,65,306,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,66,307,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,67,308,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,68,309,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,69,310,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,70,311,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,71,312,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,72,313,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,73,314,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,74,315,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,75,316,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(1,76,317,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(6,44,392,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,42,393,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,40,394,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,43,395,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,29,396,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,24,397,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,16,398,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,23,399,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,18,400,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,17,401,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,35,402,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,20,403,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,22,404,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,21,405,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,27,406,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,19,407,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,33,408,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,31,409,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,32,410,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,30,411,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,28,412,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,15,413,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,25,414,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,63,415,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,26,416,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,34,417,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,9,418,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,7,419,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,4,420,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,6,421,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,10,422,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,11,423,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,12,424,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,8,425,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,5,426,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,3,427,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,14,428,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,64,429,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,13,430,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,39,431,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,46,432,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,48,433,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,47,434,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,45,435,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,36,436,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,38,437,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,59,438,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,55,439,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,41,440,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,57,441,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,58,442,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,56,443,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,60,444,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,50,445,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,53,446,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,52,447,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,54,448,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,51,449,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,49,450,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,61,451,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,62,452,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,74,453,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,75,454,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,71,455,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,67,456,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,68,457,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,72,458,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,76,459,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,73,460,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,70,461,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,69,462,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,65,463,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,66,464,'2026-10-02 05:31:40','2026-10-02 05:31:40'),
(6,37,465,'2026-10-02 05:31:40','2026-10-02 05:31:40');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roles_slug_index` (`slug`),
  KEY `roles_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'Administrator','admin',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(2,'User','user',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(3,'Customer','customer',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(4,'Editor','editor',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(5,'All','*',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(6,'Super Admin','super-admin',NULL,'2026-06-17 09:41:00','2026-06-17 09:41:00'),
(8,'Manager','manager',NULL,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(9,'Employee','employee',NULL,'2026-10-02 05:23:36','2026-10-02 05:23:36'),
(10,'Viewer','viewer',NULL,'2026-10-02 05:23:36','2026-10-02 05:23:36');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `sessions` VALUES
('VyfVdvr33oGWJJV7OHAzlof7Q7yJ8PysBjI4bCjV',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0','eyJfdG9rZW4iOiI1WW5BUjRPTzBXem1TMmJ4bDd0ejBSWmwxaERudWxKb3AyclVtdUdEIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL3dvcmtzcGhlcmUudGVzdFwvYWRtaW5cL3N5c3RlbVwvc2V0dGluZ3MiLCJyb3V0ZSI6ImFkbWluLnN5c3RlbS5zZXR0aW5ncy5pbmRleCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjliN2FhN2NlOWE5NjM0NzE5ZmJkYzIxYzkwMWUyOGY1NDNhNWEyMDNlY2Y4M2QxYTk3Njk3YzQxZjc4NzdjNzMifQ==',1790921868);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value`)),
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  KEY `settings_group_index` (`group`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `settings` VALUES
(1,'app.locale','\"en\"','string','Localisation','Default language','BCP 47 language tag used when a user has not chosen one. Example: en, bn, ar.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(2,'app.timezone','\"GMT+6\"','string','Localisation','Timezone','IANA timezone the business operates in. Scheduled commands run in this zone.',0,1,'2026-10-02 05:26:54','2026-10-02 06:17:48'),
(3,'app.date_format','\"Y-m-d\"','string','Localisation','Date format','PHP date format used for display. Storage stays ISO 8601 regardless of this setting.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(4,'app.week_starts_on','1','integer','Localisation','Week starts on','ISO day of week: 1 = Monday, 7 = Sunday.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(5,'security.session_lifetime','120','integer','Security','Session lifetime (minutes)','A session expires after this much inactivity.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(6,'security.password_min_length','12','integer','Security','Minimum password length','NIST SP 800-63B recommends a minimum of 8; 12 is this deployment\'s policy.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(7,'security.password_expires_days','0','integer','Security','Password expiry (days)','0 disables forced expiry. NIST SP 800-63B advises against routine rotation.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(8,'security.max_failed_attempts','5','integer','Security','Failed logins before lockout','Applies within the throttle window.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(9,'notifications.reminder_default_lead','30','integer','Notifications','Default reminder lead time (minutes)','Used when a reminder is created without an explicit offset.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(10,'notifications.digest_hour','8','integer','Notifications','Daily digest hour','Local hour the daily summary is sent.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(11,'branding.organisation_name','\"WorkSphere\"','string','Branding','Organisation name','Shown in the sidebar and in exported documents.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(12,'branding.support_email',NULL,'string','Branding','Support email','Displayed on error and empty states that ask for help.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54'),
(13,'compliance.retention_days','2555','integer','Compliance','Audit retention (days)','ISO 8601 seven years. Audit rows are not deleted before this age.',0,1,'2026-10-02 05:26:54','2026-10-02 05:26:54');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `social_accounts`
--

DROP TABLE IF EXISTS `social_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(255) NOT NULL,
  `provider_user_id` varchar(255) NOT NULL,
  `provider_email` varchar(255) DEFAULT NULL,
  `provider_avatar` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_accounts_provider_provider_user_id_unique` (`provider`,`provider_user_id`),
  KEY `social_accounts_provider_provider_user_id_index` (`provider`,`provider_user_id`),
  KEY `social_accounts_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_accounts`
--

LOCK TABLES `social_accounts` WRITE;
/*!40000 ALTER TABLE `social_accounts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `social_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `taggables`
--

DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint(20) unsigned NOT NULL,
  `taggable_type` varchar(255) NOT NULL,
  `taggable_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taggable_unique` (`tag_id`,`taggable_type`,`taggable_id`),
  KEY `taggables_created_by_foreign` (`created_by`),
  KEY `taggable_subject_idx` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taggables`
--

LOCK TABLES `taggables` WRITE;
/*!40000 ALTER TABLE `taggables` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `taggables` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_unique` (`name`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `task_notification_logs`
--

DROP TABLE IF EXISTS `task_notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `notification_type` varchar(255) NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PENDING',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_notification_logs_task_id_index` (`task_id`),
  KEY `task_notification_logs_user_id_index` (`user_id`),
  KEY `task_notification_logs_status_index` (`status`),
  KEY `task_notification_logs_channel_index` (`channel`),
  KEY `task_notification_logs_notification_type_index` (`notification_type`),
  KEY `task_notification_logs_scheduled_at_index` (`scheduled_at`),
  CONSTRAINT `task_notification_logs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_notification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_notification_logs`
--

LOCK TABLES `task_notification_logs` WRITE;
/*!40000 ALTER TABLE `task_notification_logs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `task_notification_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `task_projects`
--

DROP TABLE IF EXISTS `task_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_projects_user_id_created_at_index` (`user_id`,`created_at`),
  FULLTEXT KEY `task_projects_fulltext_idx` (`name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_projects`
--

LOCK TABLES `task_projects` WRITE;
/*!40000 ALTER TABLE `task_projects` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `task_projects` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `task_remarks`
--

DROP TABLE IF EXISTS `task_remarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_remarks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `remark` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_remarks_user_id_foreign` (`user_id`),
  KEY `task_remarks_task_id_created_at_index` (`task_id`,`created_at`),
  CONSTRAINT `task_remarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_remarks`
--

LOCK TABLES `task_remarks` WRITE;
/*!40000 ALTER TABLE `task_remarks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `task_remarks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `task_transfers`
--

DROP TABLE IF EXISTS `task_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `from_user_id` bigint(20) unsigned DEFAULT NULL,
  `to_user_id` bigint(20) unsigned DEFAULT NULL,
  `transferred_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text NOT NULL,
  `remarks` text DEFAULT NULL,
  `file_title` varchar(255) DEFAULT NULL,
  `file_attache` varchar(255) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_transfers_from_user_id_foreign` (`from_user_id`),
  KEY `task_transfers_to_user_id_foreign` (`to_user_id`),
  KEY `task_transfers_transferred_by_foreign` (`transferred_by`),
  KEY `task_transfers_task_id_transfer_date_index` (`task_id`,`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_transfers`
--

LOCK TABLES `task_transfers` WRITE;
/*!40000 ALTER TABLE `task_transfers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `task_transfers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `task_watchers`
--

DROP TABLE IF EXISTS `task_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_watchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_watcher_unique` (`task_id`,`user_id`),
  KEY `task_watchers_user_id_foreign` (`user_id`),
  CONSTRAINT `task_watchers_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_watchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_watchers`
--

LOCK TABLES `task_watchers` WRITE;
/*!40000 ALTER TABLE `task_watchers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `task_watchers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','on_hold','postponed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `responsible_user_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `obligation_id` bigint(20) unsigned DEFAULT NULL,
  `task_no` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `estimated_minutes` smallint(5) unsigned DEFAULT NULL,
  `actual_minutes` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_user_id_status_index` (`user_id`,`status`),
  KEY `tasks_user_id_due_date_index` (`user_id`,`due_date`),
  KEY `tasks_responsible_user_id_index` (`responsible_user_id`),
  KEY `tasks_project_id_index` (`project_id`),
  KEY `tasks_obligation_id_index` (`obligation_id`),
  KEY `tasks_status_index` (`status`),
  KEY `tasks_due_date_index` (`due_date`),
  KEY `tasks_priority_index` (`priority`),
  KEY `tasks_task_no_index` (`task_no`),
  KEY `tasks_parent_status_idx` (`parent_id`,`status`),
  FULLTEXT KEY `tasks_fulltext_idx` (`title`,`description`),
  CONSTRAINT `tasks_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `tasks` VALUES
(51,1,'test',NULL,'medium','postponed','2026-10-02',NULL,'2026-10-02 06:04:15','2026-10-02 06:04:32',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `time_entries`
--

DROP TABLE IF EXISTS `time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `minutes` smallint(5) unsigned NOT NULL,
  `logged_on` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_entry_task_date_idx` (`task_id`,`logged_on`),
  KEY `time_entry_user_date_idx` (`user_id`,`logged_on`),
  CONSTRAINT `time_entries_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_entries`
--

LOCK TABLES `time_entries` WRITE;
/*!40000 ALTER TABLE `time_entries` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `time_entries` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `todo_checklist_items`
--

DROP TABLE IF EXISTS `todo_checklist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `todo_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `todo_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `todo_checklist_items_completed_by_foreign` (`completed_by`),
  KEY `todo_checklist_order_idx` (`todo_id`,`sort_order`),
  CONSTRAINT `todo_checklist_items_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `todo_checklist_items_todo_id_foreign` FOREIGN KEY (`todo_id`) REFERENCES `todos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todo_checklist_items`
--

LOCK TABLES `todo_checklist_items` WRITE;
/*!40000 ALTER TABLE `todo_checklist_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `todo_checklist_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `todo_links`
--

DROP TABLE IF EXISTS `todo_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `todo_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `todo_id` bigint(20) unsigned NOT NULL,
  `linkable_type` varchar(255) NOT NULL,
  `linkable_id` bigint(20) unsigned NOT NULL,
  `link_type` varchar(30) NOT NULL DEFAULT 'related',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `todo_link_unique` (`todo_id`,`linkable_type`,`linkable_id`,`link_type`),
  KEY `todo_link_reverse_idx` (`linkable_type`,`linkable_id`),
  CONSTRAINT `todo_links_todo_id_foreign` FOREIGN KEY (`todo_id`) REFERENCES `todos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todo_links`
--

LOCK TABLES `todo_links` WRITE;
/*!40000 ALTER TABLE `todo_links` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `todo_links` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `todo_watchers`
--

DROP TABLE IF EXISTS `todo_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `todo_watchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `todo_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `todo_watcher_unique` (`todo_id`,`user_id`),
  KEY `todo_watchers_user_id_foreign` (`user_id`),
  CONSTRAINT `todo_watchers_todo_id_foreign` FOREIGN KEY (`todo_id`) REFERENCES `todos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `todo_watchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todo_watchers`
--

LOCK TABLES `todo_watchers` WRITE;
/*!40000 ALTER TABLE `todo_watchers` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `todo_watchers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `todos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'inbox',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `visibility` varchar(20) NOT NULL DEFAULT 'personal',
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `creator_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `estimated_minutes` smallint(5) unsigned DEFAULT NULL,
  `actual_minutes` smallint(5) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `archived_from` varchar(20) DEFAULT NULL,
  `waiting_on` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `recurrence_rule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurrence_rule`)),
  `previous_occurrence_at` date DEFAULT NULL,
  `last_reminded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `todos_completed_by_foreign` (`completed_by`),
  KEY `todos_assignee_status_due_idx` (`assignee_id`,`status`,`due_date`),
  KEY `todos_creator_status_idx` (`creator_id`,`status`),
  KEY `todos_status_due_soft_idx` (`status`,`due_date`,`deleted_at`),
  KEY `todos_department_status_idx` (`department_id`,`status`),
  KEY `todos_last_reminded_at_index` (`last_reminded_at`),
  FULLTEXT KEY `todos_fulltext_idx` (`title`,`description`),
  CONSTRAINT `todos_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `todos_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `todos_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `todos_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=904 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todos`
--

LOCK TABLES `todos` WRITE;
/*!40000 ALTER TABLE `todos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `todos` VALUES
(1,'Deserunt pariatur autem dolor et.',NULL,'in_progress','medium','personal',34,35,NULL,NULL,'2026-09-29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-01 04:23:10','2026-10-01 04:45:39','2026-10-01 04:45:39'),
(900,'Voluptatem eos odio omnis unde voluptas.',NULL,'in_progress','medium','personal',36,37,NULL,NULL,'2026-09-29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-01 04:41:30','2026-10-01 04:45:39','2026-10-01 04:45:39'),
(901,'Nemo porro id.','Enim harum ducimus assumenda possimus blanditiis eum eum. Delectus recusandae est soluta voluptatibus labore. Velit aperiam porro dolores molestiae sit qui.','in_progress','medium','personal',1,38,NULL,NULL,'2026-09-29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'2026-10-01 04:45:43','2026-10-01 04:45:39','2026-10-02 06:05:40',NULL),
(902,'Hello1',NULL,'inbox','medium','personal',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-01 13:12:37','2026-10-01 13:12:37',NULL),
(903,'another',NULL,'inbox','medium','personal',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-01 13:13:20','2026-10-01 13:13:20',NULL);
/*!40000 ALTER TABLE `todos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tyro_audit_logs`
--

DROP TABLE IF EXISTS `tyro_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tyro_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tyro_audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `tyro_audit_logs_user_id_index` (`user_id`),
  KEY `tyro_audit_logs_event_index` (`event`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tyro_audit_logs`
--

LOCK TABLES `tyro_audit_logs` WRITE;
/*!40000 ALTER TABLE `tyro_audit_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `tyro_audit_logs` VALUES
(1,NULL,'created','App\\Models\\User',34,NULL,'{\"name\":\"Zion Kiehn\",\"email\":\"vrau@example.net\",\"email_verified_at\":\"2026-10-01T04:23:10.000000Z\",\"updated_at\":\"2026-10-01T04:23:10.000000Z\",\"created_at\":\"2026-10-01T04:23:10.000000Z\",\"id\":34}','[]','2026-10-01 04:23:10','127.0.0.1','Symfony'),
(2,NULL,'created','App\\Models\\User',35,NULL,'{\"name\":\"Darian Little\",\"email\":\"sgleason@example.org\",\"email_verified_at\":\"2026-10-01T04:23:10.000000Z\",\"updated_at\":\"2026-10-01T04:23:10.000000Z\",\"created_at\":\"2026-10-01T04:23:10.000000Z\",\"id\":35}','[]','2026-10-01 04:23:10','127.0.0.1','Symfony'),
(3,NULL,'created','App\\Models\\User',36,NULL,'{\"name\":\"Prof. Elwin Spencer\",\"email\":\"derrick.schiller@example.net\",\"email_verified_at\":\"2026-10-01T04:41:29.000000Z\",\"updated_at\":\"2026-10-01T04:41:30.000000Z\",\"created_at\":\"2026-10-01T04:41:30.000000Z\",\"id\":36}','[]','2026-10-01 04:41:30','127.0.0.1','Symfony'),
(4,NULL,'created','App\\Models\\User',37,NULL,'{\"name\":\"Casandra Dibbert III\",\"email\":\"jacobs.hilario@example.org\",\"email_verified_at\":\"2026-10-01T04:41:30.000000Z\",\"updated_at\":\"2026-10-01T04:41:30.000000Z\",\"created_at\":\"2026-10-01T04:41:30.000000Z\",\"id\":37}','[]','2026-10-01 04:41:30','127.0.0.1','Symfony'),
(5,NULL,'created','App\\Models\\User',38,NULL,'{\"name\":\"Lura Mosciski\",\"email\":\"nia42@example.net\",\"email_verified_at\":\"2026-10-01T04:45:39.000000Z\",\"updated_at\":\"2026-10-01T04:45:39.000000Z\",\"created_at\":\"2026-10-01T04:45:39.000000Z\",\"id\":38}','[]','2026-10-01 04:45:39','127.0.0.1','Symfony'),
(6,1,'updated','App\\Models\\User',1,'[]','[]','[]','2026-10-01 13:11:21','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(7,NULL,'created','App\\Models\\User',39,NULL,'{\"name\":\"Theodore Lindgren\",\"email\":\"altenwerth.brenden@example.net\",\"email_verified_at\":\"2026-10-01T13:18:55.000000Z\",\"employee_id\":7,\"updated_at\":\"2026-10-01T13:18:55.000000Z\",\"created_at\":\"2026-10-01T13:18:55.000000Z\",\"id\":39}','[]','2026-10-01 13:18:55','127.0.0.1','Symfony'),
(8,NULL,'created','App\\Models\\Role',8,NULL,'{\"slug\":\"manager\",\"name\":\"Manager\",\"updated_at\":\"2026-10-02T05:23:36.000000Z\",\"created_at\":\"2026-10-02T05:23:36.000000Z\",\"id\":8}','[]','2026-10-02 05:23:36','127.0.0.1','Symfony'),
(9,NULL,'created','App\\Models\\Role',9,NULL,'{\"slug\":\"employee\",\"name\":\"Employee\",\"updated_at\":\"2026-10-02T05:23:36.000000Z\",\"created_at\":\"2026-10-02T05:23:36.000000Z\",\"id\":9}','[]','2026-10-02 05:23:36','127.0.0.1','Symfony'),
(10,NULL,'created','App\\Models\\Role',10,NULL,'{\"slug\":\"viewer\",\"name\":\"Viewer\",\"updated_at\":\"2026-10-02T05:23:36.000000Z\",\"created_at\":\"2026-10-02T05:23:36.000000Z\",\"id\":10}','[]','2026-10-02 05:23:36','127.0.0.1','Symfony'),
(11,1,'created','Modules\\Tasks\\Models\\Task',51,NULL,'{\"title\":\"test\",\"description\":null,\"priority\":\"medium\",\"status\":\"pending\",\"due_date\":\"2026-10-01T18:00:00.000000Z\",\"responsible_user_id\":\"1\",\"project_id\":null,\"user_id\":1,\"updated_at\":\"2026-10-02T06:04:15.000000Z\",\"created_at\":\"2026-10-02T06:04:15.000000Z\",\"id\":51}','[]','2026-10-02 06:04:15','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0'),
(12,1,'updated','Modules\\Tasks\\Models\\Task',51,'{\"status\":\"pending\",\"updated_at\":\"2026-10-02 12:04:15\"}','{\"status\":\"postponed\",\"updated_at\":\"2026-10-02 12:04:32\"}','[]','2026-10-02 06:04:32','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:156.0) Gecko/20100101 Firefox/156.0');
/*!40000 ALTER TABLE `tyro_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tyro_media`
--

DROP TABLE IF EXISTS `tyro_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tyro_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `webp_path` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'public',
  `mime_type` varchar(100) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `source_url` varchar(2048) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tyro_media_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tyro_media`
--

LOCK TABLES `tyro_media` WRITE;
/*!40000 ALTER TABLE `tyro_media` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `tyro_media` VALUES
(9,1,'WorkSphere.png','media/f87b67577659782245f82cd4a5cc9ce0.png','media/f87b67577659782245f82cd4a5cc9ce0.webp','media/f87b67577659782245f82cd4a5cc9ce0_thumb.webp','public','image/png',1353817,NULL,NULL,'2026-08-29 19:09:20','2026-08-29 19:09:20');
/*!40000 ALTER TABLE `tyro_media` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tyro_starred_import_images`
--

DROP TABLE IF EXISTS `tyro_starred_import_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tyro_starred_import_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `star_key` varchar(64) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `alt` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `thumb_url` varchar(2048) DEFAULT NULL,
  `preview_url` varchar(2048) DEFAULT NULL,
  `download_url` varchar(2048) DEFAULT NULL,
  `download_location` varchar(2048) DEFAULT NULL,
  `source_url` varchar(2048) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `starred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tyro_starred_import_images_user_id_star_key_unique` (`user_id`,`star_key`),
  KEY `tyro_starred_import_images_user_id_starred_at_index` (`user_id`,`starred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tyro_starred_import_images`
--

LOCK TABLES `tyro_starred_import_images` WRITE;
/*!40000 ALTER TABLE `tyro_starred_import_images` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tyro_starred_import_images` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_roles` VALUES
(1,1,6,'2026-07-22 16:15:51','2026-07-22 16:15:51'),
(2,2,6,'2026-07-22 16:15:51','2026-07-22 16:15:51'),
(3,4,2,'2026-07-12 11:02:49','2026-07-12 11:02:49'),
(4,5,1,'2026-07-12 11:06:02','2026-07-12 11:06:02'),
(26,2,5,'2026-07-22 16:15:24','2026-07-22 16:15:24'),
(54,4,5,'2026-08-29 21:01:10','2026-08-29 21:01:10'),
(55,4,6,'2026-08-29 21:01:10','2026-08-29 21:01:10');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` text DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `use_gravatar` tinyint(1) NOT NULL DEFAULT 0,
  `employee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_employee_id_foreign` (`employee_id`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'Saidur Rahman','saidurwd@gmail.com','user-avatars/JpXcukFEkcBOsoVpwlocYdMRTvz5Md4mgBELgk9F.png',NULL,'$2y$12$wb.841ICqcYzVqjoAvR5hOG3mY3LaJ6XFPU8mlJZO0Gh9u9aBSPkW','eyJpdiI6Ijl5TmFOQjF3UzVuU1Y2RlZ6NDcyOHc9PSIsInZhbHVlIjoidi9CSmdwWC94RFBlL3JZWStBRmkyMW9kVGh3RFNUclRvRnZOWCthMklWbUREODkzNTFCUTVaVnhrZ3V6V1F2cSIsIm1hYyI6Ijg4MTA5YWExMTE5ZDA5Yzk3MTU2ZmJmMThiYWIxOWJhMDM3NDgyZWM0ZDE0MjE5Nzg4NDBiMTcwNjE0ODlkNmQiLCJ0YWciOiIifQ==',NULL,NULL,'GAOx64XGwTV5XVP8LQ8LCJzNe5rv1CZwtcjnG5t02QjhEdBLJiq9dgABwvyg','2026-06-17 09:42:00','2026-10-01 11:50:51',NULL,NULL,NULL,0,1,'active'),
(34,'Zion Kiehn','vrau@example.net',NULL,'2026-10-01 04:23:10','$2y$12$ZRGff41yvz7Ydzj6B5U9SOnzu.FrytknXaFWyq.4EDjkGhA2gGkje',NULL,NULL,NULL,'ljbOsWSZLa','2026-10-01 04:23:10','2026-10-01 11:50:51',NULL,NULL,NULL,0,2,'active'),
(35,'Darian Little','sgleason@example.org',NULL,'2026-10-01 04:23:10','$2y$12$ZRGff41yvz7Ydzj6B5U9SOnzu.FrytknXaFWyq.4EDjkGhA2gGkje',NULL,NULL,NULL,'ylCcQtYXAO','2026-10-01 04:23:10','2026-10-01 11:50:51',NULL,NULL,NULL,0,3,'active'),
(36,'Prof. Elwin Spencer','derrick.schiller@example.net',NULL,'2026-10-01 04:41:29','$2y$12$1LVcf76dcayeHEZnCcfcle6aiH.KW6hHcHfWqXsnsy/l/.fMT8EIy',NULL,NULL,NULL,'Y6jQtlG3Ga','2026-10-01 04:41:30','2026-10-01 11:50:51',NULL,NULL,NULL,0,4,'active'),
(37,'Casandra Dibbert III','jacobs.hilario@example.org',NULL,'2026-10-01 04:41:30','$2y$12$1LVcf76dcayeHEZnCcfcle6aiH.KW6hHcHfWqXsnsy/l/.fMT8EIy',NULL,NULL,NULL,'rtGZohCLg4','2026-10-01 04:41:30','2026-10-01 11:50:51',NULL,NULL,NULL,0,5,'active'),
(38,'Lura Mosciski','nia42@example.net',NULL,'2026-10-01 04:45:39','$2y$12$u28/lqBkAj.42BCJbwBJO.ebuYf5H0S7swsOcdU3YuG.GXZ2U4Wwu',NULL,NULL,NULL,'1ROQckvXx6','2026-10-01 04:45:39','2026-10-01 11:50:51',NULL,NULL,NULL,0,6,'active'),
(39,'Theodore Lindgren','altenwerth.brenden@example.net',NULL,'2026-10-01 13:18:55','$2y$12$sWrDSQycyYZ0n6b61kDKL.zajT9uSrG/waU3HtWChJ2CfaeTGyaNW',NULL,NULL,NULL,'JsnxUwu4TA','2026-10-01 13:18:55','2026-10-01 13:18:55',NULL,NULL,NULL,0,7,'active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendors_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Temporary table structure for view `work_items`
--

DROP TABLE IF EXISTS `work_items`;
/*!50001 DROP VIEW IF EXISTS `work_items`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `work_items` AS SELECT
 1 AS `source_type`,
  1 AS `source_id`,
  1 AS `title`,
  1 AS `status`,
  1 AS `priority`,
  1 AS `assignee_id`,
  1 AS `creator_id`,
  1 AS `due_date`,
  1 AS `completed_at`,
  1 AS `project_id`,
  1 AS `deleted_at` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `work_items`
--

/*!50001 DROP VIEW IF EXISTS `work_items`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `work_items` AS select 'task' AS `source_type`,`tasks`.`id` AS `source_id`,`tasks`.`title` AS `title`,`tasks`.`status` AS `status`,`tasks`.`priority` AS `priority`,`tasks`.`responsible_user_id` AS `assignee_id`,`tasks`.`user_id` AS `creator_id`,`tasks`.`due_date` AS `due_date`,`tasks`.`completed_at` AS `completed_at`,`tasks`.`project_id` AS `project_id`,NULL AS `deleted_at` from `tasks` union all select 'todo' AS `todo`,`todos`.`id` AS `id`,`todos`.`title` AS `title`,`todos`.`status` AS `status`,`todos`.`priority` AS `priority`,`todos`.`assignee_id` AS `assignee_id`,`todos`.`creator_id` AS `creator_id`,`todos`.`due_date` AS `due_date`,`todos`.`completed_at` AS `completed_at`,NULL AS `NULL`,`todos`.`deleted_at` AS `deleted_at` from `todos` union all select 'meeting_action_item' AS `meeting_action_item`,`meeting_action_items`.`id` AS `id`,`meeting_action_items`.`title` AS `title`,`meeting_action_items`.`status` AS `status`,`meeting_action_items`.`priority` AS `priority`,`meeting_action_items`.`assigned_to` AS `assigned_to`,`meeting_action_items`.`created_by` AS `created_by`,`meeting_action_items`.`due_date` AS `due_date`,`meeting_action_items`.`completed_at` AS `completed_at`,NULL AS `NULL`,NULL AS `NULL` from `meeting_action_items` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-02 12:19:34
