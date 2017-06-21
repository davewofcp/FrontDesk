CREATE TABLE `tasks_completed` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`date_done` date DEFAULT NULL,
	`tasks__id` int(10) unsigned DEFAULT NULL,
	`user_ids` text,
	`points` double DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`tasks__id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
