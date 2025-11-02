<?php
namespace addon\easypay\admin;

use addon\easypay\library\EasypayService;
use think\Controller;

/**
 * 后台配置管理控制器
 */
class Config extends Controller
{
    /**
     * 配置页面
     */
    public function index()
    {
        if (request()->isPost()) {
            return $this->save();
        }

        // 获取当前配置
        $config = $this->getConfig();
        
        // 支付方式选项
        $payTypes = [
            'alipay' => '支付宝',
            'wxpay' => '微信支付',
            'qqpay' => 'QQ钱包',
        ];

        return $this->fetch('config', [
            'config' => $config,
            'pay_types' => $payTypes,
        ]);
    }

    /**
     * 保存配置
     */
    public function save()
    {
        try {
            $data = input('post.');
            
            // 验证必要参数
            if (empty($data['merchant_id'])) {
                return $this->error('商户号不能为空');
            }

            if (empty($data['secret_key'])) {
                return $this->error('商户密钥不能为空');
            }

            if (empty($data['gateway_url'])) {
                return $this->error('网关地址不能为空');
            }

            // 验证网关地址格式
            if (!filter_var($data['gateway_url'], FILTER_VALIDATE_URL)) {
                return $this->error('网关地址格式不正确');
            }

            // 处理支付方式
            $supportType = $data['support_type'] ?? [];
            if (!is_array($supportType)) {
                $supportType = explode(',', $supportType);
            }

            // 构建配置数据
            $config = [
                'gateway_url' => rtrim($data['gateway_url'], '/') . '/',
                'merchant_id' => $data['merchant_id'],
                'secret_key' => $data['secret_key'],
                'status' => intval($data['status'] ?? 0),
                'support_type' => $supportType,
                'notify_url' => $data['notify_url'] ?? '',
                'return_url' => $data['return_url'] ?? '',
                'order_prefix' => $data['order_prefix'] ?? 'ORDER',
                'timeout' => intval($data['timeout'] ?? 30),
                'debug' => intval($data['debug'] ?? 0),
            ];

            // 保存配置
            $result = $this->saveConfig($config);

            if ($result) {
                return $this->success('配置保存成功');
            } else {
                return $this->error('配置保存失败');
            }

        } catch (\Exception $e) {
            return $this->error('保存配置异常: ' . $e->getMessage());
        }
    }

    /**
     * 测试连接
     */
    public function test()
    {
        try {
            $data = input('post.');
            
            if (empty($data['merchant_id']) || empty($data['secret_key']) || empty($data['gateway_url'])) {
                return $this->error('请先完善配置信息');
            }

            $config = [
                'gateway_url' => rtrim($data['gateway_url'], '/') . '/',
                'merchant_id' => $data['merchant_id'],
                'secret_key' => $data['secret_key'],
                'debug' => 1,
            ];

            // 创建服务实例
            $service = new EasypayService($config);

            // 构造测试查询参数
            $testParams = [
                'pid' => $data['merchant_id'],
                'out_trade_no' => 'TEST_' . time(),
                'action' => 'query',
            ];

            $testParams['sign'] = $service->generateSign($testParams);

            // 发送测试请求
            $url = $config['gateway_url'] . 'query.php';
            $response = $this->httpPost($url, $testParams);

            if ($response === false) {
                return $this->error('网络连接失败，请检查网关地址');
            }

            // 解析响应
            $result = $this->parseResponse($response);

            // 检查是否是有效的响应格式
            if (isset($result['code']) || isset($result['msg'])) {
                return $this->success('连接测试成功，接口响应正常');
            } else {
                return $this->error('接口响应格式异常');
            }

        } catch (\Exception $e) {
            return $this->error('测试连接异常: ' . $e->getMessage());
        }
    }

    /**
     * 获取配置
     * @return array 配置信息
     */
    protected function getConfig()
    {
        // 这里应该从系统配置中获取
        // 暂时返回默认配置
        return [
            'gateway_url' => 'https://api.dulupay.com/',
            'merchant_id' => '',
            'secret_key' => '',
            'status' => 0,
            'support_type' => ['alipay', 'wxpay'],
            'notify_url' => '',
            'return_url' => '',
            'order_prefix' => 'ORDER',
            'timeout' => 30,
            'debug' => 0,
        ];
    }

    /**
     * 保存配置
     * @param array $config 配置数据
     * @return bool 保存结果
     */
    protected function saveConfig($config)
    {
        // 这里应该调用系统配置保存方法
        // 暂时返回true
        return true;
    }

    /**
     * 发送HTTP POST请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @return string|false 响应内容
     */
    protected function httpPost($url, $data)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($response === false || !empty($error)) {
            return false;
        }

        return $response;
    }

    /**
     * 解析响应数据
     * @param string $response 响应内容
     * @return array 解析结果
     */
    protected function parseResponse($response)
    {
        // 尝试解析JSON
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        // 尝试解析URL参数格式
        parse_str($response, $result);
        if (!empty($result)) {
            return $result;
        }

        // 返回原始响应
        return ['response' => $response];
    }
}