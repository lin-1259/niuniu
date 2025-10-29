<?php
/**
 * 易支付插件配置文件
 */
return [
    'name' => 'yipay',
    'title' => '易支付',
    'description' => '通用易支付插件，支持支付宝、微信、QQ钱包等多种支付方式',
    'version' => '1.0.0',
    'author' => 'niushop',
    'type' => 'addon',
    'scene' => 'pay',
    'status' => 1,
    'icon' => '',
    'support_type' => ['alipay', 'wxpay', 'qqpay'],
    'config' => [
        'api_url' => '',      // 易支付API地址
        'pid' => '',          // 商户号
        'key' => '',          // 商户密钥
        'status' => 0,        // 启用状态
        'support_type' => ['alipay', 'wxpay', 'qqpay'], // 支持的支付方式
    ],
    'hooks' => [
        'payment' => 'Yipay',
    ],
    'install' => true,
    'uninstall' => true,
];
