CREATE TABLE `inventory_type_devices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categories__id` int(10) unsigned DEFAULT '0',
  `manufacturer` varchar(30) DEFAULT NULL,
  `model` varchar(30) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `operating_system` varchar(45) DEFAULT NULL,
  `has_charger` boolean DEFAULT NULL,
  `username` varchar(30) DEFAULT NULL,
  `password` varchar(30) DEFAULT NULL,
  `in_store_location` int(10) unsigned DEFAULT NULL,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `inventory_item_number` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`categories__id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`in_store_location`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
