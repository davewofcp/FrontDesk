CREATE TABLE `recurring_tasks` (
	`task_id` int(10) unsigned AUTO_INCREMENT,
	`reset_date` date DEFAULT 0,
	`created_date` date DEFAULT 0,
	`descr` varchar(255) DEFAULT NULL,
	`done_by` text,
	`points` double DEFAULT NULL,
	`report_id` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY (`task_id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--