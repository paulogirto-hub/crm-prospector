<?php
/**
 * Config — Carrega variáveis de ambiente do .env
 * 
 * Uso: $value = Config::get('DB_HOST', 'localhost');
 */

namespace App\Core;

class Config
{
    private static array $vars = [];
    private static bool $loaded = false;

    /**
     * Carrega o arquivo .env
     */
    public static function load(string $path = null): void
    {
        if (self::$loaded) return;

        $path = $path ?? dirname(__DIR__, 2) . '/.env';

        if (!file_exists($path)) {
            throw new \RuntimeException("Arquivo .env não encontrado em: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Pula comentários
            if (str_starts_with($line, '#') || str_starts_with($line, ';')) continue;

            // Variáveis de ambiente do sistema têm prioridade
            if (strpos($line, '=') === false) continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove aspas
            if (preg_match('/^["\'](.*)["\']\s*$/', $value, $m)) {
                $value = $m[1];
            }

            self::$vars[$key] = $value;

            // Só seta se não existe no $_ENV (variáveis do docker-compose têm prioridade)
            if (!isset($_ENV[$key]) && getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtém uma variável de configuração
     * Prioridade: $_ENV > getenv > .env file > default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) self::load();

        // Checa $_ENV primeiro (docker-compose env vars)
        if (isset($_ENV[$key])) {
            return self::cast($_ENV[$key]);
        }

        // Checa getenv()
        $envVal = getenv($key);
        if ($envVal !== false) {
            return self::cast($envVal);
        }

        // Checa arquivo .env
        if (isset(self::$vars[$key])) {
            return self::cast(self::$vars[$key]);
        }

        return $default;
    }

    /**
     * Converte valores string para tipo PHP apropriado
     */
    private static function cast(string $value): mixed
    {
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        if (strtolower($value) === 'null') return null;
        if (ctype_digit($value)) return (int) $value;
        if (is_numeric($value)) return (float) $value;
        return $value;
    }

    /**
     * Verifica se está em modo desenvolvimento
     */
    public static function isDev(): bool
    {
        return self::get('APP_ENV', 'production') === 'development';
    }

    /**
     * Retorna todas as variáveis (para debug)
     */
    public static function all(): array
    {
        if (!self::$loaded) self::load();
        return self::$vars;
    }
}