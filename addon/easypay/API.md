# Easypay支付插件 API 文档

## 概述

Easypay支付插件为Niushop V5提供了完整的支付解决方案，支持多种支付方式的集成。

## 基础信息

- **插件名称**: Easypay易支付
- **版本**: 1.0.0
- **兼容系统**: Niushop V5+
- **PHP版本**: 7.0+
- **支付网关**: Dulupay/Easypay

## 核心类

### Easypay 主类

```php
namespace addon\easypay;

class Easypay
{
    // 发起支付
    public function pay($params)
    
    // 查询支付状态
    public function query($tradeNo)
    
    // 退款处理
    public function refund($params)
    
    // 关闭订单
    public function close($tradeNo)
    
    // 验证签名
    public function verify($params)
}
```

### EasypayService 服务类

```php
namespace addon\easypay\library;

class EasypayService
{
    // 创建支付订单
    public function createOrder($params)
    
    // 查询订单
    public function queryOrder($outTradeNo)
    
    // 生成签名
    public function generateSign($params)
    
    // 验证签名
    public function verifySign($params)
}
```

## API 接口

### 1. 发起支付

创建支付订单并获取支付链接。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| trade_no | string | 是 | 商户订单号 | ORDER_20240101001 |
| money | float | 是 | 支付金额 | 100.00 |
| pay_type | string | 是 | 支付方式 | alipay |
| subject | string | 否 | 商品名称 | 商品购买订单 |

#### 支付方式

| 代码 | 名称 | 说明 |
|------|------|------|
| alipay | 支付宝 | 支持PC和手机端 |
| wxpay | 微信支付 | 支持扫码和H5 |
| qqpay | QQ钱包 | 支持PC和手机端 |

#### 请求示例

```php
use addon\easypay\Easypay;

$easypay = new Easypay();

$result = $easypay->pay([
    'trade_no' => 'ORDER_20240101001',
    'money' => 100.00,
    'pay_type' => 'alipay',
    'subject' => '商品购买订单',
]);
```

#### 响应格式

```json
{
    "code": 1,
    "msg": "支付发起成功",
    "data": {
        "pay_url": "https://api.dulupay.com/pay/...",
        "qrcode": "base64_encoded_qr_code"
    }
}
```

#### 响应字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| code | int | 状态码：1成功，0失败 |
| msg | string | 响应消息 |
| data | object | 响应数据 |
| data.pay_url | string | 支付链接 |
| data.qrcode | string | 二维码（可选） |

### 2. 查询支付状态

查询订单的支付状态。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| trade_no | string | 是 | 商户订单号 | ORDER_20240101001 |

#### 请求示例

```php
$result = $easypay->query('ORDER_20240101001');
```

#### 响应格式

```json
{
    "code": 1,
    "msg": "查询成功",
    "data": {
        "trade_no": "202401012200123456789",
        "out_trade_no": "ORDER_20240101001",
        "trade_status": "TRADE_SUCCESS",
        "money": "100.00",
        "time_end": "20240101120000"
    }
}
```

#### 响应字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| trade_no | string | 第三方订单号 |
| out_trade_no | string | 商户订单号 |
| trade_status | string | 交易状态 |
| money | string | 支付金额 |
| time_end | string | 支付时间 |

#### 交易状态

| 状态值 | 说明 |
|--------|------|
| TRADE_SUCCESS | 交易成功 |
| TRADE_PENDING | 交易处理中 |
| TRADE_FAILED | 交易失败 |

### 3. 退款处理

处理订单退款（当前为占位实现）。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| out_trade_no | string | 是 | 原订单号 | ORDER_20240101001 |
| refund_amount | float | 是 | 退款金额 | 50.00 |
| refund_reason | string | 否 | 退款原因 | 用户申请退款 |

#### 请求示例

```php
$result = $easypay->refund([
    'out_trade_no' => 'ORDER_20240101001',
    'refund_amount' => 50.00,
    'refund_reason' => '用户申请退款',
]);
```

#### 响应格式

```json
{
    "code": 0,
    "msg": "Easypay暂不支持退款接口，请手动处理",
    "data": {}
}
```

### 4. 关闭订单

关闭未支付的订单（当前为占位实现）。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| trade_no | string | 是 | 商户订单号 | ORDER_20240101001 |

#### 请求示例

```php
$result = $easypay->close('ORDER_20240101001');
```

#### 响应格式

```json
{
    "code": 0,
    "msg": "Easypay暂不支持关闭订单接口",
    "data": {}
}
```

### 5. 签名验证

验证回调数据的签名。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| * | mixed | 是 | 完整的回调数据 | 见下方示例 |

#### 请求示例

```php
$callbackData = [
    'pid' => '1001',
    'trade_no' => '202401012200123456789',
    'out_trade_no' => 'ORDER_20240101001',
    'type' => 'alipay',
    'money' => '100.00',
    'trade_status' => 'TRADE_SUCCESS',
    'time_end' => '20240101120000',
    'sign' => 'ABC123DEF456...',
];

$isValid = $easypay->verify($callbackData);
```

#### 响应格式

```php
bool $isValid  // true:验证通过，false:验证失败
```

## 回调处理

### 异步回调

Easypay服务器会向配置的异步回调地址发送支付结果通知。

#### 回调地址

```
POST /addon/easypay/notify/index
```

#### 回调参数

