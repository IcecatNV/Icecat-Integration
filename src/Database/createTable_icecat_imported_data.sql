

DROP TABLE IF EXISTS `icecat_imported_data`;
CREATE TABLE `icecat_imported_data` (
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_be_created` tinyint(4) NOT NULL DEFAULT 1,
  `language` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_product_proccessed` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gtin` varchar(111) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_encoded` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` blob DEFAULT NULL,
  `pim_user_id` int(11) NOT NULL,
  `icecat_username` varchar(110) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_product_found` int(2) DEFAULT NULL,
  `duplicate` int(11) DEFAULT 0,
  `error` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `search_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `gtin_icecat_username_search_key_language_job_id` (`gtin`,`icecat_username`,`search_key`,`language`,`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;