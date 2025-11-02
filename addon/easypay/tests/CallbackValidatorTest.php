<?php
namespace addon\easypay\Tests;

use PHPUnit\Framework\TestCase;
use addon\easypay\library\EasypayService;

/**
 * 回调验证测试类
 */
class CallbackValidatorTest extends TestCase
{
    private $config;
    private $service;

    protected function setUp(): void
    {
        $this->config = [
            'merchant_id' => '1001',
            'secret_key' => 'test_secret_key_123456',
            'gateway_url' => 'https://api.dulupay.com/',
            'debug' => true,
        ];
        
        $this->service = new EasypayService($this->config);
    }

    /**
     * 测试有效回调验证
     */
    public function testValidCallback()
    {
        $callbackData = $this->generateValidCallbackData();
        
        $this->assertTrue($this->service->verifySign($callbackData));
    }

    /**
     * 测试篡改回调数据
     */
    public function testTamperedCallback()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 篡改金额
        $callbackData['money'] = '200.00';
        
        $this->assertFalse($this->service->verifySign($callbackData));
    }

    /**
     * 测试缺少必要参数的回调
     */
    public function testMissingParameters()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 移除必要参数
        unset($callbackData['out_trade_no']);
        
        // 重新生成签名
        $callbackData['sign'] = $this->service->generateSign($callbackData);
        
        // 验证应该通过（因为签名是基于现有参数生成的）
        $this->assertTrue($this->service->verifySign($callbackData));
    }

    /**
     * 测试空参数值处理
     */
    public function testEmptyParameters()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 添加空值参数
        $callbackData['empty_param'] = '';
        $callbackData['null_param'] = null;
        
        // 重新生成签名
        $callbackData['sign'] = $this->service->generateSign($callbackData);
        
        $this->assertTrue($this->service->verifySign($callbackData));
    }

    /**
     * 测试不同的支付方式
     */
    public function testDifferentPayTypes()
    {
        $payTypes = ['alipay', 'wxpay', 'qqpay'];
        
        foreach ($payTypes as $payType) {
            $callbackData = $this->generateValidCallbackData();
            $callbackData['type'] = $payType;
            $callbackData['sign'] = $this->service->generateSign($callbackData);
            
            $this->assertTrue($this->service->verifySign($callbackData), "支付方式 {$payType} 验证失败");
        }
    }

    /**
     * 测试不同的交易状态
     */
    public function testDifferentTradeStatus()
    {
        $statuses = ['TRADE_SUCCESS', 'TRADE_PENDING', 'TRADE_FAILED'];
        
        foreach ($statuses as $status) {
            $callbackData = $this->generateValidCallbackData();
            $callbackData['trade_status'] = $status;
            $callbackData['sign'] = $this->service->generateSign($callbackData);
            
            $this->assertTrue($this->service->verifySign($callbackData), "交易状态 {$status} 验证失败");
        }
    }

    /**
     * 测试金额格式
     */
    public function testAmountFormats()
    {
        $amounts = ['100.00', '100', '100.5', '0.01', '999999.99'];
        
        foreach ($amounts as $amount) {
            $callbackData = $this->generateValidCallbackData();
            $callbackData['money'] = $amount;
            $callbackData['sign'] = $this->service->generateSign($callbackData);
            
            $this->assertTrue($this->service->verifySign($callbackData), "金额格式 {$amount} 验证失败");
        }
    }

    /**
     * 测试时间戳参数
     */
    public function testTimestampParameters()
    {
        $timestamps = ['20240101120000', '20241231235959', '20240229120000'];
        
        foreach ($timestamps as $timestamp) {
            $callbackData = $this->generateValidCallbackData();
            $callbackData['time_end'] = $timestamp;
            $callbackData['sign'] = $this->service->generateSign($callbackData);
            
            $this->assertTrue($this->service->verifySign($callbackData), "时间戳 {$timestamp} 验证失败");
        }
    }

    /**
     * 测试URL编码参数
     */
    public function testUrlEncodedParameters()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 添加需要URL编码的参数
        $callbackData['attach'] = '附加信息&特殊字符=测试';
        $callbackData['sign'] = $this->service->generateSign($callbackData);
        
        $this->assertTrue($this->service->verifySign($callbackData));
    }

    /**
     * 测试大批量参数
     */
    public function testLargeParameterSet()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 添加大量额外参数
        for ($i = 1; $i <= 20; $i++) {
            $callbackData["extra_param_{$i}"] = "value_{$i}";
        }
        
        $callbackData['sign'] = $this->service->generateSign($callbackData);
        
        $this->assertTrue($this->service->verifySign($callbackData));
    }

    /**
     * 测试重复签名验证
     */
    public function testRepeatedVerification()
    {
        $callbackData = $this->generateValidCallbackData();
        
        // 多次验证同一个签名
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($this->service->verifySign($callbackData), "第 " . ($i + 1) . " 次验证失败");
        }
    }

    /**
     * 生成有效的回调数据
     */
    private function generateValidCallbackData()
    {
        $data = [
            'pid' => $this->config['merchant_id'],
            'type' => 'alipay',
            'out_trade_no' => 'TEST_' . date('YmdHis') . rand(1000, 9999),
            'trade_no' => '202401012200' . time() . rand(100000, 999999),
            'money' => '100.00',
            'trade_status' => 'TRADE_SUCCESS',
            'time_end' => date('YmdHis'),
        ];
        
        $data['sign'] = $this->service->generateSign($data);
        
        return $data;
    }
}