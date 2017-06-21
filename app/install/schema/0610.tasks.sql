CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id__assigned_to` int(10) unsigned DEFAULT NULL,
  `users__id__assigned_by` int(10) unsigned DEFAULT NULL,
  `task` text,
  `due` datetime DEFAULT NULL,
  `is_completed` boolean DEFAULT NULL,
  `toc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `points` double DEFAULT 0,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id__assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--