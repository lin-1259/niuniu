<?php
namespace addon\easypay\controller;

use addon\easypay\Easypay;

/**
 * 支付同步回调处理控制器
 */
class Return
{
    /**
     * 同步回调处理
     */
    public function index()
    {
        try {
            // 获取回调参数
            $params = input('get.');
            
            if (empty($params)) {
                $this->log('同步回调数据为空', []);
                return $this->showError('回调参数异常');
            }

            // 记录回调日志
            $this->log('同步回调数据', $params);

            // 验证签名
            $easypay = new Easypay();
            if (!$easypay->verify($params)) {
                $this->log('同步回调签名验证失败', $params);
                return $this->showError('签名验证失败');
            }

            // 获取必要参数
            $outTradeNo = $params['out_trade_no'] ?? '';
            $tradeNo = $params['trade_no'] ?? '';
            $tradeStatus = $params['trade_status'] ?? '';
            $money = $params['money'] ?? '';

            if (empty($outTradeNo)) {
                $this->log('同步回调缺少订单号', $params);
                return $this->showError('订单号不能为空');
            }

            // 根据支付状态显示不同页面
            if ($tradeStatus === 'TRADE_SUCCESS') {
                // 支付成功，显示成功页面
                $this->log('同步回调支付成功', ['out_trade_no' => $outTradeNo, 'trade_no' => $tradeNo]);
                
                // 获取订单信息
                $orderInfo = $this->getOrderInfo($outTradeNo);
                
                return $this->showSuccess($orderInfo, $params);
            } else {
                // 支付失败，显示失败页面
                $this->log('同步回调支付失败', ['out_trade_no' => $outTradeNo, 'trade_status' => $tradeStatus]);
                
                return $this->showError('支付未完成或失败', $params);
            }

        } catch (\Exception $e) {
            $this->log('同步回调处理异常', ['error' => $e->getMessage()]);
            return $this->showError('处理异常: ' . $e->getMessage());
        }
    }

    /**
     * 显示支付成功页面
     * @param array $orderInfo 订单信息
     * @param array $params 回调参数
     * @return string 页面内容
     */
    protected function showSuccess($orderInfo, $params)
    {
        // 如果系统有模板引擎，这里应该渲染模板
        // 暂时返回简单的HTML页面
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>支付成功</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: #52c41a; font-size: 24px; margin-bottom: 20px; }
        .info { margin: 10px 0; }
        .btn { background: #1890ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="success">✓ 支付成功</div>
    <div class="info">订单号：' . htmlspecialchars($params['out_trade_no'] ?? '') . '</div>
    <div class="info">交易号：' . htmlspecialchars($params['trade_no'] ?? '') . '</div>
    <div class="info">支付金额：¥' . htmlspecialchars($params['money'] ?? '') . '</div>
    <div class="info">支付时间：' . date('Y-m-d H:i:s') . '</div>
    <br>
    <a href="/order/detail?order_id=' . htmlspecialchars($params['out_trade_no'] ?? '') . '" class="btn">查看订单详情</a>
    <a href="/" class="btn" style="margin-left: 10px;">返回首页</a>
</body>
</html>';

        return $html;
    }

    /**
     * 显示支付失败页面
     * @param string $message 错误消息
     * @param array $params 回调参数
     * @return string 页面内容
     */
    protected function showError($message, $params = [])
    {
        // 如果系统有模板引擎，这里应该渲染模板
        // 暂时返回简单的HTML页面
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>支付失败</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #ff4d4f; font-size: 24px; margin-bottom: 20px; }
        .info { margin: 10px 0; }
        .btn { background: #1890ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error">✗ 支付失败</div>
    <div class="info">' . htmlspecialchars($message) . '</div>';
        
        if (!empty($params['out_trade_no'])) {
            $html .= '<div class="info">订单号：' . htmlspecialchars($params['out_trade_no']) . '</div>';
        }
        
        $html .= '<br>
    <a href="/order/detail?order_id=' . htmlspecialchars($params['out_trade_no'] ?? '') . '" class="btn">查看订单详情</a>
    <a href="/" class="btn" style="margin-left: 10px;">返回首页</a>
</body>
</html>';

        return $html;
    }

    /**
     * 获取订单信息
     * @param string $outTradeNo 商户订单号
     * @return array 订单信息
     */
    protected function getOrderInfo($outTradeNo)
    {
        // 这里应该查询订单信息
        // 暂时返回模拟数据
        return [
            'order_id' => $outTradeNo,
            'status' => 'paid',
            'amount' => '0.00',
        ];
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
        error_log('[Easypay-Return] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
}