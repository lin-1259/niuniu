<?php
/**
 * Easypay插件信息文件
 * 用于插件管理系统识别和展示插件信息
 */
return [
    // 插件标识
    'name' => 'easypay',
    
    // 插件名称
    'title' => 'Easypay易支付',
    
    // 插件描述
    'description' => 'Easypay(Dulupay)支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式',
    
    // 插件类型
    'type' => 'addon',
    
    // 插件版本
    'version' => '1.0.0',
    
    // 插件作者
    'author' => 'niushop',
    
    // 作者网站
    'author_url' => '',
    
    // 插件图标
    'icon' => '',
    
    // 应用场景
    'scene' => 'pay',
    
    // 状态：1-启用，0-禁用
    'status' => 1,
    
    // 是否有配置
    'has_config' => 1,
    
    // 是否有后台管理
    'has_adminlist' => 1,
    
    // 是否有安装脚本
    'has_install' => 1,
    
    // 是否有卸载脚本
    'has_uninstall' => 1,
    
    // 依赖的系统版本
    'system_version' => '5.0.0',
    
    // 支持的支付方式
    'support_type' => ['alipay', 'wxpay', 'qqpay'],
];