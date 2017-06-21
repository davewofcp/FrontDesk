CREATE TABLE `inventory_changes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inventory__id` int(10) unsigned DEFAULT NULL,
  `inventory_item_number` int(10) unsigned DEFAULT NULL,
  `users__id` int(10) unsigned DEFAULT NULL,
  `varref_change_code` int(10) unsigned DEFAULT '0',
  `qty` int(10) unsigned DEFAULT NULL,
  `in_store_location` int(10) unsigned DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `descr` text,
  `varref_status` int(10) unsigned DEFAULT NULL,
  `reason` text,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory__id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`in_store_location`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- application internal data structure references are prefixed by type:
--  * 'mapref_'   = maps, arrays, matrices, dictionaries, lists, heaps, stacks, etc. (all are PHP 'array' type)
--  * 'varref_'   = variables
--  * 'objref_'   = objects
--  * 'constref_' = constants
--  * 'fnref_'    = functions, object or class methods
--