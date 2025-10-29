# Niushop V5 易支付插件

## 简介

这是一个为 Niushop V5 开发的通用易支付插件，支持支付宝、微信、QQ钱包等多种支付方式。

## 功能特点

- ✅ 支持支付宝、微信支付、QQ钱包
- ✅ 完整的支付流程（发起支付、异步回调、同步回调）
- ✅ 支付状态查询
- ✅ 退款功能
- ✅ 支付日志记录
- ✅ 后台配置管理
- ✅ 签名验证安全
- ✅ 幂等性处理

## 目录结构

```
addon/yipay/
├── config.php                    # 插件配置文件
├── info.php                      # 插件信息定义
├── install.sql                   # 安装SQL（创建支付日志表）
├── uninstall.sql                 # 卸载SQL
├── Yipay.php                     # 插件主类（实现支付接口）
├── README.md                     # 说明文档
├── controller/
│   ├── Index.php                 # 前台支付发起控制器
│   └── Notify.php                # 支付回调处理控制器
├── admin/
│   └── Config.php                # 后台配置管理控制器
├── model/
│   └── YipayLog.php              # 支付日志数据模型
├── library/
│   └── YipayService.php          # 易支付核心服务类
└── view/
    └── admin/
        └── config.html            # 后台配置页面视图
```

## 安装步骤

1. 将 `yipay` 目录复制到 `addon/` 目录下

2. 执行 `install.sql` 创建数据库表
   ```bash
   mysql -u用户名 -p密码 数据库名 < addon/yipay/install.sql
   ```

3. 在后台插件管理中启用易支付插件

## 配置说明

### 后台配置

访问后台配置页面：`/addon/yipay/admin/config/index`

配置项说明：
- **API地址**: 易支付平台的API地址，例如：`http://pay.example.com`
- **商户号PID**: 易支付平台分配的商户号
- **商户密钥KEY**: 易支付平台分配的商户密钥
- **启用状态**: 开启/关闭支付功能
- **支持的支付方式**: 可选择支付宝、微信支付、QQ钱包

### 测试连接

配置完成后，点击"测试连接"按钮验证配置是否正确。

## 使用方法

### 1. 发起支付

#### 方法一：通过控制器

```php
// 跳转到支付页面
$url = '/addon/yipay/index/pay?' . http_build_query([
    'trade_no' => 'ORDER_20240101001',
    'money' => 100.00,
    'pay_type' => 'alipay',  // alipay/wxpay/qqpay
    'goods_name' => '商品名称'
]);

// 跳转
header('Location: ' . $url);
```

#### 方法二：通过插件主类

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->pay([
    'trade_no' => 'ORDER_20240101001',
    'money' => 100.00,
    'pay_type' => 'alipay',
    'goods_name' => '商品名称'
]);

if ($result['code'] == 1) {
    // 跳转到支付链接
    header('Location: ' . $result['data']['pay_url']);
} else {
    echo $result['msg'];
}
```

### 2. 回调处理

#### 异步回调（Notify）

易支付会自动向以下地址发送支付结果通知：
```
http://yourdomain.com/addon/yipay/notify/notify
```

插件会自动处理回调，验证签名并更新支付状态。

#### 同步回调（Return）

支付完成后，用户会被跳转到：
```
http://yourdomain.com/addon/yipay/notify/return
```

可以在此页面展示支付结果。

### 3. 查询订单状态

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->query('ORDER_20240101001');

if ($result['code'] == 1) {
    if ($result['data']['status'] == 1) {
        echo '订单已支付';
    } else {
        echo '订单未支付';
    }
}
```

### 4. 退款

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->refund('ORDER_20240101001', 100.00);

if ($result['code'] == 1) {
    echo '退款成功';
} else {
    echo $result['msg'];
}
```

## 易支付签名算法

签名生成步骤：
1. 将参数按key升序排列
2. 拼接成 `key=value` 格式，用 `&` 连接
3. 末尾加上商户密钥 `key`
4. MD5加密后转大写

示例代码：
```php
// 参数
$params = [
    'pid' => '10001',
    'type' => 'alipay',
    'out_trade_no' => 'ORDER_001',
    'money' => '100.00',
    'name' => '商品名称'
];

// 排序
ksort($params);

// 拼接
$signStr = '';
foreach ($params as $key => $value) {
    $signStr .= $key . '=' . $value . '&';
}

// 加密钥
$signStr = rtrim($signStr, '&') . $key; // $key 为商户密钥

// 生成签名
$sign = strtoupper(md5($signStr));
```

## 支付日志表结构

```sql
CREATE TABLE `ns_yipay_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `trade_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `out_trade_no` varchar(64) NOT NULL COMMENT '易支付订单号',
  `pay_type` varchar(20) NOT NULL COMMENT '支付方式',
  `money` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0待支付/1已支付/2已退款',
  `notify_data` text COMMENT '回调数据JSON',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `pay_time` int(11) NOT NULL COMMENT '支付时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 支付状态说明

- `0` - 待支付：订单已创建，等待支付
- `1` - 已支付：支付成功
- `2` - 已退款：订单已退款

## 安全建议

1. 务必验证回调签名，防止恶意篡改
2. 做好幂等性处理，避免重复回调导致的问题
3. 妥善保管商户密钥，不要泄露
4. 生产环境建议使用 HTTPS
5. 定期查看支付日志，监控异常订单

## 常见问题

### 1. 支付后没有回调？

- 检查回调地址是否可以从外网访问
- 检查防火墙是否开放
- 查看易支付平台的回调日志

### 2. 签名验证失败？

- 检查商户密钥是否正确
- 检查参数是否完整
- 检查签名算法是否正确

### 3. 无法连接易支付？

- 检查 API 地址是否正确
- 检查服务器是否可以访问外网
- 检查 curl 扩展是否安装

## 卸载步骤

1. 在后台插件管理中禁用并卸载易支付插件

2. 执行 `uninstall.sql` 删除数据库表
   ```bash
   mysql -u用户名 -p密码 数据库名 < addon/yipay/uninstall.sql
   ```

3. 删除 `addon/yipay/` 目录

## 技术支持

如有问题，请联系技术支持。

## 版本历史

- v1.0.0 (2024-01-01)
  - 初始版本
  - 支持支付宝、微信、QQ钱包支付
  - 支持退款功能
  - 支持支付日志查询

## 许可证

MIT License
