<?php
/**
 * PDO Database Connection Singleton
 * 
 * This file provides a high-performance PDO connection for the Student Module.
 * Uses TCP persistent connections for reduced latency (<200ms vs 2.6s with HTTP SDK).
 * 
 * Pattern: Singleton
 * Settings: Persistent Connections (PDO::ATTR_PERSISTENT), Exception Mode
 * Charset: UTF8 (PostgreSQL default)
 * 
 * @see refactor-plans/REFACTOR_STRATEGY.md
 */

declare(strict_types=1);

/**
 * Get PDO connection singleton instance
 * 
 * @return PDO The shared PDO connection instance
 * @throws PDOException If connection fails
 */
function getPDOConnection(): PDO
{
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Load environment variables
    $env_path = __DIR__ . '/../.env';
    if (!file_exists($env_path)) {
        throw new RuntimeException('.env file not found. Please create it from .env.example');
    }
    
    // Parse .env file
    $env_content = file_get_contents($env_path);
    $env_vars = [];
    
    foreach (explode("\n", $env_content) as $line) {
        $line = trim($line);
        // Skip empty lines and comments
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Parse KEY=VALUE
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $env_vars[$key] = $value;
        }
    }
    
    // Get database configuration
    // Priority: Individual vars > DATABASE_URL parsing
    $db_host = $env_vars['DB_HOST'] ?? null;
    $db_port = $env_vars['DB_PORT'] ?? null;
    $db_name = $env_vars['DB_NAME'] ?? null;
    $db_user = $env_vars['DB_USER'] ?? null;
    $db_pass = $env_vars['DB_PASS'] ?? null;
    
    // If individual vars not set, parse DATABASE_URL
    if (!$db_host || !$db_user) {
        $database_url = $env_vars['DATABASE_URL'] ?? null;
        
        if (!$database_url) {
            throw new RuntimeException('DATABASE_URL or individual DB_* variables must be set in .env');
        }
        
        // Parse PostgreSQL connection string
        // Format: postgresql://user:password@host:port/database
        $parsed = parse_url($database_url);
        
        if ($parsed === false) {
            throw new RuntimeException('Invalid DATABASE_URL format');
        }
        
        $db_host = $parsed['host'] ?? 'localhost';
        $db_port = $parsed['port'] ?? 5432;
        $db_name = ltrim($parsed['path'] ?? '/postgres', '/');
        $db_user = $parsed['user'] ?? 'postgres';
        $db_pass = $parsed['pass'] ?? '';
    }
    
    // Ensure port is set
if (!$db_port) {
    $db_port = 5432;
}

    
    // Build DSN for PostgreSQL
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;options=\'--client_encoding=UTF8\'',
        $db_host,
        (int)$db_port,
        $db_name
    );
    
    // PDO options for performance and reliability
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::ATTR_PERSISTENT         => false,  // <--- CHANGE THIS TO FALSE
    ];
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        
        // Set timezone to match application
        $timezone = $env_vars['APP_TIMEZONE'] ?? 'Asia/Jakarta';
        // Convert PHP timezone to PostgreSQL format
        $tz_offset = (new DateTimeZone($timezone))->getOffset(new DateTime()) / 3600;
        $tz_offset_str = ($tz_offset >= 0 ? '+' : '') . $tz_offset;
        $pdo->exec("SET timezone TO '$tz_offset_str'");
        
        // Log successful connection (only in debug mode)
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            error_log("db_pdo.php: Successfully connected to PostgreSQL at $db_host:$db_port/$db_name");
        }
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("db_pdo.php: PostgreSQL connection failed - " . $e->getMessage());
        throw $e;
    }
}

/**
 * Execute a prepared statement and return all results
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array Array of results
 */
function pdo_fetch_all(string $sql, array $params = []): array
{
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a prepared statement and return single row
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array|null Single row or null
 */
function pdo_fetch_one(string $sql, array $params = []): ?array
{
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result !== false ? $result : null;
}

/**
 * Execute a prepared statement (INSERT/UPDATE/DELETE)
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function pdo_execute(string $sql, array $params = []): int
{
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Get the last inserted ID
 * 
 * @param string|null $sequence_name For PostgreSQL, the sequence name (optional)
 * @return string The last inserted ID
 */
function pdo_last_insert_id(?string $sequence_name = null): string
{
    $pdo = getPDOConnection();
    return $pdo->lastInsertId($sequence_name);
}

/**
 * Begin a transaction
 * 
 * @return bool True on success
 */
function pdo_begin_transaction(): bool
{
    return getPDOConnection()->beginTransaction();
}

/**
 * Commit a transaction
 * 
 * @return bool True on success
 */
function pdo_commit(): bool
{
    return getPDOConnection()->commit();
}

/**
 * Rollback a transaction
 * 
 * @return bool True on success
 */
function pdo_rollback(): bool
{
    return getPDOConnection()->rollBack();
}
