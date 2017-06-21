CREATE TABLE `pos_transactions` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `line_number` int(10) unsigned NOT NULL DEFAULT '0',
  `from_table` varchar(30) DEFAULT NULL,
  `from_key_name` varchar(30) DEFAULT NULL,
  `from_key` int(10) unsigned DEFAULT NULL,
  `writeback` varchar(30) DEFAULT NULL,
  `amt` double DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `users__id__sale` int(10) unsigned DEFAULT NULL,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `is_taxable` boolean DEFAULT '1',
  `is_refunded` boolean DEFAULT '0',
  `users__id__refund` int(10) unsigned DEFAULT '0',
  `tos` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tor` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paid_cash` double DEFAULT NULL,
  `paid_credit` double DEFAULT NULL,
  `paid_check` double DEFAULT NULL,
  `grp` varchar(10) DEFAULT NULL,
  `is_heading` boolean DEFAULT NULL,
  `paid_tax` double DEFAULT '0',
  `check_no` varchar(50) DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`,`line_number`),
  FOREIGN KEY (`users__id__sale`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__refund`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- Remember the pkey is both the id and line_number, so:
-- - No AUTO_INCREMENT on `id`
-- - DEFAULT on `id` must be 0 because you can't have a NULL column in the pkey
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--