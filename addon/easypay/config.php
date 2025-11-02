<?php
/**
 * Easypay插件配置文件
 */
return [
    'name' => 'easypay',
    'title' => 'Easypay易支付',
    'description' => 'Easypay(Dulupay)支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式',
    'version' => '1.0.0',
    'author' => 'niushop',
    'type' => 'addon',
    'scene' => 'pay',
    'status' => 1,
    'icon' => '',
    'support_type' => ['alipay', 'wxpay', 'qqpay'],
    'config' => [
        'gateway_url' => 'https://api.dulupay.com/',  // Easypay网关地址
        'merchant_id' => '',                          // 商户号(PID)
        'secret_key' => '',                           // 商户密钥
        'status' => 0,                                // 启用状态
        'support_type' => ['alipay', 'wxpay', 'qqpay'], // 支持的支付方式
        'notify_url' => '',                           // 异步回调地址
        'return_url' => '',                           // 同步回调地址
        'order_prefix' => 'ORDER',                    // 订单标题前缀
        'timeout' => 30,                              // 超时时间(分钟)
        'debug' => 0,                                 // 调试模式
    ],
    'hooks' => [
        'payment' => 'Easypay',
    ],
    'install' => true,
    'uninstall' => true,
];