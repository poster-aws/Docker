-- MySQL dump 10.13  Distrib 8.4.7, for Linux (aarch64)
--
-- Host: db    Database: vie
-- ------------------------------------------------------
-- Server version	8.4.7

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
-- Table structure for table `Vie`
--

DROP TABLE IF EXISTS `Vie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Vie` (
  `Tirage` date NOT NULL,
  `n1` tinyint unsigned NOT NULL,
  `n2` tinyint unsigned NOT NULL,
  `n3` tinyint unsigned NOT NULL,
  `n4` tinyint unsigned NOT NULL,
  `n5` tinyint unsigned NOT NULL,
  `GN` tinyint unsigned NOT NULL,
  PRIMARY KEY (`Tirage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vie`
--

LOCK TABLES `Vie` WRITE;
/*!40000 ALTER TABLE `Vie` DISABLE KEYS */;
INSERT INTO `Vie` VALUES ('2026-01-01',1,5,7,38,47,1),('2026-01-05',11,30,38,40,43,6),('2026-01-08',2,13,19,26,36,7),('2026-01-12',5,17,19,35,45,2);
/*!40000 ALTER TABLE `Vie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'vie'
--

--
-- Dumping routines for database 'vie'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-15 21:35:57
