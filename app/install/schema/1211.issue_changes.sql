CREATE TABLE `issue_changes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issues__id` int(10) unsigned DEFAULT '0',
  `description` text,
  `varref_status` int(10) unsigned DEFAULT NULL,
  `tou` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `users__id` int(10) unsigned DEFAULT '0',
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)/*,
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE*/
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
