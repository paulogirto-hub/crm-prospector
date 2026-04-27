<?php
/**
 * DashboardController — Dashboard principal
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Lead;
use App\Models\Task;

class DashboardController extends Controller
{
    /**
     * GET /dashboard — Dashboard principal
     */
    public function index(): void
    {
        $this->requireLogin();

        // KPIs
        $totalLeads = Lead::countActive();
        $newThisWeek = Lead::countNewThisWeek();
        $pipelineValue = Lead::totalEstimatedValue();
        $conversion = Lead::conversionRate();
        
        $conversionRate = 0;
        if ($conversion && $conversion['total'] > 0) {
            $conversionRate = round(($conversion['won'] / $conversion['total']) * 100, 1);
        }

        // Leads por estágio
        $leadsByStage = Lead::countByStage();

        // Tarefas pendentes
        $pendingTasks = Task::countPending(Auth::userId());
        $tasks = Task::pendingByUser(Auth::userId(), 5);

        $this->render('dashboard/index', [
            'title' => 'Dashboard — Prospec CRM',
            'totalLeads' => $totalLeads,
            'newThisWeek' => $newThisWeek,
            'pipelineValue' => $pipelineValue,
            'conversionRate' => $conversionRate,
            'leadsByStage' => $leadsByStage,
            'pendingTasks' => $pendingTasks,
            'tasks' => $tasks,
        ]);
    }
}