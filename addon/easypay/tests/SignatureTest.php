<?php
namespace addon\easypay\Tests;

use PHPUnit\Framework\TestCase;
use addon\easypay\library\EasypayService;

/**
 * 签名测试类
 */
class SignatureTest extends TestCase
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
     * 测试签名生成
     */
    public function testGenerateSign()
    {
        $params = [
            'pid' => '1001',
            'type' => 'alipay',
            'out_trade_no' => 'TEST_20240101001',
            'notify_url' => 'https://example.com/notify',
            'return_url' => 'https://example.com/return',
            'name' => '测试商品',
            'money' => '100.00',
            'clientip' => '127.0.0.1',
        ];

        $sign = $this->service->generateSign($params);

        // 验证签名不为空
        $this->assertNotEmpty($sign);
        
        // 验证签名长度为32位（MD5）
        $this->assertEquals(32, strlen($sign));
        
        // 验证签名为大写
        $this->assertEquals(strtoupper($sign), $sign);
        
        // 验证签名值（根据已知参数和密钥计算）
        $expectedSign = $this->calculateExpectedSign($params);
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试签名验证
     */
    public function testVerifySign()
    {
        $params = [
            'pid' => '1001',
            'type' => 'alipay',
            'out_trade_no' => 'TEST_20240101001',
            'notify_url' => 'https://example.com/notify',
            'return_url' => 'https://example.com/return',
            'name' => '测试商品',
            'money' => '100.00',
            'clientip' => '127.0.0.1',
        ];

        // 生成正确签名
        $sign = $this->service->generateSign($params);
        $params['sign'] = $sign;

        // 验证正确签名
        $this->assertTrue($this->service->verifySign($params));

        // 测试错误签名
        $params['sign'] = 'WRONG_SIGN';
        $this->assertFalse($this->service->verifySign($params));

        // 测试缺少签名
        unset($params['sign']);
        $this->assertFalse($this->service->verifySign($params));
    }

    /**
     * 测试参数过滤
     */
    public function testParameterFiltering()
    {
        $params = [
            'pid' => '1001',
            'type' => 'alipay',
            'out_trade_no' => 'TEST_20240101001',
            'money' => '100.00',
            'empty_value' => '',
            'null_value' => null,
            'sign' => 'should_be_ignored',
            'sign_type' => 'MD5',
        ];

        $sign1 = $this->service->generateSign($params);
        
        // 添加空值和null值，签名应该相同
        $params['another_empty'] = '';
        $params['another_null'] = null;
        
        $sign2 = $this->service->generateSign($params);
        
        $this->assertEquals($sign1, $sign2);
    }

    /**
     * 测试参数排序
     */
    public function testParameterSorting()
    {
        $params1 = [
            'z' => 'last',
            'a' => 'first',
            'm' => 'middle',
        ];

        $params2 = [
            'm' => 'middle',
            'a' => 'first',
            'z' => 'last',
        ];

        $sign1 = $this->service->generateSign($params1);
        $sign2 = $this->service->generateSign($params2);

        $this->assertEquals($sign1, $sign2);
    }

    /**
     * 测试特殊字符处理
     */
    public function testSpecialCharacters()
    {
        $params = [
            'name' => '测试商品&特殊字符=123',
            'desc' => 'This is a test with @#$%^&*()',
            'amount' => '100.00',
        ];

        $sign = $this->service->generateSign($params);
        $params['sign'] = $sign;

        $this->assertTrue($this->service->verifySign($params));
    }

    /**
     * 测试中文参数
     */
    public function testChineseParameters()
    {
        $params = [
            'subject' => '苹果iPhone 15 Pro',
            'body' => '购买手机商品',
            'attach' => '附加信息',
        ];

        $sign = $this->service->generateSign($params);
        $params['sign'] = $sign;

        $this->assertTrue($this->service->verifySign($params));
    }

    /**
     * 计算期望的签名
     */
    private function calculateExpectedSign($params)
    {
        // 过滤参数
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $key !== 'sign_type' && $value !== '' && $value !== null) {
                $filteredParams[$key] = $value;
            }
        }

        // 排序
        ksort($filteredParams);

        // 拼接
        $signString = '';
        foreach ($filteredParams as $key => $value) {
            $signString .= $key . '=' . $value . '&';
        }
        $signString = rtrim($signString, '&');
        $signString .= $this->config['secret_key'];

        // MD5加密并转大写
        return strtoupper(md5($signString));
    }
}