CREATE TABLE `xref__user_roles__user_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_roles__id` int(10) unsigned DEFAULT NULL,
  `user_groups__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_roles__id`) REFERENCES `user_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_groups__id`) REFERENCES `user_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--