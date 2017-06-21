CREATE TABLE `user_roles_perms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_roles__id` int(10) unsigned DEFAULT NULL,
  `module` varchar(30) DEFAULT NULL,
  `bitfield` int(11) NOT NULL DEFAULT '0',
  `last_mod` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_roles__id`) REFERENCES `user_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
