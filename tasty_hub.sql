/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: u147049380_tasty_hub
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-log

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
-- Table structure for table `appeals`
--

DROP TABLE IF EXISTS `appeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appeals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `appeal_message` text NOT NULL,
  `appeal_date` datetime NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `appeal_proof` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `appeals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appeals`
--

/*!40000 ALTER TABLE `appeals` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `appeals` VALUES
(1,71,3,'di yan totoo','2025-10-26 15:25:09','Rejected',NULL),
(2,71,8,'sorry di na mauulit pasensya','2025-11-12 14:40:11','Rejected',NULL),
(3,107,10,'di po yan totoo','2025-11-12 14:52:46','Rejected',NULL),
(4,107,12,'di yan totoo','2025-11-12 14:56:50','Pending',NULL);
/*!40000 ALTER TABLE `appeals` ENABLE KEYS */;
commit;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_comment_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`),
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `comments` VALUES
(2,6,69,'d nmn','2025-10-08 16:05:06',1),
(3,1,69,'s','2025-10-14 08:44:04',NULL),
(4,2,70,'wow','2025-10-15 14:47:55',NULL),
(5,6,69,'wow','2025-10-16 08:30:20',NULL),
(6,11,70,'test','2025-10-18 07:40:58',NULL),
(7,74,69,'wowww','2025-10-26 15:12:59',NULL),
(8,74,69,'pangit','2025-10-26 15:13:05',NULL),
(11,92,70,'how did you come up with this? going to try this for sure.','2025-11-09 09:58:24',NULL),
(12,77,107,'pangit niyan','2025-11-12 14:22:28',NULL),
(15,52,71,'wag  di massarap yan','2025-11-12 14:35:49',NULL),
(16,52,71,'pangit lasa niyan mas masarap ung sakin','2025-11-12 14:36:08',NULL),
(17,73,107,'ano ba yan di masarap HAHAHHAH wag niyo lutuin to','2025-11-12 14:46:34',NULL),
(18,51,107,'wag niyo subukan pagsisisihan niyo di maganda lasa','2025-11-12 14:48:17',NULL),
(19,8,69,'wow','2025-11-13 03:45:25',NULL);
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
commit;

--
-- Table structure for table `comments_reports`
--

DROP TABLE IF EXISTS `comments_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporting_user_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `custom_reason` text DEFAULT NULL,
  `status` enum('Pending','Dismissed','Deleted') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reporting_user_id` (`reporting_user_id`),
  KEY `reported_user_id` (`reported_user_id`),
  KEY `recipe_id` (`recipe_id`),
  KEY `comment_id` (`comment_id`),
  CONSTRAINT `comments_reports_ibfk_1` FOREIGN KEY (`reporting_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `comments_reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `comments_reports_ibfk_3` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`),
  CONSTRAINT `comments_reports_ibfk_4` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments_reports`
--

/*!40000 ALTER TABLE `comments_reports` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `comments_reports` VALUES
(1,72,69,6,2,'Harassment',NULL,'Dismissed','2025-10-09 03:51:02'),
(2,72,69,74,8,'Offensive',NULL,'Dismissed','2025-10-26 15:13:50'),
(3,72,69,74,7,'Offensive',NULL,'Dismissed','2025-10-26 15:14:25'),
(4,69,107,77,12,'Offensive',NULL,'Pending','2025-11-12 14:23:10');
/*!40000 ALTER TABLE `comments_reports` ENABLE KEYS */;
commit;

--
-- Table structure for table `equipments`
--

DROP TABLE IF EXISTS `equipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`),
  CONSTRAINT `equipments_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=551 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipments`
--

