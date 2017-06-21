CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `amt` double DEFAULT NULL,
  `toi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `amt_paid` double DEFAULT '0',
  `emailed` date DEFAULT NULL,
  `users__id__sale` int(10) unsigned DEFAULT NULL,
  `ts_paid` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `customer_accounts__id` int(10) unsigned DEFAULT NULL,
  `tax` double DEFAULT 0,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__sale`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`customer_accounts__id`) REFERENCES `customer_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
