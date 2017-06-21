CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id__1` int(10) unsigned DEFAULT NULL,
  `users__id__2` int(10) unsigned DEFAULT NULL,
  `box` int(10) unsigned DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_read` boolean DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id__1`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`users__id__2`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- DO NOT set repeat foreign key reference to `users` (`id`) to have ON UPDATE CASCADE; repeat cascades would be bad;
-- as it stands, an update cascade will run once and then act like RESTRICT to prevent an infinite cascade loop
--