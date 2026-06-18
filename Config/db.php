<?php
// Load the database abstraction layer
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Services\DatabaseAdapter;
use Core\Factories\DatabaseFactory;

// Set timezone for PHP to ensure consistency (Jakarta/WIB = UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Create the database adapter using the abstraction layer
// This will automatically use the appropriate adapter based on DB_TYPE in .env
try {
    $dbAdapter = DatabaseAdapter::create();
    
    // For backward compatibility, create a connection variable
    // that mimics the old mysqli connection behavior
    $conn = $dbAdapter->getConnection();
    
    // Store the adapter globally for access throughout the application
    if (!isset($GLOBALS['db_adapter'])) {
        $GLOBALS['db_adapter'] = $dbAdapter;
    }
    
    error_log("db.php: Successfully connected using database abstraction layer");
    error_log("db.php: Database type: " . $dbAdapter->getDbType());
    error_log("db.php: Database info: " . $dbAdapter->getDatabaseInfo());
    
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    // Fallback to legacy MySQL connection if abstraction layer fails
    // This ensures backward compatibility during transition
    error_log("db.php: Abstraction layer failed, attempting fallback to legacy MySQL connection");
    
    $host = "127.0.0.1"; // numeric host to avoid socket mismatch
    $user = "root";
    $pass = "";
    $db   = "mumtaza_ujian";

    // === Auto-detect MySQL port (3306 or 3305) ===
    $default_ports = [3306, 3305];
    $port = null;
    foreach ($default_ports as $p) {
        $sock = @fsockopen($host, $p, $errno, $errstr, 1.0); // Increased timeout for more reliable detection
        if ($sock) {
            $port = $p;
            fclose($sock);
            break;
        }
    }

    // If auto-detection fails, try default port as fallback
    if ($port === null) {
        error_log("No active MySQL port detected (tried 3306 and 3305). Using default port 3306 as fallback.");
        $port = 3306;
    }

    // === Connect to MySQL ===
    error_log("db.php: Attempting to connect to MySQL at $host:$port, database: $db");
    $conn = @mysqli_connect($host, $user, $pass, $db, $port);

    if (!$conn) {
        $error_code = mysqli_connect_errno();
        $error_msg = mysqli_connect_error();
        error_log("Database connection failed: " . $error_msg);
        error_log("Database connection error code: " . $error_code);
        
        // Try with alternative configuration if primary fails
        error_log("db.php: Primary connection failed, trying alternative configuration");
        
        // Try without specifying port (let MySQL client decide)
        $conn = @mysqli_connect($host, $user, $pass, $db);
        
        if (!$conn) {
            $alt_error_code = mysqli_connect_errno();
            $alt_error_msg = mysqli_connect_error();
            error_log("Alternative database connection also failed: " . $alt_error_msg);
            error_log("Alternative database connection error code: " . $alt_error_code);
            die("Koneksi database gagal. Silakan periksa konfigurasi database Anda.");
        }
    }

    // Set character set to UTF-8 for proper encoding
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        error_log("Error setting character set: " . $conn->error);
    }

    // Set timezone for MySQL to ensure consistency (Jakarta/WIB = UTC+7)
    mysqli_query($conn, "SET time_zone = '+07:00'");

    error_log("db.php: Successfully connected to database with host info: " . $conn->host_info);
}

// include table mapping constants
@include_once __DIR__ . '/tables.php';

/**
 * Get the database adapter instance
 *
 * @return DatabaseAdapter|null The database adapter instance or null if not available
 */
function get_db_adapter() {
    return $GLOBALS['db_adapter'] ?? null;
}

/**
 * Execute a query using the abstraction layer with fallback to mysqli
 *
 * @param string $sql The SQL query
 * @param array $params Optional parameters for prepared statements
 * @return mixed The result set
 */
function db_query($sql, $params = []) {
    $adapter = get_db_adapter();
    
    if ($adapter) {
        try {
            return $adapter->query($sql, $params);
        } catch (Exception $e) {
            error_log("Abstraction layer query failed: " . $e->getMessage());
            // Fall through to mysqli
        }
    }
    
    // Fallback to mysqli
    global $conn;
    if (!$conn) {
        return false;
    }
    
    if (empty($params)) {
        return mysqli_query($conn, $sql);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
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
            call_user_func_array([$stmt, 'bind_param'], refValues($bindParams));
            
            if (mysqli_stmt_execute($stmt)) {
                return mysqli_stmt_get_result($stmt);
            }
        }
        return false;
    }
}

/**
 * Convert array values to references for bind_param (helper function)
 */
function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

/**
 * Fetch all rows using the abstraction layer with fallback to mysqli
 *
 * @param mixed $result The result set
 * @return array Array of rows
 */
function db_fetch_all($result = null) {
    $adapter = get_db_adapter();
    
    if ($adapter) {
        try {
            return $adapter->fetchAll($result);
        } catch (Exception $e) {
            error_log("Abstraction layer fetch_all failed: " . $e->getMessage());
            // Fall through to mysqli
        }
    }
    
    // Fallback to mysqli
    if ($result instanceof mysqli_result) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

/**
 * Fetch associative array using the abstraction layer with fallback to mysqli
 *
 * @param mixed $result The result set
 * @return array|null The row or null
 */
function db_fetch_assoc($result = null) {
    $adapter = get_db_adapter();
    
    if ($adapter) {
        try {
            return $adapter->fetchAssoc($result);
        } catch (Exception $e) {
            error_log("Abstraction layer fetch_assoc failed: " . $e->getMessage());
            // Fall through to mysqli
        }
    }
    
    // Fallback to mysqli
    if ($result instanceof mysqli_result) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Execute RPC function using the abstraction layer
 *
 * @param string $function The function name
 * @param array $params Function parameters
 * @return mixed The result
 */
function db_rpc($function, $params = []) {
    $adapter = get_db_adapter();
    
    if ($adapter) {
        try {
            return $adapter->rpc($function, $params);
        } catch (Exception $e) {
            error_log("Abstraction layer RPC failed: " . $e->getMessage());
            return [];
        }
    }
    
    // RPC not supported in legacy mysqli
    error_log("RPC functions require Supabase database adapter");
    return [];
}
?>
