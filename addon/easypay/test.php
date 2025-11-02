<?php
/**
 * Easypay支付插件测试脚本
 */

require_once __DIR__ . '/library/EasypayService.php';

use addon\easypay\library\EasypayService;

/**
 * 测试签名生成和验证
 */
function testSignature()
{
    echo "=== 测试签名生成和验证 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://api.dulupay.com/',
        'debug' => true,
    ];
    
    $service = new EasypayService($config);
    
    // 测试数据
    $testData = [
        'pid' => '1001',
        'type' => 'alipay',
        'out_trade_no' => 'TEST_20240101001',
        'notify_url' => 'https://example.com/notify',
        'return_url' => 'https://example.com/return',
        'name' => '测试商品',
        'money' => '100.00',
        'clientip' => '127.0.0.1',
    ];
    
    // 生成签名
    $sign = $service->generateSign($testData);
    echo "生成的签名：{$sign}\n";
    
    // 验证签名
    $testData['sign'] = $sign;
    $verifyResult = $service->verifySign($testData);
    echo "签名验证结果：" . ($verifyResult ? '通过' : '失败') . "\n";
    
    // 测试错误签名
    $testData['sign'] = 'wrong_sign';
    $verifyResult2 = $service->verifySign($testData);
    echo "错误签名验证结果：" . ($verifyResult2 ? '通过' : '失败') . "\n";
    
    echo "\n";
}

/**
 * 测试参数验证
 */
function testParameterValidation()
{
    echo "=== 测试参数验证 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://api.dulupay.com/',
        'debug' => true,
    ];
    
    $service = new EasypayService($config);
    
    // 测试缺少必要参数
    $invalidData1 = [
        'pid' => '1001',
        // 缺少 type
        'out_trade_no' => 'TEST_20240101001',
        'money' => '100.00',
    ];
    
    $result1 = $service->createOrder($invalidData1);
    echo "缺少参数测试：" . ($result1['code'] == 0 ? '通过' : '失败') . " - " . $result1['msg'] . "\n";
    
    // 测试金额为0
    $invalidData2 = [
        'pid' => '1001',
        'type' => 'alipay',
        'out_trade_no' => 'TEST_20240101001',
        'money' => '0.00',
        'notify_url' => 'https://example.com/notify',
        'return_url' => 'https://example.com/return',
        'name' => '测试商品',
    ];
    
    $result2 = $service->createOrder($invalidData2);
    echo "金额为0测试：" . ($result2['code'] == 0 ? '通过' : '失败') . " - " . $result2['msg'] . "\n";
    
    echo "\n";
}

/**
 * 测试HTTP请求
 */
function testHttpRequest()
{
    echo "=== 测试HTTP请求 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://httpbin.org/',  // 使用httpbin测试
        'debug' => true,
    ];
    
    $service = new EasypayService($config);
    
    // 使用反射方法访问protected方法进行测试
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('httpPost');
    $method->setAccessible(true);
    
    $testData = [
        'test' => 'value',
        'timestamp' => time(),
    ];
    
    $response = $method->invoke($service, 'https://httpbin.org/post', $testData);
    
    if ($response !== false) {
        echo "HTTP请求测试：通过\n";
        $responseData = json_decode($response, true);
        if (isset($responseData['form'])) {
            echo "请求参数正确传递\n";
        }
    } else {
        echo "HTTP请求测试：失败\n";
    }
    
    echo "\n";
}

/**
 * 测试响应解析
 */
