<?php

class FrpDeployment {
    private $frpPath;
    private $configPath;
    private $apiEndpoint;
    
    public function __construct($config) {
        $this->frpPath = $config['frp_path'];
        $this->configPath = $config['config_path'];
        $this->apiEndpoint = $config['api_endpoint'];
    }
    
    public function deployNewInstance($userId, $config) {
        try {
            // 1. 生成配置文件
            $configFile = $this->generateConfig($userId, $config);
            
            // 2. 写入配置文件
            $this->writeConfig($userId, $configFile);
            
            // 3. 启动FRP实例
            $this->startFrpInstance($userId);
            
            return [
                'status' => 'success',
                'message' => '部署成功',
                'config' => $configFile
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function generateConfig($userId, $config) {
        // 生成FRP配置文件
        $frpConfig = [
            'common' => [
                'server_addr' => $config['server_addr'],
                'server_port' => $config['server_port'],
                'token' => $config['token']
            ],
            'user_settings' => [
                'user_id' => $userId,
                'max_tunnels' => $config['max_tunnels'],
                'bandwidth_limit' => $config['bandwidth_limit'],
                'allowed_protocols' => $config['allowed_protocols']
            ]
        ];
        
        return $frpConfig;
    }
    
    private function writeConfig($userId, $config) {
        $configPath = $this->configPath . '/user_' . $userId . '.toml';
        // 将配置写入TOML文件
        $this->writeToml($configPath, $config);
    }
    
    private function startFrpInstance($userId) {
        $configFile = $this->configPath . '/user_' . $userId . '.toml';
        $command = sprintf(
            "%s/frpc -c %s",
            $this->frpPath,
            $configFile
        );
        
        exec($command . " > /dev/null 2>&1 &");
    }
    
    public function stopInstance($userId) {
        // 停止指定用户的FRP实例
        $pidFile = $this->configPath . '/user_' . $userId . '.pid';
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            exec("kill " . $pid);
            unlink($pidFile);
        }
    }
    
    public function updateConfig($userId, $newConfig) {
        // 更新用户配置
        $this->stopInstance($userId);
        $this->deployNewInstance($userId, $newConfig);
    }
}

// API路由处理
$action = $_POST['action'] ?? '';
$config = [
    'frp_path' => '/usr/local/frp',
    'config_path' => '/etc/frp/configs',
    'api_endpoint' => 'http://localhost:7000'
];

$deployment = new FrpDeployment($config);

switch ($action) {
    case 'deploy':
        $userId = $_POST['user_id'];
        $userConfig = $_POST['config'];
        echo json_encode($deployment->deployNewInstance($userId, $userConfig));
        break;
        
    case 'stop':
        $userId = $_POST['user_id'];
        $deployment->stopInstance($userId);
        echo json_encode(['status' => 'success']);
        break;
        
    case 'update':
        $userId = $_POST['user_id'];
        $newConfig = $_POST['config'];
        echo json_encode($deployment->updateConfig($userId, $newConfig));
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}
