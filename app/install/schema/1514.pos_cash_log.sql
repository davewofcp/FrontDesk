CREATE TABLE `pos_cash_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `amt` double DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_reset` boolean DEFAULT '0',
  `pos_transactions__id` int(10) unsigned DEFAULT NULL,
  `is_checks` boolean DEFAULT '0',
  `is_drop` boolean DEFAULT '0',
  `is_deposited` boolean DEFAULT '0',
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`pos_transactions__id`) REFERENCES `pos_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
