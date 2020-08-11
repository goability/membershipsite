-- phpMyAdmin SQL Dump
-- version 4.9.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3308
-- Generation Time: Jul 07, 2020 at 01:10 AM
-- Server version: 5.7.26
-- PHP Version: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `warehouse`
--
CREATE DATABASE IF NOT EXISTS `warehouse` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `warehouse`;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE IF NOT EXISTS `client` (
  `userid` bigint(20) NOT NULL AUTO_INCREMENT,
  `providerid` bigint(20) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `movementitemtype`
--

CREATE TABLE IF NOT EXISTS `movementitemtype` (
  `ID` tinyint(4) NOT NULL AUTO_INCREMENT,
  `Name` text COMMENT 'Item, Pallet, Container',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `provider`
--

CREATE TABLE IF NOT EXISTS `provider` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(10) NOT NULL,
  `zip` int(16) NOT NULL,
  `ownerid` bigint(20) NOT NULL,
  `website` varchar(100) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phonealt` varchar(20) DEFAULT NULL,
  `notes` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagebin`
--

CREATE TABLE IF NOT EXISTS `storagebin` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` int(6) DEFAULT NULL,
  `itemproperties` mediumtext COMMENT 'JSON Properties',
  `weightpounds` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagebininventory`
--

CREATE TABLE IF NOT EXISTS `storagebininventory` (
  `binid` bigint(20) NOT NULL,
  `itemid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainer`
--

CREATE TABLE IF NOT EXISTS `storagecontainer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `providerid` bigint(20) NOT NULL,
  `description` text,
  `sizexinches` int(11) DEFAULT NULL,
  `sizeyinches` int(11) DEFAULT NULL,
  `sizezinches` int(11) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainerinventory`
--

CREATE TABLE IF NOT EXISTS `storagecontainerinventory` (
  `containerid` bigint(20) NOT NULL,
  `itemid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacility`
--

CREATE TABLE IF NOT EXISTS `storagefacility` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ownerid` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(3) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `notes` mediumtext,
  `website` varchar(100) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityinventory`
--

CREATE TABLE IF NOT EXISTS `storagefacilityinventory` (
  `storageItemtypeid` tinyint(4) DEFAULT NULL,
  `storagelocationid` bigint(20) NOT NULL,
  `storageItemid` bigint(20) NOT NULL,
  `storagecontainerid` bigint(20) NOT NULL,
  `storagepalletid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityowners`
--

CREATE TABLE IF NOT EXISTS `storagefacilityowners` (
  `facilityid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityproviders`
--

CREATE TABLE IF NOT EXISTS `storagefacilityproviders` (
  `facilityid` bigint(20) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityworkers`
--

CREATE TABLE IF NOT EXISTS `storagefacilityworkers` (
  `userid` bigint(20) DEFAULT NULL,
  `facilityid` bigint(20) NOT NULL,
  `providerid` bigint(20) DEFAULT NULL,
  `lastactiontimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `notes` mediumtext,
  UNIQUE KEY `ProviderWorker` (`userid`,`providerid`),
  UNIQUE KEY `FacilityWorker` (`userid`,`facilityid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storageitem`
--

CREATE TABLE IF NOT EXISTS `storageitem` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ownerid` bigint(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` int(6) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storageitemmovement`
--

CREATE TABLE IF NOT EXISTS `storageitemmovement` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `MovementItemTypeID` tinyint(4) DEFAULT NULL,
  `StorageItemID` bigint(20) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `FromLocationID` bigint(20) NOT NULL,
  `ToLocationID` bigint(20) NOT NULL,
  `MoverUserID` bigint(20) NOT NULL COMMENT 'Worker or User with access',
  `RequestorID` bigint(20) NOT NULL COMMENT 'Who requests it',
  `Notes` text COMMENT 'Optional 65K',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `StorageItemID` (`ID`,`StorageItemID`),
  UNIQUE KEY `RequestorID` (`RequestorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocation`
--

CREATE TABLE IF NOT EXISTS `storagelocation` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `storagefacilityid` bigint(20) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `row` varchar(10) DEFAULT NULL,
  `col` varchar(10) DEFAULT NULL,
  `shelf` varchar(10) DEFAULT NULL,
  `xshelf` varchar(4) DEFAULT NULL,
  `yshelf` varchar(4) DEFAULT NULL,
  `zshelf` varchar(4) DEFAULT NULL,
  `facilitycoords` varchar(100) DEFAULT NULL,
  `tags` tinytext,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocationinventory`
--

CREATE TABLE IF NOT EXISTS `storagelocationinventory` (
  `locationid` bigint(20) NOT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `containerid` bigint(20) DEFAULT NULL,
  `palletid` bigint(20) DEFAULT NULL,
  `binid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagepallet`
--

CREATE TABLE IF NOT EXISTS `storagepallet` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `storageproviderid` bigint(20) NOT NULL,
  `storagefacilityid` bigint(20) NOT NULL,
  `Description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` int(6) DEFAULT NULL,
  `itemproperties` mediumtext COMMENT 'JSON Properties',
  `weightpounds` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagepalletinventory`
--

CREATE TABLE IF NOT EXISTS `storagepalletinventory` (
  `palletid` bigint(20) NOT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `binid` bigint(20) DEFAULT NULL,
  `containerid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(60) NOT NULL,
  `middlename` varchar(60) NOT NULL,
  `lastname` varchar(60) NOT NULL,
  `companyname` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `state` varchar(4) DEFAULT NULL,
  `zip` varchar(12) DEFAULT NULL,
  `phonemobile` varchar(20) DEFAULT NULL,
  `phonehome` varchar(20) DEFAULT NULL,
  `phoneother` varchar(20) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `website` varchar(120) DEFAULT NULL,
  `facebookurl` varchar(120) DEFAULT NULL,
  `linkedinurl` varchar(120) DEFAULT NULL,
  `profilename` varchar(30) DEFAULT NULL,
  `profileimagepath` varchar(60) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
