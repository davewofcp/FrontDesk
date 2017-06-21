CREATE TABLE `calendar_views` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id` int(10) unsigned DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT NULL,
  `event_types` varchar(20) DEFAULT NULL,
  `users` text,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- may want `event_types` and `users` to be removed to xref tables for more atomic operations
--
-- keeping ON DELETE CASCADE for foreign key constraint `users` (`id`) to remove calendar view with user
--