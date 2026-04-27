<?php
/**
 * Setting Model
 */

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['user_id', 'key', 'value'];

    /**
     * Busca setting por usuário e chave
     */
    public static function getByKey(int $userId, string $key): ?string
    {
        $result = static::raw(
            "SELECT value FROM settings WHERE user_id = :uid AND key = :key LIMIT 1",
            ['uid' => $userId, 'key' => $key]
        )->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * Seta um valor de configuração
     */
    public static function setKey(int $userId, string $key, string $value): void
    {
        $exists = static::count("user_id = :uid AND key = :key", ['uid' => $userId, 'key' => $key]);
        
        if ($exists) {
            static::raw(
                "UPDATE settings SET value = :value WHERE user_id = :uid AND key = :key",
                ['value' => $value, 'uid' => $userId, 'key' => $key]
            );
        } else {
            static::create([
                'user_id' => $userId,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}