<?php
/**
 * ErrorCodes — Catálogo de erros padronizado (MELH-002 / BACK-25)
 * 
 * Cada erro tem: código, mensagem padrão, HTTP status
 */

namespace App\Core;

class ErrorCodes
{
    // ── Auth ──────────────────────────────────────────
    public const AUTH_001 = ['message' => 'Email ou senha incorretos.', 'status' => 401];
    public const AUTH_002 = ['message' => 'Conta desativada. Contate o administrador.', 'status' => 403];
    public const AUTH_003 = ['message' => 'Token CSRF inválido. Recarregue a página.', 'status' => 419];
    public const AUTH_004 = ['message' => 'Sessão expirada. Faça login novamente.', 'status' => 401];
    public const AUTH_005 = ['message' => 'Email já cadastrado.', 'status' => 409];
    public const AUTH_006 = ['message' => 'Link de recuperação inválido ou expirado.', 'status' => 400];
    public const AUTH_007 = ['message' => 'Acesso negado.', 'status' => 403];

    // ── Lead ──────────────────────────────────────────
    public const LEAD_001 = ['message' => 'Lead não encontrado.', 'status' => 404];
    public const LEAD_002 = ['message' => 'Transição de estágio inválida.', 'status' => 422];
    public const LEAD_003 = ['message' => 'Limite de leads do plano atingido.', 'status' => 429];
    public const LEAD_004 = ['message' => 'Lead já existe nesta empresa.', 'status' => 409];

    // ── Company ───────────────────────────────────────
    public const COMPANY_001 = ['message' => 'Empresa não encontrada.', 'status' => 404];
    public const COMPANY_002 = ['message' => 'CNPJ já cadastrado.', 'status' => 409];

    // ── Prospec ────────────────────────────────────────
    public const PROSPEC_001 = ['message' => 'Falha na busca. Tente novamente.', 'status' => 502];
    public const PROSPEC_002 = ['message' => 'Falha no enriquecimento de dados.', 'status' => 502];
    public const PROSPEC_003 = ['message' => 'Timeout na análise IA. Tente novamente.', 'status' => 504];

    // ── Rate Limit ────────────────────────────────────
    public const RATE_001 = ['message' => 'Muitas requisições. Aguarde alguns minutos.', 'status' => 429];
    public const RATE_002 = ['message' => 'Muitas tentativas de login. Aguarde 15 minutos.', 'status' => 429];

    // ── Validation ───────────────────────────────────
    public const VALID_001 = ['message' => 'Dados inválidos. Verifique os campos.', 'status' => 422];
    public const VALID_002 = ['message' => 'Campos obrigatórios não preenchidos.', 'status' => 422];

    // ── System ────────────────────────────────────────
    public const SYS_001 = ['message' => 'Erro interno. Tente novamente mais tarde.', 'status' => 500];
    public const SYS_002 = ['message' => 'Serviço indisponível. Tente novamente.', 'status' => 503];

    /**
     * Retorna mensagem de um código de erro
     */
    public static function message(string $code): string
    {
        $constant = "self::{$code}";
        if (defined($constant)) {
            return constant($constant)['message'];
        }
        return 'Erro desconhecido.';
    }

    /**
     * Retorna HTTP status de um código de erro
     */
    public static function status(string $code): int
    {
        $constant = "self::{$code}";
        if (defined($constant)) {
            return constant($constant)['status'];
        }
        return 500;
    }

    /**
     * Retorna código + mensagem para resposta JSON
     */
    public static function json(string $code, ?string $detail = null): array
    {
        return [
            'error_code' => $code,
            'message' => self::message($code) . ($detail ? ' ' . $detail : ''),
        ];
    }

    /**
     * Retorna mensagem para Flash message (uso em views)
     */
    public static function flash(string $code): string
    {
        return self::message($code);
    }
}
