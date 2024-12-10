<?php

class FrpMiddleware {
    private $db;
    private $cache;
    private $rateLimit = 60; // 每分钟请求次数限制
    private $rateLimitWindow = 60; // 时间窗口（秒）
    
    public function __construct($db, $cache) {
        $this->db = $db;
        $this->cache = $cache;
    }
    
    public function authenticate($token) {
        // 验证API token
        $cacheKey = 'auth_token_' . md5($token);
        $userData = $this->cache->get($cacheKey);
        
        if (!$userData) {
            $query = "SELECT * FROM mod_frp_user_configs WHERE token = :token";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':token' => $token]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $this->cache->set($cacheKey, $userData, 300);
            }
        }
        
        return $userData;
    }
    
    public function checkRateLimit($userId, $action) {
        $cacheKey = "rate_limit_{$userId}_{$action}";
        $requests = $this->cache->get($cacheKey) ?? [];
        
        // 清理过期的请求记录
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return $timestamp > ($now - $this->rateLimitWindow);
        });
        
        // 检查是否超过限制
        if (count($requests) >= $this->rateLimit) {
            return false;
        }
        
        // 记录新的请求
        $requests[] = $now;
        $this->cache->set($cacheKey, $requests, $this->rateLimitWindow);
        
        return true;
    }
    
    public function validateIP($ip, $allowedIPs) {
        if (empty($allowedIPs)) {
            return true;
        }
        
        foreach ($allowedIPs as $allowedIP) {
            if ($this->ipInRange($ip, $allowedIP)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') !== false) {
            // CIDR格式
            list($subnet, $bits) = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        } else {
            // 单个IP
            return $ip === $range;
        }
    }
    
    public function validateRequest($request) {
        // 基本请求验证
        $requiredFields = ['token', 'action'];
        foreach ($requiredFields as $field) {
            if (!isset($request[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }
        
        // 验证token
        $userData = $this->authenticate($request['token']);
        if (!$userData) {
            return [
                'valid' => false,
                'error' => 'Invalid token'
            ];
        }
        
        // 检查速率限制
        if (!$this->checkRateLimit($userData['id'], $request['action'])) {
            return [
                'valid' => false,
                'error' => 'Rate limit exceeded'
            ];
        }
        
        return [
            'valid' => true,
            'user' => $userData
        ];
    }
}
