<?php
/**
 * AegisZ Sentinel - Base Middleware
 * Abstract class for all middleware.
 */

namespace App\Middleware;

abstract class BaseMiddleware
{
    abstract public function handle(): void;
}
