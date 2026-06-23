-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hostel_management
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `complaint`
--

DROP TABLE IF EXISTS `complaint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaint` (
  `complaint_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`complaint_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint`
--

LOCK TABLES `complaint` WRITE;
/*!40000 ALTER TABLE `complaint` DISABLE KEYS */;
INSERT INTO `complaint` VALUES (1,1,'Maintenance','Water tap is leaking in room 102','pending','2026-06-17 12:19:39'),(2,1,'Electricity','Light bulb fused in corridor','resolved','2026-06-17 12:19:39'),(3,2,'Mess','Food quality needs improvement','pending','2026-06-17 12:19:39');
/*!40000 ALTER TABLE `complaint` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_stats`
--

DROP TABLE IF EXISTS `dashboard_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dashboard_stats` (
  `stat_id` int(11) NOT NULL AUTO_INCREMENT,
  `total_students` int(11) DEFAULT 0,
  `total_rooms` int(11) DEFAULT 0,
  `occupied_rooms` int(11) DEFAULT 0,
  `pending_complaints` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`stat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_stats`
--

LOCK TABLES `dashboard_stats` WRITE;
/*!40000 ALTER TABLE `dashboard_stats` DISABLE KEYS */;
INSERT INTO `dashboard_stats` VALUES (1,2,8,3,2,'2026-06-17 12:19:40');
/*!40000 ALTER TABLE `dashboard_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel`
--

DROP TABLE IF EXISTS `hostel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel` (
  `hostel_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `total_rooms` int(11) DEFAULT NULL,
  PRIMARY KEY (`hostel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel`
--

LOCK TABLES `hostel` WRITE;
/*!40000 ALTER TABLE `hostel` DISABLE KEYS */;
INSERT INTO `hostel` VALUES (1,'Ganga Hostel','Block A, Campus Road',50),(2,'Yamuna Hostel','Block B, Campus Road',40);
/*!40000 ALTER TABLE `hostel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mess`
--

DROP TABLE IF EXISTS `mess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mess` (
  `mess_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`mess_id`),
  KEY `hostel_id` (`hostel_id`),
  CONSTRAINT `mess_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`hostel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mess`
--

LOCK TABLES `mess` WRITE;
/*!40000 ALTER TABLE `mess` DISABLE KEYS */;
INSERT INTO `mess` VALUES (1,'Ganga Mess',1),(2,'Yamuna Mess',2);
/*!40000 ALTER TABLE `mess` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mess_menu`
--

DROP TABLE IF EXISTS `mess_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mess_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `mess_id` int(11) DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `breakfast` text DEFAULT NULL,
  `lunch` text DEFAULT NULL,
  `dinner` text DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  KEY `mess_id` (`mess_id`),
  CONSTRAINT `mess_menu_ibfk_1` FOREIGN KEY (`mess_id`) REFERENCES `mess` (`mess_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mess_menu`
--

LOCK TABLES `mess_menu` WRITE;
/*!40000 ALTER TABLE `mess_menu` DISABLE KEYS */;
INSERT INTO `mess_menu` VALUES (1,1,'Monday','Poha, Tea, Banana','Dal, Rice, Sabzi, Roti, Salad','Paneer Curry, Rice, Roti, Curd'),(2,1,'Tuesday','Idli, Sambar, Chutney','Rajma, Rice, Roti, Salad','Aloo Curry, Dal, Roti'),(3,1,'Wednesday','Paratha, Curd, Pickle','Chole, Rice, Roti, Salad','Mix Veg, Dal, Rice, Roti'),(4,1,'Thursday','Upma, Tea, Fruit','Dal Makhani, Rice, Roti, Salad','Kadai Paneer, Rice, Roti'),(5,1,'Friday','Bread, Butter, Egg','Palak Dal, Rice, Roti, Salad','Dum Aloo, Roti, Rice, Raita'),(6,1,'Saturday','Puri, Sabzi, Tea','Special Biryani, Raita, Salad','Dal Fry, Roti, Rice, Kheer'),(7,1,'Sunday','Dosa, Chutney, Sambar','Special Lunch, Salad, Sweet','Special Dinner, Dessert'),(8,2,'Monday','Paratha, Curd','Dal, Rice, Sabzi, Roti','Paneer, Rice, Roti'),(9,2,'Tuesday','Idli, Chutney','Rajma, Rice, Roti','Aloo Curry, Dal, Roti'),(10,2,'Wednesday','Poha, Tea','Chole, Rice, Roti','Mix Veg, Dal, Roti'),(11,2,'Thursday','Bread, Egg, Juice','Dal Makhani, Rice, Roti','Kadai Paneer, Rice'),(12,2,'Friday','Upma, Tea','Palak Dal, Rice, Roti','Dum Aloo, Roti, Rice'),(13,2,'Saturday','Puri, Sabzi','Biryani, Raita','Dal Fry, Roti, Kheer'),(14,2,'Sunday','Dosa, Sambar','Special Lunch, Sweet','Special Dinner, Dessert');
/*!40000 ALTER TABLE `mess_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `hostel_id` int(11) DEFAULT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  PRIMARY KEY (`room_id`),
  KEY `hostel_id` (`hostel_id`),
  CONSTRAINT `room_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`hostel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room`
--

LOCK TABLES `room` WRITE;
/*!40000 ALTER TABLE `room` DISABLE KEYS */;
INSERT INTO `room` VALUES (1,1,'101','Single',1,'available'),(2,1,'102','Double',2,'occupied'),(3,1,'103','Triple',3,'available'),(4,1,'104','Double',2,'occupied'),(5,1,'105','Single',1,'maintenance'),(6,2,'201','Double',2,'available'),(7,2,'202','Triple',3,'occupied'),(8,2,'203','Single',1,'available');
/*!40000 ALTER TABLE `room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `roll_no` (`roll_no`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `student_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`),
  CONSTRAINT `student_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES (1,2,'Rahul Sharma','2021CS001','Computer Science',3,'9876543210','rahul@student.edu',2),(2,3,'Priya Singh','2021CS002','Computer Science',2,'9876543211','priya@student.edu',4);
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$bS0XIbB6Wc.LDwV2TFRSteVOEkZZqE35ZKq7eusamLzPLNRCsO/zK','admin','2026-06-17 12:19:39'),(2,'2021CS001','$2y$10$a7uZLfSD0uNog.4onYPmuOTjWs8bVfa9pnf61nfHjCyRjS80NTBky','student','2026-06-17 12:19:39'),(3,'2021CS002','$2y$10$a7uZLfSD0uNog.4onYPmuOTjWs8bVfa9pnf61nfHjCyRjS80NTBky','student','2026-06-17 12:19:39');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-23 14:02:04
