<?php

class FrpBandwidth {
    private $db;
    private $cache;
    
    public function __construct($db, $cache) {
        $this->db = $db;
        $this->cache = $cache;
    }
    
    public function trackUsage($configId, $bytes, $direction = 'in') {
        $date = date('Y-m-d');
        $cacheKey = "bandwidth_{$configId}_{$date}_{$direction}";
        
        // 使用缓存累积小量数据，减少数据库写入
        $currentUsage = $this->cache->get($cacheKey) ?? 0;
        $currentUsage += $bytes;
        
        // 当累积数据超过1MB时写入数据库
        if ($currentUsage > 1048576) { // 1MB = 1048576 bytes
            $this->updateDatabaseUsage($configId, $currentUsage, $direction, $date);
            $currentUsage = 0;
        }
        
        $this->cache->set($cacheKey, $currentUsage, 86400); // 24小时缓存
    }
    
    private function updateDatabaseUsage($configId, $bytes, $direction, $date) {
        $field = $direction == 'in' ? 'bytes_in' : 'bytes_out';
        
        $query = "INSERT INTO mod_frp_usage_stats 
                 (config_id, {$field}, date) 
                 VALUES (:config_id, :bytes, :date)
                 ON DUPLICATE KEY UPDATE 
                 {$field} = {$field} + :bytes";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':config_id' => $configId,
            ':bytes' => $bytes,
            ':date' => $date
        ]);
    }
    
    public function checkLimit($configId) {
        $config = $this->getConfig($configId);
        if (!$config) {
            return false;
        }
        
        $usage = $this->getCurrentUsage($configId);
        return $usage < $config['bandwidth_limit'];
    }
    
    public function getCurrentUsage($configId) {
        $date = date('Y-m-d');
        $cacheKey = "total_bandwidth_{$configId}_{$date}";
        
        $usage = $this->cache->get($cacheKey);
        if ($usage === null) {
            $query = "SELECT SUM(bytes_in + bytes_out) as total 
                     FROM mod_frp_usage_stats 
                     WHERE config_id = :config_id 
                     AND date = :date";
                     
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':config_id' => $configId,
                ':date' => $date
            ]);
            
            $usage = $stmt->fetchColumn() ?: 0;
            $this->cache->set($cacheKey, $usage, 300); // 5分钟缓存
        }
        
        return $usage;
    }
    
    public function getConfig($configId) {
        $cacheKey = "config_{$configId}";
        
        $config = $this->cache->get($cacheKey);
        if ($config === null) {
            $query = "SELECT * FROM mod_frp_user_configs WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $configId]);
            
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($config) {
                $this->cache->set($cacheKey, $config, 300);
            }
        }
        
        return $config;
    }
    
    public function getDailyStats($configId, $days = 30) {
        $query = "SELECT date, 
                        SUM(bytes_in) as bytes_in, 
                        SUM(bytes_out) as bytes_out
                 FROM mod_frp_usage_stats 
                 WHERE config_id = :config_id 
                 AND date >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
                 GROUP BY date
                 ORDER BY date DESC";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':config_id' => $configId,
            ':days' => $days
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkAndNotifyLimit($configId) {
        $config = $this->getConfig($configId);
        $usage = $this->getCurrentUsage($configId);
        $limit = $config['bandwidth_limit'];
        
        $thresholds = [
            90 => '您的带宽使用量已达到限制的90%',
            75 => '您的带宽使用量已达到限制的75%',
            50 => '您的带宽使用量已达到限制的50%'
        ];
        
        foreach ($thresholds as $percentage => $message) {
            if ($usage >= ($limit * $percentage / 100)) {
                $this->sendNotification($config['client_id'], $message);
                break;
            }
        }
    }
    
    private function sendNotification($clientId, $message) {
        // 集成WHMCS通知系统
        $command = 'SendEmail';
        $values = [
            'messagename' => 'FRP Bandwidth Alert',
            'id' => $clientId,
            'customsubject' => 'FRP带宽使用提醒',
            'custommessage' => $message,
        ];
        
        localAPI($command, $values);
    }
}
