<!-- Reports Index -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Relatórios</h1>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Valor no Pipeline</span>
                <div class="w-10 h-10 rounded-lg bg-green-900/50 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-green-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">R$ <?= number_format($pipelineValue, 2, ',', '.') ?></p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Leads Ativos</span>
                <div class="w-10 h-10 rounded-lg bg-purple-900/50 flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-purple-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format(array_sum(array_column($stageStats, 'count')), 0, ',', '.') ?></p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Conversão</span>
                <div class="w-10 h-10 rounded-lg bg-yellow-900/50 flex items-center justify-center">
                    <i data-lucide="target" class="w-5 h-5 text-yellow-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">
                <?php if ($conversion && $conversion['total'] > 0): ?>
                <?= round(($conversion['won'] / $conversion['total']) * 100, 1) ?>%
                <?php else: ?>0%<?php endif; ?>
            </p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Ganhos / Perdidos</span>
                <div class="w-10 h-10 rounded-lg bg-cyan-900/50 flex items-center justify-center">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-cyan-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">
                <span class="text-green-400"><?= e($conversion['won'] ?? 0) ?></span>
                <span class="text-gray-500 text-lg">/</span>
                <span class="text-red-400"><?= e($conversion['lost'] ?? 0) ?></span>
            </p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pipeline por Estágio -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Pipeline por Estágio</h3>
            <div class="space-y-3">
                <?php foreach ($stageStats as $stage): ?>
                <?php
                    $maxCount = max(array_column($stageStats, 'count')) ?: 1;
                    $widthPct = min(100, ($stage['count'] / $maxCount) * 100);
                ?>
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= e($stage['color']) ?>"></span>
                    <span class="text-sm text-gray-300 w-28 truncate"><?= e($stage['name']) ?></span>
                    <div class="flex-1 bg-gray-800 rounded-full h-6 relative overflow-hidden">
                        <div class="h-6 rounded-full flex items-center px-3 text-xs text-white font-medium" 
                             style="width: <?= $widthPct ?>%; background-color: <?= e($stage['color']) ?>; min-width: <?= $stage['count'] > 0 ? '2rem' : '0' ?>;">
                            <?php if ($stage['count'] > 0): ?><?= e($stage['count']) ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="text-xs text-gray-500 w-24 text-right">
                        <?= $stage['value'] > 0 ? 'R$ ' . number_format($stage['value'], 0, ',', '.') : '—' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Leads por Status -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Leads por Status</h3>
            <div class="space-y-3">
                <?php
                    $statusLabels = ['active' => 'Ativos', 'won' => 'Ganhos', 'lost' => 'Perdidos', 'deleted' => 'Removidos'];
                    $statusColors = ['active' => '#6c5ce7', 'won' => '#00b894', 'lost' => '#d63031', 'deleted' => '#636e72'];
                    $totalStatus = array_sum(array_column($statusStats, 'count')) ?: 1;
                ?>
                <?php foreach ($statusStats as $stat): ?>
                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full" style="background-color: <?= e($statusColors[$stat['status']] ?? '#636e72') ?>"></span>
                        <span class="text-sm text-gray-300"><?= e($statusLabels[$stat['status']] ?? $stat['status']) ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-white"><?= e($stat['count']) ?></span>
                        <span class="text-xs text-gray-500"><?= round(($stat['count'] / $totalStatus) * 100) ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Leads por Fonte -->
        <?php if (!empty($sources)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Leads por Fonte</h3>
            <div class="space-y-3">
                <?php foreach ($sources as $source): ?>
                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                    <span class="text-sm text-gray-300"><?= e($source['source'] ?: 'N/A') ?></span>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-white"><?= e($source['count']) ?></span>
                        <span class="text-xs text-gray-500">R$ <?= number_format($source['value'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Atividades Recentes -->
        <?php if (!empty($recentActivities)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Atividades (30 dias)</h3>
            <div class="space-y-3">
                <?php
                    $actIcons = ['note' => '📝 Notas', 'call' => '📞 Ligações', 'email' => '📧 Emails', 'whatsapp' => '💬 WhatsApp', 'meeting' => '🤝 Reuniões', 'stage_change' => '🔄 Mudanças'];
                ?>
                <?php foreach ($recentActivities as $act): ?>
                <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                    <span class="text-sm text-gray-300"><?= e($actIcons[$act['type']] ?? $act['type']) ?></span>
                    <span class="text-sm font-medium text-white"><?= e($act['count']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ranking Vendedores (Admin/Manager) -->
    <?php if (!empty($rankBySeller)): ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="trophy" class="w-5 h-5 text-yellow-400"></i> Ranking de Vendedores
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Vendedor</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Total Leads</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Ativos</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Valor Pipeline</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankBySeller as $i => $seller): ?>
                    <tr class="border-b border-gray-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-white text-xs font-bold"><?= $i + 1 ?></span>
                                <div>
                                    <p class="text-sm font-medium text-white"><?= e($seller['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= e($seller['role']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-300"><?= e($seller['total_leads']) ?></td>
                        <td class="px-4 py-3 text-right text-sm text-gray-300"><?= e($seller['active_leads']) ?></td>
                        <td class="px-4 py-3 text-right text-sm text-green-400">R$ <?= number_format($seller['pipeline_value'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>