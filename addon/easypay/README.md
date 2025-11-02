# Easypay支付插件 for Niushop V5

## 项目简介

这是为 Niushop V5 电商系统开发的 Easypay(Dulupay) 支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式。

## 功能特点

- ✅ 支持支付宝、微信支付、QQ钱包
- ✅ 完整的支付流程（发起支付、异步回调、同步回调）
- ✅ 支付状态查询
- ✅ 退款功能（占位实现，需根据API支持情况完善）
- ✅ 支付日志记录和查询
- ✅ 后台配置管理界面
- ✅ MD5签名验证安全机制
- ✅ 幂等性处理防止重复回调
- ✅ 支持PC端和移动端支付
- ✅ 调试模式支持
- ✅ 错误处理和日志记录

## 系统要求

- PHP 7.0+
- MySQL 5.6+
- curl 扩展
- Niushop V5 系统

## 快速开始

### 1. 安装插件

```bash
# 创建数据库表
mysql -u用户名 -p密码 数据库名 < addon/easypay/install.sql

# 复制插件文件到 Niushop V5 的 addon 目录
cp -r addon/easypay /path/to/niushop/addon/
```

### 2. 配置插件

1. 访问后台配置页面：`/addon/easypay/admin/config/index`
2. 配置以下参数：
   - **网关地址**：Easypay支付网关地址，默认：`https://api.dulupay.com/`
   - **商户号(PID)**：登录Easypay商户后台获取
   - **商户密钥**：登录Easypay商户后台获取
   - **支持的支付方式**：选择要启用的支付方式
   - **订单标题前缀**：用于区分支付渠道
   - **超时时间**：支付订单超时时间（分钟）
   - **回调地址**：可自定义异步和同步回调地址

3. 点击"测试连接"验证配置是否正确

### 3. 发起支付

```php
use addon\easypay\Easypay;

$easypay = new Easypay();

$result = $easypay->pay([
    'trade_no' => 'ORDER_20240101001',     // 商户订单号
    'money' => 100.00,                     // 支付金额
    'pay_type' => 'alipay',                // 支付方式：alipay|wxpay|qqpay
    'subject' => '商品购买订单',             // 商品名称
]);

if ($result['code'] == 1) {
    // 跳转到支付链接
    header('Location: ' . $result['data']['pay_url']);
} else {
    echo "支付发起失败：" . $result['msg'];
}
```

## API 接口说明

### 发起支付

```php
$result = $easypay->pay([
    'trade_no' => '商户订单号',              // 必填
    'money' => 100.00,                      // 必填，支付金额
    'pay_type' => 'alipay',                 // 必填，支付方式
    'subject' => '商品名称',                 // 可选，商品名称
]);
```

**返回格式：**
```php
[
    'code' => 1,                            // 1:成功 0:失败
    'msg' => '支付发起成功',                 // 消息
    'data' => [
        'pay_url' => 'https://...',          // 支付链接
        'qrcode' => 'base64_qrcode',        // 二维码（可选）
    ]
]
```

### 查询支付状态

```php
$result = $easypay->query('ORDER_20240101001');
```

**返回格式：**
```php
[
    'code' => 1,
    'msg' => '查询成功',
    'data' => [
        'trade_no' => '第三方订单号',
        'out_trade_no' => '商户订单号',
        'trade_status' => 'TRADE_SUCCESS',  // 交易状态
        'money' => '100.00',                // 支付金额
        'time_end' => '20240101120000',     // 支付时间
    ]
]
```

### 退款接口

```php
$result = $easypay->refund([
    'out_trade_no' => '商户订单号',
    'refund_amount' => 50.00,
    'refund_reason' => '退款原因',
]);
```

### 签名验证

```php
$callbackData = [
    'pid' => '1001',
    'trade_no' => '第三方订单号',
    'out_trade_no' => '商户订单号',
    // ... 其他回调参数
    'sign' => '签名值',
];

$isValid = $easypay->verify($callbackData);
```

## 回调处理

### 异步回调

