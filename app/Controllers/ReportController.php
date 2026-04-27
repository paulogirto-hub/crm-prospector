<?php
/**
 * ReportController — Dashboard de relatórios
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Lead;
use App\Models\Company;
use App\Models\PipelineStage;
use App\Models\LeadActivity;
use App\Models\User;

class ReportController extends Controller
{
    /**
     * GET /reports — Dashboard de relatórios
     */
    public function index(): void
    {
        $this->requireLogin();

        // Conversão por stage
        $stages = PipelineStage::ordered();
        $stageStats = [];
        foreach ($stages as $stage) {
            $count = Lead::count("pipeline_stage_id = :sid AND status = 'active'", ['sid' => $stage['id']]);
            $value = Lead::raw(
                "SELECT COALESCE(SUM(estimated_value), 0) FROM leads WHERE pipeline_stage_id = :sid AND status = 'active'",
                ['sid' => $stage['id']]
            )->fetchColumn();
            $stageStats[] = [
                'name' => $stage['name'],
                'color' => $stage['color'],
                'count' => (int)$count,
                'value' => (float)$value,
            ];
        }

        // Valor total no pipeline
        $pipelineValue = Lead::totalEstimatedValue();

        // Leads por fonte
        $sources = Lead::raw(
            "SELECT source, COUNT(*) as count, COALESCE(SUM(estimated_value), 0) as value 
             FROM leads WHERE status = 'active' GROUP BY source ORDER BY count DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Leads por status
        $statusStats = Lead::raw(
            "SELECT status, COUNT(*) as count FROM leads GROUP BY status ORDER BY count DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Ranking vendedores (se admin/manager)
        $rankBySeller = [];
        if (Auth::isManagerOrAbove()) {
            $rankBySeller = Lead::raw(
                "SELECT u.name, u.role, COUNT(l.id) as total_leads,
                        COUNT(CASE WHEN l.status = 'active' THEN 1 END) as active_leads,
                        COALESCE(SUM(CASE WHEN l.status = 'active' THEN l.estimated_value ELSE 0 END), 0) as pipeline_value
                 FROM users u
                 LEFT JOIN leads l ON l.assigned_to = u.id
                 WHERE u.active = true
                 GROUP BY u.id, u.name, u.role
                 ORDER BY pipeline_value DESC"
            )->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Taxa de conversão
        $conversion = Lead::conversionRate();

        // Atividades recentes (últimos 30 dias)
        $recentActivities = LeadActivity::raw(
            "SELECT la.type, COUNT(*) as count 
             FROM lead_activities la 
             WHERE la.created_at >= NOW() - INTERVAL '30 days'
             GROUP BY la.type ORDER BY count DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('reports/index', [
            'title' => 'Relatórios — Prospec CRM',
            'stageStats' => $stageStats,
            'pipelineValue' => $pipelineValue,
            'sources' => $sources,
            'statusStats' => $statusStats,
            'rankBySeller' => $rankBySeller,
            'conversion' => $conversion,
            'recentActivities' => $recentActivities,
        ]);
    }
}