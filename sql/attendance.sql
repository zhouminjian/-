/*
Navicat MySQL Data Transfer

Source Server         : MySQL
Source Server Version : 100119
Source Host           : localhost:3306
Source Database       : attendance

Target Server Type    : MYSQL
Target Server Version : 100119
File Encoding         : 65001

Date: 2018-08-23 10:41:03
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for empattendanalysis
-- ----------------------------
DROP TABLE IF EXISTS `empattendanalysis`;
CREATE TABLE `empattendanalysis` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `EmpNumber` smallint(11) NOT NULL,
  `EmpName` varchar(20) DEFAULT NULL,
  `WorkDate` date DEFAULT NULL,
  `FirstSwipeCard` time DEFAULT NULL,
  `SecondSwipeCard` time DEFAULT NULL,
  `WorkTime` float(8,2) DEFAULT NULL,
  `HourlyWage` float(8,2) DEFAULT NULL,
  `Flag` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=733 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for empattendinfo
-- ----------------------------
DROP TABLE IF EXISTS `empattendinfo`;
CREATE TABLE `empattendinfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmpNumber` smallint(6) NOT NULL,
  `SwipeCardTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for empformat
-- ----------------------------
DROP TABLE IF EXISTS `empformat`;
CREATE TABLE `empformat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmpNumber` smallint(6) NOT NULL,
  `WorkDate` date NOT NULL,
  `FirstSwipeCard` time NOT NULL,
  `SecondSwipeCard` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1099 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for empgroup
-- ----------------------------
DROP TABLE IF EXISTS `empgroup`;
CREATE TABLE `empgroup` (
  `GroupID` smallint(6) NOT NULL,
  `GroupName` varchar(10) NOT NULL,
  PRIMARY KEY (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for empinfo
-- ----------------------------
DROP TABLE IF EXISTS `empinfo`;
CREATE TABLE `empinfo` (
  `EmpNumber` smallint(6) NOT NULL,
  `EmpName` varchar(20) NOT NULL,
  `CartNumber` varchar(10) DEFAULT NULL,
  `EmpGroup` smallint(6) NOT NULL,
  `EmpType` smallint(6) NOT NULL,
  PRIMARY KEY (`EmpNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for empnumber
-- ----------------------------
DROP TABLE IF EXISTS `empnumber`;
CREATE TABLE `empnumber` (
  `USERID` int(6) NOT NULL,
  `Badgenumber` int(6) NOT NULL,
  PRIMARY KEY (`USERID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for emptype
-- ----------------------------
DROP TABLE IF EXISTS `emptype`;
CREATE TABLE `emptype` (
  `TypeID` smallint(6) NOT NULL,
  `TypeName` varchar(10) NOT NULL,
  PRIMARY KEY (`TypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for tmp_empattendinfo
-- ----------------------------
DROP TABLE IF EXISTS `tmp_empattendinfo`;
CREATE TABLE `tmp_empattendinfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmpNumber` smallint(6) NOT NULL,
  `EmpName` varchar(20) NOT NULL,
  `SwipeCardTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `CartNumber` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for workshoplist
-- ----------------------------
DROP TABLE IF EXISTS `workshoplist`;
CREATE TABLE `workshoplist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `EmpName` varchar(20) DEFAULT NULL,
  `EmpGroup` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