插件会自动处理来自Easypay的异步回调，验证签名后更新订单状态。

**回调地址：** `/addon/easypay/notify/index`

**处理流程：**
1. 接收回调数据
2. 验证签名
3. 检查订单状态
4. 验证金额
5. 更新订单状态（幂等性处理）
6. 记录支付日志
7. 响应 `success` 给Easypay

### 同步回调

用户支付完成后会跳转到同步回调页面，显示支付结果。

**回调地址：** `/addon/easypay/return/index`

## 签名算法

Easypay使用MD5签名算法：

1. 将所有非空参数按key升序排列
2. 拼接成 `key=value&key=value` 格式
3. 在末尾加上商户密钥
4. 进行MD5加密并转大写

**示例：**
```php
$params = [
    'money' => '100.00',
    'name' => '测试商品',
    'out_trade_no' => 'ORDER_20240101001',
    'pid' => '1001',
];

// 排序后拼接
$string = 'money=100.00&name=测试商品&out_trade_no=ORDER_20240101001&pid=1001';

// 加上密钥
$string .= 'your_secret_key';

// MD5加密并转大写
$sign = strtoupper(md5($string));
```

## 配置参数说明

| 参数名 | 说明 | 默认值 |
|--------|------|--------|
| gateway_url | Easypay网关地址 | https://api.dulupay.com/ |
| merchant_id | 商户号(PID) | - |
| secret_key | 商户密钥 | - |
| status | 启用状态 | 0 |
| support_type | 支持的支付方式 | ["alipay","wxpay","qqpay"] |
| notify_url | 异步回调地址 | - |
| return_url | 同步回调地址 | - |
| order_prefix | 订单标题前缀 | ORDER |
| timeout | 超时时间(分钟) | 30 |
| debug | 调试模式 | 0 |

## 支付方式说明

| 支付方式 | 代码 | 说明 |
|----------|------|------|
| 支付宝 | alipay | 支持PC和手机端 |
| 微信支付 | wxpay | 支持扫码和H5 |
| QQ钱包 | qqpay | 支持PC和手机端 |

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 0 | 请求失败 |
| 1 | 请求成功 |

## 日志记录

插件会记录以下日志：

- 支付发起请求和响应
- 异步回调数据
- 同步回调数据
- 错误信息和异常
- 调试信息（需开启调试模式）

**日志位置：** 系统日志文件

## 安全建议

1. **签名验证**：务必验证所有回调的签名
2. **金额验证**：回调时验证订单金额是否匹配
3. **幂等性处理**：防止重复回调导致重复支付
4. **密钥安全**：妥善保管商户密钥，不要泄露
5. **HTTPS**：生产环境建议使用HTTPS
6. **IP白名单**：限制回调来源IP（如果支持）
7. **日志监控**：定期查看支付日志，监控异常

## 故障排除

### 支付发起失败

1. 检查商户号和密钥是否正确
2. 检查网关地址是否可访问
3. 检查订单参数是否完整
4. 查看错误日志

### 回调验证失败

1. 检查回调数据是否完整
2. 检查签名算法是否正确
3. 检查商户密钥是否匹配
4. 查看回调日志

### 订单状态未更新

1. 检查回调是否正常接收
2. 检查签名验证是否通过
3. 检查订单状态更新逻辑
4. 查看数据库日志

## 开发和测试

### 运行测试

```bash
# 运行测试脚本
php addon/easypay/test.php
```

### 查看示例

```bash
# 查看使用示例
php addon/easypay/example.php
```

## 版本历史

- **v1.0.0** (2024-01-01)
  - 初始版本发布
  - 支持支付宝、微信、QQ钱包支付
  - 完整的支付流程实现
  - 后台配置管理界面
  - 支付日志记录

## 技术支持

如有问题或建议，请通过以下方式联系：

- 邮箱：support@niushop.com
- 文档：https://www.kancloud.cn/niucloud/niushop_b2c_v4_develop/1839354
- Easypay文档：https://www.dulupay.com/doc/index.html

## 许可证

MIT License