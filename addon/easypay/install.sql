-- Easypay支付插件安装脚本
-- 创建支付日志表

CREATE TABLE IF NOT EXISTS `niushop_easypay_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `out_trade_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `trade_no` varchar(64) DEFAULT '' COMMENT '第三方订单号',
  `pay_type` varchar(20) DEFAULT '' COMMENT '支付方式(alipay|wxpay|qqpay)',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态(0:待支付 1:已支付 2:支付失败 3:已取消)',
  `request_data` text COMMENT '请求数据(JSON)',
  `response_data` text COMMENT '响应数据(JSON)',
  `notify_data` text COMMENT '通知数据(JSON)',
  `pay_url` varchar(500) DEFAULT '' COMMENT '支付链接',
  `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
  `pay_time` int(11) DEFAULT '0' COMMENT '支付时间',
  `client_ip` varchar(50) DEFAULT '' COMMENT '客户端IP',
  `user_agent` varchar(500) DEFAULT '' COMMENT '用户代理',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_out_trade_no` (`out_trade_no`),
  KEY `idx_trade_no` (`trade_no`),
  KEY `idx_status` (`status`),
  KEY `idx_pay_type` (`pay_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Easypay支付日志表';

-- 插入插件配置记录（如果配置表存在）
INSERT IGNORE INTO `niushop_addon` (`name`, `title`, `description`, `type`, `version`, `author`, `status`, `install_time`) 
VALUES ('easypay', 'Easypay易支付', 'Easypay(Dulupay)支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式', 'addon', '1.0.0', 'niushop', 1, UNIX_TIMESTAMP());

-- 创建支付配置表（如果不存在）
CREATE TABLE IF NOT EXISTS `niushop_easypay_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `key` varchar(64) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT '' COMMENT '配置描述',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`key`),
  UNIQUE KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Easypay支付配置表';

-- 插入默认配置
INSERT IGNORE INTO `niushop_easypay_config` (`key`, `value`, `description`, `create_time`, `update_time`) VALUES
('gateway_url', 'https://api.dulupay.com/', 'Easypay网关地址', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('merchant_id', '', '商户号(PID)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('secret_key', '', '商户密钥', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('status', '0', '启用状态', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('support_type', '["alipay","wxpay","qqpay"]', '支持的支付方式', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('notify_url', '', '异步回调地址', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('return_url', '', '同步回调地址', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_prefix', 'ORDER', '订单标题前缀', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('timeout', '30', '超时时间(分钟)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('debug', '0', '调试模式', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());