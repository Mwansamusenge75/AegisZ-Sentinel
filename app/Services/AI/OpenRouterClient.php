<?php
/**
 * AegisZ Sentinel - OpenRouter Client (v0.7.0)
 * Thin HTTP client for the OpenRouter chat completions API.
 *
 * SECURITY BOUNDARY: this class has NO dependency on, NO reference to, and
 * NO import of any Repository, Service, or Entity that can write to
 * operational data (Alerts, Incidents, Correlations, Risk Scores, Threats).
 * It only ever returns raw text/JSON from the model back to its caller.
 * It cannot create, close, modify, or delete anything in the platform.
 *
 * Uses cURL only — no Composer HTTP client dependency.
 */

namespace App\Services\AI;

use App\Core\Logger;

class OpenRouterClient
{
    private array  $config;
    private Logger $logger;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 3) . '/config/openrouter.php';
        $this->logger = new Logger();
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Send a chat completion request. Returns the assistant's text content,
     * or null on failure. Never throws to the caller — failures are logged
     * and degrade gracefully (caller must handle null).
     */
    public function complete(string $systemPrompt, string $userPrompt): ?string
    {
        if (!$this->isEnabled()) {
            $this->logAi('skipped', 'AI not configured (no API key present)');
            return null;
        }

        $models = array_merge([$this->config['model']], $this->config['fallback_models'] ?? []);

        foreach ($models as $model) {
            $result = $this->attemptCompletion($model, $systemPrompt, $userPrompt);
            if ($result !== null) {
                return $result;
            }
        }

        $this->logAi('failed', 'All models exhausted (primary + fallbacks)');
        return null;
    }

    private function attemptCompletion(string $model, string $systemPrompt, string $userPrompt): ?string
    {
        $startTime = microtime(true);

        $payload = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => $this->config['temperature'] ?? 0.3,
            'max_tokens'  => $this->config['max_tokens'] ?? 1500,
        ];

        $ch = curl_init($this->config['base_url'] . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => $this->config['timeout'] ?? 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config['api_key'],
                'HTTP-Referer: ' . ($this->config['http_referer'] ?? ''),
                'X-Title: ' . ($this->config['x_title'] ?? 'AegisZ Sentinel'),
            ],
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = round((microtime(true) - $startTime) * 1000);

        if ($curlError) {
            $this->logAi('error', "cURL error on model {$model}: {$curlError}", $model, $latencyMs);
            return null;
        }

        if ($httpCode !== 200) {
            $this->logAi('error', "HTTP {$httpCode} on model {$model}", $model, $latencyMs);
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        $usage   = $decoded['usage'] ?? [];

        if ($content === null) {
            $this->logAi('error', "Empty response content from model {$model}", $model, $latencyMs);
            return null;
        }

        $this->logAi(
            'success',
            "Completion received",
            $model,
            $latencyMs,
            $usage['total_tokens'] ?? null
        );

        return $content;
    }

    /**
     * Write to storage/logs/ai.log. Never logs the API key, request payload
     * contents, or response contents — only metadata.
     */
    private function logAi(string $status, string $message, ?string $model = null, ?int $latencyMs = null, ?int $tokens = null): void
    {
        $logPath = dirname(__DIR__, 3) . '/storage/logs/ai.log';
        $entry = [
            'timestamp'  => date('Y-m-d H:i:s'),
            'status'     => $status,
            'message'    => $message,
            'model'      => $model,
            'latency_ms' => $latencyMs,
            'tokens'     => $tokens,
        ];
        $line = json_encode($entry) . PHP_EOL;
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);

        // Also mirror to the main app logger at appropriate level
        if ($status === 'error') {
            $this->logger->warning("[AI] {$message}", ['model' => $model]);
        } else {
            $this->logger->info("[AI] {$message}", ['model' => $model]);
        }
    }
}