/*!40000 ALTER TABLE `equipments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `equipments` VALUES
(19,5,'Frying pan'),
(20,5,'Spatula'),
(21,5,'Plate and knife'),
(22,6,'Coffee maker'),
(23,6,'Glass'),
(24,6,'Spoon'),
(29,7,'Saucepan or pot'),
(30,7,'Blender or immersion blender'),
(31,7,'Ladle'),
(32,7,'Whisk'),
(33,7,'Cutting board & knife'),
(34,8,'Deep frying pan or pot'),
(35,8,'Tongs'),
(36,8,'Mixing bowls'),
(37,8,'Whisk'),
(38,8,'Wire rack or paper towel'),
(39,8,'Thermometer (optional)'),
(40,9,'Blender'),
(41,9,'Mixing glass or shaker'),
(42,9,'Whisk or spoon'),
(43,9,'Measuring cups'),
(44,9,'Strainer (optional)'),
(45,9,'Serving glasses'),
(90,11,'Oven'),
(91,11,'roasting pan'),
(92,11,'basting brush'),
(93,11,'mixing bowl'),
(144,10,'Wok or large frying pan'),
(145,10,'Wooden spatula'),
(146,10,'Knife and chopping board'),
(147,10,'Mixing bowl'),
(148,10,'Measuring cups and spoons'),
(169,4,'Mixing bowl'),
(170,4,'Hand mixer or whisk'),
(171,4,'Measuring cups'),
(172,4,'Refrigerator'),
(183,3,'Mixing bowl'),
(184,3,'Whisk'),
(185,3,'Refrigerator'),
(186,2,'Saucepan'),
(187,2,'Blender (for mango puree)'),
(188,2,'Ladle'),
(189,2,'Bowl & chopsticks'),
(190,1,'Mixing bowls (for marinating and salsa)'),
(191,1,'Non-stick frying pan or skillet'),
(192,1,'Wooden spatula'),
(193,1,'Rice cooker or pot with lid'),
(194,1,'Measuring cups and spoons'),
(195,1,'Knife and chopping board'),
(196,1,'Serving bowls'),
(225,12,'Pot'),
(226,12,'mixing bowl'),
(227,12,'fork'),
(302,28,'1'),
(303,29,'Mixing bowl'),
(304,29,'Whisk or spoon'),
(305,29,'Measuring cups and spoons'),
(306,29,'Serving glasses or bowls'),
(307,29,'Refrigerator'),
(308,30,'whisk'),
(309,30,'small bowl'),
(310,30,'spatula'),
(311,30,'Frying pan,'),
(312,17,'Large Pot or Dutch Oven'),
(313,17,'Cutting Board & Knife'),
(314,17,'Wooden Spoon or Silicone Spatula'),
(315,17,'Measuring Cups & Spoons'),
(316,17,'Garlic Press (optional)'),
(317,17,'Ladle'),
(318,17,'Immersion Blender (or regular blender)'),
(319,17,'Small Bowl/Plate'),
(320,18,'Mixing bowls'),
(321,18,'Measuring cups & spoons'),
(322,18,'Whisk or fork'),
(323,18,'Dough scraper or spatula'),
(324,18,'Rolling Pin'),
(325,18,'Knife or dough cutter'),
(326,18,'Baking pan or loaf pan'),
(327,18,'Oven'),
(328,18,'Paint Brush'),
(329,18,'Cooling rack'),
(330,31,'spoon'),
(331,31,'Jar or container'),
(332,32,'Deep frying pan or pot'),
(333,32,'Medium sauté pan'),
(334,32,'Mixing bowls'),
(335,32,'Tongs or chopsticks'),
(336,32,'Paper towels (for draining oil)'),
(337,32,'Measuring spoons and cups'),
(338,33,'Non-stick frying pan or wok'),
(339,33,'Mixing bowl'),
(340,33,'Spatula'),
(341,33,'Measuring spoons'),
(342,33,'Rice cooker (optional)'),
(343,34,'Pan'),
(344,34,'pot'),
(345,34,'strainer'),
(346,35,'Frying pan'),
(347,35,'bowl'),
(348,35,'tongs'),
(349,36,'Wok'),
(350,36,'spatula'),
(351,37,'Tray'),
(352,37,'mixer'),
(353,37,'fridge'),
(354,38,'Pan'),
(355,38,'spatula'),
(356,39,'Frying pan'),
(357,39,'tongs'),
(358,39,'plate'),
(359,39,'paper towel'),
(360,40,'Pan'),
(361,40,'mixing bowl'),
(362,40,'strainer'),
(366,42,'Sandwich maker or pan'),
(367,42,'bowl'),
(368,42,'spoon'),
(369,43,'Pot'),
(370,43,'ladle'),
(371,43,'knife'),
(372,43,'chopping board'),
(373,44,'Saucepan'),
(374,44,'blender'),
(375,44,'ladle'),
(376,45,'Bowl'),
(377,45,'knife'),
(378,45,'Spoon'),
(382,47,'Pot'),
(383,47,'strainer'),
(384,47,'bowl'),
(385,47,'spoon'),
(386,46,'Bowl'),
(387,46,'grater'),
(388,46,'spoon'),
(389,41,'Mixing bowl'),
(390,41,'spoon'),
(391,41,'frying pan'),
(392,48,'Frying pan or skillet'),
(393,48,'Mixing bowl'),
(394,48,'Tongs or spatula'),
(395,48,'Knife and chopping board'),
(396,48,'Whisk or fork'),
(397,49,'Mixing bowl'),
(398,49,'Frying pan or griddle'),
(399,49,'Spatula'),
(400,49,'Measuring cups'),
(405,50,'Pitcher'),
(406,50,'Strainer'),
(407,50,'Spoon'),
(408,50,'Glasses'),
(412,52,'Saucepan'),
(413,52,'Strainer'),
(414,52,'Pitcher'),
(415,52,'Glasses'),
(416,53,'Saucepan'),
(417,53,'Strainer'),
(418,53,'Spoon'),
(419,53,'Glasses'),
(420,54,'Blender'),
(421,54,'Spoon'),
(422,54,'Glasses'),
(423,55,'Strainer (optional)'),
(424,55,'Pitcher'),
(425,55,'Blender'),
(426,55,'Glasses'),
(427,56,'Frying pan or wok'),
(428,56,'Spatula'),
(429,56,'Bowl'),
(430,57,'Wok or frying pan'),
(431,57,'Mixing bowl'),
(432,57,'Spoon'),
(433,58,'Grill or stove'),
(434,58,'Fork'),
(435,58,'Bowl'),
(436,58,'Frying pan'),
(437,59,'Pan or wok'),
(438,59,'Knife and chopping board'),
(439,59,'Spoon'),
(440,60,'Pot'),
(441,60,'Pan'),
(442,60,'Strainer'),
(443,60,'Mixing spoon'),
(444,61,'Bowl'),
(445,61,'Frying pan'),
(446,61,'Tongs'),
(447,61,'Knife and board'),
(448,62,'Pot'),
(449,62,'Pan'),
(450,62,'Oven or toaster oven'),
(451,62,'Baking dishV'),
(452,63,'Oven or oven toaster'),
(453,63,'Mixing bowl'),
(454,63,'Cupcake molds or banana leaves'),
(455,63,'Whisk'),
(456,64,'Grill pan or non-stick pan'),
(457,64,'Mixing bowl'),
(458,64,'Whisk'),
(465,65,'Wok or frying pan'),
(466,65,'Wooden spoon'),
(467,67,'Toaster or pan'),
(468,67,'Small bowl'),
(469,67,'Fork'),
(470,68,'Baking tray'),
(471,68,'Parchment paper'),
(472,68,'Oven'),
(473,69,'Saucepan'),
(474,69,'Ladle'),
(475,70,'Frying pan'),
(476,70,'Spatula'),
(477,70,'Bowl'),
(478,71,'Oven'),
(479,71,'Baking tray'),
(480,71,'Mixing bowl'),
(481,72,'Pot'),
(482,72,'Spoon'),
(483,72,'Knife'),
(484,73,'Non-stick pan'),
(485,73,'Spatula'),
(486,73,'Bowl'),
(487,74,'Mixing bowl'),
(488,74,'Spoon'),
(489,74,'Knife and chopping board'),
(490,75,'1'),
(491,76,'Pan'),
(492,76,'pot'),
(493,76,'strainer'),
(529,51,'Coffee press or drip filter'),
(530,51,'Glasses'),
(531,51,'Spoon'),
(533,77,'Mixing bowl'),
(534,77,'cups or glasses'),
(535,77,'spoon'),
(536,77,'whisk'),
(537,91,'Pan'),
(538,91,'Knife'),
(539,91,'Plate'),
(540,92,'pan'),
(541,92,'knife'),
(542,92,'bowl'),
(543,93,'Skillet or frying pan'),
(544,93,'Spatula'),
(545,93,'Knife & cutting board'),
(546,93,'Measuring spoons'),
(547,94,'Toaster or oven'),
(548,94,'Bowl & fork'),
(549,94,'Knife & cutting board'),
(550,95,'mixing bowl');
/*!40000 ALTER TABLE `equipments` ENABLE KEYS */;
commit;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `favorites` VALUES
(5,71,7,'2025-10-08 09:32:28'),
(6,72,10,'2025-10-08 20:49:57'),
(9,72,1,'2025-10-08 20:52:17'),
(11,72,7,'2025-10-08 20:52:18'),
(13,72,12,'2025-10-08 20:52:39'),
(17,72,6,'2025-10-08 20:52:56'),
(18,72,11,'2025-10-08 21:00:43'),
(21,70,7,'2025-10-10 03:14:27'),
(22,70,8,'2025-10-10 03:14:39'),
(36,72,17,'2025-10-17 20:16:53'),
(38,109,63,'2025-10-26 19:18:51'),
(39,72,33,'2025-10-26 22:23:45'),
(41,72,52,'2025-10-26 23:09:36'),
(42,72,51,'2025-10-26 23:09:40'),
(43,72,41,'2025-10-26 23:09:43'),
(44,72,40,'2025-10-26 23:09:44'),
(45,72,42,'2025-10-26 23:09:47'),
(46,72,2,'2025-10-26 23:10:13'),
(47,72,3,'2025-10-26 23:10:14'),
(48,72,4,'2025-10-26 23:10:15'),
(49,72,77,'2025-10-26 23:10:26'),
(50,69,79,'2025-10-28 21:13:30'),
(60,72,72,'2025-10-30 13:54:41'),
(61,72,74,'2025-10-30 13:55:17'),
(67,69,73,'2025-10-30 14:20:09'),
(76,69,54,'2025-10-30 14:29:20'),
(77,72,69,'2025-10-31 10:48:37'),
(78,72,70,'2025-10-31 10:48:44'),
(79,72,45,'2025-10-31 10:53:37'),
(80,72,44,'2025-10-31 10:53:37'),
(81,72,46,'2025-10-31 10:53:38'),
(82,72,47,'2025-10-31 10:53:39'),
(83,72,48,'2025-10-31 10:53:40'),
(84,72,43,'2025-10-31 10:53:41'),
(85,72,39,'2025-10-31 10:53:44'),
(86,72,50,'2025-10-31 10:53:57'),
(87,72,49,'2025-10-31 10:53:58'),
(88,72,38,'2025-10-31 10:54:05'),
(89,72,37,'2025-10-31 10:54:06'),
(90,72,36,'2025-10-31 10:54:07'),
(91,72,35,'2025-10-31 10:54:08'),
(92,72,34,'2025-10-31 10:54:09'),
(93,72,32,'2025-10-31 10:54:12'),
(94,72,31,'2025-10-31 10:54:14'),
(95,72,5,'2025-10-31 10:54:16'),
(96,72,55,'2025-10-31 10:54:53'),
(98,107,91,'2025-11-01 17:28:46'),
(99,105,91,'2025-11-01 23:16:27'),
(100,105,8,'2025-11-01 23:17:57'),
(101,105,5,'2025-11-01 23:17:57'),
(102,105,6,'2025-11-01 23:17:57'),
(103,114,92,'2025-11-04 19:02:25'),
(104,70,91,'2025-11-04 19:03:11'),
(105,70,94,'2025-11-04 19:12:53'),
(106,70,53,'2025-11-04 19:54:07'),
(110,69,92,'2025-11-13 11:38:53');
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
commit;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `feedback` VALUES
(9,'chailes','chailesreyes04@gmail.com','wow','2025-10-16 03:37:36'),
(10,'joza','joza@gmail.com','the best website','2025-10-26 07:56:47'),
(11,'christine','chailesreyes04@gmail.com','how to use?','2025-10-30 11:52:05');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
commit;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `ingredient_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`),
  CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=914 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ingredients` VALUES
(37,5,'1 cup leftover adobo (shredded)'),
(38,5,'2 cups garlic rice'),
(39,5,'2 large flour tortillas'),
(40,5,'2 eggs (scrambled)'),
(41,5,'1 tbsp cooking oil'),
(42,5,'1 tbsp soy sauce'),
(43,5,'Salt and pepper to taste'),
(44,5,'Optional: sliced tomatoes, cheese, or hot sauce'),
(45,6,'1 shot espresso (or 1/2 cup strong coffee)'),
(46,6,'1/2 cup coconut milk'),
(47,6,'2 tbsp pandan syrup'),
(48,6,'1/4 cup ice cubes'),
(49,6,'Optional: whipped cream or pandan leaf for garnish'),
(57,7,'1 head garlic (roasted)'),
(58,7,'2 tbsp butter'),
(59,7,'1 small onion (chopped)'),
(60,7,'2 tbsp flour'),
(61,7,'3 cups chicken broth'),
(62,7,'1 cup milk'),
(63,7,'Â½ cup grated Parmesan cheese'),
(64,7,'Salt and pepper to taste'),
(65,7,'Fresh parsley (for garnish)'),
(66,8,'2 chicken breasts (cut into bite-sized cubes)'),
(67,8,'1 cup breadcrumbs (preferably Panko)'),
(68,8,'2 eggs (beaten)'),
(69,8,'Â½ tsp salt'),
(70,8,'Â½ tsp pepper'),
(71,8,'1 cup cooking oil'),
(72,8,'3 tbsp mayonnaise'),
(73,8,'1 tbsp ketchup'),
(74,8,'Â½ tsp sugar'),
(75,8,'1 tsp soy sauce'),
(76,9,'6 fresh strawberries (hulled)'),
(77,9,'Â½ cup canned lychee with syrup'),
(78,9,'1 tbsp lemon juice'),
(79,9,'1 tbsp honey or sugar (optional, adjust to taste)'),
(80,9,'1 Â½ cups sparkling water or soda water (chilled)'),
(81,9,'Ice cubes'),
(82,9,'Mint leaves (for garnish)'),
(164,11,'1 whole chicken (1.2â€“1.5 kg)'),
(165,11,'3 tbsp soy sauce'),
(166,11,'2 tbsp honey'),
(167,11,'2 tbsp calamansi or lemon juice'),
(168,11,'1 tbsp minced garlic'),
(169,11,'1 tsp black pepper'),
(170,11,'1 tbsp oil'),
(236,10,'1 cup broccoli florets, cauliflower florets'),
(237,10,'1 small carrot (thinly sliced)'),
(238,10,'Â½ cup bell peppers (red and green, sliced)'),
(239,10,'1 small zucchini (sliced)'),
(240,10,'2 tbsp butter'),
(241,10,'2 cloves garlic (minced)'),
(242,10,'1 tbsp soy sauce'),
(243,10,'Â½ tsp salt'),
(244,10,'Â¼ tsp black pepper'),
(245,10,'Â½ tsp sesame oil (optional)'),
(246,10,'1 tsp sesame seeds (for garnish)'),
(269,4,'1 cup crushed graham crackers'),
(270,4,'1/4 cup melted butter'),
(271,4,'1 1/2 cups cream cheese'),
(272,4,'1/2 cup ube halaya'),
(273,4,'1/4 cup condensed milk'),
(274,4,'1/2 tsp vanilla extract'),
(275,4,'Whipped cream for topping'),
(287,3,'1 cup cooked quinoa'),
(288,3,'1/2 cup diced pineapple'),
(289,3,'1/2 cup diced mango'),
(290,3,'1/4 cup cucumber (chopped)'),
(291,3,'1 tbsp olive oil'),
(292,3,'2 tbsp calamansi juice'),
(293,3,'1 tsp honey'),
(294,3,'Salt and pepper to taste'),
(295,3,'Fresh mint leaves'),
(296,2,'2 packs ramen noodles'),
(297,2,'1 cup coconut milk'),
(298,2,'1 ripe mango (pureed)'),
(299,2,'1 tbsp chili garlic oil, 1 tbsp soy sauce'),
(300,2,'1 small onion (chopped)'),
(301,2,'2 cloves garlic (minced)'),
(302,2,'1 tsp fish sauce'),
(303,2,'1 cup vegetable broth'),
(304,2,'1 boiled egg (halved)'),
(305,2,'Green onions & sesame seeds (for garnish)'),
(306,1,'300g chicken breast (thinly sliced)'),
(307,1,'Â½ cup coconut milk'),
(308,1,'1 tbsp soy sauce, honey,lime juice,chopped cilantro'),
(309,1,'2 cloves garlic (minced)'),
(310,1,'1 tsp grated ginger'),
(311,1,'Salt and pepper to taste'),
(312,1,'1 ripe mango (diced)'),
(313,1,'Â¼ red onion (finely chopped)'),
(314,1,'1 small tomato (diced)'),
(315,1,'1 cup rice, coconut milk, cup water'),
(352,12,'½ cup quinoa'),
(353,12,'1 cup mixed veggies (broccoli, carrots, bell peppers)'),
(354,12,'1 tbsp olive oil'),
(355,12,'1 tsp lemon juice'),
(356,12,'Salt & pepper'),
(446,28,'1'),
(447,29,'3 tbsp chia seeds'),
(448,29,'1 cup coconut milk (unsweetened)'),
(449,29,'1 tsp turmeric powder'),
(450,29,'1 tsp honey or maple syrup (optional)'),
(451,29,'½ tsp vanilla extract'),
(452,29,'Pinch of black pepper (enhances turmeric absorption)'),
(453,29,'Fresh fruits or nuts for topping (mango, berries, almonds)'),
(454,30,'2 salmon fillets'),
(455,30,'2 tbsp honey'),
(456,30,'2 cloves garlic, minced'),
(457,30,'1 tbsp soy sauce'),
(458,30,'1 tsp olive oil'),
(459,30,'Salt and pepper to taste'),
(460,17,'⅘ medium potatoes, peeled and diced (Yukon gold or russet work best)'),
(461,17,'4 strips bacon, chopped (optional but adds great flavor)'),
(462,17,'1 medium onion, diced'),
(463,17,'2 cloves garlic, minced'),
(464,17,'3 tablespoons butter'),
(465,17,'3 tablespoons all-purpose flour'),
(466,17,'4 cups chicken or vegetable broth'),
(467,17,'½ cups milk (or half-and-half for richer soup)'),
(468,17,'½ cup heavy cream (optional for extra creaminess)'),
(469,17,'½ teaspoon salt (adjust to taste)'),
(470,17,'¼ teaspoon black pepper'),
(471,17,'¼ teaspoon paprika (optional, adds warmth)'),
(472,17,'½ cup shredded cheddar cheese (plus more for topping)'),
(473,17,'2 tablespoons chopped chives or green onions for garnish'),
(474,18,'300 g all-purpose flour (or bread flour)'),
(475,18,'3 g (½ teaspoon) salt'),
(476,18,'3 g active dry yeast'),
(477,18,'1 tablespoon sugar'),
(478,18,'180 ml warm water (about 38â€“40 Â°C / 100â€“105 Â°F)'),
(479,18,'15 ml olive oil (or neutral oil)'),
(480,18,'50 g (¼ cup) softened butter'),
(481,18,'3 to 4 cloves garlic, minced (or more, to taste)'),
(482,18,'10 g chopped parsley (optional)'),
(483,18,'80 g mozzarella cheese (shredded)'),
(484,18,'(Optional) Egg wash (1 egg + splash of water) for brushing the tops'),
(485,31,'½ cup rolled oats'),
(486,31,'½ cup milk (or plant-based milk)'),
(487,31,'1 tsp cocoa powder'),
(488,31,'1 tsp honey or maple syrup'),
(489,31,'1 small banana, sliced'),
(490,31,'Optional: chia seeds or nuts for topping'),
(491,32,'1 kg chicken wings'),
(492,32,'Salt and pepper, to taste'),
(493,32,'1 tablespoon cornstarch'),
(494,32,'1 tablespoon all-purpose flour'),
(495,32,'Cooking oil (for frying)'),
(496,32,'2 tablespoons gochujang (Korean chili paste)'),
(497,32,'2 tablespoons soy sauce'),
(498,32,'2 tablespoons honey or brown sugar'),
(499,32,'1 tablespoon rice vinegar'),
(500,32,'2 cloves garlic, minced'),
(501,32,'1 teaspoon grated ginger'),
(502,32,'1 teaspoon sesame oil'),
(503,32,'1 tablespoon water'),
(504,33,'2 cups cooked rice'),
(505,33,'300 g medium shrimp, peeled and deveined'),
(506,33,'3 tbsp unsalted butter'),
(507,33,'4 cloves garlic, minced'),
(508,33,'1 tbsp soy sauce, oyster sauce'),
(509,33,'½ tsp chili flakes (optional)'),
(510,33,'1 tbsp lemon juice'),
(511,33,'1 tbsp chopped parsley or spring onions'),
(512,33,'Salt and pepper to taste'),
(513,34,'Spaghetti'),
(514,34,'canned tuna'),
(515,34,'garlic'),
(516,34,'cream'),
(517,34,'cheese'),
(518,34,'salt'),
(519,34,'pepper'),
(520,35,'Chicken breast'),
(521,35,'cornstarch'),
(522,35,'sweet chili sauce'),
(523,35,'soy sauce'),
(524,35,'garlic'),
(525,36,'Rice'),
(526,36,'egg'),
(527,36,'carrots'),
(528,36,'peas'),
(529,36,'soy sauce'),
(530,36,'garlic'),
(531,37,'Graham crackers'),
(532,37,'cream'),
(533,37,'condensed milk'),
(534,37,'mango'),
(535,38,'Corn kernels'),
(536,38,'butter'),
(537,38,'garlic'),
(538,38,'salt'),
(539,38,'parsley'),
(540,39,'Cheese sticks'),
(541,39,'lumpia wrapper'),
(542,39,'oil'),
(543,39,'flour'),
(544,39,'water (for sealing)'),
(545,40,'Potatoes'),
(546,40,'butter'),
(547,40,'garlic'),
(548,40,'parmesan cheese'),
(549,40,'salt'),
(550,40,'parsley'),
(558,42,'White bread'),
(559,42,'canned tuna'),
(560,42,'mayonnaise'),
(561,42,'salt'),
(562,42,'pepper'),
(563,43,'Chicken cuts'),
(564,43,'green papaya'),
(565,43,'ginger'),
(566,43,'garlic'),
(567,43,'onion'),
(568,43,'chili leaves'),
(569,44,'Button mushrooms'),
(570,44,'milk'),
(571,44,'butter'),
(572,44,'flour'),
(573,44,'garlic'),
(574,44,'onion'),
(575,44,'broth'),
(576,45,'Cucumber'),
(577,45,'tomato'),
(578,45,'onion'),
(579,45,'vinegar'),
(580,45,'olive oil'),
(581,45,'salt'),
(582,45,'pepper'),
(590,47,'Elbow macaroni'),
(591,47,'mayo'),
(592,47,'cheese'),
(593,47,'condensed milk'),
(594,46,'Cabbage'),
(595,46,'carrot'),
(596,46,'mayonnaise'),
(597,46,'vinegar'),
(598,46,'sugar'),
(599,46,'salt'),
(600,46,'pepper'),
(601,41,'Ground pork'),
(602,41,'carrots'),
(603,41,'onion'),
(604,41,'lumpia wrapper'),
(605,41,'soy sauce'),
(606,41,'egg'),
(607,41,'oil'),
(608,48,'2 cups leftover adobo meat, shredded (chicken or pork)'),
(609,48,'4 pcs large flour tortillas'),
(610,48,'3 pcs eggs, scrambled'),
(611,48,'1 cup grated cheese (cheddar or quick melt)'),
(612,48,'½ cup sautéed onions'),
(613,48,'1 tbsp cooking oil'),
(614,48,'Salt and pepper to taste'),
(615,48,'3 tbsp mayonnaise'),
(616,48,'1 tsp minced garlic'),
(617,48,'1 tsp calamansi or lemon juice'),
(618,49,'1 cup all-purpose flour'),
(619,49,'2 tsp baking powder'),
(620,49,'2 tbsp sugar'),
(621,49,'½ cup ube jam (halaya)'),
(622,49,'¾ cup milk'),
(623,49,'1 pc egg'),
(624,49,'2 tbsp butter, melted'),
(625,49,'¼ cup cream cheese, cubed and chilled'),
(632,50,'6 pcs calamansi, juiced'),
(633,50,'2 tbsp honey (or sugar syrup)'),
(634,50,'1 cup cold soda water'),
(635,50,'½ cup cold water'),
(636,50,'Ice cubes'),
(637,50,'Lemon slices and mint for garnish'),
(643,52,'4 pcs pandan leaves, knotted'),
(644,52,'2 cups water'),
(645,52,'1 cup coconut milk'),
(646,52,'¼ cup sugar'),
(647,52,'Ice cubes'),
(648,52,'Optional: young coconut strips'),
(649,53,'1 cup fresh milk'),
(650,53,'½ cup ube jam (halaya)'),
(651,53,'1 cup strongly brewed black tea'),
(652,53,'2 tbsp condensed milk'),
(653,53,'½ cup tapioca pearls (optional)'),
(654,53,'Ice cubes'),
(655,54,'2 pcs ripe bananas'),
(656,54,'2 tbsp peanut butter'),
(657,54,'1 cup fresh milk'),
(658,54,'1 tsp honey (optional)'),
(659,54,'Ice cubes'),
(660,55,'1 ½ cups fresh pineapple chunks'),
(661,55,'1 cup cold water'),
(662,55,'1 tbsp lemon juice'),
(663,55,'5 pcs fresh basil leaves'),
(664,55,'2 tsp honey'),
(665,55,'Ice cubes'),
(666,56,'2 bunches kangkong (water spinach), trimmed'),
(667,56,'5 cloves garlic, minced'),
(668,56,'1 tbsp soy sauce'),
(669,56,'1 tbsp cooking oil'),
(670,56,'¼ tsp black pepper'),
(671,56,'salt'),
(672,57,'½ cup broccoli florets'),
(673,57,'½ cup carrots, sliced thinly'),
(674,57,'½ cup cauliflower florets'),
(675,57,'½ cup bell pepper, sliced'),
(676,57,'2 tbsp oyster sauce'),
(677,57,'1 tbsp soy sauce'),
(678,57,'1 tbsp cornstarch (dissolved in 2 tbsp water)'),
(679,57,'2 tbsp cooking oil'),
(680,57,'Salt and pepper to taste'),
(681,58,'2 pcs eggplant (talong)'),
(682,58,'2 pcs eggs, beaten'),
(683,58,'1 tbsp soy sauce'),
(684,58,'½ tsp salt'),
(685,58,'¼ tsp pepper'),
(686,58,'2 tbsp cooking oil'),
(687,59,'1 cup ampalaya (bitter melon), sliced'),
(688,59,'1 cup eggplant, sliced'),
(689,59,'½ cup sitaw (string beans), cut into 2-inch pieces'),
(690,59,'½ cup okra, sliced'),
(691,59,'2 tbsp bagoong alamang (shrimp paste)'),
(692,59,'1 small onion, chopped'),
(693,59,'2 cloves garlic, minced'),
(694,59,'1 tomato, chopped'),
(695,59,'1 cup water'),
(696,59,'2 tbsp cooking oil'),
(697,60,'400g spaghetti pasta'),
(698,60,'1 cup all-purpose cream'),
(699,60,'½ cup evaporated milk'),
(700,60,'1 cup bacon or ham, diced'),
(701,60,'½ cup grated cheese'),
(702,60,'3 cloves garlic, minced'),
(703,60,'1 tbsp butter'),
(704,60,'Salt and pepper to taste'),
(705,61,'½ kg ground pork'),
(706,61,'1 pc carrot, grated'),
(707,61,'1 pc onion, chopped'),
(708,61,'2 cloves garlic, minced'),
(709,61,'1 pc egg'),
(710,61,'1 tsp salt'),
(711,61,'½ tsp pepper'),
(712,61,'25 pcs lumpia wrapper'),
(713,61,'Oil for frying'),
(714,62,'400g spaghetti noodles'),
(715,62,'1 cup tomato sauce'),
(716,62,'½ cup banana ketchup'),
(717,62,'½ kg ground beef or pork'),
(718,62,'1 small onion, chopped'),
(719,62,'2 cloves garlic, minced'),
(720,62,'1 cup grated cheese'),
(721,62,'½ cup all-purpose cream'),
(722,62,'1 tbsp cooking oil'),
(723,63,'1 cup rice flour'),
(724,63,'1 tsp baking powder'),
(725,63,'½ cup sugar'),
(726,63,'1 cup coconut milk'),
(727,63,'2 pcs eggs'),
(728,63,'2 tbsp butter, melted'),
(729,63,'1 pc salted egg, sliced'),
(730,63,'½ cup grated cheese'),
(731,64,'1 pc chicken breast fillet'),
(732,64,'2 cups mixed lettuce'),
(733,64,'½ cup cucumber, sliced'),
(734,64,'½ cup cherry tomatoes'),
(735,64,'1 tbsp olive oil'),
(736,64,'Salt and pepper to taste'),
(737,64,'2 tbsp honey'),
(738,64,'1 tbsp lime juice'),
(739,64,'1 tsp olive oil'),
(740,64,'Pinch of salt'),
(762,65,'2 cups cooked brown rice'),
(763,65,'1 cup broccoli florets'),
(764,65,'½ cup carrots, sliced'),
(765,65,'½ cup bell peppers'),
(766,65,'2 tbsp soy sauce (low sodium)'),
(767,65,'1 tbsp olive oil'),
(768,65,'1 clove garlic, minced'),
(769,67,'1 slice whole grain bread'),
(770,67,'½ ripe avocado'),
(771,67,'1 egg (boiled or poached)'),
(772,67,'Salt and pepper to taste'),
(773,67,'1 tsp olive oil (optional)'),
(774,68,'2 pcs sweet potatoes, sliced into fries'),
(775,68,'1 tbsp olive oil'),
(776,68,'Salt and pepper to taste'),
(777,68,'½ tsp paprika (optional)'),
(778,69,'3 cups water'),
(779,69,'2 tbsp miso paste'),
(780,69,'½ cup soft tofu, cubed'),
(781,69,'1 tbsp dried seaweed (wakame)'),
(782,69,'1 stalk green onion, sliced'),
(783,70,'250g shrimp, peeled and deveined'),
(784,70,'2 cloves garlic, minced'),
(785,70,'2 tbsp unsalted butter'),
(786,70,'1 tsp lemon juice'),
(787,70,'Salt and pepper to taste'),
(788,70,'Fresh parsley for garnish'),
(789,71,'2 cups cauliflower rice'),
(790,71,'1 egg'),
(791,71,'½ cup grated mozzarella cheese'),
(792,71,'1 tsp dried oregano'),
(793,71,'½ tsp garlic powder'),
(794,71,'Salt and pepper to taste'),
(795,72,'1 cup green or brown lentils'),
(796,72,'1 pc carrot, diced'),
(797,72,'1 stalk celery, diced'),
(798,72,'1 pc onion, chopped'),
(799,72,'2 cloves garlic, minced'),
(800,72,'4 cups water or low-sodium broth'),
(801,72,'1 tsp herbs de Provence'),
(802,72,'Black pepper to taste'),
(803,73,'4 pcs egg whites'),
(804,73,'½ cup spinach'),
(805,73,'¼ cup mushrooms, sliced'),
(806,73,'1 tbsp onion, chopped'),
(807,73,'1 tsp olive oil'),
(808,73,'Salt and pepper to taste'),
(809,74,'1 can chickpeas, drained and rinsed'),
(810,74,'1 pc cucumber, diced'),
(811,74,'1 cup cherry tomatoes, halved'),
(812,74,'½ cup feta cheese, crumbled (optional)'),
(813,74,'¼ cup black olives, sliced'),
(814,74,'2 tbsp olive oil'),
(815,74,'1 tbsp lemon juice'),
(816,74,'1 tsp dried oregano'),
(817,74,'Salt and pepper to taste'),
(818,75,'1'),
(819,76,'200g pasta (spaghetti or fettuccine)'),
(820,76,'1 tbsp butter'),
(821,76,'3 cloves garlic, minced'),
(822,76,'1 cup spinach'),
(823,76,'1 cup milk or cream'),
(872,51,'2 tbsp ground barako coffee'),
(873,51,'¾ cup hot water'),
(874,51,'½ cup fresh milk'),
(875,51,'1 tbsp muscovado sugar (or brown sugar)'),
(876,51,'Ice cubes'),
(878,77,'1 cup crushed graham crackers'),
(879,77,'1 tbsp melted butter'),
(880,77,'1 cup cream cheese'),
(881,77,'¼ cup condensed milk'),
(882,77,'1 ripe mango (sliced)'),
(883,91,'200g spaghetti or linguine'),
(884,91,'3 salted egg yolks (store-bought or homemade)'),
(885,91,'2 tbsp unsalted butter'),
(886,91,'2 cloves garlic, minced'),
(887,91,'1/4 cup heavy cream'),
(888,91,'2 tbsp grated Parmesan cheese'),
(889,91,'Pinch of black pepper'),
(890,91,'200g large prawns, peeled and deveined'),
(891,91,'1/4 cup cornstarch'),
(892,91,'1/4 tsp paprika'),
(893,91,'Salt and pepper to taste'),
(894,91,'Chopped spring onions'),
(895,92,'4 boneless, skinless chicken thighs (or breasts)'),
(896,92,'1 large ripe mango, peeled and diced'),
(897,92,'1 cup coconut milk (full-fat preferred)'),
(898,92,'2 tablespoons olive oil'),
(899,92,'3 cloves garlic, minced'),
(900,92,'1 small onion, finely chopped'),
(901,93,'200g shrimp, peeled'),
(902,93,'½ cup pineapple, diced'),
(903,93,'1 tsp chili powder'),
(904,93,'1 tsp olive oil'),
(905,93,'4 small tortillas'),
(906,93,'Lime wedges & cilantro'),
(907,94,'2 slices whole-grain bread, toasted'),
(908,94,'½ avocado'),
(909,94,'1 tsp miso paste'),
(910,94,'1 tsp sesame seeds'),
(911,94,'Chili flakes (optional)'),
(912,95,'sugar'),
(913,95,'soy souce');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
commit;

--
-- Table structure for table `instructions`
--

DROP TABLE IF EXISTS `instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `instructions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `instruction_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=638 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructions`
--

/*!40000 ALTER TABLE `instructions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `instructions` VALUES
(19,5,'Heat oil in a pan and fry shredded adobo until crispy.'),
(20,5,'Scramble eggs and season with salt and pepper.'),
(21,5,'Warm the tortillas.'),
(22,5,'Layer garlic rice, adobo flakes, and eggs; roll tightly.'),
(23,5,'Toast the burrito lightly on a pan until golden.'),
(24,6,'Brew your espresso or coffee.'),
(25,6,'In a glass, add pandan syrup and ice.'),
(26,6,'Pour in coconut milk, then add coffee.'),
(27,6,'Stir lightly and garnish with pandan leaf.'),
(32,7,'Roast the garlic until soft and fragrant (about 20 mins at 180Â°C).'),
(33,7,'In a pot, melt butter and sautÃ© onions until translucent.'),
(34,7,'Stir in flour to make a roux, cooking for 1 minute.'),
(35,7,'Gradually add chicken broth and whisk until smooth.'),
(36,7,'Add roasted garlic and simmer for 10 minutes.'),
(37,7,'Blend the soup until smooth, return to pot, and stir in milk and Parmesan.'),
(38,7,'Simmer for 5 minutes until creamy.'),
(39,7,'Season with salt and pepper; serve garnished with parsley.'),
(40,8,'Season chicken cubes with salt and pepper.'),
(41,8,'Dredge each piece in flour, dip in beaten eggs, then coat with breadcrumbs.'),
(42,8,'Heat oil to 170â€“180Â°C and deep fry until golden brown (about 3â€“4 minutes per batch).'),
(43,8,'Drain on a paper towel or wire rack.'),
(44,8,'Mix mayo, ketchup, soy sauce, and sugar for the dipping sauce.'),
(45,8,'Serve warm with sauce on the side.'),
(46,9,'In a blender, combine strawberries, lychee (with syrup), lemon juice, and honey. Blend until smooth.'),
(47,9,'Pour the mixture through a strainer into a mixing glass or shaker to remove pulp if desired.'),
(48,9,'Add ice and gently whisk or stir with sparkling water until well mixed.'),
(49,9,'Pour into glasses filled with ice cubes.'),
(50,9,'Garnish with mint leaves and a strawberry slice.'),
(51,9,'Serve immediately while bubbly and cold!'),
(87,13,'asdadaf'),
(100,11,'Mix soy sauce, honey, lemon juice, garlic, and pepper.'),
(101,11,'Marinate chicken for 1 hour.'),
(102,11,'Roast at 190Â°C for 60 minutes, basting every 20 minutes.'),
(103,11,'Rest 10 mins before slicing.'),
(107,14,'ed'),
(108,15,'a'),
(109,16,'f'),
(127,19,'1'),
(129,20,'1'),
(130,21,'1'),
(132,22,'1'),
(133,23,'1'),
(151,10,'Heat butter in a wok over medium heat until melted.'),
(152,10,'Add minced garlic and sautÃ© until fragrant (about 30 seconds).'),
(153,10,'Add carrots, broccoli, and cauliflower. Stir-fry for 3 minutes.'),
(154,10,'Add bell peppers and zucchini, then pour in soy sauce, salt, and pepper.'),
(155,10,'Stir-fry for another 4â€“5 minutes, until vegetables are tender-crisp.'),
(156,10,'Drizzle sesame oil (optional) and toss well.'),
(157,10,'Transfer to a serving dish and sprinkle sesame seeds on top.'),
(176,4,'Mix crushed grahams and melted butter; press into mini cups.'),
(177,4,'Blend cream cheese, ube halaya, condensed milk, and vanilla until smooth.'),
(178,4,'Pour over crust; chill for at least 2 hours.'),
(179,4,'Top with whipped cream before serving.'),
(189,3,'In a bowl, combine quinoa, mango, pineapple, and cucumber.'),
(190,3,'Mix olive oil, calamansi juice, honey, salt, and pepper to make the dressing.'),
(191,3,'Pour dressing over salad and toss gently.'),
(192,3,'Chill for 10 minutes before serving.'),
(193,2,'SautÃ© garlic and onion in chili oil until fragrant.'),
(194,2,'Add coconut milk, broth, soy sauce, and fish sauce. Simmer for 5 minutes.'),
(195,2,'Stir in mango puree; simmer another 3 minutes.'),
(196,2,'Add noodles and cook until tender.'),
(197,2,'Serve hot topped with egg, green onions, and sesame seeds.'),
(198,1,'Mix coconut milk, soy sauce, honey, garlic, ginger, and lime juice. Add chicken and let marinate for 15â€“30 minutes.'),
(199,1,'Combine rice, coconut milk, water, and salt in a pot or rice cooker. Cook until fluffy (15â€“20 minutes).'),
(200,1,'In a bowl, combine mango, onion, tomato, bell pepper, cilantro, and lime juice. Season lightly with salt.'),
(201,1,'Heat a pan over medium heat. Add the marinated chicken and cook for 5â€“7 minutes per side, until golden brown and fully cooked.'),
(202,1,'Add a serving of coconut rice to a bowl. Top with chicken and mango salsa. Garnish with coconut flakes and lime wedges.'),
(229,12,'Boil quinoa until fluffy.'),
(230,12,'Steam or roast veggies.'),
(231,12,'Mix all ingredients and drizzle with olive oil and lemon juice.'),
(249,24,'1'),
(283,25,'1'),
(285,27,'1'),
(301,26,'Uahw'),
(303,28,'1'),
(304,29,'In a mixing bowl, combine coconut milk, turmeric, maple syrup, vanilla, and black pepper.'),
(305,29,'Whisk in chia seeds until well combined.'),
(306,29,'Let mixture sit for 5 minutes, then whisk again to prevent clumping.'),
(307,29,'Cover and refrigerate for at least 2 hours or overnight.'),
(308,29,'Serve chilled, topped with toasted coconut flakes or fresh fruit.'),
(309,30,'Season salmon fillets with salt and pepper.'),
(310,30,'Heat olive oil in a pan over medium heat.'),
(311,30,'Place salmon fillets skin-side down and cook for 4-5 minutes.'),
(312,30,'Flip the fillets and cook for another 3-4 minutes.'),
(313,30,'In a small bowl, whisk together honey, garlic, and soy sauce.'),
(314,30,'Pour the glaze over the salmon in the last 2 minutes of cooking.'),
(315,30,'Remove from heat and serve immediately.'),
(316,17,'In a large pot, cook chopped bacon over medium heat until crispy. Remove and set aside, leaving about 1 tablespoon of bacon fat in the pot.'),
(317,17,'Add butter to the pot. Once melted, sautÃ© the diced onion for 3â€“4 minutes until translucent. Add minced garlic and cook for another 30 seconds.'),
(318,17,'Stir in the flour and cook for 1 minute, forming a paste that helps thicken the soup.'),
(319,17,'Gradually whisk in the broth to avoid lumps. Add the diced potatoes. Bring to a boil, then reduce heat to medium-low and simmer for 15â€“20 minutes, or until potatoes are tender.'),
(320,17,'Use an immersion blender to puree part (or all) of the soup to your desired texture.\r\nTip: Blend half for a thick yet chunky soup.'),
(321,17,'Stir in the milk (and cream, if using). Heat gently but donâ€™t let it boil. Add salt, pepper, and paprika.'),
(322,17,'Stir in shredded cheddar until melted and smooth. Taste and adjust seasoning.'),
(323,17,'Ladle into bowls, top with crispy bacon, more cheese, and chopped chives.'),
(324,18,'Activate yeast:\r\nMix warm water + sugar + yeast. Let it sit for ~5 minutes until foamy.'),
(325,18,'Make dough:\r\nIn a bowl, combine flour and salt. Add yeast mixture + oil. Mix until it forms a dough.'),
(326,18,'Knead:\r\nOn a lightly floured surface, knead for 8-10 minutes until smooth and elastic.'),
(327,18,'First proof / rise:\r\nPlace dough in an oiled bowl, cover (with a cloth or plastic wrap), and let rise for ~30 minutes (until doubled in size).'),
(328,18,'Prepare garlic butter & cheese:\r\nWhile dough rises, mix softened butter + minced garlic + parsley (if using). Shred the mozzarella.'),
(329,18,'Shape and fill:\r\nPunch down the dough to remove air. Roll it into a rectangle. Spread garlic butter mixture over the dough, then sprinkle the shredded mozzarella. Roll the dough (like a jelly-roll) from one short side, pinch the seams closed.'),
(330,18,'Second rise:\r\nCover rolls and let them rise again for ~20 to 30 minutes until puffy.'),
(331,18,'Bake:\r\nPreheat oven to 180 °C (350 °F). Brush tops with egg wash (optional). Bake for 20â€“25 minutes, until golden brown. If cheese starts browning too fast, cover loosely with foil.'),
(332,18,'Finish & serve:\r\nLet cool slightly, the serve warm. You can brush extra garlic butter or sprinkle parsley/cheese before serving.'),
(333,31,'In a jar or container, combine oats, milk, cocoa powder, and honey.'),
(334,31,'Stir well to mix all ingredients.'),
(335,31,'Cover and refrigerate overnight (or at least 4 hours).'),
(336,31,'In the morning, top with banana slices and optional chia seeds or nuts.'),
(337,31,'Serve chilled.'),
(338,32,'Prepare the Chicken:\r\nSeason chicken wings with salt and pepper. Add cornstarch and flour, then toss until coated.'),
(339,32,'Deep-Fry the Wings:\r\nHeat oil in a deep pan over medium-high heat. Fry wings for 8–10 minutes until golden and crispy. Drain excess oil.'),
(340,32,'Make the Sauce (Sautéing):\r\nIn a separate pan, sauté garlic and ginger in a small amount of sesame oil. Add gochujang, soy sauce, honey, and rice vinegar. Simmer until the sauce thickens slightly.'),
(341,32,'Coat the Wings:\r\nAdd the fried wings to the pan and toss until evenly coated with the spicy sauce.'),
(342,32,'Serve:\r\nGarnish with sesame seeds and green onions. Serve hot and enjoy!'),
(343,33,'Pat the shrimp dry with paper towels. Season lightly with salt and pepper.'),
(344,33,'In a pan over medium heat, melt 1 tablespoon of butter. Add the shrimp and cook for 1–2 minutes per side until pink. Remove and set aside.'),
(345,33,'In the same pan, melt the remaining butter and add minced garlic. Sauté until golden and fragrant.'),
(346,33,'Stir in soy sauce, oyster sauce, and chili flakes.'),
(347,33,'Toss in the cooked rice and mix well to absorb the sauce.'),
(348,33,'Return the shrimp to the pan, drizzle with lemon juice, and stir everything together.'),
(349,33,'Top with parsley or spring onions. Serve hot in bowls.'),
(350,34,'Boil pasta'),
(351,34,'Sauté garlic'),
(352,34,'Add tuna & cream'),
(353,34,'Mix pasta'),
(354,34,'Serve'),
(355,35,'Coat chicken'),
(356,35,'Fry'),
(357,35,'Add sauce'),
(358,35,'Simmer'),
(359,36,'Cook egg'),
(360,36,'Add veggies'),
(361,36,'Mix rice'),
(362,36,'Season'),
(363,37,'Mix cream'),
(364,37,'Layer graham & mango'),
(365,37,'Chill'),
(366,37,'Serve and eat'),
(367,38,'Melt butter'),
(368,38,'Add garlic'),
(369,38,'Toss corn'),
(370,38,'Mix'),
(371,38,'Serve'),
(372,39,'Slice cheese'),
(373,39,'Wrap in lumpia'),
(374,39,'Seal edges'),
(375,39,'Deep-fry until golden'),
(376,39,'Drain oil and serve with dip'),
(377,40,'Fry potatoes'),
(378,40,'Melt butter with garlic'),
(379,40,'Toss fries'),
(380,40,'Add cheese'),
(381,40,'Serve warm'),
(387,42,'Mix tuna spread'),
(388,42,'Fill bread'),
(389,42,'Seal edges'),
(390,42,'Toast until golden'),
(391,42,'Serve warm'),
(392,43,'Sauté garlic, onion, and ginger'),
(393,43,'Add chicken'),
(394,43,'Pour water'),
(395,43,'Simmer'),
(396,43,'Add papaya'),
(397,43,'Add chili leaves'),
(398,43,'Serve'),
(399,44,'Sauté onion and garlic'),
(400,44,'Add mushrooms'),
(401,44,'Add flour'),
(402,44,'Pour milk and broth'),
(403,44,'Blend until smooth'),
(404,45,'Slice veggies'),
(405,45,'Mix in bowl'),
(406,45,'Add vinegar & oil'),
(407,45,'Toss'),
(408,45,'Chill and serve.'),
(413,47,'Boil pasta'),
(414,47,'Drain'),
(415,47,'Mix with mayo and milk'),
(416,47,'Chill'),
(417,46,'Shred veggies'),
(418,46,'Combine dressing'),
(419,46,'Mix everything'),
(420,46,'Chill before serving'),
(421,41,'Mix filling'),
(422,41,'Wrap'),
(423,41,'Seal'),
(424,41,'Fry until golden'),
(425,41,'Serve with sweet chili sauce'),
(426,48,'Prepare the Filling'),
(427,48,'Assemble the Quesadilla'),
(428,48,'Cook Until Golden'),
(429,48,'Make the Garlic Aioli'),
(430,48,'Serve and Enjoy'),
(431,49,'In a bowl, whisk flour, baking powder, and sugar.'),
(432,49,'Add ube jam, milk, egg, and butter. Mix until smooth.'),
(433,49,'Heat pan over low heat and grease lightly.'),
(434,49,'Pour ¼ cup batter, place a small cube of cream cheese in the center, and cover with more batter.'),
(435,49,'Flip once bubbles form and cook until golden.'),
(436,49,'Stack pancakes and drizzle with condensed milk or ube syrup.'),
(441,50,'Squeeze calamansi and strain seeds.'),
(442,50,'Mix juice, honey, and cold water in a pitcher. Stir well.'),
(443,50,'Add soda water just before serving.'),
(444,50,'Pour into glasses with ice and garnish with lemon slices and mint leaves.'),
(449,52,'In a saucepan, boil pandan leaves in water for 8 minutes to extract flavor.'),
(450,52,'Remove leaves and add sugar; stir until dissolved.'),
(451,52,'Let it cool, then add coconut milk and mix well.'),
(452,52,'Chill and serve over ice with coconut strips.'),
(453,53,'Brew black tea and let cool.'),
(454,53,'In a saucepan, heat milk and ube jam until smooth.'),
(455,53,'Add condensed milk and mix well.'),
(456,53,'Combine with brewed tea and pour over ice.'),
(457,53,'Add tapioca pearls if desired and serve.'),
(458,54,'Combine all ingredients in a blender.'),
(459,54,'Blend until smooth and creamy.'),
(460,54,'Pour into glasses and serve immediately.'),
(461,55,'In a blender, combine pineapple, basil, honey, and water.'),
(462,55,'Blend until smooth.'),
(463,55,'Strain if desired for a smoother texture.'),
(464,55,'Add lemon juice and ice. Mix well and serve cold.'),
(465,56,'Heat oil in a pan over medium heat.'),
(466,56,'Sauté garlic until golden brown.'),
(467,56,'Add kangkong and soy sauce; stir-fry for 1–2 minutes.'),
(468,56,'Season with salt and pepper, then serve hot.'),
(469,57,'Heat oil in a wok. Sauté carrots and broccoli for 2 minutes.'),
(470,57,'Add remaining vegetables and continue stirring.'),
(471,57,'Pour in oyster sauce and soy sauce; mix well.'),
(472,57,'Add cornstarch slurry to thicken sauce.'),
(473,57,'Cook for 2–3 more minutes, season to taste, and serve warm.'),
(474,58,'Grill eggplants until skin is charred and soft.'),
(475,58,'Peel off the skin carefully while keeping the stem intact.'),
(476,58,'Flatten the eggplant with a fork.'),
(477,58,'Dip into beaten egg mixture with soy sauce, salt, and pepper.'),
(478,58,'Fry in hot oil until golden brown on both sides.'),
(479,58,'Serve with rice and ketchup or soy sauce.'),
(480,59,'Heat oil in a pan, sauté garlic, onion, and tomato until soft.'),
(481,59,'Add bagoong and mix for 1 minute.'),
(482,59,'Add kalabasa and ½ cup water. Cook until halfway tender.'),
(483,59,'Add ampalaya, eggplant, okra, and sitaw. Simmer until cooked.'),
(484,59,'Adjust seasoning and serve with rice.'),
(485,60,'Boil pasta according to package instructions. Drain and set aside.'),
(486,60,'In a pan, melt butter and sauté garlic until fragrant.'),
(487,60,'Add bacon or ham and cook until slightly crispy.'),
(488,60,'Pour in all-purpose cream and milk. Stir continuously.'),
(489,60,'Add cooked pasta, mix, and season with salt, pepper, and cheese.'),
(490,60,'Serve hot and garnish with extra cheese on top.'),
(491,61,'Combine pork, carrot, onion, garlic, egg, salt, and pepper in a bowl.'),
(492,61,'Mix well and wrap 1 tbsp of mixture in each lumpia wrapper.'),
(493,61,'Heat oil and fry until golden brown and crispy.'),
(494,61,'Drain excess oil and serve with sweet chili sauce.'),
(495,62,'Boil noodles and set aside.'),
(496,62,'In a pan, sauté garlic and onion. Add ground meat and cook until browned.'),
(497,62,'Pour in tomato sauce and ketchup; simmer for 10 minutes.'),
(498,62,'Layer spaghetti, sauce, and cheese in a baking dish.'),
(499,62,'Top with cream and more cheese.'),
(500,62,'Top with cream and more cheese.'),
(501,63,'Preheat oven to 350°F.'),
(502,63,'Mix rice flour, sugar, baking powder, and coconut milk. Add eggs and butter.'),
(503,63,'Pour batter into molds lined with banana leaves.'),
(504,63,'Top with cheese and salted egg.'),
(505,63,'Bake for 20–25 minutes or until golden.'),
(506,63,'Brush with butter before serving.'),
(507,64,'Season chicken with salt, pepper, and olive oil.'),
(508,64,'Grill for 5–7 minutes per side or until cooked.'),
(509,64,'Mix lettuce, cucumber, and tomatoes in a bowl.'),
(510,64,'Combine honey, lime juice, and olive oil for dressing.'),
(511,64,'Slice grilled chicken and toss with salad and dressing.'),
(516,66,'Heat oil in a pan and sauté garlic.'),
(517,66,'Add vegetables and stir-fry for 3–4 minutes.'),
(518,66,'Add rice and soy sauce, toss well.'),
(519,66,'Cook for 2 more minutes and serve hot.'),
(524,65,'Heat oil in a pan and sauté garlic.'),
(525,65,'Add vegetables and stir-fry for 3–4 minutes.'),
(526,65,'Add rice and soy sauce, toss well.'),
(527,65,'Cook for 2 more minutes and serve hot.'),
(528,67,'Toast bread until crispy.'),
(529,67,'Mash avocado and spread on toast.'),
(530,67,'Top with boiled or poached egg.'),
(531,67,'Top with boiled or poached egg.'),
(532,68,'Preheat oven to 400°F (200°C).'),
(533,68,'Toss sweet potato slices with oil, salt, and paprika.'),
(534,68,'Spread on a tray and bake for 20–25 minutes, flipping halfway.'),
(535,68,'Serve warm.'),
(536,69,'Boil water and lower heat.'),
(537,69,'Add miso paste, stir until dissolved.'),
(538,69,'Add tofu and seaweed, simmer for 2–3 minutes.'),
(539,69,'Garnish with green onions before serving.'),
(540,70,'Melt butter in a pan and sauté garlic until fragrant.'),
(541,70,'Add shrimp and cook until pink and opaque (3–5 minutes).'),
(542,70,'Season with salt, pepper, and lemon juice.'),
(543,70,'Garnish with parsley and serve hot.'),
(544,71,'Preheat oven to 400°F (200°C).'),
(545,71,'Steam cauliflower rice for 5 minutes; let cool.'),
(546,71,'Mix cauliflower, egg, cheese, oregano, garlic powder, salt, and pepper.'),
(547,71,'Press mixture into a pizza shape on a tray and bake for 15–20 minutes.'),
(548,71,'Add toppings and bake 5–10 more minutes.'),
(549,72,'Sauté garlic, onion, carrot, and celery in a small amount of olive oil until soft.'),
(550,72,'Add lentils, herbs, and water/broth.'),
(551,72,'Bring to a boil, then simmer for 25–30 minutes or until lentils are tender.'),
(552,72,'Season with black pepper and serve hot.'),
(553,73,'Heat olive oil in a pan; sauté onion and mushrooms until soft.'),
(554,73,'Add spinach and cook briefly.'),
(555,73,'Pour in egg whites and cook until set.'),
(556,73,'Fold omelette and serve hot.'),
(557,74,'Combine chickpeas, cucumber, tomatoes, olives, and feta in a mixing bowl.'),
(558,74,'Whisk olive oil, lemon juice, oregano, salt, and pepper in a small bowl.'),
(559,74,'Pour dressing over salad and toss well.'),
(560,74,'Chill for 10 minutes before serving.'),
(561,75,'1'),
(562,76,'Boil pasta until al dente.'),
(563,76,'In a pan, sauté garlic in butter, add spinach and cream.'),
(564,76,'Stir in pasta and parmesan; season and serve hot.'),
(571,78,'1'),
(572,79,'1'),
(573,80,'hello'),
(574,81,'hello'),
(575,82,'hello'),
(576,83,'yey'),
(577,84,'s'),
(578,85,'s'),
(579,86,'2083'),
(580,87,'2083'),
(581,88,'<script>\r\n  let cropper;\r\n\r\n  const fileInput = document.getElementById(\'recipe_image\');\r\n  const cropperModal = document.getElementById(\'cropper-modal\');\r\n  const cropperImage = document.getElementById(\'cropper-image\');\r\n  const previewBox = document.get'),
(582,89,'w'),
(607,51,'Brew barako coffee using hot water; let it steep for 3–5 minutes.'),
(608,51,'Pour into a glass with muscovado sugar. Stir until dissolved.'),
(609,51,'Add ice and milk on top.'),
(610,51,'Mix and enjoy chilled.'),
(611,90,'YY'),
(612,77,'Mix grahams with butter and press into cups.'),
(613,77,'Blend cream cheese and condensed milk.'),
(614,77,'Add mixture over the crust.'),
(615,77,'Top with mango slices.'),
(616,77,'Chill for 1 hour before serving.'),
(617,91,'Boil pasta in salted water until al dente. Drain and set aside, reserving 1/4 cup pasta water.'),
(618,91,'Steam or boil salted egg yolks for 5 minutes. Mash into a smooth paste.'),
(619,91,'In a pan, melt butter over medium heat. Sauté garlic until fragrant.'),
(620,91,'Add mashed salted egg yolks, heavy cream, and Parmesan cheese. Stir until creamy.'),
(621,91,'Toss in cooked pasta and a little pasta water to coat evenly.'),
(622,91,'Toss prawns in cornstarch, paprika, salt, and pepper.'),
(623,91,'Heat oil in a pan over medium-high heat. Fry prawns until golden and crispy (~2–3 min per side). Drain on paper towels.'),
(624,91,'Plate pasta and top with crispy prawns. Garnish with spring onions, bread crumbs, or a squeeze of lemon/calamansi.'),
(625,91,'Best enjoyed hot while the sauce is creamy and prawns are crispy.'),
(626,92,'In a medium saucepan, heat coconut oil over medium heat.'),
(627,92,'Add the rinsed rice and turmeric powder, stirring for 1–2 minutes until fragrant.'),
(628,92,'Pour in water or broth and add salt.'),
(629,92,'Bring to a boil, then reduce heat to low. Cover and simmer for 15 minutes.'),
(630,92,'Remove from heat and let sit, covered, for 5 minutes. Fluff with a fork before serving.'),
(631,92,'Add diced mango and cooked chicken back to the skillet. Simmer for another 5–7 minutes until chicken is cooked through and sauce has thickened slightly.'),
(632,92,'Finish with lime juice and adjust salt/pepper if needed.'),
(633,93,'Sauté shrimp with olive oil, chili, and pineapple for 4–5 min.'),
(634,93,'Warm tortillas, fill with shrimp mixture, top with cilantro and a squeeze of lime.'),
(635,94,'Mash avocado with miso paste.'),
(636,94,'Spread on toast, sprinkle sesame seeds and chili flakes.'),
(637,95,'Mixed');
/*!40000 ALTER TABLE `instructions` ENABLE KEYS */;
commit;

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `likes`
--

