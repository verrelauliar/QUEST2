<?php

namespace Core\Adapters;

use Core\Interfaces\DatabaseInterface;
use Supabase\CreateClient;
use Dotenv\Dotenv;

class SupabaseAdapter implements DatabaseInterface
{
    private $client;
    private $lastResult = [];
    private $lastError = '';
    private $lastErrorCode = 0;
    private $inTransaction = false;
    private $config;
    private $authToken = null;

    private function extractResponseData($response)
    {
        if (is_object($response) && property_exists($response, 'data')) {
            if (isset($response->data['code']) && $response->data['code'] === 'PGRST204') {
                $this->lastError = $response->data['message'] ?? 'Unknown error';
                $this->lastErrorCode = 404;
                return [];
            }

            if (property_exists($response, 'status') && $response->status === 201) {
                return $response->data ?? [];
            }

            return $response->data ?? [];
        }

        if (is_array($response)) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'getData')) {
            return $response->getData() ?? [];
        }

        if (is_object($response)) {
            $responseArray = json_decode(json_encode($response), true);
            if (isset($responseArray['data'])) {
                return $responseArray['data'];
            }
            if (!empty($responseArray) && !isset($responseArray['status'])) {
                return $responseArray;
            }
        }

        return [];
    }

    private function extractResponseCount($response)
    {
        if (is_object($response) && property_exists($response, 'count')) {
            return $response->count ?? 0;
        }

        if (is_object($response) && method_exists($response, 'getCount')) {
            return $response->getCount() ?? 0;
        }

        return 0;
    }

    public function __construct($config = [])
    {
        $this->config = array_merge([
            'url' => null,
            'key' => null,
            'reference_id' => null
        ], $config);

        $this->connect();
    }

    private function connect()
    {
        try {
            if (!isset($_ENV['SUPABASE_PROJECT_URL']) || !isset($_ENV['SUPABASE_SECRET_KEY'])) {
                $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
                $dotenv->safeLoad();
            }

            $url = $this->config['url'] ?? $_ENV['SUPABASE_PROJECT_URL'] ?? null;
            $key = $this->config['key'] ?? $_ENV['SUPABASE_SECRET_KEY'] ?? null;
            $referenceId = $this->config['reference_id'] ?? null;

            if (!$url || !$key) {
                throw new \Exception('Supabase URL and key are required');
            }

            if (!$referenceId) {
                $referenceId = parse_url($url, PHP_URL_HOST);
                $referenceId = explode('.', $referenceId)[0];
            }

            error_log("SupabaseAdapter: Attempting to connect to Supabase at {$url}");

            $this->client = new CreateClient($key, $referenceId);

            $this->getDatabaseInfo();

            error_log("SupabaseAdapter: Successfully connected to Supabase");
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            error_log("SupabaseAdapter: Connection exception: " . $this->lastError);
            return false;
        }
    }

    public function setAuthToken($token)
    {
        $this->authToken = $token;
    }

    /**
     * Execute a query and return the result
     * 
     * @param string $query The SQL query to execute
     * @param array $params Optional parameters for prepared statements
     * @return array The result set
     * @throws \Exception If query execution fails
     */
    public function query($query, $params = [])
    {
        if (!$this->isConnected()) {
            throw new \Exception("Database connection is not active");
        }

        $this->lastError = '';
        $this->lastErrorCode = 0;

        try {
            // For Supabase, we need to use RPC for complex queries
            // or direct table operations for simple CRUD
            if (stripos(trim($query), 'SELECT') === 0) {
                return $this->executeSelect($query, $params);
            } elseif (stripos(trim($query), 'INSERT') === 0) {
                return $this->executeInsert($query, $params);
            } elseif (stripos(trim($query), 'UPDATE') === 0) {
                return $this->executeUpdate($query, $params);
            } elseif (stripos(trim($query), 'DELETE') === 0) {
                return $this->executeDelete($query, $params);
            } else {
                // For other queries, use RPC
                return $this->executeGenericQuery($query, $params);
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            throw new \Exception("Query failed: " . $this->lastError);
        }
    }

    /**
     * Execute a SELECT query
     * 
     * @param string $query The SELECT query
     * @param array $params Query parameters
     * @return array The result set
     */
    private function executeSelect($query, $params = [])
    {
        // Parse table name from query
        preg_match('/FROM\s+([`\"]?)(\w+)\1/i', $query, $matches);
        if (!isset($matches[2])) {
            // If we can't parse the table name, try a different approach
            // For simple queries like "SELECT 1 as test", just execute directly
            if (stripos(trim($query), 'SELECT 1') === 0) {
                $result = $this->client->rpc('execute_query', [
                    'query' => $query,
                    'params' => $params
                ])->execute();
                $this->lastResult = $this->extractResponseData($result);
                return $this->lastResult;
            }
            // For COUNT queries, try a different approach
            if (stripos(trim($query), 'SELECT COUNT') === 0) {
                preg_match('/FROM\s+([`\"]?)(\w+)\1/i', $query, $countMatches);
                if (isset($countMatches[2])) {
                    $table = $countMatches[2];
                    $result = $this->client->from($table)->select('*', ['count' => 'exact'])->execute();
                    $this->lastResult = $this->extractResponseData($result);
                    return $this->lastResult;
                }
            }
            throw new \Exception("Unable to parse table name from SELECT query");
        }
        
        $table = $matches[2];
        
        // Parse WHERE clause
        $where = $this->parseWhereClause($query, $params);
        
        // Parse ORDER BY
        $orderBy = $this->parseOrderBy($query);
        
        // Parse LIMIT
        $limit = $this->parseLimit($query);

        // Use RPC for complex queries since the basic client doesn't support
        // advanced query features like ORDER BY, LIMIT, etc.
        try {
            $result = $this->client->rpc('execute_query', [
                'query' => $query,
                'params' => $params
            ])->execute();
            
            $this->lastResult = $this->extractResponseData($result);
            return $this->lastResult;
        } catch (\Exception $e) {
            // If RPC fails, try a simple query without ORDER BY and LIMIT
            try {
                $simpleQuery = "SELECT * FROM {$table}";
                $result = $this->client->from($table)->select('*')->execute();
                $this->lastResult = $this->extractResponseData($result);
                
                // Apply LIMIT in PHP if needed
                if ($limit !== null && count($this->lastResult) > $limit) {
                    $this->lastResult = array_slice($this->lastResult, 0, $limit);
                }
                
                return $this->lastResult;
            } catch (\Exception $e2) {
                throw new \Exception("Both RPC and direct query failed: " . $e->getMessage() . " | " . $e2->getMessage());
            }
        }
    }

    /**
     * Execute an INSERT query
     *
     * @param string $query The INSERT query
     * @param array $params Query parameters
     * @return array The result set
     */
    private function executeInsert($query, $params = [])
    {
        // Parse table name and data from INSERT query
        preg_match('/INSERT\s+INTO\s+([`\"]?)(\w+)\1\s*\(([^)]+)\)\s*VALUES\s+(.+)/i', $query, $matches);
        
        if (!isset($matches[2])) {
            throw new \Exception("Unable to parse INSERT query");
        }

        $table = $matches[2];
        $columns = array_map('trim', explode(',', $matches[3]));
        $valuesSection = $matches[4];

        // Parse multiple value rows
        $valueRows = [];
        preg_match_all('/\(([^)]+)\)/', $valuesSection, $valueRows);
        
        if (empty($valueRows[1])) {
            throw new \Exception("Unable to parse VALUES section of INSERT query");
        }

        // Build data array for each row
        $dataRows = [];
        foreach ($valueRows[1] as $rowIndex => $valueRow) {
            $values = array_map('trim', explode(',', $valueRow));
            $data = [];
            
            foreach ($columns as $colIndex => $column) {
                $column = trim($column, '`"');
                
                if (isset($values[$colIndex])) {
                    $value = trim($values[$colIndex], "'\"");
                    
                    // Replace placeholders with actual parameters
                    if ($value === '?' && isset($params[$colIndex])) {
                        $data[$column] = $params[$colIndex];
                    } else {
                        $data[$column] = $value;
                    }
                } else {
                    $data[$column] = null;
                }
            }
            
            $dataRows[] = $data;
        }

        // Execute insert using Supabase client
        $result = $this->client->from($table)->insert($dataRows)->execute();
        $this->lastResult = $this->extractResponseData($result);
        return $this->lastResult;
    }

    /**
     * Execute an UPDATE query
     * 
     * @param string $query The UPDATE query
     * @param array $params Query parameters
     * @return array The result set
     */
    private function executeUpdate($query, $params = [])
    {
        // Parse table name from UPDATE query
        preg_match('/UPDATE\s+([`\"]?)(\w+)\1\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/is', $query, $matches);
        
        if (!isset($matches[2])) {
            throw new \Exception("Unable to parse UPDATE query");
        }

        $table = $matches[2];
        $setClause = $matches[3];
        $whereClause = $matches[4] ?? null;

        // Parse SET clause
        $setData = $this->parseSetClause($setClause, $params);

        // Parse WHERE clause
        $where = $this->parseWhereClause($whereClause ? "WHERE {$whereClause}" : '', $params);

        // Execute update using Supabase client
        $queryBuilder = $this->client->from($table)->update($setData);
        
        if (!empty($where['clause'])) {
            $queryBuilder = $queryBuilder->filter($where['clause'], $where['params']);
        }

        $result = $queryBuilder->execute();
        $this->lastResult = $this->extractResponseData($result);
        return $this->lastResult;
    }

    /**
     * Execute a DELETE query
     * 
     * @param string $query The DELETE query
     * @param array $params Query parameters
     * @return array The result set
     */
    private function executeDelete($query, $params = [])
    {
        // Parse table name from DELETE query
        preg_match('/DELETE\s+FROM\s+([`\"]?)(\w+)\1(?:\s+WHERE\s+(.+))?$/is', $query, $matches);
        
        if (!isset($matches[2])) {
            throw new \Exception("Unable to parse DELETE query");
        }

        $table = $matches[2];
        $whereClause = $matches[3] ?? null;

        // Parse WHERE clause
        $where = $this->parseWhereClause($whereClause ? "WHERE {$whereClause}" : '', $params);

        // Execute delete using Supabase client
        $queryBuilder = $this->client->from($table)->delete();
        
        if (!empty($where['clause'])) {
            $queryBuilder = $queryBuilder->filter($where['clause'], $where['params']);
        }

        $result = $queryBuilder->execute();
        $this->lastResult = $this->extractResponseData($result);
        return $this->lastResult;
    }

    /**
     * Execute a generic query using RPC
     * 
     * @param string $query The query
     * @param array $params Query parameters
     * @return array The result set
     */
    private function executeGenericQuery($query, $params = [])
    {
        $result = $this->client->rpc('execute_query', [
            'query' => $query,
            'params' => $params
        ])->execute();
        
        $this->lastResult = $this->extractResponseData($result);
        return $this->lastResult;
    }

    private function parseWhereClause($query, $params = [])
    {
        if (empty($query) || stripos($query, 'WHERE') === false) {
            return ['clause' => '', 'params' => []];
        }

        preg_match('/WHERE\s+(.+?)(?:\s+ORDER\s+BY|\s+LIMIT|$)/is', $query, $matches);
        if (!isset($matches[1])) {
            return ['clause' => '', 'params' => []];
        }

        $whereClause = trim($matches[1]);

        $whereParams = [];
        $paramIndex = 0;
        $whereClause = preg_replace_callback('/\?/', function() use ($params, &$paramIndex, &$whereParams) {
            if (isset($params[$paramIndex])) {
                $whereParams[] = $params[$paramIndex];
                $paramIndex++;
                return '$' . count($whereParams);
            }
            return '?';
        }, $whereClause);

        return ['clause' => $whereClause, 'params' => $whereParams];
    }

    private function parseOrderBy($query)
    {
        if (preg_match('/ORDER\s+BY\s+(.+?)(?:\s+LIMIT|$)/is', $query, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function parseLimit($query)
    {
        if (preg_match('/LIMIT\s+(\d+)/i', $query, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function parseSetClause($setClause, $params = [])
    {
        $setData = [];
        $paramIndex = 0;

        $assignments = explode(',', $setClause);
        foreach ($assignments as $assignment) {
            $assignment = trim($assignment);
            if (preg_match('/([`\"]?)(\w+)\1\s*=\s*(.+)/', $assignment, $matches)) {
                $column = $matches[2];
                $value = trim($matches[3]);

                if ($value === '?' && isset($params[$paramIndex])) {
                    $setData[$column] = $params[$paramIndex];
                    $paramIndex++;
                } else {
                    $setData[$column] = trim($value, "'\"");
                }
            }
        }

        return $setData;
    }

    private function applyWhereFilters($queryBuilder, $whereClause, $params = [])
    {
        if (empty($whereClause) || empty($params)) {
            return $queryBuilder;
        }

        if (preg_match('/(\w+)\s*=\s*\?/i', $whereClause, $matches)) {
            $column = $matches[1];
            if (isset($params[0])) {
                $queryBuilder = $queryBuilder->eq($column, $params[0]);
            }
        }

        return $queryBuilder;
    }

    public function fetchAll($result = null)
    {
        return $result ?: $this->lastResult;
    }

    public function fetchAssoc($result = null)
    {
        $resultSet = $result ?: $this->lastResult;
        return !empty($resultSet) ? $resultSet[0] : null;
    }

    public function fetchRow($result = null)
    {
        $resultSet = $result ?: $this->lastResult;
        return !empty($resultSet) ? array_values($resultSet[0]) : null;
    }

    public function affectedRows()
    {
        if (empty($this->lastResult) && isset($this->lastError) && empty($this->lastError)) {
            return 1;
        }

        return count($this->lastResult);
    }

    public function lastInsertId()
    {
        if (!empty($this->lastResult) && isset($this->lastResult[0])) {
            $row = $this->lastResult[0];
            if (isset($row['id'])) {
                return $row['id'];
            } elseif (isset($row['ID'])) {
                return $row['ID'];
            } elseif (isset($row['id_user'])) {
                return $row['id_user'];
            }
        }
        return null;
    }

    public function escape($string)
    {
        return addslashes($string);
    }

    public function prepare($query)
    {
        return $query;
    }

    public function executePrepared($stmt, $params = [])
    {
        return $this->query($stmt, $params);
    }

    public function beginTransaction()
    {
        if ($this->inTransaction) {
            return false;
        }

        try {
            $result = $this->client->rpc('begin_transaction')->execute();
            $this->inTransaction = true;
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return false;
        }
    }

    public function commit()
    {
        if (!$this->inTransaction) {
            return false;
        }

        try {
            $result = $this->client->rpc('commit_transaction')->execute();
            $this->inTransaction = false;
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return false;
        }
    }

    public function rollback()
    {
        if (!$this->inTransaction) {
            return false;
        }

        try {
            $result = $this->client->rpc('rollback_transaction')->execute();
            $this->inTransaction = false;
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return false;
        }
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
        try {
            $result = $this->client->from($table)->insert($data)->execute();

            if (property_exists($result, 'status') && $result->status === 201) {
                if (isset($data['username'])) {
                    $verifyResult = $this->client->from($table)->select('*')->eq('username', $data['username'])->execute();
                    $this->lastResult = $this->extractResponseData($verifyResult);
                } else {
                    $this->lastResult = $this->extractResponseData($result);
                }
                return $this->lastInsertId();
            } else {
                $this->lastResult = $this->extractResponseData($result);
                return $this->lastInsertId();
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return false;
        }
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $queryBuilder = $this->client->from($table)->update($data);

            if (!empty($where)) {
                $queryBuilder = $queryBuilder->filter($where, $whereParams);
            }

            $result = $queryBuilder->execute();
            $this->lastResult = $this->extractResponseData($result);
            return $this->affectedRows();
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return 0;
        }
    }

    public function delete($table, $where, $whereParams = [])
    {
        try {
            $queryBuilder = $this->client->from($table)->delete();

            if (!empty($where)) {
                $queryBuilder = $queryBuilder->filter($where, $whereParams);
            }

            $result = $queryBuilder->execute();
            $this->lastResult = $this->extractResponseData($result);
            return $this->affectedRows();
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return 0;
        }
    }

    public function count($table, $where = '1=1', $whereParams = [])
    {
        try {
            $queryBuilder = $this->client->from($table)->select('count', ['count' => 'exact']);

            if ($where !== '1=1') {
                $queryBuilder = $queryBuilder->filter($where, $whereParams);
            }

            $result = $queryBuilder->execute();
            $count = $this->extractResponseCount($result);

            if ($count === 0) {
                $allRecords = $this->client->from($table)->select('*')->limit(1000)->execute();
                $actualCount = count($this->extractResponseData($allRecords));
                return $actualCount;
            }

            return $count;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return 0;
        }
    }

    public function rpc($function, $params = [])
    {
        try {
            $result = $this->client->rpc($function, $params)->execute();
            $this->lastResult = $this->extractResponseData($result);
            return $this->lastResult;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            return [];
        }
    }

    public function getConnection()
    {
        return $this->client;
    }

    public function close()
    {
        $this->client = null;
        return true;
    }

    public function isConnected()
    {
        return $this->client !== null;
    }

    public function reconnect()
    {
        $this->close();
        return $this->connect();
    }

    public function getDatabaseInfo()
    {
        try {
            $result = $this->client->rpc('version')->execute();
            $data = $this->extractResponseData($result);
            return "Supabase (PostgreSQL) " . ($data[0]['version'] ?? 'Unknown');
        } catch (\Exception $e) {
            return "Supabase (PostgreSQL) - Version unavailable";
        }
    }
}