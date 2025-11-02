# Easypay支付插件安装指南

## 系统要求

- **PHP版本**: 7.0 或更高版本
- **MySQL版本**: 5.6 或更高版本  
- **Web服务器**: Apache、Nginx 或 IIS
- **PHP扩展**: curl、json、mbstring
- **Niushop版本**: V5.0 或更高版本

## 安装步骤

### 第一步：下载插件

将插件文件下载到本地，确保包含以下目录结构：

```
addon/easypay/
├── admin/                    # 后台控制器
├── controller/               # 前台控制器
├── library/                  # 核心服务类
├── model/                    # 数据模型
├── view/                     # 视图文件
├── resources/                # 资源文件
├── tests/                    # 单元测试
├── config.php               # 插件配置
├── info.php                 # 插件信息
├── Easypay.php              # 插件主类
├── install.sql              # 安装脚本
├── uninstall.sql            # 卸载脚本
├── composer.json            # Composer配置
├── README.md                # 说明文档
├── example.php              # 使用示例
├── test.php                 # 测试脚本
```

### 第二步：上传文件

将整个 `easypay` 目录上传到 Niushop V5 的 `addon` 目录下：

```bash
# 使用FTP或SSH上传
scp -r easypay/ user@your-server:/path/to/niushop/addon/
```

或者直接在服务器上操作：

```bash
# 复制到插件目录
cp -r easypay /path/to/niushop/addon/
# 设置权限
chmod -R 755 /path/to/niushop/addon/easypay/
chown -R www-data:www-data /path/to/niushop/addon/easypay/
```

### 第三步：创建数据库表

使用MySQL命令行或phpMyAdmin执行安装脚本：

```bash
# 方法1：使用MySQL命令行
mysql -u用户名 -p密码 数据库名 < addon/easypay/install.sql

# 方法2：使用phpMyAdmin
# 1. 登录phpMyAdmin
# 2. 选择数据库
# 3. 点击"导入"
# 4. 选择 install.sql 文件
# 5. 点击执行
```

**安装脚本会创建以下表：**
- `niushop_easypay_log` - 支付日志表
- `niushop_easypay_config` - 插件配置表

### 第四步：配置插件

1. **登录Niushop后台**
2. **进入插件管理**
   - 导航到 `插件管理` → `支付插件`
   - 找到 "Easypay易支付" 插件
3. **配置插件参数**
   - 点击插件名称进入配置页面
   - 填写以下必要信息：

| 参数 | 说明 | 获取方式 |
|------|------|----------|
| 网关地址 | Easypay支付网关 | 默认：https://api.dulupay.com/ |
| 商户号(PID) | Easypay商户号 | 登录Easypay商户后台获取 |
| 商户密钥 | Easypay商户密钥 | 登录Easypay商户后台获取 |

4. **选择支付方式**
   - 勾选要支持的支付方式（支付宝、微信、QQ钱包）
   - 设置其他可选参数
5. **测试连接**
   - 点击"测试连接"按钮验证配置
   - 确保显示"连接测试成功"

### 第五步：设置回调地址

插件会自动生成回调地址，也可以自定义：

**默认回调地址：**
- 异步回调：`https://您的域名/addon/easypay/notify/index`
- 同步回调：`https://您的域名/addon/easypay/return/index`

**在Easypay商户后台设置：**
1. 登录Easypay商户后台
2. 进入"商户设置" → "回调设置"
3. 填写上述回调地址
4. 保存设置

### 第六步：验证安装

1. **检查插件状态**
   - 在插件管理页面确认Easypay插件状态为"已启用"
2. **运行测试脚本**
   ```bash
   php addon/easypay/test.php
   ```
3. **查看示例代码**
   ```bash
   php addon/easypay/example.php
   ```

## 配置参数详解

### 基本配置

| 参数名 | 说明 | 示例值 |
|--------|------|--------|
| gateway_url | Easypay网关地址 | https://api.dulupay.com/ |
| merchant_id | 商户号(PID) | 1001 |
| secret_key | 商户密钥 | abc123def456 |
| status | 插件状态 | 1(启用) |

### 支付配置

| 参数名 | 说明 | 示例值 |
|--------|------|--------|
| support_type | 支持的支付方式 | ["alipay","wxpay"] |
| order_prefix | 订单标题前缀 | ORDER |
| timeout | 超时时间(分钟) | 30 |

### 回调配置

| 参数名 | 说明 | 示例值 |
|--------|------|--------|
| notify_url | 异步回调地址 | 留空使用默认 |
| return_url | 同步回调地址 | 留空使用默认 |

### 高级配置

| 参数名 | 说明 | 示例值 |
|--------|------|--------|
| debug | 调试模式 | 0(关闭) |

