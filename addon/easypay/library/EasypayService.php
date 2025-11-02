<?php
namespace addon\easypay\library;

/**
 * Easypay核心服务类
 * 处理支付请求、签名验证、查询等核心功能
 */
class EasypayService
{
    /**
     * 配置信息
     */
    protected $config;

    /**
     * 网关地址
     */
    protected $gatewayUrl;

    /**
     * 构造函数
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        $this->gatewayUrl = rtrim($config['gateway_url'] ?? 'https://api.dulupay.com/', '/') . '/';
    }

    /**
     * 创建支付订单
     * @param array $params 订单参数
     * @return array 支付结果
     */
    public function createOrder($params)
    {
        try {
            // 验证必要参数
            $requiredFields = ['pid', 'type', 'out_trade_no', 'notify_url', 'return_url', 'name', 'money'];
            foreach ($requiredFields as $field) {
                if (empty($params[$field])) {
                    return $this->error("缺少必要参数: {$field}");
                }
            }

            // 添加签名
            $params['sign'] = $this->generateSign($params);
            $params['sign_type'] = 'MD5';

            // 发送请求
            $url = $this->gatewayUrl . 'submit.php';
            $response = $this->httpPost($url, $params);

            if ($response === false) {
                return $this->error('网络请求失败');
            }

            // 解析响应
            $result = $this->parseResponse($response);

            // 记录调试日志
            if ($this->config['debug']) {
                $this->log('创建订单请求', $params);
                $this->log('创建订单响应', $result);
            }

            // 验证响应签名
            if (isset($result['sign']) && !$this->verifySign($result)) {
                return $this->error('响应签名验证失败');
            }

            if (isset($result['code']) && $result['code'] == 1) {
                return $this->success('订单创建成功', [
                    'trade_no' => $result['trade_no'] ?? '',
                    'pay_url' => $result['pay_url'] ?? '',
                    'qrcode' => $result['qrcode'] ?? '',
                ]);
            } else {
                return $this->error($result['msg'] ?? '订单创建失败');
            }

        } catch (\Exception $e) {
            return $this->error('创建订单异常: ' . $e->getMessage());
        }
    }

    /**
     * 查询订单状态
     * @param string $outTradeNo 商户订单号
     * @return array 查询结果
     */
    public function queryOrder($outTradeNo)
    {
        try {
            $params = [
                'pid' => $this->config['merchant_id'],
                'out_trade_no' => $outTradeNo,
                'action' => 'query',
            ];

            $params['sign'] = $this->generateSign($params);
            $params['sign_type'] = 'MD5';

            $url = $this->gatewayUrl . 'query.php';
            $response = $this->httpPost($url, $params);

            if ($response === false) {
                return $this->error('网络请求失败');
            }

            $result = $this->parseResponse($response);

            // 记录调试日志
            if ($this->config['debug']) {
                $this->log('查询订单请求', $params);
                $this->log('查询订单响应', $result);
            }

            // 验证响应签名
            if (isset($result['sign']) && !$this->verifySign($result)) {
                return $this->error('响应签名验证失败');
            }

            if (isset($result['code']) && $result['code'] == 1) {
                return $this->success('查询成功', [
                    'trade_no' => $result['trade_no'] ?? '',
                    'out_trade_no' => $result['out_trade_no'] ?? '',
                    'trade_status' => $result['trade_status'] ?? '',
                    'money' => $result['money'] ?? '',
                    'time_end' => $result['time_end'] ?? '',
                ]);
            } else {
                return $this->error($result['msg'] ?? '查询失败');
            }

        } catch (\Exception $e) {
            return $this->error('查询订单异常: ' . $e->getMessage());
        }
    }

    /**
     * 生成签名
     * @param array $params 待签名参数
     * @return string 签名字符串
     */
    public function generateSign($params)
    {
        // 过滤空值和sign字段
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $key !== 'sign_type' && $value !== '' && $value !== null) {
                $filteredParams[$key] = $value;
            }
        }

        // 按键名升序排序
        ksort($filteredParams);

        // 构建签名字符串
        $signString = '';
        foreach ($filteredParams as $key => $value) {
            $signString .= $key . '=' . $value . '&';
        }
        $signString = rtrim($signString, '&');
        $signString .= $this->config['secret_key'];

        // MD5加密并转大写
        return strtoupper(md5($signString));
    }

    /**
     * 验证签名
     * @param array $params 待验证参数
     * @return bool 验证结果
     */
    public function verifySign($params)
    {
        if (!isset($params['sign'])) {
            return false;
        }

        $sign = $params['sign'];
        $calculatedSign = $this->generateSign($params);

        return $sign === $calculatedSign;
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Easypay-NiuShop-V5/1.0.0');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($response === false || !empty($error)) {
            $this->log('HTTP请求错误', ['url' => $url, 'error' => $error, 'http_code' => $httpCode]);
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

    /**
     * 记录日志
     * @param string $title 日志标题
     * @param mixed $data 日志数据
     */
    protected function log($title, $data)
    {
        if (!$this->config['debug']) {
            return;
        }

        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'title' => $title,
            'data' => is_array($data) ? $data : [$data],
        ];

        // 这里应该调用系统的日志记录功能
        error_log('[Easypay] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 返回成功结果
     * @param string $msg 消息
     * @param array $data 数据
     * @return array 结果
     */
    protected function success($msg = 'success', $data = [])
    {
        return [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
        ];
    }

    /**
     * 返回错误结果
     * @param string $msg 错误消息
     * @return array 结果
     */
    protected function error($msg = 'error')
    {
        return [
            'code' => 0,
            'msg' => $msg,
            'data' => [],
        ];
    }
}