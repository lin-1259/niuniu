# Niushop V5 易支付插件

## 项目简介

这是为 Niushop V5 电商系统开发的通用易支付插件，支持支付宝、微信支付、QQ钱包等多种支付方式。

## 功能特点

- ✅ 支持支付宝、微信支付、QQ钱包
- ✅ 完整的支付流程（发起支付、异步回调、同步回调）
- ✅ 支付状态查询
- ✅ 退款功能
- ✅ 支付日志记录和查询
- ✅ 后台配置管理界面
- ✅ 签名验证安全机制
- ✅ 幂等性处理防止重复回调
- ✅ 支持PC端和移动端支付

## 目录结构

```
addon/yipay/
├── config.php                    # 插件配置文件
├── install.sql                   # 安装SQL（创建支付日志表）
├── uninstall.sql                 # 卸载SQL
├── Yipay.php                     # 插件主类（实现支付接口）
├── README.md                     # 插件说明文档
├── example.php                   # 使用示例
├── test.php                      # 测试脚本
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

## 快速开始

### 1. 安装插件

```bash
# 创建数据库表
mysql -u用户名 -p密码 数据库名 < addon/yipay/install.sql
```

### 2. 配置插件

访问后台配置页面：`/addon/yipay/admin/config/index`

配置以下参数：
- 易支付API地址
- 商户号（PID）
- 商户密钥（KEY）
- 启用状态
- 支持的支付方式

### 3. 测试连接

在配置页面点击"测试连接"按钮，确保配置正确。

### 4. 发起支付

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->pay([
    'trade_no' => 'ORDER_20240101001',
    'money' => 100.00,
    'pay_type' => 'alipay',  // alipay/wxpay/qqpay
    'goods_name' => '商品名称'
]);

if ($result['code'] == 1) {
    // 跳转到支付链接
    header('Location: ' . $result['data']['pay_url']);
}
```

## 使用文档

详细使用文档请参考：[addon/yipay/README.md](addon/yipay/README.md)

使用示例请参考：[addon/yipay/example.php](addon/yipay/example.php)

## 测试

运行测试脚本检查插件功能：

```bash
php addon/yipay/test.php
```

## 接受标准

- ✅ 插件可以正常安装和卸载
- ✅ 后台可以配置API参数
- ✅ 支持支付宝、微信、QQ钱包支付
- ✅ 异步回调和同步回调正常工作
- ✅ 签名验证正确
- ✅ 支付日志完整记录
- ✅ 退款功能正常
- ✅ 代码符合 niushop v5 插件规范

## 技术栈

- PHP 7.0+
- ThinkPHP 5.x
- MySQL 5.6+
- curl 扩展

## 易支付签名算法

签名生成步骤：
1. 将参数按 key 升序排列
2. 拼接成 `key=value` 格式，用 `&` 连接
3. 末尾加上商户密钥
4. MD5 加密后转大写

## 安全建议

1. 务必验证回调签名，防止恶意篡改
2. 做好幂等性处理，避免重复回调导致的问题
3. 妥善保管商户密钥，不要泄露
4. 生产环境建议使用 HTTPS
5. 定期查看支付日志，监控异常订单

## 版本历史

- v1.0.0 (2024-01-01)
  - 初始版本
  - 支持支付宝、微信、QQ钱包支付
  - 支持退款功能
  - 支持支付日志查询

## 许可证

MIT License