| 参数名 | 类型 | 说明 | 示例值 |
|--------|------|------|--------|
| pid | string | 商户号 | 1001 |
| type | string | 支付方式 | alipay |
| out_trade_no | string | 商户订单号 | ORDER_20240101001 |
| trade_no | string | 第三方订单号 | 202401012200123456789 |
| money | string | 支付金额 | 100.00 |
| trade_status | string | 交易状态 | TRADE_SUCCESS |
| time_end | string | 支付时间 | 20240101120000 |
| sign | string | 签名 | ABC123DEF456 |

#### 处理流程

1. 接收POST数据
2. 验证签名
3. 检查订单状态
4. 验证金额匹配
5. 更新订单状态（幂等性）
6. 记录支付日志
7. 响应"success"

#### 响应格式

```
success  // 处理成功
fail     // 处理失败
```

### 同步回调

用户支付完成后会跳转到同步回调页面。

#### 回调地址

```
GET /addon/easypay/return/index?trade_no=ORDER_20240101001
```

#### 回调参数

与异步回调参数相同，通过GET方式传递。

#### 处理流程

1. 获取GET参数
2. 验证签名
3. 显示支付结果页面
4. 提供订单详情链接

## 签名算法

### 签名生成步骤

1. **过滤参数**: 移除空值和sign、sign_type字段
2. **参数排序**: 按键名ASCII码升序排列
3. **拼接字符串**: 按`key=value&key=value`格式拼接
4. **添加密钥**: 在字符串末尾加上商户密钥
5. **MD5加密**: 对字符串进行MD5加密
6. **转大写**: 将结果转为大写

### 算法示例

```php
// 1. 原始参数
$params = [
    'money' => '100.00',
    'name' => '测试商品',
    'out_trade_no' => 'ORDER_20240101001',
    'pid' => '1001',
];

// 2. 过滤和排序
ksort($params);
// 结果: ['money' => '100.00', 'name' => '测试商品', 'out_trade_no' => 'ORDER_20240101001', 'pid' => '1001']

// 3. 拼接字符串
$string = 'money=100.00&name=测试商品&out_trade_no=ORDER_20240101001&pid=1001';

// 4. 添加密钥
$string .= 'your_secret_key';

// 5. MD5加密
$sign = md5($string);

// 6. 转大写
$sign = strtoupper($sign);
```

## 错误码

### 通用错误码

| 错误码 | 说明 | 处理建议 |
|--------|------|----------|
| 0 | 请求失败 | 检查参数和网络 |
| 1 | 请求成功 | 正常处理 |

### 业务错误码

| 错误信息 | 说明 | 处理建议 |
|----------|------|----------|
| 订单号不能为空 | 缺少必要参数 | 检查trade_no参数 |
| 支付金额必须大于0 | 金额参数错误 | 检查money参数 |
| 不支持的支付方式 | pay_type无效 | 检查支付方式代码 |
| 签名验证失败 | 签名不匹配 | 检查密钥和算法 |
| 网络请求失败 | 网络连接问题 | 检查网络和网关地址 |

## 配置管理

### 配置参数

| 参数名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| gateway_url | string | https://api.dulupay.com/ | 网关地址 |
| merchant_id | string | - | 商户号 |
| secret_key | string | - | 商户密钥 |
| status | int | 0 | 启用状态 |
| support_type | array | ['alipay','wxpay','qqpay'] | 支持的支付方式 |
| notify_url | string | - | 异步回调地址 |
| return_url | string | - | 同步回调地址 |
| order_prefix | string | ORDER | 订单标题前缀 |
| timeout | int | 30 | 超时时间(分钟) |
| debug | int | 0 | 调试模式 |

### 获取配置

```php
$config = [
    'gateway_url' => 'https://api.dulupay.com/',
    'merchant_id' => '1001',
    'secret_key' => 'your_secret_key',
    // ... 其他配置
];
```

## 日志系统

### 日志类型

- **支付日志**: 记录支付请求和响应
- **回调日志**: 记录异步和同步回调
- **错误日志**: 记录异常和错误信息
- **调试日志**: 记录详细的调试信息

### 日志查看

```php
// 查看支付日志
$log = EasypayLog::getByOutTradeNo('ORDER_20240101001');

// 获取统计信息
$stats = EasypayLog::getStatistics();
```

## 最佳实践

### 1. 错误处理

```php
$result = $easypay->pay($params);

if ($result['code'] != 1) {
    // 记录错误日志
    error_log('[Easypay] 支付失败: ' . $result['msg']);
    
    // 返回用户友好的错误信息
    return json_error('支付服务暂时不可用，请稍后重试');
}
```

### 2. 幂等性处理

```php
// 在回调处理中检查订单状态
if ($order['pay_status'] == 1) {
    // 已支付，直接返回成功
    echo 'success';
    return;
}
```

### 3. 金额验证

```php
// 验证回调金额是否匹配
if (abs($order['pay_amount'] - $callbackData['money']) > 0.01) {
    // 金额不匹配，记录异常
    echo 'fail';
    return;
}
```

### 4. 异常捕获

```php
try {
    $result = $easypay->pay($params);
} catch (Exception $e) {
    // 记录异常
    error_log('[Easypay] 支付异常: ' . $e->getMessage());
    
    // 返回错误信息
    return json_error('支付处理异常');
}
```

## 技术支持

- **文档**: https://www.kancloud.cn/niucloud/niushop_b2c_v4_develop/1839354
- **Easypay文档**: https://www.dulupay.com/doc/index.html
- **技术支持**: support@niushop.com
- **问题反馈**: GitHub Issues