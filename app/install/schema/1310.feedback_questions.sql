CREATE TABLE `feedback_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` text,
  `is_active` boolean DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
