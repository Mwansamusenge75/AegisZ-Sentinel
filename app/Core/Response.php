<?php
/**
 * AegisZ Sentinel - Response Helper
 * JSON and HTML response utilities.
 */

namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data = [], string $message = 'OK'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'timestamp' => date('c'),
        ]);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
            'timestamp' => date('c'),
        ], $statusCode);
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
