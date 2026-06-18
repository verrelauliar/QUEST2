<?php
/**
 * PATCHED Request.php for Supabase PostgREST
 * 
 * This replaces vendor/supabase/postgrest-php/src/Util/Request.php
 * to support persistent HTTP client connections
 * 
 * CRITICAL FIX: Use singleton GuzzleHttp client instead of creating new one per query
 */

namespace Supabase\Postgrest\Util;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;

class Request
{
    // Static persistent client shared across all requests
    private static $persistentClient = null;
    
    /**
     * Get or create persistent GuzzleHttp client
     * This eliminates the need to create new connections for each query
     */
    private static function getClient(): GuzzleClient
    {
        if (self::$persistentClient === null) {
            self::$persistentClient = new GuzzleClient([
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => true,
                'verify' => true,
                // CRITICAL: Keep connections alive between requests
                'curl' => [
                    CURLOPT_TCP_KEEPALIVE => 1,
                    CURLOPT_TCP_KEEPIDLE => 120,
                    CURLOPT_TCP_KEEPINTVL => 60,
                    CURLOPT_FORBID_REUSE => 0,  // Allow connection reuse
                    CURLOPT_FRESH_CONNECT => 0, // Don't force new connections
                ],
            ]);
            
            error_log("Request: Created persistent HTTP client (will be reused for all queries)");
        }
        
        return self::$persistentClient;
    }
    
    public static function request($method, $url, $headers, $body = null): ResponseInterface
    {
        try {
            $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
            
            // Use persistent client instead of creating new one
            $client = self::getClient();
            
            $promise = $client->sendAsync($request)->then(function ($response) {
                return $response;
            });

            $response = $promise->wait();

            return $response;
        } catch (\Exception $e) {
            throw self::handleError($e);
        }
    }

    public static function handleError($error)
    {
        if (method_exists($error, 'getResponse')) {
            $response = $error->getResponse();
            $data = json_decode($response->getBody(), true);
            $error = new PostgrestApiError($data['code'], $data['details'], $data['hint'], $data['message'], $response);
        } else {
            $error = new PostgrestUnknownError($error->getMessage(), $error->getCode());
        }

        return $error;
    }
}
