<?php
namespace addon\easypay;

use addon\easypay\library\EasypayService;
use addon\easypay\model\EasypayLog;

/**
 * Easypay插件主类
 * 实现支付插件接口
 */
class Easypay
{
    /**
     * 插件配置
     */
    protected $config;

    /**
     * Easypay服务
     */
    protected $easypayService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->easypayService = new EasypayService($this->config);
    }

    /**
     * 发起支付
     * @param array $params 支付参数
     * @return array 支付结果
     */
    public function pay($params)
    {
        try {
            // 验证参数
            if (empty($params['trade_no'])) {
                return $this->error('订单号不能为空');
            }

            if (empty($params['money']) || $params['money'] <= 0) {
                return $this->error('支付金额必须大于0');
            }

            $payType = isset($params['pay_type']) ? $params['pay_type'] : 'alipay';

            // 检查支付方式是否支持
            if (!in_array($payType, $this->config['support_type'])) {
                return $this->error('不支持的支付方式: ' . $payType);
            }

            // 构建支付参数
            $orderData = [
                'pid' => $this->config['merchant_id'],
                'type' => $payType,
                'out_trade_no' => $params['trade_no'],
                'notify_url' => $this->getNotifyUrl(),
                'return_url' => $this->getReturnUrl($params['trade_no']),
                'name' => isset($params['subject']) ? $params['subject'] : $this->config['order_prefix'] . '_' . $params['trade_no'],
                'money' => number_format($params['money'], 2, '.', ''),
                'clientip' => $this->getClientIp(),
            ];

            // 记录支付日志
            $logData = [
                'trade_no' => $params['trade_no'],
                'pay_type' => $payType,
                'money' => $params['money'],
                'status' => 0, // 待支付
                'create_time' => time(),
                'request_data' => json_encode($orderData),
            ];
            $logId = $this->createLog($logData);

            // 发起支付请求
            $result = $this->easypayService->createOrder($orderData);

            if ($result['code'] == 1) {
                // 更新日志
                $this->updateLog($logId, [
                    'response_data' => json_encode($result),
                    'pay_url' => $result['data']['pay_url'] ?? '',
                ]);

                return $this->success('支付发起成功', $result['data']);
            } else {
                // 更新日志
                $this->updateLog($logId, [
                    'response_data' => json_encode($result),
                    'error_msg' => $result['msg'] ?? '支付发起失败',
                ]);

                return $this->error($result['msg'] ?? '支付发起失败');
            }

        } catch (\Exception $e) {
            return $this->error('支付发起异常: ' . $e->getMessage());
        }
    }

    /**
     * 查询支付状态
     * @param string $tradeNo 订单号
     * @return array 查询结果
     */
    public function query($tradeNo)
    {
        try {
            if (empty($tradeNo)) {
                return $this->error('订单号不能为空');
            }

            $result = $this->easypayService->queryOrder($tradeNo);

            if ($result['code'] == 1) {
                return $this->success('查询成功', $result['data']);
            } else {
                return $this->error($result['msg'] ?? '查询失败');
            }

        } catch (\Exception $e) {
            return $this->error('查询异常: ' . $e->getMessage());
        }
    }

    /**
     * 退款
     * @param array $params 退款参数
     * @return array 退款结果
     */
    public function refund($params)
    {
        try {
            // Easypay可能不支持退款API，这里做占位实现
            return $this->error('Easypay暂不支持退款接口，请手动处理');

        } catch (\Exception $e) {
            return $this->error('退款异常: ' . $e->getMessage());
        }
    }

    /**
     * 关闭订单
     * @param string $tradeNo 订单号
     * @return array 关闭结果
     */
    public function close($tradeNo)
    {
        try {
            // Easypay可能不支持关闭订单API，这里做占位实现
            return $this->error('Easypay暂不支持关闭订单接口');

        } catch (\Exception $e) {
            return $this->error('关闭订单异常: ' . $e->getMessage());
        }
    }

    /**
     * 验证回调签名
     * @param array $params 回调参数
     * @return bool 验证结果
     */
    public function verify($params)
    {
        return $this->easypayService->verifySign($params);
    }

    /**
     * 获取插件配置
     * @return array 配置信息
     */
    protected function getConfig()
    {
        // 这里应该从系统配置中获取，暂时返回默认配置
        return [
            'gateway_url' => 'https://api.dulupay.com/',
            'merchant_id' => '',
            'secret_key' => '',
            'status' => 0,
            'support_type' => ['alipay', 'wxpay', 'qqpay'],
            'notify_url' => '',
            'return_url' => '',
            'order_prefix' => 'ORDER',
            'timeout' => 30,
            'debug' => 0,
        ];
    }

    /**
     * 获取异步回调地址
     * @return string 回调地址
     */
    protected function getNotifyUrl()
    {
        if (!empty($this->config['notify_url'])) {
            return $this->config['notify_url'];
        }
        
        // 默认回调地址
        return request()->domain() . '/addon/easypay/notify/index';
    }

    /**
     * 获取同步回调地址
     * @param string $tradeNo 订单号
     * @return string 回调地址
     */
    protected function getReturnUrl($tradeNo)
    {
        if (!empty($this->config['return_url'])) {
            return $this->config['return_url'] . '?trade_no=' . $tradeNo;
        }
        
        // 默认回调地址
        return request()->domain() . '/addon/easypay/return/index?trade_no=' . $tradeNo;
    }

    /**
     * 获取客户端IP
     * @return string IP地址
     */
    protected function getClientIp()
    {
        return request()->ip() ?? '127.0.0.1';
    }

    /**
     * 创建支付日志
     * @param array $data 日志数据
     * @return int 日志ID
     */
    protected function createLog($data)
    {
        // 这里应该使用模型创建日志记录
        // 暂时返回模拟ID
        return time();
    }

    /**
     * 更新支付日志
     * @param int $logId 日志ID
     * @param array $data 更新数据
     * @return bool 更新结果
     */
    protected function updateLog($logId, $data)
    {
        // 这里应该使用模型更新日志记录
        return true;
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