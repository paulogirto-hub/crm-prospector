<?php
/**
 * TestPipelineRules — Testa PipelineRules::canTransition e getValidTransitions
 */

namespace App\Tests;

use App\Core\PipelineRules;

class TestPipelineRules extends TestCase
{
    public function runAll(): void
    {
        echo "  ▶ PipelineRules::canTransition\n";

        // Transições válidas
        $this->assertTrue(PipelineRules::canTransition(1, 2), 'Novo → Contatado');
        $this->assertTrue(PipelineRules::canTransition(1, 7), 'Novo → Perdido');
        $this->assertTrue(PipelineRules::canTransition(2, 3), 'Contatado → Respondendo');
        $this->assertTrue(PipelineRules::canTransition(2, 1), 'Contatado → Novo');
        $this->assertTrue(PipelineRules::canTransition(3, 4), 'Respondendo → Reunião');
        $this->assertTrue(PipelineRules::canTransition(4, 5), 'Reunião → Proposta');
        $this->assertTrue(PipelineRules::canTransition(5, 6), 'Proposta → Fechado');
        $this->assertTrue(PipelineRules::canTransition(7, 1), 'Perdido → Novo (reabrir)');
        $this->assertTrue(PipelineRules::canTransition(1, 1), 'Novo → Novo (mesmo estágio)');
        $this->assertTrue(PipelineRules::canTransition(6, 6), 'Fechado → Fechado (mesmo estágio)');

        // Transições inválidas (nunca deve permitir pulo de estágio)
        $this->assertFalse(PipelineRules::canTransition(1, 3), 'Novo → Respondendo (inválido)');
        $this->assertFalse(PipelineRules::canTransition(1, 4), 'Novo → Reunião (inválido)');
        $this->assertFalse(PipelineRules::canTransition(1, 5), 'Novo → Proposta (inválido)');
        $this->assertFalse(PipelineRules::canTransition(1, 6), 'Novo → Fechado (inválido - CRÍTICO)');
        $this->assertFalse(PipelineRules::canTransition(6, 1), 'Fechado → Novo (inválido)');
        $this->assertFalse(PipelineRules::canTransition(6, 7), 'Fechado → Perdido (inválido)');
        $this->assertFalse(PipelineRules::canTransition(6, 2), 'Fechado → Contatado (inválido)');
        $this->assertFalse(PipelineRules::canTransition(2, 5), 'Contatado → Proposta (inválido)');
        $this->assertFalse(PipelineRules::canTransition(5, 2), 'Proposta → Contatado (inválido)');
        $this->assertFalse(PipelineRules::canTransition(3, 6), 'Respondendo → Fechado (inválido)');

        echo "  ▶ PipelineRules::getValidTransitions\n";
        $this->assertEqual('Estágio 1 (Novo)', [2, 7], PipelineRules::getValidTransitions(1));
        $this->assertEqual('Estágio 2 (Contatado)', [1, 3, 7], PipelineRules::getValidTransitions(2));
        $this->assertEqual('Estágio 3 (Respondendo)', [2, 4, 7], PipelineRules::getValidTransitions(3));
        $this->assertEqual('Estágio 4 (Reunião)', [3, 5, 7], PipelineRules::getValidTransitions(4));
        $this->assertEqual('Estágio 5 (Proposta)', [4, 6, 7], PipelineRules::getValidTransitions(5));
        $this->assertEqual('Estágio 6 (Fechado)', [], PipelineRules::getValidTransitions(6));
        $this->assertEqual('Estágio 7 (Perdido)', [1], PipelineRules::getValidTransitions(7));
        $this->assertEqual('Estágio inexistente', [], PipelineRules::getValidTransitions(99));

        echo "  ▶ PipelineRules::isFinalStage\n";
        $this->assertFalse(PipelineRules::isFinalStage(1), 'Novo não é final');
        $this->assertFalse(PipelineRules::isFinalStage(5), 'Proposta não é final');
        $this->assertTrue(PipelineRules::isFinalStage(6), 'Fechado é final');
        $this->assertFalse(PipelineRules::isFinalStage(7), 'Perdido não é final');

        echo "  ▶ PipelineRules::getStageName\n";
        $this->assertEqual('Nome estágio 1', 'Novo', PipelineRules::getStageName(1));
        $this->assertEqual('Nome estágio 2', 'Contatado', PipelineRules::getStageName(2));
        $this->assertEqual('Nome estágio 3', 'Respondendo', PipelineRules::getStageName(3));
        $this->assertEqual('Nome estágio 4', 'Reunião', PipelineRules::getStageName(4));
        $this->assertEqual('Nome estágio 5', 'Proposta', PipelineRules::getStageName(5));
        $this->assertEqual('Nome estágio 6', 'Fechado', PipelineRules::getStageName(6));
        $this->assertEqual('Nome estágio 7', 'Perdido', PipelineRules::getStageName(7));
        $this->assertEqual('Nome estágio 99', 'Estágio 99', PipelineRules::getStageName(99));

        echo "  ▶ PipelineRules::getValidTargetStages (inclui o próprio para edição)\n";
        $targets1 = PipelineRules::getValidTargetStages(1);
        $this->assertTrue(in_array(1, $targets1) && in_array(2, $targets1) && in_array(7, $targets1), 'Novo inclui a si mesmo + válidos');
        $targets6 = PipelineRules::getValidTargetStages(6);
        $this->assertTrue(in_array(6, $targets6) && count($targets6) === 1, 'Fechado só inclui a si mesmo (não pode mover)');
    }
}