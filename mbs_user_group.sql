/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:39:37
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_user_group
-- ----------------------------
DROP TABLE IF EXISTS `mbs_user_group`;
CREATE TABLE `mbs_user_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(32) DEFAULT NULL,
  `group_role` text CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT '初始权限为1,5,17,18,22,23,24,25',
  `owner_id` int(11) DEFAULT NULL COMMENT '创建人ID',
  `group_desc` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='账号组';

-- ----------------------------
-- Records of mbs_user_group
-- ----------------------------
INSERT INTO `mbs_user_group` VALUES ('1', '超级管理员组', '1,2,326,328,14,329', '1', '');
INSERT INTO `mbs_user_group` VALUES ('2', '前台', '1,326', '1', '');
INSERT INTO `mbs_user_group` VALUES ('3', '财务', '1,326', '1', '');
INSERT INTO `mbs_user_group` VALUES ('4', '院长', '1,2,326,328', '1', '');
INSERT INTO `mbs_user_group` VALUES ('5', '技师', '1,2,326', '1', '');
INSERT INTO `mbs_user_group` VALUES ('6', '销售', '1,326', '1', '');
INSERT INTO `mbs_user_group` VALUES ('7', '副总经理', '1,2,326,328', '1', '');
