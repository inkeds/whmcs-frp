# FRP WHMCS 模块使用说明

## 目录
1. [系统部署](#系统部署)
2. [WHMCS配置](#whmcs配置)
3. [FRP服务器配置](#frp服务器配置)
4. [日常运维](#日常运维)
5. [故障处理](#故障处理)

## 系统部署

### 1. 环境准备

确保服务器满足以下要求：
```
- 操作系统：Linux/Windows
- PHP版本：>= 7.4
- MySQL版本：>= 5.7
- FRP版本：>= 0.37.0
- WHMCS版本：>= 8.0
```

### 2. 安装步骤

1. 下载模块：
```bash
git clone https://your-repo/whmcs-frp.git
cd whmcs-frp
```

2. 修改安装配置：
```php
// 编辑 install.php
$config = [
    'db_host' => 'localhost',     // 数据库主机
    'db_user' => 'whmcs_user',    // 数据库用户名
    'db_pass' => 'your_password', // 数据库密码
    'db_name' => 'whmcs',         // 数据库名称
    'base_dir' => '/path/to/whmcs' // WHMCS根目录
];
```

3. 运行安装脚本：
```bash
php install.php
```

## WHMCS配置

### 1. 添加服务器

1. 登录WHMCS管理后台
2. 进入 `系统设置` -> `产品/服务` -> `服务器`
3. 点击 `添加新服务器`
4. 填写配置：
   - 名称：FRP服务器
   - 主机名：your-frp-server.com
   - IP地址：服务器IP
   - 分配的IP：可选
   - 状态：活动
   - 最大账户：根据服务器配置设置
   - 类型：选择"FRP"

### 2. 创建产品

1. 创建产品组：
   - 进入 `系统设置` -> `产品/服务` -> `产品组`
   - 添加新组："FRP服务"

2. 创建产品：
   - 进入 `系统设置` -> `产品/服务` -> `产品/服务`
   - 点击 `创建新产品`
   - 选择类型：`其他服务`
   - 产品组：选择"FRP服务"

3. 配置产品：
   - 模块：选择"FRP"
   - 配置选项：
     ```
     带宽限制：10 Mbps（示例）
     最大隧道数：5（示例）
     允许协议：tcp,udp,http,https
     ```

### 3. 自定义字段

添加以下自定义字段：
1. 域名（可选）
2. 备注信息
3. 其他需求字段

## FRP服务器配置

### 1. 基础配置

1. 编辑服务器配置：
```toml
# frps.toml
bindPort = 7000
bindAddr = "0.0.0.0"

# 管理面板设置
webServer.port = 7500
webServer.addr = "0.0.0.0"

# 认证设置
auth.method = "token"
auth.token = "your_secure_token"
```

2. 启动服务：
```bash
frps -c /etc/frp/frps.toml
```

### 2. 安全设置

1. 配置防火墙：
```bash
# 开放必要端口
ufw allow 7000/tcp  # FRP服务端口
ufw allow 7500/tcp  # 管理面板端口
```

2. 设置SSL证书（推荐）：
```toml
# frps.toml
webServer.tls.certFile = "/path/to/cert.pem"
webServer.tls.keyFile = "/path/to/key.pem"
```

## 日常运维

### 1. 监控检查

1. 服务状态检查：
```bash
# 检查FRP服务状态
systemctl status frps

# 检查日志
tail -f /var/log/frps.log
```

2. 资源监控：
- 通过WHMCS管理面板查看使用统计
- 检查服务器负载
- 监控带宽使用

### 2. 日常维护

1. 数据库维护：
```sql
-- 清理过期数据
DELETE FROM mod_frp_usage_stats WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- 优化表
OPTIMIZE TABLE mod_frp_usage_stats;
```

2. 缓存清理：
- 通过WHMCS面板清理缓存
- 或使用API：
```bash
curl -X POST http://your-server/api/system/cache/clear
```

### 3. 安全维护

1. 定期更新密钥：
```bash
# 生成新token
openssl rand -hex 32

# 更新配置文件
vim /etc/frp/frps.toml
```

2. 日志审计：
- 检查访问日志
- 审查操作记录
- 分析异常行为

## 故障处理

### 1. 常见问题

1. 连接失败：
- 检查FRP服务状态
- 验证端口是否开放
- 确认配置文件正确

2. 带宽问题：
- 检查限制设置
- 查看使用统计
- 分析流量日志

3. 性能问题：
- 清理系统缓存
- 优化数据库
- 检查服务器负载

### 2. 故障恢复

1. 服务重启：
```bash
systemctl restart frps
```

2. 配置恢复：
```bash
# 恢复配置文件
cp /etc/frp/frps.toml.backup /etc/frp/frps.toml

# 重启服务
systemctl restart frps
```

3. 数据恢复：
```bash
# 从备份恢复数据库
mysql -u username -p whmcs < backup.sql
```

### 3. 技术支持

如遇到无法解决的问题，请联系技术支持：
- 邮件：support@your-company.com
- 电话：+86-xxx-xxxx-xxxx
- 工单系统：https://support.your-company.com

## 最佳实践

1. 定期备份：
- 配置文件备份
- 数据库备份
- 日志备份

2. 安全建议：
- 使用强密码
- 启用SSL/TLS
- 限制IP访问

3. 性能优化：
- 合理设置缓存
- 定期清理数据
- 监控系统资源
