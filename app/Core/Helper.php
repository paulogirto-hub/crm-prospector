<?php
/**
 * Helper — Funções utilitárias globais (classe)
 */

namespace App\Core;

class Helper
{
    /**
     * Escape HTML (XSS protection)
     */
    public static function e(mixed $value): string
    {
        if ($value === null) return '';
        if (is_array($value)) return e(json_encode($value, JSON_UNESCAPED_UNICODE));
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Gera slug a partir de texto
     */
    public static function slugify(string $text): string
    {
        $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', mb_strtolower($text));
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Formata CNPJ
     */
    public static function formatCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14) return $cnpj;
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    /**
     * Formata telefone
     */
    public static function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($phone);
        if ($len === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        }
        if ($len === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $phone;
    }

    /**
     * Trunca texto
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length) . $suffix;
    }

    /**
     * Formata data relativa (há 2 horas, há 3 dias, etc)
     */
    public static function timeAgo(string $datetime): string
    {
        $now = time();
        $time = strtotime($datetime);
        $diff = $now - $time;

        if ($diff < 60) return 'agora';
        if ($diff < 3600) return floor($diff / 60) . ' min atrás';
        if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
        if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
        if ($diff < 2592000) return floor($diff / 604800) . ' sem atrás';
        return date('d/m/Y', $time);
    }

    /**
     * Formata moeda (BRL)
     */
    public static function money(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * Formata número
     */
    public static function number(int|float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /**
     * Formata data
     */
    public static function formatDate(string $datetime, string $format = 'd/m/Y H:i'): string
    {
        return date($format, strtotime($datetime));
    }
}