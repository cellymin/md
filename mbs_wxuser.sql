/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:39:44
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_wxuser
-- ----------------------------
DROP TABLE IF EXISTS `mbs_wxuser`;
CREATE TABLE `mbs_wxuser` (
  `id` int(11) NOT NULL,
  `openid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '微信唯一标识',
  `nickname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '昵称',
  `headimgurl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '头像',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of mbs_wxuser
-- ----------------------------