/*!40000 ALTER TABLE `likes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `likes` VALUES
(1,70,3,'2025-10-08 15:31:35'),
(2,70,4,'2025-10-08 15:31:46'),
(3,71,4,'2025-10-08 16:12:56'),
(4,71,6,'2025-10-08 16:12:59'),
(5,71,2,'2025-10-08 16:14:15'),
(6,71,5,'2025-10-08 16:32:26'),
(7,69,8,'2025-10-08 16:39:56'),
(8,69,6,'2025-10-08 16:39:57'),
(11,72,6,'2025-10-09 03:48:46'),
(12,72,4,'2025-10-09 03:48:47'),
(13,72,7,'2025-10-09 03:48:49'),
(16,72,8,'2025-10-09 03:49:03'),
(17,72,5,'2025-10-09 03:49:07'),
(19,72,3,'2025-10-09 03:49:12'),
(20,72,2,'2025-10-09 03:49:17'),
(21,72,12,'2025-10-09 03:49:19'),
(23,72,10,'2025-10-09 03:49:58'),
(24,72,11,'2025-10-09 03:52:15'),
(25,72,1,'2025-10-09 03:52:16'),
(26,72,9,'2025-10-09 03:53:03'),
(28,70,8,'2025-10-10 10:14:17'),
(29,70,6,'2025-10-10 10:14:19'),
(31,70,7,'2025-10-10 10:14:27'),
(44,72,17,'2025-10-17 12:16:51'),
(48,69,18,'2025-10-19 17:00:26'),
(49,69,17,'2025-10-19 17:00:29'),
(51,69,9,'2025-10-19 17:00:41'),
(52,105,49,'2025-10-26 09:10:55'),
(53,105,48,'2025-10-26 09:10:56'),
(54,105,47,'2025-10-26 09:10:58'),
(55,105,46,'2025-10-26 09:11:00'),
(56,105,45,'2025-10-26 09:11:01'),
(57,105,44,'2025-10-26 09:11:02'),
(59,105,42,'2025-10-26 09:11:05'),
(60,105,43,'2025-10-26 09:11:06'),
(61,105,39,'2025-10-26 09:11:07'),
(63,105,35,'2025-10-26 09:11:13'),
(64,105,6,'2025-10-26 09:11:23'),
(65,105,5,'2025-10-26 09:11:26'),
(66,70,5,'2025-10-26 09:12:06'),
(69,105,51,'2025-10-26 09:33:04'),
(70,105,52,'2025-10-26 09:33:04'),
(71,105,11,'2025-10-26 09:36:06'),
(72,105,12,'2025-10-26 09:36:06'),
(73,105,17,'2025-10-26 09:36:08'),
(75,105,8,'2025-10-26 09:36:11'),
(77,109,63,'2025-10-26 11:17:57'),
(78,109,62,'2025-10-26 11:17:58'),
(79,109,60,'2025-10-26 11:17:59'),
(80,109,8,'2025-10-26 11:18:01'),
(81,109,6,'2025-10-26 11:18:02'),
(82,109,5,'2025-10-26 11:18:03'),
(84,109,11,'2025-10-26 11:18:05'),
(85,109,7,'2025-10-26 11:18:09'),
(86,72,64,'2025-10-26 14:23:40'),
(87,72,70,'2025-10-26 14:23:41'),
(88,72,33,'2025-10-26 14:23:43'),
(89,72,57,'2025-10-26 14:28:03'),
(90,72,77,'2025-10-26 15:09:12'),
(94,72,63,'2025-10-26 15:09:19'),
(95,72,60,'2025-10-26 15:09:25'),
(96,72,51,'2025-10-26 15:09:32'),
(97,72,40,'2025-10-26 15:09:46'),
(98,72,41,'2025-10-26 15:09:47'),
(99,70,74,'2025-10-26 15:11:07'),
(100,70,77,'2025-10-26 15:11:08'),
(130,72,73,'2025-10-30 05:54:44'),
(131,72,74,'2025-10-30 05:54:48'),
(132,72,61,'2025-10-30 05:54:50'),
(133,72,62,'2025-10-30 05:54:51'),
(134,72,58,'2025-10-30 05:54:53'),
(135,72,59,'2025-10-30 05:54:53'),
(136,72,56,'2025-10-30 05:54:55'),
(137,72,55,'2025-10-30 05:54:56'),
(139,72,18,'2025-10-30 05:54:59'),
(151,69,5,'2025-10-30 06:12:13'),
(155,69,7,'2025-10-30 06:12:31'),
(156,69,73,'2025-10-30 06:20:11'),
(166,69,54,'2025-10-30 06:29:21'),
(168,72,72,'2025-10-31 02:48:15'),
(169,72,71,'2025-10-31 02:48:32'),
(170,72,69,'2025-10-31 02:48:41'),
(171,72,50,'2025-10-31 02:51:14'),
(172,72,49,'2025-10-31 02:51:16'),
(173,72,48,'2025-10-31 02:51:17'),
(174,72,47,'2025-10-31 02:53:08'),
(175,72,46,'2025-10-31 02:53:09'),
(176,72,45,'2025-10-31 02:53:34'),
(177,72,44,'2025-10-31 02:53:36'),
(178,72,43,'2025-10-31 02:53:41'),
(179,72,42,'2025-10-31 02:53:42'),
(180,72,39,'2025-10-31 02:53:44'),
(181,72,38,'2025-10-31 02:53:45'),
(182,72,37,'2025-10-31 02:54:07'),
(183,72,36,'2025-10-31 02:54:08'),
(184,72,35,'2025-10-31 02:54:09'),
(185,72,34,'2025-10-31 02:54:10'),
(186,72,32,'2025-10-31 02:54:13'),
(187,72,31,'2025-10-31 02:54:14'),
(190,107,91,'2025-11-01 09:28:48'),
(191,105,91,'2025-11-01 15:16:26'),
(192,105,77,'2025-11-01 15:16:26'),
(193,105,74,'2025-11-01 15:16:30'),
(194,70,91,'2025-11-04 11:03:09'),
(195,70,94,'2025-11-04 11:12:52'),
(196,70,53,'2025-11-04 11:54:04'),
(197,70,54,'2025-11-04 11:54:05'),
(198,70,29,'2025-11-04 11:54:09'),
(199,70,30,'2025-11-04 11:54:11'),
(200,69,94,'2025-11-09 06:10:22'),
(201,69,93,'2025-11-09 06:10:23'),
(204,69,92,'2025-11-13 03:38:52');
/*!40000 ALTER TABLE `likes` ENABLE KEYS */;
commit;

