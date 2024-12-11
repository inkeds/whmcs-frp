<?php

class FrpCache {
    private $cacheDir;
    private $defaultTTL = 300; // 5分钟默认缓存时间
    
    public function __construct($baseDir) {
        $this->cacheDir = $baseDir . '/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $filename = $this->getCacheFile($key);
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        
        if (!$data || $data['expires'] < time()) {
            @unlink($filename);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $filename = $this->getCacheFile($key);
        return file_put_contents($filename, json_encode($data));
    }
    
    public function delete($key) {
        $filename = $this->getCacheFile($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
