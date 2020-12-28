/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:39:24
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_module
-- ----------------------------
DROP TABLE IF EXISTS `mbs_module`;
CREATE TABLE `mbs_module` (
  `module_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL,
  `module_url` varchar(128) NOT NULL,
  `module_sort` int(11) unsigned NOT NULL DEFAULT '1',
  `module_desc` varchar(255) DEFAULT NULL,
  `module_icon` varchar(32) DEFAULT 'icon-th' COMMENT '菜单模块图标',
  `online` int(11) NOT NULL DEFAULT '1' COMMENT '模块是否在线',
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='菜单模块';

-- ----------------------------
-- Records of mbs_module
-- ----------------------------
INSERT INTO `mbs_module` VALUES ('1', '控制面板', '/panel/index.php', '0', 'erp', 'icon-th', '1');
INSERT INTO `mbs_module` VALUES ('4', '系统设置', '/index.php', '1', '', 'icon-cog', '1');
INSERT INTO `mbs_module` VALUES ('8', '财务管理', '/index.php', '6', '', 'icon-folder-close', '1');
INSERT INTO `mbs_module` VALUES ('12', '消息管理', '/index.php', '0', '', 'icon-comment', '1');
