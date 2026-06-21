CREATE TABLE `exhibitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ico` varchar(20) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_czech_ci NOT NULL,
  `dic` varchar(20) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `contact_name` varchar(150) COLLATE utf8mb4_czech_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_czech_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_czech_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `social_networks` text COLLATE utf8mb4_czech_ci,
  `sortiment` text COLLATE utf8mb4_czech_ci NOT NULL,
  `terms_agreed` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_price` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_ico` (`ico`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `exhibitor_festivals` (
  `exhibitor_id` int(11) NOT NULL,
  `festival_id` int(11) NOT NULL,
  `space` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '2x2, 4x2, 6x2, 8x2',
  `electricity` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT 'none, 2.5kw, 9kw, 18kw',
  `price_space` int(11) NOT NULL DEFAULT '0',
  `price_elec` int(11) NOT NULL DEFAULT '0',
  `price_total` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`exhibitor_id`,`festival_id`),
  CONSTRAINT `exhibitor_festivals_ibfk_1` FOREIGN KEY (`exhibitor_id`) REFERENCES `exhibitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;