## 集成到订单系统

### 发起支付示例

```php
<?php
use addon\easypay\Easypay;

// 在订单创建后调用
$easypay = new Easypay();

$result = $easypay->pay([
    'trade_no' => $order['order_id'],      // 商户订单号
    'money' => $order['pay_amount'],       // 支付金额
    'pay_type' => $order['pay_method'],    // 支付方式
    'subject' => $order['order_title'],    // 商品名称
]);

if ($result['code'] == 1) {
    // 保存支付信息到订单
    OrderModel::update($order['order_id'], [
        'pay_url' => $result['data']['pay_url'],
        'pay_type' => $order['pay_method'],
    ]);
    
    // 返回支付链接
    return json_success([
        'pay_url' => $result['data']['pay_url']
    ]);
} else {
    return json_error($result['msg']);
}
```

### 处理支付回调

插件会自动处理回调，您只需要在订单系统中处理支付成功的逻辑：

```php
<?php
// 在订单服务中添加支付成功处理
public function handlePaymentSuccess($outTradeNo, $tradeNo, $payData)
{
    // 1. 验证订单
    $order = OrderModel::getByOrderNo($outTradeNo);
    if (!$order) {
        return false;
    }
    
    // 2. 检查订单状态
    if ($order['pay_status'] == 1) {
        return true; // 已支付，避免重复处理
    }
    
    // 3. 验证金额
    if (abs($order['pay_amount'] - $payData['money']) > 0.01) {
        return false; // 金额不匹配
    }
    
    // 4. 更新订单状态
    OrderModel::update($order['id'], [
        'pay_status' => 1,           // 已支付
        'pay_time' => time(),        // 支付时间
        'transaction_id' => $tradeNo, // 第三方交易号
        'pay_data' => json_encode($payData),
    ]);
    
    // 5. 触发后续业务逻辑
    $this->triggerOrderPaid($order);
    
    return true;
}
```

## 故障排除

### 常见问题

#### 1. 插件安装失败
**问题**: 上传后插件列表中看不到Easypay插件
**解决方案**:
- 检查文件权限是否正确设置
- 确认 `info.php` 文件格式正确
- 检查Niushop版本是否兼容

#### 2. 数据库表创建失败
**问题**: 执行install.sql时报错
**解决方案**:
- 检查数据库用户权限
- 确认数据库表前缀是否正确
- 手动执行SQL语句

#### 3. 配置测试失败
**问题**: 点击"测试连接"失败
**解决方案**:
- 检查商户号和密钥是否正确
- 确认网关地址是否可访问
- 检查服务器网络连接

#### 4. 支付发起失败
**问题**: 发起支付时返回错误
**解决方案**:
- 检查订单参数是否完整
- 查看插件错误日志
- 确认商户账户状态正常

#### 5. 回调处理异常
**问题**: 支付成功但订单状态未更新
**解决方案**:
- 检查回调地址是否正确设置
- 查看回调处理日志
- 确认签名验证是否通过

### 日志查看

**插件日志位置**:
- 系统日志文件
- Niushop后台日志管理
- 数据库 `niushop_easypay_log` 表

**调试模式**:
在插件配置中开启调试模式，可以查看详细的请求和响应日志。

### 性能优化

1. **数据库优化**
   - 定期清理过期日志
   - 添加必要的索引
   - 优化查询语句

2. **缓存优化**
   - 缓存插件配置
   - 缓存支付方式信息

3. **网络优化**
   - 使用CDN加速
   - 启用HTTP/2
   - 优化SSL配置

## 卸载插件

如需卸载插件，请按以下步骤操作：

1. **停止使用插件**
   - 在插件管理中禁用Easypay插件
   - 确认没有未完成的订单

2. **备份数据**
   ```bash
   # 备份支付日志
   mysqldump -u用户名 -p密码 数据库名 niushop_easypay_log > easypay_log_backup.sql
   ```

3. **执行卸载脚本**
   ```bash
   mysql -u用户名 -p密码 数据库名 < addon/easypay/uninstall.sql
   ```

4. **删除插件文件**
   ```bash
   rm -rf /path/to/niushop/addon/easypay
   ```

## 技术支持

如遇到问题，请通过以下方式获取支持：

- **官方文档**: https://www.kancloud.cn/niucloud/niushop_b2c_v4_develop/1839354
- **Easypay文档**: https://www.dulupay.com/doc/index.html
- **技术支持**: support@niushop.com
- **问题反馈**: 在GitHub提交Issue

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 支持支付宝、微信、QQ钱包支付
- 完整的支付流程实现
- 后台配置管理界面
- 支付日志记录功能