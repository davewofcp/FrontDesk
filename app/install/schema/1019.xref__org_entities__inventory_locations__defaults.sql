CREATE TABLE `xref__org_entities__inventory_locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  `inventory_locations__id` int(10) unsigned DEFAULT NULL,
  `is_default` boolean DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory_locations__id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--