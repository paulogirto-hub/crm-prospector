<?php
/**
 * LeadActivity Model
 */

namespace App\Models;

use App\Core\Model;

class LeadActivity extends Model
{
    protected static string $table = 'lead_activities';
    protected static array $fillable = ['lead_id', 'user_id', 'type', 'description', 'metadata'];

    /**
     * Busca atividades de um lead
     */
    public static function findByLead(int $leadId, int $limit = 50): array
    {
        return static::query()
            ->where('lead_id', $leadId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}