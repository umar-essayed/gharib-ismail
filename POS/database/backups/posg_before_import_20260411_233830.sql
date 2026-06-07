-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: 127.0.0.1    Database: posg
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_action` (`action`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'login','تسجيل دخول ناجح','127.0.0.1','curl/8.7.1','2026-04-09 18:38:54'),(2,1,'login','تسجيل دخول ناجح','127.0.0.1','curl/8.7.1','2026-04-09 18:39:20'),(3,1,'sales.create','إنشاء فاتورة بيع SAL000001','127.0.0.1','curl/8.7.1','2026-04-09 18:40:05'),(4,1,'customers.receipt','سند قبض عميل #1 بقيمة 3','127.0.0.1','curl/8.7.1','2026-04-09 18:40:05'),(5,1,'suppliers.payment','سداد مورد #1 بقيمة 2','127.0.0.1','curl/8.7.1','2026-04-09 18:40:05'),(6,1,'purchase.create','إنشاء فاتورة شراء PUR000001','127.0.0.1','curl/8.7.1','2026-04-09 18:40:33'),(7,1,'purchase.update_draft','تعديل فاتورة شراء مسودة PUR000001','127.0.0.1','curl/8.7.1','2026-04-09 18:40:33'),(8,1,'purchase.create','إنشاء فاتورة شراء PUR000002','127.0.0.1','curl/8.7.1','2026-04-09 18:41:04'),(9,1,'login','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 18:43:20'),(10,1,'login','تسجيل دخول ناجح','127.0.0.1','curl/8.7.1','2026-04-09 18:59:06'),(11,1,'login','تسجيل دخول ناجح','127.0.0.1','curl/8.7.1','2026-04-09 18:59:15'),(12,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 19:13:11'),(13,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 19:13:40'),(14,1,'purchase.create','إنشاء فاتورة شراء PUR000003','::1','curl/8.7.1','2026-04-09 19:13:40'),(15,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 20:03:38'),(16,1,'logout','تسجيل خروج','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 20:08:54'),(17,1,'login','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 20:09:15'),(18,1,'sales.create','إنشاء فاتورة بيع SAL000002','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 21:04:12'),(19,1,'purchase.create','إنشاء فاتورة شراء PUR000004','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 21:17:17'),(20,1,'sales.create','إنشاء فاتورة بيع SAL000003','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-09 21:18:16'),(21,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 21:26:38'),(22,1,'sales.create','إنشاء فاتورة بيع SAL000004','::1','curl/8.7.1','2026-04-09 21:26:38'),(23,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 21:34:58'),(24,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 21:34:58'),(25,1,'sales.create','إنشاء فاتورة بيع SAL000005','::1','curl/8.7.1','2026-04-09 21:34:58'),(26,1,'login','تسجيل دخول ناجح','::1','curl/8.7.1','2026-04-09 22:30:40'),(27,1,'login','تسجيل دخول ناجح','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-11 13:30:15'),(28,1,'login','تسجيل دخول ناجح','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 21:05:10');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `table_name` varchar(120) NOT NULL,
  `record_id` varchar(64) NOT NULL,
  `operation` enum('insert','update','delete','approve','cancel','close_shift') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_table_record` (`table_name`,`record_id`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'cash_shifts','1','insert',NULL,'{\"shift_no\":\"SHF00001\"}','127.0.0.1','2026-04-09 18:40:05'),(2,1,'sales_invoices','1','insert',NULL,'{\"invoice_no\":\"SAL000001\"}','127.0.0.1','2026-04-09 18:40:05'),(3,1,'customers','1','update',NULL,'{\"action\":\"receipt\",\"amount\":3}','127.0.0.1','2026-04-09 18:40:05'),(4,1,'suppliers','1','update',NULL,'{\"action\":\"payment\",\"amount\":2}','127.0.0.1','2026-04-09 18:40:05'),(5,1,'purchase_invoices','1','insert',NULL,'{\"invoice_no\":\"PUR000001\",\"status\":\"draft\"}','127.0.0.1','2026-04-09 18:40:33'),(6,1,'purchase_invoices','1','update','{\"status\":\"draft\"}','{\"supplier_id\":1,\"grand_total\":19.5,\"paid_total\":5}','127.0.0.1','2026-04-09 18:40:33'),(7,1,'purchase_invoices','1','approve','{\"status\":\"draft\"}','{\"status\":\"approved\"}','127.0.0.1','2026-04-09 18:40:33'),(8,1,'purchase_invoices','2','insert',NULL,'{\"invoice_no\":\"PUR000002\",\"status\":\"draft\"}','127.0.0.1','2026-04-09 18:41:04'),(9,1,'purchase_invoices','2','approve','{\"status\":\"draft\"}','{\"status\":\"approved\"}','127.0.0.1','2026-04-09 18:41:04'),(10,1,'purchase_invoices','3','approve',NULL,'{\"invoice_no\":\"PUR000003\",\"status\":\"approved\"}','::1','2026-04-09 19:13:40'),(11,1,'sales_invoices','7','insert',NULL,'{\"invoice_no\":\"SAL000002\"}','127.0.0.1','2026-04-09 21:04:12'),(12,1,'products','4','insert',NULL,'{\"category_id\":\"\",\"unit_id\":\"\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":0,\"sale_price\":0,\"wholesale_price\":\"\",\"min_stock\":0,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"piece\",\"package_size\":1,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"created_by\":1}','127.0.0.1','2026-04-09 21:09:35'),(13,1,'products','4','update','{\"id\":4,\"category_id\":null,\"unit_id\":null,\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"image_path\":null,\"purchase_price\":\"0.000\",\"sale_price\":\"0.000\",\"wholesale_price\":null,\"min_stock\":\"0.000\",\"opening_stock\":\"0.000\",\"sell_type\":\"piece\",\"package_type\":\"piece\",\"package_size\":\"1.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":null,\"created_at\":\"2026-04-09 23:09:35\",\"updated_at\":\"2026-04-09 23:09:35\",\"deleted_at\":null}','{\"category_id\":\"1\",\"unit_id\":\"1\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":40,\"sale_price\":48,\"wholesale_price\":\"42\",\"min_stock\":44,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":12,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 21:11:15'),(14,1,'products','4','update','{\"id\":4,\"category_id\":1,\"unit_id\":1,\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"image_path\":null,\"purchase_price\":\"40.000\",\"sale_price\":\"48.000\",\"wholesale_price\":\"42.000\",\"min_stock\":\"44.000\",\"opening_stock\":\"0.000\",\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":\"12.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-04-09 23:09:35\",\"updated_at\":\"2026-04-09 23:11:15\",\"deleted_at\":null}','{\"category_id\":\"1\",\"unit_id\":\"1\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":4,\"sale_price\":5,\"wholesale_price\":\"4.5\",\"min_stock\":0,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":12,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 21:15:52'),(15,1,'purchase_invoices','4','approve',NULL,'{\"invoice_no\":\"PUR000004\",\"status\":\"approved\"}','127.0.0.1','2026-04-09 21:17:17'),(16,1,'sales_invoices','8','insert',NULL,'{\"invoice_no\":\"SAL000003\"}','127.0.0.1','2026-04-09 21:18:16'),(17,1,'sales_invoices','9','insert',NULL,'{\"invoice_no\":\"SAL000004\"}','::1','2026-04-09 21:26:38'),(18,1,'sales_invoices','10','insert',NULL,'{\"invoice_no\":\"SAL000005\"}','::1','2026-04-09 21:34:58'),(19,1,'products','4','update','{\"id\":4,\"category_id\":1,\"unit_id\":1,\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"image_path\":null,\"purchase_price\":\"4.000\",\"sale_price\":\"5.000\",\"wholesale_price\":\"4.500\",\"min_stock\":\"0.000\",\"opening_stock\":\"0.000\",\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":\"12.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-04-09 23:09:35\",\"updated_at\":\"2026-04-09 23:15:52\",\"deleted_at\":null}','{\"category_id\":\"1\",\"unit_id\":\"1\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":4,\"sale_price\":5,\"wholesale_price\":\"4\",\"min_stock\":0,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":12,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 22:29:52'),(20,1,'products','4','update','{\"id\":4,\"category_id\":1,\"unit_id\":1,\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"image_path\":null,\"purchase_price\":\"4.000\",\"sale_price\":\"5.000\",\"wholesale_price\":\"4.000\",\"min_stock\":\"0.000\",\"opening_stock\":\"0.000\",\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":\"12.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-04-09 23:09:35\",\"updated_at\":\"2026-04-10 00:29:52\",\"deleted_at\":null}','{\"category_id\":\"1\",\"unit_id\":\"1\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":40,\"sale_price\":48,\"wholesale_price\":\"40\",\"min_stock\":0,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":1,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 22:31:56'),(21,1,'products','4','update','{\"id\":4,\"category_id\":1,\"unit_id\":1,\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"image_path\":null,\"purchase_price\":\"40.000\",\"sale_price\":\"48.000\",\"wholesale_price\":\"40.000\",\"min_stock\":\"0.000\",\"opening_stock\":\"0.000\",\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":\"1.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":1,\"created_at\":\"2026-04-09 23:09:35\",\"updated_at\":\"2026-04-10 00:31:56\",\"deleted_at\":null}','{\"category_id\":\"1\",\"unit_id\":\"1\",\"name\":\"كوفي ميكس خمسين\",\"sku\":\"٢٦٢٥\",\"internal_code\":\"٢٦٢٥\",\"barcode\":\"٦٧٧٢٥٠٤٣٩٢٦٢٥\",\"purchase_price\":4,\"sale_price\":5,\"wholesale_price\":\"4\",\"min_stock\":0,\"opening_stock\":0,\"sell_type\":\"piece\",\"package_type\":\"box\",\"package_size\":1,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 22:32:35'),(22,1,'sales_returns','1','insert',NULL,'{\"return_no\":\"SRT000001\"}','127.0.0.1','2026-04-09 22:44:17'),(23,1,'products','2','update','{\"id\":2,\"category_id\":2,\"unit_id\":1,\"name\":\"أرز 1 كجم\",\"sku\":\"SKU-1002\",\"internal_code\":\"P-1002\",\"barcode\":\"6222222222222\",\"image_path\":null,\"purchase_price\":\"22.000\",\"sale_price\":\"27.000\",\"wholesale_price\":\"25.500\",\"min_stock\":\"10.000\",\"opening_stock\":\"50.000\",\"sell_type\":\"piece\",\"package_type\":\"piece\",\"package_size\":\"1.000\",\"track_stock\":1,\"is_active\":1,\"created_by\":1,\"updated_by\":null,\"created_at\":\"2026-04-09 20:37:06\",\"updated_at\":\"2026-04-09 20:37:06\",\"deleted_at\":null}','{\"category_id\":\"2\",\"unit_id\":\"2\",\"name\":\"أرز 1 كجم\",\"sku\":\"SKU-1002\",\"internal_code\":\"P-1002\",\"barcode\":\"6222222222222\",\"purchase_price\":22,\"sale_price\":27,\"wholesale_price\":\"25.500\",\"min_stock\":10,\"opening_stock\":0,\"sell_type\":\"weight\",\"package_type\":\"sack\",\"package_size\":25,\"track_stock\":1,\"is_active\":1,\"image_path\":null,\"updated_by\":1}','127.0.0.1','2026-04-09 23:16:25');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'الفرع الرئيسي','MAIN','العنوان الرئيسي','01000000000',1,'2026-04-09 18:37:06');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `movement_type` enum('sale','purchase_payment','customer_receipt','supplier_refund','sales_return_refund','purchase_return_receipt','deposit','withdraw','expense','adjustment') NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `amount` decimal(14,3) NOT NULL,
  `reference_table` varchar(60) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cash_movements_user` (`user_id`),
  KEY `idx_cash_movements_shift` (`shift_id`),
  KEY `idx_cash_movements_type` (`movement_type`),
  CONSTRAINT `fk_cash_movements_shift` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cash_movements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,1,1,'sale','in',8.000,'sales_invoices',1,'تحصيل فاتورة بيع','2026-04-09 18:40:05'),(2,1,1,'customer_receipt','in',3.000,'customers',1,'سند اختبار - طريقة الدفع: نقدي','2026-04-09 18:40:05'),(3,1,1,'purchase_payment','out',2.000,'suppliers',1,'سداد اختبار - طريقة الدفع: نقدي','2026-04-09 18:40:05'),(4,1,1,'purchase_payment','out',5.000,'purchase_invoices',1,'سداد شراء بعد الاعتماد','2026-04-09 18:40:33'),(5,1,1,'purchase_payment','out',5.000,'purchase_invoices',2,'سداد شراء بعد الاعتماد','2026-04-09 18:41:04'),(6,1,1,'sale','in',72.000,'sales_invoices',7,'تحصيل فاتورة بيع','2026-04-09 21:04:12'),(7,1,1,'purchase_payment','out',96.000,'purchase_invoices',4,'سداد فاتورة شراء','2026-04-09 21:17:17'),(8,1,1,'sale','in',8.000,'sales_invoices',9,'تحصيل فاتورة بيع','2026-04-09 21:26:38'),(9,1,1,'sale','in',5.000,'sales_invoices',10,'تحصيل فاتورة بيع','2026-04-09 21:34:58'),(10,1,1,'sales_return_refund','out',72.000,'sales_returns',1,'رد نقدية مرتجع بيع','2026-04-09 22:44:17');
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_shifts`
--

