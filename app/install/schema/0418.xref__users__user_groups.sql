CREATE TABLE `xref__users__user_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `user_groups__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_groups__id`) REFERENCES `user_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--