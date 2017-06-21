CREATE TABLE `reports_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `reports` varchar(15) DEFAULT NULL,
  `last_emailed` date DEFAULT NULL,
  `email_every` int(10) unsigned DEFAULT NULL,
  `org_entities_list` text DEFAULT NULL,
  `hr` int(10) unsigned DEFAULT NULL,
  `do_attach` boolean DEFAULT '0',
  `users_list` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- may want to move `org_entities_list` and `users_list` out to xref tables at some point
--
