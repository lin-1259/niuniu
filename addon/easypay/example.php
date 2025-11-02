<?php
/**
 * Easypay支付插件使用示例
 */

// 引入插件类
require_once __DIR__ . '/Easypay.php';

use addon\easypay\Easypay;

/**
 * 发起支付示例
 */
function createPayment()
{
    try {
        // 创建Easypay实例
        $easypay = new Easypay();

        // 构建支付参数
        $params = [
            'trade_no' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),  // 商户订单号
            'money' => 100.00,                                            // 支付金额
            'pay_type' => 'alipay',                                       // 支付方式：alipay|wxpay|qqpay
            'subject' => '商品购买订单',                                   // 商品名称
        ];

        // 发起支付
        $result = $easypay->pay($params);

        if ($result['code'] == 1) {
            // 支付发起成功
            echo "支付发起成功！\n";
            echo "支付链接：" . $result['data']['pay_url'] . "\n";
            
            // 跳转到支付页面
            // header('Location: ' . $result['data']['pay_url']);
            
        } else {
            // 支付发起失败
            echo "支付发起失败：" . $result['msg'] . "\n";
        }

    } catch (Exception $e) {
        echo "支付异常：" . $e->getMessage() . "\n";
    }
}

/**
 * 查询支付状态示例
 */
function queryPayment()
{
    try {
        $easypay = new Easypay();
        
        $outTradeNo = 'ORDER_20240101001';  // 要查询的订单号
        
        $result = $easypay->query($outTradeNo);
        
        if ($result['code'] == 1) {
            echo "查询成功！\n";
            echo "订单状态：" . ($result['data']['trade_status'] ?? '未知') . "\n";
            echo "支付金额：" . ($result['data']['money'] ?? '0') . "\n";
            echo "支付时间：" . ($result['data']['time_end'] ?? '未知') . "\n";
        } else {
            echo "查询失败：" . $result['msg'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "查询异常：" . $e->getMessage() . "\n";
    }
}

/**
 * 退款示例
 */
function refundPayment()
{
    try {
        $easypay = new Easypay();
        
        $params = [
            'out_trade_no' => 'ORDER_20240101001',  // 原订单号
            'refund_amount' => 50.00,                // 退款金额
            'refund_reason' => '用户申请退款',        // 退款原因
        ];
        
        $result = $easypay->refund($params);
        
        if ($result['code'] == 1) {
            echo "退款成功！\n";
        } else {
            echo "退款失败：" . $result['msg'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "退款异常：" . $e->getMessage() . "\n";
    }
}

/**
 * 签名验证示例
 */
function verifySignature()
{
    try {
        $easypay = new Easypay();
        
        // 模拟回调数据
        $callbackData = [
            'pid' => '1001',
            'trade_no' => '202401012200123456789',
            'out_trade_no' => 'ORDER_20240101001',
            'type' => 'alipay',
            'money' => '100.00',
            'trade_status' => 'TRADE_SUCCESS',
            'time_end' => '20240101120000',
            'sign' => 'ABC123DEF456',  // 这个签名需要根据实际数据计算
        ];
        
        $result = $easypay->verify($callbackData);
        
        if ($result) {
            echo "签名验证通过！\n";
        } else {
            echo "签名验证失败！\n";
        }
        
    } catch (Exception $e) {
        echo "验证异常：" . $e->getMessage() . "\n";
    }
}

/**
 * 在实际项目中的集成示例
 */
function integrateWithOrderSystem()
{
    // 假设这是订单创建后的支付处理
    $orderInfo = [
        'order_id' => 'ORDER_20240101001',
        'amount' => 299.00,
        'subject' => 'iPhone 15 Pro 手机壳',
        'user_id' => 12345,
    ];
    
    try {
        $easypay = new Easypay();
        
        // 根据用户选择的支付方式确定pay_type
        $payType = 'alipay';  // 这里应该从前端获取
        
        $paymentParams = [
            'trade_no' => $orderInfo['order_id'],
            'money' => $orderInfo['amount'],
            'pay_type' => $payType,
            'subject' => $orderInfo['subject'],
        ];
        
        $result = $easypay->pay($paymentParams);
        
        if ($result['code'] == 1) {
            // 更新订单支付信息
            updateOrderPaymentInfo($orderInfo['order_id'], [
                'pay_url' => $result['data']['pay_url'],
                'pay_type' => $payType,
                'create_time' => time(),
            ]);
            
            // 返回支付链接给前端
            return [
                'success' => true,
                'pay_url' => $result['data']['pay_url'],
            ];
        } else {
            // 支付失败，记录日志
            logPaymentError($orderInfo['order_id'], $result['msg']);
            
            return [
                'success' => false,
                'message' => $result['msg'],
            ];
        }
        
    } catch (Exception $e) {
        logPaymentError($orderInfo['order_id'], $e->getMessage());
        
        return [
            'success' => false,
            'message' => '支付处理异常',
        ];
    }
}

/**
 * 更新订单支付信息（示例函数）
 */
function updateOrderPaymentInfo($orderId, $paymentInfo)
{
    // 这里应该调用订单服务更新支付信息
    echo "更新订单 {$orderId} 支付信息\n";
}

/**
 * 记录支付错误日志（示例函数）
 */
function logPaymentError($orderId, $error)
{
    // 这里应该记录支付错误日志
    echo "订单 {$orderId} 支付错误：{$error}\n";
}

// 运行示例
if (php_sapi_name() === 'cli') {
    echo "=== Easypay支付插件示例 ===\n\n";
    
    echo "1. 发起支付示例：\n";
    createPayment();
    echo "\n";
    
    echo "2. 查询支付示例：\n";
    queryPayment();
    echo "\n";
    
    echo "3. 退款示例：\n";
    refundPayment();
    echo "\n";
    
    echo "4. 签名验证示例：\n";
    verifySignature();
    echo "\n";
    
    echo "5. 订单系统集成示例：\n";
    integrateWithOrderSystem();
    echo "\n";
}