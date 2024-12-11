使用方法：

安装系统：
bash
CopyInsert
# 修改配置
vim install.php   # 更新数据库配置

# 运行安装脚本
php install.php
运行测试：
bash
CopyInsert
# 修改测试配置
vim tests/test.php   # 更新数据库配置

# 运行测试
php tests/test.php
配置WHMCS：
在WHMCS后台添加服务器
创建产品配置
设置产品选项
启动服务：
bash
CopyInsert
# 启动FRP服务器
frps -c frps.toml

# 检查服务状态
curl http://your-server:7500/api/status
需要我详细解释任何部分吗？或者需要对某个功能进行调整？