--
-- Table structure for table `livestream_viewers`
--

DROP TABLE IF EXISTS `livestream_viewers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `livestream_viewers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `livestream_id` int(11) NOT NULL,
  `user_ip` varchar(50) DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`livestream_id`,`user_ip`)
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `livestream_viewers`
--

/*!40000 ALTER TABLE `livestream_viewers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `livestream_viewers` VALUES
(98,21,'120.29.66.155','2025-10-17 08:06:07'),
(162,28,'103.206.80.67','2025-10-19 11:33:45'),
(172,30,'175.176.24.143','2025-10-22 05:17:37'),
(182,33,'175.176.28.5','2025-10-27 05:39:12'),
(211,34,'124.104.137.128','2025-11-13 03:36:03'),
(212,35,'175.176.27.210','2025-11-13 04:30:52');
/*!40000 ALTER TABLE `livestream_viewers` ENABLE KEYS */;
commit;

--
-- Table structure for table `livestreams`
--

DROP TABLE IF EXISTS `livestreams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `livestreams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `youtube_link` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `viewer_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `total_views` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_livestreams_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `livestreams`
--

/*!40000 ALTER TABLE `livestreams` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `livestreams` VALUES
(3,70,'TastyHub','Baking Cakes','nice','https://www.youtube.com/embed/dury1DNaLI0?si=KY7npw24KlytNyYD\" title=',0,0,'2025-10-16 09:36:10','2025-10-16 10:58:15',22),
(4,69,'wowchef','No Recipe Cooking Challenge: TWINS Battle','','https://www.youtube.com/embed/wkCotPG-WrM?si=gZesT6AL1hbMucLN\" title=',0,0,'2025-10-16 09:40:33','2025-10-17 05:16:48',2),
(5,71,'yumyummy','s','','https://www.youtube.com/embed/Pe1KrkiBDzE?si=RiJjMHuWUKGAQ2v3',0,0,'2025-10-16 09:42:05','2025-10-16 09:42:13',0),
(6,71,'yumyummy','Strawberry Chocolate Cake ðŸ“',NULL,'https://www.youtube.com/embed/Pe1KrkiBDzE?si=RiJjMHuWUKGAQ2v3\" title=',1,0,'2025-10-16 09:42:33',NULL,4),
(23,69,'wowchef','Cooking Livestream with Chef HyRool!','thanks','https://www.youtube.com/embed/PjdT-Tco1J8?si=Fs4dDpUf85B6gUrq\" title=',0,0,'2025-10-17 08:41:40','2025-10-17 08:42:51',2),
(27,70,'TastyHub','We Are Making Tacos!! - Cooking Live Stream','','https://www.youtube.com/embed/1-g-92O9oUE?si=u9akwiArBg5_tE_8\" title=',0,0,'2025-10-17 11:39:23','2025-10-18 07:42:31',2),
(29,105,'masterchef','Seafood Videos','Thanks for Watching!','https://www.youtube.com/embed/Ssh7WZO21_M?si=OBPdscFGjx1tjZ4L\" title=',0,0,'2025-10-19 15:19:57','2025-11-01 14:18:04',5),
(33,72,'chailes','watch live!','','https://www.youtube.com/embed/aeFwlh3Gbl8?si=vBYqJG3FO9Ugjmv8\" title=',0,1,'2025-10-26 08:40:22','2025-11-03 08:17:49',3),
(34,72,'FoodAlchemy','Watch Live',NULL,'https://www.youtube.com/embed/uM1uNAhFNG0?si=e-028D2uuATU6zYv\" title=',1,1,'2025-11-03 08:20:41',NULL,3),
(35,70,'TastyHub','mukbang seafood',NULL,'https://www.youtube.com/embed/iq00cKOHV1M?si=IJqy7EYT_VBR8XG3\" title=',1,1,'2025-11-12 15:05:13',NULL,2);
/*!40000 ALTER TABLE `livestreams` ENABLE KEYS */;
commit;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `reply_to_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `messages` VALUES
(1,0,'teyia','suggest kayo luto sa manok','2025-11-01 10:59:50',NULL),
(2,0,'TastyHub','chicken pasta','2025-11-01 11:00:43',NULL),
(3,0,'yumyummy','sabawan mo','2025-11-01 11:02:16',NULL),
(4,0,'teyia','nyek','2025-11-01 11:02:24',NULL),
(5,0,'teyia','anong sabaw','2025-11-01 11:02:36',3),
(6,0,'yumyummy','pininyahan','2025-11-01 11:03:43',NULL),
(7,0,'teyia','sge tnx','2025-11-01 11:04:51',NULL),
(9,0,'teyia','tnx','2025-11-01 11:35:16',2),
(10,0,'wowchef','hellooooo','2025-11-01 17:07:42',NULL),
(11,0,'wowchef','wassup','2025-11-03 06:45:00',NULL),
(12,0,'TastyHub','hello','2025-11-04 09:42:53',NULL),
(13,0,'TastyHub','ano masarap lutuin','2025-11-04 11:58:47',NULL),
(14,0,'FoodAlchemy','Tara guys midnight snacks','2025-11-04 15:43:33',NULL),
(15,0,'TastyHub','hellooo','2025-11-09 10:59:30',NULL),
(16,0,'TastyHub','hi sainyo','2025-11-12 14:59:59',NULL),
(17,0,'FoodAlchemy','Hello! tasty hub','2025-11-12 15:00:30',NULL),
(18,0,'FoodAlchemy','May marereco ka ba now for late night snackss😋','2025-11-12 15:01:07',NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
commit;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `notifications` VALUES
(1,71,'You have received your 1st warning due to a report. Please review our community guidelines.','2025-10-24 12:50:09',0),
(2,69,'📢 Thank you for your report regarding yumyummy. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-10-24 12:50:09',1),
(3,71,'You have received your 2nd warning. Continued violations may result in suspension.','2025-10-24 12:50:23',0),
(4,69,'📢 Thank you for your report regarding yumyummy. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-10-24 12:50:23',1),
(5,71,'Your account has been suspended for 7 days due to repeated violations.','2025-10-24 12:51:14',0),
(6,69,'📢 Thank you for your report regarding yumyummy. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-10-24 12:51:14',1),
(7,69,'You have received your 1st warning due to a report. Please review our community guidelines.','2025-10-26 15:21:57',1),
(8,72,'📢 Thank you for your report regarding wowchef. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-10-26 15:21:57',1),
(9,72,'📢 Thank you for your report regarding wowchef. After review, we have Dismissed your report. We found that this user’s actions did not violate our community guidelines.','2025-10-31 12:26:39',1),
(10,71,'Your suspension period has ended. Your account is now active again.','2025-11-01 09:03:05',0),
(11,69,'📢 Thank you for your report regarding yumyummy. After review, we have Dismissed your report. We found that this user’s actions did not violate our community guidelines.','2025-11-12 14:33:31',1),
(12,71,'Your account has been suspended for 30 days due to repeated violations.','2025-11-12 14:37:33',0),
(13,69,'📢 Thank you for your report regarding yumyummy. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-11-12 14:37:33',1),
(14,107,'You have received your 1st warning due to a report. Please review our community guidelines.','2025-11-12 14:44:51',1),
(15,69,'📢 Thank you for your report regarding teyia. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-11-12 14:44:51',1),
(16,107,'You have received your 2nd warning. Continued violations may result in suspension.','2025-11-12 14:48:43',0),
(17,72,'📢 Thank you for your report regarding teyia. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-11-12 14:48:43',0),
(18,107,'Your account has been suspended for 7 days due to repeated violations.','2025-11-12 14:50:56',0),
(19,69,'📢 Thank you for your report regarding teyia. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-11-12 14:50:56',1),
(20,72,'📢 Thank you for your report regarding teyia. After review, we have Dismissed your report. We found that this user’s actions did not violate our community guidelines.','2025-11-12 14:55:39',0),
(21,107,'Your account has been suspended for 30 days due to repeated violations.','2025-11-12 14:56:34',0),
(22,72,'📢 Thank you for your report regarding teyia. After review, we have Resolved your report. We found that this user’s actions violated our community guidelines.','2025-11-12 14:56:34',0);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
commit;

--
-- Table structure for table `nutritional_info`
--

DROP TABLE IF EXISTS `nutritional_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nutritional_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `calories` int(11) NOT NULL,
  `fat` int(11) NOT NULL,
  `protein` int(11) NOT NULL,
  `carbohydrates` int(11) NOT NULL,
  `fiber` int(11) NOT NULL,
  `sugar` int(11) NOT NULL,
  `cholesterol` int(11) NOT NULL,
  `sodium` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutritional_info`
--

/*!40000 ALTER TABLE `nutritional_info` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `nutritional_info` VALUES
(1,1,520,18,35,56,4,14,65,480),
(2,2,520,22,18,60,4,15,90,780),
(3,3,290,8,6,48,5,14,0,120),
(4,4,250,15,4,25,1,1,45,130),
(5,5,480,18,22,52,3,4,210,870),
(6,6,160,9,2,16,0,12,0,35),
(7,7,280,16,11,22,2,6,40,480),
(8,8,340,22,20,18,1,4,95,420),
(9,9,90,0,1,22,1,18,0,15),
(10,10,120,8,4,10,3,4,15,260),
(11,11,320,18,28,10,0,0,0,0),
(12,12,210,8,7,28,0,0,0,0),
(13,13,250,0,4,0,0,0,0,0),
(14,14,210,8,7,28,0,0,0,0),
(15,15,210,8,7,28,0,0,0,0),
(16,16,5,0,5,0,0,0,0,0),
(17,17,320,17,10,32,0,5,50,780),
(18,18,151,7,2,18,1,2,0,300),
(19,19,1,1,1,1,1,1,1,1),
(20,20,1,1,1,1,1,1,1,1),
(21,21,1,1,1,1,1,0,1,1),
(22,22,1,1,1,1,1,0,1,1),
(23,23,1,1,1,1,1,1,0,1),
(24,24,1,1,1,1,1,1,1,1),
(25,25,1,1,1,1,1,1,1,1),
(26,26,33,0,255,0,0,0,0,0),
(27,27,1,1,1,1,1,1,1,1),
(28,28,1,1,1,1,1,1,1,1),
(29,29,220,15,4,18,8,6,0,20),
(30,30,320,14,38,8,0,0,0,0),
(31,31,250,5,7,45,6,0,0,0),
(32,32,420,25,28,20,1,10,95,680),
(33,33,420,14,28,45,2,3,180,720),
(34,34,480,16,22,58,2,3,40,620),
(35,35,510,18,30,45,1,8,90,700),
(36,36,350,10,12,48,3,2,60,500),
(37,37,290,12,4,38,1,25,30,120),
(38,38,220,9,5,30,3,5,15,150),
(39,39,280,18,10,22,1,1,35,420),
(40,40,330,14,6,45,3,1,10,420),
(41,41,400,22,25,25,2,2,80,650),
(42,42,250,12,15,20,1,2,30,400),
(43,43,320,14,35,8,1,2,95,520),
(44,44,290,12,8,32,2,4,25,420),
(45,45,120,5,3,15,1,4,0,150),
(46,46,180,9,2,22,3,8,10,180),
(47,47,350,14,8,48,2,20,25,200),
(48,48,430,22,27,28,2,2,180,540),
(49,49,320,12,7,45,1,15,65,190),
(50,50,45,0,0,12,0,10,0,8),
(51,51,90,3,2,14,0,12,10,40),
(52,52,160,10,1,16,0,14,0,30),
(53,53,240,8,6,36,1,30,15,90),
(54,54,210,9,8,28,2,18,8,75),
(55,55,95,0,1,23,1,19,0,5),
(56,56,80,5,3,7,2,1,0,180),
(57,57,120,6,4,12,3,4,0,310),
(58,58,190,13,7,9,2,3,0,300),
(59,59,160,8,5,18,0,4,5,400),
(60,60,410,21,14,40,2,3,0,380),
(61,61,270,17,10,20,1,2,0,310),
(62,62,410,18,19,45,0,9,420,0),
(63,63,200,9,5,25,0,10,0,210),
(64,64,280,11,32,10,0,7,0,180),
(65,65,250,8,7,38,4,0,0,220),
(66,66,250,8,7,38,4,0,0,220),
(67,67,210,13,8,16,5,0,0,120),
(68,68,180,5,2,32,5,0,0,140),
(69,69,90,3,7,7,0,0,0,260),
(70,70,220,14,22,2,0,0,0,200),
(71,71,190,12,11,8,3,0,0,220),
(72,72,180,3,12,28,10,0,0,90),
(73,73,110,4,15,3,1,0,0,150),
(74,74,180,8,7,22,6,0,0,210),
(75,75,1,1,1,1,1,1,1,1),
(76,76,220,14,9,25,0,0,0,250),
(77,77,280,14,6,30,0,20,0,0),
(78,78,1,1,1,1,1,1,1,1),
(79,79,1,1,1,0,1,1,1,1),
(80,80,11,1,1,1,1,1,0,1),
(81,81,11,1,1,1,1,1,0,1),
(82,82,11,1,1,1,1,1,0,1),
(83,83,10,1,0,1,0,0,0,0),
(84,84,10,1,0,1,0,0,0,0),
(85,85,10,1,0,1,0,0,0,0),
(86,86,10,1,0,1,0,0,0,0),
(87,87,10,1,0,1,0,0,0,0),
(88,88,10,1,0,1,0,0,0,0),
(89,89,1,1,1,1,11,1,1,1),
(90,90,10,1,0,1,0,0,0,0),
(91,91,480,22,25,45,2,2,0,0),
(92,92,480,18,28,55,4,10,0,450),
(93,93,280,10,20,28,3,8,0,350),
(94,94,210,14,6,20,6,0,0,350),
(95,95,100,10,10,30,5,15,35,15);
/*!40000 ALTER TABLE `nutritional_info` ENABLE KEYS */;
commit;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `password_resets` VALUES
(10,'chailesreyes04@gmail.com','4a2ef39f2d5019fc388e3a9db36831b57bd3e0a1aac97fac385dffed36306b65','2025-10-19 19:31:58');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
commit;

--
-- Table structure for table `recipe`
--

DROP TABLE IF EXISTS `recipe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipe_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `recipe_description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `difficulty` varchar(50) DEFAULT NULL,
  `preparation` varchar(50) DEFAULT NULL,
  `cooktime` varchar(255) DEFAULT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected','archived') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `servings` int(11) DEFAULT 1,
  `video_path` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe`
--

/*!40000 ALTER TABLE `recipe` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `recipe` VALUES
(1,69,'Mango Coconut Chicken Rice Bowl','2025-10-08 14:11:45','uploads/image/68e671207d204_th (21).jpg','A tropical fusion dish combining tender coconut-infused chicken, jasmine rice, and fresh mango salsa. Perfect for summer days â€” sweet, savory, and aromatic in every bite.','Main Dish','Medium','Frying','15 to 30 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,3,'https://www.youtube.com/watch?v=EFiCAvaQCdE','mango,coconut,chicken,delicious,rice,yummy,healthy',0,NULL),
(2,69,'Spicy Mango Ramen Bowl','2025-10-08 14:45:12','uploads/image/68e678f80f6fe_639613db2721b3079d7fa650_Spicy_Mango_Rice_Bowl_6.jpg','A fusion of Japanese ramen and tropical Filipino flavors â€” this spicy mango ramen combines tangy sweetness, chili heat, and creamy coconut broth for a refreshing twist.','Main Dish','Medium','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','Mango,Ramen,Spicy,FilipinoTwist',0,NULL),
(3,69,'Tropical Quinoa Salad with Calamansi Dressing','2025-10-08 14:52:50','uploads/image/68e67ac24f4f6_th.jpg','A light, refreshing salad packed with tropical fruits and a tangy calamansi vinaigrette â€” perfect for healthy eaters.','Salads & Sides','Easy','Chilling','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'https://www.youtube.com/watch?v=ov3dU_OLP3E','Vegan,Salad,Calamansi,Tropical',0,NULL),
(4,69,'Ube Cheesecake Bites','2025-10-08 14:58:18','uploads/image/68e67c0aed06e_Screenshot_8-10-2025_225414_www.haohungry.co.jpeg','Mini no-bake cheesecakes made with creamy ube halaya â€” the perfect Filipino dessert fusion.','Desserts & Sweets','Medium','Chilling','1 to 3 hours','Mid Budget (₱251 - ₱500)','approved',NULL,6,'https://www.youtube.com/watch?v=xDIQBWlYQrg&t=1s','Dessert,Ube,Cheesecake,FilipinoSweet',0,NULL),
(5,70,'Adobo Breakfast Burrito','2025-10-08 15:15:02','uploads/image/68e67ff6bc0a0_adoboburritorecipe-1.jpg','A Filipino-Mexican fusion dish â€” classic adobo flakes, garlic rice, and egg wrapped in a warm tortilla for an on-the-go breakfast.','Brunch','Easy','Frying','15 to 30 minutes','Low Budget (â‚±101 - â‚±250)','approved',NULL,2,'https://www.youtube.com/watch?v=cpX-W4kUZH4','Breakfast,Adobo,QuickMeal,FilipinoTwist',0,NULL),
(6,70,'Coconut-Pandan Iced Latte','2025-10-08 15:29:55','uploads/image/68e6837351c46_Screenshot_8-10-2025_232613_tse1.mm.bing.net.jpeg','An aromatic and creamy coffee drink that blends coconut milk and pandan syrup â€” a tropical twist on your usual iced latte.','Desserts & Sweets','Easy','Pouring Over Ice','5 to 15 minutes','Ultra Low Budget (â‚±0 - â‚±100)','approved',NULL,1,'https://www.youtube.com/watch?v=YyQaUfL2dOw&t=6s','Drink,Coffee,Pandan,Coconut,IcedLatte',0,NULL),
(7,71,'Creamy Garlic Parmesan Soup','2025-10-08 16:24:33','uploads/image/68e6904136c36_R.jpg','A luxurious soup made with roasted garlic, creamy milk, and Parmesan cheese. Perfect for cozy days and pairs beautifully with crusty bread.','Soups & Stews','Easy','Simmering','15 to 30 minutes','Low Budget (â‚±101 - â‚±250)','approved',NULL,3,'https://www.youtube.com/watch?v=_QIORDSB-ro&t=32s','soup,creamy,parmesan',0,NULL),
(8,71,'Crispy Chicken Katsu Bites','2025-10-08 16:29:59','uploads/image/68e6918781c16_number00004_58940_Crispy_Chicken_Katsu_Amateur_photo_from_Reddi_41b1ffe9-1ce1-4852-aa6f-32d9d247d4b9.png','A bite-sized version of Japanese chicken katsu, these crunchy golden nuggets are perfect for on-the-go snacking â€” served with a creamy mayo dipping sauce.','Appetizers & Snacks','Medium','Frying','15 to 30 minutes','Low Budget (â‚±101 - â‚±250)','approved',NULL,3,'https://www.youtube.com/watch?v=dWFLuvDIKQc','Chicken,Katsu',0,NULL),
(9,71,'Strawberry Lychee Sparkling Refresher','2025-10-08 16:37:48','uploads/image/68e6935cdd681_strawberry-refresher-735x490.jpg','A refreshing tropical blend of fresh strawberries, lychee syrup, and sparkling soda â€” perfectly sweet, fizzy, and hydrating. This cafÃ©-style drink is ideal for hot days and easy to make at home!','Drinks & Beverages','Easy','Juicing','5 to 15 minutes','Low Budget (â‚±101 - â‚±250)','approved',NULL,2,'https://www.youtube.com/watch?v=IVJwri_9kpI','sweet,refresh',0,NULL),
(10,69,'Stir-Fried Garlic Butter Vegetables','2025-10-08 16:43:19','uploads/image/68e694a788d5a_Screenshot_9-10-2025_03922_www.bing.com.jpeg','A colorful medley of vegetables stir-fried in a rich garlic butter sauce. This quick and healthy dish pairs perfectly with rice or grilled meats â€” crisp, savory, and full of flavor.','Vegetables','Easy','Stirring','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'https://www.youtube.com/watch?v=6sBjCeKy_4I','Vegetable,Healthy',0,NULL),
(11,70,'Honey-Glazed Roast Chicken','2025-10-08 16:48:23','uploads/image/68e695d71e9af_sb-Whole-Roasted-Chicken-with-Potatoes-&-Carrots-Landscape-Image.jpg','A golden, juicy roast chicken glazed with honey, soy sauce, and garlic â€” perfect for family gatherings and holidays.','Occasional','Medium','Roasting','1 to 3 hours','Mid Budget (â‚±251 - â‚±500)','approved',NULL,5,'','Chicken',0,NULL),
(12,70,'Quinoa Vegetable Bowl','2025-10-08 16:51:40','uploads/image/68e6969cc64c9_instagram-In-Stream_Square___quinoa-veggie-bowl-3-1024x1024.jpg','A nutrient-packed bowl of quinoa, roasted veggies, and olive oil dressing are ideal for clean eating or weight management.','Healthy & Special Diets','Easy','Boiling','15 to 30 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,2,'','Healthy,Diet,Quinoa',0,NULL),
(17,72,'Creamy Potato Soup Recipe','2025-10-17 12:10:09','uploads/image/68f232217eff2_Screenshot 2025-10-17 200331.png','A comforting classic that soothes the soul on any chilly day. This Creamy Potato Soup blends tender potatoes, sautéed onions, and garlic in a velvety, buttery broth enriched with milk and cream. Each spoonful delivers smooth, hearty warmth — finished with melted cheddar, crispy bacon, and a sprinkle of fresh chives. Perfectly balanced between rich and savory, it’s the ultimate bowl of comfort for cozy nights or comforting family meals.','Soups & Stews','Medium','Blending','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'https://youtu.be/jHq0RXIjAfk?si=X4uyahLzKtiKTftE','potato,creamy,soup',0,NULL),
(18,72,'Garlic Bread or Cheesy Rolls','2025-10-17 12:30:38','uploads/image/68f236ee11628_Screenshot 2025-10-17 203013.png','Golden, buttery, and irresistibly aromatic — these Garlic Bread and Cheesy Rolls are the ultimate comfort bake. Each piece is brushed with rich garlic butter, stuffed or topped with melted mozzarella, and baked until perfectly crisp on the outside and soft on the inside. Whether served alongside pasta, soup, or enjoyed on their own, they’re a warm, cheesy indulgence that everyone loves.','Appetizers & Snacks','Hard','Baking','30 to 60 minutes','Low Budget (₱101 - ₱250)','approved',NULL,8,'','garlic,bread,cheesy,rolls',0,NULL),
(28,69,'Honey-Orange Glazed Salmon with Garlic Butter Rice','2025-10-19 14:29:13','uploads/image/68f4f5b96dc8a_RoukquR.jpg','d. Clear inputs when switching between link/upload\r\nYou already call clearVidoInputs() on radio change — that’s correct.','Main Dish','Medium','Steaming','5 to 15 minutes','High Budget (₱501 - ₱1,000)','rejected','Incomplete Information',1,'https://www.youtube.com/watch?v=FnG6tHuzn1M&list=RDu-JbgHBIegU&index=2','',0,NULL),
(29,105,'Golden Coconut Chia Pudding','2025-10-19 15:35:24','uploads/image/68f5053ca9987_5482e7039c663d4a433333b08b0d57ba.jpg','A creamy and vibrant chia pudding infused with golden turmeric and rich coconut milk. Naturally sweetened and packed with fiber, this easy no-cook dessert or breakfast is perfect for a healthy start to your day.','Desserts & Sweets','Easy','Chilling','1 to 3 hours','Low Budget (₱101 - ₱250)','approved',NULL,1,'https://youtu.be/42dFzIWohbs?si=6a61Saojr7YtenAO','pudding',0,NULL),
(30,105,'Honey Garlic Glazed Salmon','2025-10-19 15:45:34','uploads/image/68f5079e2ee12_Honey-Glazed-Salmon-Recipe-2-768x1146.jpg','Succulent salmon fillets glazed with a sweet and savory honey garlic sauce. Perfect for a quick weeknight dinner with minimal effort.','Main Dish','Medium','Frying','15 to 30 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,2,'https://youtu.be/P4xuyEq37nE?si=BWZsQWny8CJycA0O','salmon',0,NULL),
(31,70,'Chocolate Banana Overnight Oats','2025-10-19 15:54:34','uploads/image/68f509ba40f7f_th (1).jpg','Creamy overnight oats infused with cocoa powder, topped with banana slices and a touch of honey. Quick, healthy, and ready in the morning!','Brunch','Easy','Raw','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,1,'','banana',0,NULL),
(32,70,'Spicy Korean Chicken Wings','2025-10-23 14:04:30','uploads/image/68fa35eed4ada_Spicy Korean Chicken Wings.jpg','Crispy chicken wings coated in a rich, sticky, and fiery Korean-style sauce made from gochujang (Korean chili paste), honey, and garlic. This dish delivers the perfect balance of sweetness, spiciness, and umami — ideal for those who love bold, flavorful bites. Whether served as an appetizer or a main course, it’s guaranteed to be a crowd favorite!','Main Dish','Easy','Sauteing','30 to 60 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,4,'https://youtu.be/F2TPBLuUf74','Spicy,Chicken,Korean-Style,Wings',0,NULL),
(33,70,'Garlic Butter Shrimp Rice Bowl','2025-10-24 14:40:31','uploads/image/68fb8fdf67092_th (3).jpg','A rich and flavorful salmon dish in a creamy garlic butter sauce with spinach and sun-dried tomatoes.','Main Dish','Easy','Frying','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'','Shrimp',0,NULL),
(34,70,'Creamy Tuna Pasta','2025-10-24 14:48:01','uploads/image/68fb91a11056f_th (5).jpg','A quick creamy pasta tossed with tuna, garlic, and more cheese.','Main Dish','Easy','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'https://www.youtube.com/watch?v=a84jy_S7at0','Pasta,Tuna',0,NULL),
(35,70,'Sweet and Spicy Chicken','2025-10-24 14:53:32','uploads/image/68fb92ec8ee3e_th (6).jpg','Crispy chicken glazed in sweet chili sauce. You can try it at home!','Main Dish','Medium','Frying','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','chicken,spicy,sweet',0,NULL),
(36,70,'Vegetable Fried Rice','2025-10-24 14:58:55','uploads/image/68fb942f47952_easy-fried-rice-1-2-1200x1799.jpg','Try this savory rice stir-fried with eggs and mixed veggies.','Vegetables','Easy','Frying','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'','vegetable,easy',0,NULL),
(37,70,'Mango Graham Float','2025-10-24 15:02:47','uploads/image/68fb951786fe3_th (7).jpg','A chilled and sweet layered dessert of graham, cream, and mango.','Desserts & Sweets','Easy','Raw','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','graham,mango,sweet',0,NULL),
(38,70,'Garlic Butter Corn','2025-10-24 15:09:57','uploads/image/68fb96c53ea32_honey-butter-skillet-corn-1-9-640x800.jpg','Juicy corn tossed in garlic butter sauce. Serve with sweetness.','Salads & Sides','Easy','Sauteing','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','corn,butter',0,NULL),
(39,70,'Crispy Cheese Sticks','2025-10-24 15:15:14','uploads/image/68fb980238f1b_crispy-cheese-sticks-3-650x867.jpg','Golden, crunchy cheese sticks made with melty cheese wrapped in spring roll wrappers — a simple and addictive Filipino snack best served with ketchup or mayo dip.','Appetizers & Snacks','Easy','Frying','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,4,'','cheesy',0,NULL),
(40,70,'Garlic Parmesan Fries','2025-10-24 15:18:20','uploads/image/68fb98bc21401_th (8).jpg','Crispy fries coated in garlic butter and parmesan cheese for a tasty twist on the classic snack. Perfect as a side or movie-time munch.','Appetizers & Snacks','Easy','Frying','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,3,'','parmesan,fries',0,NULL),
(41,70,'Mini Lumpiang Shanghai','2025-10-24 15:24:13','uploads/image/68fb9a1d4028d_Vegan-Lumpiang-Shanghai-Filipino-Spring-Rolls-Sweet-Simple-Vegan-4.jpg','A Filipino favorite bite-sized spring rolls filled with ground pork, carrots, and seasoning, fried until crisp and golden. Great for parties or meriendas.','Appetizers & Snacks','Easy','Frying','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,5,'','shanghai,merienda',0,NULL),
(42,70,'Tuna Sandwich Pockets','2025-10-24 15:29:42','uploads/image/68fb9b6645bc7_shutterstock_2014160.jpg','Soft sandwich pockets filled with creamy tuna spread and lightly toasted. Ideal for snacks or light breakfast.','Appetizers & Snacks','Easy','Grilling','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','tuna',0,NULL),
(43,70,'Chicken Tinola','2025-10-24 15:46:44','uploads/image/68fb9f64045bc_Chicke Tinola.jpg','A comforting Filipino soup made with chicken, green papaya, and chili leaves simmered in a ginger broth. Perfect for rainy days.','Soups & Stews','Medium','Simmering','30 to 60 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','chicken,filipino',0,NULL),
(44,70,'Creamy Mushroom Soup','2025-10-24 15:52:50','uploads/image/68fba0d2be100_how-to-make-mushroom-soup-1.jpg','A smooth, velvety soup made with fresh mushrooms, milk, and butter — simple, rich, and comforting.','Soups & Stews','Easy','Simmering','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'','soup,mushroom',0,NULL),
(45,70,'Cucumber Tomato Salad','2025-10-24 15:57:36','uploads/image/68fba1f038539_th (9).jpg','A refreshing side salad made with crisp cucumbers, juicy tomatoes, and a light vinaigrette — perfect for grilled dishes or summer meals.','Salads & Sides','Easy','Stirring','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,3,'','',0,NULL),
(46,70,'Coleslaw','2025-10-24 16:00:24','uploads/image/68fba298868fd_th (10).jpg','Classic creamy cabbage salad made with shredded cabbage, carrots, and a tangy-sweet dressing.','Salads & Sides','Easy','Stirring','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,4,'','creamy',0,NULL),
(47,70,'Macaroni Salad','2025-10-24 16:05:42','uploads/image/68fba3d6eb021_th (11).jpg','A Filipino-style sweet and creamy macaroni salad with mayonnaise, fruit cocktail, and cheese — perfect for holidays or merienda.','Salads & Sides','Medium','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,5,'','filipino',0,NULL),
(48,70,'Adobo Breakfast Quesadilla with Garlic Aioli','2025-10-26 09:01:22','uploads/image/68fde362215ca_Screenshot_26-10-2025_165627_i1.wp.com.jpeg','A Filipino-Mexican fusion brunch dish featuring shredded adobo, scrambled eggs, and melted cheese wrapped in a crispy tortilla — served with creamy garlic aioli for dipping. Perfect for lazy weekend mornings!','Brunch','Medium','Frying','30 to 60 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,4,'https://youtu.be/j2z1ABDaaxg?si=wL4_AdiVZ5uJvLdy','breakfast,adobo',0,NULL),
(49,70,'Ube Cream Cheese Pancakes','2025-10-26 09:09:30','uploads/image/68fde54a2280f_th (13).jpg','Fluffy ube pancakes filled with a gooey cream cheese center — the perfect sweet treat for a colorful brunch!','Brunch','Easy','Frying','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'https://youtu.be/WDdAsFEM82c?si=gK_jbdRlqBtdj4Kx','pancakes,ube',0,NULL),
(50,70,'Calamansi Honey Sparkler','2025-10-26 09:18:15','uploads/image/68fde7847ec9e_Screenshot_26-10-2025_171845_www.bing.com.jpeg','A refreshing sparkling drink made with tangy calamansi juice, honey, and soda water — a sweet-tart pick-me-up that’s perfect for brunch or summer afternoons.','Drinks & Beverages','Easy','Juicing','1 to 5 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'https://youtu.be/SdanmdctCKI?si=YMcqZ0kWKPXqrky1','calamansi,drink',0,NULL),
(51,69,'Iced Barako Latte','2025-10-26 09:23:37','uploads/image/69022117ec4a3_cropped_image.jpg','A bold Filipino-style iced coffee made with Batangas barako beans, milk, and a touch of muscovado sugar for earthy sweetness.','Drinks & Beverages','Easy','Brewing','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','coffee,barako,latte,icecofee',0,NULL),
(52,69,'Pandan Coconut Milk Cooler','2025-10-26 09:27:35','uploads/image/68fde9876cbfb_Screenshot_26-10-2025_172520_www.bing.com.jpeg','A fragrant and creamy drink made with pandan leaves and fresh coconut milk, sweetened lightly for a refreshing tropical flavor.','Drinks & Beverages','Easy','Boiling','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','pandan,drinks',0,NULL),
(53,105,'Ube Milk Tea','2025-10-26 09:35:46','uploads/image/68fdeb727aa36_Screenshot_26-10-2025_173239_www.bing.com.jpeg','A creamy, purple-hued milk tea made from real ube and black tea — combining Filipino flavor with the milk tea craze!','Drinks & Beverages','Medium','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','ube,miktea',0,NULL),
(54,105,'Banana Peanut Butter Smoothie','2025-10-26 09:41:01','uploads/image/68fdecadcd3c6_Screenshot_26-10-2025_17397_www.bing.com.jpeg','A protein-packed smoothie made with ripe bananas, peanut butter, and milk — perfect for an energizing brunch or post-workout drink!','Drinks & Beverages','Easy','Blending','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','banana,drinks',0,NULL),
(55,72,'Pineapple Basil Refresher','2025-10-26 09:45:01','uploads/image/68fded9dc78e3_Screenshot_26-10-2025_174253_www.bing.com.jpeg','A zesty tropical drink that blends fresh pineapple with aromatic basil and lemon for a surprisingly refreshing and fragrant flavor.','Drinks & Beverages','Easy','Blending','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'','drinks,pineapple',0,NULL),
(56,72,'Stir-Fried Kangkong with Garlic','2025-10-26 09:49:22','uploads/image/68fdeea220d9b_Screenshot_26-10-2025_174638_www.bing.com.jpeg','A quick and flavorful Filipino veggie dish made with water spinach, garlic, and soy sauce — perfect for a healthy side dish.','Vegetables','Easy','Frying','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','vegetable,fried,kangkong',0,NULL),
(57,72,'Mixed Vegetable Stir-Fry','2025-10-26 09:52:07','uploads/image/68fdef474f990_Screenshot_26-10-2025_174949_www.bing.com.jpeg','A colorful medley of fresh vegetables sautéed with oyster sauce for a delicious and nutritious main or side dish.','Vegetables','Medium','Frying','15 to 30 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,3,'','',0,NULL),
(58,72,'Tortang Talong (Eggplant Omelette)','2025-10-26 10:39:03','uploads/image/68fdfa476f5ae_th (14).jpg','A Filipino classic made by grilling eggplants and coating them in egg before frying — simple yet satisfying!','Vegetables','Medium','Frying','15 to 30 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','vegetable,torta',0,NULL),
(59,72,'Pinakbet','2025-10-26 10:45:37','uploads/image/68fdfbd1b6bfe_Screenshot_26-10-2025_184131_www.bing.com.jpeg','A hearty Filipino vegetable stew made with bitter melon, squash, eggplant, and shrimp paste — bursting with umami and local flavor.','Vegetables','Hard','Stewing','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','',0,NULL),
(60,72,'Creamy Carbonara','2025-10-26 10:51:23','uploads/image/68fdfd2bc4209_Screenshot_26-10-2025_184728_www.bing.com.jpeg','A rich, creamy, and comforting pasta dish perfect for birthdays and special occasions — made with bacon, cream, and cheese that everyone will love.','Occasional','Medium','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,5,'','creamy,pasta',0,NULL),
(61,72,'Fiesta Lumpiang Shanghai','2025-10-26 10:56:04','uploads/image/68fdfe447e8f8_Screenshot_26-10-2025_185258_www.bing.com.jpeg','Crispy Filipino-style spring rolls filled with ground pork and vegetables — a must-have for any celebration or gathering.','Occasional','Easy','Frying','30 to 60 minutes','Low Budget (₱101 - ₱250)','approved',NULL,8,'','fiesta',0,NULL),
(62,72,'Baked Spaghetti Christmas Style','2025-10-26 11:06:11','uploads/image/68fe00a3bce11_th (15).jpg','A cheesy and festive spaghetti baked with layers of tomato-meat sauce and creamy cheese topping — perfect for Noche Buena.','Occasional','Hard','Baking','30 to 60 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,6,'','',0,NULL),
(63,72,'Bibingka Mini Cakes','2025-10-26 11:11:42','uploads/image/68fe01eec7e09_Screenshot_26-10-2025_19855_www.bing.com.jpeg','A soft, buttery rice cake traditionally served during Christmas — baked with salted egg and cheese for a nostalgic holiday taste.','Occasional','Medium','Baking','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,6,'','',0,NULL),
(64,109,'Grilled Chicken Salad with Honey-Lime Dressing','2025-10-26 11:27:45','uploads/image/68fe05b1ccbce_Screenshot_26-10-2025_192339_www.bing.com.jpeg','A light yet filling salad with grilled chicken, crisp vegetables, and a zesty honey-lime dressing — perfect for a healthy lunch or dinner.','Healthy & Special Diets','Medium','Grilling','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','chicken,grilled',0,NULL),
(65,109,'Brown Rice and Vegetable Stir-Fry','2025-10-26 11:37:43','uploads/image/68fe0cc73242d_vegetable-stir-fried-rice-275-768x768.jpg','A colorful mix of veggies stir-fried with brown rice — rich in fiber, vitamins, and perfect for a balanced diet.','Main Dish','Medium','Frying','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,3,'https://www.youtube.com/watch?v=pylpywaSZ6c','vegetable,brownrice',0,NULL),
(67,109,'Avocado Toast with Egg','2025-10-26 12:02:37','uploads/image/68fe0ddd30515_th (16).jpg','A nutritious breakfast toast topped with creamy avocado and a protein-rich egg — simple, quick, and energizing.','Healthy & Special Diets','Easy','Grilling','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,1,'','',0,NULL),
(68,109,'Baked Sweet Potato Fries','2025-10-26 12:11:22','uploads/image/68fe0fea7226e_Screenshot_26-10-2025_20747_www.bing.com.jpeg','A guilt-free snack alternative to French fries — baked instead of fried, and rich in vitamins and fiber.','Appetizers & Snacks','Easy','Baking','15 to 30 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,2,'','potato,baked,sweet,fries',0,NULL),
(69,109,'Miso Soup with Tofu and Seaweed','2025-10-26 12:26:49','uploads/image/68fe138956539_1582212402929.jpeg','A Japanese-inspired light soup that’s rich in probiotics and low in calories — perfect for detox or light meals.','Healthy & Special Diets','Easy','Boiling','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,3,'','tofu,seaweed,soup,vegetarian',0,NULL),
(70,109,'Keto Garlic Butter Shrimp  Description:','2025-10-26 12:34:28','uploads/image/68fe15547c3d4_Lemon-Butter-Shrimp-18.jpg','Low-carb and high-protein shrimp sautéed in garlic butter — perfect for a ketogenic diet.','Healthy & Special Diets','Medium','Sauteing','15 to 30 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'https://www.youtube.com/watch?v=nWHT1o1UqdI','shrimp,keto',0,NULL),
(71,109,'Gluten-Free Cauliflower Pizza Crust','2025-10-26 12:43:15','uploads/image/68fe1763874b8_th (17).jpg','A low-carb, gluten-free alternative to regular pizza crust, made from cauliflower — great for special diets.','Healthy & Special Diets','Hard','Baking','30 to 60 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','',0,NULL),
(72,72,'Low-Sodium Lentil Soup','2025-10-26 12:51:12','uploads/image/68fe194064344_14ebce64f140095848120d0a6e7b6308.jpg','A hearty and warming soup rich in fiber and plant-based protein, made without added salt — ideal for low-sodium diets.','Healthy & Special Diets','Medium','Boiling','30 to 60 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,4,'','soup',0,NULL),
(73,72,'Egg White Veggie Omelette (Diabetic-Friendly & Low-Calorie)','2025-10-26 13:00:54','uploads/image/68fe1b867fee0_Screenshot_26-10-2025_205853_www.bing.com.jpeg','A fluffy omelette made with only egg whites and fresh vegetables — ideal for weight management and diabetic-friendly meals.','Brunch','Easy','Frying','5 to 15 minutes','Ultra Low Budget (₱0 - ₱100)','approved',NULL,1,'','healthy',0,NULL),
(74,72,'Mediterranean Chickpea Salad','2025-10-26 13:09:20','uploads/image/68fe1d80136e0_th (18).jpg','A refreshing, protein-rich salad with chickpeas, cucumber, tomato, olives, and feta — perfect as a side or light main for healthy meals.','Salads & Sides','Easy','Raw','5 to 15 minutes','Low Budget (₱101 - ₱250)','archived',NULL,4,'https://www.youtube.com/watch?v=jWCrEAvSZ8g','healthy,salad',1,'2025-11-01 23:54:39'),
(75,70,'gahah','2025-10-26 14:30:58','uploads/image/68fe30a2e0049_R.png','Recipe Description\r\nRecipe Description\r\nRecipe Description\r\nRecipe Description','Occasional','Hard','Broiling','1 to 3 hours','Mid Budget (₱251 - ₱500)','pending',NULL,1,'','',0,NULL),
(76,69,'Creamy Garlic Spinach Pasta','2025-10-26 14:45:09','uploads/image/68fe33f5b5b52_th (19).jpg','Smooth and creamy pasta made with spinach, garlic, and parmesan — perfect for a comforting meal.','Vegetables','Medium','Boiling','15 to 30 minutes','Low Budget (₱101 - ₱250)','archived',NULL,20,'','',1,'2025-10-29 12:14:01'),
(77,69,'No-Bake Mango Cheesecake Cups','2025-10-26 15:05:43','uploads/image/69032d8fe6aa1_cropped_image.jpg','A light, creamy dessert layered with crushed grahams, cheesecake filling, and fresh mangoes. Easy to prepare and great for summer treats.','Desserts & Sweets','Easy','Chilling','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,4,'','mango',0,'2025-10-29 23:38:58'),
(91,107,'Salted Egg Yolk Pasta with Crispy Prawns','2025-11-01 09:26:33','uploads/image/6905d2495b25e_be0a6477fbca89fbb8a239744440f012.jpg','A unique fusion dish combining the rich, savory flavor of salted egg yolk with tender pasta and crispy golden prawns. This indulgent yet elegant meal is perfect for a dinner party or a special treat at home, blending Filipino and Italian flavors in a creamy, luxurious sauce.','Main Dish','Medium','Boiling','15 to 30 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,2,'','',0,NULL),
(92,113,'Golden Coconut Mango Chicken with Turmeric Rice','2025-11-04 10:56:30','uploads/image/6909dbde3e486_Mango-Chicken-and-Rice-9.jpg','A tropical, aromatic dish combining sweet mango, creamy coconut, and subtly spiced chicken served over golden turmeric rice.','Main Dish','Medium','Sauteing','15 to 30 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,3,'','mango',0,NULL),
(93,114,'Spicy Pineapple Shrimp Tacos','2025-11-04 11:02:10','uploads/image/6909dd323cd1b_cropped_image.jpg','Sweet, tangy, and mildly spicy, these tacos are a tropical twist on classic shrimp tacos—perfect for a quick weeknight dinner.','Main Dish','Easy','Sauteing','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','',0,NULL),
(94,115,'Miso Avocado Toast with Sesame','2025-11-04 11:08:28','uploads/image/6909deaccb7dd_cropped_image.jpg','Creamy avocado meets savory miso in this easy, nutrient-packed toast, perfect for breakfast or a quick snack with a subtle umami flavor.','Desserts & Sweets','Easy','Baking','5 to 15 minutes','Low Budget (₱101 - ₱250)','approved',NULL,2,'','avocado,toast',0,NULL),
(95,117,'Adobong Manok','2025-11-13 03:47:01','uploads/image/691554b5c9128_Qpb85Do6vwUwSfk.png','The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.','Main Dish','Medium','Sauteing','5 to 15 minutes','Mid Budget (₱251 - ₱500)','approved',NULL,2,'','ulam',0,NULL);
/*!40000 ALTER TABLE `recipe` ENABLE KEYS */;
commit;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reported_user_id` int(11) NOT NULL,
  `reporting_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `custom_reason` text DEFAULT NULL,
  `status` enum('Pending','Resolved','Dismissed') DEFAULT 'Pending',
  `proof_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reported_user_id` (`reported_user_id`),
  KEY `reporting_user_id` (`reporting_user_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporting_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `reports` VALUES
(1,71,69,'Offensive Language','','Resolved','uploads/reports/proof_68fb75fbecd560.42801400.jpeg','2025-10-24 20:50:03'),
(2,71,69,'Inappropriate Content','','Resolved','uploads/reports/proof_68fb760a409783.87963750.jpeg','2025-10-24 20:50:18'),
(3,71,69,'Harassment','','Resolved','uploads/reports/proof_68fb763b6e0244.42761345.jpeg','2025-10-24 20:51:07'),
(4,69,72,'Harassment','','Resolved','uploads/reports/proof_68fe3b6ad989e7.87691619.jpeg','2025-10-26 23:16:58'),
(5,69,72,'Harassment','','Dismissed','uploads/reports/proof_68fe3ca9d679a3.39965079.jpeg','2025-10-26 23:22:17'),
(6,107,69,'Harassment','','Resolved','uploads/reports/proof_69149901049f33.08886006.jpeg','2025-11-12 22:26:09'),
(7,71,69,'Harassment','','Dismissed','uploads/reports/proof_69149a969ab120.71560999.jpeg','2025-11-12 22:32:54'),
(8,71,69,'Harassment','','Resolved','uploads/reports/proof_69149b9b6634f8.72947012.jpeg','2025-11-12 22:37:15'),
(9,107,72,'Harassment','','Resolved','uploads/reports/proof_69149e2521fb51.10215056.png','2025-11-12 22:48:05'),
(10,107,69,'Harassment','','Resolved','uploads/reports/proof_69149ec8cd9357.16716657.jpeg','2025-11-12 22:50:48'),
(11,107,72,'Harassment','','Dismissed','uploads/reports/proof_69149fd7051bf1.01864307.png','2025-11-12 22:55:19'),
(12,107,72,'Harassment','','Resolved','uploads/reports/proof_6914a016e784f8.36548494.png','2025-11-12 22:56:22');
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
commit;

--
-- Table structure for table `reposts`
--

DROP TABLE IF EXISTS `reposts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reposts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `caption` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reposts`
--

/*!40000 ALTER TABLE `reposts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `reposts` VALUES
(1,69,8,'kagutomh','2025-10-08 16:55:26'),
(2,72,12,'Yum','2025-10-10 06:03:42'),
(5,71,11,'','2025-10-16 09:57:47'),
(6,71,3,'','2025-10-16 09:58:15'),
(15,69,12,'MUST TRY','2025-10-17 09:22:38'),
(16,70,3,'wow yummyyyyy so much','2025-10-25 12:33:59'),
(21,109,63,'matry nga','2025-10-26 11:18:59'),
(22,72,57,'try this','2025-10-26 14:28:27'),
(23,70,94,'nice food','2025-11-11 13:04:43'),
(25,69,92,'','2025-11-13 03:39:03');
/*!40000 ALTER TABLE `reposts` ENABLE KEYS */;
commit;

--
-- Table structure for table `stories`
--

DROP TABLE IF EXISTS `stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT (current_timestamp() + interval 24 hour),
  `is_active` tinyint(1) DEFAULT 1,
  `recipe_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stories`
--

/*!40000 ALTER TABLE `stories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `stories` VALUES
(2,71,'uploads/stories/story_71_1760700392_68f227e83e102.jpeg','must try!','2025-10-17 11:26:32','2025-10-18 11:26:32',1,'https://tastyhub.free.nf/recipe_details.php?id=4'),
(3,71,'uploads/stories/story_71_1760700426_68f2280a16f51.jpg','','2025-10-17 11:27:06','2025-10-18 11:27:06',1,NULL),
(4,69,'uploads/stories/story_69_1760700621_68f228cd781de.jpg','sarap','2025-10-17 11:30:21','2025-10-18 11:30:21',1,NULL),
(5,72,'uploads/stories/story_72_1760700944_68f22a105913d.jpg','my lunch','2025-10-17 11:35:44','2025-10-18 11:35:44',1,NULL),
(8,69,'uploads/stories/story_69_1760868943_68f4ba4f69c0d.jpeg','wow','2025-10-19 10:15:43','2025-10-20 10:15:43',1,NULL),
(9,105,'uploads/stories/story_105_1760887029_68f500f59b07b.jpg','heyy','2025-10-19 15:17:09','2025-10-20 15:17:09',1,NULL),
(10,69,'uploads/stories/story_69_1760887570_68f5031215b2e.jpg','','2025-10-19 15:26:10','2025-10-20 15:26:10',1,NULL),
(11,71,'uploads/stories/story_71_1760889721_68f50b79272ae.jpg','','2025-10-19 16:02:01','2025-10-20 16:02:01',1,NULL),
(12,69,'uploads/stories/story_69_1761110513_68f869f1b6f29.jpg','cooking','2025-10-22 05:21:53','2025-10-23 05:21:53',1,NULL),
(13,105,'uploads/stories/story_105_1761467743_68fddd5fb9d54.jpg','yummy food!','2025-10-26 08:35:43','2025-10-27 08:35:43',1,'https://tastyhub.site/recipe_details.php?id=46'),
(14,105,'uploads/stories/story_105_1761467799_68fddd977e921.jpeg','brownies','2025-10-26 08:36:39','2025-10-27 08:36:39',1,NULL),
(16,72,'uploads/stories/story_72_1761467921_68fdde11983be.jpg','','2025-10-26 08:38:41','2025-10-27 08:38:41',1,NULL),
(17,69,'uploads/stories/story_69_1761468239_68fddf4fb3304.jpg','','2025-10-26 08:43:59','2025-10-27 08:43:59',1,NULL),
(18,70,'uploads/stories/story_70_1761489392_68fe31f021382.jpg','','2025-10-26 14:36:32','2025-10-27 14:36:32',1,NULL),
(19,69,'uploads/stories/story_69_1761838536_690385c813bfc.jpg','','2025-10-30 15:35:36','2025-10-31 15:35:36',1,NULL),
(20,69,'uploads/stories/story_69_1761986285_6905c6ed8532e.jpeg','','2025-11-01 08:38:05','2025-11-02 08:38:05',1,NULL),
(21,72,'uploads/stories/story_72_1762158128_690866302e344.jpg','Korean Spicy Chicken Wings','2025-11-03 08:22:08','2025-11-04 08:22:08',1,NULL),
(22,70,'uploads/stories/story_70_1762959738_6914a17ad69ae.jpg','yummy','2025-11-12 15:02:18','2025-11-13 15:02:18',1,NULL),
(23,72,'uploads/stories/story_72_1762959914_6914a22a3549e.png','My breakfast','2025-11-12 15:05:14','2025-11-13 15:05:14',1,'https://tastyhub.site/recipe_details.php?id=73');
/*!40000 ALTER TABLE `stories` ENABLE KEYS */;
commit;

--
-- Table structure for table `story_views`
--

DROP TABLE IF EXISTS `story_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `story_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`story_id`,`user_id`),
  KEY `idx_story_id` (`story_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_views`
--

/*!40000 ALTER TABLE `story_views` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `story_views` VALUES
(1,3,69,'2025-10-17 11:31:11'),
(2,2,69,'2025-10-17 11:31:15'),
(3,4,72,'2025-10-17 11:35:50'),
(4,3,72,'2025-10-17 11:35:56'),
(5,2,72,'2025-10-17 11:35:57'),
(6,5,70,'2025-10-17 11:45:01'),
(7,4,70,'2025-10-17 11:45:05'),
(8,3,70,'2025-10-17 11:45:15'),
(9,2,70,'2025-10-17 11:45:34'),
(10,5,69,'2025-10-17 11:46:14'),
(12,8,72,'2025-10-19 10:17:51'),
(13,8,103,'2025-10-19 11:26:59'),
(14,8,105,'2025-10-19 11:45:07'),
(15,9,69,'2025-10-19 15:22:46'),
(16,10,105,'2025-10-19 15:38:52'),
(17,12,72,'2025-10-22 10:09:52'),
(18,12,107,'2025-10-22 10:11:57'),
(19,17,70,'2025-10-26 18:21:33'),
(20,18,69,'2025-10-27 05:30:54'),
(21,16,69,'2025-10-27 05:31:00'),
(22,19,72,'2025-10-31 02:47:32'),
(24,20,107,'2025-11-01 09:32:31'),
(26,20,70,'2025-11-01 09:37:21'),
(28,22,72,'2025-11-12 15:05:21'),
(30,23,69,'2025-11-12 15:12:24'),
(32,22,69,'2025-11-12 15:12:38'),
(37,22,117,'2025-11-13 03:42:14'),
(38,23,117,'2025-11-13 03:42:32');
/*!40000 ALTER TABLE `story_views` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_badge_tracking`
--

DROP TABLE IF EXISTS `user_badge_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badge_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_name` varchar(50) NOT NULL,
  `has_seen_animation` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`,`badge_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badge_tracking`
--

/*!40000 ALTER TABLE `user_badge_tracking` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_badge_tracking` VALUES
(1,69,'Freshly Baked',1,'2025-10-08 14:59:28','2025-10-08 14:59:28'),
(2,70,'Freshly Baked',1,'2025-10-08 16:48:55','2025-10-08 16:48:55'),
(3,71,'Freshly Baked',1,'2025-10-16 05:20:28','2025-10-16 05:20:28'),
(4,70,'Kitchen Star',1,'2025-10-24 15:02:53','2025-10-24 15:02:53'),
(5,70,'Flavor Favorite',1,'2025-10-26 09:11:50','2025-10-26 09:19:21'),
(6,72,'Freshly Baked',1,'2025-10-26 10:58:12','2025-10-26 10:58:12'),
(7,109,'Freshly Baked',1,'2025-10-26 12:37:42','2025-10-26 12:37:42'),
(8,72,'Kitchen Star',1,'2025-10-26 13:02:08','2025-10-26 13:02:08'),
(9,69,'Kitchen Star',0,'2025-10-26 15:11:48','2025-10-30 05:19:36'),
(10,105,'Freshly Baked',1,'2025-11-01 14:18:05','2025-11-01 14:18:05');
/*!40000 ALTER TABLE `user_badge_tracking` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_badges`
--

DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badges` (
  `user_id` int(11) NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `badge_icon` varchar(255) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badges`
--

/*!40000 ALTER TABLE `user_badges` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_badges` VALUES
(1,'No Badge Yet',' ',0,'2025-10-08 15:39:30'),
(69,'Freshly Baked','img/freshly_baked.png',71,'2025-11-11 08:59:53'),
(70,'Flavor Favorite','img/flavor_favorite.png',217,'2025-11-12 15:05:22'),
(71,'Freshly Baked','img/freshly_baked.png',34,'2025-11-01 15:17:57'),
(72,'Kitchen Star','img/kitchen_star.png',500,'2025-11-12 15:02:29'),
(103,'No Badge Yet',' ',0,'2025-10-19 11:27:33'),
(104,'No Badge Yet',' ',0,'2025-10-19 11:40:36'),
(105,'Freshly Baked','img/freshly_baked.png',29,'2025-11-04 11:54:11'),
(107,'No Badge Yet','img/nobadge.png',11,'2025-11-12 14:58:04'),
(109,'Freshly Baked','img/freshly_baked.png',43,'2025-10-31 02:48:44'),
(110,'No Badge Yet',' ',0,'2025-10-27 07:06:46'),
(113,'No Badge Yet','img/nobadge.png',10,'2025-11-13 03:40:32'),
(114,'No Badge Yet','img/nobadge.png',6,'2025-11-13 03:36:10'),
(115,'No Badge Yet','img/nobadge.png',9,'2025-11-09 07:51:23'),
(117,'No Badge Yet','img/nobadge.png',5,'2025-11-13 03:55:14');
/*!40000 ALTER TABLE `user_badges` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_budgetlevel`
--

DROP TABLE IF EXISTS `user_budgetlevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_budgetlevel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `budget_level` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_budgetlevel`
--

/*!40000 ALTER TABLE `user_budgetlevel` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_budgetlevel` VALUES
(1,102,'Low Budget (â‚±101 - â‚±250)'),
(2,110,'Low Budget (₱101 - ₱250)'),
(3,111,'Low Budget (₱101 - ₱250)'),
(4,117,'Low Budget (₱101 - ₱250)');
/*!40000 ALTER TABLE `user_budgetlevel` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_category`
--

DROP TABLE IF EXISTS `user_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_category`
--

/*!40000 ALTER TABLE `user_category` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_category` VALUES
(4,101,'Main Dish'),
(5,101,'Appetizers & Snacks'),
(6,102,'Main Dish'),
(7,107,'Desserts & Sweets'),
(8,109,'Occasional'),
(9,110,'Vegetables'),
(10,111,'Main Dish'),
(11,114,'Drinks & Beverages'),
(12,70,'Drinks & Beverages'),
(13,70,'Healthy & Special Diets'),
(14,69,'Main Dish'),
(15,117,'Main Dish'),
(16,117,'Appetizers & Snacks');
/*!40000 ALTER TABLE `user_category` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_cooktime`
--

DROP TABLE IF EXISTS `user_cooktime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_cooktime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `cook_time` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cooktime`
--

/*!40000 ALTER TABLE `user_cooktime` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_cooktime` VALUES
(20,102,'5 to 15 minutes'),
(21,117,'15 to 30 minutes');
/*!40000 ALTER TABLE `user_cooktime` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_difflevel`
--

DROP TABLE IF EXISTS `user_difflevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_difflevel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `diff_level` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_difflevel`
--

/*!40000 ALTER TABLE `user_difflevel` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_difflevel` VALUES
(33,101,'Medium'),
(34,102,'Easy'),
(35,103,'Hard'),
(38,104,'Medium'),
(40,111,'Medium'),
(41,117,'Medium');
/*!40000 ALTER TABLE `user_difflevel` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_equipment`
--

DROP TABLE IF EXISTS `user_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `equipment_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_equipment`
--

/*!40000 ALTER TABLE `user_equipment` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_equipment` VALUES
(37,102,'Knife'),
(38,102,'Cutting Board'),
(39,102,'Pan'),
(40,102,'Pot'),
(41,117,'Knife'),
(42,117,'Cutting Board'),
(43,117,'Pan'),
(44,117,'Pot'),
(45,117,'Blender'),
(46,117,'Oven'),
(47,117,'Microwave');
/*!40000 ALTER TABLE `user_equipment` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_ingredients`
--

DROP TABLE IF EXISTS `user_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ingredient` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_ingredients`
--

/*!40000 ALTER TABLE `user_ingredients` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_ingredients` VALUES
(46,101,'Chicken'),
(47,101,'Beef'),
(48,101,'Pork'),
(49,102,'Chicken'),
(51,111,'Beef'),
(52,117,'Chicken'),
(53,117,'Beef'),
(54,117,'Pork');
/*!40000 ALTER TABLE `user_ingredients` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_otps`
--

DROP TABLE IF EXISTS `user_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_otps`
--

/*!40000 ALTER TABLE `user_otps` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_otps` VALUES
(60,'asd@gmail.com','981159','2025-10-22 16:03:08'),
(67,'altheabenedictos1@gmail.com','898678','2025-11-03 15:45:24'),
(71,'orly.adriano_garcia@yahoo.com','126300','2025-11-13 11:34:01');
/*!40000 ALTER TABLE `user_otps` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_points_history`
--

DROP TABLE IF EXISTS `user_points_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_points_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points_earned` int(11) NOT NULL,
  `points_type` enum('like','favorite','recipe_upload') NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points_history`
--

/*!40000 ALTER TABLE `user_points_history` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_points_history` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_preparation`
--

DROP TABLE IF EXISTS `user_preparation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preparation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `preparation_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preparation`
--

/*!40000 ALTER TABLE `user_preparation` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_preparation` VALUES
(44,101,'Raw'),
(45,101,'Steaming'),
(46,101,'Frying'),
(47,102,'Sauteing'),
(48,102,'Frying'),
(49,102,'Marinating'),
(50,105,'Chilling'),
(51,111,'Boiling'),
(52,117,'Raw'),
(53,117,'Steaming'),
(54,117,'Simmering'),
(55,117,'Frying');
/*!40000 ALTER TABLE `user_preparation` ENABLE KEYS */;
commit;

--
-- Table structure for table `user_tags`
--

DROP TABLE IF EXISTS `user_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tags`
--

/*!40000 ALTER TABLE `user_tags` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_tags` VALUES
(2,111,'Vegetarian'),
(3,117,'Vegan'),
(4,117,'GlutenFree'),
(5,117,'LowCarb'),
(6,117,'Halal');
/*!40000 ALTER TABLE `user_tags` ENABLE KEYS */;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `biography` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_username_change` datetime DEFAULT NULL,
  `total_violations` int(11) DEFAULT 0,
  `accstatus` varchar(50) DEFAULT 'Active',
  `status_updated_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'admin','admin','$2y$10$b2P7Ygf7Lo3Ly6N8Iq..X.BLPd1mhRv8As83LLSnlbwcB0XMk8JbK','2025-02-23 04:39:25','admin',NULL,NULL,NULL,0,'Active',NULL,1),
