CREATE TABLE `inventory_transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inventory__id__orig` int(10) unsigned DEFAULT NULL,
  `inventory_item_number_orig` int(10) unsigned DEFAULT NULL,
  `users__id__orig` int(10) unsigned DEFAULT NULL,
  `org_entities__id__dest` int(10) unsigned DEFAULT NULL,
  `inventory__id__dest` int(10) unsigned DEFAULT NULL,
  `inventory_item_number_dest` int(10) unsigned DEFAULT NULL,
  `users__id__dest` int(10) unsigned DEFAULT NULL,
  `inventory_name_dest` varchar(100) DEFAULT NULL,
  `is_incoming` boolean DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `varref_status` int(10) unsigned DEFAULT NULL,
  `payload` text,
  `ts_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ts_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_completed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `inventory_transfers__id__dest` int(10) unsigned DEFAULT NULL,
  `org_entities__id__orig` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory__id__orig`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__orig`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id__dest`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id__dest`) REFERENCES `inventory` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`users__id__dest`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`inventory_transfers__id__dest`) REFERENCES `inventory_transfers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id__orig`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- application internal data structure references are prefixed by type:
--  * 'mapref_'   = maps, arrays, matrices, dictionaries, lists, heaps, stacks, etc. (all are PHP 'array' type)
--  * 'varref_'   = variables
--  * 'objref_'   = objects
--  * 'constref_' = constants
--  * 'fnref_'    = functions, object or class methods
--
-- org_entities__id__orig is the store sending the item (origin of the item requested)
--
-- org_entities__id__dest is the store receiving the item (destination of the item requested)
--
-- DO NOT set repeat foreign key references to the following parent table columns to have ON UPDATE CASCADE:
--   `inventory` (`id`)
--   `users` (`id`)
--   `org_entities` (`id`)
-- repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--