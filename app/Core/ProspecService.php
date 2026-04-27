<?php
/**
 * ProspecService — Cliente HTTP para a API do Prospector
 * 
 * Comunica com o Prospector (porta 8088) via cURL.
 * Inclui fallback com mensagens amigáveis para timeout, 500, 429.
 */

namespace App\Core;

class ProspecService
{
    private static string $baseUrl = 'http://185.139.1.41:8088/api';
    private static int $timeout = 30;
    private static int $longTimeout = 120; // Para operações de IA que podem demorar 90s+

    /**
     * Inicia uma nova busca no Prospector
     */
    public static function search(string $niche, string $city, string $state): array
    {
        return self::post('/search', [
            'niche' => $niche,
            'city'  => $city,
            'state' => $state,
        ]);
    }

    /**
     * Retorna status e resultados de uma busca
     */
    public static function getStatus(string $searchId): array
    {
        return self::get("/search/{$searchId}");
    }

    /**
     * Retorna histórico de buscas
     */
    public static function getHistory(): array
    {
        return self::get('/history');
    }

    /**
     * Dispara enriquecimento de uma busca
     */
    public static function enrich(string $searchId): array
    {
        return self::post("/search/{$searchId}/enrich");
    }

    /**
     * Dispara scoring de uma busca
     */
    public static function score(string $searchId): array
    {
        return self::post("/search/{$searchId}/score");
    }

    /**
     * Dispara análise IA de leads (count=1 para análise individual)
     */
    public static function analyze(string $searchId, int $count = 0): array
    {
        $body = [];
        if ($count > 0) {
            $body['count'] = $count;
        }
        return self::postLong("/search/{$searchId}/analyze-leads", $body);
    }

    /**
     * Exporta resultados de uma busca (CSV/JSON)
     */
    public static function exportSearch(string $searchId): array
    {
        return self::get("/search/{$searchId}/export");
    }

    /**
     * Retorna detalhe de um lead específico
     */
    public static function getLead(string $searchId, string $leadId): array
    {
        return self::get("/search/{$searchId}/lead/{$leadId}");
    }

    /**
     * Diagnóstico de vendas por lead (IA, ~90s)
     * Inclui retry automático (até 2x) se Prospector retornar 500
     */
    public static function diagnoseLead(string $searchId, string $leadId): array
    {
        $result = self::postLong("/search/{$searchId}/diagnose/{$leadId}");
        
        // Retry em caso de 500 do Prospector (IA pode estar reiniciando)
        if (($result['http_code'] ?? 0) >= 500) {
            sleep(3);
            $result = self::postLong("/search/{$searchId}/diagnose/{$leadId}");
            
            if (($result['http_code'] ?? 0) >= 500) {
                // Segundo retry após pausa maior
                sleep(5);
                $result = self::postLong("/search/{$searchId}/diagnose/{$leadId}");
                
                if (($result['http_code'] ?? 0) >= 500) {
                    $result['error'] = 'O diagnóstico de IA está temporariamente indisponível. O serviço de IA pode estar reiniciando — tente novamente em 1-2 minutos.';
                }
            }
        }
        
        return $result;
    }

    /**
     * Análise de mercado com IA (~90s)
     */
    public static function analyzeMarket(string $searchId): array
    {
        return self::postLong("/search/{$searchId}/analyze-market");
    }

    /**
     * Re-analisar lead por índice (IA, ~90s)
     */
    public static function reanalyzeLead(string $searchId, int $leadIndex): array
    {
        return self::postLong("/search/{$searchId}/analyze-leads/{$leadIndex}");
    }

    /**
     * Editar texto da análise IA de um lead
     */
    public static function editAnalysis(string $searchId, string $leadId, string $text): array
    {
        return self::put("/search/{$searchId}/lead/{$leadId}/analysis", ['ia_analise' => $text]);
    }

