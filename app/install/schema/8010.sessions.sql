CREATE TABLE `sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `remote_addr` varchar(45) DEFAULT NULL,
  `last` datetime DEFAULT NULL,
  `customers__id` int(10) unsigned DEFAULT '0',
  `inventory_items__id` int(10) unsigned DEFAULT '0',
  `issues__id` int(10) unsigned DEFAULT NULL,
  `issue_filter` varchar(30) DEFAULT NULL,
  `customer_ts` datetime DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory_items__id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
--
-- keeping ON DELETE CASCADE for foreign key constraint `users` (`id`) to delete session when user is deleted
--