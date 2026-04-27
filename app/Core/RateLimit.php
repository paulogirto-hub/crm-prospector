<?php
/**
 * RateLimit — Rate limiting usando Redis
 * 
 * Limite por IP: 30 req/min geral, 5 tentativas/login em 15min
 */

namespace App\Core;

class RateLimit
{
    private static ?\Redis $redis = null;

    /**
     * Obtém conexão Redis
     */
    private static function redis(): ?\Redis
    {
        if (self::$redis !== null) {
            return self::$redis;
        }

        try {
            $redis = new \Redis();
            $host = Config::get('REDIS_HOST', 'redis');
            $port = (int)Config::get('REDIS_PORT', 6379);
            $db = (int)Config::get('REDIS_DB', 0);

            if (!$redis->connect($host, $port, 2.0)) {
                return null;
            }

            if ($db > 0) {
                $redis->select($db);
            }

            self::$redis = $redis;
            return $redis;
        } catch (\Exception $e) {
            // Fallback para arquivo se Redis não disponível
            return null;
        }
    }

    /**
     * Verifica se o limite foi atingido
     * Retorna true se ALLOWED, false se BLOCKED
     */
    public static function check(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $attempts = self::attempts($key);
        return $attempts < $maxAttempts;
    }

    /**
     * Incrementa o contador para a chave
     */
    public static function hit(string $key, int $decaySeconds = 0): void
    {
        $redis = self::redis();

        if ($redis) {
            $redisKey = "rate_limit:{$key}";
            $redis->multi();
            $redis->incr($redisKey);
            if ($decaySeconds > 0) {
                $redis->expire($redisKey, $decaySeconds);
            }
            $redis->exec();
        } else {
            // Fallback: arquivo
            self::fileHit($key, $decaySeconds);
        }
    }

    /**
     * Retorna número de tentativas para a chave
     */
    public static function attempts(string $key): int
    {
        $redis = self::redis();

        if ($redis) {
            $val = $redis->get("rate_limit:{$key}");
            return $val !== false ? (int)$val : 0;
        }

        return self::fileAttempts($key);
    }

    /**
     * Retorna timestamp quando o limite será resetado
     */
    public static function availableAt(string $key): int
    {
        $redis = self::redis();

        if ($redis) {
            $ttl = $redis->ttl("rate_limit:{$key}");
            return $ttl > 0 ? time() + $ttl : time();
        }

        return self::fileAvailableAt($key);
    }

    /**
     * Limpa o contador para uma chave
     */
    public static function clear(string $key): void
    {
        $redis = self::redis();

        if ($redis) {
            $redis->del("rate_limit:{$key}");
        } else {
            $file = self::filePath($key);
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Retorna segundos restantes até poder tentar novamente
     */
    public static function retryAfter(string $key): int
    {
        $available = self::availableAt($key);
        return max(0, $available - time());
    }

    // ─── File-based fallback ───

    private static function storagePath(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/rate_limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function filePath(string $key): string
    {
        return self::storagePath() . '/' . md5($key) . '.json';
    }

    private static function fileHit(string $key, int $decaySeconds = 0): void
    {
        $file = self::filePath($key);
        $data = self::fileRead($file);
        $data['attempts']++;
        if ($decaySeconds > 0 && !isset($data['expires_at'])) {
            $data['expires_at'] = time() + $decaySeconds;
        }
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private static function fileAttempts(string $key): int
    {
        $file = self::filePath($key);
        $data = self::fileRead($file);

        // Check expiry
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            @unlink($file);
            return 0;
        }

        return $data['attempts'] ?? 0;
    }

    private static function fileAvailableAt(string $key): int
    {
        $file = self::filePath($key);
        $data = self::fileRead($file);
        return $data['expires_at'] ?? time();
    }

    private static function fileRead(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }

    /**
     * Gera chave de rate limit por IP
     */
    public static function keyForIp(string $prefix = ''): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
        return $prefix . ':' . $ip;
    }
}