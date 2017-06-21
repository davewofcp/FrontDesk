CREATE TABLE `issue_inv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `inventory__id` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT 0,
  `do_add` boolean DEFAULT NULL,
  `inventory_items__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory_items__id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--