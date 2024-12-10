<?php

class FrpInstaller {
    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbName;
    private $baseDir;
    
    public function __construct($config) {
        $this->dbHost = $config['db_host'];
        $this->dbUser = $config['db_user'];
        $this->dbPass = $config['db_pass'];
        $this->dbName = $config['db_name'];
        $this->baseDir = $config['base_dir'];
    }
    
    public function install() {
        try {
            // 1. 检查系统要求
            $this->checkRequirements();
            
            // 2. 创建必要目录
            $this->createDirectories();
            
            // 3. 设置权限
            $this->setPermissions();
            
            // 4. 导入数据库
            $this->importDatabase();
            
            // 5. 配置FRP服务器
            $this->configureFrpServer();
            
            echo "安装完成！\n";
            return true;
        } catch (Exception $e) {
            echo "安装失败: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function checkRequirements() {
        // 检查PHP版本
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            throw new Exception('需要PHP 7.4或更高版本');
        }
        
        // 检查必要的PHP扩展
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("缺少必要的PHP扩展: {$ext}");
            }
        }
        
        // 检查目录权限
        if (!is_writable($this->baseDir)) {
            throw new Exception("目录不可写: {$this->baseDir}");
        }
    }
    
    private function createDirectories() {
        $directories = [
            'modules/servers/frp',
            'modules/servers/frp/cache',
            'modules/servers/frp/templates',
            'modules/servers/frp/templates/admin',
            'api/templates'
        ];
        
        foreach ($directories as $dir) {
            $path = $this->baseDir . '/' . $dir;
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new Exception("无法创建目录: {$path}");
                }
            }
        }
    }
    
    private function setPermissions() {
        $paths = [
            'modules/servers/frp' => 0755,
            'modules/servers/frp/cache' => 0777,
            'api/templates' => 0755
        ];
        
        foreach ($paths as $path => $perm) {
            $fullPath = $this->baseDir . '/' . $path;
            if (!chmod($fullPath, $perm)) {
                throw new Exception("无法设置权限: {$fullPath}");
            }
        }
    }
    
    private function importDatabase() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->dbHost};dbname={$this->dbName}",
                $this->dbUser,
                $this->dbPass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 读取SQL文件
            $sql = file_get_contents($this->baseDir . '/sql/install.sql');
            
            // 执行SQL
            $pdo->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("数据库错误: " . $e->getMessage());
        }
    }
    
    private function configureFrpServer() {
        // 复制FRP服务器配置模板
        $srcConfig = $this->baseDir . '/api/templates/frps.toml';
        $dstConfig = $this->baseDir . '/frps.toml';
        
        if (!copy($srcConfig, $dstConfig)) {
            throw new Exception("无法复制FRP服务器配置文件");
        }
        
        // 生成随机Token
        $token = bin2hex(random_bytes(16));
        
        // 更新配置文件
        $config = file_get_contents($dstConfig);
        $config = str_replace('YOUR_DEFAULT_TOKEN', $token, $config);
        
        if (!file_put_contents($dstConfig, $config)) {
            throw new Exception("无法更新FRP服务器配置文件");
        }
    }
}

// 使用示例
if (php_sapi_name() === 'cli') {
    $config = [
        'db_host' => 'localhost',
        'db_user' => 'whmcs_user',
        'db_pass' => 'your_password',
        'db_name' => 'whmcs',
        'base_dir' => __DIR__
    ];
    
    $installer = new FrpInstaller($config);
    $installer->install();
}
