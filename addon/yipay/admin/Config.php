<?php
namespace addon\yipay\admin;

use think\Controller;
use addon\yipay\library\YipayService;
use addon\yipay\model\YipayLog;

/**
 * 后台配置管理控制器
 */
class Config extends Controller
{
    /**
     * 配置页面
     * @return mixed
     */
    public function index()
    {
        if (request()->isPost()) {
            return $this->save();
        }

        // 获取当前配置
        $config = $this->getConfig();

        // 获取支付统计
        $logModel = new YipayLog();
        $statistics = $logModel->getStatistics();

        $this->assign('config', $config);
        $this->assign('statistics', $statistics);

        return $this->fetch('config');
    }

    /**
     * 保存配置
     * @return mixed
     */
    public function save()
    {
        try {
            $params = input('post.');

            // 验证必要参数
            if (empty($params['api_url'])) {
                return json([
                    'code' => 0,
                    'msg' => '易支付API地址不能为空',
                ]);
            }

            if (empty($params['pid'])) {
                return json([
                    'code' => 0,
                    'msg' => '商户号不能为空',
                ]);
            }

            if (empty($params['key'])) {
                return json([
                    'code' => 0,
                    'msg' => '商户密钥不能为空',
                ]);
            }

            // 读取配置文件
            $configFile = __DIR__ . '/../config.php';
            $config = include $configFile;

            // 更新配置
            $config['config']['api_url'] = rtrim($params['api_url'], '/');
            $config['config']['pid'] = $params['pid'];
            $config['config']['key'] = $params['key'];
            $config['config']['status'] = isset($params['status']) ? intval($params['status']) : 0;

            // 支持的支付方式
            $supportType = [];
            if (isset($params['support_alipay']) && $params['support_alipay']) {
                $supportType[] = 'alipay';
            }
            if (isset($params['support_wxpay']) && $params['support_wxpay']) {
                $supportType[] = 'wxpay';
            }
            if (isset($params['support_qqpay']) && $params['support_qqpay']) {
                $supportType[] = 'qqpay';
            }

            $config['config']['support_type'] = $supportType;

            // 写入配置文件
            $content = "<?php\n/**\n * 易支付插件配置文件\n */\nreturn " . var_export($config, true) . ";\n";

            file_put_contents($configFile, $content);

            return json([
                'code' => 1,
                'msg' => '保存成功',
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '保存失败：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 测试连接
     * @return mixed
     */
    public function test()
    {
        try {
            $apiUrl = input('api_url', '');
            $pid = input('pid', '');
            $key = input('key', '');

            if (empty($apiUrl) || empty($pid) || empty($key)) {
                return json([
                    'code' => 0,
                    'msg' => '参数不完整',
                ]);
            }

            // 创建服务
            $yipayService = new YipayService([
                'api_url' => $apiUrl,
                'pid' => $pid,
                'key' => $key,
            ]);

            // 测试查询接口（使用一个不存在的订单号）
            $testTradeNo = 'TEST_' . time();
            $result = $yipayService->queryOrder($testTradeNo);

            // 如果返回了数据，说明连接成功
            if (is_array($result)) {
                return json([
                    'code' => 1,
                    'msg' => '连接成功',
                ]);
            }

            return json([
                'code' => 0,
                'msg' => '连接失败',
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '测试失败：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 支付日志列表
     * @return mixed
     */
    public function logList()
    {
        $page = input('page', 1);
        $limit = input('limit', 20);

        $where = [];
        if (input('trade_no')) {
            $where['trade_no'] = input('trade_no');
        }
        if (input('pay_type')) {
            $where['pay_type'] = input('pay_type');
        }
        if (input('status') !== '') {
            $where['status'] = input('status');
        }

        $logModel = new YipayLog();
        $result = $logModel->getLogList($where, $page, $limit);

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $result['total'],
            'data' => $result['list'],
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
}
