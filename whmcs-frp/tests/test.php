<?php

require_once __DIR__ . '/../modules/servers/frp/cache.php';
require_once __DIR__ . '/../modules/servers/frp/middleware.php';
require_once __DIR__ . '/../modules/servers/frp/bandwidth.php';
require_once __DIR__ . '/../modules/servers/frp/logger.php';

class FrpTests {
    private $pdo;
    private $cache;
    private $middleware;
    private $bandwidth;
    private $logger;
    
    public function __construct($config) {
        $this->pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']}",
            $config['db_user'],
            $config['db_pass']
        );
        
        $this->cache = new FrpCache(__DIR__);
        $this->middleware = new FrpMiddleware($this->pdo, $this->cache);
        $this->bandwidth = new FrpBandwidth($this->pdo, $this->cache);
        $this->logger = new FrpLogger($this->pdo);
    }
    
    public function runTests() {
        $this->testCache();
        $this->testMiddleware();
        $this->testBandwidth();
        $this->testLogger();
        echo "所有测试完成！\n";
    }
    
    private function testCache() {
        echo "测试缓存系统...\n";
        
        // 测试设置和获取
        $this->cache->set('test_key', 'test_value', 60);
        assert($this->cache->get('test_key') === 'test_value', '缓存读写失败');
        
        // 测试过期
        $this->cache->set('expire_key', 'expire_value', 1);
        sleep(2);
        assert($this->cache->get('expire_key') === null, '缓存过期失败');
        
        // 测试删除
        $this->cache->set('delete_key', 'delete_value');
        $this->cache->delete('delete_key');
        assert($this->cache->get('delete_key') === null, '缓存删除失败');
        
        echo "缓存测试通过\n";
    }
    
    private function testMiddleware() {
        echo "测试安全中间件...\n";
        
        // 测试认证
        $token = 'test_token';
        $request = ['token' => $token, 'action' => 'test'];
        $result = $this->middleware->validateRequest($request);
        assert($result['valid'] === false, '无效token验证失败');
        
        // 测试速率限制
        $userId = 1;
        $action = 'test_action';
        for ($i = 0; $i < 61; $i++) {
            $this->middleware->checkRateLimit($userId, $action);
        }
        assert($this->middleware->checkRateLimit($userId, $action) === false, '速率限制测试失败');
        
        echo "中间件测试通过\n";
    }
    
    private function testBandwidth() {
        echo "测试带宽控制...\n";
        
        // 测试使用量记录
        $configId = 1;
        $bytes = 1024 * 1024; // 1MB
        $this->bandwidth->trackUsage($configId, $bytes, 'in');
        
        // 测试限制检查
        $usage = $this->bandwidth->getCurrentUsage($configId);
        assert($usage >= $bytes, '带宽使用统计失败');
        
        // 测试统计
        $stats = $this->bandwidth->getDailyStats($configId, 1);
        assert(is_array($stats), '带宽统计获取失败');
        
        echo "带宽控制测试通过\n";
    }
    
    private function testLogger() {
        echo "测试日志系统...\n";
        
        // 测试日志记录
        $serviceId = 1;
        $userId = 1;
        $action = 'test_action';
        $description = 'test description';
        
        $this->logger->log($serviceId, $userId, $action, $description);
        
        // 测试日志获取
        $logs = $this->logger->getLogs($serviceId, 1);
        assert(count($logs) > 0, '日志记录失败');
        assert($logs[0]['action'] === $action, '日志内容不匹配');
        
        echo "日志系统测试通过\n";
    }
}

// 运行测试
if (php_sapi_name() === 'cli') {
    $config = [
        'db_host' => 'localhost',
        'db_user' => 'whmcs_user',
        'db_pass' => 'your_password',
        'db_name' => 'whmcs'
    ];
    
    $tests = new FrpTests($config);
    $tests->runTests();
}
