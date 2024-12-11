<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function frp_ClientArea($params) {
    // 加载必要的类
    require_once __DIR__ . '/monitor.php';
    
    // 初始化监控系统
    $monitor = new FrpMonitor($pdo, $params['serverhostname']);
    
    // 获取服务状态
    $status = $monitor->checkStatus($params['serviceid']);
    
    // 获取隧道列表
    $tunnels = getTunnels($params['serviceid']);
    
    // 获取使用统计
    $stats = getUsageStats($params['serviceid']);
    
    // 获取配置信息
    $config = getClientConfig($params['serviceid']);
    
    // 准备图表数据
    $chartData = prepareChartData($stats);
    
    return array(
        'templatefile' => 'templates/clientarea',
        'vars' => array(
            'service_status' => $status['status'],
            'bandwidth_limit' => $params['configoption1'],
            'max_tunnels' => $params['configoption2'],
            'tunnel_count' => count($tunnels),
            'today_traffic' => formatTraffic($stats['today_total']),
            'tunnels' => $tunnels,
            'chart_labels' => $chartData['labels'],
            'chart_data' => $chartData['data'],
            'config_content' => $config,
            'serviceid' => $params['serviceid']
        ),
    );
}

function getTunnels($serviceId) {
    global $pdo;
    
    $query = "SELECT t.*, s.bytes_in, s.bytes_out, s.connections 
              FROM mod_frp_tunnels t 
              LEFT JOIN mod_frp_usage_stats s ON t.id = s.tunnel_id 
              WHERE t.config_id = (
                  SELECT id FROM mod_frp_user_configs WHERE service_id = :service_id
              )
              AND (s.date IS NULL OR s.date = CURRENT_DATE())";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([':service_id' => $serviceId]);
    
    $tunnels = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['traffic'] = formatTraffic($row['bytes_in'] + $row['bytes_out']);
        $tunnels[] = $row;
    }
    
    return $tunnels;
}

function getUsageStats($serviceId) {
    global $pdo;
    
    $query = "SELECT date, SUM(bytes_in) as bytes_in, SUM(bytes_out) as bytes_out 
              FROM mod_frp_usage_stats 
              WHERE config_id = (
                  SELECT id FROM mod_frp_user_configs WHERE service_id = :service_id
              )
              AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
              GROUP BY date
              ORDER BY date";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([':service_id' => $serviceId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prepareChartData($stats) {
    $labels = [];
    $data = ['in' => [], 'out' => []];
    
    foreach ($stats as $stat) {
        $labels[] = $stat['date'];
        $data['in'][] = round($stat['bytes_in'] / (1024 * 1024), 2); // Convert to MB
        $data['out'][] = round($stat['bytes_out'] / (1024 * 1024), 2);
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function formatTraffic($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getClientConfig($serviceId) {
    global $pdo;
    
    $query = "SELECT * FROM mod_frp_user_configs WHERE service_id = :service_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':service_id' => $serviceId]);
    
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$config) {
        return '';
    }
    
    // 生成配置文件内容
    return generateConfigFile($config);
}

function generateConfigFile($config) {
    // 读取模板
    $template = file_get_contents(__DIR__ . '/api/templates/frpc.toml');
    
    // 替换变量
    $replacements = [
        '{{ server_addr }}' => $config['server_addr'],
        '{{ server_port }}' => $config['server_port'],
        '{{ user_token }}' => $config['token'],
        '{{ user_id }}' => $config['client_id'],
        '{{ bandwidth_limit }}' => $config['bandwidth_limit']
    ];
    
    return strtr($template, $replacements);
}
