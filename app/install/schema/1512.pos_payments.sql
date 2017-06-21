CREATE TABLE `pos_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `paid_cash` double DEFAULT NULL,
  `paid_check` double DEFAULT NULL,
  `paid_credit` double DEFAULT NULL,
  `top` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_to` text,
  `org_entities__id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`org_entities__id`) REFERENCES `org_entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
