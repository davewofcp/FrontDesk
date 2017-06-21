CREATE TABLE `xref__org_entities__org_structs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  `org_structs__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_entities__id`) REFERENCES org_entities (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_structs__id`) REFERENCES org_structs (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--