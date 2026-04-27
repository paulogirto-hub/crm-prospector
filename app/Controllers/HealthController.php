<?php
/**
 * HealthController — Health check e readiness probes
 * MELH-008: INFRA-19
 * 
 * GET /health  — Liveness (serviço está rodando)
 * GET /ready   — Readiness (DB + Redis conectados)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;

class HealthController extends Controller
{
    /**
     * Liveness probe — serviço está rodando?
     * Retorna 200 sempre (nginx já faz isso, mas adicionamos mais info)
     */
    public function health(): void
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
        ];

        // Verificar disco
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskPercent = $diskTotal > 0 ? round(($diskFree / $diskTotal) * 100, 1) : 0;
        $checks['disk'] = [
            'free' => $this->formatBytes($diskFree),
            'total' => $this->formatBytes($diskTotal),
            'used_percent' => 100 - $diskPercent,
        ];

        // Aviso se disco > 90%
        if ((100 - $diskPercent) > 90) {
            $checks['status'] = 'warning';
            $checks['warnings'][] = 'Disk usage above 90%';
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($checks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Readiness probe — DB e Redis estão conectados?
     */
    public function ready(): void
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'checks' => [],
        ];

        $allOk = true;

        // PostgreSQL
        try {
            $pdo = \App\Core\Model::getPdo();
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetchColumn();
            $checks['checks']['postgresql'] = [
                'status' => $result == 1 ? 'ok' : 'error',
                'latency_ms' => 0,
            ];
            if ($result != 1) $allOk = false;
        } catch (\Throwable $e) {
            $checks['checks']['postgresql'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $allOk = false;
        }

        // Redis
        try {
            $redis = new \Redis();
            $redis->connect(
                Config::get('REDIS_HOST', 'redis'),
                (int)Config::get('REDIS_PORT', 6379),
                2 // 2s timeout
            );
            $redis->ping();
            $checks['checks']['redis'] = ['status' => 'ok'];
            $redis->close();
        } catch (\Throwable $e) {
            $checks['checks']['redis'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            // Redis failure is not critical — service can still work
            $checks['checks']['redis']['note'] = 'Non-critical; file fallback available';
        }

        if (!$allOk) {
            $checks['status'] = 'error';
            http_response_code(503);
        } else {
            http_response_code(200);
        }

        header('Content-Type: application/json');
        echo json_encode($checks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Formata bytes para humano
     */
    private function formatBytes(int|float $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}