<?php
/**
 * 易支付插件使用示例
 * 
 * 本文件展示了如何使用易支付插件进行支付、查询、退款等操作
 */

// ============================================
// 示例 1: 发起支付
// ============================================

use addon\yipay\Yipay;

// 创建插件实例
$yipay = new Yipay();

// 构建支付参数
$payParams = [
    'trade_no' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),  // 商户订单号（唯一）
    'money' => 99.99,                                              // 支付金额
    'pay_type' => 'alipay',                                       // 支付方式：alipay/wxpay/qqpay
    'goods_name' => 'VIP会员 - 1个月'                              // 商品名称
];

// 发起支付
$payResult = $yipay->pay($payParams);

if ($payResult['code'] == 1) {
    // 支付链接生成成功
    $payUrl = $payResult['data']['pay_url'];
    echo "支付链接：{$payUrl}\n";
    
    // 跳转到支付页面
    // header('Location: ' . $payUrl);
    // exit;
    
} else {
    // 支付失败
    echo "支付失败：{$payResult['msg']}\n";
}

// ============================================
// 示例 2: 查询订单状态
// ============================================

$tradeNo = 'ORDER_20240101120000';

$queryResult = $yipay->query($tradeNo);

if ($queryResult['code'] == 1) {
    $status = $queryResult['data']['status'];
    
    if ($status == 1) {
        echo "订单已支付\n";
        echo "支付时间：" . date('Y-m-d H:i:s', $queryResult['data']['pay_time']) . "\n";
    } else {
        echo "订单未支付\n";
    }
} else {
    echo "查询失败：{$queryResult['msg']}\n";
}

// ============================================
// 示例 3: 退款
// ============================================

$tradeNo = 'ORDER_20240101120000';
$refundMoney = 99.99;

$refundResult = $yipay->refund($tradeNo, $refundMoney);

if ($refundResult['code'] == 1) {
    echo "退款成功\n";
    echo "订单号：{$refundResult['data']['trade_no']}\n";
    echo "退款金额：{$refundResult['data']['refund_money']}\n";
} else {
    echo "退款失败：{$refundResult['msg']}\n";
}

// ============================================
// 示例 4: 直接通过URL发起支付
// ============================================

$payUrl = '/addon/yipay/index/pay?' . http_build_query([
    'trade_no' => 'ORDER_' . date('YmdHis') . rand(1000, 9999),
    'money' => 99.99,
    'pay_type' => 'wxpay',
    'goods_name' => 'VIP会员 - 1个月'
]);

echo "支付URL：{$payUrl}\n";

// 在前端跳转
// window.location.href = '<?php echo $payUrl; ?>';

// ============================================
// 示例 5: AJAX 发起支付
// ============================================
?>

<script>
// 使用 jQuery 发起支付
$.ajax({
    url: '/addon/yipay/index/pay',
    type: 'POST',
    data: {
        trade_no: 'ORDER_' + Date.now(),
        money: 99.99,
        pay_type: 'alipay',
        goods_name: 'VIP会员 - 1个月'
    },
    dataType: 'json',
    success: function(res) {
        if (res.code == 1) {
            // 跳转到支付页面
            window.location.href = res.data.pay_url;
        } else {
            alert(res.msg);
        }
    }
});
</script>

<?php
// ============================================
// 示例 6: 查询支付日志
// ============================================

use addon\yipay\model\YipayLog;

$logModel = new YipayLog();

// 查询单个订单日志
$log = $logModel->getLogByTradeNo('ORDER_20240101120000');

if ($log) {
    echo "订单号：{$log['trade_no']}\n";
    echo "支付方式：{$log['pay_type']}\n";
    echo "金额：{$log['money']}\n";
    echo "状态：{$log['status']}\n";  // 0:待支付 1:已支付 2:已退款
    echo "创建时间：" . date('Y-m-d H:i:s', $log['create_time']) . "\n";
    
    if ($log['pay_time'] > 0) {
        echo "支付时间：" . date('Y-m-d H:i:s', $log['pay_time']) . "\n";
    }
}