    /**
     * Retornar detalhe de um lead (alias para getLead)
     */
    public static function getLeadDetail(string $searchId, string $leadId): array
    {
        return self::get("/search/{$searchId}/lead/{$leadId}");
    }

    // ─── Helpers cURL ────────────────────────────────────────

    private static function get(string $path): array
    {
        return self::request('GET', $path);
    }

    private static function post(string $path, array $data = []): array
    {
        return self::request('POST', $path, $data, self::$timeout);
    }

    /**
     * POST com timeout longo (120s) — usado para operações de IA
     */
    private static function postLong(string $path, array $data = []): array
    {
        return self::request('POST', $path, $data, self::$longTimeout);
    }

    /**
     * PUT request (30s) — usado para edições rápidas
     */
    private static function put(string $path, array $data = []): array
    {
        return self::request('PUT', $path, $data, self::$timeout);
    }

    /**
     * Retorna mensagem amigável para erros cURL
     */
    private static function getFriendlyCurlError(string $error, int $httpCode = 0): string
    {
        if (stripos($error, 'timed out') !== false || stripos($error, 'timeout') !== false) {
            return 'A operação demorou demais. O servidor pode estar sobrecarregado — tente novamente em alguns instantes.';
        }
        if (stripos($error, 'resolve') !== false || stripos($error, 'could not connect') !== false || stripos($error, 'connection refused') !== false) {
            return 'Não foi possível conectar ao serviço de prospecção. Verifique sua conexão ou tente mais tarde.';
        }
        if (stripos($error, 'ssl') !== false || stripos($error, 'certificate') !== false) {
            return 'Erro de segurança na conexão. Tente novamente ou contate o suporte.';
        }
        return 'Erro de conexão com o servidor. Tente novamente em alguns instantes.';
    }

    private static function request(string $method, string $path, array $data = [], int $timeout = 0): array
    {
        $url = self::$baseUrl . $path;
        $effectiveTimeout = $timeout > 0 ? $timeout : self::$timeout;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $effectiveTimeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        // Fallback amigável para erros de conexão
        if ($error) {
            $friendlyMessage = self::getFriendlyCurlError($error, $httpCode ?: 0);
            return [
                'success'      => false,
                'error'        => $friendlyMessage,
                'error_detail' => "cURL error: {$error}",
                'http_code'    => $httpCode ?: 0,
            ];
        }

        // Fallback amigável para HTTP 5xx
        if ($httpCode >= 500) {
            return [
                'success'      => false,
                'error'        => 'O serviço de prospecção está temporariamente indisponível. Tente novamente em alguns minutos.',
                'error_detail' => "HTTP {$httpCode}",
                'http_code'    => $httpCode,
            ];
        }

        // Fallback amigável para HTTP 429
        if ($httpCode === 429) {
            return [
                'success'      => false,
                'error'        => 'Muitas requisições. Aguarde alguns segundos antes de tentar novamente.',
                'error_detail' => 'Rate limit exceeded',
                'http_code'    => 429,
                'retry_after'  => 30,
            ];
        }

        // Fallback para HTTP 4xx genérico
        if ($httpCode >= 400 && $httpCode < 500) {
            $decoded = json_decode($response, true);
            $msg = $decoded['error'] ?? $decoded['message'] ?? 'Requisição inválida. Verifique os dados e tente novamente.';
            return [
                'success'      => false,
                'error'        => $msg,
                'error_detail' => "HTTP {$httpCode}",
                'http_code'    => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success'      => false,
                'error'        => 'Resposta inesperada do servidor. Tente novamente.',
                'error_detail' => 'Invalid JSON response',
                'raw'          => mb_substr($response, 0, 500),
                'http_code'    => $httpCode,
            ];
        }

        if (!is_array($decoded)) {
            $decoded = ['data' => $decoded];
        }

        $decoded['success']   = ($httpCode >= 200 && $httpCode < 300);
        $decoded['http_code'] = $httpCode;

        return $decoded;
    }
}
