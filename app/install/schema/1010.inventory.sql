CREATE TABLE `inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `upc` varchar(17) DEFAULT NULL,
  `descr` varchar(300) DEFAULT NULL,
  `purchase_price` double DEFAULT NULL,
  `cost` double DEFAULT NULL,
  `is_taxable` boolean DEFAULT NULL,
  `item_type_table` varchar(100) DEFAULT NULL,
  `item_type_lookup` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `is_qty` boolean DEFAULT '1',
  `do_notify_low_qty` boolean DEFAULT '0',
  `low_qty` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES org_entities (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
