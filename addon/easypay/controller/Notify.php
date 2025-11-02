<?php
namespace addon\easypay\controller;

use addon\easypay\Easypay;
use addon\easypay\model\EasypayLog;

/**
 * 支付回调处理控制器
 */
class Notify
{
    /**
     * 异步回调处理
     */
    public function index()
    {
        try {
            // 获取回调数据
            $params = input('post.');
            
            if (empty($params)) {
                $this->log('异步回调数据为空', []);
                echo 'fail';
                return;
            }

            // 记录回调日志
            $this->log('异步回调数据', $params);

            // 验证签名
            $easypay = new Easypay();
            if (!$easypay->verify($params)) {
                $this->log('异步回调签名验证失败', $params);
                echo 'fail';
                return;
            }

            // 验证订单状态
            if (!isset($params['trade_status']) || $params['trade_status'] !== 'TRADE_SUCCESS') {
                $this->log('订单状态异常', $params);
                echo 'fail';
                return;
            }

            // 获取必要参数
            $outTradeNo = $params['out_trade_no'] ?? '';
            $tradeNo = $params['trade_no'] ?? '';
            $money = $params['money'] ?? '';
            $payType = $params['type'] ?? '';

            if (empty($outTradeNo) || empty($tradeNo) || empty($money)) {
                $this->log('回调参数不完整', $params);
                echo 'fail';
                return;
            }

            // 幂等性检查 - 查询是否已经处理过
            if ($this->isProcessed($outTradeNo, $tradeNo)) {
                $this->log('订单已处理过', ['out_trade_no' => $outTradeNo, 'trade_no' => $tradeNo]);
                echo 'success';
                return;
            }

            // 验证订单金额
            if (!$this->verifyOrderAmount($outTradeNo, $money)) {
                $this->log('订单金额验证失败', ['out_trade_no' => $outTradeNo, 'money' => $money]);
                echo 'fail';
                return;
            }

            // 更新订单状态
            $result = $this->updateOrderStatus($outTradeNo, [
                'trade_no' => $tradeNo,
                'pay_type' => $payType,
                'money' => $money,
                'pay_time' => time(),
                'status' => 2, // 已支付
            ]);

            if ($result) {
                // 更新支付日志
                $this->updatePayLog($outTradeNo, [
                    'trade_no' => $tradeNo,
                    'status' => 2,
                    'pay_time' => time(),
                    'notify_data' => json_encode($params),
                ]);

                $this->log('订单支付成功', ['out_trade_no' => $outTradeNo, 'trade_no' => $tradeNo]);
                echo 'success';
            } else {
                $this->log('更新订单状态失败', ['out_trade_no' => $outTradeNo]);
                echo 'fail';
            }

        } catch (\Exception $e) {
            $this->log('异步回调处理异常', ['error' => $e->getMessage()]);
            echo 'fail';
        }
    }

    /**
     * 检查订单是否已处理
     * @param string $outTradeNo 商户订单号
     * @param string $tradeNo 第三方订单号
     * @return bool 是否已处理
     */
    protected function isProcessed($outTradeNo, $tradeNo)
    {
        // 这里应该查询数据库检查订单状态
        // 暂时返回false，实际应该查询订单是否已经是支付状态
        return false;
    }

    /**
     * 验证订单金额
     * @param string $outTradeNo 商户订单号
     * @param string $payMoney 支付金额
     * @return bool 验证结果
     */
    protected function verifyOrderAmount($outTradeNo, $payMoney)
    {
        // 这里应该查询订单原始金额进行比对
        // 暂时返回true，实际应该查询订单金额是否匹配
        return true;
    }

    /**
     * 更新订单状态
     * @param string $outTradeNo 商户订单号
     * @param array $updateData 更新数据
     * @return bool 更新结果
     */
    protected function updateOrderStatus($outTradeNo, $updateData)
    {
        // 这里应该调用订单服务更新订单状态
        // 暂时返回true，实际应该调用订单API
        return true;
    }

    /**
     * 更新支付日志
     * @param string $outTradeNo 商户订单号
     * @param array $updateData 更新数据
     * @return bool 更新结果
     */
    protected function updatePayLog($outTradeNo, $updateData)
    {
        try {
            // 这里应该更新支付日志记录
            // 暂时模拟更新操作
            return true;
        } catch (\Exception $e) {
            $this->log('更新支付日志失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 记录日志
     * @param string $title 日志标题
     * @param mixed $data 日志数据
     */
    protected function log($title, $data)
    {
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'title' => $title,
            'data' => is_array($data) ? $data : [$data],
        ];

        // 这里应该调用系统的日志记录功能
        error_log('[Easypay-Notify] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
}