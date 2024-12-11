<?php

class FrpLogger {
    private $db;
    private $logTable = 'mod_frp_logs';
    
    public function __construct($db) {
        $this->db = $db;
        $this->initLogTable();
    }
    
    private function initLogTable() {
        $query = "CREATE TABLE IF NOT EXISTS `{$this->logTable}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `service_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `action` varchar(50) NOT NULL,
            `description` text NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `status` varchar(20) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `service_id` (`service_id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($query);
    }
    
    public function log($serviceId, $userId, $action, $description, $status = 'success') {
        $query = "INSERT INTO {$this->logTable} 
                 (service_id, user_id, action, description, ip_address, status) 
                 VALUES (:service_id, :user_id, :action, :description, :ip_address, :status)";
                 
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':service_id' => $serviceId,
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $this->getClientIP(),
            ':status' => $status
        ]);
    }
    
    public function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return 'unknown';
    }
    
    public function getLogs($serviceId, $limit = 100) {
        $query = "SELECT * FROM {$this->logTable} 
                 WHERE service_id = :service_id 
                 ORDER BY created_at DESC 
                 LIMIT :limit";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':service_id', $serviceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAuditLogs($userId, $limit = 100) {
        $query = "SELECT l.*, s.domain as service_name 
                 FROM {$this->logTable} l
                 JOIN tblhosting s ON l.service_id = s.id
                 WHERE l.user_id = :user_id 
                 ORDER BY l.created_at DESC 
                 LIMIT :limit";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
