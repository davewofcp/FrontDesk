CREATE TABLE `user_groups_perms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_groups__id` int(10) unsigned DEFAULT NULL,
  `module` varchar(30) DEFAULT NULL,
  `bitfield` int(11) NOT NULL DEFAULT '0',
  `last_mod` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_groups__id`) REFERENCES `user_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
