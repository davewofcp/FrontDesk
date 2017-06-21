CREATE TABLE `user_rpt_submissions` (
	`submission_id` int(10) unsigned AUTO_INCREMENT,
	`template_id` int(10) unsigned DEFAULT NULL,
	`user_id` int(10) unsigned DEFAULT NULL,
	`submitted_ts` timestamp DEFAULT CURRENT_TIMESTAMP,
	`submitted_data` text,
	`was_viewed` boolean DEFAULT NULL,
	PRIMARY KEY (`submission_id`),
	FOREIGN KEY (`template_id`) REFERENCES `user_rpt_templates` (`template_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--