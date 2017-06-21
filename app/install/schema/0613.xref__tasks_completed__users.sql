CREATE TABLE `xref__tasks_completed__users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tasks_completed__id` int(10) unsigned DEFAULT NULL,
  `users__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tasks_completed__id`) REFERENCES `tasks_completed` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--