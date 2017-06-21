CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orders__id` int(10) unsigned DEFAULT NULL,
  `inventory__id` int(10) unsigned DEFAULT NULL,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `cost` double DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `varref_status` int(10) unsigned DEFAULT NULL,
  `rma_number` varchar(100) DEFAULT NULL,
  `r_tracking_number` varchar(100) DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`orders__id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
