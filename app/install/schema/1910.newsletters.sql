CREATE TABLE `newsletters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users__id__created_by` int(10) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_emailed` date DEFAULT NULL,
  `emailed_to` int(10) unsigned DEFAULT '0',
  `subj` varchar(255) DEFAULT NULL,
  `msg` text,
  `html` text,
  `is_attachment` boolean DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users__id__created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- note: `emailed_to` is the number of recipients that the mail() function returned an OK status for
--