// 查询日志列表（分页）
$logList = $logModel->getLogList([
    'pay_type' => 'alipay',  // 筛选支付方式
    'status' => 1,           // 筛选状态
], 1, 20);

echo "总数：{$logList['total']}\n";
foreach ($logList['list'] as $item) {
    echo "订单号：{$item['trade_no']} - 金额：{$item['money']} - 状态：{$item['status']}\n";
}

// 统计数据
$statistics = $logModel->getStatistics([
    'start_time' => strtotime('2024-01-01'),
    'end_time' => strtotime('2024-12-31'),
]);

echo "订单总数：{$statistics['total_count']}\n";
echo "成功订单：{$statistics['success_count']}\n";
echo "交易总额：{$statistics['total_money']}\n";

// ============================================
// 示例 7: 手动处理回调（高级用法）
// ============================================

use addon\yipay\library\YipayService;

// 获取回调数据
$notifyData = $_POST;  // 或 $_GET

// 创建服务实例
$config = [
    'api_url' => 'http://pay.example.com',
    'pid' => '10001',
    'key' => 'your_secret_key',
];

$yipayService = new YipayService($config);

// 验证签名
if ($yipayService->verify($notifyData)) {
    echo "签名验证成功\n";
    
    // 处理业务逻辑
    $tradeNo = $notifyData['out_trade_no'];
    $status = $notifyData['trade_status'];
    
    if ($status == 'TRADE_SUCCESS') {
        // 更新订单状态
        // updateOrderStatus($tradeNo);
        echo "支付成功\n";
    }
    
    // 返回成功给易支付
    echo 'success';
    
} else {
    echo "签名验证失败\n";
    echo 'fail';
}

// ============================================
// 示例 8: 在业务代码中集成支付
// ============================================

class OrderController
{
    /**
     * 订单支付
     */
    public function pay()
    {
        // 获取订单信息
        $orderId = input('order_id');
        $order = $this->getOrder($orderId);
        
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        
        if ($order['pay_status'] == 1) {
            return json(['code' => 0, 'msg' => '订单已支付']);
        }
        
        // 使用易支付
        $yipay = new Yipay();
        
        $result = $yipay->pay([
            'trade_no' => $order['order_no'],
            'money' => $order['total_amount'],
            'pay_type' => input('pay_type', 'alipay'),
            'goods_name' => $order['goods_name'],
        ]);
        
        if ($result['code'] == 1) {
            // 返回支付链接给前端
            return json([
                'code' => 1,
                'msg' => '获取支付链接成功',
                'data' => [
                    'pay_url' => $result['data']['pay_url']
                ]
            ]);
        } else {
            return json([
                'code' => 0,
                'msg' => $result['msg']
            ]);
        }
    }
    
    /**
     * 支付成功回调处理
     */
    public function onPaySuccess($tradeNo, $notifyData)
    {
        // 根据订单号查询订单
        $order = $this->getOrderByOrderNo($tradeNo);
        
        if (!$order) {
            return false;
        }
        
        // 检查是否已处理（幂等性）
        if ($order['pay_status'] == 1) {
            return true;
        }
        
        // 更新订单状态
        $this->updateOrderPayStatus($order['id'], 1);
        
        // 发送支付成功通知
        $this->sendPaySuccessNotice($order);
        
        // 其他业务逻辑（如发货、积分等）
        // ...
        
        return true;
    }
    
    private function getOrder($orderId)
    {
        // 实现获取订单逻辑
        return [];
    }
    
    private function getOrderByOrderNo($orderNo)
    {
        // 实现根据订单号获取订单逻辑
        return [];
    }
    
    private function updateOrderPayStatus($orderId, $status)
    {
        // 实现更新订单支付状态逻辑
    }
    
    private function sendPaySuccessNotice($order)
    {
        // 实现发送支付成功通知逻辑
    }
}
?>
