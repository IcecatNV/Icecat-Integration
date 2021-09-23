
DROP TABLE IF EXISTS `icecat_user_login`;
CREATE TABLE `icecat_user_login` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `icecat_user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pim_user_id` int(5) NOT NULL,
  `login_status` int(2) DEFAULT 1,
  `lastactivity_time` datetime DEFAULT NULL,
  `creation_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;