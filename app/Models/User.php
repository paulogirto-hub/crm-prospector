<?php
/**
 * User Model
 */

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static bool $softDelete = true;
    protected static array $fillable = ['name', 'email', 'password_hash', 'role', 'active', 'remember_token', 'reset_token', 'reset_token_expires_at', 'plan_id', 'email_verified_at', 'updated_at', 'deleted_at'];

    /**
     * Busca por email
     */
    public static function findByEmail(string $email): ?array
    {
        return static::findWhere('email', $email);
    }

    /**
     * Cria um novo usuário com senha hasheada
     */
    public static function createUser(string $name, string $email, string $password, string $role = 'seller'): int
    {
        return static::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $role,
            'active' => true,
        ]);
    }

    /**
     * Atualiza senha
     */
    public static function updatePassword(int $id, string $password): bool
    {
        return static::updateById($id, [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
    }

    /**
     * Alterna estado ativo/inativo
     */
    public static function toggleActive(int $id): bool
    {
        $user = static::findById($id);
        if (!$user) return false;
        return static::updateById($id, ['active' => !$user['active']]);
    }

    /**
     * Lista usuários por role
     */
    public static function byRole(string $role): array
    {
        return static::where('role', $role);
    }

    /**
     * Conta usuários ativos
     */
    public static function countActive(): int
    {
        return static::count('active = true');
    }
}