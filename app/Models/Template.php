<?php
/**
 * Template Model
 */

namespace App\Models;

use App\Core\Model;

class Template extends Model
{
    protected static string $table = 'templates';
    protected static array $fillable = ['user_id', 'name', 'niche', 'channel', 'subject', 'body', 'variables'];

    /**
     * Busca templates do usuário
     */
    public static function findByUser(int $userId): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Busca por canal
     */
    public static function findByChannel(string $channel): array
    {
        return static::where('channel', $channel);
    }
}