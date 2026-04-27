<?php
/**
 * Testes — PipelineRules
 */

class TestPipelineRules
{
    public static function run(): void
    {
        if (!class_exists('App\Core\PipelineRules')) {
            require_once __DIR__ . '/../app/Core/PipelineRules.php';
        }

        $cls = 'App\Core\PipelineRules';

        // Estágios: 1=Novo, 2=Contatado, 3=Respondendo, 4=Reunião, 5=Proposta, 6=Fechado, 7=Perdido

        // Transições válidas: Novo → Contatado
        assert_true('Novo→Contatado', $cls::canTransition(1, 2));

        // Novo → Respondendo (pular etapa deve falhar)
        assert_false('Novo→Respondendo (skip)', $cls::canTransition(1, 3));

        // Contatado → Respondendo
        assert_true('Contatado→Respondendo', $cls::canTransition(2, 3));

        // Respondendo → Reunião
        assert_true('Respondendo→Reunião', $cls::canTransition(3, 4));

        // Reunião → Proposta
        assert_true('Reunião→Proposta', $cls::canTransition(4, 5));

        // Proposta → Fechado
        assert_true('Proposta→Fechado', $cls::canTransition(5, 6));

        // Proposta → Perdido
        assert_true('Proposta→Perdido', $cls::canTransition(5, 7));

        // Fechado é estágio final — não pode mover
        assert_true('Fechado é final', $cls::isFinalStage(6));
        assert_false('Fechado→Proposta', $cls::canTransition(6, 5));
        assert_false('Fechado→Novo', $cls::canTransition(6, 1));

        // Contatado → Novo é válido (reabrir)
        assert_true('Contatado→Novo (reabrir)', $cls::canTransition(2, 1));

        // Get stage name
        assert_equals('Stage name 1', 'Novo', $cls::getStageName(1));
        assert_equals('Stage name 6', 'Fechado', $cls::getStageName(6));
        assert_equals('Stage name 7', 'Perdido', $cls::getStageName(7));

        // Get valid transitions
        $fromNovo = $cls::getValidTransitions(1);
        assert_not_empty('Transitions from Novo', $fromNovo);
        assert_true('Novo→Contatado in transitions', in_array(2, $fromNovo));

        // Novo não é final
        assert_false('Novo não é final', $cls::isFinalStage(1));
    }
}