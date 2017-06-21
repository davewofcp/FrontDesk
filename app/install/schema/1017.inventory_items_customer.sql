CREATE TABLE `inventory_items_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `inventory__id` int(10) unsigned DEFAULT NULL,
  `inventory_item_number` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `ts` timestamp DEFAULT CURRENT_TIMESTAMP,
  `unit_cost` double DEFAULT '0',
  `total_cost` double DEFAULT '0',
  `serial_numbers` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- (strike) used unique key instead of foreign key constraint for `inventory_item_number` (/strike)
-- since it seems this field is redundantly xrefing several tables, pointing out a
-- need to factored out the column later by using an xref table in the appropriate module
--
