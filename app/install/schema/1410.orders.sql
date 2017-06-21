CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `purchased_from` varchar(100) DEFAULT NULL,
  `order_number` varchar(100) DEFAULT NULL,
  `shipping_type` int(10) unsigned DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `subtotal` double DEFAULT NULL,
  `tax` double DEFAULT NULL,
  `carrier` int(10) unsigned DEFAULT NULL,
  `varref_status` int(10) unsigned DEFAULT NULL,
  `desc` text,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- `shipping_type` may later point to an external `shipping_types` table key
-- `carrier` may later point to an external `shipping_carriers` table key
--
-- application internal data structure references are prefixed by type:
--  * 'mapref_'   = maps, arrays, matrices, dictionaries, lists, heaps, stacks, etc. (all are PHP 'array' type)
--  * 'varref_'   = variables
--  * 'objref_'   = objects
--  * 'constref_' = constants
--  * 'fnref_'    = functions, object or class methods
--