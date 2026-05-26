-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hanapdorm
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
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amenities` (
  `amenity_id` int(11) NOT NULL AUTO_INCREMENT,
  `amenity_name` varchar(100) NOT NULL,
  PRIMARY KEY (`amenity_id`),
  UNIQUE KEY `amenity_name` (`amenity_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amenities`
--

LOCK TABLES `amenities` WRITE;
/*!40000 ALTER TABLE `amenities` DISABLE KEYS */;
INSERT INTO `amenities` VALUES (1,'Aircon'),(2,'Parking'),(4,'Shared'),(3,'Solo'),(5,'Wifi');
/*!40000 ALTER TABLE `amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `dorm_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `move_in_date` date DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  KEY `dorm_id` (`dorm_id`),
  KEY `renter_id` (`renter_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`dorm_id`) REFERENCES `dorms` (`dorm_id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`renter_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dorm_amenities`
--

DROP TABLE IF EXISTS `dorm_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dorm_amenities` (
  `dorm_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`dorm_id`,`amenity_id`),
  KEY `amenity_id` (`amenity_id`),
  CONSTRAINT `dorm_amenities_ibfk_1` FOREIGN KEY (`dorm_id`) REFERENCES `dorms` (`dorm_id`),
  CONSTRAINT `dorm_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`amenity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dorm_amenities`
--

LOCK TABLES `dorm_amenities` WRITE;
/*!40000 ALTER TABLE `dorm_amenities` DISABLE KEYS */;
INSERT INTO `dorm_amenities` VALUES (1,1),(1,4),(1,5),(2,2),(2,3),(2,5),(3,1),(3,2),(3,3),(4,1),(4,3);
/*!40000 ALTER TABLE `dorm_amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dorm_images`
--

DROP TABLE IF EXISTS `dorm_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dorm_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `dorm_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  PRIMARY KEY (`image_id`),
  KEY `dorm_id` (`dorm_id`),
  CONSTRAINT `dorm_images_ibfk_1` FOREIGN KEY (`dorm_id`) REFERENCES `dorms` (`dorm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dorm_images`
--

LOCK TABLES `dorm_images` WRITE;
/*!40000 ALTER TABLE `dorm_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `dorm_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dorms`
--

DROP TABLE IF EXISTS `dorms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dorms` (
  `dorm_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `dorm_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `available_rooms` int(11) DEFAULT NULL,
  `total_rooms` int(11) DEFAULT NULL,
  `room_capacity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`dorm_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `dorms_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dorms`
--

LOCK TABLES `dorms` WRITE;
/*!40000 ALTER TABLE `dorms` DISABLE KEYS */;
INSERT INTO `dorms` VALUES (1,1,'Greenview Residences','<p>Please apply for a slot here: <a href=\"#\">https://greenview-indang.com/reserve-now</a></p>\n          <p>Looking for a quiet, study-friendly environment in the heart of Indang? Located just 5 minutes from CvSU Main Campus.</p>\n          <span class=\"section-label\">Requirements:</span>\n          <ul><li>Minimum 1-year contract (2 months deposit, 1 month advance)</li><li>Periodic room inspections</li><li>Curfew: 10:00 PM</li></ul>','Indang, Cavite',14.19621884,120.88607101,6000.00,2,2,2,'2026-05-26 05:39:21','2026-05-26 05:39:21'),(2,1,'Alulod Studio Apartments','<p>Located along Indang-Mendez Rd, ideal for students with motorcycles. Parking available on site.</p>\n          <span class=\"section-label\">Requirements:</span>\n          <ul><li>Minimum 1-year contract</li><li>Motorcycle parking included</li><li>Curfew: 11:00 PM</li></ul>','Alulod, Indang, Cavite',14.20155448,120.89059621,5500.00,3,2,1,'2026-05-26 05:42:25','2026-05-26 05:42:25'),(3,1,'The Yellow Bell House','<p>Quiet female-only dormitory in Brgy. Mahabang Lupa. Study-friendly environment with strict curfew.</p>\n          <span class=\"section-label\">Requirements:</span>\n          <ul><li>Female students only</li><li>Minimum 6-month contract</li><li>Curfew: 9:00 PM</li></ul>','Indang, Cavite',14.20010355,120.88856309,4000.00,1,6,3,'2026-05-26 05:44:31','2026-05-26 05:44:31'),(4,1,'Bancod Skibidi','<p>Walking distance from CvSU. Budget-friendly option along Bancod Road.</p>\n          <span class=\"section-label\">Requirements:</span>\n          <ul><li>Minimum 6-month contract</li><li>1 month deposit, 1 month advance</li><li>Curfew: 10:00 PM</li></ul>','Bancod, Indang, Cavite',14.21131556,120.87711274,3000.00,2,4,1,'2026-05-26 05:47:20','2026-05-26 05:47:20');
/*!40000 ALTER TABLE `dorms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','dorm_owner') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'John','Doe','johndoe@gmail.com','1234','dorm_owner','2026-05-26 05:39:12');
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

-- Dump completed on 2026-05-26 15:03:01
