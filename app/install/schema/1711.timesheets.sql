CREATE TABLE `timesheets` (
	`event_id` int(10) unsigned AUTO_INCREMENT,
	`start` date DEFAULT NULL,
	`start_time` time DEFAULT NULL,
	`rec_end` date DEFAULT NULL,
	`end_time` time DEFAULT NULL,
	`name` varchar(50) DEFAULT NULL,
	`descr` text,
	`user_id` int(10) unsigned DEFAULT NULL,
	`created_by` int(10) unsigned DEFAULT NULL,
	`recurring` boolean DEFAULT NULL,
	`rec_type` varchar(10) DEFAULT NULL,
	`parent` int(10) unsigned DEFAULT NULL,
	`updated_by` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY (`event_id`),
	FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent`) REFERENCES `timesheets` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--