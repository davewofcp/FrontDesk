CREATE TABLE `user_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `for_table` varchar(32) DEFAULT NULL,
  `for_key` varchar(32) DEFAULT NULL,
  `note` text,
  `users__id` int(10) unsigned DEFAULT NULL,
  `note_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
