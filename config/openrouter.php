<?php
/**
 * AegisZ Sentinel - OpenRouter Configuration (v0.7.0)
 * All values are read from environment variables via App\Core\Env.
 * The API key is NEVER hardcoded here and NEVER logged anywhere in the app.
 *
 * "Best premium model at no cost" — OpenRouter periodically offers select
 * models at $0 input/output pricing (typically promotional or community-
 * sponsored slots, often suffixed ":free"). These rotate, so this config
 * defaults to a strong general-purpose free-tier model and is fully
 * overridable via OPENROUTER_MODEL without touching code.
 */

use App\Core\Env;

return [
    'enabled' => Env::get('OPENROUTER_API_KEY', '') !== '',

    'api_key'  => Env::get('OPENROUTER_API_KEY', ''),
    'base_url' => Env::get('OPENROUTER_BASE_URL') ?: 'https://openrouter.ai/api/v1',

    // Default: a capable free-tier model on OpenRouter. Check
    // https://openrouter.ai/models?max_price=0 for the current best
    // free option — availability and naming rotate over time.
    'model' => Env::get('OPENROUTER_MODEL') ?: 'meta-llama/llama-3.3-70b-instruct:free',

    // Fallback chain if the primary free model is rate-limited/unavailable
    'fallback_models' => [
        'google/gemini-2.0-flash-exp:free',
        'qwen/qwen-2.5-72b-instruct:free',
    ],

    'temperature' => 0.3,   // Low temperature: factual, consistent analyst output
    'max_tokens'  => 1500,
    'timeout'     => 30,    // seconds

    // OpenRouter recommends these headers for attribution/rate-limit tiers
    'http_referer' => Env::get('APP_BASE_URL') ?: 'https://aegisz.local',
    'x_title'      => 'AegisZ Sentinel',

    // Assessment cache lifetime in minutes (spec: refresh every 15-30 min
    // or after Intelligence Worker completes — worker triggers an explicit
    // cache bust, this TTL is the time-based fallback)
    'assessment_cache_minutes' => 20,
];
