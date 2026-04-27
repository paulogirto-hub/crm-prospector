<?php
/**
 * Company Model
 */

namespace App\Models;

use App\Core\Model;

class Company extends Model
{
    protected static string $table = 'companies';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name', 'cnpj', 'niche', 'city', 'state', 'phone', 'email',
        'site_url', 'instagram', 'facebook', 'youtube', 'tiktok',
        'maps_rating', 'maps_reviews', 'maps_address', 'maps_phone', 'maps_category',
        'maps_lat', 'maps_lng', 'score', 'notes',
        'razao_social', 'situacao', 'situacao_cnpj', 'capital_social', 'data_inicio',
        'opcao_pelo_mei', 'opcao_pelo_simples', 'cnae_descricao', 'natureza_juridica',
        'porte', 'email_receita', 'telefone_receita', 'socios',
        'site_emails', 'site_phones', 'site_instagram', 'site_facebook',
        'site_youtube', 'site_tiktok', 'cnpj_source', 'snippet', 'enrichment_status',
        'tem_site', 'tem_instagram', 'tem_facebook', 'tem_maps', 'tem_ads',
        'archived',
        'created_by', 'updated_at', 'deleted_at'
    ];

    /**
     * Busca por CNPJ
     */
    public static function findByCnpj(string $cnpj): ?array
    {
        return static::findWhere('cnpj', $cnpj);
    }

    /**
     * Busca por nome (fuzzy — LIKE)
     */
    public static function findByName(string $name): array
    {
        return static::query()
            ->where('name', 'LIKE', "%{$name}%")
            ->get();
    }

    /**
     * Conta empresas com site
     */
    public static function countWithSite(): int
    {
        return static::count("tem_site = true");
    }

    /**
     * Conta empresas com Instagram
     */
    public static function countWithInstagram(): int
    {
        return static::count("tem_instagram = true");
    }

    /**
     * Top empresas por score
     */
    public static function topByScore(int $limit = 20): array
    {
        return static::query()
            ->where('score', 0, '>')
            ->orderBy('score', 'DESC')
            ->limit($limit)
            ->get();
    }
}