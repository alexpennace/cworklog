-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2014 at 06:44 AM
-- Server version: 5.6.11
-- PHP Version: 5.5.3

SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `company`
--
-- Creation: Oct 08, 2013 at 05:32 AM
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
-- Creation: Oct 08, 2013 at 05:32 AM
--

CREATE TABLE IF NOT EXISTS `files_log` (
  `work_log_id` int(11) NOT NULL,
  `feature` varchar(55) NOT NULL,
  `file` varchar(255) NOT NULL,
  `change_type` enum('file','db') NOT NULL DEFAULT 'file',
  `notes` text,
  `in_production` tinyint(1) NOT NULL DEFAULT '0',
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`work_log_id`,`feature`,`file`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `install`
--
-- Creation: Feb 22, 2014 at 04:48 AM
--

CREATE TABLE IF NOT EXISTS `install` (
  `version` varchar(25) NOT NULL,
  `version_date` date NOT NULL,
  `version_int` int(11) NOT NULL,
  `date_installed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `note_log`
--
-- Creation: Oct 08, 2013 at 05:32 AM
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
-- Creation: Oct 08, 2013 at 06:32 AM
-- Last update: Oct 08, 2013 at 05:32 AM
--

CREATE TABLE IF NOT EXISTS `plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(25) NOT NULL,
  `name` varchar(35) NOT NULL,
  `descrip_html` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
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
-- Table structure for table `public_links`
--
-- Creation: Oct 08, 2013 at 05:32 AM
--

CREATE TABLE IF NOT EXISTS `public_links` (
  `code` varchar(255) NOT NULL,
  `date_expires` datetime DEFAULT NULL,
  `permission` enum('view_time_log') NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `work_log_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `time_log`
--
-- Creation: Oct 08, 2013 at 05:32 AM
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
-- Creation: Oct 08, 2013 at 05:32 AM
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
  `date_created` datetime NOT NULL,
  `plan_id` int(11) NOT NULL DEFAULT '0',
  `trial_expired` tinyint(1) NOT NULL DEFAULT '0',
  `date_plan_expires` date DEFAULT NULL,
  `referred_by_id` int(11) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `stripe_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `work_log`
--
-- Creation: Oct 08, 2013 at 05:32 AM
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