function testResponseParsing()
{
    echo "=== 测试响应解析 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://api.dulupay.com/',
        'debug' => true,
    ];
    
    $service = new EasypayService($config);
    
    // 使用反射方法访问protected方法进行测试
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseResponse');
    $method->setAccessible(true);
    
    // 测试JSON响应
    $jsonResponse = '{"code":1,"msg":"success","data":{"trade_no":"123456"}}';
    $result1 = $method->invoke($service, $jsonResponse);
    echo "JSON解析测试：" . (isset($result1['code']) ? '通过' : '失败') . "\n";
    
    // 测试URL参数响应
    $urlResponse = 'code=1&msg=success&trade_no=123456';
    $result2 = $method->invoke($service, $urlResponse);
    echo "URL参数解析测试：" . (isset($result2['code']) ? '通过' : '失败') . "\n";
    
    // 测试纯文本响应
    $textResponse = 'success';
    $result3 = $method->invoke($service, $textResponse);
    echo "纯文本解析测试：" . (isset($result3['response']) ? '通过' : '失败') . "\n";
    
    echo "\n";
}

/**
 * 测试回调验证
 */
function testCallbackValidation()
{
    echo "=== 测试回调验证 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://api.dulupay.com/',
        'debug' => true,
    ];
    
    $service = new EasypayService($config);
    
    // 构造有效的回调数据
    $callbackData = [
        'pid' => '1001',
        'type' => 'alipay',
        'out_trade_no' => 'TEST_20240101001',
        'trade_no' => '202401012200123456789',
        'money' => '100.00',
        'trade_status' => 'TRADE_SUCCESS',
        'time_end' => '20240101120000',
    ];
    
    // 生成正确的签名
    $callbackData['sign'] = $service->generateSign($callbackData);
    
    // 验证有效回调
    $validResult = $service->verifySign($callbackData);
    echo "有效回调验证：" . ($validResult ? '通过' : '失败') . "\n";
    
    // 测试篡改的回调数据
    $tamperedData = $callbackData;
    $tamperedData['money'] = '200.00';  // 篡改金额
    $tamperedResult = $service->verifySign($tamperedData);
    echo "篡改回调验证：" . ($tamperedResult ? '失败' : '通过') . "\n";
    
    // 测试缺少签名的回调
    $noSignData = $callbackData;
    unset($noSignData['sign']);
    $noSignResult = $service->verifySign($noSignData);
    echo "无签名回调验证：" . ($noSignResult ? '失败' : '通过') . "\n";
    
    echo "\n";
}

/**
 * 性能测试
 */
function testPerformance()
{
    echo "=== 性能测试 ===\n";
    
    $config = [
        'merchant_id' => '1001',
        'secret_key' => 'test_secret_key_123456',
        'gateway_url' => 'https://api.dulupay.com/',
        'debug' => false,  // 关闭调试模式
    ];
    
    $service = new EasypayService($config);
    
    $testData = [
        'pid' => '1001',
        'type' => 'alipay',
        'out_trade_no' => 'TEST_20240101001',
        'notify_url' => 'https://example.com/notify',
        'return_url' => 'https://example.com/return',
        'name' => '测试商品',
        'money' => '100.00',
        'clientip' => '127.0.0.1',
    ];
    
    // 测试签名生成性能
    $startTime = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $service->generateSign($testData);
    }
    $endTime = microtime(true);
    $signTime = ($endTime - $startTime) * 1000;
    echo "1000次签名生成耗时：" . number_format($signTime, 2) . "毫秒\n";
    echo "平均每次签名耗时：" . number_format($signTime / 1000, 4) . "毫秒\n";
    
    // 测试签名验证性能
    $testData['sign'] = $service->generateSign($testData);
    $startTime = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $service->verifySign($testData);
    }
    $endTime = microtime(true);
    $verifyTime = ($endTime - $startTime) * 1000;
    echo "1000次签名验证耗时：" . number_format($verifyTime, 2) . "毫秒\n";
    echo "平均每次验证耗时：" . number_format($verifyTime / 1000, 4) . "毫秒\n";
    
    echo "\n";
}

// 运行所有测试
if (php_sapi_name() === 'cli') {
    echo "Easypay支付插件测试开始...\n\n";
    
    testSignature();
    testParameterValidation();
    testHttpRequest();
    testResponseParsing();
    testCallbackValidation();
    testPerformance();
    
    echo "所有测试完成！\n";
} else {
    echo "请在命令行环境下运行此测试脚本\n";
}