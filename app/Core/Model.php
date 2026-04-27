<?php
/**
 * Model — Base model com PDO e CRUD genérico
 * 
 * Uso: class User extends Model { protected static string $table = 'users'; }
 */

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected static string $table = '';
    protected static PDO $pdo;
    protected static array $fillable = [];

    /**
     * MELH-005: Se true, queries automáticas excluem soft-deleted
     */
    protected static bool $softDelete = false;

    /**
     * Verifica se o model usa soft delete
     */
    public static function usesSoftDelete(): bool
    {
        return static::$softDelete;
    }

    /**
     * Seta a conexão PDO
     */
    public static function setPdo(PDO $pdo): void
    {
        static::$pdo = $pdo;
    }

    /**
     * Retorna a conexão PDO
     */
    public static function getPdo(): PDO
    {
        return static::$pdo;
    }

    /**
     * Retorna o nome da tabela
     */
    public static function getTable(): string
    {
        return static::$table;
    }

    /**
     * Busca por ID (respeita soft delete)
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE id = :id";
        if (static::$softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca todos os registros (respeita soft delete)
     */
    public static function all(string $orderBy = 'id DESC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM " . static::$table;
        if (static::$softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        $sql .= " ORDER BY {$orderBy}";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        $stmt = static::$pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca com WHERE (única condição)
     */
    public static function findWhere(string $column, mixed $value, string $operator = '='): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} {$operator} :val LIMIT 1";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['val' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca múltiplos com WHERE
     */
    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} {$operator} :val";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['val' => $value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo registro
     */
    public static function create(array $data): int
    {
        // Filtrar apenas campos fillable (se definidos)
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        
        $sql = "INSERT INTO " . static::$table . " ({$cols}) VALUES ({$placeholders})";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute($data);
        
        return (int) static::$pdo->lastInsertId();
    }

    /**
     * Atualiza um registro por ID
     */
    public static function updateById(int $id, array $data): bool
    {
        // Filtrar apenas campos fillable
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $data['id'] = $id;
        
        $sql = "UPDATE " . static::$table . " SET {$sets} WHERE id = :id";
        $stmt = static::$pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Deleta um registro por ID (soft delete se habilitado)
     */
    public static function deleteById(int $id): bool
    {
        if (static::$softDelete) {
            return static::updateById($id, ['deleted_at' => date('c')]);
        }
        $sql = "DELETE FROM " . static::$table . " WHERE id = :id";
        $stmt = static::$pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * MELH-005: Inclui soft-deleted nas queries
     */
    public static function withTrashed(): QueryBuilder
    {
        return new QueryBuilder(static::$table, static::$pdo, true);
    }

    /**
     * MELH-005: Só soft-deleted
     */
    public static function onlyTrashed(): QueryBuilder
    {
        return (new QueryBuilder(static::$table, static::$pdo, true))
            ->whereRaw('deleted_at IS NOT NULL');
    }

    /**
     * MELH-005: Restaura um soft-deleted
     */
    public static function restoreById(int $id): bool
    {
        return static::updateById($id, ['deleted_at' => null]);
    }

    /**
     * MELH-005: Deleta permanentemente (force)
     */
    public static function forceDeleteById(int $id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE id = :id";
        $stmt = static::$pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Conta registros (respeita soft delete)
     */
    public static function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM " . static::$table;
        $conditions = [];
        if (static::$softDelete) {
            $conditions[] = "deleted_at IS NULL";
        }
        if ($where) {
            $conditions[] = "({$where})";
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica se existe
     */
    public static function exists(string $where, array $params = []): bool
    {
        return self::count($where, $params) > 0;
    }

    /**
     * Query builder simples
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table, static::$pdo, static::$softDelete);
    }

    /**
     * Executa query raw com prepared statements
     */
    public static function raw(string $sql, array $params = []): PDOStatement
    {
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

/**
 * QueryBuilder — Builder simples para queries com soft delete support
 */
class QueryBuilder
{
    private string $table;
    private PDO $pdo;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $params = [];
    private bool $excludeSoftDelete;

    public function __construct(string $table, PDO $pdo, bool $excludeSoftDelete = true)
    {
        $this->table = $table;
        $this->pdo = $pdo;
        $this->excludeSoftDelete = $excludeSoftDelete;
    }

    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $param = 'p' . count($this->params);
        $this->wheres[] = "{$column} {$operator} :{$param}";
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * Where com condição SQL raw (sem binding)
     */
    public function whereRaw(string $condition): self
    {
        $this->wheres[] = $condition;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $sql .= $this->buildWhere();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimit();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $sql .= $this->buildWhere();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return (int) $stmt->fetchColumn();
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage)->offset($offset);
        $data = $this->get();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    private function buildWhere(): string
    {
        $conditions = [];

        // Auto-exclude soft-deleted if configured
        if ($this->excludeSoftDelete) {
            // Check if table has deleted_at column — we assume it does if enabled
            $conditions[] = 'deleted_at IS NULL';
        }

        // Add user conditions
        foreach ($this->wheres as $w) {
            $conditions[] = $w;
        }

        if (empty($conditions)) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function buildOrderBy(): string
    {
        if (empty($this->orders)) return '';
        return ' ORDER BY ' . implode(', ', $this->orders);
    }

    private function buildLimit(): string
    {
        $sql = '';
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        return $sql;
    }
}