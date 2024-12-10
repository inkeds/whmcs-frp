# FRP WHMCS 模块快速入门指南

## 5分钟快速部署

### 1. 下载安装

```bash
# 克隆代码
git clone https://your-repo/whmcs-frp.git

# 复制到WHMCS目录
cp -r whmcs-frp/* /path/to/whmcs/

# 运行安装脚本
php install.php
```

### 2. WHMCS配置

1. 登录WHMCS后台
2. 添加服务器：
   - 系统设置 -> 产品/服务 -> 服务器
   - 添加新服务器，选择"FRP"类型

3. 创建产品：
   - 系统设置 -> 产品/服务 -> 产品/服务
   - 创建新产品，选择"FRP"模块

### 3. FRP服务器配置

```bash
# 复制配置文件
cp /path/to/whmcs/modules/servers/frp/api/templates/frps.toml /etc/frp/

# 启动服务
frps -c /etc/frp/frps.toml
```

## 基本使用

### 1. 创建订单

1. 客户下单流程：
   - 选择FRP产品
   - 填写必要信息
   - 完成支付

2. 自动开通：
   - 系统自动配置FRP
   - 生成客户配置文件
   - 发送开通邮件

### 2. 管理隧道

1. 客户操作：
   - 登录客户中心
   - 选择FRP服务
   - 管理隧道配置

2. 查看统计：
   - 流量使用情况
   - 在线状态
   - 带宽统计

### 3. 常用操作

1. 重置配置：
   - 进入产品详情
   - 点击"重置配置"
   - 获取新配置文件

2. 查看日志：
   - 进入产品详情
   - 点击"查看日志"
   - 分析使用情况

## 注意事项

1. 安全建议：
   - 及时更新密码
   - 定期检查日志
   - 监控异常活动

2. 性能建议：
   - 合理设置限制
   - 定期清理缓存
   - 监控资源使用

3. 维护建议：
   - 定期备份数据
   - 更新系统补丁
   - 检查服务状态

## 获取帮助

- 详细文档：查看 `docs/USAGE.md`
- 技术支持：support@your-company.com
- 问题反馈：https://github.com/your-repo/issues
