# 易支付插件 - 快速入门指南

## 5分钟快速集成

### 第一步：安装插件

```bash
# 1. 上传插件文件到 addon/yipay/ 目录

# 2. 创建数据库表
mysql -u用户名 -p密码 数据库名 < addon/yipay/install.sql
```

### 第二步：配置参数

访问后台配置页面：`http://yourdomain.com/addon/yipay/admin/config/index`

填写以下信息：
- **API地址**：`http://pay.example.com`
- **商户号**：`10001`
- **商户密钥**：`your_secret_key`
- **启用状态**：开启
- **支付方式**：勾选需要的支付方式

点击"测试连接"确保配置正确。

### 第三步：配置回调地址

在易支付平台后台设置：
- **异步回调**：`http://yourdomain.com/addon/yipay/notify/notify`
- **同步回调**：`http://yourdomain.com/addon/yipay/notify/return`

### 第四步：发起支付

在您的代码中使用：

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->pay([
    'trade_no' => 'ORDER_' . time(),
    'money' => 99.99,
    'pay_type' => 'alipay',
    'goods_name' => 'VIP会员'
]);

if ($result['code'] == 1) {
    // 跳转到支付页面
    header('Location: ' . $result['data']['pay_url']);
}
```

完成！用户完成支付后，系统会自动处理回调并更新订单状态。

---

## 常用操作

### 查询订单状态

```php
$yipay = new Yipay();
$result = $yipay->query('ORDER_20240101001');

if ($result['data']['status'] == 1) {
    echo '已支付';
}
```

### 发起退款

```php
$yipay = new Yipay();
$result = $yipay->refund('ORDER_20240101001', 99.99);

if ($result['code'] == 1) {
    echo '退款成功';
}
```

### 查询支付日志

```php
use addon\yipay\model\YipayLog;

$logModel = new YipayLog();

// 查询单条
$log = $logModel->getLogByTradeNo('ORDER_20240101001');

// 查询列表
$result = $logModel->getLogList([], 1, 20);
```

---

## 使用场景

### 场景1：商城订单支付

```php
class OrderController
{
    public function pay($orderId)
    {
        // 获取订单
        $order = $this->getOrder($orderId);
        
        // 发起支付
        $yipay = new \addon\yipay\Yipay();
        $result = $yipay->pay([
            'trade_no' => $order['order_no'],
            'money' => $order['total_amount'],
            'pay_type' => input('pay_type', 'alipay'),
            'goods_name' => $order['goods_name']
        ]);
        
        if ($result['code'] == 1) {
            return json($result);
        }
    }
}
```

### 场景2：会员充值

```php
public function recharge()
{
    $userId = session('user_id');
    $money = input('money', 0);
    
    // 创建充值订单
    $rechargeNo = 'RECHARGE_' . time() . $userId;
    
    // 发起支付
    $yipay = new \addon\yipay\Yipay();
    $result = $yipay->pay([
        'trade_no' => $rechargeNo,
        'money' => $money,
        'pay_type' => 'wxpay',
        'goods_name' => '账户充值'
    ]);
    
    return json($result);
}
```

### 场景3：VIP购买

```php
public function buyVip()
{
    $userId = session('user_id');
    $vipLevel = input('level', 1);
    
    // VIP价格
    $prices = [1 => 30, 2 => 88, 3 => 198];
    $money = $prices[$vipLevel];
    
    // 发起支付
    $yipay = new \addon\yipay\Yipay();
    $result = $yipay->pay([
        'trade_no' => 'VIP_' . time() . $userId,
        'money' => $money,
        'pay_type' => 'alipay',
        'goods_name' => 'VIP等级' . $vipLevel
    ]);
    
    return json($result);
}
```

---

## 前端集成

### HTML表单方式

```html
<form action="/addon/yipay/index/pay" method="POST">
    <input type="hidden" name="trade_no" value="ORDER_001">
    <input type="hidden" name="money" value="99.99">
    <input type="hidden" name="goods_name" value="商品名称">
    
    <button type="submit" name="pay_type" value="alipay">支付宝支付</button>
    <button type="submit" name="pay_type" value="wxpay">微信支付</button>
    <button type="submit" name="pay_type" value="qqpay">QQ支付</button>
