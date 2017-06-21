CREATE TABLE `customer_dupes` (
  `id` int(10) unsigned NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(60) DEFAULT NULL,
  `is_male` boolean DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `apt` varchar(10) DEFAULT '',
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(3) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `postcode` varchar(12) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone_home` varchar(20) DEFAULT NULL,
  `phone_cell` varchar(20) DEFAULT NULL,
  `referral` varchar(150) DEFAULT NULL,
  `is_subscribed` boolean DEFAULT '1',
  `v_address` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
