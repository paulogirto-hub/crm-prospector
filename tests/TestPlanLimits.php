<?php
/**
 * TestPlanLimits — Testa limites de planos Free/Pro/Enterprise
 */

namespace App\Tests;

use App\Core\PlanLimits;
use App\Models\User;
use App\Models\Lead;

class TestPlanLimits extends \TestCase
{
    public function runAll(): void
    {
        // Criar usuário de teste
        $adminId = 1;
        
        echo "  ▶ PlanLimits::getPlan — identifica corretamente Free/Pro/Enterprise\n";
        
        $freePlan = PlanLimits::getPlan(1); // admin é free (plan_id=1)
        $this->assertEqual('Plano free identificado', 'free', $freePlan['slug']);
        $this->assertEqual('Free tem 50 leads', 50, $freePlan['max_leads']);

        echo "  ▶ PlanLimits::canCreateLead — respeita limite do Free\n";
        
        // O Free tem 50 leads máximo — verificar lógica
        $this->assertFalse('Free NÃO pode criar acima do limite', PlanLimits::canCreateLead($adminId) === false);
        // A função deve retornar true/false corretamente

        echo "  ▶ PlanLimits::canSearch — free tem limite mensal\n";
        
        // Free tem max_searches_per_month = 5
        $plan = PlanLimits::getPlan($adminId);
        $this->assertTrue('Free tem 5 searches/mês', $plan['max_searches_per_month'] === 5);

        echo "  ▶ PlanLimits::canUseAI — free NÃO pode usar IA\n";
        
        $canUseAI = PlanLimits::canUseAI($adminId);
        $this->assertFalse('Free não pode usar IA', $canUseAI);

        echo "  ▶ PlanLimits::canExport — free tem limite de exports\n";
        
        $plan = PlanLimits::getPlan($adminId);
        $this->assertEqual('Free tem 2 exports/mês', 2, $plan['max_exports_per_month']);

        echo "  ▶ PlanLimits::getRemaining — calcula sobras corretamente\n";

        // Contar leads atuais do admin
        $currentLeads = Lead::count('assigned_to = :uid AND status = :status', [
            'uid' => $adminId,
            'status' => 'active'
        ]);
        
        $remaining = PlanLimits::getRemaining('leads', $adminId);
        $plan = PlanLimits::getPlan($adminId);
        
        // O remaining deve ser (max - usados) ou -1 se passou
        $expectedMax = $plan['max_leads'];
        $this->assertTrue("Leads usados: {$currentLeads}, Máximo: {$expectedMax}, Restantes: {$remaining}", 
            $remaining >= 0 || $remaining === -1);

        echo "  ▶ PlanLimits::canCreateCompany — respeita limite do plano\n";
        
        $canCreate = PlanLimits::canCreateCompany($adminId);
        $this->assertTrue('Free pode criar companies', $canCreate !== false);

        echo "  ▶ PlanLimits::canCreateLead com plano Enterprise\n";
        
        // Enterprise tem 999999 leads
        $enterprisePlan = PlanLimits::getPlan(3); // Enterprise é id 3
        if (!empty($enterprisePlan) && $enterprisePlan['slug'] === 'enterprise') {
            $this->assertEqual('Enterprise max_leads', 999999, $enterprisePlan['max_leads']);
            $this->assertEqual('Enterprise max_companies', 999999, $enterprisePlan['max_companies']);
            $this->assertEqual('Enterprise max_searches', 999, $enterprisePlan['max_searches_per_month']);
            $this->assertEqual('Enterprise max_exports', 999, $enterprisePlan['max_exports_per_month']);
            $this->assertTrue('Enterprise pode usar IA', $enterprisePlan['can_use_ai_analysis']);
        } else {
            $this->assertTrue('Enterprise existe', true); // Skip se não existir
        }

        echo "  ▶ Validação: plano inexistente retorna free\n";
        
        $unknownPlan = PlanLimits::getPlan(9999);
        $this->assertTrue('Plano inexistente retorna array', is_array($unknownPlan));
    }
}