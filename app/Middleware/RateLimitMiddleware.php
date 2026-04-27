<?php
/**
 * RateLimitMiddleware — Limita requisições POST por IP
 * 30 requisições por minuto por IP
 */

namespace App\Middleware;

use App\Core\RateLimit;
use App\Core\Response;

class RateLimitMiddleware
{
    /**
     * Limite geral: 30 req/min por IP
     */
    public function handle()
    {
        $key = RateLimit::keyForIp('post');
        $maxAttempts = 30;
        $decaySeconds = 60;

        if (!RateLimit::check($key, $maxAttempts, $decaySeconds)) {
            $retryAfter = RateLimit::retryAfter($key);
            http_response_code(429);
            header("Retry-After: {$retryAfter}");
            echo json_encode([
                'error' => 'Muitas requisições. Tente novamente em ' . $retryAfter . ' segundos.',
                'retry_after' => $retryAfter,
            ]);
            return false;
        }

        RateLimit::hit($key, $decaySeconds);
        return true;
    }
}