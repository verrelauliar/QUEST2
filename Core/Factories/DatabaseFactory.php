<?php

namespace Core\Factories;

use Core\Interfaces\DatabaseInterface;
use Core\Adapters\MySQLAdapter;
use Core\Adapters\SupabaseAdapter;
use Dotenv\Dotenv;

class DatabaseFactory
{
    private static $connections = [];
    private static $defaultConfig = [
        'mysql' => [
            'host' => '127.0.0.1',
            'user' => 'root',
            'pass' => '',
            'db' => 'mumtaza_ujian',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ],
        'supabase' => [
            'url' => null,
            'key' => null,
            'reference_id' => null
        ]
    ];

    public static function create($type = null, $config = [])
    {
        if (!isset($_ENV['DB_TYPE'])) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->safeLoad();
        }

        $dbType = $type ?: $_ENV['DB_TYPE'] ?? null;

        if ($dbType === null) {
            if (!empty($_ENV['SUPABASE_PROJECT_URL']) && !empty($_ENV['SUPABASE_SECRET_KEY'])) {
                $dbType = 'supabase';
                error_log("DatabaseFactory: Auto-detected Supabase configuration, using 'supabase' adapter");
            } elseif (!empty($_ENV['DB_HOST']) || !empty($_ENV['DB_NAME'])) {
                $dbType = 'mysql';
                error_log("DatabaseFactory: Auto-detected MySQL configuration, using 'mysql' adapter");
            } else {
                $dbType = 'mysql';
                error_log("DatabaseFactory: No configuration detected, defaulting to 'mysql' adapter");
            }
        }

        if (!in_array(strtolower($dbType), ['mysql', 'supabase'])) {
            throw new \Exception("Unsupported database type: {$dbType}. Supported types: mysql, supabase");
        }

        $cacheKey = $dbType . '_' . md5(serialize($config));

        if (isset(self::$connections[$cacheKey]) && self::$connections[$cacheKey]->isConnected()) {
            return self::$connections[$cacheKey];
        }

        $fullConfig = array_merge(self::$defaultConfig[$dbType] ?? [], $config);

        if ($dbType === 'mysql') {
            $fullConfig = array_merge($fullConfig, [
                'host' => $_ENV['DB_HOST'] ?? $fullConfig['host'],
                'user' => $_ENV['DB_USER'] ?? $fullConfig['user'],
                'pass' => $_ENV['DB_PASS'] ?? $fullConfig['pass'],
                'db' => $_ENV['DB_NAME'] ?? $fullConfig['db'],
                'port' => $_ENV['DB_PORT'] ?? $fullConfig['port'],
                'charset' => $_ENV['DB_CHARSET'] ?? $fullConfig['charset']
            ]);
        } elseif ($dbType === 'supabase') {
            $fullConfig = array_merge($fullConfig, [
                'url' => $_ENV['SUPABASE_PROJECT_URL'] ?? $fullConfig['url'],
                'key' => $_ENV['SUPABASE_SECRET_KEY'] ?? $fullConfig['key'],
                'reference_id' => $_ENV['SUPABASE_REFERENCE_ID'] ?? $fullConfig['reference_id']
            ]);
        }

        $adapter = null;
        switch (strtolower($dbType)) {
            case 'mysql':
                $adapter = new MySQLAdapter($fullConfig);
                break;

            case 'supabase':
                $adapter = new SupabaseAdapter($fullConfig);
                break;

            default:
                throw new \Exception("Unsupported database type: {$dbType}. Supported types: mysql, supabase");
        }

        self::$connections[$cacheKey] = $adapter;

        return $adapter;
    }

    public static function createMySQL($config = [])
    {
        return self::create('mysql', $config);
    }

    public static function createSupabase($config = [])
    {
        return self::create('supabase', $config);
    }

    public static function getCurrentDbType()
    {
        if (!isset($_ENV['DB_TYPE'])) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->safeLoad();
        }

        return $_ENV['DB_TYPE'] ?? 'mysql';
    }

    public static function isDbTypeAvailable($type)
    {
        try {
            $adapter = self::create($type);
            return $adapter && $adapter->isConnected();
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getAvailableDbTypes()
    {
        $available = [];
        $types = ['mysql', 'supabase'];

        foreach ($types as $type) {
            $available[$type] = [
                'available' => false,
                'info' => null,
                'error' => null
            ];

            try {
                $adapter = self::create($type);
                if ($adapter && $adapter->isConnected()) {
                    $available[$type]['available'] = true;
                    $available[$type]['info'] = $adapter->getDatabaseInfo();
                }
            } catch (\Exception $e) {
                $available[$type]['error'] = $e->getMessage();
            }
        }

        return $available;
    }

    public static function clearCache($type = null)
    {
        if ($type) {
            foreach (self::$connections as $key => $connection) {
                if (strpos($key, $type . '_') === 0) {
                    $connection->close();
                    unset(self::$connections[$key]);
                }
            }
        } else {
            foreach (self::$connections as $connection) {
                $connection->close();
            }
            self::$connections = [];
        }
    }

    public static function getCachedConnectionCount($type = null)
    {
        if ($type) {
            $count = 0;
            foreach (self::$connections as $key => $connection) {
                if (strpos($key, $type . '_') === 0) {
                    $count++;
                }
            }
            return $count;
        }

        return count(self::$connections);
    }

    public static function testConnection($type, $config = [])
    {
        $result = [
            'success' => false,
            'info' => null,
            'error' => null,
            'execution_time' => 0
        ];

        $startTime = microtime(true);

        try {
            $adapter = self::create($type, $config);

            if ($adapter && $adapter->isConnected()) {
                $result['success'] = true;
                $result['info'] = $adapter->getDatabaseInfo();
            } else {
                $result['error'] = 'Failed to establish connection';
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $result['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    public static function createWithFallback($primaryType, $fallbackType, $config = [])
    {
        try {
            return self::create($primaryType, $config);
        } catch (\Exception $e) {
            error_log("Primary database connection failed: " . $e->getMessage());
            error_log("Attempting fallback to {$fallbackType}");

            try {
                return self::create($fallbackType, $config);
            } catch (\Exception $fallbackException) {
                throw new \Exception(
                    "Both primary ({$primaryType}) and fallback ({$fallbackType}) database connections failed. " .
                    "Primary error: " . $e->getMessage() . ". " .
                    "Fallback error: " . $fallbackException->getMessage()
                );
            }
        }
    }

    public static function getConfigTemplate($type)
    {
        $templates = [
            'mysql' => [
                'DB_TYPE' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_NAME' => 'mumtaza_ujian',
                'DB_PORT' => '3306',
                'DB_CHARSET' => 'utf8mb4'
            ],
            'supabase' => [
                'DB_TYPE' => 'supabase',
                'SUPABASE_PROJECT_URL' => 'https://your-project.supabase.co',
                'SUPABASE_SECRET_KEY' => 'your-secret-key',
                'SUPABASE_PUBLISHABLE_KEY' => 'your-publishable-key',
                'SUPABASE_REFERENCE_ID' => 'your-project-id'
            ]
        ];

        return $templates[$type] ?? [];
    }
}