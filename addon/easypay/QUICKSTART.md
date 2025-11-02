# Easypay支付插件快速开始指南

## 🚀 快速上手

只需5分钟，即可在您的Niushop V5商城中集成Easypay支付功能！

### 第一步：获取Easypay商户账号

1. 注册Easypay商户账号：https://www.dulupay.com/
2. 登录商户后台
3. 获取以下信息：
   - 商户号(PID)
   - 商户密钥

### 第二步：安装插件

```bash
# 1. 上传插件到Niushop
cp -r easypay /path/to/niushop/addon/

# 2. 创建数据库表
mysql -u用户名 -p密码 数据库名 < addon/easypay/install.sql

# 3. 设置权限
chmod -R 755 /path/to/niushop/addon/easypay/
```

### 第三步：配置插件

1. 访问后台配置页面：`/addon/easypay/admin/config/index`
2. 填写商户信息：
   - 商户号：`您的PID`
   - 商户密钥：`您的密钥`
   - 网关地址：`https://api.dulupay.com/`
3. 选择支持的支付方式
4. 点击"测试连接"确认配置正确

### 第四步：集成支付

在您的订单处理代码中添加：

```php
use addon\easypay\Easypay;

// 发起支付
$easypay = new Easypay();
$result = $easypay->pay([
    'trade_no' => $order_id,
    'money' => $amount,
    'pay_type' => 'alipay', // alipay|wxpay|qqpay
    'subject' => $product_name,
]);

if ($result['code'] == 1) {
    // 跳转到支付页面
    header('Location: ' . $result['data']['pay_url']);
}
```

### 第五步：测试支付

1. 创建一个测试订单
2. 选择Easypay支付方式
3. 完成支付流程
4. 检查订单状态是否正确更新

## 📋 配置清单

### 必需配置 ✅
- [ ] 商户号(PID)
- [ ] 商户密钥
- [ ] 网关地址
- [ ] 支付方式选择

### 可选配置 ⚙️
- [ ] 自定义回调地址
- [ ] 订单标题前缀
- [ ] 支付超时时间
- [ ] 调试模式

## 🔧 支付方式配置

| 支付方式 | 代码 | 说明 |
|----------|------|------|
| 支付宝 | `alipay` | 支持PC和手机端 |
| 微信支付 | `wxpay` | 支持扫码和H5 |
| QQ钱包 | `qqpay` | 支持PC和手机端 |

## 🌐 回调地址配置

### 自动生成（推荐）
- 异步回调：`https://您的域名/addon/easypay/notify/index`
- 同步回调：`https://您的域名/addon/easypay/return/index`

### 自定义回调
在插件配置中填写自定义地址：
- 异步回调用于接收支付状态通知
- 同步回调用于用户支付后跳转

## 🧪 快速测试

运行测试脚本验证插件功能：

```bash
php addon/easypay/test.php
```

测试内容包括：
- ✅ 签名生成和验证
- ✅ 参数验证
- ✅ HTTP请求
- ✅ 回调处理
- ✅ 性能测试

## 💡 使用示例

### 基础支付
```php
$easypay = new Easypay();
$result = $easypay->pay([
    'trade_no' => 'ORDER_' . time(),
    'money' => 100.00,
    'pay_type' => 'alipay',
    'subject' => '商品购买',
]);
```

### 查询订单
```php
$result = $easypay->query('ORDER_20240101001');
if ($result['code'] == 1) {
    echo "订单状态：" . $result['data']['trade_status'];
}
```

### 验证回调
```php
$callbackData = $_POST; // 获取回调数据
$easypay = new Easypay();
if ($easypay->verify($callbackData)) {
    // 处理支付成功逻辑
}
```

## 🚨 常见问题

### Q: 支付发起失败怎么办？
A: 检查以下几点：
1. 商户号和密钥是否正确
2. 网关地址是否可访问
3. 订单参数是否完整

### Q: 回调没有收到？
A: 确认以下设置：
1. Easypay后台是否正确设置了回调地址
2. 服务器防火墙是否允许回调请求
3. 插件日志中是否有回调记录

### Q: 签名验证失败？
A: 检查：
1. 商户密钥是否正确
2. 参数排序和拼接是否正确
3. 是否有特殊字符处理问题

## 📞 技术支持

- 📧 邮箱：support@niushop.com
- 📖 文档：https://www.kancloud.cn/niucloud/niushop_b2c_v4_develop/1839354
- 🌐 Easypay文档：https://www.dulupay.com/doc/index.html

## 🎯 下一步

完成快速开始后，建议您：

1. 阅读[完整文档](README.md)了解详细功能
2. 查看[安装指南](INSTALL.md)进行深度配置
3. 运行[示例代码](example.php)学习最佳实践
4. 配置[单元测试](tests/)确保代码质量

---

**🎉 恭喜！您已成功集成Easypay支付功能！**