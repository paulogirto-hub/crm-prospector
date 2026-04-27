<?php
/**
 * Lead Model
 */

namespace App\Models;

use App\Core\Model;

class Lead extends Model
{
    protected static string $table = 'leads';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'company_id', 'pipeline_stage_id', 'assigned_to', 'score',
        'source', 'status', 'estimated_value', 'last_contact_at',
        'ia_analise', 'ia_market_analysis', 'created_at', 'updated_at',
        'deleted_at', 'contact_name', 'email', 'phone'
    ];

    /**
     * Conta leads ativos
     */
    public static function countActive(): int
    {
        return static::count("status = 'active'");
    }

    /**
     * Conta leads novos esta semana
     */
    public static function countNewThisWeek(): int
    {
        return static::count("created_at >= date_trunc('week', CURRENT_DATE)");
    }

    /**
     * Conta leads por status
     */
    public static function countByStatus(string $status): int
    {
        return static::count("status = :status", ['status' => $status]);
    }

    /**
     * Soma valor estimado no pipeline
     */
    public static function totalEstimatedValue(): float
    {
        $result = static::raw(
            "SELECT COALESCE(SUM(estimated_value), 0) FROM leads WHERE status = 'active'"
        )->fetchColumn();
        return (float) $result;
    }

    /**
     * Conta leads por pipeline stage
     */
    public static function countByStage(): array
    {
        return static::raw(
            "SELECT ps.name, ps.color, ps.position, COUNT(l.id) as count 
             FROM pipeline_stages ps 
             LEFT JOIN leads l ON l.pipeline_stage_id = ps.id AND l.status = 'active' 
             GROUP BY ps.id, ps.name, ps.color, ps.position 
             ORDER BY ps.position"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Taxa de conversão
     */
    public static function conversionRate(): array
    {
        return static::raw(
            "SELECT 
                COUNT(CASE WHEN status = 'won' THEN 1 END) as won,
                COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(*) as total
             FROM leads"
        )->fetch(\PDO::FETCH_ASSOC);
    }
}