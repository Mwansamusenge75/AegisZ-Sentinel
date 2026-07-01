<?php
/**
 * AegisZ Sentinel - Configuration
 * Central configuration array. Replace with .env loader in production.
 */

return [
    'app' => [
        'name'        => 'AegisZ Sentinel',
        'version'     => '0.7.0',
        'environment' => 'development',
        'timezone'    => 'Africa/Lusaka',
        'base_url'    => '/aegisz-sentinel/public',
        'debug'       => true,
    ],
    'database' => [
        'host'      => 'localhost',
        'database'  => 'aegisz_db',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'paths' => [
        'base'    => dirname(__DIR__),
        'app'     => dirname(__DIR__) . '/app',
        'storage' => dirname(__DIR__) . '/storage',
        'public'  => dirname(__DIR__) . '/public',
    ],
    'security' => [
        'csrf_token_name' => 'aegisz_csrf_token',
        'session_name'    => 'AEGISZ_SESSION',
    ],
];
