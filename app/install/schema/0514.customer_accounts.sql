CREATE TABLE `customer_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customers__id` int(10) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `block_hours` double DEFAULT NULL,
  `block_rate` double DEFAULT NULL,
  `overage_rate` double DEFAULT NULL,
  `period` int(10) unsigned DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `is_disabled` boolean DEFAULT FALSE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customers__id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
