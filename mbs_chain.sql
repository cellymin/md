/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:38:42
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_chain
-- ----------------------------
DROP TABLE IF EXISTS `mbs_chain`;
CREATE TABLE `mbs_chain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名称',
  `phone` varchar(255) NOT NULL COMMENT '电话',
  `tel` varchar(255) NOT NULL COMMENT '固定电话',
  `address` varchar(255) NOT NULL COMMENT '地址',
  `country` varchar(255) DEFAULT NULL COMMENT '国家',
  `province` varchar(255) DEFAULT NULL COMMENT '省份',
  `city` varchar(255) DEFAULT NULL COMMENT '城市',
  `area` varchar(255) DEFAULT NULL COMMENT '区域',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '修改时间',
  `dean_id` int(11) NOT NULL COMMENT '院长id',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='门店表';

-- ----------------------------
-- Records of mbs_chain
-- ----------------------------
INSERT INTO `mbs_chain` VALUES ('1', '清扬路店', '4006667715', '4006667715', '清扬路', '中国', '江苏', '无锡市', '崇安区', '2020-09-19 15:52:39', '2020-10-05 05:05:42', '31', '1');
INSERT INTO `mbs_chain` VALUES ('2', '胜利门店', '4006667715', '4006667715', '胜利门', '中国', '江苏', '无锡市', '崇安区', '2020-09-19 15:54:10', '2020-10-05 05:05:10', '30', '1');
INSERT INTO `mbs_chain` VALUES ('3', '江阴店', '4006667715', '4006667715', '江阴市', '中国', '江苏', '无锡市', '江阴市', '2020-10-05 04:27:06', '2020-10-05 05:04:17', '33', '1');
