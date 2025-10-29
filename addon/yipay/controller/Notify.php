<?php
namespace addon\yipay\controller;

use think\Controller;
use addon\yipay\library\YipayService;
use addon\yipay\model\YipayLog;

/**
 * 支付回调处理控制器
 */
class Notify extends Controller
{
    /**
     * 异步回调通知
     * @return string
     */
    public function notify()
    {
        try {
            // 获取回调数据
            $data = input('post.');

            // 记录原始回调数据（用于调试）
            $this->log('notify', $data);

            // 验证必要参数
            if (empty($data['out_trade_no']) || empty($data['trade_no'])) {
                $this->log('notify_error', '缺少必要参数');
                return 'fail';
            }

            // 获取插件配置
            $config = $this->getConfig();

            // 创建支付服务
            $yipayService = new YipayService($config);

            // 验证签名
            if (!$yipayService->verify($data)) {
                $this->log('notify_error', '签名验证失败');
                return 'fail';
            }

            // 查询支付日志
            $logModel = new YipayLog();
            $log = $logModel->getLogByTradeNo($data['out_trade_no']);

            if (!$log) {
                $this->log('notify_error', '订单不存在：' . $data['out_trade_no']);
                return 'fail';
            }

            // 检查是否已处理（幂等性）
            if ($log['status'] == 1) {
                $this->log('notify_info', '订单已处理：' . $data['out_trade_no']);
                return 'success';
            }

            // 更新支付状态
            $status = isset($data['trade_status']) && $data['trade_status'] == 'TRADE_SUCCESS' ? 1 : 0;

            $logModel->updateStatus($data['out_trade_no'], $status, $data);

            // 触发支付成功事件（供主系统处理订单）
            if ($status == 1) {
                $this->onPaySuccess($data['out_trade_no'], $data);
            }

            $this->log('notify_success', '处理成功：' . $data['out_trade_no']);

            return 'success';

        } catch (\Exception $e) {
            $this->log('notify_exception', $e->getMessage());
            return 'fail';
        }
    }

    /**
     * 同步回调（跳转）
     * @return mixed
     */
    public function returnNotify()
    {
        try {
            // 获取回调数据
            $data = input('get.');

            // 记录原始回调数据
            $this->log('return', $data);

            // 验证必要参数
            if (empty($data['out_trade_no'])) {
                return $this->error('参数错误');
            }

            // 获取插件配置
            $config = $this->getConfig();

            // 创建支付服务
            $yipayService = new YipayService($config);

            // 验证签名
            if (!$yipayService->verify($data)) {
                return $this->error('签名验证失败');
            }

            // 查询支付日志
            $logModel = new YipayLog();
            $log = $logModel->getLogByTradeNo($data['out_trade_no']);

            if (!$log) {
                return $this->error('订单不存在');
            }

            // 跳转到订单详情页（根据实际业务调整）
            $redirectUrl = '/order/detail?trade_no=' . $data['out_trade_no'];

            $this->redirect($redirectUrl);

        } catch (\Exception $e) {
            return $this->error('处理失败：' . $e->getMessage());
        }
    }

    /**
     * 支付成功回调事件
     * @param string $tradeNo 商户订单号
     * @param array $data 回调数据
     * @return void
     */
    private function onPaySuccess($tradeNo, $data)
    {
        // 这里可以触发主系统的订单处理逻辑
        // 例如：更新订单状态、发货通知等

        // 方式1: 触发事件
        // event('PaySuccess', ['trade_no' => $tradeNo, 'data' => $data]);

        // 方式2: 调用订单服务
        // $orderService = new \app\common\service\OrderService();
        // $orderService->updatePayStatus($tradeNo, $data);

        $this->log('pay_success', [
            'trade_no' => $tradeNo,
            'data' => $data,
        ]);
    }

    /**
     * 获取插件配置
     * @return array
     */
    private function getConfig()
    {
        $configFile = __DIR__ . '/../config.php';
        $config = include $configFile;

        return isset($config['config']) ? $config['config'] : [];
    }

    /**
     * 记录日志
     * @param string $type 日志类型
     * @param mixed $data 日志数据
     * @return void
     */
    private function log($type, $data)
    {
        $logFile = __DIR__ . '/../runtime/' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $content = date('Y-m-d H:i:s') . ' [' . $type . '] ' . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data) . PHP_EOL;

        file_put_contents($logFile, $content, FILE_APPEND);
    }

    /**
     * 错误返回
     * @param string $msg 错误信息
     * @return string
     */
    private function error($msg)
    {
        return '<h3>回调错误</h3><p>' . $msg . '</p>';
    }
}
