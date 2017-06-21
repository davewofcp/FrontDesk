CREATE TABLE `calendar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `start` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `rec_end` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `descr` text,
  `users__id__target` int(10) unsigned DEFAULT NULL,
  `users__id__created` int(10) unsigned DEFAULT NULL,
  `is_recurring` boolean DEFAULT NULL,
  `rec_type` varchar(10) DEFAULT NULL,
  `event_type` int(10) unsigned DEFAULT NULL,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `parent` int(10) unsigned DEFAULT NULL,
  `users__id__updated` int(10) unsigned DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id__target`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__created`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`parent`) REFERENCES `calendar` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__updated`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- note: `rec_type` stands for recurrence type and is in the format
--       {day|week|month}_N where N is 1..6
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--