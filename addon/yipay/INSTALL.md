# 易支付插件安装指南

## 环境要求

在安装插件之前，请确保您的系统满足以下要求：

- PHP >= 7.0
- MySQL >= 5.6
- ThinkPHP >= 5.0
- PHP curl 扩展已启用
- PHP json 扩展已启用

## 安装步骤

### 1. 上传插件文件

将 `yipay` 目录上传到您的 Niushop V5 系统的 `addon/` 目录下。

```
/path/to/niushop/addon/yipay/
```

### 2. 创建数据库表

使用以下命令执行安装SQL脚本：

```bash
mysql -u用户名 -p密码 数据库名 < addon/yipay/install.sql
```

或者在 phpMyAdmin 中导入 `install.sql` 文件。

执行后会创建以下数据表：
- `ns_yipay_log` - 支付日志表

### 3. 验证安装

检查数据表是否创建成功：

```sql
SHOW TABLES LIKE 'ns_yipay_log';
```

检查表结构：

```sql
DESCRIBE ns_yipay_log;
```

应该看到以下字段：
- id
- trade_no
- out_trade_no
- pay_type
- money
- status
- notify_data
- create_time
- pay_time

### 4. 配置插件

访问后台配置页面：

```
http://yourdomain.com/addon/yipay/admin/config/index
```

填写以下配置信息：

#### 4.1 易支付API地址
例如：`http://pay.example.com` 或 `https://pay.example.com`

**注意**：地址末尾不要加斜杠 `/`

#### 4.2 商户号（PID）
从易支付平台获取的商户号，例如：`10001`

#### 4.3 商户密钥（KEY）
从易支付平台获取的商户密钥，例如：`your_secret_key_123456`

**重要**：请妥善保管商户密钥，不要泄露给他人。

#### 4.4 启用状态
勾选"开启"启用支付功能。

#### 4.5 支持的支付方式
根据您的易支付平台支持的支付方式，勾选相应选项：
- 支付宝
- 微信支付
- QQ钱包

### 5. 测试连接

配置完成后，点击"测试连接"按钮，确保配置正确。

如果连接成功，会显示"连接成功"的提示。

### 6. 配置回调地址

在您的易支付平台后台配置以下回调地址：

#### 异步回调地址（必填）
```
http://yourdomain.com/addon/yipay/notify/notify
```

#### 同步回调地址（选填）
```
http://yourdomain.com/addon/yipay/notify/return
```

**注意**：
- 回调地址必须是可以从外网访问的地址
- 如果使用了内网穿透工具（如 ngrok），请使用对应的外网地址
- 回调地址不要使用 localhost 或 127.0.0.1

### 7. 测试支付

创建一个测试订单进行支付测试：

```php
use addon\yipay\Yipay;

$yipay = new Yipay();

$result = $yipay->pay([
    'trade_no' => 'TEST_' . time(),
    'money' => 0.01,  // 测试金额
    'pay_type' => 'alipay',
    'goods_name' => '测试商品'
]);

if ($result['code'] == 1) {
    echo "支付链接：" . $result['data']['pay_url'];
}
```

## 常见问题

### Q1: 提示"配置不完整"

**原因**：未配置或配置错误易支付参数。

**解决**：
1. 检查 API 地址是否正确
2. 检查商户号和密钥是否正确
3. 确保已保存配置

### Q2: 测试连接失败

**原因**：无法连接到易支付平台。

**解决**：
1. 检查服务器是否可以访问外网
2. 检查 API 地址是否正确
3. 检查防火墙是否阻止了请求
4. 检查 curl 扩展是否已安装

验证 curl 扩展：
```bash
php -m | grep curl
```

### Q3: 支付后没有回调

**原因**：回调地址无法访问或配置错误。

**解决**：
1. 检查回调地址是否可以从外网访问
2. 在易支付平台后台正确配置回调地址
3. 检查服务器防火墙设置
4. 查看易支付平台的回调日志

测试回调地址是否可访问：
```bash
curl http://yourdomain.com/addon/yipay/notify/notify
```

### Q4: 签名验证失败

**原因**：商户密钥配置错误或签名算法不匹配。

**解决**：
1. 检查商户密钥是否正确（区分大小写）
2. 确保密钥中没有多余的空格
3. 查看日志文件：`addon/yipay/runtime/日期.log`

