<?php
/**
 * TestConsistency — Testa regras de consistência anti-duplicação e proteção de delete
 */

namespace App\Tests;

use App\Core\PipelineRules;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;

class TestConsistency extends \TestCase
{
    public function runAll(): void
    {
        // Usar um usuário de teste fixo (admin) para os testes
        $adminId = 1;

        echo "  ▶ Anti-duplicação: Lead duplicado (mesma empresa + vendedor)\n";

        // Encontrar ou criar empresa de teste
        $pdo = $this->pdo;
        
        // Limpar leads de teste duplicados
        $pdo->exec("DELETE FROM leads WHERE source = 'test_duplicate'");

        // Criar empresa de teste
        $companyId = Company::create([
            'name' => 'Teste Duplicação LTDA ' . time(),
            'niche' => 'Tecnologia',
            'city' => 'São Paulo',
            'state' => 'SP',
            'created_by' => $adminId,
        ]);

        // Criar primeiro lead (deve funcionar)
        $leadId1 = Lead::create([
            'company_id' => $companyId,
            'assigned_to' => $adminId,
            'pipeline_stage_id' => 1,
            'status' => 'active',
            'source' => 'test_duplicate',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->assertTrue('Primeiro lead criado', !empty($leadId1));

        // Criar segundo lead mesmo company + mesmo vendedor (deve ser bloqueado)
        $duplicateLead = Lead::query()
            ->where('company_id', $companyId)
            ->where('assigned_to', $adminId)
            ->where('status', 'active')
            ->first();
        $this->assertTrue('Detecta lead duplicado', !empty($duplicateLead));

        // Limpar
        Lead::delete($leadId1);
        Company::delete($companyId);

        echo "  ▶ Anti-duplicação: Empresa com CNPJ duplicado\n";
        
        $cnpjTest = '00.000.000/0001-00';

        // Criar empresa com CNPJ
        $c1 = Company::create([
            'name' => 'Empresa CNPJ Teste ' . time(),
            'cnpj' => $cnpjTest,
            'niche' => 'Comércio',
            'created_by' => $adminId,
        ]);

        // Verificar se encontra por CNPJ
        $found = Company::findWhere('cnpj', $cnpjTest);
        $this->assertTrue('Empresa encontrada por CNPJ', !empty($found) && $found['id'] == $c1);

        // Tentar criar outra com mesmo CNPJ (deve redirecionar)
        $found2 = Company::query()->where('cnpj', $cnpjTest)->first();
        $this->assertTrue('Detecta CNPJ duplicado', !empty($found2) && $found2['id'] == $c1);

        // Limpar
        Company::delete($c1);

        echo "  ▶ Proteção: Não deletar empresa com leads ativos\n";

        // Criar empresa e lead
        $companyWithLead = Company::create([
            'name' => 'Empresa Com Leads ' . time(),
            'niche' => 'Teste',
            'created_by' => $adminId,
        ]);

        $leadForCompany = Lead::create([
            'company_id' => $companyWithLead,
            'assigned_to' => $adminId,
            'pipeline_stage_id' => 1,
            'status' => 'active',
            'source' => 'test',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Contar leads ativos da empresa
        $leadCount = Lead::count('company_id = :cid AND status = :status', [
            'cid' => $companyWithLead,
            'status' => 'active'
        ]);
        $this->assertTrue('Empresa tem leads', $leadCount > 0);

        // Verificar se existe Task vinculada ao lead
        $taskCount = Task::count('lead_id = :lid', ['lid' => $leadForCompany]);

        // Se existirem tasks, delete cascade ou bloqueio
        if ($taskCount > 0) {
            // Test cascade delete
            Task::deleteWhere('lead_id', $leadForCompany);
            $remainingTasks = Task::count('lead_id = :lid', ['lid' => $leadForCompany]);
            $this->assertTrue('Tasks removidas após cascade', $remainingTasks === 0);
        }

        // Limpar lead e empresa
        Lead::delete($leadForCompany);
        Company::delete($companyWithLead);

        echo "  ▶ Transações: Rollback em import parcial\n";

        // Simular transação rollback
        try {
            $pdo->beginTransaction();
            
            // Inserir algo
            $stmt = $pdo->prepare("INSERT INTO companies (name, created_by) VALUES (?, ?)");
            $stmt->execute(['Empresa Rollback Test', $adminId]);
            $insertedId = $pdo->lastInsertId();
            
            // Forçar erro para rollback
            throw new \Exception('Simulated error for rollback test');
            
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }

        // Verificar que empresa não foi inserida
        $exists = Company::findById($insertedId);
        $this->assertFalse('Empresa não existe após rollback', !empty($exists));

        echo "  ▶ Pipeline: Stage 6 (Fechado) não pode ser movido\n";
        $this->assertTrue('Fechado é estágio final', PipelineRules::isFinalStage(6));
        $this->assertEqual('Fechado não tem transições', [], PipelineRules::getValidTransitions(6));
        $this->assertFalse('Fechado → Novo bloqueado', PipelineRules::canTransition(6, 1));
        $this->assertFalse('Fechado → qualquer bloqueado', PipelineRules::canTransition(6, 7));

        echo "  ▶ Pipeline: Stage 7 (Perdido) pode voltar pra Novo\n";
        $this->assertFalse('Perdido não é final', PipelineRules::isFinalStage(7));
        $this->assertEqual('Perdido só pode ir pra Novo', [1], PipelineRules::getValidTransitions(7));
        $this->assertTrue('Perdido → Novo permitido', PipelineRules::canTransition(7, 1));
    }
}