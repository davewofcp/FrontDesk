CREATE TABLE `invoice_changes` (
	`change_id` int(10) unsigned AUTO_INCREMENT,
	`invoice_id` int(10) unsigned DEFAULT NULL,
	`changed_by` int(10) unsigned DEFAULT NULL,
	`change_summary` text,
	`old_amt` double DEFAULT NULL,
	`new_amt` double DEFAULT NULL,
	`ts` timestamp DEFAULT CURRENT_TIMESTAMP,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY( `change_id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--