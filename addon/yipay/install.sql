-- 易支付日志表
CREATE TABLE IF NOT EXISTS `ns_yipay_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '商户订单号',
  `out_trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '易支付订单号',
  `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式：alipay/wxpay/qqpay',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0待支付/1已支付/2已退款',
  `notify_data` text COMMENT '回调数据JSON',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  PRIMARY KEY (`id`),
  KEY `trade_no` (`trade_no`),
  KEY `out_trade_no` (`out_trade_no`),
  KEY `status` (`status`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='易支付日志表';
