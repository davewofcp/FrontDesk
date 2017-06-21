CREATE TABLE `customer_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `remote_addr` varchar(45) DEFAULT NULL,
  `last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
