CREATE TABLE `inventory_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inventory__id` int(10) unsigned DEFAULT NULL,
  `notes` text,
  `sn` varchar(50) DEFAULT NULL,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `varref_status` int(10) unsigned DEFAULT '0',
  `in_store_location` int(10) unsigned DEFAULT NULL,
  `item_type_table` varchar(100) DEFAULT NULL,
  `item_table_lookup` int(10) unsigned DEFAULT NULL,
  `is_in_transit` boolean DEFAULT '0',
  `org_entities__id` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory__id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY (`issues__id`),
  FOREIGN KEY (`in_store_location`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- used unique key instead of foreign key constraint for `issues__id`
-- since it would break module dependancy chain and the column will be factored out
-- later by using an xref table in the 'issues' module
--
