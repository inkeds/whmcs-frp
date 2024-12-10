<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// 加载必要的类
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/bandwidth.php';
require_once __DIR__ . '/logger.php';

// 初始化组件
$cache = new FrpCache(__DIR__);
$middleware = new FrpMiddleware($pdo, $cache);
$bandwidth = new FrpBandwidth($pdo, $cache);
$logger = new FrpLogger($pdo);

function frp_ConfigOptions() {
    return [
        "bandwidth" => [
            "FriendlyName" => "带宽限制(Mbps)",
            "Type" => "text",
            "Size" => "10",
            "Default" => "10",
            "Description" => "设置客户端带宽限制，单位Mbps",
        ],
        "max_tunnels" => [
            "FriendlyName" => "最大隧道数",
            "Type" => "text",
            "Size" => "5",
            "Default" => "5",
            "Description" => "允许创建的最大隧道数量",
        ],
        "allowed_protocols" => [
            "FriendlyName" => "允许的协议",
            "Type" => "checkboxgroup",
            "Options" => "tcp,udp,http,https",
            "Default" => "tcp,http",
            "Description" => "允许使用的协议类型",
        ],
    ];
}

function frp_CreateAccount(array $params) {
    global $middleware, $bandwidth, $logger;
    
    try {
        // 生成客户端配置
        $clientConfig = generateClientConfig($params);
        
        // 在FRP服务器上创建配置
        $serverConfig = createServerConfig($params);
        
        // 保存配置信息到WHMCS数据库
        saveConfigToDatabase($params, $clientConfig, $serverConfig);
        
        // 记录操作日志
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'create_account',
            '创建FRP账户'
        );
        
        return 'success';
    } catch (Exception $e) {
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'create_account',
            $e->getMessage(),
            'error'
        );
        return "创建失败: " . $e->getMessage();
    }
}

function frp_SuspendAccount(array $params) {
    try {
        // 暂停FRP服务器上的配置
        suspendServerConfig($params);
        return 'success';
    } catch (Exception $e) {
        return "暂停失败: " . $e->getMessage();
    }
}

function frp_TerminateAccount(array $params) {
    try {
        // 删除FRP服务器上的配置
        deleteServerConfig($params);
        return 'success';
    } catch (Exception $e) {
        return "终止失败: " . $e->getMessage();
    }
}

function frp_ClientArea($params) {
    try {
        // 获取客户端配置和使用统计
        $clientConfig = getClientConfig($params);
        $stats = getClientStats($params);
        
        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'config' => $clientConfig,
                'stats' => $stats,
            ],
        ];
    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

// 辅助函数
function generateClientConfig($params) {
    // 生成客户端配置文件
    $config = [
        'serverAddr' => $params['serverip'],
        'serverPort' => $params['serverport'],
        'auth' => [
            'token' => generateUniqueToken($params),
        ],
        'transport' => [
            'bandwidthLimit' => $params['configoption1'] . "MB",
        ],
    ];
    
    return $config;
}

function createServerConfig($params) {
    // 在FRP服务器上创建对应配置
    $config = [
        'user' => $params['username'],
        'maxTunnels' => $params['configoption2'],
        'allowedProtocols' => explode(',', $params['configoption3']),
        'bandwidthLimit' => $params['configoption1'],
    ];
    
    // 调用FRP API创建配置
    return callFrpApi('create_config', $config);
}

function generateUniqueToken($params) {
    return md5($params['serviceid'] . time() . rand(1000, 9999));
}

// 添加新的管理功能
function frp_AdminCustomButtonArray() {
    return [
        "查看日志" => "viewLogs",
        "重置配置" => "resetConfig",
        "清理缓存" => "clearCache"
    ];
}

function frp_viewLogs(array $params) {
    global $logger;
    
    $logs = $logger->getLogs($params['serviceid']);
    return [
        'templatefile' => 'templates/admin/logs',
        'vars' => ['logs' => $logs]
    ];
}

function frp_resetConfig(array $params) {
    global $middleware, $logger, $cache;
    
    try {
        // 停止现有实例
        stopFrpInstance($params);
        
        // 生成新配置
        $newConfig = generateClientConfig($params);
        
        // 更新服务器配置
        updateServerConfig($params, $newConfig);
        
        // 清理缓存
        $cache->clear();
        
        // 记录操作
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'reset_config',
            '重置FRP配置'
        );
        
        return 'success';
    } catch (Exception $e) {
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'reset_config',
            $e->getMessage(),
            'error'
        );
        return "重置失败: " . $e->getMessage();
    }
}

function frp_clearCache(array $params) {
    global $cache, $logger;
    
    try {
        $cache->clear();
        
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'clear_cache',
            '清理系统缓存'
        );
        
        return 'success';
    } catch (Exception $e) {
        $logger->log(
            $params['serviceid'],
            $params['userid'],
            'clear_cache',
            $e->getMessage(),
            'error'
        );
        return "清理失败: " . $e->getMessage();
    }
}
