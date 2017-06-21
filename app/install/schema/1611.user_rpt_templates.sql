CREATE TABLE `user_rpt_templates` (
	`template_id` int(10) unsigned AUTO_INCREMENT,
	`template_name` varchar(255) DEFAULT NULL,
	`created_by` int(10) unsigned DEFAULT NULL,
	`created_ts` timestamp DEFAULT CURRENT_TIMESTAMP,
	`column_data` text,
	`point_value` double DEFAULT NULL,
	PRIMARY KEY (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--