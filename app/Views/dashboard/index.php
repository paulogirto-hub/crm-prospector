<!-- Dashboard -->
<div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Leads -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Total de Leads</span>
                <div class="w-10 h-10 rounded-lg bg-purple-900/50 flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-purple-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format($totalLeads, 0, ',', '.') ?></p>
            <?php if ($totalLeads === 0): ?>
            <p class="text-xs text-gray-500 mt-1">Dados serão exibidos quando houver leads</p>
            <?php endif; ?>
        </div>
        
        <!-- Novos esta semana -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Novos esta semana</span>
                <div class="w-10 h-10 rounded-lg bg-cyan-900/50 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-5 h-5 text-cyan-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format($newThisWeek, 0, ',', '.') ?></p>
            <?php if ($newThisWeek > 0): ?>
            <p class="text-xs text-green-400 mt-1">+<?= $newThisWeek ?> esta semana</p>
            <?php else: ?>
            <p class="text-xs text-gray-500 mt-1">Nenhum lead novo</p>
            <?php endif; ?>
        </div>
        
        <!-- Pipeline Valor -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Valor no Pipeline</span>
                <div class="w-10 h-10 rounded-lg bg-green-900/50 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-green-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">R$ <?= number_format($pipelineValue, 2, ',', '.') ?></p>
            <?php if ($pipelineValue === 0.0): ?>
            <p class="text-xs text-gray-500 mt-1">Adicione valores estimados aos leads</p>
            <?php endif; ?>
        </div>
        
        <!-- Taxa Conversão -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Taxa de Conversão</span>
                <div class="w-10 h-10 rounded-lg bg-yellow-900/50 flex items-center justify-center">
                    <i data-lucide="target" class="w-5 h-5 text-yellow-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= $conversionRate ?>%</p>
            <?php if ($conversionRate === 0): ?>
            <p class="text-xs text-gray-500 mt-1">Calc com base em leads fechados</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pipeline + Tarefas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pipeline Overview -->
        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Pipeline</h3>
            <?php if (!empty($leadsByStage)): ?>
            <div class="space-y-3">
                <?php foreach ($leadsByStage as $stage): ?>
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full" style="background-color: <?= e($stage['color']) ?>"></span>
                    <span class="text-sm text-gray-300 w-28"><?= e($stage['name']) ?></span>
                    <div class="flex-1 bg-gray-800 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full" 
                             style="width: <?= $totalLeads > 0 ? min(100, ($stage['count'] / max($totalLeads, 1)) * 100) : 0 ?>%; background-color: <?= e($stage['color']) ?>"></div>
                    </div>
                    <span class="text-sm font-medium text-white w-8 text-right"><?= e($stage['count']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-gray-500">
                <i data-lucide="bar-chart-3" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                <p class="text-sm">Dados serão exibidos quando houver leads no pipeline</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tarefas Pendentes -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Tarefas</h3>
                <?php if ($pendingTasks > 0): ?>
                <span class="text-xs bg-red-900 text-red-300 px-2 py-1 rounded-full"><?= $pendingTasks ?> pendente<?= $pendingTasks > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($tasks)): ?>
            <div class="space-y-3">
                <?php foreach ($tasks as $task): ?>
                <div class="flex items-start gap-3 p-3 bg-gray-800 rounded-lg">
                    <div class="w-2 h-2 rounded-full bg-yellow-400 mt-2 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white truncate"><?= e($task['title']) ?></p>
                        <?php if ($task['due_date']): ?>
                        <p class="text-xs text-gray-500 mt-0.5"><?= date('d/m/Y', strtotime($task['due_date'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                <p class="text-sm">Nenhuma tarefa pendente</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Gráfico Placeholder -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Evolução de Leads</h3>
        <div class="flex items-center justify-center h-48 text-gray-600 border-2 border-dashed border-gray-800 rounded-lg">
            <div class="text-center">
                <i data-lucide="line-chart" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                <p class="text-sm">Gráfico será implementado na Fase 4</p>
            </div>
        </div>
    </div>
</div>