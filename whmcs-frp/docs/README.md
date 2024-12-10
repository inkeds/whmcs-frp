# FRP WHMCS 模块文档

## 目录
1. [系统概述](#系统概述)
2. [安装配置](#安装配置)
3. [功能说明](#功能说明)
4. [API文档](#api文档)
5. [故障排除](#故障排除)

## 系统概述

本模块实现了FRP与WHMCS的完整集成，提供以下核心功能：

- 自动化配置和部署
- 带宽监控和控制
- 用户管理界面
- 安全认证机制
- 性能优化系统

### 系统架构

```
whmcs-frp/
├── modules/
│   └── servers/
│       └── frp/
│           ├── frp.php          # 主模块文件
│           ├── monitor.php      # 监控系统
│           ├── middleware.php   # 安全中间件
│           ├── bandwidth.php    # 带宽控制
│           ├── cache.php        # 缓存系统
│           ├── logger.php       # 日志系统
│           └── templates/       # 模板文件
├── api/
│   ├── deploy.php              # 部署脚本
│   └── templates/              # 配置模板
└── sql/
    └── install.sql             # 数据库结构

## 安装配置

### 系统要求

- WHMCS 8.0+
- PHP 7.4+
- MySQL 5.7+
- FRP 0.37.0+

### 安装步骤

1. 复制文件：
```bash
cp -r whmcs-frp/* /path/to/whmcs/
```

2. 创建数据库表：
```sql
mysql -u username -p whmcs < /path/to/whmcs/modules/servers/frp/sql/install.sql
```

3. 设置权限：
```bash
chmod -R 755 /path/to/whmcs/modules/servers/frp
chmod -R 777 /path/to/whmcs/modules/servers/frp/cache
```

4. 配置FRP服务器：
- 复制 `api/templates/frps.toml` 到FRP服务器
- 修改配置参数
- 启动FRP服务器

### WHMCS配置

1. 创建服务器：
- 进入WHMCS管理后台
- 系统设置 -> 产品/服务 -> 服务器
- 添加新服务器，选择"FRP"类型

2. 创建产品：
- 创建新产品组"FRP服务"
- 创建产品，选择"FRP"作为模块
- 配置产品选项：
  - 带宽限制
  - 最大隧道数
  - 允许的协议

## 功能说明

### 1. 缓存系统

缓存系统使用文件存储，支持：
- 自动过期
- 批量清理
- 性能优化

使用示例：
```php
$cache = new FrpCache(__DIR__);
$cache->set('key', 'value', 300); // 缓存5分钟
$value = $cache->get('key');
```

### 2. 安全系统

包含多层安全机制：
- API认证
- IP限制
- 速率控制
- 操作日志

配置示例：
```php
$middleware = new FrpMiddleware($pdo, $cache);
$result = $middleware->validateRequest($request);
```

### 3. 带宽控制

实时监控和控制带宽使用：
- 使用量统计
- 限制控制
- 警告通知
- 报表生成

使用示例：
```php
$bandwidth = new FrpBandwidth($pdo, $cache);
$bandwidth->trackUsage($configId, $bytes);
$bandwidth->checkAndNotifyLimit($configId);
```

### 4. 监控系统

全面的系统监控：
- 服务状态
- 资源使用
- 性能指标
- 异常报警

### 5. 用户界面

提供完整的用户控制面板：
- 隧道管理
- 统计查看
- 配置下载
- 日志查看

## API文档

### 认证

所有API请求需要包含认证信息：
```
Authorization: Bearer <token>
```

### 端点

1. 隧道管理
```
POST /api/tunnel/create
POST /api/tunnel/update
POST /api/tunnel/delete
GET  /api/tunnel/list
```

2. 统计信息
```
GET /api/stats/bandwidth
GET /api/stats/usage
GET /api/stats/daily
```

3. 系统管理
```
POST /api/system/reset
POST /api/system/cache/clear
GET  /api/system/status
```

## 故障排除

### 常见问题

1. 连接失败
- 检查FRP服务器状态
- 验证配置文件
- 检查防火墙设置

2. 带宽限制
- 确认限制设置
- 检查统计数据
- 查看警告日志

3. 性能问题
- 清理缓存
- 优化数据库
- 检查日志

### 日志说明

日志级别：
- INFO: 普通信息
- WARNING: 警告信息
- ERROR: 错误信息
- DEBUG: 调试信息

### 维护建议

1. 定期维护：
- 清理过期数据
- 优化数据库
- 更新配置文件

2. 监控检查：
- 服务器状态
- 资源使用
- 异常情况

3. 安全维护：
- 更新密钥
- 检查访问日志
- 审计操作记录
