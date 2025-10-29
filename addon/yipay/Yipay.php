<?php
namespace addon\yipay;

use addon\yipay\library\YipayService;
use addon\yipay\model\YipayLog;

/**
 * 易支付插件主类
 * 实现支付插件接口
 */
class Yipay
{
    /**
     * 插件配置
     */
    protected $config;

    /**
     * 易支付服务
     */
    protected $yipayService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->yipayService = new YipayService($this->config);
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

            if (!in_array($payType, $this->config['support_type'])) {
                return $this->error('不支持的支付方式');
            }

            // 检查配置
            if ($this->config['status'] != 1) {
                return $this->error('易支付已关闭');
            }

            if (empty($this->config['api_url']) || empty($this->config['pid']) || empty($this->config['key'])) {
                return $this->error('易支付配置不完整');
            }

            // 检查是否已支付
            $logModel = new YipayLog();
            $existLog = $logModel->getLogByTradeNo($params['trade_no']);

            if ($existLog && $existLog['status'] == 1) {
                return $this->error('该订单已支付');
            }

            // 构建支付参数
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';

            $notifyUrl = $scheme . '://' . $domain . '/addon/yipay/notify/notify';
            $returnUrl = $scheme . '://' . $domain . '/addon/yipay/notify/return';

            $submitParams = [
                'type' => $payType,
                'out_trade_no' => $params['trade_no'],
                'notify_url' => $notifyUrl,
                'return_url' => $returnUrl,
                'name' => isset($params['goods_name']) ? $params['goods_name'] : '商品购买',
                'money' => $params['money'],
            ];

            // 生成支付链接
            $payUrl = $this->yipayService->submit($submitParams);

            // 记录日志
            if (!$existLog) {
                $logModel->createLog([
                    'trade_no' => $params['trade_no'],
                    'out_trade_no' => $params['trade_no'],
                    'pay_type' => $payType,
                    'money' => $params['money'],
                    'status' => 0,
                ]);
            }

            return $this->success('获取支付链接成功', [
                'pay_url' => $payUrl,
                'trade_no' => $params['trade_no'],
            ]);

        } catch (\Exception $e) {
            return $this->error('支付失败：' . $e->getMessage());
        }
    }

    /**
     * 处理异步回调
     * @param array $data 回调数据
     * @return bool 处理结果
     */
    public function notify($data)
    {
        try {
            // 验证签名
            if (!$this->yipayService->verify($data)) {
                return false;
            }

            // 查询支付日志
            $logModel = new YipayLog();
            $log = $logModel->getLogByTradeNo($data['out_trade_no']);

            if (!$log) {
                return false;
            }

            // 检查是否已处理
            if ($log['status'] == 1) {
                return true;
            }

            // 更新支付状态
            $status = isset($data['trade_status']) && $data['trade_status'] == 'TRADE_SUCCESS' ? 1 : 0;

            $logModel->updateStatus($data['out_trade_no'], $status, $data);

            return $status == 1;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 处理同步回调
     * @param array $data 回调数据
     * @return bool 处理结果
     */
    public function returnNotify($data)
    {
        try {
            // 验证签名
            if (!$this->yipayService->verify($data)) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 查询支付状态
     * @param string $tradeNo 商户订单号
     * @return array 查询结果
     */
    public function query($tradeNo)
    {
        try {
            if (empty($tradeNo)) {
                return $this->error('订单号不能为空');
            }

            // 先查询本地日志
            $logModel = new YipayLog();
            $log = $logModel->getLogByTradeNo($tradeNo);

            if ($log && $log['status'] == 1) {
                return $this->success('订单已支付', [
                    'status' => 1,
                    'trade_no' => $tradeNo,
                    'pay_time' => $log['pay_time'],
                ]);
            }

            // 查询易支付订单状态
            $result = $this->yipayService->queryOrder($tradeNo);

            if (isset($result['code']) && $result['code'] == 1) {
                // 更新本地状态
                if ($log) {
                    $logModel->updateStatus($tradeNo, 1, $result);
                }

                return $this->success('查询成功', [
                    'status' => 1,
                    'trade_no' => $tradeNo,
                ]);
            }

            return $this->success('订单未支付', [
                'status' => 0,
                'trade_no' => $tradeNo,
            ]);

        } catch (\Exception $e) {
            return $this->error('查询失败：' . $e->getMessage());
        }
    }

    /**
     * 发起退款
     * @param string $tradeNo 商户订单号
     * @param float $money 退款金额
     * @return array 退款结果
     */
    public function refund($tradeNo, $money)
    {
        try {
            if (empty($tradeNo)) {
                return $this->error('订单号不能为空');
            }

            if (empty($money) || $money <= 0) {
                return $this->error('退款金额必须大于0');
            }

            // 查询支付日志
            $logModel = new YipayLog();
            $log = $logModel->getLogByTradeNo($tradeNo);

            if (!$log) {
                return $this->error('订单不存在');
            }

            if ($log['status'] != 1) {
                return $this->error('订单未支付');
            }

            if ($log['status'] == 2) {
                return $this->error('订单已退款');
            }

            if ($money > $log['money']) {
                return $this->error('退款金额不能大于支付金额');
            }

            // 调用易支付退款接口
            $result = $this->yipayService->refundOrder($tradeNo, $money);

            if (isset($result['code']) && $result['code'] == 1) {
                // 更新退款状态
                $logModel->updateStatus($tradeNo, 2, $result);

                return $this->success('退款成功', [
                    'trade_no' => $tradeNo,
                    'refund_money' => $money,
                ]);
            }

            return $this->error('退款失败：' . (isset($result['msg']) ? $result['msg'] : '未知错误'));

        } catch (\Exception $e) {
            return $this->error('退款失败：' . $e->getMessage());
        }
    }

    /**
     * 获取插件配置
     * @return array
     */
    protected function getConfig()
    {
        $configFile = __DIR__ . '/config.php';
        $config = include $configFile;

        return isset($config['config']) ? $config['config'] : [];
    }

    /**
     * 成功返回
     * @param string $msg 成功信息
     * @param array $data 返回数据
     * @return array
     */
    protected function success($msg, $data = [])
    {
        return [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
        ];
    }

    /**
     * 错误返回
     * @param string $msg 错误信息
     * @return array
     */
    protected function error($msg)
    {
        return [
            'code' => 0,
            'msg' => $msg,
        ];
    }

    /**
     * 插件安装
     * @return array
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 启用插件
     * @return bool
     */
    public function enable()
    {
        return true;
    }

    /**
     * 禁用插件
     * @return bool
     */
    public function disable()
    {
        return true;
    }

    /**
     * 获取插件信息
     * @return array
     */
    public function getInfo()
    {
        $infoFile = __DIR__ . '/info.php';
        if (file_exists($infoFile)) {
            return include $infoFile;
        }
        return [];
    }
}
