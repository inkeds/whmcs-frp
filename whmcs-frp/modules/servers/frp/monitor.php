<?php

class FrpMonitor {
    private $db;
    private $apiUrl;
    
    public function __construct($db, $apiUrl) {
        $this->db = $db;
        $this->apiUrl = $apiUrl;
    }
    
    public function collectStats() {
        // 获取所有活跃的FRP配置
        $configs = $this->getActiveConfigs();
        
        foreach ($configs as $config) {
            // 获取每个配置的使用统计
            $stats = $this->getFrpStats($config['id']);
            
            // 更新数据库
            $this->updateStats($config['id'], $stats);
        }
    }
    
    private function getActiveConfigs() {
        $query = "SELECT * FROM mod_frp_user_configs 
                 WHERE service_id IN (
                     SELECT id FROM tblhosting 
                     WHERE domainstatus = 'Active'
                 )";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getFrpStats($configId) {
        // 从FRP API获取统计数据
        $url = $this->apiUrl . "/api/stats";
        $token = $this->getConfigToken($configId);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function updateStats($configId, $stats) {
        $date = date('Y-m-d');
        
        // 更新总体统计
        $query = "INSERT INTO mod_frp_usage_stats 
                 (config_id, bytes_in, bytes_out, connections, date) 
                 VALUES (:config_id, :bytes_in, :bytes_out, :connections, :date)
                 ON DUPLICATE KEY UPDATE 
                 bytes_in = :bytes_in,
                 bytes_out = :bytes_out,
                 connections = :connections";
                 
        $this->db->prepare($query)->execute([
            ':config_id' => $configId,
            ':bytes_in' => $stats['bytes_in'],
            ':bytes_out' => $stats['bytes_out'],
            ':connections' => $stats['connections'],
            ':date' => $date
        ]);
        
        // 更新每个隧道的统计
        foreach ($stats['tunnels'] as $tunnel) {
            $this->updateTunnelStats($configId, $tunnel);
        }
    }
    
    private function updateTunnelStats($configId, $tunnelStats) {
        $date = date('Y-m-d');
        
        $query = "INSERT INTO mod_frp_usage_stats 
                 (config_id, tunnel_id, bytes_in, bytes_out, connections, date) 
                 VALUES (:config_id, :tunnel_id, :bytes_in, :bytes_out, :connections, :date)
                 ON DUPLICATE KEY UPDATE 
                 bytes_in = :bytes_in,
                 bytes_out = :bytes_out,
                 connections = :connections";
                 
        $this->db->prepare($query)->execute([
            ':config_id' => $configId,
            ':tunnel_id' => $tunnelStats['id'],
            ':bytes_in' => $tunnelStats['bytes_in'],
            ':bytes_out' => $tunnelStats['bytes_out'],
            ':connections' => $tunnelStats['connections'],
            ':date' => $date
        ]);
    }
    
    public function checkStatus($configId) {
        // 检查FRP实例状态
        $url = $this->apiUrl . "/api/status";
        $token = $this->getConfigToken($configId);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function getConfigToken($configId) {
        $query = "SELECT token FROM mod_frp_user_configs WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $configId]);
        return $stmt->fetchColumn();
    }
}
