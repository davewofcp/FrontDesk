CREATE TABLE `issue_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issues__id` int(10) unsigned DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `amt` double DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `is_taxable` boolean DEFAULT NULL,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`issues__id`) REFERENCES `issues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- keeping ON DELETE CASCADE on foreign key constraint `issues` (`id`) so issue items are deleted with their parent issue
--