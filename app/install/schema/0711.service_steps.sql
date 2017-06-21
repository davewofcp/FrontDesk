CREATE TABLE `service_steps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `services__id` int(10) unsigned DEFAULT NULL,
  `order` int(5) unsigned DEFAULT NULL,
  `step` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`services__id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