(69,'wowchef','theabenedictos16@gmail.com','$2y$10$RdhpTa/1vTdV24OKOVIgq.yoScy1LbpXmdM.wymz3w/25tRlTPnru','2025-10-08 13:39:07','user','🌶️ I hope my recipes inspire you to cook with passion, try something new, and enjoy every flavorful bite. Let’s make cooking more fun and delicious together here on TastyHub! 💫','profile_69_1762960032.jpeg','2025-10-08 06:40:38',1,'1st Warning','2025-10-26 23:21:57',1),
(70,'TastyHub','tastyhubrecipe@gmail.com','$2y$10$szVcYsB94CegukvzsU87FezZPQvFJy8uedUmOxzLwFQONdMYTxLxy','2025-10-08 15:06:07','user','Welcome to Tasty Hub where food meets Innovation✨🧑‍🍳\n\nDiscover, create, and share your favorite recipes with fellow food lovers.\nTastyHub connects home cooks and food enthusiasts in one flavorful community!','profile_70_1759936137.png',NULL,0,'Active',NULL,1),
(71,'yumyummy','althealucas2003@gmail.com','$2y$10$j1PV7SG6ZSzr7a8o21yjSO0M0mK6.E/QlFXmWjh675sqk4iUbE3dO','2025-10-08 16:12:20','user',NULL,'profile_71_1760889871.jpg',NULL,4,'Suspended (30 days)','2025-11-12 22:37:33',1),
(72,'FoodAlchemy','chailesreyes04@gmail.com','$2y$10$DLM7OvZTYREaaIJIWxNs4.nWX2j6BDvd.MDoQTgwDyC5HPAwV2jGi','2025-10-09 03:43:00','user','🧪 Food magician in the kitchen\n🔥 Mixing creativity, culture & taste\n🍽 #FoodAlchemy','profile_72_1761476404.jpeg','2025-10-26 18:59:15',0,'Active',NULL,1),
(73,'sabinanicole','sabinanicole11@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(74,'gwynethv','gwynethvillafuert27@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(75,'crysteldzn','crysteldzn.cscsas@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(76,'maryrose26','marychristinerose26@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(77,'rayanned','dereglarayanne5@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(78,'maiahmai','maiahmai28@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(79,'jayceeg','jayceegallemit@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(80,'gloriareyes','gloria1082reyes@gmail.co.','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(81,'preitya','Preityantoinette0521@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(82,'joyagaser','joy.agaser1995@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(83,'jayceecat','catindigjaycee43@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(84,'bernadeth','rcitechvocbernadethaguilar@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(85,'cbaquiran','cbaquiran99@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(86,'maelizabeth','manansalamaelizabeth@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(87,'mvmspcpc','mvm.spcpc@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(88,'jowanpeyt','jowanpeyt@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(89,'angeloswift','angelotayaoswift@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(90,'jenniferl','jennifer.leonzon@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(91,'haileyjade','Haileyjaderivera@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(92,'milletm','milletmadla@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(93,'jesrylm','jesryl.marinas03@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(94,'lealennie','lealennie@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(95,'aaronb','aaronbenedictos24@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(96,'airisron','airisronquillo23@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(97,'leeab','leea.bernardooo@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(98,'gdarell','gdarellgianne@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(99,'maximk','maxim.kerber@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(100,'prosenthal','p.rosenthal.bochum@gmail.com','$2y$10$FTL.T6oaHhlGPZUioJ9TAeV6KxqVc7qeCT/1UAm0UlxdKH5xUKO0m','2025-10-12 11:38:13','user',NULL,NULL,NULL,0,'Active',NULL,1),
(101,'Sijey','pascochristinejoyce03@gmail.com','$2y$10$xDn57yHZk7VH.SMwh17lgedUXgO3TjXfUxnyerCljop9vHVyTVgKS','2025-10-15 13:33:26','user',NULL,NULL,NULL,0,'Active',NULL,1),
(102,'Adriyano','allysaadriano222@gmail.com','$2y$10$u9TWDkPxiwhHpOHLB5uEo.pmsKc56KndqWmzxzj4/uoY3l4anj6ES','2025-10-15 13:39:35','user',NULL,NULL,NULL,0,'Active',NULL,1),
(105,'masterchef','altheadump1117@gmail.com','$2y$10$oVRIxP8aRVqQwIsTA.ht.uDhCcsGr5Vc9g5idinphUhigDEkftoYK','2025-10-19 11:43:32','user',NULL,'profile_105_1760888315.jpg',NULL,0,'Active',NULL,1),
(106,'bentong','asd@gmail.com','$2y$10$xX4Mf/V9K8x.544j2l02befv7sUZScw4wF3LQZXZP4UtHdWzj4Cqu','2025-10-22 08:03:08','user',NULL,NULL,NULL,0,'Active',NULL,0),
(107,'teyia','chailes.reyes@gmail.com','$2y$10$IUzAPCeQIz9TYiarOCV8..v2g9tCjqSDbmAI2PYam3SfUIhkJxTxO','2025-10-22 10:10:53','user',NULL,'profile_107_1761987980.jpg',NULL,4,'Suspended (30 days)','2025-11-12 22:56:34',1),
(108,'Joza','jozamiranda7@gmail.com','$2y$10$0bvdB4Ue/t8vswCgD8wqdOHRYTQRxKKEl1WRZkWQG0Wmt10dSHqeK','2025-10-26 05:41:01','user',NULL,NULL,NULL,0,'Active',NULL,1),
(109,'DishMuse','altheabenedictosmendoza@gmail.com','$2y$10$RK6dIewBFiePfIdvsvWGOOqCqmi9nMzIWMOIwv/lzGuSCVgUDCOqS','2025-10-26 11:15:34','user','Serving up a mush of dishes and a dash of chaos. 😋\nBecause good food doesn’t have to be fancy — just tasty!','profile_109_1761477466.jpeg',NULL,0,'Active',NULL,1),
(110,'altheaaa','chailes.reyes+1@gmail.com','$2y$10$9DfYDYl1EgKrFkzgH3QO.uga7iN8slPWmrbIFS3XMwK93ebzK7c/a','2025-10-27 05:55:34','user',NULL,NULL,NULL,0,'Active',NULL,1),
(112,'hehe','altheabenedictos1@gmail.com','$2y$10$svm/21QxbVLSRqb6RjDGHOHu36/8V1rgWMEhhOn6UR93aBr7mDXge','2025-11-03 07:39:16','user',NULL,NULL,NULL,0,'Active',NULL,0),
(113,'easycooks','althealucas2003+1@gmail.com','$2y$10$tG8uQBuvVvp9xCa8rGuBP.knhrJH3lKTDGqHVPddpmp809MFAn2YK','2025-11-04 10:49:49','user',NULL,'profile_113_1762253430.jpeg',NULL,0,'Active',NULL,1),
(114,'mendoza','altheabenedictosmendoza+1@gmail.com','$2y$10$/a3MFS7W2jia1C0wJ590SeBCEhFAmbo5kybwOr2sR7/4H9Dqn4xxO','2025-11-04 10:57:51','user',NULL,'profile_114_1762253926.jpg',NULL,0,'Active',NULL,1),
(115,'foody','theabenedictos16+1@gmail.com','$2y$10$CfAo7M1v7Z/C86eO2EASceTrcLE1qS2xuPFZ1wQxBRKOUJTv64tQC','2025-11-04 11:04:38','user',NULL,'profile_115_1762254629.jpg',NULL,0,'Active',NULL,1),
(116,'OrlyRenWin','orly.adriano_garcia@yahoo.com','$2y$10$.Zzh6/oOfVVMU3htSSFCfuT9FxtEyEOG6dqi1RgeVJU1YZzDbHIUW','2025-11-13 03:34:01','user',NULL,NULL,NULL,0,'Active',NULL,0),
(117,'EdwinRenOrly','edwin.garcia@bulsu.edu.ph','$2y$10$mP784fcvxO0HE6/Dl84Ow.qJQJj.ulrGCa.c2cySzfUaX6mK35FsS','2025-11-13 03:34:51','user',NULL,NULL,NULL,0,'Active',NULL,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
commit;

--
-- Table structure for table `view_logs`
--

DROP TABLE IF EXISTS `view_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `view_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `livestream_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `viewed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `livestream_id` (`livestream_id`,`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_logs`
--

/*!40000 ALTER TABLE `view_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `view_logs` VALUES
(1,1,'112.208.169.204','2025-10-16 17:35:38'),
(2,6,'112.208.169.204','2025-10-16 17:46:11'),
(3,4,'112.208.169.204','2025-10-16 18:25:43'),
(4,9,'112.208.169.204','2025-10-16 19:37:01'),
(5,9,'120.29.66.155','2025-10-16 19:38:33'),
(6,6,'120.29.66.155','2025-10-16 19:39:20'),
(7,10,'112.208.169.204','2025-10-16 19:47:16'),
(8,10,'120.29.66.155','2025-10-16 19:48:11'),
(9,11,'112.208.169.204','2025-10-16 20:07:00'),
(10,13,'112.208.169.204','2025-10-16 21:02:15'),
(11,14,'112.208.169.204','2025-10-17 13:20:26'),
(12,15,'112.208.169.204','2025-10-17 13:37:02'),
(13,15,'120.29.66.155','2025-10-17 13:38:06'),
(14,16,'112.208.169.204','2025-10-17 13:59:04'),
(15,16,'120.29.66.155','2025-10-17 13:59:19'),
(16,17,'112.208.169.204','2025-10-17 14:36:56'),
(17,18,'112.208.169.204','2025-10-17 14:46:13'),
(18,19,'112.208.169.204','2025-10-17 15:10:08'),
(19,20,'112.208.169.204','2025-10-17 15:30:26'),
(20,21,'120.29.66.155','2025-10-17 16:06:07'),
(21,21,'112.208.169.204','2025-10-17 16:06:37'),
(22,22,'112.208.169.204','2025-10-17 16:20:52'),
(23,24,'112.208.169.204','2025-10-17 17:15:35'),
(24,24,'120.29.66.155','2025-10-17 17:16:23'),
(25,25,'112.208.169.204','2025-10-17 19:32:20'),
(26,26,'112.208.169.204','2025-10-17 19:35:34'),
(27,27,'112.208.169.204','2025-10-17 19:39:36'),
(28,27,'120.29.66.155','2025-10-17 19:41:11'),
(29,25,'120.29.66.155','2025-10-17 19:41:33'),
(30,6,'136.158.60.23','2025-10-18 15:42:42'),
(31,28,'112.208.169.204','2025-10-19 18:16:51'),
(32,28,'103.206.80.67','2025-10-19 19:33:45'),
(33,28,'120.29.66.155','2025-10-19 19:34:44'),
(34,29,'112.208.169.204','2025-10-19 23:19:59'),
(35,30,'175.176.24.143','2025-10-22 13:17:37'),
(36,29,'175.176.24.143','2025-10-22 13:23:58'),
(37,32,'175.176.24.143','2025-10-22 18:12:07'),
(38,29,'120.29.66.155','2025-10-26 16:36:08'),
(39,33,'120.29.66.155','2025-10-26 22:22:49'),
(40,29,'175.176.28.5','2025-10-27 13:27:28'),
(41,33,'175.176.28.5','2025-10-27 13:39:12'),
(42,29,'120.29.110.70','2025-11-01 13:00:35'),
(44,33,'120.29.110.70','2025-11-01 13:00:45'),
(45,34,'136.158.61.65','2025-11-03 16:20:49'),
(46,34,'112.208.169.204','2025-11-11 21:05:59'),
(47,35,'175.176.27.210','2025-11-13 11:33:34'),
(48,35,'124.104.137.128','2025-11-13 11:35:33'),
(50,6,'124.104.137.128','2025-11-13 11:35:45'),
(51,34,'124.104.137.128','2025-11-13 11:36:03');
/*!40000 ALTER TABLE `view_logs` ENABLE KEYS */;
commit;

--
-- Dumping routines for database 'u147049380_tasty_hub'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-11-17  9:16:24
