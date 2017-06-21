CREATE TABLE `issue_labor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_accounts__id` int(10) unsigned DEFAULT NULL,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `users__id` int(10) unsigned DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customer_accounts__id`) REFERENCES `customer_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
