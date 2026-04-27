<?php
/**
 * PipelineRules — Regras de transição do pipeline
 * 
 * Controla quais transições de estágio são válidas.
 * Pipeline stages: 1=Novo, 2=Contatado, 3=Respondendo, 4=Reunião, 5=Proposta, 6=Fechado, 7=Perdido
 */

namespace App\Core;

class PipelineRules
{
    /**
     * Transições válidas (de → [para])
     */
    private static array $transitions = [
        1 => [2, 7],         // Novo → Contatado ou Perdido
        2 => [1, 3, 7],      // Contatado → Novo, Respondendo ou Perdido
        3 => [2, 4, 7],      // Respondendo → Contatado, Reunião ou Perdido
        4 => [3, 5, 7],      // Reunião → Respondendo, Proposta ou Perdido
        5 => [4, 6, 7],      // Proposta → Reunião, Fechado ou Perdido
        6 => [],             // Fechado → NADA (final)
        7 => [1],            // Perdido → Novo (reabrir)
    ];

    /**
     * Nomes dos estágios para mensagens de erro
     */
    private static array $stageNames = [
        1 => 'Novo',
        2 => 'Contatado',
        3 => 'Respondendo',
        4 => 'Reunião',
        5 => 'Proposta',
        6 => 'Fechado',
        7 => 'Perdido',
    ];

    /**
     * Verifica se a transição de $from para $to é permitida
     */
    public static function canTransition(int $from, int $to): bool
    {
        // Mesmo estágio = sem mudança
        if ($from === $to) {
            return true;
        }

        $allowed = self::$transitions[$from] ?? [];
        return in_array($to, $allowed);
    }

    /**
     * Retorna os estágios para os quais é possível transicionar a partir de $from
     */
    public static function getValidTransitions(int $from): array
    {
        return self::$transitions[$from] ?? [];
    }

    /**
     * Verifica se o estágio é final (Fechado)
     */
    public static function isFinalStage(int $stageId): bool
    {
        return $stageId === 6;
    }

    /**
     * Retorna o nome do estágio
     */
    public static function getStageName(int $stageId): string
    {
        return self::$stageNames[$stageId] ?? "Estágio {$stageId}";
    }

    /**
     * Retorna mensagem de erro amigável para transição inválida
     */
    public static function getTransitionErrorMessage(int $from, int $to): string
    {
        $fromName = self::getStageName($from);
        $toName = self::getStageName($to);

        if (self::isFinalStage($from)) {
            return "Lead em '{$fromName}' é final e não pode ser movido.";
        }

        $validTransitions = self::getValidTransitions($from);
        if (empty($validTransitions)) {
            return "Transição não permitida. Lead em '{$fromName}' não pode ser movido.";
        }

        $validNames = array_map(fn($id) => self::getStageName($id), $validTransitions);
        return "Transição não permitida. De '{$fromName}' só pode ir para: " . implode(', ', $validNames) . ".";
    }

    /**
     * Retorna todos os estágios válidos para transição (incluindo o atual)
     * Útil para popular selects no frontend
     */
    public static function getValidTargetStages(int $from): array
    {
        $valid = self::getValidTransitions($from);
        // Inclui o estágio atual como opção (manter no mesmo)
        if (!in_array($from, $valid)) {
            array_unshift($valid, $from);
        }
        return $valid;
    }
}