<?php

namespace Core\Services;

use Core\Services\DatabaseAdapter;

class ConnectionManager
{
    private static $instance = null;
    private static $connections = [];
    private static $lastUsed = [];
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection($type = null)
    {
        $type = $type ?: $_ENV['DB_TYPE'] ?? 'mysql';
        $cacheKey = $type . '_primary';
        
        // Check if connection exists and is alive
        if (isset(self::$connections[$cacheKey])) {
            $connection = self::$connections[$cacheKey];
            if ($connection->isConnected()) {
                self::$lastUsed[$cacheKey] = time();
                return $connection;
            } else {
                // Remove dead connection
                unset(self::$connections[$cacheKey]);
            }
        }
        
        // Create new connection
        self::$connections[$cacheKey] = DatabaseAdapter::create($type);
        self::$lastUsed[$cacheKey] = time();
        
        return self::$connections[$cacheKey];
    }
    
    public function closeIdleConnections($maxIdleTime = 300)
    {
        $currentTime = time();
        foreach (self::$lastUsed as $key => $lastUsed) {
            if ($currentTime - $lastUsed > $maxIdleTime) {
                if (isset(self::$connections[$key])) {
                    self::$connections[$key]->close();
                    unset(self::$connections[$key]);
                    unset(self::$lastUsed[$key]);
                }
            }
        }
    }
    
    public function closeAllConnections()
    {
        foreach (self::$connections as $connection) {
            $connection->close();
        }
        self::$connections = [];
        self::$lastUsed = [];
    }
    
    public function getConnectionCount()
    {
        return count(self::$connections);
    }
    
    public function getConnectionStats()
    {
        $stats = [];
        foreach (self::$connections as $key => $connection) {
            $stats[$key] = [
                'connected' => $connection->isConnected(),
                'last_used' => self::$lastUsed[$key] ?? 0,
                'idle_time' => time() - (self::$lastUsed[$key] ?? 0)
            ];
        }
        return $stats;
    }
}