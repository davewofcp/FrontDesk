CREATE TABLE `org_structs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT NULL,
  `org_struct_groups__id` int(10) unsigned DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`org_struct_groups__id`) REFERENCES `org_struct_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `org_structs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--