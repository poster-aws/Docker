-- MySQL dump 10.13  Distrib 8.4.6, for Linux (aarch64)
--
-- Host: db    Database: banco
-- ------------------------------------------------------
-- Server version	8.4.6

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banco`
--

DROP TABLE IF EXISTS `banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banco` (
  `Tirage` date NOT NULL,
  `n1` tinyint unsigned NOT NULL,
  `n2` tinyint unsigned NOT NULL,
  `n3` tinyint unsigned NOT NULL,
  `n4` tinyint unsigned NOT NULL,
  `n5` tinyint unsigned NOT NULL,
  `n6` tinyint unsigned NOT NULL,
  `n7` tinyint unsigned NOT NULL,
  `n8` tinyint unsigned NOT NULL,
  `n9` tinyint unsigned NOT NULL,
  `n10` tinyint unsigned NOT NULL,
  `n11` tinyint unsigned NOT NULL,
  `n12` tinyint unsigned NOT NULL,
  `n13` tinyint unsigned NOT NULL,
  `n14` tinyint unsigned NOT NULL,
  `n15` tinyint unsigned NOT NULL,
  `n16` tinyint unsigned NOT NULL,
  `n17` tinyint unsigned NOT NULL,
  `n18` tinyint unsigned NOT NULL,
  `n19` tinyint unsigned NOT NULL,
  `n20` tinyint unsigned NOT NULL,
  `turbo` tinyint unsigned NOT NULL,
  PRIMARY KEY (`Tirage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco`
--

LOCK TABLES `banco` WRITE;
/*!40000 ALTER TABLE `banco` DISABLE KEYS */;
/*!40000 ALTER TABLE `banco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'banco'
--

--
-- Dumping routines for database 'banco'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-28 15:31:42
