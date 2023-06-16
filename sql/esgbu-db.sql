-- MySQL dump 10.14  Distrib 5.5.64-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: esgbu
-- ------------------------------------------------------
-- Server version	5.5.64-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `administration_types`
--

DROP TABLE IF EXISTS `administration_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administration_types` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_types`
--

DROP TABLE IF EXISTS `content_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contents`
--

DROP TABLE IF EXISTS `contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_fk` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contents_type_fk` (`type_fk`),
  CONSTRAINT `contents_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `content_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `data_types`
--

DROP TABLE IF EXISTS `data_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name_fk` int(10) unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `code_eu` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `measure_unit_fk` int(10) unsigned DEFAULT NULL,
  `group_fk` smallint(5) unsigned NOT NULL,
  `instruction_fk` int(10) unsigned DEFAULT NULL,
  `type_fk` tinyint(3) unsigned NOT NULL,
  `definition_fk` int(10) unsigned DEFAULT NULL,
  `date_fk` int(10) unsigned DEFAULT NULL,
  `group_order` smallint(6) NOT NULL,
  `administrator` tinyint(1) NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `facet` tinyint(1) NOT NULL DEFAULT '0',
  `simplified_facet` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `group_fk` (`group_fk`),
  KEY `data_types_name_fk` (`name_fk`),
  KEY `data_types_measure_unit_fk` (`measure_unit_fk`),
  KEY `data_types_instruction_fk` (`instruction_fk`),
  KEY `data_types_type_fk` (`type_fk`),
  KEY `data_types_definition_fk` (`definition_fk`),
  KEY `data_types_date_fk` (`date_fk`),
  CONSTRAINT `data_types_date_fk` FOREIGN KEY (`date_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `data_types_definition_fk` FOREIGN KEY (`definition_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `data_types_group_fk` FOREIGN KEY (`group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `data_types_instruction_fk` FOREIGN KEY (`instruction_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `data_types_measure_unit_fk` FOREIGN KEY (`measure_unit_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `data_types_name_fk` FOREIGN KEY (`name_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `data_types_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `region_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_region_fk` (`region_fk`),
  CONSTRAINT `departments_region_fk` FOREIGN KEY (`region_fk`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_active_history`
--

DROP TABLE IF EXISTS `documentary_structure_active_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_active_history` (
  `survey_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`survey_fk`,`documentary_structure_fk`),
  KEY `documentary_structure_active_history_documentary_structure_fk` (`documentary_structure_fk`),
  KEY `documentary_structure_active_history_survey_fk` (`survey_fk`),
  CONSTRAINT `documentary_structure_active_history_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_active_history_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_comments`
--

DROP TABLE IF EXISTS `documentary_structure_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_comments` (
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`documentary_structure_fk`,`survey_fk`,`data_type_fk`),
  KEY `documentary_structure_comments_data_type_fk` (`data_type_fk`),
  KEY `documentary_structure_comments_survey_fk` (`survey_fk`),
  CONSTRAINT `documentary_structure_comments_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`),
  CONSTRAINT `documentary_structure_comments_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_comments_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_data_values`
--

DROP TABLE IF EXISTS `documentary_structure_data_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_data_values` (
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`data_type_fk`,`survey_fk`,`documentary_structure_fk`),
  KEY `data_type_fk` (`data_type_fk`),
  KEY `survey_fk` (`survey_fk`),
  KEY `documentary_structure_fk` (`documentary_structure_fk`),
  CONSTRAINT `documentary_structure_data_values_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`),
  CONSTRAINT `documentary_structure_data_values_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_data_values_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_group_locks`
--

DROP TABLE IF EXISTS `documentary_structure_group_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_group_locks` (
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `user_fk` smallint(5) unsigned NOT NULL,
  `group_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `lock_date` datetime NOT NULL,
  PRIMARY KEY (`group_fk`,`survey_fk`,`documentary_structure_fk`),
  KEY `documentary_structure_fk_user_fk` (`documentary_structure_fk`),
  KEY `documentary_structure_group_locks_survey_fk` (`survey_fk`),
  KEY `documentary_structure_group_locks_group_fk` (`group_fk`),
  KEY `documentary_structure_group_locks_documentary_structure_fk` (`documentary_structure_fk`),
  KEY `documentary_structure_group_locks_user_fk` (`user_fk`),
  CONSTRAINT `documentary_structure_group_locks_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_group_locks_group_fk` FOREIGN KEY (`group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `documentary_structure_group_locks_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`),
  CONSTRAINT `documentary_structure_group_locks_user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_link_history`
--

DROP TABLE IF EXISTS `documentary_structure_link_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_link_history` (
  `survey_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `establishment_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`survey_fk`,`documentary_structure_fk`,`establishment_fk`),
  KEY `documentary_structure_link_history_survey_fk` (`survey_fk`),
  KEY `documentary_structure_link_history_documentary_structure_fk` (`documentary_structure_fk`),
  KEY `documentary_structure_link_history_establishment_fk` (`establishment_fk`),
  CONSTRAINT `documentary_structure_link_history_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_link_history_establishment_fk` FOREIGN KEY (`establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `documentary_structure_link_history_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structure_relations`
--

DROP TABLE IF EXISTS `documentary_structure_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structure_relations` (
  `origin_documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `result_documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `type_fk` smallint(5) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`origin_documentary_structure_fk`,`result_documentary_structure_fk`,`type_fk`),
  KEY `origin_documentary_structure_fk` (`origin_documentary_structure_fk`) USING BTREE,
  KEY `result_documentary_structure_fk` (`result_documentary_structure_fk`) USING BTREE,
  KEY `type_fk` (`type_fk`),
  CONSTRAINT `documentary_structure_relations_origin_documentary_structure_fk` FOREIGN KEY (`origin_documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_relations_result_documentary_structure_fk` FOREIGN KEY (`result_documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `documentary_structure_relations_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `relation_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentary_structures`
--

DROP TABLE IF EXISTS `documentary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentary_structures` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `official_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `use_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `acronym` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `postal_code` char(5) COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `establishment_fk` smallint(5) unsigned NOT NULL,
  `instruction` text COLLATE utf8_unicode_ci,
  `department_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `documentary_structures_establishment_fk` (`establishment_fk`) USING BTREE,
  KEY `documentary_structures_department_fk` (`department_fk`),
  CONSTRAINT `documentary_structures_department_fk` FOREIGN KEY (`department_fk`) REFERENCES `departments` (`id`),
  CONSTRAINT `establishment_fk` FOREIGN KEY (`establishment_fk`) REFERENCES `establishments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `editorials`
--

DROP TABLE IF EXISTS `editorials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `editorials` (
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `survey_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`survey_fk`),
  CONSTRAINT `editorials_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishment_active_history`
--

DROP TABLE IF EXISTS `establishment_active_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishment_active_history` (
  `survey_fk` smallint(5) unsigned NOT NULL,
  `establishment_fk` smallint(5) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`survey_fk`,`establishment_fk`),
  KEY `establishment_active_history_establishment_fk` (`establishment_fk`),
  KEY `establishment_active_history_survey_fk` (`survey_fk`),
  CONSTRAINT `establishment_active_history_establishment_fk` FOREIGN KEY (`establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `establishment_active_history_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishment_data_values`
--

DROP TABLE IF EXISTS `establishment_data_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishment_data_values` (
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `establishment_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`data_type_fk`,`survey_fk`,`establishment_fk`),
  KEY `data_type_fk` (`data_type_fk`),
  KEY `survey_fk` (`survey_fk`),
  KEY `establishment_fk` (`establishment_fk`),
  CONSTRAINT `establishment_data_values_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`),
  CONSTRAINT `establishment_data_values_establishment_fk` FOREIGN KEY (`establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `establishment_data_values_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishment_group_locks`
--

DROP TABLE IF EXISTS `establishment_group_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishment_group_locks` (
  `establishment_fk` smallint(5) unsigned NOT NULL,
  `user_fk` smallint(5) unsigned NOT NULL,
  `group_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `lock_date` datetime NOT NULL,
  PRIMARY KEY (`group_fk`,`survey_fk`,`establishment_fk`),
  KEY `establishment_fk_user_fk` (`establishment_fk`),
  KEY `establishment_group_locks_survey_fk` (`survey_fk`),
  KEY `establishment_group_locks_group_fk` (`group_fk`),
  KEY `establishment_group_locks_establishment_fk` (`establishment_fk`),
  KEY `establishment_group_locks_user_fk` (`user_fk`),
  CONSTRAINT `establishment_group_locks_establishment_fk` FOREIGN KEY (`establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `establishment_group_locks_group_fk` FOREIGN KEY (`group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `establishment_group_locks_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`),
  CONSTRAINT `establishment_group_locks_user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishment_relations`
--

DROP TABLE IF EXISTS `establishment_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishment_relations` (
  `origin_establishment_fk` smallint(5) unsigned NOT NULL,
  `result_establishment_fk` smallint(6) unsigned NOT NULL,
  `type_fk` smallint(5) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`origin_establishment_fk`,`result_establishment_fk`,`type_fk`),
  KEY `establishment_relations_origin_establishment_fk` (`origin_establishment_fk`) USING BTREE,
  KEY `establishment_relations_result_establishment_fk` (`result_establishment_fk`) USING BTREE,
  KEY `type_fk` (`type_fk`),
  CONSTRAINT `establishment_relations_origin_establishment_fk` FOREIGN KEY (`origin_establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `establishment_relations_result_establishment_fk` FOREIGN KEY (`result_establishment_fk`) REFERENCES `establishments` (`id`),
  CONSTRAINT `establishment_relations_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `relation_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishment_types`
--

DROP TABLE IF EXISTS `establishment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishment_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `establishments`
--

DROP TABLE IF EXISTS `establishments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishments` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `official_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `use_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `acronym` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brand` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `postal_code` char(5) COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type_fk` smallint(5) unsigned NOT NULL,
  `instruction` text COLLATE utf8_unicode_ci,
  `department_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_fk` (`type_fk`),
  KEY `establishments_department_fk` (`department_fk`),
  CONSTRAINT `establishments_department_fk` FOREIGN KEY (`department_fk`) REFERENCES `departments` (`id`),
  CONSTRAINT `establishments_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `establishment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_instructions`
--

DROP TABLE IF EXISTS `group_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_instructions` (
  `group_fk` smallint(5) unsigned NOT NULL,
  `instruction_fk` int(10) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`group_fk`,`survey_fk`),
  KEY `group_instructions_survey_fk` (`survey_fk`),
  KEY `group_instructions_instruction_fk` (`instruction_fk`),
  CONSTRAINT `group_instructions_group_fk` FOREIGN KEY (`group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `group_instructions_instruction_fk` FOREIGN KEY (`instruction_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `group_instructions_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `title_fk` int(10) unsigned NOT NULL,
  `parent_group_fk` smallint(5) unsigned DEFAULT NULL,
  `administration_type_fk` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_group_fk` (`parent_group_fk`),
  KEY `groups_title_fk` (`title_fk`),
  KEY `groups_administration_type_fk` (`administration_type_fk`),
  CONSTRAINT `groups_administration_type_fk` FOREIGN KEY (`administration_type_fk`) REFERENCES `administration_types` (`id`),
  CONSTRAINT `groups_parent_group_fk` FOREIGN KEY (`parent_group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `groups_title_fk` FOREIGN KEY (`title_fk`) REFERENCES `contents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indicators`
--

DROP TABLE IF EXISTS `indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indicators` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name_fk` int(10) unsigned NOT NULL,
  `query` mediumtext COLLATE utf8_unicode_ci,
  `by_establishment` tinyint(1) NOT NULL,
  `by_doc_struct` tinyint(1) NOT NULL,
  `by_region` tinyint(1) NOT NULL,
  `global` tinyint(1) NOT NULL,
  `key_figure` tinyint(1) NOT NULL,
  `description_fk` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL,
  `administrator` tinyint(1) NOT NULL DEFAULT '0',
  `prefix` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `suffix` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indicators_name_fk` (`name_fk`),
  KEY `indicators_description_fk` (`description_fk`),
  CONSTRAINT `indicators_description_fk` FOREIGN KEY (`description_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `indicators_name_fk` FOREIGN KEY (`name_fk`) REFERENCES `contents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `languages` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `numbers`
--

DROP TABLE IF EXISTS `numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `numbers` (
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `min` float DEFAULT NULL,
  `max` float DEFAULT NULL,
  `is_decimal` tinyint(1) NOT NULL DEFAULT '0',
  `min_alert` float DEFAULT NULL,
  `max_alert` float DEFAULT NULL,
  `evolution_min` float DEFAULT NULL,
  `evolution_max` float DEFAULT NULL,
  PRIMARY KEY (`data_type_fk`),
  CONSTRAINT `numbers_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operations`
--

DROP TABLE IF EXISTS `operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operations` (
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `formula` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`data_type_fk`),
  CONSTRAINT `operations_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `physical_libraries`
--

DROP TABLE IF EXISTS `physical_libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_libraries` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `official_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `use_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `postal_code` char(5) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `instruction` text COLLATE utf8_unicode_ci,
  `sort_order` smallint(5) unsigned NOT NULL,
  `fictitious` tinyint(1) NOT NULL,
  `department_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `physical_libraries_documentaryStructure_fk` (`documentary_structure_fk`) USING BTREE,
  KEY `physical_libraries_department_fk` (`department_fk`),
  CONSTRAINT `physical_libraries_department_fk` FOREIGN KEY (`department_fk`) REFERENCES `departments` (`id`),
  CONSTRAINT `physical_libraries_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `physical_library_active_history`
--

DROP TABLE IF EXISTS `physical_library_active_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_library_active_history` (
  `survey_fk` smallint(5) unsigned NOT NULL,
  `physical_library_fk` smallint(5) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`survey_fk`,`physical_library_fk`),
  KEY `physical_library_active_history_physical_library_fk` (`physical_library_fk`),
  KEY `physical_library_active_history_survey_fk` (`survey_fk`),
  CONSTRAINT `physical_library_active_history_physical_library_fk` FOREIGN KEY (`physical_library_fk`) REFERENCES `physical_libraries` (`id`),
  CONSTRAINT `physical_library_active_history_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `physical_library_data_values`
--

DROP TABLE IF EXISTS `physical_library_data_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_library_data_values` (
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `physical_library_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`data_type_fk`,`survey_fk`,`physical_library_fk`),
  KEY `data_type_fk` (`data_type_fk`),
  KEY `survey_fk` (`survey_fk`),
  KEY `physical_library_fk` (`physical_library_fk`),
  CONSTRAINT `physical_library_data_values_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`),
  CONSTRAINT `physical_library_data_values_physical_library_fk` FOREIGN KEY (`physical_library_fk`) REFERENCES `physical_libraries` (`id`),
  CONSTRAINT `physical_library_data_values_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `physical_library_group_locks`
--

DROP TABLE IF EXISTS `physical_library_group_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_library_group_locks` (
  `physical_library_fk` smallint(5) unsigned NOT NULL,
  `user_fk` smallint(5) unsigned NOT NULL,
  `group_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `lock_date` datetime NOT NULL,
  PRIMARY KEY (`group_fk`,`survey_fk`,`physical_library_fk`),
  KEY `physical_library_fk_user_fk` (`physical_library_fk`),
  KEY `physical_library_group_locks_survey_fk` (`survey_fk`),
  KEY `physical_library_group_locks_group_fk` (`group_fk`),
  KEY `physical_library_group_locks_physical_library_fk` (`physical_library_fk`),
  KEY `physical_library_group_locks_user_fk` (`user_fk`),
  CONSTRAINT `physical_library_group_locks_group_fk` FOREIGN KEY (`group_fk`) REFERENCES `groups` (`id`),
  CONSTRAINT `physical_library_group_locks_physical_library_fk` FOREIGN KEY (`physical_library_fk`) REFERENCES `physical_libraries` (`id`),
  CONSTRAINT `physical_library_group_locks_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`),
  CONSTRAINT `physical_library_group_locks_user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `physical_library_link_history`
--

DROP TABLE IF EXISTS `physical_library_link_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physical_library_link_history` (
  `survey_fk` smallint(5) unsigned NOT NULL,
  `physical_library_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`survey_fk`,`physical_library_fk`,`documentary_structure_fk`),
  KEY `physical_library_link_history_survey_fk` (`survey_fk`),
  KEY `physical_library_link_history_physical_library_fk` (`physical_library_fk`),
  KEY `physical_library_link_history_documentary_structure_fk` (`documentary_structure_fk`),
  CONSTRAINT `physical_library_link_history_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `physical_library_link_history_physical_library_fk` FOREIGN KEY (`physical_library_fk`) REFERENCES `physical_libraries` (`id`),
  CONSTRAINT `physical_library_link_history_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regions` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relation_types`
--

DROP TABLE IF EXISTS `relation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relation_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `associated` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `route_contents`
--

DROP TABLE IF EXISTS `route_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_contents` (
  `route_fk` smallint(5) unsigned NOT NULL,
  `language_fk` tinyint(4) unsigned NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`route_fk`,`language_fk`),
  KEY `route_contents_language_fk` (`language_fk`),
  CONSTRAINT `route_contents_language_fk` FOREIGN KEY (`language_fk`) REFERENCES `languages` (`id`),
  CONSTRAINT `route_contents_route_fk` FOREIGN KEY (`route_fk`) REFERENCES `routes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `states` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_data_types`
--

DROP TABLE IF EXISTS `survey_data_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_data_types` (
  `type_fk` smallint(5) unsigned NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `survey_fk` (`survey_fk`),
  KEY `type_fk` (`type_fk`),
  CONSTRAINT `survey_data_types_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`),
  CONSTRAINT `survey_data_types_type_fk` FOREIGN KEY (`type_fk`) REFERENCES `data_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_validations`
--

DROP TABLE IF EXISTS `survey_validations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_validations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `valid` tinyint(1) NOT NULL,
  `validation_date` datetime NOT NULL,
  `survey_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_validations_documentary_structure_fk` (`documentary_structure_fk`),
  KEY `survey_validation_survey_fk` (`survey_fk`),
  CONSTRAINT `survey_validations_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `survey_validation_survey_fk` FOREIGN KEY (`survey_fk`) REFERENCES `surveys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `surveys` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `calendar_year` date NOT NULL,
  `data_calendar_year` date NOT NULL,
  `creation` datetime NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `instruction` text COLLATE utf8_unicode_ci,
  `state_fk` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `state_fk` (`state_fk`),
  CONSTRAINT `surveys_state_fk` FOREIGN KEY (`state_fk`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texts`
--

DROP TABLE IF EXISTS `texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts` (
  `data_type_fk` smallint(5) unsigned NOT NULL,
  `max_length` smallint(5) unsigned DEFAULT NULL,
  `min_length` smallint(5) unsigned DEFAULT NULL,
  `regex` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`data_type_fk`),
  CONSTRAINT `texts_data_type_fk` FOREIGN KEY (`data_type_fk`) REFERENCES `data_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_fk` int(10) unsigned NOT NULL,
  `language_fk` tinyint(4) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `translations_content_fk` (`content_fk`),
  KEY `translations_language_fk` (`language_fk`),
  CONSTRAINT `translations_content_fk` FOREIGN KEY (`content_fk`) REFERENCES `contents` (`id`),
  CONSTRAINT `translations_language_fk` FOREIGN KEY (`language_fk`) REFERENCES `languages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `types`
--

DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `types` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_role_requests`
--

DROP TABLE IF EXISTS `user_role_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role_requests` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_fk` smallint(5) unsigned NOT NULL,
  `role_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned DEFAULT NULL,
  `creation` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_role_requests_user_fk` (`user_fk`),
  KEY `user_role_requests_role_fk` (`role_fk`),
  KEY `user_role_requests_documentary_structure_fk` (`documentary_structure_fk`),
  CONSTRAINT `user_role_requests_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`),
  CONSTRAINT `user_role_requests_role_fk` FOREIGN KEY (`role_fk`) REFERENCES `roles` (`id`),
  CONSTRAINT `user_role_requests_user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_fk` smallint(5) unsigned NOT NULL,
  `role_fk` smallint(5) unsigned NOT NULL,
  `documentary_structure_fk` smallint(5) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_fk` (`user_fk`,`role_fk`,`documentary_structure_fk`),
  KEY `user_roles_user_fk` (`user_fk`) USING BTREE,
  KEY `user_roles_role_fk` (`role_fk`) USING BTREE,
  KEY `user_roles_documentary_structure_fk` (`documentary_structure_fk`) USING BTREE,
  CONSTRAINT `role_fk` FOREIGN KEY (`role_fk`) REFERENCES `roles` (`id`),
  CONSTRAINT `user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`),
  CONSTRAINT `user_role_documentary_structure_fk` FOREIGN KEY (`documentary_structure_fk`) REFERENCES `documentary_structures` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_validations`
--

DROP TABLE IF EXISTS `user_validations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_validations` (
  `user_fk` smallint(5) unsigned NOT NULL,
  `token` text COLLATE utf8_unicode_ci NOT NULL,
  `creation` datetime NOT NULL,
  PRIMARY KEY (`user_fk`),
  CONSTRAINT `user_validations_user_fk` FOREIGN KEY (`user_fk`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `eppn` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `mail` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `valid` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `eppn` (`eppn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-05-11 11:28:12
