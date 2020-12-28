/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:39:03
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_grade
-- ----------------------------
DROP TABLE IF EXISTS `mbs_grade`;
CREATE TABLE `mbs_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '职级名称',
  `sort` varchar(255) NOT NULL COMMENT '职级排序',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1激活 -1失效',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='职级表';

-- ----------------------------
-- Records of mbs_grade
-- ----------------------------
INSERT INTO `mbs_grade` VALUES ('1', '超级管理员', '1', '2020-09-18 13:46:11', null, '1');
INSERT INTO `mbs_grade` VALUES ('2', '院长', '3', '2020-09-18 13:47:30', null, '1');
INSERT INTO `mbs_grade` VALUES ('3', '员工', '4', '2020-09-18 13:48:01', null, '1');
INSERT INTO `mbs_grade` VALUES ('4', '总院长', '2', '2020-09-24 10:42:59', '2020-09-30 10:43:33', '1');
