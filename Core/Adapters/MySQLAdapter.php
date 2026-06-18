<?php

namespace Core\Adapters;

use Core\Interfaces\DatabaseInterface;
use mysqli;
use mysqli_result;
use mysqli_stmt;

class MySQLAdapter implements DatabaseInterface
{
    private $connection;
    private $lastResult;
    private $lastError = '';
    private $lastErrorCode = 0;
    private $inTransaction = false;
    private $config;

    /**
     * Constructor
     * 
     * @param array $config Database connection parameters
     */
    public function __construct($config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'user' => 'root',
            'pass' => '',
            'db' => 'mumtaza_ujian',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ], $config);

        $this->connect();
    }

    private function connect()
    {
        try {
            if (!isset($this->config['port']) || $this->config['port'] === null) {
                $default_ports = [3306, 3305];
                $port = null;

                foreach ($default_ports as $p) {
                    $sock = @fsockopen($this->config['host'], $p, $errno, $errstr, 1.0);
                    if ($sock) {
                        $port = $p;
                        fclose($sock);
                        break;
                    }
                }

                if ($port === null) {
                    error_log("No active MySQL port detected (tried 3306 and 3305). Using default port 3306 as fallback.");
                    $port = 3306;
                }

                $this->config['port'] = $port;
            }

            error_log("MySQLAdapter: Attempting to connect to MySQL at {$this->config['host']}:{$this->config['port']}, database: {$this->config['db']}");

            $this->connection = @mysqli_connect(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['db'],
                $this->config['port']
            );

            if (!$this->connection) {
                error_log("MySQLAdapter: Primary connection failed, trying alternative configuration");
                $this->connection = @mysqli_connect(
                    $this->config['host'],
                    $this->config['user'],
                    $this->config['pass'],
                    $this->config['db']
                );

                if (!$this->connection) {
                    $this->lastError = mysqli_connect_error();
                    $this->lastErrorCode = mysqli_connect_errno();
                    error_log("MySQLAdapter: Alternative database connection also failed: " . $this->lastError);
                    return false;
                }
            }

            if (!mysqli_set_charset($this->connection, $this->config['charset'])) {
                error_log("MySQLAdapter: Error setting character set: " . $this->connection->error);
            }

            mysqli_query($this->connection, "SET time_zone = '+07:00'");

            error_log("MySQLAdapter: Successfully connected to database with host info: " . $this->connection->host_info);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            error_log("MySQLAdapter: Connection exception: " . $this->lastError);
            return false;
        }
    }

    public function query($query, $params = [])
    {
        if (!$this->isConnected()) {
            throw new \Exception("Database connection is not active");
        }

        $this->lastError = '';
        $this->lastErrorCode = 0;

        if (empty($params)) {
            $this->lastResult = mysqli_query($this->connection, $query);
            if ($this->lastResult === false) {
                $this->lastError = mysqli_error($this->connection);
                $this->lastErrorCode = mysqli_errno($this->connection);
                throw new \Exception("Query failed: " . $this->lastError);
            }
        } else {
            $stmt = $this->prepare($query);
            $this->lastResult = $this->executePrepared($stmt, $params);
        }

        return $this->lastResult;
    }

    public function fetchAll($result = null)
    {
        $resultSet = $result ?: $this->lastResult;

        if (!$resultSet) {
            return [];
        }

        if ($resultSet instanceof mysqli_result) {
            return mysqli_fetch_all($resultSet, MYSQLI_ASSOC);
        }

        return [];
    }

    public function fetchAssoc($result = null)
    {
        $resultSet = $result ?: $this->lastResult;

        if (!$resultSet) {
            return null;
        }

        if ($resultSet instanceof mysqli_result) {
            return mysqli_fetch_assoc($resultSet);
        }

        return null;
    }

    public function fetchRow($result = null)
    {
        $resultSet = $result ?: $this->lastResult;

        if (!$resultSet) {
            return null;
        }

        if ($resultSet instanceof mysqli_result) {
            return mysqli_fetch_row($resultSet);
        }

        return null;
    }

    public function affectedRows()
    {
        return mysqli_affected_rows($this->connection);
    }

    public function lastInsertId()
    {
        return mysqli_insert_id($this->connection);
    }

    public function escape($string)
    {
        return mysqli_real_escape_string($this->connection, $string);
    }

    public function prepare($query)
    {
        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) {
            $this->lastError = mysqli_error($this->connection);
            $this->lastErrorCode = mysqli_errno($this->connection);
        }
        return $stmt;
    }

    public function executePrepared($stmt, $params = [])
    {
        if (!empty($params)) {
            $types = '';
            $bindParams = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                $bindParams[] = $param;
            }

            array_unshift($bindParams, $types);

            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }

        $result = mysqli_stmt_execute($stmt);

        if (!$result) {
            $this->lastError = mysqli_stmt_error($stmt);
            $this->lastErrorCode = mysqli_stmt_errno($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            return $stmt;
        }

        return $result;
    }

    private function refValues($arr)
    {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    public function beginTransaction()
    {
        if ($this->inTransaction) {
            return false;
        }

        $result = mysqli_begin_transaction($this->connection);
        if ($result) {
            $this->inTransaction = true;
        }
        return $result;
    }

    public function commit()
    {
        if (!$this->inTransaction) {
            return false;
        }

        $result = mysqli_commit($this->connection);
        $this->inTransaction = false;
        return $result;
    }

    public function rollback()
    {
        if (!$this->inTransaction) {
            return false;
        }

        $result = mysqli_rollback($this->connection);
        $this->inTransaction = false;
        return $result;
    }

    public function inTransaction()
    {
        return $this->inTransaction;
    }

    public function getError()
    {
        return $this->lastError;
    }

    public function getErrorCode()
    {
        return $this->lastErrorCode;
    }

    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

        $this->query($sql, array_values($data));
        return $this->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $setClauses) . " WHERE {$where}";

        $allParams = array_merge($params, $whereParams);
        $this->query($sql, $allParams);
        return $this->affectedRows();
    }

    public function delete($table, $where, $whereParams = [])
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $this->query($sql, $whereParams);
        return $this->affectedRows();
    }

    public function count($table, $where = '1=1', $whereParams = [])
    {
        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE {$where}";
        $result = $this->query($sql, $whereParams);
        $row = $this->fetchAssoc($result);
        return (int)$row['count'];
    }

    public function rpc($function, $params = [])
    {
        $paramPlaceholders = empty($params) ? '' : "'" . implode("','", array_map([$this, 'escape'], $params)) . "'";
        $sql = "CALL {$function}({$paramPlaceholders})";

        $result = $this->query($sql);
        return $this->fetchAll($result);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function close()
    {
        if ($this->connection) {
            return mysqli_close($this->connection);
        }
        return true;
    }

    public function isConnected()
    {
        return $this->connection && mysqli_ping($this->connection);
    }

    public function reconnect()
    {
        $this->close();
        return $this->connect();
    }

    public function getDatabaseInfo()
    {
        $result = $this->query("SELECT VERSION() as version");
        $row = $this->fetchAssoc($result);
        return "MySQL " . $row['version'];
    }
}