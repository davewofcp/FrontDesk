CREATE TABLE `invoice_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoices__id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `cost` double DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `is_taxable` boolean DEFAULT NULL,
  `from_table` varchar(50) DEFAULT NULL,
  `from_key_name` varchar(50) DEFAULT NULL,
  `from_key` int(10) unsigned DEFAULT NULL,
  `writeback` varchar(50) DEFAULT NULL,
  `is_heading` boolean DEFAULT NULL,
  `grp` varchar(10) DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`invoices__id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
