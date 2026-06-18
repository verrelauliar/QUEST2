<?php

namespace Core\Services;

use Core\Interfaces\DatabaseInterface;
use Core\Factories\DatabaseFactory;
use Core\Adapters\MySQLAdapter;
use Core\Adapters\SupabaseAdapter;

class DatabaseAdapter implements DatabaseInterface
{
    private $adapter;
    private $dbType;
    private $config;
    private $useFallback;
    private $fallbackType;

    public function __construct($type = null, $config = [], $useFallback = false, $fallbackType = null)
    {
        $this->config = $config;
        $this->useFallback = $useFallback;
        $this->fallbackType = $fallbackType ?: ($type === 'mysql' ? 'supabase' : 'mysql');

        try {
            if ($useFallback) {
                $this->adapter = DatabaseFactory::createWithFallback($type, $this->fallbackType, $config);
                $this->dbType = $this->adapter instanceof MySQLAdapter ? 'mysql' : 'supabase';
            } else {
                $this->adapter = DatabaseFactory::create($type, $config);
                $this->dbType = DatabaseFactory::getCurrentDbType();
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize database adapter: " . $e->getMessage());
        }
    }

    public static function create($type = null, $config = [], $useFallback = false, $fallbackType = null)
    {
        return new self($type, $config, $useFallback, $fallbackType);
    }

    public static function createMySQL($config = [])
    {
        return new self('mysql', $config);
    }

    public static function createSupabase($config = [])
    {
        return new self('supabase', $config);
    }

    public static function createWithFallback($primaryType, $fallbackType, $config = [])
    {
        return new self($primaryType, $config, true, $fallbackType);
    }

    public function getDbType()
    {
        return $this->dbType;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function switchAdapter($type, $config = [])
    {
        try {
            $newConfig = array_merge($this->config, $config);
            $newAdapter = DatabaseFactory::create($type, $newConfig);

            if ($newAdapter && $newAdapter->isConnected()) {
                $this->adapter = $newAdapter;
                $this->dbType = $type;
                return true;
            }
        } catch (\Exception $e) {
            error_log("Failed to switch to {$type} adapter: " . $e->getMessage());
        }

        return false;
    }

    public function query($query, $params = [])
    {
        return $this->adapter->query($query, $params);
    }

    public function fetchAll($result = null)
    {
        return $this->adapter->fetchAll($result);
    }

    public function fetchAssoc($result = null)
    {
        return $this->adapter->fetchAssoc($result);
    }

    public function fetchRow($result = null)
    {
        return $this->adapter->fetchRow($result);
    }

    public function affectedRows()
    {
        return $this->adapter->affectedRows();
    }

    public function lastInsertId()
    {
        return $this->adapter->lastInsertId();
    }

    public function escape($string)
    {
        return $this->adapter->escape($string);
    }

    public function prepare($query)
    {
        return $this->adapter->prepare($query);
    }

    public function executePrepared($stmt, $params = [])
    {
        return $this->adapter->executePrepared($stmt, $params);
    }

    public function beginTransaction()
    {
        return $this->adapter->beginTransaction();
    }

    public function commit()
    {
        return $this->adapter->commit();
    }

    public function rollback()
    {
        return $this->adapter->rollback();
    }

    public function inTransaction()
    {
        return $this->adapter->inTransaction();
    }

    public function getError()
    {
        return $this->adapter->getError();
    }

    public function getErrorCode()
    {
        return $this->adapter->getErrorCode();
    }

    public function insert($table, $data)
    {
        return $this->adapter->insert($table, $data);
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        return $this->adapter->update($table, $data, $where, $whereParams);
    }

    public function delete($table, $where, $whereParams = [])
    {
        return $this->adapter->delete($table, $where, $whereParams);
    }

    public function count($table, $where = '1=1', $whereParams = [])
    {
        return $this->adapter->count($table, $where, $whereParams);
    }

    public function rpc($function, $params = [])
    {
        return $this->adapter->rpc($function, $params);
    }

    public function getConnection()
    {
        return $this->adapter->getConnection();
    }

    public function close()
    {
        return $this->adapter->close();
    }

    public function isConnected()
    {
        return $this->adapter->isConnected();
    }

    public function reconnect()
    {
        return $this->adapter->reconnect();
    }

    public function getDatabaseInfo()
    {
        return $this->adapter->getDatabaseInfo();
    }

    public function getPerformanceMetrics()
    {
        $metrics = [
            'db_type' => $this->dbType,
            'connected' => $this->isConnected(),
            'in_transaction' => $this->inTransaction(),
            'last_error' => $this->getError(),
            'last_error_code' => $this->getErrorCode(),
            'database_info' => $this->getDatabaseInfo()
        ];

        if ($this->adapter instanceof MySQLAdapter) {
            $connection = $this->adapter->getConnection();
            if ($connection) {
                $metrics['connection_info'] = $connection->host_info;
                $metrics['thread_id'] = $connection->thread_id;
            }
        } elseif ($this->adapter instanceof SupabaseAdapter) {
            $metrics['adapter_type'] = 'Supabase';
            $metrics['supports_rpc'] = true;
        }

        return $metrics;
    }

    public function testConnection()
    {
        $result = [
            'success' => false,
            'db_type' => $this->dbType,
            'error' => null,
            'execution_time' => 0
        ];

        $startTime = microtime(true);

        try {
            if ($this->isConnected()) {
                $testResult = $this->query('SELECT 1 as test');
                $testData = $this->fetchAssoc($testResult);

                if ($testData && isset($testData['test']) && $testData['test'] == 1) {
                    $result['success'] = true;
                } else {
                    $result['error'] = 'Connection test query failed';
                }
            } else {
                $result['error'] = 'Not connected to database';
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $result['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    public function getAvailableDatabaseTypes()
    {
        return DatabaseFactory::getAvailableDbTypes();
    }

    public function clearCache($type = null)
    {
        DatabaseFactory::clearCache($type);
    }

    public function getCachedConnectionCount($type = null)
    {
        return DatabaseFactory::getCachedConnectionCount($type);
    }
}