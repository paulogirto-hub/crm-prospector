<?php
/**
 * SearchSession Model
 */

namespace App\Models;

use App\Core\Model;

class SearchSession extends Model
{
    protected static string $table = 'search_sessions';
    protected static array $fillable = [
        'user_id', 'niche', 'city', 'state', 'query', 'query_variations',
        'raw_results_count', 'total_results', 'com_site', 'com_instagram',
        'com_maps', 'com_ads', 'com_cnpj', 'com_site_email', 'com_site_phone',
        'com_youtube', 'com_tiktok', 'ia_market_analysis', 'status',
        'analyzed_count', 'total_to_analyze', 'prospec_search_id'
    ];

    /**
     * Busca por usuário
     */
    public static function findByUser(int $userId, int $limit = 20): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Busca por prospec_search_id (BUG-004: hex ID do Prospector)
     */
    public static function findByProspecId(string $prospecId): ?array
    {
        return static::query()
            ->where('prospec_search_id', $prospecId)
            ->first();
    }

    /**
     * Busca ou cria uma SearchSession a partir do ID do Prospector
     * BUG-004 FIX: Mapeia hex ID do Prospector para nosso DB
     */
    public static function findOrCreateFromProspector(string $prospecId, int $userId, array $data = []): array
    {
        $existing = static::findByProspecId($prospecId);
        if ($existing) {
            return $existing;
        }

        $createData = array_merge($data, [
            'user_id' => $userId,
            'prospec_search_id' => $prospecId,
        ]);

        return static::create($createData);
    }
}