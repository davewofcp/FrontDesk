CREATE TABLE `modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(30) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `version` double DEFAULT NULL,
  `is_default` boolean DEFAULT NULL,
  `in_nav` boolean DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
