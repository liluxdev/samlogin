CREATE TABLE IF NOT EXISTS `#__samlogin_authz_hist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(500) NOT NULL,
  `group` int(11) NOT NULL,
  `email` varchar(500) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `initiator` varchar(1000) NOT NULL DEFAULT 'manual',
  `timeid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `samlogin_user` (`username`(255)),
  KEY `samlogin_timeid` (`time`),
  KEY `samlogin_group` (`group`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__samlogin_authz_adv_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attrvalue` varchar(255) NOT NULL,
  `attrname` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `samlogin_unq_mapping_id` (`attrvalue`,`attrname`),
  KEY `samlogin_userid` (`userid`)
) DEFAULT CHARSET=utf8;
