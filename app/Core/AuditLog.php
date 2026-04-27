<?php
/**
 * AuditLog — Model para log de auditoria
 * Usado internamente pelo Auth e Controllers
 */

namespace App\Core;

class AuditLog
{
    private static ?\PDO $pdo = null;

    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Cria entrada de audit log (MELH-007: com user_agent e session_id)
     */
    public static function create(array $data): int
    {
        if (!self::$pdo) return 0;

        $defaults = [
            'user_id' => null,
            'action' => '',
            'entity_type' => null,
            'entity_id' => null,
            'details' => '{}',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id() ?: null,
        ];

        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip, user_agent, session_id, created_at) 
                VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip, :user_agent, :session_id, NOW())";
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($data);
        return (int) self::$pdo->lastInsertId();
    }

    /**
     * MELH-007: Log de leitura (ex: export, acesso admin)
     */
    public static function logRead(string $entityType, ?int $entityId = null, ?string $details = null): int
    {
        return self::create([
            'action' => 'read',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ?? json_encode(['uri' => $_SERVER['REQUEST_URI'] ?? '/']),
        ]);
    }

    /**
     * MELH-007: Log de exportação de dados
     */
    public static function logExport(string $entityType, int $count, string $format = 'csv'): int
    {
        return self::create([
            'action' => 'export',
            'entity_type' => $entityType,
            'details' => json_encode(['count' => $count, 'format' => $format]),
        ]);
    }

    /**
     * MELH-007: Log de acesso a página admin
     */
    public static function logAdminAccess(string $page): int
    {
        return self::create([
            'action' => 'admin_access',
            'entity_type' => 'page',
            'details' => json_encode(['page' => $page]),
        ]);
    }
}