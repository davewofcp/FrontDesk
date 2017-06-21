CREATE TABLE `inventory_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `org_entities__id__dest` int(10) unsigned DEFAULT NULL,
  `inventory__id__dest` int(10) unsigned DEFAULT NULL,
  `inventory_item_number_dest` int(10) unsigned DEFAULT NULL,
  `inventory__id__orig` int(10) unsigned DEFAULT NULL,
  `inventory_item_number_orig` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT '1',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `varref_status` int(10) unsigned DEFAULT '0',
  `org_entities__id__orig` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id__dest`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id__dest`) REFERENCES `inventory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`inventory__id__orig`) REFERENCES `inventory` (`id`) ON DELETE SET NULL,
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
-- org_entities__id__orig is the store receiving the request (origin of the item requested)
--
-- org_entities__id__dest is the requesting store (destination of the item requested)
--
-- DO NOT set repeat foreign key references to the following parent table columns to have ON UPDATE CASCADE:
--   `org_entities` (`id`)
--   `inventory` (`id`)
-- repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--