</form>
```

### AJAX方式

```javascript
function pay(payType) {
    $.ajax({
        url: '/addon/yipay/index/pay',
        type: 'POST',
        data: {
            trade_no: 'ORDER_' + Date.now(),
            money: 99.99,
            pay_type: payType,
            goods_name: 'VIP会员'
        },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                // 跳转到支付页面
                window.location.href = res.data.pay_url;
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('请求失败');
        }
    });
}
```

### 二维码支付

```javascript
function qrcodePay() {
    $.ajax({
        url: '/addon/yipay/index/pay',
        type: 'POST',
        data: {
            trade_no: 'ORDER_' + Date.now(),
            money: 99.99,
            pay_type: 'wxpay',
            goods_name: 'VIP会员'
        },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                // 显示二维码
                $('#qrcode').html('');
                new QRCode(document.getElementById('qrcode'), {
                    text: res.data.pay_url,
                    width: 256,
                    height: 256
                });
                
                // 显示弹窗
                $('#qrcodeModal').modal('show');
                
                // 轮询查询支付状态
                checkPayStatus();
            }
        }
    });
}

function checkPayStatus() {
    var timer = setInterval(function() {
        $.ajax({
            url: '/addon/yipay/query',
            data: { trade_no: currentTradeNo },
            success: function(res) {
                if (res.data.status == 1) {
                    clearInterval(timer);
                    alert('支付成功！');
                    location.reload();
                }
            }
        });
    }, 3000);
}
```

---

## 移动端集成

### 手机浏览器

```php
// 自动识别并跳转到对应的支付APP
$yipay = new \addon\yipay\Yipay();

$result = $yipay->pay([
    'trade_no' => 'ORDER_' . time(),
    'money' => 99.99,
    'pay_type' => 'wxpay',  // 会跳转到微信APP
    'goods_name' => 'VIP会员'
]);

if ($result['code'] == 1) {
    header('Location: ' . $result['data']['pay_url']);
}
```

### 微信内支付

```javascript
// 在微信浏览器中
if (isWechat()) {
    pay('wxpay');
}

function isWechat() {
    return /MicroMessenger/i.test(navigator.userAgent);
}
```

---

## 调试技巧

### 1. 查看日志

```bash
tail -f addon/yipay/runtime/$(date +%Y-%m-%d).log
```

### 2. 运行测试脚本

```bash
php addon/yipay/test.php
```

### 3. 测试签名算法

```php
use addon\yipay\library\YipayService;

$service = new YipayService([
    'api_url' => 'http://pay.example.com',
    'pid' => '10001',
    'key' => 'your_key'
]);

$params = [
    'pid' => '10001',
    'type' => 'alipay',
    'out_trade_no' => 'TEST_001',
    'money' => '100.00'
];

$sign = $service->sign($params);
echo "签名：{$sign}\n";
```

### 4. 模拟回调

```bash
curl -X POST http://yourdomain.com/addon/yipay/notify/notify \
  -d "pid=10001" \
  -d "trade_no=YIPAY123456" \
  -d "out_trade_no=ORDER_001" \
  -d "type=alipay" \
  -d "money=100.00" \
  -d "trade_status=TRADE_SUCCESS" \
  -d "sign=YOUR_SIGN"
```

---

## 常见问题速查

| 问题 | 解决方案 |
|------|----------|
| 签名验证失败 | 检查商户密钥是否正确 |
| 无法连接API | 检查服务器网络和API地址 |
| 支付后无回调 | 确保回调地址可外网访问 |
| 重复支付 | 插件已做幂等性处理，无需担心 |
| 金额不对 | 使用 decimal 类型，保留2位小数 |

---

## 下一步

- 阅读 [完整文档](README.md)
- 查看 [API文档](API.md)
- 参考 [使用示例](example.php)
- 了解 [安装说明](INSTALL.md)

---

## 获取帮助

遇到问题？

1. 查看文档
2. 运行测试脚本
3. 查看日志文件
4. 联系技术支持

祝您使用愉快！🎉
