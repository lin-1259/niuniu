<?php
/**
 * Easypay支付插件中文语言包
 */

return [
    // 插件基本信息
    'plugin_name' => 'Easypay易支付',
    'plugin_description' => 'Easypay(Dulupay)支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式',
    
    // 支付方式
    'pay_type_alipay' => '支付宝',
    'pay_type_wxpay' => '微信支付',
    'pay_type_qqpay' => 'QQ钱包',
    
    // 配置页面
    'config_title' => 'Easypay支付配置',
    'config_basic' => '基本配置',
    'config_payment' => '支付配置',
    'config_callback' => '回调配置',
    'config_advanced' => '高级配置',
    
    // 配置字段
    'field_status' => '启用状态',
    'field_status_enabled' => '启用',
    'field_status_disabled' => '禁用',
    'field_gateway_url' => '网关地址',
    'field_merchant_id' => '商户号(PID)',
    'field_secret_key' => '商户密钥',
    'field_support_type' => '支持的支付方式',
    'field_order_prefix' => '订单标题前缀',
    'field_timeout' => '超时时间(分钟)',
    'field_notify_url' => '异步回调地址',
    'field_return_url' => '同步回调地址',
    'field_debug' => '调试模式',
    
    // 配置说明
    'help_status' => '是否启用Easypay支付方式',
    'help_gateway_url' => 'Easypay支付网关地址，如：https://api.dulupay.com/',
    'help_merchant_id' => 'Easypay商户号，登录商户后台获取',
    'help_secret_key' => 'Easypay商户密钥，请妥善保管',
    'help_support_type' => '选择支持的支付方式',
    'help_order_prefix' => '支付订单标题的前缀，用于区分支付渠道',
    'help_timeout' => '支付订单超时时间，超过此时间订单将自动取消',
    'help_notify_url' => '支付异步通知地址，留空则使用默认地址',
    'help_return_url' => '支付同步跳转地址，留空则使用默认地址',
    'help_debug' => '开启后将记录详细的调试日志',
    
    // 操作按钮
    'btn_save' => '保存配置',
    'btn_test' => '测试连接',
    'btn_return' => '返回',
    
    // 提示信息
    'required_field' => '必填项',
    'config_save_success' => '配置保存成功',
    'config_save_failed' => '配置保存失败',
    'test_connection_success' => '连接测试成功，接口响应正常',
    'test_connection_failed' => '连接测试失败',
    'test_connection_exception' => '测试连接异常',
    'validation_required' => '请输入',
    'validation_url' => '格式不正确',
    
    // 支付状态
    'status_pending' => '待支付',
    'status_paid' => '已支付',
    'status_failed' => '支付失败',
    'status_cancelled' => '已取消',
    
    // 支付结果
    'payment_success' => '支付成功',
    'payment_failed' => '支付失败',
    'payment_pending' => '支付处理中',
    'payment_cancelled' => '支付已取消',
    
    // 错误信息
    'error_order_no_empty' => '订单号不能为空',
    'error_amount_invalid' => '支付金额必须大于0',
    'error_pay_type_unsupported' => '不支持的支付方式',
    'error_payment_failed' => '支付发起失败',
    'error_payment_exception' => '支付发起异常',
    'error_query_failed' => '查询失败',
    'error_query_exception' => '查询异常',
    'error_refund_failed' => '退款失败',
    'error_refund_exception' => '退款异常',
    'error_close_failed' => '关闭订单失败',
    'error_close_exception' => '关闭订单异常',
    'error_callback_invalid' => '回调参数异常',
    'error_signature_failed' => '签名验证失败',
    'error_order_processed' => '订单已处理过',
    'error_amount_mismatch' => '订单金额验证失败',
    'error_update_status_failed' => '更新订单状态失败',
    
    // 回调信息
    'callback_data_empty' => '回调数据为空',
    'callback_signature_failed' => '回调签名验证失败',
    'callback_status_invalid' => '订单状态异常',
    'callback_params_incomplete' => '回调参数不完整',
    'callback_processed' => '订单已处理过',
    'callback_success' => '订单支付成功',
    'callback_update_failed' => '更新订单状态失败',
    'callback_exception' => '回调处理异常',
    
    // 同步返回页面
    'return_title_success' => '支付成功',
    'return_title_failed' => '支付失败',
    'return_order_no' => '订单号',
    'return_trade_no' => '交易号',
    'return_amount' => '支付金额',
    'return_time' => '支付时间',
    'return_message' => '错误信息',
    'return_view_order' => '查看订单详情',
    'return_back_home' => '返回首页',
    
    // 日志信息
    'log_create_order_request' => '创建订单请求',
    'log_create_order_response' => '创建订单响应',
    'log_query_order_request' => '查询订单请求',
    'log_query_order_response' => '查询订单响应',
    'log_notify_data' => '异步回调数据',
    'log_return_data' => '同步回调数据',
    'log_signature_failed' => '签名验证失败',
    'log_http_error' => 'HTTP请求错误',
    
    // 支付方式说明
    'alipay_description' => '支付宝支付，支持PC和手机端',
    'wxpay_description' => '微信支付，支持扫码和H5',
    'qqpay_description' => 'QQ钱包支付，支持PC和手机端',
    
    // 安全提示
    'security_tip_1' => '务必验证回调签名，防止恶意篡改',
    'security_tip_2' => '做好幂等性处理，避免重复回调导致的问题',
    'security_tip_3' => '妥善保管商户密钥，不要泄露',
    'security_tip_4' => '生产环境建议使用HTTPS',
    'security_tip_5' => '定期查看支付日志，监控异常订单',
    
    // 版本信息
    'version' => '版本',
    'author' => '作者',
    'license' => '许可证',
];