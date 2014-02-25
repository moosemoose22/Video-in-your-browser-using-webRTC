-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 25, 2014 at 05:14 PM
-- Server version: 5.1.58
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `orent_info_-_zion`
--

-- --------------------------------------------------------

--
-- Table structure for table `videoCalls`
--

CREATE TABLE IF NOT EXISTS `videoCalls` (
  `videoCallID` int(11) NOT NULL AUTO_INCREMENT,
  `videoCallTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`videoCallID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `videoCallUsers`
--

CREATE TABLE IF NOT EXISTS `videoCallUsers` (
  `videoCallUserID` int(11) NOT NULL AUTO_INCREMENT,
  `videoCallUserCallID` int(11) NOT NULL,
  `videoCallUserUserID` int(11) NOT NULL,
  PRIMARY KEY (`videoCallUserID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `videoICEcandidates`
--

CREATE TABLE IF NOT EXISTS `videoICEcandidates` (
  `videoCallID` int(11) NOT NULL,
  `sendVideoUsername` varchar(20) NOT NULL,
  `receiveVideoUsername` varchar(20) NOT NULL,
  `videoIceCandidateMLineIndex` varchar(200) NOT NULL,
  `videoIceCandidateMediaType` varchar(200) NOT NULL,
  `videoIceCandidateCandidate` varchar(200) NOT NULL,
  `videoIceCandidateRead` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `videoSDP`
--

CREATE TABLE IF NOT EXISTS `videoSDP` (
  `videoCallID` int(11) NOT NULL,
  `sendVideoUsername` varchar(20) NOT NULL,
  `receiveVideoUsername` varchar(20) NOT NULL,
  `videoSDP` text NOT NULL,
  `videoSDPtype` varchar(25) NOT NULL,
  `videoSDPread` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `videoUsers`
--

CREATE TABLE IF NOT EXISTS `videoUsers` (
  `videoUserID` int(11) NOT NULL AUTO_INCREMENT,
  `videoCallID` int(11) NOT NULL,
  `videoUsername` varchar(20) NOT NULL,
  `videoUserIP` varchar(28) NOT NULL,
  `videoUserTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`videoUserID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=112 ;
