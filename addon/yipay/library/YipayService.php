<?php
namespace addon\yipay\library;

/**
 * 易支付核心服务类
 */
class YipayService
{
    /**
     * 易支付API地址
     */
    private $apiUrl;

    /**
     * 商户号
     */
    private $pid;

    /**
     * 商户密钥
     */
    private $key;

    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->apiUrl = isset($config['api_url']) ? rtrim($config['api_url'], '/') : '';
        $this->pid = isset($config['pid']) ? $config['pid'] : '';
        $this->key = isset($config['key']) ? $config['key'] : '';
    }

    /**
     * 生成支付请求
     * @param array $params 支付参数
     * @return string 支付链接
     */
    public function submit($params)
    {
        $data = [
            'pid' => $this->pid,
            'type' => $params['type'],
            'out_trade_no' => $params['out_trade_no'],
            'notify_url' => $params['notify_url'],
            'return_url' => $params['return_url'],
            'name' => $params['name'],
            'money' => $params['money'],
            'sign_type' => 'MD5',
        ];

        // 生成签名
        $data['sign'] = $this->sign($data);

        // 构建支付链接
        $url = $this->apiUrl . '/submit.php?' . http_build_query($data);

        return $url;
    }

    /**
     * 验证签名
     * @param array $data 待验证数据
     * @return bool 验证结果
     */
    public function verify($data)
    {
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);

        $mySign = $this->sign($data);

        return $sign === $mySign;
    }

    /**
     * 生成签名
     * @param array $params 参数数组
     * @return string 签名字符串
     */
    public function sign($params)
    {
        // 过滤空值和sign字段
        $params = array_filter($params, function($value) {
            return $value !== '' && $value !== null;
        });

        // 按key升序排列
        ksort($params);

        // 拼接成 key=value 格式
        $signStr = '';
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type') {
                $signStr .= $key . '=' . $value . '&';
            }
        }

        // 末尾加上密钥
        $signStr = rtrim($signStr, '&') . $this->key;

        // MD5加密转大写
        return strtoupper(md5($signStr));
    }

    /**
     * 查询订单状态
     * @param string $tradeNo 商户订单号
     * @return array 查询结果
     */
    public function queryOrder($tradeNo)
    {
        $data = [
            'pid' => $this->pid,
            'out_trade_no' => $tradeNo,
            'sign_type' => 'MD5',
        ];

        $data['sign'] = $this->sign($data);

        $url = $this->apiUrl . '/api.php?' . http_build_query($data);

        $response = $this->httpGet($url);

        if ($response) {
            return json_decode($response, true);
        }

        return ['code' => -1, 'msg' => '查询失败'];
    }

    /**
     * 退款订单
     * @param string $tradeNo 商户订单号
     * @param float $money 退款金额
     * @return array 退款结果
     */
    public function refundOrder($tradeNo, $money)
    {
        $data = [
            'pid' => $this->pid,
            'out_trade_no' => $tradeNo,
            'money' => $money,
            'sign_type' => 'MD5',
        ];

        $data['sign'] = $this->sign($data);

        $url = $this->apiUrl . '/refund.php';

        $response = $this->httpPost($url, $data);

        if ($response) {
            return json_decode($response, true);
        }

        return ['code' => -1, 'msg' => '退款失败'];
    }

    /**
     * HTTP GET请求
     * @param string $url 请求地址
     * @return string|false 响应内容
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * HTTP POST请求
     * @param string $url 请求地址
     * @param array $data POST数据
     * @return string|false 响应内容
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
