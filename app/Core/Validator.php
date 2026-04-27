<?php
/**
 * Validator — Validação de dados
 * 
 * Regras: required, email, unique, min, max, confirmed, pattern
 */

namespace App\Core;

class Validator
{
    private static array $errors = [];
    private static ?\PDO $pdo = null;

    /**
     * Valida dados contra regras
     * Retorna array de erros (vazio = válido)
     */
    public static function make(array $data, array $rules): array
    {
        self::$errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRulesList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($fieldRulesList as $rule) {
                self::applyRule($field, $value, $rule, $data);
            }
        }

        return self::$errors;
    }

    /**
     * Aplica uma regra a um campo
     */
    private static function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        // Regras com parâmetros (ex: min:8, unique:users,email)
        [$ruleName, $param] = str_contains($rule, ':') 
            ? explode(':', $rule, 2) 
            : [$rule, null];

        $fieldLabel = self::fieldLabel($field);

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    self::addError($field, "{$fieldLabel} é obrigatório");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    self::addError($field, "{$fieldLabel} deve ser um email válido");
                }
                break;

            case 'unique':
                if (!empty($value) && $param) {
                    [$table, $column] = explode(',', $param) + [1 => $field];
                    if (self::checkUnique($table, $column, $value, $data['id'] ?? null)) {
                        self::addError($field, "{$fieldLabel} já está em uso");
                    }
                }
                break;

            case 'min':
                if (!empty($value) && mb_strlen((string)$value) < (int)$param) {
                    self::addError($field, "{$fieldLabel} deve ter no mínimo {$param} caracteres");
                }
                break;

            case 'max':
                if (!empty($value) && mb_strlen((string)$value) > (int)$param) {
                    self::addError($field, "{$fieldLabel} deve ter no máximo {$param} caracteres");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($data[$confirmField] ?? null)) {
                    self::addError($field, "{$fieldLabel} não confere com a confirmação");
                }
                break;

            case 'pattern':
                if (!empty($value) && !preg_match($param, $value)) {
                    self::addError($field, "{$fieldLabel} formato inválido");
                }
                break;

            case 'in':
                $allowed = explode(',', $param);
                if (!empty($value) && !in_array($value, $allowed)) {
                    self::addError($field, "{$fieldLabel} valor inválido");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    self::addError($field, "{$fieldLabel} deve ser numérico");
                }
                break;
        }
    }

    /**
     * Verifica unicidade no banco
     */
    private static function checkUnique(string $table, string $column, string $value, ?int $excludeId = null): bool
    {
        if (!self::$pdo) return false;

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Adiciona erro
     */
    private static function addError(string $field, string $message): void
    {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = $message;
        }
    }

    /**
     * Verifica se validação falhou
     */
    public static function fails(): bool
    {
        return !empty(self::$errors);
    }

    /**
     * Retorna erros
     */
    public static function errors(): array
    {
        return self::$errors;
    }

    /**
     * Retorna primeiro erro de um campo
     */
    public static function firstError(string $field): ?string
    {
        return self::$errors[$field] ?? null;
    }

    /**
     * Seta a conexão PDO para validação unique
     */
    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Label amigável para campo
     */
    private static function fieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Nome',
            'email' => 'Email',
            'password' => 'Senha',
            'password_confirmation' => 'Confirmação de senha',
            'role' => 'Papel',
            'niche' => 'Nicho',
            'city' => 'Cidade',
            'state' => 'Estado',
            'title' => 'Título',
            'body' => 'Conteúdo',
            'channel' => 'Canal',
        ];
        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    // ─── Static helper methods ───

    /**
     * Valida formato de email
     */
    public static function email(string $v): bool
    {
        return filter_var(trim($v), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida string com min/max
     */
    public static function string(string $v, int $min = 1, int $max = 255): bool
    {
        $len = mb_strlen(trim($v));
        return $len >= $min && $len <= $max;
    }

    /**
     * Valida inteiro
     */
    public static function integer(mixed $v): bool
    {
        return filter_var($v, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Valida telefone brasileiro
     */
    public static function phone(string $v): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $v);
        return strlen($digits) >= 10 && strlen($digits) <= 13;
    }

    /**
     * Sanitiza string: strip_tags + trim + htmlspecialchars
     */
    public static function sanitize(string $v): string
    {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitização profunda para HTML (XSS protection)
     * Remove tags, scripts, events e codificação maliciosa
     */
    public static function sanitizeHtml(string $v): string
    {
        // Remove null bytes
        $v = str_replace(chr(0), '', $v);
        // Strip tags
        $v = strip_tags($v);
        // Remove javascript: e event handlers
        $v = preg_replace('/on\w+\s*=\s*["\'].*?["\']/i', '', $v);
        $v = preg_replace('/javascript\s*:/i', '', $v);
        $v = preg_replace('/vbscript\s*:/i', '', $v);
        $v = preg_replace('/data\s*:/i', '', $v);
        // HTML encode
        $v = htmlspecialchars(trim($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $v;
    }

    /**
     * Sanitiza para uso em URLs (path traversal prevention)
     */
    public static function sanitizePath(string $v): string
    {
        // Remove path traversal
        $v = str_replace(['../', '..\\', '%2e%2e', '..'], '', $v);
        // Remove null bytes
        $v = str_replace(chr(0), '', $v);
        // Only allow safe characters
        $v = preg_replace('/[^a-zA-Z0-9_\\-.]/', '', $v);
        return $v;
    }

    /**
     * Sanitiza para busca SQL (remove wildcards)
     */
    public static function sanitizeSearch(string $v): string
    {
        $v = strip_tags(trim($v));
        // Remove SQL-like wildcards that could be abused
        $v = str_replace(['%', '_', ';', "'", '"', '--'], '', $v);
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitiza CNPJ (apenas dígitos)
     */
    public static function sanitizeCnpj(string $v): string
    {
        return preg_replace('/[^0-9]/', '', $v);
    }

    /**
     * Sanitiza telefone (apenas dígitos e +)
     */
    public static function sanitizePhone(string $v): string
    {
        return preg_replace('/[^0-9+]/', '', $v);
    }
}