/*
Navicat MySQL Data Transfer

Source Server         : liboshi
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : jxc0910

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2020-10-23 14:39:17
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mbs_menu_url
-- ----------------------------
DROP TABLE IF EXISTS `mbs_menu_url`;
CREATE TABLE `mbs_menu_url` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(50) NOT NULL,
  `menu_url` varchar(255) NOT NULL,
  `module_id` int(11) NOT NULL,
  `is_show` tinyint(4) NOT NULL COMMENT '是否在sidebar里出现',
  `online` int(11) NOT NULL DEFAULT '1' COMMENT '在线状态，还是下线状态，即可用，不可用。',
  `shortcut_allowed` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许快捷访问',
  `menu_desc` varchar(255) DEFAULT NULL,
  `father_menu` int(11) NOT NULL DEFAULT '0' COMMENT '上一级菜单',
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `menu_url` (`menu_url`)
) ENGINE=MyISAM AUTO_INCREMENT=330 DEFAULT CHARSET=utf8 COMMENT='功能链接（菜单链接）';

-- ----------------------------
-- Records of mbs_menu_url
-- ----------------------------
INSERT INTO `mbs_menu_url` VALUES ('1', '首页', '/index.php', '1', '1', '1', '1', '后台首页', '0');
INSERT INTO `mbs_menu_url` VALUES ('2', '账号列表', '/index.php/index/user', '1', '1', '1', '1', '账号列表', '0');
INSERT INTO `mbs_menu_url` VALUES ('3', '修改账号', '/panel/user_modify.php', '1', '0', '1', '0', '修改账号', '2');
INSERT INTO `mbs_menu_url` VALUES ('4', '新建账号', '/panel/user_add.php', '1', '0', '1', '1', '新建账号', '2');
INSERT INTO `mbs_menu_url` VALUES ('5', '个人信息', '/panel/profile.php', '1', '0', '1', '1', '个人信息', '0');
INSERT INTO `mbs_menu_url` VALUES ('6', '账号组成员', '/panel/group.php', '1', '0', '1', '0', '显示账号组详情及该组成员', '7');
INSERT INTO `mbs_menu_url` VALUES ('7', '账号组管理', '/panel/groups.php', '1', '0', '1', '1', '增加管理员', '0');
INSERT INTO `mbs_menu_url` VALUES ('8', '修改账号组', '/panel/group_modify.php', '1', '0', '1', '0', '修改账号组', '7');
INSERT INTO `mbs_menu_url` VALUES ('9', '新建账号组', '/panel/group_add.php', '1', '0', '1', '1', '新建账号组', '7');
INSERT INTO `mbs_menu_url` VALUES ('10', '权限管理', '/panel/group_role.php', '1', '0', '1', '1', '用户权限依赖于账号组的权限', '0');
INSERT INTO `mbs_menu_url` VALUES ('11', '菜单模块', '/panel/modules.php', '1', '0', '1', '1', '菜单里的模块', '0');
INSERT INTO `mbs_menu_url` VALUES ('12', '编辑菜单模块', '/panel/module_modify.php', '1', '0', '1', '0', '编辑模块', '11');
INSERT INTO `mbs_menu_url` VALUES ('13', '添加菜单模块', '/panel/module_add.php', '1', '0', '1', '1', '添加菜单模块', '11');
INSERT INTO `mbs_menu_url` VALUES ('14', '功能列表', '/index.php/index/pannel', '4', '1', '1', '1', '菜单功能及可访问的链接', '0');
INSERT INTO `mbs_menu_url` VALUES ('15', '增加功能', '/panel/menu_add.php', '1', '0', '1', '1', '增加功能', '14');
INSERT INTO `mbs_menu_url` VALUES ('16', '功能修改', '/panel/menu_modify.php', '1', '0', '1', '0', '修改功能', '14');
INSERT INTO `mbs_menu_url` VALUES ('17', '设置模板', '/panel/set.php', '1', '0', '1', '1', '设置模板', '0');
INSERT INTO `mbs_menu_url` VALUES ('18', '便签管理', '/panel/quicknotes.php', '1', '0', '1', '1', 'quick note', '0');
INSERT INTO `mbs_menu_url` VALUES ('19', '菜单链接列表', '/panel/module.php', '1', '0', '1', '0', '显示模块详情及该模块下的菜单', '11');
INSERT INTO `mbs_menu_url` VALUES ('20', '登入', '/login.php', '1', '0', '1', '1', '登入页面', '0');
INSERT INTO `mbs_menu_url` VALUES ('21', '操作记录', '/panel/syslog.php', '1', '0', '1', '1', '用户操作的历史行为', '0');
INSERT INTO `mbs_menu_url` VALUES ('22', '系统信息', '/panel/system.php', '1', '0', '1', '1', '显示系统相关信息', '0');
INSERT INTO `mbs_menu_url` VALUES ('23', 'ajax访问修改快捷菜单', '/ajax/shortcut.php', '1', '0', '1', '0', 'ajax请求', '0');
INSERT INTO `mbs_menu_url` VALUES ('24', '添加便签', '/panel/quicknote_add.php', '1', '0', '1', '1', '添加quicknote的内容', '18');
INSERT INTO `mbs_menu_url` VALUES ('25', '修改便签', '/panel/quicknote_modify.php', '1', '0', '1', '0', '修改quicknote的内容', '18');
INSERT INTO `mbs_menu_url` VALUES ('26', '系统设置', '/panel/setting.php', '1', '0', '1', '0', '系统设置', '0');
INSERT INTO `mbs_menu_url` VALUES ('105', '新增产品', '/sys/goods_add.php', '4', '0', '1', '1', '', '107');
INSERT INTO `mbs_menu_url` VALUES ('106', '编辑产品', '/sys/goods_modify.php', '4', '0', '1', '0', '', '107');
INSERT INTO `mbs_menu_url` VALUES ('107', '产品管理', '/sys/goods.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('108', '产品分类管理', '/sys/goods_cats.php', '4', '0', '1', '1', null, '0');
INSERT INTO `mbs_menu_url` VALUES ('109', '增加产品分类', '/sys/goods_cats_add.php', '4', '0', '1', '1', '', '108');
INSERT INTO `mbs_menu_url` VALUES ('110', '编辑产品分类', '/sys/goods_cats_modify.php', '4', '0', '1', '0', '', '108');
INSERT INTO `mbs_menu_url` VALUES ('111', '供应商管理', '/sys/suppliers.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('112', '新增供应商', '/sys/suppliers_add.php', '4', '0', '1', '1', '', '111');
INSERT INTO `mbs_menu_url` VALUES ('113', '编辑供应商', '/sys/suppliers_modify.php', '4', '0', '1', '0', '', '111');
INSERT INTO `mbs_menu_url` VALUES ('114', '员工管理', '/sys/employee.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('115', '新增员工', '/sys/employee_add.php', '4', '0', '1', '1', '', '114');
INSERT INTO `mbs_menu_url` VALUES ('116', '编辑员工', '/sys/employee_modify.php', '4', '0', '1', '0', '', '114');
INSERT INTO `mbs_menu_url` VALUES ('117', '公司管理', '/sys/company.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('118', '新增公司', '/sys/company_add.php', '4', '0', '1', '1', '', '117');
INSERT INTO `mbs_menu_url` VALUES ('119', '编辑公司', '/sys/company_modify.php', '4', '0', '1', '0', '', '117');
INSERT INTO `mbs_menu_url` VALUES ('120', '部门管理', '/sys/department.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('121', '新增部门', '/sys/department_add.php', '4', '0', '1', '1', '', '120');
INSERT INTO `mbs_menu_url` VALUES ('122', '编辑部门', '/sys/department_modify.php', '4', '0', '1', '0', '', '120');
INSERT INTO `mbs_menu_url` VALUES ('123', '仓库管理', '/sys/depot.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('124', '新增仓库', '/sys/depot_add.php', '4', '0', '1', '1', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('125', '编辑仓库', '/sys/depot_modify.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('126', '采购计划单', '/purchase/plan_order.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('127', '采购订单录入', '/purchase/plan_order_add.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('128', '采购单修改', '/purchase/plan_order_modify.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('129', '采购进货单', '/purchase/arrival_order.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('130', '采购退货单', '/purchase/return_order.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('131', '采购发票', '/purchase/invoice.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('132', '价格管理', '/purchase/price.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('133', '应付款单', '/finance/pay_order.php', '8', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('134', '财务详情', '/finance/finance_detail.php', '8', '0', '1', '0', '', '149');
INSERT INTO `mbs_menu_url` VALUES ('135', '采购入库单', '/storage/purchase_storage.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('137', '其他入库单', '/storage/other_storage.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('138', '调拨入库单', '/storage/allot_storage.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('139', '领用出库单', '/storage/using_deliver.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('140', '其他出库单', '/storage/other_deliver.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('141', '库存盘点单', '/storage/count_order.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('142', '各部门领用明细表', '/charts/purchase.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('143', '各部门领用汇总表', '/charts/return.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('144', '出入库盘存表', '/charts/suppliers.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('145', '材料入库、出库、盘存汇总表', '/charts/global.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('148', '应付款单添加财务', '/finance/pay_add.php', '8', '0', '1', '0', '', '133');
INSERT INTO `mbs_menu_url` VALUES ('149', '财务查询', '/finance/search.php', '8', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('150', '应收账款', '/finance/collect.php', '8', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('151', '产品计量管理', '/sys/unit.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('152', '增加产品计量单位', '/sys/unit_add.php', '4', '0', '1', '1', '', '151');
INSERT INTO `mbs_menu_url` VALUES ('153', '编辑产品计量单位', '/sys/unit_modify.php', '4', '0', '1', '0', '', '151');
INSERT INTO `mbs_menu_url` VALUES ('161', '采购退货单录入', '/purchase/return_order_add.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('162', '登记入库商品', '/storage/purchase_storage_add.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('163', '商品查询', '/purchase/goods_search.php', '10', '0', '1', '0', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('164', '登记出库单', '/storage/using_deliver_add.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('165', '调拨入库单新增', '/storage/allot_storage_add.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('166', '增加盘点单', '/storage/count_order_add.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('167', '删除公司', '/sys/company_del.php', '4', '0', '1', '0', '', '117');
INSERT INTO `mbs_menu_url` VALUES ('168', '删除部门', '/sys/department_del.php', '4', '0', '1', '0', '', '120');
INSERT INTO `mbs_menu_url` VALUES ('169', '删除员工', '/sys/employee_del.php', '4', '0', '1', '0', '', '114');
INSERT INTO `mbs_menu_url` VALUES ('170', '产品分类删除', '/sys/goods_cats_del.php', '4', '0', '1', '0', '', '108');
INSERT INTO `mbs_menu_url` VALUES ('171', '产品计量单位删除', '/sys/unit_del.php', '4', '0', '1', '0', '', '151');
INSERT INTO `mbs_menu_url` VALUES ('172', '删除商品', '/sys/goods_del.php', '4', '0', '1', '0', '删除商品', '107');
INSERT INTO `mbs_menu_url` VALUES ('173', '删除供应商', '/sys/suppliers_del.php', '4', '0', '1', '0', '', '111');
INSERT INTO `mbs_menu_url` VALUES ('174', '客户管理', '/sys/customer.php', '4', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('175', '供应商审核', '/sys/suppliers_review.php', '4', '0', '1', '0', '', '111');
INSERT INTO `mbs_menu_url` VALUES ('176', '库存预警', '/message/storage.php', '12', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('177', '价格预警', '/message/price.php', '12', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('178', '待办事务', '/message/other.php', '12', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('179', '产品条码打印', '/code/goods_qrcode.php', '11', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('180', '库位条码打印', '/code/depotSub_qrcode.php', '11', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('181', '产品打印预览', '/code/preview.php', '11', '0', '1', '0', '', '179');
INSERT INTO `mbs_menu_url` VALUES ('182', '价格管理趋势图', '/purchase/price_view.php', '10', '0', '1', '0', '', '132');
INSERT INTO `mbs_menu_url` VALUES ('183', '价格管理详情列表', '/purchase/price_list.php', '10', '0', '1', '0', '', '132');
INSERT INTO `mbs_menu_url` VALUES ('184', '采购进货单录入', '/purchase/arrival_order_add.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('185', '库位产品列表', '/sys/depotSub_goods.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('186', '新增库位', '/sys/depotSub_add.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('187', '产品二维码设计', '/code/design.php', '11', '0', '1', '0', '', '179');
INSERT INTO `mbs_menu_url` VALUES ('188', '库位打印预览', '/code/depotSub_preview.php', '11', '0', '1', '0', '', '180');
INSERT INTO `mbs_menu_url` VALUES ('189', '新增客户', '/sys/customer_add.php', '4', '0', '1', '0', '', '174');
INSERT INTO `mbs_menu_url` VALUES ('190', '编辑客户', '/sys/customer_modify.php', '4', '0', '1', '0', '', '174');
INSERT INTO `mbs_menu_url` VALUES ('191', '删除客户', '/sys/customer_del.php', '4', '0', '1', '0', '', '174');
INSERT INTO `mbs_menu_url` VALUES ('192', '调拨出库单', '/storage/allot_deliver.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('193', '采购订单审核', '/purchase/plan_order_review.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('194', '采购进货单审核', '/purchase/arrival_order_review.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('195', '采购退货单审核', '/purchase/return_order_review.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('196', '采购入库单审核', '/storage/purchase_storage_review.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('197', '其他入库单审核', '/storage/other_storage_review.php', '5', '0', '1', '0', '', '137');
INSERT INTO `mbs_menu_url` VALUES ('198', '调拨入库单审核', '/storage/allot_storage_review.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('199', '领用出库单审核', '/storage/using_deliver_review.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('200', '其他出库单审核', '/storage/other_deliver_review.php', '5', '0', '1', '0', '', '140');
INSERT INTO `mbs_menu_url` VALUES ('201', '调拨出库单审核', '/storage/allot_deliver_review.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('202', '库存盘点单审核', '/storage/count_order_review.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('203', '采购订单创建', '/purchase/plan_order_create.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('204', '采购订单详情', '/purchase/plan_order_details.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('205', '进货单创建', '/purchase/arrival_order_create.php', '10', '0', '1', '0', 'xxx', '129');
INSERT INTO `mbs_menu_url` VALUES ('206', '进货单详情', '/purchase/arrival_order_details.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('207', '进货单修改', '/purchase/arrival_order_modify.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('208', '采购退货单创建', '/purchase/return_order_create.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('209', '采购退货单详情', '/purchase/return_order_details.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('210', '采购退货单修改', '/purchase/return_order_modify.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('211', '采购入库单创建', '/storage/purchase_storage_create.php', '5', '0', '1', '0', 'xxxx', '135');
INSERT INTO `mbs_menu_url` VALUES ('212', '采购入库单详情', '/storage/purchase_storage_details.php', '5', '0', '1', '0', 'xx', '135');
INSERT INTO `mbs_menu_url` VALUES ('213', '采购入库单修改', '/storage/purchase_storage_modify.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('214', '商品查询', '/storage/goods_search.php', '5', '0', '1', '0', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('215', '调拨入库单创建', '/storage/allot_storage_create.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('216', '调拨入库单详情', '/storage/allot_storage_details.php', '5', '0', '1', '0', '\r\n', '138');
INSERT INTO `mbs_menu_url` VALUES ('217', '调拨入库单修改', '/storage/allot_storage_modify.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('218', '调拨出库单创建', '/storage/allot_deliver_create.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('219', '调拨出库单详情', '/storage/allot_deliver_details.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('220', '调拨出库单修改', '/storage/allot_deliver_modify.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('221', '调拨出库单录入', '/storage/allot_deliver_add.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('222', '领用出库单创建', '/storage/using_deliver_create.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('223', '领用出库单详情', '/storage/using_deliver_details.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('224', '领用出库单修改', '/storage/using_deliver_modify.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('225', '库存盘点单创建', '/storage/count_order_create.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('226', '库存盘点单详情', '/storage/count_order_details.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('227', '库存盘点单修改', '/storage/count_order_modify.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('228', '采购订单商品删除', '/purchase/plan_order_delete.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('229', '进货单商品删除', '/purchase/arrival_order_delete.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('230', '退货单商品删除', '/purchase/return_order_delete.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('231', '采购入库单商品删除', '/storage/ip_goods_delete.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('232', '调拨入库单商品删除', '/storage/id_goods_delete.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('233', '领用出库单商品删除', '/storage/oy_goods_delete.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('234', '库存盘点单商品删除', '/storage/pd_goods_delete.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('235', '调拨出库单商品删除', '/storage/od_goods_delete.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('236', '采购订单删除', '/purchase/plan_delete.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('237', '进货单删除', '/purchase/arrival_delete.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('238', '退货单删除', '/purchase/return_delete.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('239', '采购入库单删除', '/storage/ip_delete.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('240', '调拨入库单删除', '/storage/id_delete.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('241', '领用出库单删除', '/storage/oy_delete.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('242', '库存盘点单删除', '/storage/pd_delete.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('243', '调拨出库单删除', '/storage/od_delete.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('244', '采购订单提交审核', '/purchase/plan_to_review.php', '10', '0', '1', '0', '', '126');
INSERT INTO `mbs_menu_url` VALUES ('245', '进货单提交审核', '/purchase/arrival_to_review.php', '10', '0', '1', '0', '', '129');
INSERT INTO `mbs_menu_url` VALUES ('246', '退货单提交审核', '/purchase/return_to_review.php', '10', '0', '1', '0', '', '130');
INSERT INTO `mbs_menu_url` VALUES ('247', '采购入库单提交审核', '/storage/ip_to_review.php', '5', '0', '1', '0', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('248', '调拨入库单提交审核', '/storage/id_to_review.php', '5', '0', '1', '0', '', '138');
INSERT INTO `mbs_menu_url` VALUES ('249', '领用出库单提交审核', '/storage/oy_to_review.php', '5', '0', '1', '0', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('250', '库存盘点单提交审核', '/storage/pd_to_review.php', '5', '0', '1', '0', '', '141');
INSERT INTO `mbs_menu_url` VALUES ('251', '调拨出库单提交审核', '/storage/od_to_review.php', '5', '0', '1', '0', '', '192');
INSERT INTO `mbs_menu_url` VALUES ('252', '其他入库单详情', '/storage/other_storage_details.php', '5', '0', '1', '0', '', '137');
INSERT INTO `mbs_menu_url` VALUES ('253', '其他出库单详情', '/storage/other_deliver_details.php', '5', '0', '1', '0', '', '140');
INSERT INTO `mbs_menu_url` VALUES ('254', '删除库位', '/sys/depotSub_del.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('255', '删除仓库', '/sys/depot_del.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('256', '编辑库位', '/sys/depotSub_modify.php', '4', '0', '1', '0', '', '123');
INSERT INTO `mbs_menu_url` VALUES ('257', '上传发票', '/ajax/base64Upload.php', '8', '0', '1', '0', '', '133');
INSERT INTO `mbs_menu_url` VALUES ('258', '发票详情', '/purchase/invoice_details.php', '10', '0', '1', '0', '', '131');
INSERT INTO `mbs_menu_url` VALUES ('259', '库存预警添加设置', '/message/storage_setting.php', '12', '0', '1', '0', '', '260');
INSERT INTO `mbs_menu_url` VALUES ('260', '库存预警设置', '/message/storage_sets.php', '12', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('261', '库存预警设置修改', '/message/storage_modify.php', '12', '0', '1', '0', '', '260');
INSERT INTO `mbs_menu_url` VALUES ('262', '库存预警设置删除', '/message/storage_del.php', '12', '0', '1', '0', '', '260');
INSERT INTO `mbs_menu_url` VALUES ('263', '价格预警设置', '/message/price_sets.php', '12', '0', '1', '1', '价格预警设置', '0');
INSERT INTO `mbs_menu_url` VALUES ('264', '价格预警设置新增', '/message/price_setting.php', '12', '0', '1', '0', '', '263');
INSERT INTO `mbs_menu_url` VALUES ('265', '价格预警设置编辑', '/message/price_modify.php', '12', '0', '1', '0', '', '263');
INSERT INTO `mbs_menu_url` VALUES ('266', '价格预警设置删除', '/message/price_del.php', '12', '0', '1', '0', '', '263');
INSERT INTO `mbs_menu_url` VALUES ('267', '销售订单', '/sales/index.php', '13', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('268', '销售订单创建', '/sales/create.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('269', '销售订单产品修改', '/sales/edit.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('270', '销售订单审核', '/sales/review.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('271', '销售订单删除', '/sales/del.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('272', '销售订单详情', '/sales/details.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('273', '销售订单产品新增', '/sales/add.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('274', '商品查询', '/sales/goods_search.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('275', '提交审核', '/sales/to_review.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('276', '订单商品删除', '/sales/goods_del.php', '13', '0', '1', '0', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('277', '应收账款添加财务', '/finance/collect_add.php', '8', '0', '1', '0', '', '150');
INSERT INTO `mbs_menu_url` VALUES ('278', '添加财务', '/finance/finance_add.php', '8', '0', '1', '0', '', '149');
INSERT INTO `mbs_menu_url` VALUES ('279', '应收款单收票', '/finance/pay_collect.php', '8', '0', '1', '0', '', '150');
INSERT INTO `mbs_menu_url` VALUES ('280', '采购明细/汇总表', '/charts/chart1.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('281', '库存明细/汇总表', '/charts/chart2.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('282', '调拨明细/汇总表', '/charts/chart3.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('283', '入库明细/汇总表', '/charts/chart4.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('284', '出库明细/汇总表', '/charts/chart5.php', '7', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('285', '发票对账单', '/finance/pay_invoice.php', '10', '0', '1', '1', '', '131');
INSERT INTO `mbs_menu_url` VALUES ('286', '导出对账单', '/finance/export_invoice.php', '10', '0', '1', '1', '', '131');
INSERT INTO `mbs_menu_url` VALUES ('287', '接口', '/storage/depot.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('288', '采购清单', '/message/purchase_list.php', '12', '0', '1', '1', '', '176');
INSERT INTO `mbs_menu_url` VALUES ('289', '销售退货单', '/sales/return.php', '13', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('290', '生成领用出库退货单', '/storage/using_return.php', '5', '0', '1', '1', '', '139');
INSERT INTO `mbs_menu_url` VALUES ('291', '生成销售退货单', '/sales/change.php', '13', '0', '1', '1', '', '267');
INSERT INTO `mbs_menu_url` VALUES ('292', '领用出库退货单', '/storage/return.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('293', '创建领用退货单', '/storage/return_create.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('294', '修改领用退货单', '/storage/return_modify.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('296', '领用退货单详情', '/storage/return_details.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('298', '销售退货单创建', '/sales/return_create.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('299', '销售退货单详情', '/sales/return_details.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('300', '销售退货单修改', '/sales/return_modify.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('301', '销售退货单删除', '/sales/return_delete.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('302', '销售退货订单商品删除', '/sales/return_goods_delete.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('303', '销售退货单录入', '/sales/return_add.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('304', '销售退货单提交审核', '/sales/return_to_review.php', '13', '0', '1', '1', '', '289');
INSERT INTO `mbs_menu_url` VALUES ('305', '领用退货单录入', '/storage/using_return_add.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('306', '领用退货单删除', '/storage/return_delete.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('307', '领用退货单商品删除', '/storage/return_goods_delete.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('308', '领用退货单提交审核', '/storage/return_to_review.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('309', '领用退货审核', '/storage/return_review.php', '5', '0', '1', '1', '', '292');
INSERT INTO `mbs_menu_url` VALUES ('310', '采购退货单创建', '/storage/purchase_return.php', '5', '0', '1', '1', '', '135');
INSERT INTO `mbs_menu_url` VALUES ('311', '采购入库退货单', '/storage/purchase_in_return.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('312', '采购退货单详情', '/storage/purchase_return_details.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('313', '采购退货单删除', '/storage/purchase_return_delete.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('314', '采购退货单商品修改', '/storage/purchase_return_modify.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('315', '采购退货商品删除', '/storage/ip_return_goods_delete.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('316', '采购退货商品添加', '/storage/purchase_return_add.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('317', '审核采购退货', '/storage/preturn_to_review.php', '5', '0', '1', '1', '', '311');
INSERT INTO `mbs_menu_url` VALUES ('318', '采购申请单', '/purchase/purchase_play.php', '10', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('319', '采购入库单改版', '/storage/purchase_storage_createnew.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('320', '选择供应商', '/storage/suppliers_choose.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('321', '选择商品列表', '/storage/goods_choose.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('322', '采购入库单清单', '/storage/purchase_storage_new.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('323', '领用出库清单表', '/storage/using_deliver_list.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('324', '添加领用出库改版', '/storage/using_deliver_createnew.php', '5', '0', '1', '1', '', '323');
INSERT INTO `mbs_menu_url` VALUES ('325', '选择库存商品', '/storage/depot_goods_choose.php', '5', '0', '1', '1', '', '0');
INSERT INTO `mbs_menu_url` VALUES ('326', '客户列表', '/index.php/index/customers', '1', '1', '1', '1', null, '0');
INSERT INTO `mbs_menu_url` VALUES ('327', 'fcrdewv', 'cfdwv', '4', '2', '1', '1', 'cdwqc', '0');
INSERT INTO `mbs_menu_url` VALUES ('328', '门店管理', '/index.php/index/chain', '1', '1', '1', '1', '门店列表', '0');
INSERT INTO `mbs_menu_url` VALUES ('329', '权限管理', '/index.php/index/rolelist', '4', '1', '1', '1', '用户组权限分配', '0');
