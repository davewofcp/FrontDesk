CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  `user_roles__id` int(10) unsigned DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `salt` varchar(10) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(60) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_onsite` boolean DEFAULT NULL,
  `hourlyrate` double DEFAULT NULL,
  `notepad` text,
  `timeout` int(8) unsigned DEFAULT NULL,
  `is_disabled` boolean DEFAULT NULL,
  `rc_read` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES org_entities (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_roles__id`) REFERENCES user_roles (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