DROP TABLE IF EXISTS `cash_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_no` varchar(40) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `opening_balance` decimal(14,3) NOT NULL,
  `expected_balance` decimal(14,3) NOT NULL DEFAULT 0.000,
  `actual_balance` decimal(14,3) DEFAULT NULL,
  `difference` decimal(14,3) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_no` (`shift_no`),
  KEY `fk_cash_shifts_user` (`user_id`),
  KEY `idx_cash_shifts_status` (`status`),
  CONSTRAINT `fk_cash_shifts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_shifts`
--

LOCK TABLES `cash_shifts` WRITE;
/*!40000 ALTER TABLE `cash_shifts` DISABLE KEYS */;
INSERT INTO `cash_shifts` VALUES (1,'SHF00001',1,200.000,116.000,NULL,NULL,'open','2026-04-09 20:40:05',NULL,'فتح تلقائي للاختبار','2026-04-09 18:40:05');
/*!40000 ALTER TABLE `cash_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_transactions`
--

DROP TABLE IF EXISTS `customer_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `transaction_type` enum('opening','sale_invoice','payment','sales_return','adjustment') NOT NULL,
  `reference_table` varchar(60) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `debit` decimal(14,3) NOT NULL DEFAULT 0.000,
  `credit` decimal(14,3) NOT NULL DEFAULT 0.000,
  `balance_after` decimal(14,3) NOT NULL DEFAULT 0.000,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_customer_transactions_user` (`created_by`),
  KEY `idx_customer_transactions_customer_date` (`customer_id`,`created_at`),
  CONSTRAINT `fk_customer_transactions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_customer_transactions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_transactions`
--

LOCK TABLES `customer_transactions` WRITE;
/*!40000 ALTER TABLE `customer_transactions` DISABLE KEYS */;
INSERT INTO `customer_transactions` VALUES (1,1,'sale_invoice','sales_invoices',1,8.000,8.000,0.000,'فاتورة بيع',1,'2026-04-09 18:40:05'),(2,1,'payment','cash_movements',2,0.000,3.000,-3.000,'سند اختبار - طريقة الدفع: نقدي',1,'2026-04-09 18:40:05'),(3,2,'sale_invoice','sales_invoices',7,72.000,72.000,0.000,'فاتورة بيع',1,'2026-04-09 21:04:12'),(4,2,'sale_invoice','sales_invoices',8,60.000,0.000,60.000,'فاتورة بيع',1,'2026-04-09 21:18:16'),(5,1,'sale_invoice','sales_invoices',9,8.000,8.000,-3.000,'فاتورة بيع',1,'2026-04-09 21:26:38'),(6,1,'sale_invoice','sales_invoices',10,5.000,5.000,-3.000,'فاتورة بيع',1,'2026-04-09 21:34:58'),(7,2,'sales_return','sales_returns',1,0.000,72.000,-12.000,'مرتجع بيع',1,'2026-04-09 22:44:17');
/*!40000 ALTER TABLE `customer_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(14,3) NOT NULL DEFAULT 0.000,
  `credit_limit` decimal(14,3) NOT NULL DEFAULT 0.000,
  `current_balance` decimal(14,3) NOT NULL DEFAULT 0.000,
  `is_cash_customer` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customers_name` (`name`),
  KEY `idx_customers_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'عميل نقدي','0000',NULL,NULL,0.000,0.000,-3.000,1,1,'2026-04-09 18:37:06','2026-04-09 18:40:05',NULL),(2,'عميل تجزئة','01012345678',NULL,NULL,0.000,500.000,-12.000,0,1,'2026-04-09 18:37:06','2026-04-09 22:44:17',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_logins`
--

DROP TABLE IF EXISTS `failed_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_logins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_failed_logins_username` (`username`),
  KEY `idx_failed_logins_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_logins`
--

LOCK TABLES `failed_logins` WRITE;
/*!40000 ALTER TABLE `failed_logins` DISABLE KEYS */;
INSERT INTO `failed_logins` VALUES (1,'admin','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 20:59:50'),(2,'احمد','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 21:03:33'),(3,'احمد','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 21:03:49'),(4,'احمد','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 21:04:17'),(5,'احمد','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15','2026-04-11 21:04:57');
/*!40000 ALTER TABLE `failed_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_adjustment_items`
--

DROP TABLE IF EXISTS `inventory_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_adjustment_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `old_qty` decimal(14,3) NOT NULL,
  `new_qty` decimal(14,3) NOT NULL,
  `diff_qty` decimal(14,3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inventory_adjustment_items_product` (`product_id`),
  KEY `idx_inventory_adjustment_items_adj` (`inventory_adjustment_id`),
  CONSTRAINT `fk_inventory_adjustment_items_adj` FOREIGN KEY (`inventory_adjustment_id`) REFERENCES `inventory_adjustments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_adjustment_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_adjustment_items`
--

LOCK TABLES `inventory_adjustment_items` WRITE;
/*!40000 ALTER TABLE `inventory_adjustment_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_adjustment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_adjustments`
--

DROP TABLE IF EXISTS `inventory_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjust_no` varchar(40) NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `adjust_date` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjust_no` (`adjust_no`),
  KEY `fk_inventory_adjustments_warehouse` (`warehouse_id`),
  KEY `fk_inventory_adjustments_user` (`user_id`),
  CONSTRAINT `fk_inventory_adjustments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_inventory_adjustments_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_adjustments`
--

LOCK TABLES `inventory_adjustments` WRITE;
/*!40000 ALTER TABLE `inventory_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `number_sequences`
--

DROP TABLE IF EXISTS `number_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `number_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seq_key` varchar(80) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `current_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `pad_length` int(11) NOT NULL DEFAULT 6,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `seq_key` (`seq_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `number_sequences`
--

LOCK TABLES `number_sequences` WRITE;
/*!40000 ALTER TABLE `number_sequences` DISABLE KEYS */;
INSERT INTO `number_sequences` VALUES (1,'sales_invoice','SAL',5,6,'2026-04-09 21:34:58'),(2,'purchase_invoice','PUR',4,6,'2026-04-09 21:17:17'),(3,'sales_return','SRT',1,6,'2026-04-09 22:44:17'),(4,'purchase_return','PRT',0,6,'2026-04-09 18:37:06'),(5,'cash_shift','SHF',1,5,'2026-04-09 18:40:05'),(6,'inventory_adjustment','ADJ',0,6,'2026-04-09 18:37:06'),(7,'sale_hold','HLD',1,6,'2026-04-09 20:56:44');
/*!40000 ALTER TABLE `number_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'cash','نقدي',1,1,'2026-04-09 18:37:06'),(2,'card','بطاقة',0,1,'2026-04-09 18:37:06'),(3,'credit','آجل',0,1,'2026-04-09 18:37:06'),(4,'mixed','مختلط',0,1,'2026-04-09 18:37:06');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(120) NOT NULL,
  `name` varchar(190) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','عرض لوحة التحكم'),(2,'products.manage','إدارة المنتجات'),(3,'customers.manage','إدارة العملاء'),(4,'suppliers.manage','إدارة الموردين'),(5,'sales.manage','إدارة المبيعات'),(6,'purchases.manage','إدارة المشتريات'),(7,'returns.manage','إدارة المرتجعات'),(8,'inventory.manage','إدارة المخزون'),(9,'shifts.manage','إدارة الشيفتات'),(10,'cash.manage','إدارة الصندوق'),(11,'users.manage','إدارة المستخدمين'),(12,'roles.manage','إدارة الأدوار والصلاحيات'),(13,'reports.view','عرض التقارير'),(14,'settings.manage','إدارة الإعدادات'),(15,'barcode.print','طباعة ملصقات الباركود'),(16,'sales.cancel','إلغاء فاتورة بيع'),(17,'purchases.approve','اعتماد فاتورة شراء'),(18,'pos.sell','البيع عبر شاشة POS'),(19,'pos.modify_price','تعديل السعر في POS'),(20,'pos.modify_discount','تعديل الخصم في POS'),(21,'promotions.manage','إدارة العروض والخصومات');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_barcodes`
--

DROP TABLE IF EXISTS `product_barcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_barcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `barcode` varchar(80) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_barcode` (`barcode`),
  KEY `idx_product_barcodes_product` (`product_id`),
  CONSTRAINT `fk_product_barcodes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_barcodes`
--

LOCK TABLES `product_barcodes` WRITE;
/*!40000 ALTER TABLE `product_barcodes` DISABLE KEYS */;
INSERT INTO `product_barcodes` VALUES (1,1,'6221111111111',1),(2,2,'6222222222222',1),(3,3,'2800000000001',1);
/*!40000 ALTER TABLE `product_barcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'مشروبات','منتجات المشروبات',1,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL),(2,'مواد غذائية','منتجات غذائية',1,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL),(3,'منظفات','منتجات النظافة',1,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `internal_code` varchar(80) DEFAULT NULL,
  `barcode` varchar(80) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `purchase_price` decimal(14,3) NOT NULL DEFAULT 0.000,
  `sale_price` decimal(14,3) NOT NULL DEFAULT 0.000,
  `wholesale_price` decimal(14,3) DEFAULT NULL,
  `min_stock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `opening_stock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `sell_type` enum('piece','weight') NOT NULL DEFAULT 'piece',
  `package_type` enum('piece','box','kg','sack') NOT NULL DEFAULT 'piece',
  `package_size` decimal(14,3) NOT NULL DEFAULT 1.000,
  `track_stock` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  UNIQUE KEY `uq_products_internal_code` (`internal_code`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_unit` (`unit_id`),
  KEY `fk_products_created_by` (`created_by`),
  KEY `fk_products_updated_by` (`updated_by`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_barcode` (`barcode`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,3,'مياه معدنية 1.5 لتر','SKU-1001','P-1001','6221111111111',NULL,6.500,8.000,7.500,20.000,100.000,'piece','box',12.000,1,1,1,NULL,'2026-04-09 18:37:06','2026-04-09 19:13:40',NULL),(2,2,2,'أرز 1 كجم','SKU-1002','P-1002','6222222222222',NULL,22.000,27.000,25.500,10.000,50.000,'weight','sack',25.000,1,1,1,1,'2026-04-09 18:37:06','2026-04-09 23:16:25',NULL),(3,2,2,'تفاح ميزان','SKU-1003','P-1003','2800000000001',NULL,35.000,45.000,42.000,5.000,30.000,'weight','piece',1.000,1,1,1,NULL,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL),(4,1,1,'كوفي ميكس خمسين','٢٦٢٥','٢٦٢٥','٦٧٧٢٥٠٤٣٩٢٦٢٥',NULL,4.000,5.000,4.000,0.000,0.000,'piece','box',1.000,1,1,1,1,'2026-04-09 21:09:35','2026-04-09 22:32:35',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(180) NOT NULL,
  `discount_type` enum('percent','fixed','price') NOT NULL,
  `discount_value` decimal(14,3) NOT NULL DEFAULT 0.000,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_promotions_created_by` (`created_by`),
  KEY `fk_promotions_updated_by` (`updated_by`),
  KEY `idx_promotions_product_dates` (`product_id`,`start_date`,`end_date`),
  KEY `idx_promotions_active_dates` (`is_active`,`start_date`,`end_date`),
  CONSTRAINT `fk_promotions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_promotions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promotions_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `qty` decimal(14,3) NOT NULL,
  `purchase_unit` enum('piece','box','kg','sack') NOT NULL DEFAULT 'piece',
  `stock_qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(14,3) NOT NULL,
  `discount_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `line_total` decimal(14,3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_purchase_items_unit` (`unit_id`),
  KEY `idx_purchase_items_invoice` (`purchase_invoice_id`),
  KEY `idx_purchase_items_product` (`product_id`),
  CONSTRAINT `fk_purchase_items_invoice` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_purchase_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_items`
--

LOCK TABLES `purchase_invoice_items` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_items` DISABLE KEYS */;
INSERT INTO `purchase_invoice_items` VALUES (2,1,1,3,3.000,'piece',3.000,6.500,0.000,0.000,19.500,'2026-04-09 18:40:33'),(3,2,1,3,2.000,'piece',2.000,6.500,0.000,0.000,13.000,'2026-04-09 18:41:04'),(4,3,1,3,2.000,'box',24.000,78.000,0.000,0.000,156.000,'2026-04-09 19:13:40'),(5,4,4,1,2.000,'box',24.000,48.000,0.000,0.000,96.000,'2026-04-09 21:17:17');
/*!40000 ALTER TABLE `purchase_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_payments`
--

DROP TABLE IF EXISTS `purchase_invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,3) NOT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_purchase_payments_invoice` (`purchase_invoice_id`),
  KEY `fk_purchase_payments_method` (`payment_method_id`),
  CONSTRAINT `fk_purchase_payments_invoice` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_payments`
--

LOCK TABLES `purchase_invoice_payments` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_payments` DISABLE KEYS */;
INSERT INTO `purchase_invoice_payments` VALUES (1,1,1,5.000,NULL,'2026-04-09 18:40:33'),(2,1,1,5.000,NULL,'2026-04-09 18:40:33'),(3,2,1,5.000,NULL,'2026-04-09 18:41:04'),(4,4,1,96.000,NULL,'2026-04-09 21:17:17');
/*!40000 ALTER TABLE `purchase_invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(40) NOT NULL,
  `supplier_invoice_no` varchar(80) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `invoice_date` datetime NOT NULL,
  `status` enum('draft','approved','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,3) NOT NULL,
  `discount_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `grand_total` decimal(14,3) NOT NULL,
  `paid_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `due_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `payment_status` enum('paid','partial','due') NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_purchase_user` (`user_id`),
  KEY `fk_purchase_warehouse` (`warehouse_id`),
  KEY `fk_purchase_payment_method` (`payment_method_id`),
  KEY `fk_purchase_approved_by` (`approved_by`),
  KEY `idx_purchase_invoices_date` (`invoice_date`),
  KEY `idx_purchase_invoices_supplier` (`supplier_id`),
  CONSTRAINT `fk_purchase_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_purchase_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoices`
--

LOCK TABLES `purchase_invoices` WRITE;
/*!40000 ALTER TABLE `purchase_invoices` DISABLE KEYS */;
INSERT INTO `purchase_invoices` VALUES (1,'PUR000001','TEST-001-U',1,1,1,'2026-04-09 20:40:33','approved',19.500,0.000,0.000,19.500,5.000,14.500,'partial',1,'2026-04-09 20:40:33',1,'تعديل مسودة','2026-04-09 18:40:33','2026-04-09 18:40:33'),(2,'PUR000002','TEST-002',1,1,1,'2026-04-09 20:41:04','approved',13.000,0.000,0.000,13.000,5.000,8.000,'partial',1,'2026-04-09 20:41:04',1,'','2026-04-09 18:41:04','2026-04-09 18:41:04'),(3,'PUR000003','TEST-BOX-01',1,1,1,'2026-04-09 21:13:40','approved',156.000,0.000,0.000,156.000,0.000,156.000,'due',1,'2026-04-09 21:13:40',1,'package conversion test','2026-04-09 19:13:40','2026-04-09 19:13:40'),(4,'PUR000004','',2,1,1,'2026-04-09 23:17:17','approved',96.000,0.000,0.000,96.000,96.000,0.000,'paid',1,'2026-04-09 23:17:17',1,'','2026-04-09 21:17:17','2026-04-09 21:17:17');
/*!40000 ALTER TABLE `purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_return_items`
--

DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `purchase_invoice_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `stock_qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(14,3) NOT NULL,
  `discount_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `line_total` decimal(14,3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_purchase_return_items_purchase_item` (`purchase_invoice_item_id`),
  KEY `fk_purchase_return_items_product` (`product_id`),
  KEY `idx_purchase_return_items_return` (`purchase_return_id`),
  CONSTRAINT `fk_purchase_return_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_purchase_return_items_purchase_item` FOREIGN KEY (`purchase_invoice_item_id`) REFERENCES `purchase_invoice_items` (`id`),
  CONSTRAINT `fk_purchase_return_items_return` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_return_items`
--

LOCK TABLES `purchase_return_items` WRITE;
/*!40000 ALTER TABLE `purchase_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(40) NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `return_date` datetime NOT NULL,
  `subtotal` decimal(14,3) NOT NULL,
  `discount_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `grand_total` decimal(14,3) NOT NULL,
  `refund_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `fk_purchase_returns_invoice` (`purchase_invoice_id`),
  KEY `fk_purchase_returns_user` (`user_id`),
  KEY `fk_purchase_returns_supplier` (`supplier_id`),
  KEY `fk_purchase_returns_payment_method` (`payment_method_id`),
  KEY `idx_purchase_returns_date` (`return_date`),
  CONSTRAINT `fk_purchase_returns_invoice` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`),
  CONSTRAINT `fk_purchase_returns_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_returns_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_purchase_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(2,1),(2,5),(2,7),(2,8),(2,10),(2,18),(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),(3,8),(3,9),(3,10),(3,13),(3,15),(3,17),(3,18),(3,21);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','مدير النظام الكامل',1,1,'2026-04-09 18:37:06'),(2,'cashier','كاشير',1,1,'2026-04-09 18:37:06'),(3,'manager','مدير الفرع',1,1,'2026-04-09 18:37:06');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_suspensions`
--

DROP TABLE IF EXISTS `sale_suspensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_suspensions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hold_no` varchar(40) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hold_no` (`hold_no`),
  KEY `fk_sale_suspensions_user` (`user_id`),
  KEY `fk_sale_suspensions_customer` (`customer_id`),
  CONSTRAINT `fk_sale_suspensions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sale_suspensions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_suspensions`
--

LOCK TABLES `sale_suspensions` WRITE;
/*!40000 ALTER TABLE `sale_suspensions` DISABLE KEYS */;
INSERT INTO `sale_suspensions` VALUES (1,'HLD000001',1,2,'[{\"product_id\":3,\"name\":\"تفاح ميزان\",\"qty\":1,\"sale_unit\":\"kg\",\"unit_price\":45,\"discount_amount\":0,\"tax_amount\":0}]','2026-04-09 20:56:44');
/*!40000 ALTER TABLE `sale_suspensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_items`
--

DROP TABLE IF EXISTS `sales_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `barcode` varchar(80) DEFAULT NULL,
  `qty` decimal(14,3) NOT NULL,
  `sale_unit` enum('piece','box','kg','sack') NOT NULL DEFAULT 'piece',
  `stock_qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(14,3) NOT NULL,
  `discount_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `line_total` decimal(14,3) NOT NULL,
  `cost_price` decimal(14,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sales_items_unit` (`unit_id`),
  KEY `idx_sales_items_invoice` (`sales_invoice_id`),
  KEY `idx_sales_items_product` (`product_id`),
  CONSTRAINT `fk_sales_items_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sales_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_items`
--

LOCK TABLES `sales_invoice_items` WRITE;
/*!40000 ALTER TABLE `sales_invoice_items` DISABLE KEYS */;
INSERT INTO `sales_invoice_items` VALUES (1,1,1,3,'6221111111111',1.000,'piece',1.000,8.000,0.000,0.000,8.000,6.500,'2026-04-09 18:40:05'),(2,7,2,1,'6222222222222',1.000,'piece',1.000,27.000,0.000,0.000,27.000,22.000,'2026-04-09 21:04:12'),(3,7,3,2,'2800000000001',1.000,'kg',1.000,45.000,0.000,0.000,45.000,35.000,'2026-04-09 21:04:12'),(4,8,4,1,'٦٧٧٢٥٠٤٣٩٢٦٢٥',1.000,'box',12.000,60.000,0.000,0.000,60.000,4.000,'2026-04-09 21:18:16'),(5,9,1,3,'6221111111111',1.000,'piece',1.000,8.000,0.000,0.000,8.000,6.500,'2026-04-09 21:26:38'),(6,10,1,3,'6221111111111',1.000,'piece',1.000,5.000,0.000,0.000,5.000,6.500,'2026-04-09 21:34:58');
/*!40000 ALTER TABLE `sales_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_payments`
--

DROP TABLE IF EXISTS `sales_invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,3) NOT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sales_payments_invoice` (`sales_invoice_id`),
  KEY `fk_sales_payments_method` (`payment_method_id`),
  CONSTRAINT `fk_sales_payments_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_payments`
--

LOCK TABLES `sales_invoice_payments` WRITE;
/*!40000 ALTER TABLE `sales_invoice_payments` DISABLE KEYS */;
INSERT INTO `sales_invoice_payments` VALUES (1,1,1,5.000,NULL,'2026-04-09 18:40:05'),(2,1,2,3.000,NULL,'2026-04-09 18:40:05'),(3,7,1,72.000,NULL,'2026-04-09 21:04:12'),(4,9,1,8.000,NULL,'2026-04-09 21:26:38'),(5,10,1,5.000,NULL,'2026-04-09 21:34:58');
/*!40000 ALTER TABLE `sales_invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoices`
--

DROP TABLE IF EXISTS `sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(40) NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_date` datetime NOT NULL,
  `status` enum('posted','cancelled') NOT NULL DEFAULT 'posted',
  `subtotal` decimal(14,3) NOT NULL,
  `discount_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `grand_total` decimal(14,3) NOT NULL,
  `paid_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `due_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `payment_status` enum('paid','partial','due') NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_sales_invoices_branch` (`branch_id`),
  KEY `fk_sales_invoices_warehouse` (`warehouse_id`),
  KEY `fk_sales_invoices_shift` (`shift_id`),
  KEY `fk_sales_invoices_user` (`user_id`),
  KEY `fk_sales_invoices_payment_method` (`payment_method_id`),
  KEY `fk_sales_invoices_cancelled_by` (`cancelled_by`),
  KEY `idx_sales_invoices_date` (`invoice_date`),
  KEY `idx_sales_invoices_customer` (`customer_id`),
  CONSTRAINT `fk_sales_invoices_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_invoices_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_invoices_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_invoices_shift` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sales_invoices_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoices`
--

LOCK TABLES `sales_invoices` WRITE;
/*!40000 ALTER TABLE `sales_invoices` DISABLE KEYS */;
INSERT INTO `sales_invoices` VALUES (1,'SAL000001',1,1,1,1,1,'2026-04-09 20:40:05','posted',8.000,0.000,0.000,8.000,8.000,0.000,'paid',4,'اختبار دفع مختلط','2026-04-09 18:40:05','2026-04-09 18:40:05',NULL,NULL),(7,'SAL000002',1,1,1,1,2,'2026-04-09 23:04:12','posted',72.000,0.000,0.000,72.000,72.000,0.000,'paid',1,'','2026-04-09 21:04:12','2026-04-09 21:04:12',NULL,NULL),(8,'SAL000003',1,1,1,1,2,'2026-04-09 23:18:16','posted',60.000,0.000,0.000,60.000,0.000,60.000,'due',1,'','2026-04-09 21:18:16','2026-04-09 21:18:16',NULL,NULL),(9,'SAL000004',1,1,1,1,1,'2026-04-09 23:26:38','posted',8.000,0.000,0.000,8.000,8.000,0.000,'paid',1,'quick test','2026-04-09 21:26:38','2026-04-09 21:26:38',NULL,NULL),(10,'SAL000005',1,1,1,1,1,'2026-04-09 23:34:58','posted',5.000,0.000,0.000,5.000,5.000,0.000,'paid',1,'unit test','2026-04-09 21:34:58','2026-04-09 21:34:58',NULL,NULL);
/*!40000 ALTER TABLE `sales_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `stock_qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(14,3) NOT NULL,
  `discount_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_amount` decimal(14,3) NOT NULL DEFAULT 0.000,
  `line_total` decimal(14,3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sales_return_items_sales_item` (`sales_invoice_item_id`),
  KEY `fk_sales_return_items_product` (`product_id`),
  KEY `idx_sales_return_items_return` (`sales_return_id`),
  CONSTRAINT `fk_sales_return_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sales_return_items_return` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_return_items_sales_item` FOREIGN KEY (`sales_invoice_item_id`) REFERENCES `sales_invoice_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
INSERT INTO `sales_return_items` VALUES (1,1,2,2,1.000,1.000,27.000,0.000,0.000,27.000,'2026-04-09 22:44:17'),(2,1,3,3,1.000,1.000,45.000,0.000,0.000,45.000,'2026-04-09 22:44:17');
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(40) NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `return_date` datetime NOT NULL,
  `subtotal` decimal(14,3) NOT NULL,
  `discount_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `tax_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `grand_total` decimal(14,3) NOT NULL,
  `refund_total` decimal(14,3) NOT NULL DEFAULT 0.000,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `fk_sales_returns_invoice` (`sales_invoice_id`),
  KEY `fk_sales_returns_user` (`user_id`),
  KEY `fk_sales_returns_customer` (`customer_id`),
  KEY `fk_sales_returns_shift` (`shift_id`),
  KEY `fk_sales_returns_payment_method` (`payment_method_id`),
  KEY `idx_sales_returns_date` (`return_date`),
  CONSTRAINT `fk_sales_returns_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_returns_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`),
  CONSTRAINT `fk_sales_returns_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_returns_shift` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
INSERT INTO `sales_returns` VALUES (1,'SRT000001',7,1,2,1,'2026-04-10 00:44:17',72.000,0.000,0.000,72.000,72.000,1,'','2026-04-09 22:44:17');
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(120) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('allow_negative_stock','0','2026-04-09 18:37:06'),('company_address','ميدان الناصريه القديمه','2026-04-09 20:06:21'),('company_name','سوبر ماركت الناصرية','2026-04-09 20:06:21'),('company_phone','01286868676','2026-04-09 20:05:45'),('currency','ج.م','2026-04-09 18:37:06'),('default_branch_id','1','2026-04-09 21:04:12'),('default_warehouse_id','1','2026-04-09 21:04:12'),('invoice_footer','Glory Tech مهندس احمد ابو المجد ٠١٠٣٢١٦٢١٦٣','2026-04-09 20:06:29'),('logo_path','','2026-04-09 18:37:06'),('low_stock_alert_enabled','','2026-04-09 20:05:45'),('receipt_print_mode','thermal','2026-04-09 18:37:06'),('require_shift_for_sale','1','2026-04-09 18:37:06'),('tax_number','123456789','2026-04-09 18:37:06');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `movement_type` enum('initial','sale','purchase','sales_return','purchase_return','adjustment_in','adjustment_out') NOT NULL,
  `qty_in` decimal(14,3) NOT NULL DEFAULT 0.000,
  `qty_out` decimal(14,3) NOT NULL DEFAULT 0.000,
  `balance_after` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_cost` decimal(14,3) NOT NULL DEFAULT 0.000,
  `reference_table` varchar(60) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_stock_movements_warehouse` (`warehouse_id`),
  KEY `fk_stock_movements_user` (`created_by`),
  KEY `idx_stock_movements_product_date` (`product_id`,`created_at`),
  KEY `idx_stock_movements_reference` (`reference_table`,`reference_id`),
  CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_stock_movements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stock_movements_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,1,'initial',100.000,0.000,100.000,6.500,'products',1,'رصيد افتتاحي',1,'2026-04-09 18:37:06'),(2,1,2,'initial',50.000,0.000,50.000,22.000,'products',2,'رصيد افتتاحي',1,'2026-04-09 18:37:06'),(3,1,3,'initial',30.000,0.000,30.000,35.000,'products',3,'رصيد افتتاحي',1,'2026-04-09 18:37:06'),(4,1,1,'sale',0.000,1.000,99.000,6.500,'sales_invoices',1,'بيع فاتورة SAL000001',1,'2026-04-09 18:40:05'),(5,1,1,'purchase',3.000,0.000,102.000,6.500,'purchase_invoices',1,'اعتماد شراء PUR000001',1,'2026-04-09 18:40:33'),(6,1,1,'purchase',2.000,0.000,104.000,6.500,'purchase_invoices',2,'اعتماد شراء PUR000002',1,'2026-04-09 18:41:04'),(7,1,1,'purchase',24.000,0.000,128.000,78.000,'purchase_invoices',3,'اعتماد شراء PUR000003',1,'2026-04-09 19:13:40'),(8,1,2,'sale',0.000,1.000,49.000,22.000,'sales_invoices',7,'بيع فاتورة SAL000002',1,'2026-04-09 21:04:12'),(9,1,3,'sale',0.000,1.000,29.000,35.000,'sales_invoices',7,'بيع فاتورة SAL000002',1,'2026-04-09 21:04:12'),(10,1,4,'purchase',24.000,0.000,24.000,48.000,'purchase_invoices',4,'اعتماد شراء PUR000004',1,'2026-04-09 21:17:17'),(11,1,4,'sale',0.000,12.000,12.000,4.000,'sales_invoices',8,'بيع فاتورة SAL000003',1,'2026-04-09 21:18:16'),(12,1,1,'sale',0.000,1.000,127.000,6.500,'sales_invoices',9,'بيع فاتورة SAL000004',1,'2026-04-09 21:26:38'),(13,1,1,'sale',0.000,1.000,126.000,6.500,'sales_invoices',10,'بيع فاتورة SAL000005',1,'2026-04-09 21:34:58'),(14,1,2,'sales_return',1.000,0.000,50.000,22.000,'sales_returns',1,'مرتجع بيع SRT000001',1,'2026-04-09 22:44:17'),(15,1,3,'sales_return',1.000,0.000,30.000,35.000,'sales_returns',1,'مرتجع بيع SRT000001',1,'2026-04-09 22:44:17');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_transactions`
--

DROP TABLE IF EXISTS `supplier_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `transaction_type` enum('opening','purchase_invoice','payment','purchase_return','adjustment') NOT NULL,
  `reference_table` varchar(60) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `debit` decimal(14,3) NOT NULL DEFAULT 0.000,
  `credit` decimal(14,3) NOT NULL DEFAULT 0.000,
  `balance_after` decimal(14,3) NOT NULL DEFAULT 0.000,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_supplier_transactions_user` (`created_by`),
  KEY `idx_supplier_transactions_supplier_date` (`supplier_id`,`created_at`),
  CONSTRAINT `fk_supplier_transactions_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_supplier_transactions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_transactions`
--

LOCK TABLES `supplier_transactions` WRITE;
/*!40000 ALTER TABLE `supplier_transactions` DISABLE KEYS */;
INSERT INTO `supplier_transactions` VALUES (1,1,'payment','cash_movements',3,0.000,2.000,-2.000,'سداد اختبار - طريقة الدفع: نقدي',1,'2026-04-09 18:40:05'),(2,1,'purchase_invoice','purchase_invoices',1,19.500,5.000,12.500,'اعتماد فاتورة شراء',1,'2026-04-09 18:40:33'),(3,1,'purchase_invoice','purchase_invoices',2,13.000,5.000,20.500,'اعتماد فاتورة شراء',1,'2026-04-09 18:41:04'),(4,1,'purchase_invoice','purchase_invoices',3,156.000,0.000,176.500,'فاتورة شراء',1,'2026-04-09 19:13:40'),(5,2,'purchase_invoice','purchase_invoices',4,96.000,96.000,0.000,'فاتورة شراء',1,'2026-04-09 21:17:17');
/*!40000 ALTER TABLE `supplier_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(14,3) NOT NULL DEFAULT 0.000,
  `current_balance` decimal(14,3) NOT NULL DEFAULT 0.000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_name` (`name`),
  KEY `idx_suppliers_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'مورد عام','01099999999',NULL,NULL,0.000,176.500,1,'2026-04-09 18:37:06','2026-04-09 19:13:40',NULL),(2,'مورد مشروبات','01088888888',NULL,NULL,0.000,0.000,1,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `is_weight` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_units_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'قطعة','قطعة',0,1,'2026-04-09 18:37:06','2026-04-09 18:37:06'),(2,'كيلوجرام','كجم',1,1,'2026-04-09 18:37:06','2026-04-09 18:37:06'),(3,'لتر','لتر',0,1,'2026-04-09 18:37:06','2026-04-09 18:37:06');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `username` varchar(80) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'admin','مدير النظام','$2y$10$/L3VnXbWrIqJIf1h19AQS.NV0oaz0Wpantx1pUWcK4u.S0KyqbGG2','admin@local.test','01000000000',1,'2026-04-11 23:05:10','::1','2026-04-09 18:37:06','2026-04-11 21:05:10',NULL),(2,2,'cashier','كاشير افتراضي','$2y$10$/L3VnXbWrIqJIf1h19AQS.NV0oaz0Wpantx1pUWcK4u.S0KyqbGG2','cashier@local.test','01000000001',1,NULL,NULL,'2026-04-09 18:37:06','2026-04-09 18:37:06',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_warehouses_branch` (`branch_id`),
  CONSTRAINT `fk_warehouses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,1,'المخزن الرئيسي',1,'2026-04-09 18:37:06');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-11 23:38:31
