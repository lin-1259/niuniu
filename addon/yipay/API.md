# 易支付插件 API 文档

## 目录

- [插件主类 API](#插件主类-api)
- [控制器 API](#控制器-api)
- [模型 API](#模型-api)
- [服务类 API](#服务类-api)
- [易支付平台 API](#易支付平台-api)

---

## 插件主类 API

类名：`addon\yipay\Yipay`

### pay() - 发起支付

发起支付，生成支付链接。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 商户订单号（唯一） |
| money | float | 是 | 支付金额（元） |
| pay_type | string | 否 | 支付方式：alipay/wxpay/qqpay，默认alipay |
| goods_name | string | 否 | 商品名称，默认"商品购买" |

**返回值：**

```php
[
    'code' => 1,              // 状态码：1-成功，0-失败
    'msg' => '获取支付链接成功',  // 提示信息
    'data' => [
        'pay_url' => 'http://...',  // 支付链接
        'trade_no' => 'ORDER_001'   // 商户订单号
    ]
]
```

**示例：**

```php
$yipay = new \addon\yipay\Yipay();

$result = $yipay->pay([
    'trade_no' => 'ORDER_20240101001',
    'money' => 99.99,
    'pay_type' => 'alipay',
    'goods_name' => 'VIP会员'
]);

if ($result['code'] == 1) {
    header('Location: ' . $result['data']['pay_url']);
}
```

---

### notify() - 处理异步回调

处理易支付异步回调通知。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| data | array | 是 | 回调数据数组 |

**返回值：**

| 类型 | 说明 |
|------|------|
| bool | true-处理成功，false-处理失败 |

**示例：**

```php
$yipay = new \addon\yipay\Yipay();

$notifyData = $_POST;
$result = $yipay->notify($notifyData);

if ($result) {
    echo 'success';
} else {
    echo 'fail';
}
```

---

### returnNotify() - 处理同步回调

处理易支付同步回调（页面跳转）。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| data | array | 是 | 回调数据数组 |

**返回值：**

| 类型 | 说明 |
|------|------|
| bool | true-验证成功，false-验证失败 |

---

### query() - 查询支付状态

查询订单支付状态。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 商户订单号 |

**返回值：**

```php
[
    'code' => 1,
    'msg' => '查询成功',
    'data' => [
        'status' => 1,           // 0-未支付，1-已支付
        'trade_no' => 'ORDER_001',
        'pay_time' => 1704067200  // 支付时间戳（可选）
    ]
]
```

**示例：**

```php
$yipay = new \addon\yipay\Yipay();

$result = $yipay->query('ORDER_20240101001');

if ($result['code'] == 1) {
    if ($result['data']['status'] == 1) {
        echo '订单已支付';
    } else {
        echo '订单未支付';
    }
}
```

---

### refund() - 发起退款

对已支付订单发起退款。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 商户订单号 |
| money | float | 是 | 退款金额（元） |

**返回值：**

```php
[
    'code' => 1,
    'msg' => '退款成功',
    'data' => [
        'trade_no' => 'ORDER_001',
        'refund_money' => 99.99
    ]
]
```

**示例：**

```php
$yipay = new \addon\yipay\Yipay();

$result = $yipay->refund('ORDER_20240101001', 99.99);

if ($result['code'] == 1) {
    echo '退款成功';
}
```

---

## 控制器 API

### Index 控制器

类名：`addon\yipay\controller\Index`

#### pay() - 支付发起接口

**请求方式：** GET / POST

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 商户订单号 |
| money | float | 是 | 支付金额 |
| pay_type | string | 否 | 支付方式，默认alipay |
| goods_name | string | 否 | 商品名称 |

**请求示例：**

```
GET /addon/yipay/index/pay?trade_no=ORDER_001&money=99.99&pay_type=alipay&goods_name=VIP会员
```

或使用 AJAX：

```javascript
$.ajax({
    url: '/addon/yipay/index/pay',
    type: 'POST',
    data: {
        trade_no: 'ORDER_001',
        money: 99.99,
        pay_type: 'alipay',
        goods_name: 'VIP会员'
    },
    success: function(res) {
        if (res.code == 1) {
            window.location.href = res.data.pay_url;
        }
    }
});
```

---

### Notify 控制器

类名：`addon\yipay\controller\Notify`

#### notify() - 异步回调接口

**请求方式：** POST

**说明：** 此接口由易支付平台调用，无需手动调用。

**请求参数：** 由易支付平台发送

**响应内容：**
- `success` - 处理成功
- `fail` - 处理失败

#### returnNotify() - 同步回调接口

**请求方式：** GET

**说明：** 此接口在用户支付完成后跳转，由易支付平台调用。

---

### Config 控制器（后台）

类名：`addon\yipay\admin\Config`

#### index() - 配置页面

**请求方式：** GET / POST

**说明：** 显示配置页面或保存配置。

#### save() - 保存配置

**请求方式：** POST

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| api_url | string | 是 | 易支付API地址 |
| pid | string | 是 | 商户号 |
| key | string | 是 | 商户密钥 |
| status | int | 否 | 启用状态：0-关闭，1-开启 |
| support_alipay | int | 否 | 支持支付宝：1-是 |
| support_wxpay | int | 否 | 支持微信：1-是 |
| support_qqpay | int | 否 | 支持QQ钱包：1-是 |

#### test() - 测试连接

**请求方式：** POST

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| api_url | string | 是 | 易支付API地址 |
| pid | string | 是 | 商户号 |
| key | string | 是 | 商户密钥 |

**返回示例：**

```json
{
    "code": 1,
    "msg": "连接成功"
}
```

#### logList() - 支付日志列表

**请求方式：** GET

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |
| trade_no | string | 否 | 订单号（模糊查询） |
| pay_type | string | 否 | 支付方式 |
| status | int | 否 | 支付状态 |

**返回示例：**

```json
{
    "code": 0,
    "msg": "",
    "count": 100,
    "data": [
        {
            "id": 1,
            "trade_no": "ORDER_001",
            "pay_type": "alipay",
            "money": "99.99",
            "status": 1,
            "create_time": 1704067200,
            "pay_time": 1704067300
        }
    ]
}
```

---

## 模型 API

### YipayLog 模型

类名：`addon\yipay\model\YipayLog`

#### createLog() - 创建日志

创建支付日志记录。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 商户订单号 |
| out_trade_no | string | 否 | 易支付订单号 |
| pay_type | string | 是 | 支付方式 |
| money | float | 是 | 支付金额 |
| status | int | 否 | 状态，默认0 |
| notify_data | string | 否 | 回调数据JSON |

**返回值：** 日志ID 或 false

**示例：**

```php
$logModel = new \addon\yipay\model\YipayLog();

$logId = $logModel->createLog([
    'trade_no' => 'ORDER_001',
    'pay_type' => 'alipay',
    'money' => 99.99,
    'status' => 0
]);
```

---

#### updateStatus() - 更新状态

更新支付状态。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| tradeNo | string | 是 | 商户订单号 |
| status | int | 是 | 状态：0-待支付，1-已支付，2-已退款 |
| notifyData | array | 否 | 回调数据 |

**返回值：** 更新结果（bool）

---

#### getLogByTradeNo() - 根据订单号查询

根据商户订单号查询日志。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| tradeNo | string | 是 | 商户订单号 |

**返回值：** 日志数组 或 null

---

#### getLogList() - 获取日志列表

获取支付日志列表（分页）。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| where | array | 否 | 查询条件 |
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |

**返回值：**

```php
[
    'total' => 100,
    'list' => [...]
]
```

---

#### getStatistics() - 统计数据

获取支付统计数据。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| where | array | 否 | 查询条件（start_time, end_time） |

**返回值：**

```php
[
    'total_count' => 100,      // 订单总数
    'success_count' => 80,     // 成功订单数
    'total_money' => 9999.00   // 交易总额
]
```

---

## 服务类 API

### YipayService 服务类

类名：`addon\yipay\library\YipayService`

#### __construct() - 构造函数

**参数：**

```php
$config = [
    'api_url' => 'http://pay.example.com',
    'pid' => '10001',
    'key' => 'secret_key'
];

$service = new YipayService($config);
```

---

#### submit() - 生成支付请求

生成支付链接。

**参数：**

```php
$params = [
    'type' => 'alipay',
    'out_trade_no' => 'ORDER_001',
    'notify_url' => 'http://...',
    'return_url' => 'http://...',
    'name' => '商品名称',
    'money' => 99.99
];
```

**返回值：** 支付链接（string）

---

#### verify() - 验证签名

验证回调数据签名。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| data | array | 是 | 回调数据（包含sign字段） |

**返回值：** true-验证通过，false-验证失败

---

#### sign() - 生成签名

生成签名字符串。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| params | array | 是 | 参数数组 |

**返回值：** 签名字符串（string）

**签名算法：**
1. 过滤空值
2. 按key升序排列
3. 拼接成 key=value&key=value 格式
4. 末尾加上商户密钥
5. MD5加密后转大写

---

#### queryOrder() - 查询订单

查询订单状态。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| tradeNo | string | 是 | 商户订单号 |

**返回值：**

```php
[
    'code' => 1,
    'msg' => '查询成功',
    'data' => [...]
]
```

---

#### refundOrder() - 退款订单

发起退款。

**参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| tradeNo | string | 是 | 商户订单号 |
| money | float | 是 | 退款金额 |

**返回值：**

```php
[
    'code' => 1,
    'msg' => '退款成功',
    'data' => [...]
]
```

---

## 易支付平台 API

### 支付请求

**接口地址：** `{api_url}/submit.php`

**请求方式：** GET

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | string | 是 | 商户号 |
| type | string | 是 | 支付方式：alipay/wxpay/qqpay |
| out_trade_no | string | 是 | 商户订单号 |
| notify_url | string | 是 | 异步回调地址 |
| return_url | string | 是 | 同步回调地址 |
| name | string | 是 | 商品名称 |
| money | string | 是 | 支付金额 |
| sign | string | 是 | 签名 |
| sign_type | string | 是 | 签名类型：MD5 |

---

### 异步回调

**回调方式：** POST

**回调参数：**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| pid | string | 商户号 |
| trade_no | string | 易支付订单号 |
| out_trade_no | string | 商户订单号 |
| type | string | 支付方式 |
| name | string | 商品名称 |
| money | string | 支付金额 |
| trade_status | string | 交易状态：TRADE_SUCCESS |
| sign | string | 签名 |

**响应内容：**
- `success` - 处理成功
- `fail` - 处理失败

---

### 订单查询

**接口地址：** `{api_url}/api.php`

**请求方式：** GET

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | string | 是 | 商户号 |
| out_trade_no | string | 是 | 商户订单号 |
| sign | string | 是 | 签名 |
| sign_type | string | 是 | 签名类型：MD5 |

---

### 退款接口

**接口地址：** `{api_url}/refund.php`

**请求方式：** POST

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | string | 是 | 商户号 |
| out_trade_no | string | 是 | 商户订单号 |
| money | string | 是 | 退款金额 |
| sign | string | 是 | 签名 |
| sign_type | string | 是 | 签名类型：MD5 |

---

## 错误码

| 错误码 | 说明 |
|--------|------|
| 0 | 失败 |
| 1 | 成功 |
| -1 | 参数错误 |
| -2 | 签名验证失败 |
| -3 | 订单不存在 |
| -4 | 订单已支付 |
| -5 | 退款失败 |

---

## 状态码

### 支付状态

| 状态码 | 说明 |
|--------|------|
| 0 | 待支付 |
| 1 | 已支付 |
| 2 | 已退款 |

### 支付方式

| 代码 | 说明 |
|------|------|
| alipay | 支付宝 |
| wxpay | 微信支付 |
| qqpay | QQ钱包 |

---

## 附录

### 签名算法示例

```php
$params = [
    'pid' => '10001',
    'type' => 'alipay',
    'out_trade_no' => 'ORDER_001',
    'money' => '100.00',
    'name' => '商品名称'
];

$key = 'your_secret_key';

// 1. 排序
ksort($params);

// 2. 拼接
$signStr = '';
foreach ($params as $k => $v) {
    $signStr .= $k . '=' . $v . '&';
}
$signStr = rtrim($signStr, '&');

// 3. 加密钥
$signStr .= $key;

// 4. MD5 + 转大写
$sign = strtoupper(md5($signStr));
```

---

更多信息请参考 [README.md](README.md) 和 [example.php](example.php)。
