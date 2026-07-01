<?php
/**
 * AegisZ Sentinel - API Base Controller
 * Placeholder for future REST API layer.
 */

namespace App\Api;

use App\Core\Response;

abstract class ApiBaseController
{
    protected function success(array $data = [], string $message = 'OK'): void
    {
        Response::success($data, $message);
    }

    protected function error(string $message, int $statusCode = 400): void
    {
        Response::error($message, $statusCode);
    }
}
