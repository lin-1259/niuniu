<?php
namespace addon\yipay\controller;

use think\Controller;
use addon\yipay\library\YipayService;
use addon\yipay\model\YipayLog;

/**
 * 支付发起控制器
 */
class Index extends Controller
{
    /**
     * 发起支付
     * @return mixed
     */
    public function pay()
    {
        try {
            // 获取参数
            $tradeNo = input('trade_no', '');
            $money = input('money', 0);
            $payType = input('pay_type', 'alipay');
            $goodsName = input('goods_name', '商品购买');

            // 验证参数
            if (empty($tradeNo)) {
                return $this->error('订单号不能为空');
            }

            if ($money <= 0) {
                return $this->error('支付金额必须大于0');
            }

            if (!in_array($payType, ['alipay', 'wxpay', 'qqpay'])) {
                return $this->error('不支持的支付方式');
            }

            // 获取插件配置
            $config = $this->getConfig();

            if (empty($config['api_url']) || empty($config['pid']) || empty($config['key'])) {
                return $this->error('易支付配置不完整，请联系管理员');
            }

            if ($config['status'] != 1) {
                return $this->error('易支付已关闭');
            }

            // 检查是否已存在支付记录
            $logModel = new YipayLog();
            $existLog = $logModel->getLogByTradeNo($tradeNo);

            if ($existLog && $existLog['status'] == 1) {
                return $this->error('该订单已支付');
            }

            // 创建支付服务
            $yipayService = new YipayService($config);

            // 构建支付参数
            $notifyUrl = request()->domain() . '/addon/yipay/notify/notify';
            $returnUrl = request()->domain() . '/addon/yipay/notify/return';

            $params = [
                'type' => $payType,
                'out_trade_no' => $tradeNo,
                'notify_url' => $notifyUrl,
                'return_url' => $returnUrl,
                'name' => $goodsName,
                'money' => $money,
            ];

            // 生成支付链接
            $payUrl = $yipayService->submit($params);

            // 记录日志
            if (!$existLog) {
                $logModel->createLog([
                    'trade_no' => $tradeNo,
                    'out_trade_no' => $tradeNo,
                    'pay_type' => $payType,
                    'money' => $money,
                    'status' => 0,
                ]);
            }

            // 判断是否为AJAX请求
            if (request()->isAjax()) {
                return json([
                    'code' => 1,
                    'msg' => '获取支付链接成功',
                    'data' => [
                        'pay_url' => $payUrl,
                    ],
                ]);
            }

            // 跳转到支付页面
            $this->redirect($payUrl);

        } catch (\Exception $e) {
            return $this->error('支付失败：' . $e->getMessage());
        }
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
     * 错误返回
     * @param string $msg 错误信息
     * @return mixed
     */
    private function error($msg)
    {
        if (request()->isAjax()) {
            return json([
                'code' => 0,
                'msg' => $msg,
            ]);
        }

        return '<h3>支付错误</h3><p>' . $msg . '</p>';
    }
}
