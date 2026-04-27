<?php
/**
 * Task Model
 */

namespace App\Models;

use App\Core\Model;

class Task extends Model
{
    protected static string $table = 'tasks';
    protected static array $fillable = ['lead_id', 'user_id', 'title', 'description', 'due_date', 'completed_at'];

    /**
     * Tarefas pendentes do usuário
     */
    public static function pendingByUser(int $userId, int $limit = 20): array
    {
        return static::raw(
            "SELECT * FROM tasks WHERE user_id = :uid AND completed_at IS NULL ORDER BY due_date ASC NULLS LAST LIMIT :lim",
            ['uid' => $userId, 'lim' => $limit]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Conta tarefas pendentes
     */
    public static function countPending(int $userId): int
    {
        return static::count("user_id = :uid AND completed_at IS NULL", ['uid' => $userId]);
    }

    /**
     * Marca tarefa como completa
     */
    public static function complete(int $id): bool
    {
        return static::updateById($id, ['completed_at' => date('c')]);
    }
}