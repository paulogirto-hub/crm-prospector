<?php
/**
 * PipelineStage Model
 */

namespace App\Models;

use App\Core\Model;

class PipelineStage extends Model
{
    protected static string $table = 'pipeline_stages';
    protected static array $fillable = ['name', 'position', 'color', 'is_default'];

    /**
     * Retorna stages ordenados por posição
     */
    public static function ordered(): array
    {
        return static::query()
            ->orderBy('position', 'ASC')
            ->get();
    }

    /**
     * Retorna stage padrão (Novo)
     */
    public static function defaultStage(): ?array
    {
        return static::findWhere('is_default', true);
    }

    /**
     * Busca por nome
     */
    public static function findByName(string $name): ?array
    {
        return static::findWhere('name', $name);
    }
}