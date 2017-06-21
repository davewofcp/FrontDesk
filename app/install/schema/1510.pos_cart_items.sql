CREATE TABLE `pos_cart_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_table` varchar(30) DEFAULT NULL,
  `from_key_name` varchar(30) DEFAULT NULL,
  `from_key` int(10) unsigned DEFAULT NULL,
  `writeback` varchar(30) DEFAULT NULL,
  `amt` double DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `users__id__sale` int(10) unsigned DEFAULT NULL,
  `is_taxable` boolean DEFAULT NULL,
  `grp` varchar(10) DEFAULT NULL,
  `is_heading` boolean DEFAULT '0',
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id__sale`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
