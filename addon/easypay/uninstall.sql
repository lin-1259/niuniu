-- Easypay支付插件卸载脚本
-- 删除支付日志表
DROP TABLE IF EXISTS `niushop_easypay_log`;

-- 删除配置表
DROP TABLE IF EXISTS `niushop_easypay_config`;

-- 删除插件记录
DELETE FROM `niushop_addon` WHERE `name` = 'easypay';