<?php
/**
 * DashboardService — Métricas e estatísticas do dashboard
 * MELH-001: Extraído de DashboardController
 */

namespace App\Services;

use App\Core\Model;
use App\Core\Auth;

class DashboardService
{
    /**
     * Retorna todas as métricas do dashboard
     */
    public static function getMetrics(): array
    {
        $pdo = Model::getPdo();
        $userId = Auth::userId();
        $isAdmin = Auth::isAdmin();

        // Base filter: admin vê tudo, outros só seus leads
        $leadFilter = $isAdmin ? '' : " AND assigned_to = {$userId}";

        // Total de leads ativos (não soft-deleted)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL{$leadFilter}");
        $stmt->execute();
        $totalLeads = (int)$stmt->fetchColumn();

        // Leads por estágio
        $stmt = $pdo->prepare(
            "SELECT ps.name, ps.color, COUNT(l.id) as count 
             FROM pipeline_stages ps 
             LEFT JOIN leads l ON l.pipeline_stage_id = ps.id AND l.deleted_at IS NULL{$leadFilter}
             GROUP BY ps.id, ps.name, ps.color 
             ORDER BY ps.position"
        );
        $stmt->execute();
        $leadsByStage = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Leads novos esta semana
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE created_at >= NOW() - INTERVAL '7 days' AND deleted_at IS NULL{$leadFilter}");
        $stmt->execute();
        $newLeadsWeek = (int)$stmt->fetchColumn();

        // Valor estimado total
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(estimated_value), 0) FROM leads WHERE deleted_at IS NULL{$leadFilter}");
        $stmt->execute();
        $totalValue = (float)$stmt->fetchColumn();

        // Leads convertidos (estágio "Fechado")
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE pipeline_stage_id = 6 AND deleted_at IS NULL{$leadFilter}");
        $stmt->execute();
        $closedLeads = (int)$stmt->fetchColumn();

        // Taxa de conversão
        $conversionRate = $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 1) : 0;

        // Empresas cadastradas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalCompanies = (int)$stmt->fetchColumn();

        // Atividades recentes (últimas 10)
        $activityFilter = $isAdmin ? '' : " AND la.user_id = {$userId}";
        $stmt = $pdo->prepare(
            "SELECT la.*, u.name as user_name 
             FROM lead_activities la 
             LEFT JOIN users u ON la.user_id = u.id 
             WHERE 1=1{$activityFilter}
             ORDER BY la.created_at DESC 
             LIMIT 10"
        );
        $stmt->execute();
        $recentActivities = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Tarefas pendentes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE completed = false AND deleted_at IS NULL AND user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $pendingTasks = (int)$stmt->fetchColumn();

        return [
            'total_leads' => $totalLeads,
            'leads_by_stage' => $leadsByStage,
            'new_leads_week' => $newLeadsWeek,
            'total_value' => $totalValue,
            'closed_leads' => $closedLeads,
            'conversion_rate' => $conversionRate,
            'total_companies' => $totalCompanies,
            'recent_activities' => $recentActivities,
            'pending_tasks' => $pendingTasks,
        ];
    }

    /**
     * Métricas rápidas para cards
     */
    public static function getQuickStats(): array
    {
        $pdo = Model::getPdo();
        $userId = Auth::userId();
        $isAdmin = Auth::isAdmin();
        $leadFilter = $isAdmin ? '' : " AND assigned_to = {$userId}";

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL{$leadFilter}");
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND pipeline_stage_id = 1{$leadFilter}");
        $stmt->execute();
        $new = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND pipeline_stage_id = 6{$leadFilter}");
        $stmt->execute();
        $won = (int)$stmt->fetchColumn();

        return [
            'total' => $total,
            'new' => $new,
            'won' => $won,
        ];
    }
}