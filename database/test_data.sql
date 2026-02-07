-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: core_app
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,'1ab66e16-0238-11f1-b881-b2da8b41d4c9','How to Measure Blood Pressure',NULL,'VIDEO','hypertension','en-IN',NULL,NULL,NULL,NULL,NULL,'URL','https://example.org/videos/bp_measurement.mp4',1,1,NULL,'2026-02-04 20:12:21','2026-02-04 20:12:21'),(2,'1f3e94ae-0238-11f1-b881-b2da8b41d4c9','Nurse Triage Checklist',NULL,'PDF','triage','en',NULL,NULL,NULL,NULL,NULL,'URL','/internal/training/triage_checklist.pdf',0,1,NULL,'2026-02-04 20:12:29','2026-02-04 20:12:29');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `case_closures`
--

LOCK TABLES `case_closures` WRITE;
/*!40000 ALTER TABLE `case_closures` DISABLE KEYS */;
/*!40000 ALTER TABLE `case_closures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `case_sheets`
--

LOCK TABLES `case_sheets` WRITE;
/*!40000 ALTER TABLE `case_sheets` DISABLE KEYS */;
/*!40000 ALTER TABLE `case_sheets` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_case_sheets_after_insert_create_closure
AFTER INSERT ON case_sheets
FOR EACH ROW
BEGIN
  INSERT INTO case_closures (case_sheet_id, closure_type)
  VALUES (NEW.case_sheet_id, 'PENDING');
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Andrew','Hawkinson',NULL,'hawk@d3s3.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'IN','Hawkinson','$2y$10$lO.z3dxebDVH.eVN9UATbO4WmZXs9gOw8X23BGpEKffE1aSaAmXF.','SUPER_ADMIN',1,'2026-02-04 20:47:10','2026-02-04 14:41:37','2026-02-06 18:32:33'),(2,'Admin','Account',NULL,'admin1@d3s3.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'IN','admin1','$2y$10$wH/SJPGCqIxx1wmYUrPzCe7i6JOgeFBk.6a.xrQ4Nl.MuEEUxoASG','ADMIN',0,NULL,'2026-02-04 14:49:41','2026-02-06 18:32:55'),(3,'Gary','Marks',NULL,'g.marks@d3s3.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'IN','gmarks','$2y$10$SW6AIjBtzZU8SkQXsy0EOO84g6Ffe5XlEtKYmm/yGm0QKZUWLxCAa','SUPER_ADMIN',1,NULL,'2026-02-04 18:26:01','2026-02-06 18:34:57');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'67c3e030-0238-11f1-b881-b2da8b41d4c9','MEDICAL_CAMP','Health Camp - Community Center',NULL,'2026-02-10 09:00:00','2026-02-10 16:00:00','Asia/Kolkata','Ward 12 Community Center',NULL,NULL,'Pune','Maharashtra',NULL,'IN',NULL,NULL,'SCHEDULED',0,1,NULL,'2026-02-04 20:14:30','2026-02-04 20:14:30'),(2,'6b5806ae-0238-11f1-b881-b2da8b41d4c9','EDUCATIONAL_SEMINAR','Diabetes Prevention Seminar',NULL,'2026-02-12 18:00:00',NULL,'America/Chicago','Library Meeting Room A',NULL,NULL,'St. Paul','MN',NULL,'US',NULL,NULL,'SCHEDULED',1,1,NULL,'2026-02-04 20:14:36','2026-02-04 20:14:36');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `message_threads`
--

LOCK TABLES `message_threads` WRITE;
/*!40000 ALTER TABLE `message_threads` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_threads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `patient_accounts`
--

LOCK TABLES `patient_accounts` WRITE;
/*!40000 ALTER TABLE `patient_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `patient_daily_sequence`
--

LOCK TABLES `patient_daily_sequence` WRITE;
/*!40000 ALTER TABLE `patient_daily_sequence` DISABLE KEYS */;
INSERT INTO `patient_daily_sequence` VALUES ('2026-02-04',0);
/*!40000 ALTER TABLE `patient_daily_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `patient_feedback`
--

LOCK TABLES `patient_feedback` WRITE;
/*!40000 ALTER TABLE `patient_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'20260204000','2026-02-04','Rahul','Sharma','MALE',NULL,NULL,'+919876543210',NULL,NULL,NULL,NULL,NULL,NULL,'IN',NULL,NULL,NULL,NULL,1,'2026-02-04 20:07:09','2026-02-04 20:07:09');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_patients_before_insert
BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
  DECLARE v_date DATE;
  DECLARE v_n   INT UNSIGNED;

  -- Use provided first_seen_date or default to today
  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  -- Increment the per-day counter safely
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, 0)
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_n = LAST_INSERT_ID();

  -- Build patient_code: YYYYMMDD + 3-digit sequence
  SET NEW.patient_code = CONCAT(DATE_FORMAT(v_date, '%Y%m%d'), LPAD(v_n, 3, '0'));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06 18:43:16