### Q5: 数据表创建失败

**原因**：数据库权限不足或表已存在。

**解决**：
1. 确保数据库用户有创建表的权限
2. 如果表已存在，先删除旧表：
```sql
DROP TABLE IF EXISTS ns_yipay_log;
```
然后重新执行 `install.sql`

### Q6: 找不到类 YipayService

**原因**：命名空间或自动加载配置问题。

**解决**：
1. 检查文件路径是否正确
2. 确保使用了正确的命名空间：`addon\yipay\library\YipayService`
3. 清空 ThinkPHP 缓存

### Q7: 回调处理重复

**原因**：易支付可能会多次发送回调。

**解决**：插件已实现幂等性处理，重复回调不会导致问题。如果仍有问题，请检查日志。

## 调试技巧

### 查看日志

插件会自动记录日志到：
```
addon/yipay/runtime/YYYY-MM-DD.log
```

日志内容包括：
- 回调原始数据
- 签名验证结果
- 处理成功/失败信息
- 异常错误信息

### 手动测试签名

使用测试脚本验证签名算法：
```bash
php addon/yipay/test.php
```

### 查询支付日志

在后台配置页面可以查看支付日志列表，包括：
- 订单号
- 支付方式
- 金额
- 状态
- 创建时间
- 支付时间

或直接查询数据库：
```sql
SELECT * FROM ns_yipay_log ORDER BY create_time DESC LIMIT 10;
```

### 手动触发回调

模拟易支付回调进行测试：
```bash
curl -X POST http://yourdomain.com/addon/yipay/notify/notify \
  -d "pid=10001" \
  -d "trade_no=TEST123456" \
  -d "out_trade_no=ORDER_001" \
  -d "type=alipay" \
  -d "name=测试商品" \
  -d "money=100.00" \
  -d "trade_status=TRADE_SUCCESS" \
  -d "sign=YOUR_SIGN_HERE"
```

## 性能优化

### 1. 数据库索引

安装SQL已包含必要的索引，如果数据量大，可以考虑添加更多索引：

```sql
ALTER TABLE ns_yipay_log ADD INDEX idx_pay_time (pay_time);
ALTER TABLE ns_yipay_log ADD INDEX idx_status_create (status, create_time);
```

### 2. 日志清理

定期清理过期日志文件：
```bash
# 删除30天前的日志
find addon/yipay/runtime/ -name "*.log" -mtime +30 -delete
```

### 3. 数据归档

对于历史支付记录，可以定期归档到其他表：
```sql
-- 创建归档表
CREATE TABLE ns_yipay_log_archive LIKE ns_yipay_log;

-- 归档一年前的数据
INSERT INTO ns_yipay_log_archive 
SELECT * FROM ns_yipay_log 
WHERE create_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR));

-- 删除已归档的数据
DELETE FROM ns_yipay_log 
WHERE create_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR));
```

## 安全建议

1. **使用HTTPS**：生产环境建议使用HTTPS协议
2. **定期更新密钥**：定期更换商户密钥
3. **限制访问**：配置防火墙规则限制回调接口的访问
4. **监控异常**：定期检查支付日志，发现异常及时处理
5. **备份数据**：定期备份支付日志数据

## 升级说明

如果需要升级到新版本：

1. 备份现有数据和配置
2. 下载新版本文件
3. 覆盖旧文件
4. 执行升级SQL（如果有）
5. 测试功能是否正常

## 卸载说明

如果需要卸载插件：

1. 在后台禁用插件
2. 执行卸载SQL：
```bash
mysql -u用户名 -p密码 数据库名 < addon/yipay/uninstall.sql
```
3. 删除插件目录：
```bash
rm -rf addon/yipay/
```

**警告**：卸载会删除所有支付日志数据，请提前备份！

## 技术支持

如果遇到问题：

1. 查看本文档的"常见问题"部分
2. 查看插件日志文件
3. 阅读 README.md 和 example.php
4. 运行测试脚本 test.php 进行诊断
5. 联系技术支持

## 相关资源

- [插件说明文档](README.md)
- [使用示例](example.php)
- [测试脚本](test.php)
- [更新日志](CHANGELOG.md)

---

祝您使用愉快！
