-- phpMyAdmin SQL Dump
-- version 4.0.0-rc1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 21, 2014 at 09:35 PM
-- Server version: 5.1.72-2
-- PHP Version: 5.3.3-7+squeeze17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `db_cworklog`
--

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE IF NOT EXISTS `company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `street` varchar(90) DEFAULT NULL,
  `street2` varchar(90) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` varchar(25) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `notes` text,
  `default_hourly_rate` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `files_log`
--

CREATE TABLE IF NOT EXISTS `files_log` (
  `work_log_id` int(11) NOT NULL,
  `feature` varchar(55) NOT NULL,
  `file` varchar(255) NOT NULL,
  `change_type` enum('file','db') NOT NULL DEFAULT 'file',
  `notes` text,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`work_log_id`,`feature`,`file`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE IF NOT EXISTS `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('bug','feature') NOT NULL,
  `date_created` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `priority` enum('low','normal','high') NOT NULL,
  `status` enum('open','closed','analyzed','in-progress') NOT NULL,
  `memo` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `note_log`
--

CREATE TABLE IF NOT EXISTS `note_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_log_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

CREATE TABLE IF NOT EXISTS `plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(25) NOT NULL,
  `max_clients` int(11) NOT NULL,
  `max_active_worklogs` int(11) NOT NULL,
  `allow_api_key` tinyint(1) NOT NULL DEFAULT '0',
  `cost_monthly` decimal(10,2) NOT NULL,
  `expires` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shortname` (`shortname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `time_log`
--

CREATE TABLE IF NOT EXISTS `time_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `work_log_id` int(10) unsigned NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `stop_time` datetime DEFAULT NULL,
  `notes` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_log_id` (`work_log_id`,`start_time`),
  KEY `time_log_FKIndex1` (`work_log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `name` varchar(100) NOT NULL,
  `street` varchar(50) NOT NULL,
  `street2` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip` varchar(25) NOT NULL,
  `country` varchar(50) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 - email verified',
  `verify_command` enum('initial_email_check','change_email','reset_password') NOT NULL,
  `verify_code` varchar(50) DEFAULT NULL,
  `verify_param` varchar(255) NOT NULL,
  `plan_id` int(11) NOT NULL DEFAULT '0',
  `trial_expired` tinyint(1) NOT NULL,
  `date_plan_expires` date DEFAULT NULL,
  `referred_by_id` int(11) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `api_key` (`api_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_time_usage`
--
CREATE TABLE IF NOT EXISTS `view_time_usage` (
`username` varchar(30)
,`title` varchar(100)
,`start_time` datetime
,`stop_time` datetime
,`diff` double(27,10)
);
-- --------------------------------------------------------

--
-- Table structure for table `work_log`
--

CREATE TABLE IF NOT EXISTS `work_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `hours` float NOT NULL,
  `rate` float DEFAULT NULL,
  `amount_billed` decimal(10,2) DEFAULT NULL,
  `date_billed` date DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `date_paid` date DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `work_log_FKIndex1` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure for view `view_time_usage`
--
DROP TABLE IF EXISTS `view_time_usage`;

CREATE ALGORITHM=UNDEFINED DEFINER=`cworklog_php`@`%` SQL SECURITY DEFINER VIEW `view_time_usage` AS select `user`.`username` AS `username`,`work_log`.`title` AS `title`,`time_log`.`start_time` AS `start_time`,`time_log`.`stop_time` AS `stop_time`,((`time_log`.`stop_time` - `time_log`.`start_time`) / 60) AS `diff` from ((`user` left join `work_log` on((`work_log`.`user_id` = `user`.`id`))) left join `time_log` on((`time_log`.`work_log_id` = `work_log`.`id`))) order by `time_log`.`start_time` desc;
