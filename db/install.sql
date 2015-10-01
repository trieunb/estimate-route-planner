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
-- Table structure for table `company_info`
--

DROP TABLE IF EXISTS `company_info`;
CREATE TABLE `company_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `full_address` text,
  `primary_phone_number` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `estimate_footer` text,
  `logo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


LOCK TABLES `company_info` WRITE;
/*!40000 ALTER TABLE `company_info` DISABLE KEYS */;
INSERT INTO `company_info` VALUES (1,'Your Company Name','Company address','(123) 456-7890','123-456-789','company-name@example.com','http://example.com','Thank you for your business and have a great day!',NULL);
/*!40000 ALTER TABLE `company_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) NOT NULL,
  `sync_token` bigint(20) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `given_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `print_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `primary_phone_number` varchar(255) DEFAULT NULL,
  `mobile_phone_number` varchar(255) DEFAULT NULL,
  `alternate_phone_number` varchar(255) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `bill_address_id` int(11) DEFAULT NULL,
  `bill_address` text,
  `bill_city` varchar(255) DEFAULT NULL COMMENT 'Print on check name',
  `bill_state` varchar(255) DEFAULT NULL,
  `bill_zip_code` varchar(255) DEFAULT NULL,
  `bill_country` varchar(255) DEFAULT NULL,
  `ship_address_id` int(11) DEFAULT NULL,
  `ship_address` text,
  `ship_city` varchar(255) DEFAULT NULL,
  `ship_state` varchar(255) DEFAULT NULL,
  `ship_zip_code` varchar(255) DEFAULT NULL,
  `ship_country` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `last_updated_at` datetime NOT NULL COMMENT 'useful for synchronize',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `estimates`
--

DROP TABLE IF EXISTS `estimates`;
CREATE TABLE `estimates` (
  `id` bigint(20) NOT NULL,
  `sync_token` bigint(20) NOT NULL DEFAULT '0',
  `customer_id` bigint(20) NOT NULL,
  `route_id` bigint(20) DEFAULT NULL,
  `route_order` int(11) NOT NULL DEFAULT '0',
  `estimate_footer` text COMMENT 'the customer memo',
  `due_date` date DEFAULT NULL,
  `txn_date` date DEFAULT NULL COMMENT 'this is estimate date',
  `expiration_date` date DEFAULT NULL COMMENT 'Date by which estimate must be accepted before invalidation.',
  `source` varchar(50) DEFAULT NULL,
  `customer_signature` varchar(255) DEFAULT NULL COMMENT 'the path to image of signature',
  `location_notes` text,
  `date_of_signature` date DEFAULT NULL,
  `sold_by_1` varchar(255) DEFAULT NULL,
  `sold_by_2` varchar(255) DEFAULT NULL,
  `job_customer_id` bigint(20) DEFAULT NULL,
  `job_address_id` int(11) DEFAULT NULL,
  `job_address` text COMMENT 'worker',
  `job_city` varchar(255) DEFAULT NULL,
  `job_state` varchar(255) DEFAULT NULL,
  `job_zip_code` varchar(255) DEFAULT NULL,
  `job_country` varchar(255) DEFAULT NULL,
  `job_lat` float DEFAULT NULL,
  `job_lng` float DEFAULT NULL,
  `primary_phone_number` varchar(255) DEFAULT NULL,
  `alternate_phone_number` varchar(255) DEFAULT NULL COMMENT 'secondary phone',
  `email` varchar(255) DEFAULT NULL,
  `bill_address_id` int(11) DEFAULT NULL,
  `bill_address` text,
  `bill_city` varchar(255) DEFAULT NULL,
  `bill_state` varchar(255) DEFAULT NULL,
  `bill_zip_code` varchar(255) DEFAULT NULL,
  `bill_country` varchar(255) DEFAULT NULL,
  `status` varchar(45) NOT NULL,
  `doc_number` bigint(20) DEFAULT NULL,
  `total` float DEFAULT '0',
  `last_updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- Table structure for table `estimate_routes`
--

DROP TABLE IF EXISTS `estimate_routes`;
CREATE TABLE `estimate_routes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `employee_id` bigint(20) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Table structure for table `estimate_attachments`
--

DROP TABLE IF EXISTS `estimate_attachments`;
CREATE TABLE `estimate_attachments` (
  `id` bigint(20) NOT NULL,
  `sync_token` bigint(20) DEFAULT NULL,
  `estimate_id` bigint(20) NOT NULL,
  `content_type` varchar(100) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `access_uri` varchar(255) DEFAULT NULL,
  `tmp_download_uri` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_updated_at` datetime NOT NULL,
  `is_customer_signature` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `products_and_services`
--

DROP TABLE IF EXISTS `products_and_services`;
CREATE TABLE `products_and_services` (
  `id` bigint(20) NOT NULL,
  `sync_token` bigint(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rate` float NOT NULL DEFAULT '0' COMMENT 'Unit price',
  `active` tinyint(1) NOT NULL,
  `taxable` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `last_updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- Table structure for table `referrals`
--

DROP TABLE IF EXISTS `referrals`;
CREATE TABLE `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) DEFAULT NULL,
  `route_id` bigint(20) DEFAULT NULL,
  `route_order` int(11) NOT NULL DEFAULT '0',
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `primary_phone_number` varchar(100) DEFAULT NULL,
  `date_service` date DEFAULT NULL,
  `how_find_us` varchar(255) DEFAULT NULL,
  `type_of_service_description` text,
  `status` varchar(50) DEFAULT NULL,
  `date_requested` date DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Table structure for table `crew_routes`
--

DROP TABLE IF EXISTS `crew_routes`;
CREATE TABLE `crew_routes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `employee_id` bigint(20) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
CREATE TABLE `preferences` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gmap_api_key` varchar(255) DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `qbo_oauth_token` varchar(255) DEFAULT NULL,
  `qbo_oauth_secret` varchar(255) DEFAULT NULL,
  `qbo_company_id` varchar(255) DEFAULT NULL,
  `qbo_token_expires_at` datetime DEFAULT NULL,
  `qbo_consumer_secret` varchar(255) DEFAULT NULL,
  `qbo_consumer_key` varchar(255) DEFAULT NULL,
  `is_synchronizing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'determine that cron job is running',
  `gmail_username` varchar(255) DEFAULT NULL,
  `gmail_password` varchar(255) DEFAULT NULL,
  `gmail_server` varchar(255) DEFAULT NULL,
  `gmail_port` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` bigint(20) NOT NULL,
  `sync_token` bigint(20) NOT NULL,
  `primary_address` text,
  `primary_city` varchar(100) DEFAULT NULL,
  `primary_state` varchar(100) DEFAULT NULL,
  `primary_zip_code` varchar(100) DEFAULT NULL,
  `primary_country` varchar(100) DEFAULT NULL,
  `given_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL COMMENT 'PrintOnCheckName',
  `print_name` varchar(100) DEFAULT NULL,
  `primary_phone_number` varchar(255) DEFAULT NULL,
  `ssn` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `last_updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `estimate_lines`
--

DROP TABLE IF EXISTS `estimate_lines`;
CREATE TABLE `estimate_lines` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `line_id` bigint(20) DEFAULT '0',
  `line_num` bigint(20) DEFAULT '0',
  `estimate_id` bigint(20) NOT NULL,
  `product_service_id` bigint(20) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT '0',
  `rate` float NOT NULL DEFAULT '0' COMMENT 'UnitPrice',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2751 DEFAULT CHARSET=utf8;

--
-- Table structure for table `sync_histories`
--

DROP TABLE IF EXISTS `sync_histories`;
CREATE TABLE `sync_histories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL COMMENT 'inprogress,',
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
