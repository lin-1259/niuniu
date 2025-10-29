<?php
/**
 * 易支付插件测试脚本
 * 
 * 用于测试插件的各项功能是否正常
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "====================================\n";
echo "易支付插件功能测试\n";
echo "====================================\n\n";

// ============================================
// 测试 1: 签名算法测试
// ============================================
echo "【测试 1】签名算法测试\n";
echo "------------------------------------\n";

require_once __DIR__ . '/library/YipayService.php';

use addon\yipay\library\YipayService;

$config = [
    'api_url' => 'http://pay.example.com',
    'pid' => '10001',
    'key' => 'test_secret_key_123456',
];

$service = new YipayService($config);

$params = [
    'pid' => '10001',
    'type' => 'alipay',
    'out_trade_no' => 'TEST_ORDER_001',
    'money' => '100.00',
    'name' => '测试商品',
    'sign_type' => 'MD5',
];

$sign = $service->sign($params);
echo "参数：" . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";
echo "签名：{$sign}\n";

// 验证签名
$params['sign'] = $sign;
$verifyResult = $service->verify($params);
echo "签名验证结果：" . ($verifyResult ? '通过' : '失败') . "\n\n";

// ============================================
// 测试 2: 配置文件读取测试
// ============================================
echo "【测试 2】配置文件读取测试\n";
echo "------------------------------------\n";

$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    $config = include $configFile;
    echo "插件名称：{$config['name']}\n";
    echo "插件标题：{$config['title']}\n";
    echo "插件版本：{$config['version']}\n";
    echo "插件作者：{$config['author']}\n";
    echo "插件类型：{$config['type']}\n";
    echo "配置读取：成功\n\n";
} else {
    echo "配置文件不存在\n\n";
}

// ============================================
// 测试 3: 支付链接生成测试
// ============================================
echo "【测试 3】支付链接生成测试\n";
echo "------------------------------------\n";

$service = new YipayService([
    'api_url' => 'http://pay.example.com',
    'pid' => '10001',
    'key' => 'test_key',
]);

$payParams = [
    'type' => 'alipay',
    'out_trade_no' => 'TEST_' . time(),
    'notify_url' => 'http://yourdomain.com/addon/yipay/notify/notify',
    'return_url' => 'http://yourdomain.com/addon/yipay/notify/return',
    'name' => '测试商品',
    'money' => '99.99',
];

$payUrl = $service->submit($payParams);
echo "支付链接：{$payUrl}\n\n";

// ============================================
// 测试 4: 数据库表检查
// ============================================
echo "【测试 4】数据库表结构检查\n";
echo "------------------------------------\n";

$installSql = file_get_contents(__DIR__ . '/install.sql');
if ($installSql) {
    echo "安装SQL文件存在\n";
    
    // 解析表结构
    if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $installSql, $matches)) {
        echo "数据表名：{$matches[1]}\n";
    }
    
    // 检查字段
    $fields = ['id', 'trade_no', 'out_trade_no', 'pay_type', 'money', 'status', 'notify_data', 'create_time', 'pay_time'];
    $missingFields = [];
    
    foreach ($fields as $field) {
        if (strpos($installSql, "`{$field}`") === false) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "所有必需字段都存在\n";
    } else {
        echo "缺少字段：" . implode(', ', $missingFields) . "\n";
    }
    
    echo "\n";
}

// ============================================
// 测试 5: 目录结构检查
// ============================================
echo "【测试 5】目录结构检查\n";
echo "------------------------------------\n";

$requiredFiles = [
    'config.php',
    'install.sql',
    'uninstall.sql',
    'Yipay.php',
    'README.md',
    'controller/Index.php',
    'controller/Notify.php',
    'admin/Config.php',
    'model/YipayLog.php',
    'library/YipayService.php',
    'view/admin/config.html',
];

$allExists = true;

foreach ($requiredFiles as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "{$status} {$file}\n";
    
    if (!$exists) {
        $allExists = false;
    }
}

echo "\n目录结构检查：" . ($allExists ? '完整' : '不完整') . "\n\n";

// ============================================
// 测试 6: 类加载测试
// ============================================
echo "【测试 6】类加载测试\n";
echo "------------------------------------\n";

$classes = [
    'YipayService' => __DIR__ . '/library/YipayService.php',
    // 'YipayLog' => __DIR__ . '/model/YipayLog.php',
    // 'Yipay' => __DIR__ . '/Yipay.php',
];

foreach ($classes as $className => $file) {
    if (file_exists($file)) {
        require_once $file;
        echo "✓ {$className} 加载成功\n";
    } else {
        echo "✗ {$className} 文件不存在\n";
    }
}

echo "\n";

// ============================================
// 测试 7: 支付方式检查
// ============================================
echo "【测试 7】支付方式检查\n";
echo "------------------------------------\n";

$config = include __DIR__ . '/config.php';
$supportTypes = $config['config']['support_type'];

echo "支持的支付方式：\n";
foreach ($supportTypes as $type) {
    $typeNames = [
        'alipay' => '支付宝',
        'wxpay' => '微信支付',
        'qqpay' => 'QQ钱包',
    ];
    
    $name = isset($typeNames[$type]) ? $typeNames[$type] : $type;
    echo "  - {$name} ({$type})\n";
}

echo "\n";

// ============================================
// 测试 8: URL 路由检查
// ============================================
echo "【测试 8】URL 路由检查\n";
echo "------------------------------------\n";

$routes = [
    '支付发起' => '/addon/yipay/index/pay',
    '异步回调' => '/addon/yipay/notify/notify',
    '同步回调' => '/addon/yipay/notify/return',
    '后台配置' => '/addon/yipay/admin/config/index',
    '保存配置' => '/addon/yipay/admin/config/save',
    '测试连接' => '/addon/yipay/admin/config/test',
    '日志列表' => '/addon/yipay/admin/config/logList',
];

foreach ($routes as $name => $route) {
    echo "  {$name}：{$route}\n";
}

echo "\n";

// ============================================
// 测试总结
// ============================================
echo "====================================\n";
echo "测试完成！\n";
echo "====================================\n\n";

echo "提示：\n";
echo "1. 请确保已执行 install.sql 创建数据库表\n";
echo "2. 请在后台配置易支付参数后再进行实际支付测试\n";
echo "3. 测试支付时请使用易支付的测试环境\n";
echo "4. 确保回调地址可以从外网访问\n\n";

// ============================================
// 签名算法详细测试
// ============================================
echo "====================================\n";
echo "签名算法详细测试\n";
echo "====================================\n\n";

$testKey = 'test_secret_key_123456';

$testParams = [
    'pid' => '10001',
    'type' => 'alipay',
    'out_trade_no' => 'ORDER_20240101120000',
    'money' => '100.00',
    'name' => '测试商品',
];

echo "测试参数：\n";
foreach ($testParams as $key => $value) {
    echo "  {$key} = {$value}\n";
}

echo "\n商户密钥：{$testKey}\n\n";

// 排序
ksort($testParams);

echo "排序后参数：\n";
foreach ($testParams as $key => $value) {
    echo "  {$key} = {$value}\n";
}

// 拼接
$signStr = '';
foreach ($testParams as $key => $value) {
    $signStr .= $key . '=' . $value . '&';
}
$signStr = rtrim($signStr, '&');

echo "\n拼接字符串：{$signStr}\n";

// 加密钥
$signStrWithKey = $signStr . $testKey;
echo "加密钥后：{$signStrWithKey}\n";

// MD5
$md5Sign = md5($signStrWithKey);
echo "MD5加密：{$md5Sign}\n";

// 转大写
$finalSign = strtoupper($md5Sign);
echo "转大写：{$finalSign}\n\n";

echo "最终签名：{$finalSign}\n\n";

echo "====================================\n";
echo "测试脚本结束\n";
echo "====================================\n";
?>
