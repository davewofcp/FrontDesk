CREATE TABLE `issues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customers__id` int(10) unsigned DEFAULT '0',
  `varref_status` int(10) unsigned DEFAULT NULL,
  `device_id` int(10) unsigned DEFAULT '0',
  `services__id` int(10) unsigned DEFAULT NULL,
  `varref_issue_type` int(10) unsigned DEFAULT NULL,
  `savedfiles` varchar(200) DEFAULT NULL,
  `troubledesc` text,
  `intake_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `users__id__intake` int(10) unsigned DEFAULT NULL,
  `users__id__assigned` int(10) unsigned DEFAULT NULL,
  `quote_price` double DEFAULT NULL,
  `do_price` double DEFAULT NULL,
  `final_summary` text,
  `invoices__id` int(10) unsigned DEFAULT NULL,
  `is_resolved` boolean DEFAULT '0',
  `is_deleted` boolean DEFAULT '0',
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `subtotal` double DEFAULT NULL,
  `issue_step` varchar(200) DEFAULT NULL,
  `issue_step_done` varchar(200) DEFAULT NULL,
  `customer_accounts__id` int(10) unsigned DEFAULT NULL,
  `diagnosis` text,
  `last_status_chg` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `services` text,
  `service_steps` text,
  `last_step_ts` timestamp DEFAULT '0000-00-00 00:00:00',
  `has_charger` boolean DEFAULT '0',
  `check_notes` boolean DEFAULT '0',
  `warranty_status` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`services__id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__intake`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__assigned`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`invoices__id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`customer_accounts__id`) REFERENCES `customer_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- left `device_id` alone, since it's foreign target will change with later refactoring of modules
-- however, in the meantime, removed the foreign key constraint so 'inventory' module can be free
-- to restructure to accommodate abstracted and pluggable item types (separate item type tables)
--
-- application internal data structure references are prefixed by type:
--  * 'mapref_'   = maps, arrays, matrices, dictionaries, lists, heaps, stacks, etc. (all are PHP 'array' type)
--  * 'varref_'   = variables
--  * 'objref_'   = objects
--  * 'constref_' = constants
--  * 'fnref_'    = functions, object or class methods
--
-- also, will need to later remove `invoices__id` to a `xref__inventory__issues` table in the inventory
-- module to remove circular dependancy arising from needing `issue_labor` for cron account invoices
-- and let us flip the module order to have issues first and invoices after it, which is much better
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--