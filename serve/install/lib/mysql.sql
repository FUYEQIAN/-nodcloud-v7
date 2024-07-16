-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `is_account`;
CREATE TABLE `is_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '账户名称',
  `number` varchar(32) NOT NULL COMMENT '账户编号',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `time` int(11) NOT NULL COMMENT '余额日期',
  `initial` decimal(16,4) NOT NULL COMMENT '期初余额',
  `balance` decimal(16,4) DEFAULT '0.0000' COMMENT '账户余额',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资金账户';


DROP TABLE IF EXISTS `is_account_info`;
CREATE TABLE `is_account_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属账户',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `direction` tinyint(1) NOT NULL COMMENT '方向[0:出|1:入]',
  `money` decimal(16,4) NOT NULL COMMENT '金额',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `class` (`class`),
  KEY `time` (`time`),
  KEY `direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资金详情';


DROP TABLE IF EXISTS `is_allot`;
CREATE TABLE `is_allot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `people` (`people`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='转账单';


DROP TABLE IF EXISTS `is_allot_info`;
CREATE TABLE `is_allot_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `account` int(11) NOT NULL COMMENT '转出账户',
  `tat` int(11) NOT NULL COMMENT '转入账户',
  `money` decimal(12,4) NOT NULL COMMENT '结算金额',
  `settle` varchar(256) DEFAULT '' COMMENT '结算号',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `account` (`account`),
  KEY `tat` (`tat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='转账单详情';


DROP TABLE IF EXISTS `is_attr`;
CREATE TABLE `is_attr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属商品',
  `name` varchar(64) NOT NULL COMMENT '属性名称',
  `buy` decimal(12,4) NOT NULL COMMENT '采购价格',
  `sell` decimal(12,4) NOT NULL COMMENT '销售价格',
  `code` varchar(64) NOT NULL COMMENT '条形码',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='辅助属性[商品]';


DROP TABLE IF EXISTS `is_attribute`;
CREATE TABLE `is_attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '属性名称',
  `sort` int(11) NOT NULL COMMENT '属性排序',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='辅助属性[基础]';


DROP TABLE IF EXISTS `is_attribute_info`;
CREATE TABLE `is_attribute_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属属性',
  `name` varchar(32) NOT NULL COMMENT '属性名称',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='属性详情表';

DROP TABLE IF EXISTS `is_batch`;
CREATE TABLE `is_batch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room` int(11) NOT NULL COMMENT '所属仓储',
  `warehouse` int(11) NOT NULL COMMENT '所属仓库',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `number` varchar(64) NOT NULL COMMENT '批次号',
  `time` int(11) DEFAULT '0' COMMENT '生产日期',
  `nums` decimal(12,4) NOT NULL COMMENT '库存数量',
  PRIMARY KEY (`id`),
  KEY `room` (`room`),
  KEY `warehouse` (`warehouse`),
  KEY `goods` (`goods`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='批次号';


DROP TABLE IF EXISTS `is_batch_info`;
CREATE TABLE `is_batch_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属批次',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `info` int(11) NOT NULL COMMENT '所属详情',
  `direction` tinyint(1) NOT NULL COMMENT '方向[0:出|1:入]',
  `nums` decimal(12,4) NOT NULL COMMENT '出入数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `type` (`type`),
  KEY `direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='批次号详情';


DROP TABLE IF EXISTS `is_bill`;
CREATE TABLE `is_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) NOT NULL COMMENT '客户',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `type` tinyint(1) NOT NULL COMMENT '核销类型[0:预收冲应收|1:预付冲应付|2:应收冲应付|3:销退冲销售|4:购退冲采购]',
  `pmy` decimal(16,4) NOT NULL COMMENT '总核金额',
  `smp` decimal(16,4) NOT NULL COMMENT '总销金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `customer` (`customer`),
  KEY `time` (`time`),
  KEY `type` (`type`),
  KEY `people` (`people`),
  KEY `examine` (`examine`),
  KEY `user` (`user`),
  KEY `supplier` (`supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='核销单';


DROP TABLE IF EXISTS `is_bill_info`;
CREATE TABLE `is_bill_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `bill` varchar(32) NOT NULL COMMENT '核销类型',
  `mold` varchar(32) NOT NULL COMMENT '单据类型',
  `money` decimal(12,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `mold` (`mold`),
  KEY `bill` (`bill`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='核销单详情';


DROP TABLE IF EXISTS `is_bor`;
CREATE TABLE `is_bor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL COMMENT '关联单据|Sor',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `arrival` int(11) NOT NULL COMMENT '到货时间',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `state` tinyint(1) NOT NULL COMMENT '入库状态[0:未入库|1:部分入库|2:已入库|3:关闭]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`supplier`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `state` (`state`),
  KEY `source` (`source`),
  KEY `people` (`people`),
  KEY `arrival` (`arrival`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购订单';


DROP TABLE IF EXISTS `is_bor_info`;
CREATE TABLE `is_bor_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `handle` decimal(12,4) DEFAULT '0.0000' COMMENT '入库数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购订单详情';


DROP TABLE IF EXISTS `is_bre`;
CREATE TABLE `is_bre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL COMMENT '关联单据|BUY',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实收金额',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `invoice` tinyint(1) NOT NULL COMMENT '发票状态[0:未开票|1:部分开票|2:已开票|3:无需开具]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`supplier`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `source` (`source`),
  KEY `people` (`people`),
  KEY `cse` (`cse`),
  KEY `invoice` (`invoice`),
  KEY `check` (`check`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购退货单';


DROP TABLE IF EXISTS `is_bre_bill`;
CREATE TABLE `is_bre_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购退货单核销详情';


DROP TABLE IF EXISTS `is_bre_info`;
CREATE TABLE `is_bre_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '关联详情|BUY',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购退货单详情';


DROP TABLE IF EXISTS `is_buy`;
CREATE TABLE `is_buy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL COMMENT '关联单据|BOR',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实付金额',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `invoice` tinyint(1) NOT NULL COMMENT '发票状态[0:未开票|1:部分开票|2:已开票|3:无需开具]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`supplier`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `source` (`source`),
  KEY `people` (`people`),
  KEY `cse` (`cse`),
  KEY `invoice` (`invoice`),
  KEY `check` (`check`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单';


DROP TABLE IF EXISTS `is_buy_bill`;
CREATE TABLE `is_buy_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单核销详情';


DROP TABLE IF EXISTS `is_buy_info`;
CREATE TABLE `is_buy_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '关联详情|BOR',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `retreat` decimal(12,4) DEFAULT '0.0000' COMMENT '退货数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单详情';


DROP TABLE IF EXISTS `is_category`;
CREATE TABLE `is_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属类别',
  `name` varchar(32) NOT NULL COMMENT '类别名称',
  `sort` int(11) DEFAULT '0' COMMENT '类别排序',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品类别';

INSERT INTO `is_category` (`id`, `pid`, `name`, `sort`, `data`) VALUES
(0,	-1,	'默认类别',	0,	'隐藏类别');

DROP TABLE IF EXISTS `is_code`;
CREATE TABLE `is_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `info` varchar(64) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '条码类型[0:条形码 | 1:二维码]',
  `data` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='条码';


DROP TABLE IF EXISTS `is_cost`;
CREATE TABLE `is_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `iet` int(11) NOT NULL COMMENT '所属收支',
  `money` decimal(16,4) NOT NULL COMMENT '金额',
  `data` varchar(256) DEFAULT '' COMMENT '备注',
  `settle` decimal(16,4) DEFAULT '0.0000' COMMENT '结算金额',
  `state` tinyint(1) DEFAULT NULL COMMENT '结算状态[0:未结算|1:部分结算|2:已结算]',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `class` (`class`),
  KEY `time` (`time`),
  KEY `iet` (`iet`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='单据费用';


DROP TABLE IF EXISTS `is_cost_info`;
CREATE TABLE `is_cost_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `oce` int(11) NOT NULL COMMENT '所属支出',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '结算金额',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `oce` (`oce`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='单据费用详情';


DROP TABLE IF EXISTS `is_customer`;
CREATE TABLE `is_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '客户名称',
  `py` varchar(32) NOT NULL COMMENT '拼音信息',
  `number` varchar(32) NOT NULL COMMENT '客户编号',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `user` int(11) NOT NULL COMMENT '所属用户',
  `category` varchar(32) NOT NULL COMMENT '客户类别',
  `grade` varchar(32) NOT NULL COMMENT '客户等级',
  `bank` varchar(32) NOT NULL COMMENT '开户银行',
  `account` varchar(64) NOT NULL COMMENT '银行账号',
  `tax` varchar(64) NOT NULL COMMENT '纳税号码',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `contacts` text NOT NULL COMMENT '联系资料',
  `balance` decimal(16,4) DEFAULT '0.0000' COMMENT '应收款余额',
  `more` text NOT NULL COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `user` (`user`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户';




SET NAMES utf8mb4;

DROP TABLE IF EXISTS `is_entry`;
CREATE TABLE `is_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '单据编号',
  `type` tinyint(1) NOT NULL COMMENT '单据类型[0:其它入库单|1:盘盈单]',
  `total` decimal(16,4) NOT NULL COMMENT '单据成本',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(265) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `supplier` (`supplier`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `type` (`type`),
  KEY `people` (`people`),
  KEY `examine` (`examine`),
  KEY `cse` (`cse`),
  KEY `check` (`check`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它入库单';


DROP TABLE IF EXISTS `is_entry_info`;
CREATE TABLE `is_entry_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '成本',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `total` decimal(12,4) NOT NULL COMMENT '总成本',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它入库单详情';


DROP TABLE IF EXISTS `is_extry`;
CREATE TABLE `is_extry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer` int(11) NOT NULL COMMENT '客户',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '单据编号',
  `type` tinyint(1) NOT NULL COMMENT '单据类型[0:其它出库单|1:盘亏单]',
  `total` decimal(16,4) NOT NULL COMMENT '单据成本',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(265) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `customer` (`customer`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `type` (`type`),
  KEY `people` (`people`),
  KEY `examine` (`examine`),
  KEY `cse` (`cse`),
  KEY `check` (`check`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它出库单';


DROP TABLE IF EXISTS `is_extry_info`;
CREATE TABLE `is_extry_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '成本',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `total` decimal(12,4) NOT NULL COMMENT '总成本',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它出库单详情';


DROP TABLE IF EXISTS `is_field`;
CREATE TABLE `is_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '模块名称',
  `key` varchar(32) NOT NULL COMMENT '模块标识',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `fields` text NOT NULL COMMENT '字段数据',
  PRIMARY KEY (`id`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='表单字段';


DROP TABLE IF EXISTS `is_fifo`;
CREATE TABLE `is_fifo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `out` int(11) NOT NULL COMMENT '所属出',
  `in` int(11) NOT NULL COMMENT '所属进',
  `handle` decimal(12,4) NOT NULL COMMENT '处理数量',
  PRIMARY KEY (`id`),
  KEY `out` (`out`),
  KEY `in` (`in`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='先进先出记录';


DROP TABLE IF EXISTS `is_frame`;
CREATE TABLE `is_frame` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `name` varchar(32) NOT NULL COMMENT '组织名称',
  `sort` int(11) DEFAULT '0' COMMENT '组织排序',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid_sort` (`pid`,`sort`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='组织机构';

INSERT INTO `is_frame` (`id`, `pid`, `name`, `sort`, `data`) VALUES
(0,	-1,	'默认组织',	0,	'隐藏组织');

DROP TABLE IF EXISTS `is_goods`;
CREATE TABLE `is_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '商品名称',
  `py` varchar(32) NOT NULL COMMENT '拼音信息',
  `number` varchar(32) NOT NULL COMMENT '商品编号',
  `spec` varchar(32) NOT NULL COMMENT '规格型号',
  `category` int(11) NOT NULL COMMENT '商品类别',
  `brand` varchar(32) NOT NULL COMMENT '商品品牌',
  `unit` varchar(32) NOT NULL COMMENT '商品单位[*:常规单位|-1:多单位]',
  `buy` decimal(12,4) NOT NULL COMMENT '采购价格',
  `sell` decimal(12,4) NOT NULL COMMENT '销售价格',
  `code` varchar(64) NOT NULL COMMENT '商品条码',
  `location` varchar(64) NOT NULL COMMENT '商品货位',
  `stock` decimal(12,4) NOT NULL COMMENT '库存阈值',
  `type` tinyint(1) NOT NULL COMMENT '产品类型[0:常规商品|1:服务商品]',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `imgs` text NOT NULL COMMENT '商品图像',
  `details` text NOT NULL COMMENT '图文详情',
  `units` text NOT NULL COMMENT '多单位配置',
  `strategy` text NOT NULL COMMENT '折扣策略',
  `serial` tinyint(1) DEFAULT '0' COMMENT '序列产品[0:关闭|1:启用]',
  `batch` tinyint(1) DEFAULT '0' COMMENT '批次产品[0:关闭|1:启用]',
  `validity` tinyint(1) DEFAULT '0' COMMENT '有效期[0:关闭|1:启用]',
  `protect` smallint(1) DEFAULT '0' COMMENT '保质期',
  `threshold` smallint(1) DEFAULT '0' COMMENT '预警阀值',
  `more` text NOT NULL COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  KEY `number` (`number`),
  KEY `name_py` (`name`,`py`),
  KEY `category` (`category`),
  KEY `code` (`code`),
  KEY `type` (`type`),
  KEY `brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品';


DROP TABLE IF EXISTS `is_ice`;
CREATE TABLE `is_ice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) DEFAULT '0' COMMENT '客户',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实收金额',
  `account` int(11) NOT NULL COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `people` (`people`),
  KEY `customer` (`customer`),
  KEY `account` (`account`),
  KEY `nucleus` (`nucleus`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它收入单';


DROP TABLE IF EXISTS `is_ice_bill`;
CREATE TABLE `is_ice_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它收入单核销详情';


DROP TABLE IF EXISTS `is_ice_info`;
CREATE TABLE `is_ice_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `iet` int(11) NOT NULL COMMENT '收支类型',
  `money` decimal(12,4) NOT NULL COMMENT '结算金额',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `iet` (`iet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它收入单详情';


DROP TABLE IF EXISTS `is_iet`;
CREATE TABLE `is_iet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '类别名称',
  `type` tinyint(1) NOT NULL COMMENT '收支类型[0:收入:1支出]',
  `sort` int(11) NOT NULL COMMENT '类别排序',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收支类别';


DROP TABLE IF EXISTS `is_imy`;
CREATE TABLE `is_imy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) NOT NULL COMMENT '客户',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`customer`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `people` (`people`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收款单';


DROP TABLE IF EXISTS `is_imy_bill`;
CREATE TABLE `is_imy_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收款单核销详情';


DROP TABLE IF EXISTS `is_imy_info`;
CREATE TABLE `is_imy_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `money` decimal(12,4) NOT NULL COMMENT '结算金额',
  `settle` varchar(256) NOT NULL COMMENT '结算号',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收款单详情';

DROP TABLE IF EXISTS `is_invoice`;
CREATE TABLE `is_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属单据',
  `time` int(11) NOT NULL COMMENT '开票时间',
  `number` varchar(64) NOT NULL COMMENT '发票号码',
  `title` varchar(64) NOT NULL COMMENT '发票抬头',
  `money` decimal(16,4) NOT NULL COMMENT '开票金额',
  `file` text NOT NULL COMMENT '发票附件',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `class` (`class`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='发票详情';


DROP TABLE IF EXISTS `is_log`;
CREATE TABLE `is_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL COMMENT '操作时间',
  `user` int(11) NOT NULL COMMENT '所属用户',
  `info` varchar(256) NOT NULL COMMENT '操作内容',
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='操作日志';


DROP TABLE IF EXISTS `is_menu`;
CREATE TABLE `is_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属菜单',
  `name` varchar(32) NOT NULL COMMENT '菜单名称',
  `key` varchar(32) NOT NULL COMMENT '菜单标识',
  `model` tinyint(1) NOT NULL COMMENT '菜单模式[0:标签模式|1:新页模式]',
  `type` tinyint(1) NOT NULL COMMENT '菜单类型[0:独立菜单|1:附属菜单]',
  `resource` varchar(128) NOT NULL COMMENT '菜单地址',
  `sort` int(11) DEFAULT '0' COMMENT '菜单排序',
  `ico` varchar(32) NOT NULL COMMENT '菜单图标',
  `root` varchar(32) NOT NULL COMMENT '权限标识',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`),
  KEY `model` (`model`),
  KEY `type` (`type`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='菜单信息';

INSERT INTO `is_menu` (`id`, `pid`, `name`, `key`, `model`, `type`, `resource`, `sort`, `ico`, `root`, `data`) VALUES
(0,	-1,	'默认菜单',	'',	0,	0,	'#group',	0,	'',	'',	'隐藏菜单'),
(18,	0,	'系统参数',	'system',	0,	0,	'#group',	7,	'el-icon-menu',	'',	'系统设置组'),
(35,	64,	'菜单管理',	'menu',	0,	0,	'/develop/menu',	4,	'',	'admin',	'管理专属'),
(58,	0,	'首页',	'home',	0,	0,	'/home',	0,	'el-icon-monitor',	'',	'主页'),
(63,	18,	'基础资料',	'base',	0,	0,	'#group',	1,	'',	'',	'基础资料组'),
(64,	0,	'系统配置',	'develop',	0,	0,	'#group',	8,	'el-icon-setting',	'',	'开发工具组'),
(66,	18,	'辅助资料',	'assist',	0,	0,	'#group',	2,	'',	'form',	'辅助资料组'),
(67,	18,	'高级设置',	'senior',	0,	0,	'#group',	3,	'',	'',	'高级设置组'),
(68,	63,	'客户管理',	'customer',	0,	0,	'/system/customer',	1,	'',	'base',	''),
(69,	63,	'供应商管理',	'supplier',	0,	0,	'/system/supplier',	2,	'',	'base',	''),
(70,	67,	'组织机构',	'frame',	0,	0,	'/system/frame',	2,	'',	'senior',	''),
(71,	67,	'用户角色',	'role',	0,	0,	'/system/role',	3,	'',	'senior',	''),
(72,	67,	'用户管理',	'user',	0,	0,	'/system/user',	4,	'',	'senior',	''),
(73,	64,	'表单字段',	'field',	0,	0,	'/develop/field',	3,	'',	'admin',	'管理专属'),
(74,	67,	'操作日志',	'log',	0,	0,	'/system/log',	6,	'',	'senior',	''),
(75,	67,	'系统设置',	'sys',	0,	0,	'/system/sys',	1,	'',	'senior',	''),
(76,	67,	'数据备份',	'backup',	0,	0,	'/system/backup',	9,	'',	'senior',	''),
(77,	66,	'商品类别',	'category',	0,	0,	'/system/category',	1,	'',	'assist',	''),
(78,	66,	'条码管理',	'code',	0,	0,	'/system/code',	2,	'',	'assist',	''),
(79,	66,	'辅助属性',	'attribute',	0,	0,	'/system/attribute',	3,	'',	'assist',	''),
(80,	66,	'常用功能',	'often',	0,	0,	'/system/often',	4,	'',	'assist',	''),
(81,	63,	'仓库管理',	'warehouse',	0,	0,	'/system/warehouse',	4,	'',	'base',	''),
(82,	63,	'资金账户',	'account',	0,	0,	'/system/account',	5,	'',	'base',	''),
(83,	63,	'商品管理',	'goods',	0,	0,	'/system/goods',	3,	'',	'base',	''),
(85,	0,	'采购',	'purchase',	0,	0,	'#group',	1,	'el-icon-shopping-cart-2',	'',	'采购组'),
(86,	85,	'采购订单',	'bor',	0,	0,	'/purchase/bor',	0,	'',	'bor|add',	''),
(87,	86,	'报表|采购订单报表',	'borForm',	0,	1,	'/purchase/borForm',	0,	'',	'bor',	''),
(88,	85,	'采购单',	'buy',	0,	0,	'/purchase/buy',	1,	'',	'buy|add',	''),
(89,	88,	'报表|采购单报表',	'buyForm',	0,	1,	'/purchase/buyForm',	0,	'',	'buy',	''),
(90,	85,	'采购退货单',	'bre',	0,	0,	'/purchase/bre',	2,	'',	'bre|add',	''),
(91,	90,	'报表|采购退货单报表',	'breForm',	0,	1,	'/purchase/breForm',	0,	'',	'bre',	''),
(92,	0,	'销售',	'sale',	0,	0,	'#group',	2,	'el-icon-shopping-cart-full',	'',	'销售组'),
(93,	92,	'销售订单',	'sor',	0,	0,	'/sale/sor',	0,	'',	'sor|add',	''),
(94,	93,	'报表|销售订单报表',	'sorForm',	0,	1,	'/sale/sorForm',	0,	'',	'sor',	''),
(95,	92,	'销售单',	'sell',	0,	0,	'/sale/sell',	1,	'',	'sell|add',	''),
(96,	95,	'报表|销售单报表',	'sellForm',	0,	1,	'/sale/sellForm',	0,	'',	'sell',	''),
(97,	92,	'销售退货单',	'sre',	0,	0,	'/sale/sre',	2,	'',	'sre|add',	''),
(98,	97,	'报表|销售退货单报表',	'sreForm',	0,	1,	'/sale/sreForm',	0,	'',	'sre',	''),
(102,	0,	'仓库',	'room',	0,	0,	'#group',	4,	'el-icon-house',	'',	'仓库组'),
(103,	102,	'其它入库单',	'entry',	0,	0,	'/room/entry',	6,	'',	'entry|add',	''),
(104,	103,	'报表|其它入库单报表',	'entryForm',	0,	1,	'/room/entryForm',	0,	'',	'entry',	''),
(105,	102,	'其它出库单',	'extry',	0,	0,	'/room/extry',	7,	'',	'extry|add',	''),
(106,	105,	'报表|其它出库单报表',	'extryForm',	0,	1,	'/room/extryForm',	0,	'',	'extry',	''),
(108,	102,	'调拨单',	'swap',	0,	0,	'/room/swap',	5,	'',	'swap|add',	''),
(109,	108,	'报表|调拨单报表',	'swapForm',	0,	1,	'/room/swapForm',	0,	'',	'swap',	''),
(116,	102,	'库存查询',	'stock',	0,	0,	'/room/stock',	1,	'',	'stock',	''),
(117,	102,	'库存盘点',	'inventory',	0,	0,	'/room/inventory',	4,	'',	'inventory',	''),
(118,	102,	'批次查询',	'batch',	0,	0,	'/room/batch',	2,	'',	'batch',	''),
(119,	102,	'序列查询',	'serial',	0,	0,	'/room/serial',	3,	'',	'serial',	''),
(120,	0,	'资金',	'fund',	0,	0,	'#group',	5,	'el-icon-coin',	'',	'资金组'),
(121,	120,	'收款单',	'imy',	0,	0,	'/fund/imy',	1,	'',	'imy|add',	''),
(122,	121,	'报表|收款单报表',	'imyForm',	0,	1,	'/fund/imyForm',	0,	'',	'imy',	''),
(123,	120,	'付款单',	'omy',	0,	0,	'/fund/omy',	2,	'',	'omy|add',	''),
(124,	123,	'报表|付款单报表',	'omyForm',	0,	1,	'/fund/omyForm',	0,	'',	'omy',	''),
(125,	120,	'核销单',	'bill',	0,	0,	'/fund/bill',	3,	'',	'bill|add',	''),
(126,	125,	'报表|核销单报表',	'billForm',	0,	1,	'/fund/billForm',	0,	'',	'bill',	''),
(127,	120,	'其它收入单',	'ice',	0,	0,	'/fund/ice',	5,	'',	'ice|add',	''),
(128,	127,	'报表|其它收入单报表 ',	'iceForm',	0,	1,	'/fund/iceForm',	0,	'',	'ice',	''),
(129,	120,	'其它支出单',	'oce',	0,	0,	'/fund/oce',	6,	'',	'oce|add',	''),
(130,	129,	'报表|其它支出单报表',	'oceForm',	0,	1,	'/fund/oceForm',	0,	'',	'oce',	''),
(131,	120,	'转账单',	'allot',	0,	0,	'/fund/allot',	4,	'',	'allot|add',	''),
(132,	131,	'报表|转账单报表',	'allotForm',	0,	1,	'/fund/allotForm',	0,	'',	'allot',	''),
(133,	67,	'人员管理',	'people',	0,	0,	'/system/people',	5,	'',	'senior',	''),
(134,	0,	'报表',	'sheet',	0,	0,	'#group',	6,	'el-icon-tickets',	'',	'报表组'),
(135,	134,	'采购报表',	'brt',	0,	0,	'#group',	1,	'',	'',	'采购报表组'),
(136,	135,	'采购订单跟踪表',	'btt',	0,	0,	'/sheet/btt',	1,	'',	'brt',	''),
(137,	135,	'采购明细表',	'blt',	0,	0,	'/sheet/blt',	2,	'',	'brt',	''),
(138,	135,	'采购汇总表',	'bsy',	0,	0,	'/sheet/bsy',	3,	'',	'brt',	''),
(140,	135,	'采购付款表',	'bbt',	0,	0,	'/sheet/bbt',	4,	'',	'brt',	''),
(141,	134,	'销售报表',	'srt',	0,	0,	'#group',	2,	'',	'',	'销售报表组'),
(142,	141,	'销售订单跟踪表',	'stt',	0,	0,	'/sheet/stt',	1,	'',	'srt',	''),
(143,	141,	'销售明细表',	'slt',	0,	0,	'/sheet/slt',	2,	'',	'srt',	''),
(144,	141,	'销售汇总表',	'ssy',	0,	0,	'/sheet/ssy',	3,	'',	'srt',	''),
(148,	141,	'销售收款表',	'sbt',	0,	0,	'/sheet/sbt',	4,	'',	'srt',	''),
(149,	165,	'销售利润表',	'mpt',	0,	0,	'/sheet/mpt',	1,	'',	'mrt',	''),
(150,	165,	'销售排行表',	'mot',	0,	0,	'/sheet/mot',	2,	'',	'mrt',	''),
(151,	134,	'仓库报表',	'wrf',	0,	0,	'#group',	5,	'',	'',	'仓库报表组'),
(152,	134,	'资金报表',	'crt',	0,	0,	'#group',	6,	'',	'',	'资金报表组'),
(153,	151,	'商品库存余额表',	'wbs',	0,	0,	'/sheet/wbs',	1,	'',	'wrf',	''),
(154,	151,	'商品收发明细表',	'wds',	0,	0,	'/sheet/wds',	2,	'',	'wrf',	''),
(155,	151,	'商品收发汇总表',	'wss',	0,	0,	'/sheet/wss',	3,	'',	'wrf',	''),
(156,	152,	'现金银行报表',	'cbf',	0,	0,	'/sheet/cbf',	1,	'',	'crt',	''),
(157,	152,	'应付账款明细表',	'cps',	0,	0,	'/sheet/cps',	3,	'',	'crt',	''),
(158,	152,	'应收账款明细表',	'crs',	0,	0,	'/sheet/crs',	2,	'',	'crt',	''),
(159,	152,	'客户对账单',	'cct',	0,	0,	'/sheet/cct',	4,	'',	'crt',	''),
(160,	152,	'供应商对账单',	'cst',	0,	0,	'/sheet/cst',	5,	'',	'crt',	''),
(161,	152,	'其它收支明细表',	'cos',	0,	0,	'/sheet/cos',	6,	'',	'crt',	''),
(162,	152,	'利润表',	'cit',	0,	0,	'/sheet/cit',	7,	'',	'crt',	''),
(163,	152,	'往来单位欠款表',	'cds',	0,	0,	'/sheet/cds',	8,	'',	'crt',	''),
(165,	134,	'销售报表',	'mrt',	0,	0,	'#group',	4,	'',	'',	'销售报表组'),
(168,	135,	'采购排行表',	'bot',	0,	0,	'/sheet/bot',	5,	'',	'brt',	''),
(170,	66,	'收支类别',	'iet',	0,	0,	'/system/iet',	1,	'',	'assist',	''),
(171,	120,	'购销发票',	'invoice',	0,	0,	'/fund/invoice',	8,	'',	'invoice',	''),
(172,	171,	'报表|购销发票报表',	'invoiceForm',	0,	1,	'/fund/invoiceForm',	0,	'',	'invoice',	''),
(173,	120,	'购销费用',	'cost',	0,	0,	'/fund/cost',	7,	'',	'cost',	''),
(174,	173,	'报表|购销费用报表',	'costForm',	0,	1,	'/fund/costForm',	0,	'',	'cost',	''),
(175,	67,	'数据核准',	'summary',	0,	0,	'/system/summary',	8,	'',	'senior',	''),
(176,	67,	'结账管理',	'period',	0,	0,	'/system/period',	7,	'',	'senior',	'');


DROP TABLE IF EXISTS `is_oce`;
CREATE TABLE `is_oce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实付金额',
  `account` int(11) NOT NULL COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `people` (`people`),
  KEY `supplier` (`supplier`),
  KEY `account` (`account`),
  KEY `nucleus` (`nucleus`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它支出单';


DROP TABLE IF EXISTS `is_oce_bill`;
CREATE TABLE `is_oce_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它支出单核销详情';


DROP TABLE IF EXISTS `is_oce_info`;
CREATE TABLE `is_oce_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '所属费用',
  `iet` int(11) NOT NULL COMMENT '收支类型',
  `money` decimal(12,4) NOT NULL COMMENT '结算金额',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `iet` (`iet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='其它支出单详情';


DROP TABLE IF EXISTS `is_often`;
CREATE TABLE `is_often` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL COMMENT '所属用户',
  `name` varchar(64) NOT NULL COMMENT '功能名称',
  `key` varchar(32) NOT NULL COMMENT '功能标识',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='常用功能';


DROP TABLE IF EXISTS `is_omy`;
CREATE TABLE `is_omy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `supplier` int(11) NOT NULL COMMENT '供应商',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`supplier`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `people` (`people`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='付款单';


DROP TABLE IF EXISTS `is_omy_bill`;
CREATE TABLE `is_omy_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='付款单核销详情';


DROP TABLE IF EXISTS `is_omy_info`;
CREATE TABLE `is_omy_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `money` decimal(12,4) NOT NULL COMMENT '结算金额',
  `settle` varchar(256) NOT NULL COMMENT '结算号',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='付款单详情';


DROP TABLE IF EXISTS `is_people`;
CREATE TABLE `is_people` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '人员名称',
  `py` varchar(32) NOT NULL COMMENT '拼音信息',
  `number` varchar(32) NOT NULL COMMENT '人员编号',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `sex` tinyint(1) NOT NULL COMMENT '人员性别[0:女|1:男]',
  `tel` varchar(32) NOT NULL COMMENT '联系电话',
  `add` varchar(64) NOT NULL COMMENT '联系地址',
  `card` varchar(32) NOT NULL COMMENT '身份证号',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `more` text NOT NULL COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  KEY `name_py` (`name`,`py`),
  KEY `frame` (`frame`),
  KEY `user` (`sex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='人员管理';


DROP TABLE IF EXISTS `is_period`;
CREATE TABLE `is_period` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL COMMENT '结账日期',
  `time` int(11) NOT NULL COMMENT '操作日期',
  `user` int(11) NOT NULL COMMENT '操作人',
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `time` (`time`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='结账表';


DROP TABLE IF EXISTS `is_record`;
CREATE TABLE `is_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '事件时间',
  `user` int(11) NOT NULL COMMENT '事件用户',
  `info` varchar(64) NOT NULL COMMENT '事件内容',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='单据记录';


DROP TABLE IF EXISTS `is_role`;
CREATE TABLE `is_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '角色名称',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `root` text NOT NULL COMMENT '功能数据',
  `auth` text NOT NULL COMMENT '数据权限',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色';


DROP TABLE IF EXISTS `is_room`;
CREATE TABLE `is_room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse` int(11) NOT NULL COMMENT '所属仓库',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `nums` decimal(12,4) NOT NULL COMMENT '库存数量',
  PRIMARY KEY (`id`),
  KEY `warehouse` (`warehouse`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓储信息';


DROP TABLE IF EXISTS `is_room_info`;
CREATE TABLE `is_room_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属仓储',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `info` int(11) NOT NULL COMMENT '所属详情',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `direction` tinyint(1) NOT NULL COMMENT '方向[0:出|1:入]',
  `price` decimal(12,4) NOT NULL COMMENT '基础单价',
  `nums` decimal(12,4) NOT NULL COMMENT '基础数量',
  PRIMARY KEY (`id`),
  KEY `room_attr` (`pid`),
  KEY `type` (`type`),
  KEY `class` (`class`),
  KEY `info` (`info`),
  KEY `time` (`time`),
  KEY `direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓储详情';


DROP TABLE IF EXISTS `is_sell`;
CREATE TABLE `is_sell` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL COMMENT '关联单据|SOR',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) NOT NULL COMMENT '客户',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实收金额',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `invoice` tinyint(1) NOT NULL COMMENT '发票状态[0:未开票|1:部分开票|2:已开票|3:无需开具]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`customer`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `people` (`people`),
  KEY `cse` (`cse`),
  KEY `invoice` (`invoice`),
  KEY `check` (`check`),
  KEY `user` (`user`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售单';


DROP TABLE IF EXISTS `is_sell_bill`;
CREATE TABLE `is_sell_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售单核销详情';


DROP TABLE IF EXISTS `is_sell_info`;
CREATE TABLE `is_sell_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '关联详情|SOR',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `retreat` decimal(12,4) DEFAULT '0.0000' COMMENT '退货数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售单详情';


DROP TABLE IF EXISTS `is_serial`;
CREATE TABLE `is_serial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room` int(11) NOT NULL COMMENT '所属仓储',
  `warehouse` int(11) NOT NULL COMMENT '所属仓库',
  `batch` int(11) DEFAULT '0' COMMENT '所属批次',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `number` varchar(64) NOT NULL COMMENT '序列号',
  `state` tinyint(1) NOT NULL COMMENT '状态[0:未销售|1:已销售|2:已调拨|3:已退货]',
  PRIMARY KEY (`id`),
  KEY `room_attr` (`room`),
  KEY `number` (`number`),
  KEY `state` (`state`),
  KEY `warehouse` (`warehouse`),
  KEY `batch` (`batch`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='序列号';


DROP TABLE IF EXISTS `is_serial_info`;
CREATE TABLE `is_serial_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属序列',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `info` int(11) NOT NULL COMMENT '所属详情',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `type` (`type`),
  KEY `class` (`class`),
  KEY `info` (`info`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='序列号详情';


DROP TABLE IF EXISTS `is_serve`;
CREATE TABLE `is_serve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `nums` decimal(12,4) NOT NULL COMMENT '累计数量',
  PRIMARY KEY (`id`),
  KEY `goods_attr` (`goods`,`attr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='服务信息';


DROP TABLE IF EXISTS `is_serve_info`;
CREATE TABLE `is_serve_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属类',
  `info` int(11) NOT NULL COMMENT '所属详情',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `type_info` (`type`,`info`),
  KEY `class` (`class`),
  KEY `info` (`info`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='服务详情';


DROP TABLE IF EXISTS `is_sor`;
CREATE TABLE `is_sor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) NOT NULL COMMENT '客户',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `arrival` int(11) NOT NULL COMMENT '到货日期',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `state` tinyint(1) NOT NULL COMMENT '出库状态[0:未出库|1:部分出库|2:已出库|3:关闭]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `supplier` (`customer`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `state` (`state`),
  KEY `people` (`people`),
  KEY `user` (`user`),
  KEY `arrival` (`arrival`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售订单';


DROP TABLE IF EXISTS `is_sor_info`;
CREATE TABLE `is_sor_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `handle` decimal(12,4) DEFAULT '0.0000' COMMENT '出库数量',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售订单详情';


DROP TABLE IF EXISTS `is_sre`;
CREATE TABLE `is_sre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL COMMENT '关联单据|SELL',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `customer` int(11) NOT NULL COMMENT '客户',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据金额',
  `actual` decimal(16,4) NOT NULL COMMENT '实际金额',
  `money` decimal(16,4) NOT NULL COMMENT '实付金额',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `account` int(11) DEFAULT '0' COMMENT '结算账户',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `logistics` text COMMENT '物流信息',
  `file` text COMMENT '单据附件',
  `data` varchar(256) DEFAULT '' COMMENT '备注信息',
  `more` text COMMENT '扩展信息',
  `examine` tinyint(1) NOT NULL COMMENT '审核状态[0:未审核|1:已审核]',
  `nucleus` tinyint(1) NOT NULL COMMENT '核销状态[0:未核销|1:部分核销|2:已核销]',
  `cse` tinyint(1) NOT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `invoice` tinyint(1) NOT NULL COMMENT '发票状态[0:未开票|1:部分开票|2:已开票|3:无需开具]',
  `check` tinyint(1) NOT NULL COMMENT '核对状态[0:未核对|1:已核对]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `examine` (`examine`),
  KEY `nucleus` (`nucleus`),
  KEY `source` (`source`),
  KEY `people` (`people`),
  KEY `customer` (`customer`),
  KEY `account` (`account`),
  KEY `cse` (`cse`),
  KEY `invoice` (`invoice`),
  KEY `check` (`check`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售退货单';


DROP TABLE IF EXISTS `is_sre_bill`;
CREATE TABLE `is_sre_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属单据',
  `type` varchar(32) NOT NULL COMMENT '核销类型',
  `source` int(11) NOT NULL COMMENT '关联单据',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `money` decimal(16,4) NOT NULL COMMENT '核销金额',
  PRIMARY KEY (`id`),
  KEY `pid_account_user` (`pid`),
  KEY `type` (`type`),
  KEY `source` (`source`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售退货单核销详情';


DROP TABLE IF EXISTS `is_sre_info`;
CREATE TABLE `is_sre_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `source` int(11) NOT NULL COMMENT '关联详情|SELL',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) DEFAULT '0' COMMENT '仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '单价',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `discount` decimal(5,2) NOT NULL COMMENT '折扣率',
  `dsc` decimal(12,4) NOT NULL COMMENT '折扣额',
  `total` decimal(12,4) NOT NULL COMMENT '金额',
  `tax` decimal(5,2) NOT NULL COMMENT '税率',
  `tat` decimal(12,4) NOT NULL COMMENT '税额',
  `tpt` decimal(12,4) NOT NULL COMMENT '价税合计',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `source` (`source`),
  KEY `goods` (`goods`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='销售退货单详情';


DROP TABLE IF EXISTS `is_summary`;
CREATE TABLE `is_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '仓储详情',
  `type` varchar(32) NOT NULL COMMENT '单据类型',
  `class` int(11) NOT NULL COMMENT '所属单据',
  `info` int(11) NOT NULL COMMENT '所属详情',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `warehouse` int(11) NOT NULL COMMENT '所属仓库',
  `batch` varchar(64) NOT NULL COMMENT '批次',
  `mfd` int(11) NOT NULL COMMENT '生产日期',
  `serial` text NOT NULL COMMENT '序列号',
  `direction` tinyint(1) NOT NULL COMMENT '方向[0:出|1:入]',
  `price` decimal(12,4) NOT NULL COMMENT '基础单价',
  `nums` decimal(12,4) NOT NULL COMMENT '基础数量',
  `uct` decimal(12,4) NOT NULL COMMENT '单位成本',
  `bct` decimal(12,4) NOT NULL COMMENT '基础成本',
  `exist` text NOT NULL COMMENT '结存组',
  `balance` text NOT NULL COMMENT '结余组',
  `handle` decimal(12,4) NOT NULL COMMENT '先进先出',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `type` (`type`),
  KEY `class` (`class`),
  KEY `info` (`info`),
  KEY `time` (`time`),
  KEY `goods` (`goods`),
  KEY `direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收发统计表';


DROP TABLE IF EXISTS `is_supplier`;
CREATE TABLE `is_supplier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '供应商名称',
  `py` varchar(32) NOT NULL COMMENT '拼音信息',
  `number` varchar(32) NOT NULL COMMENT '供应商编号',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `user` int(11) NOT NULL COMMENT '所属用户',
  `category` varchar(32) NOT NULL COMMENT '供应商类别',
  `rate` decimal(5,2) NOT NULL COMMENT '增值税税率',
  `bank` varchar(32) NOT NULL COMMENT '开户银行',
  `account` varchar(64) NOT NULL COMMENT '银行账号',
  `tax` varchar(64) NOT NULL COMMENT '纳税号码',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `contacts` text NOT NULL COMMENT '联系资料',
  `balance` decimal(16,4) DEFAULT '0.0000' COMMENT '应付款余额',
  `more` text NOT NULL COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  KEY `name_py` (`name`,`py`),
  KEY `frame` (`frame`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='供应商';


DROP TABLE IF EXISTS `is_swap`;
CREATE TABLE `is_swap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `time` int(11) NOT NULL COMMENT '单据时间',
  `number` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '单据编号',
  `total` decimal(16,4) NOT NULL COMMENT '单据成本',
  `cost` decimal(16,4) NOT NULL COMMENT '单据费用',
  `logistics` text NOT NULL COMMENT '物流信息',
  `people` int(11) DEFAULT '0' COMMENT '关联人员',
  `file` text NOT NULL COMMENT '单据附件',
  `data` varchar(265) NOT NULL COMMENT '备注信息',
  `more` text NOT NULL COMMENT '扩展信息',
  `examine` tinyint(1) DEFAULT '0' COMMENT '审核状态[0:未审核|1:已审核]',
  `cse` tinyint(1) DEFAULT NULL COMMENT '费用状态[0:未结算|1:部分结算|2:已结算|3:无需结算]',
  `user` int(11) NOT NULL COMMENT '制单人',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `time` (`time`),
  KEY `people` (`people`),
  KEY `examine` (`examine`),
  KEY `cse` (`cse`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='调拨单';


DROP TABLE IF EXISTS `is_swap_info`;
CREATE TABLE `is_swap_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '所属ID',
  `goods` int(11) NOT NULL COMMENT '所属商品',
  `attr` varchar(64) NOT NULL COMMENT '辅助属性',
  `unit` varchar(32) NOT NULL COMMENT '单位',
  `warehouse` int(11) NOT NULL COMMENT '调出仓库',
  `storehouse` int(11) NOT NULL COMMENT '调入仓库',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `mfd` int(11) DEFAULT '0' COMMENT '生产日期',
  `price` decimal(12,4) NOT NULL COMMENT '成本',
  `nums` decimal(12,4) NOT NULL COMMENT '数量',
  `serial` text NOT NULL COMMENT '序列号',
  `total` decimal(12,4) NOT NULL COMMENT '总成本',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `goods` (`goods`),
  KEY `warehouse` (`warehouse`),
  KEY `storehouse` (`storehouse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='调拨单详情';


DROP TABLE IF EXISTS `is_sys`;
CREATE TABLE `is_sys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '配置名称',
  `info` text NOT NULL COMMENT '配置内容',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统配置';

INSERT INTO `is_sys` (`id`, `name`, `info`, `data`) VALUES
(1,	'name',	'点可云进销存软件',	'软件名称'),
(2,	'company',	'山西点可云科技有限公司',	'公司名称'),
(3,	'icp',	'晋备000000-0',	'备案信息'),
(4,	'notice',	'欢迎使用点可云进销存系统\n官网地址：www.nodcloud.com\n反馈邮箱：ceo@nodcloud.com',	'公告信息'),
(5,	'brand',	'[]',	'商品品牌'),
(6,	'unit',	'[]',	'计量单位'),
(7,	'crCategory',	'[\"\\u5e38\\u89c4\\u7c7b\\u522b\"]',	'客户类别'),
(8,	'crGrade',	'[\"\\u5e38\\u89c4\\u7b49\\u7ea7\"]',	'客户等级'),
(9,	'srCategory',	'[\"\\u5e38\\u89c4\\u7c7b\\u522b\"]',	'供应商类别'),
(10,	'fun',	'{\"examine\":false,\"tax\":false,\"rate\":0,\"contain\":false,\"overflow\":false,\"valuation\":\"base\",\"branch\":0,\"rule\":\"def\",\"digit\":{\"nums\":0,\"money\":2},\"days\":\"30\"}',	'功能参数'),
(11,	'logistics',	'[{\"key\":\"auto\",\"name\":\"\\u81ea\\u52a8\\u8bc6\\u522b\",\"enable\":true},{\"key\":\"debangwuliu\",\"name\":\"\\u5fb7\\u90a6\\u7269\\u6d41\",\"enable\":true},{\"key\":\"ems\",\"name\":\"\\u90ae\\u653f\\u5feb\\u9012\",\"enable\":true},{\"key\":\"kuaijiesudi\",\"name\":\"\\u5feb\\u6377\\u901f\\u9012\",\"enable\":true},{\"key\":\"quanfengkuaidi\",\"name\":\"\\u5168\\u5cf0\\u5feb\\u9012\",\"enable\":true},{\"key\":\"shentong\",\"name\":\"\\u7533\\u901a\\u901f\\u9012\",\"enable\":true},{\"key\":\"shunfeng\",\"name\":\"\\u987a\\u4e30\\u901f\\u9012\",\"enable\":true},{\"key\":\"tiantian\",\"name\":\"\\u5929\\u5929\\u5feb\\u9012\",\"enable\":true},{\"key\":\"youshuwuliu\",\"name\":\"\\u4f18\\u901f\\u7269\\u6d41\",\"enable\":true},{\"key\":\"yuantong\",\"name\":\"\\u5706\\u901a\\u901f\\u9012\",\"enable\":true},{\"key\":\"yunda\",\"name\":\"\\u97f5\\u8fbe\\u5feb\\u8fd0\",\"enable\":true},{\"key\":\"zhongtong\",\"name\":\"\\u4e2d\\u901a\\u901f\\u9012\",\"enable\":true},{\"key\":\"htky\",\"name\":\"\\u767e\\u4e16\\u5feb\\u9012\",\"enable\":true}]',	'物流配置');


DROP TABLE IF EXISTS `is_user`;
CREATE TABLE `is_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '用户名称',
  `py` varchar(32) NOT NULL COMMENT '拼音信息',
  `tel` varchar(11) NOT NULL COMMENT '手机号码',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `role` int(11) NOT NULL COMMENT '所属角色',
  `user` varchar(32) NOT NULL COMMENT '登陆账号',
  `pwd` varchar(32) NOT NULL COMMENT '登陆密码',
  `img` text NOT NULL COMMENT '用户头像',
  `token` varchar(32) DEFAULT '' COMMENT '秘钥信息',
  `expire` int(11) NOT NULL COMMENT '秘钥时效',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  `more` text NOT NULL COMMENT '扩展信息',
  PRIMARY KEY (`id`),
  KEY `name_py` (`name`,`py`),
  KEY `frame` (`frame`),
  KEY `tel` (`tel`),
  KEY `role` (`role`),
  KEY `user` (`user`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户';

INSERT INTO `is_user` (`id`, `name`, `py`, `tel`, `frame`, `role`, `user`, `pwd`, `img`, `token`, `expire`, `data`, `more`) VALUES
(1,	'管理员',	'gly',	'18800000000',	0,	0,	'admin',	'7fef6171469e80d32c0559f88b377245',	'',	'',	0,	'管理员',	'');

DROP TABLE IF EXISTS `is_warehouse`;
CREATE TABLE `is_warehouse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '仓库名称',
  `number` varchar(32) NOT NULL COMMENT '仓库编号',
  `frame` int(11) NOT NULL COMMENT '所属组织',
  `contacts` varchar(32) NOT NULL COMMENT '联系人员',
  `tel` varchar(32) NOT NULL COMMENT '联系电话',
  `add` varchar(64) NOT NULL COMMENT '仓库地址',
  `data` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `frame` (`frame`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库';


-- 2021-04-11 01:54:46
