<!-- Prospec History — Histórico de buscas do Prospector API -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/prospec" class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Histórico de Prospecção</h1>
        </div>
        <a href="/prospec" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Nova Busca
        </a>
    </div>

    <?php if (empty($searches)): ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl">
        <div class="text-center py-16 text-gray-500">
            <i data-lucide="search" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <p class="text-lg font-medium">Nenhuma busca realizada</p>
            <p class="text-sm mt-1">Comece buscando empresas por nicho e localização</p>
            <a href="/prospec" class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                Nova Busca
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">ID</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Nicho</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Local</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Resultados</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Site</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Insta</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Maps</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Data</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searches as $search): ?>
                    <?php
                        $sid    = $search['search_id'] ?? '';
                        $sNiche = $search['niche'] ?? '';
                        $sCity  = $search['city'] ?? '';
                        $sState = $search['state'] ?? '';
                        $sTotal = $search['total_results'] ?? 0;
                        $sStatus= $search['status'] ?? 'unknown';
                        $sTs    = $search['timestamp'] ?? '';
                    ?>
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3 text-xs font-mono text-gray-500"><?= e(substr($sid, 0, 8)) ?></td>
                        <td class="px-4 py-3 text-sm text-white font-medium"><?= e(ucfirst($sNiche)) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?= e(ucfirst($sCity)) ?><?= $sState ? '/' . e($sState) : '' ?></td>
                        <td class="px-4 py-3 text-sm text-right text-gray-300"><?= e($sTotal) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-cyan-400"><?= e($search['com_site'] ?? 0) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-purple-400"><?= e($search['com_instagram'] ?? 0) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-green-400"><?= e($search['com_maps'] ?? 0) ?></td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full <?php
                            $sClassMap = [
                                'discovering' => 'bg-blue-900/50 text-blue-400',
                                'discovery' => 'bg-blue-900/50 text-blue-400',
                                'enriching' => 'bg-cyan-900/50 text-cyan-400',
                                'enriched' => 'bg-cyan-900/50 text-cyan-400',
                                'scoring' => 'bg-yellow-900/50 text-yellow-400',
                                'scored' => 'bg-yellow-900/50 text-yellow-400',
                                'analyzing_leads' => 'bg-orange-900/50 text-orange-400',
                                'market_analyzed' => 'bg-orange-900/50 text-orange-400',
                                'analyzed' => 'bg-green-900/50 text-green-400',
                                'completed' => 'bg-green-900/50 text-green-400',
                            ];
                            echo $sClassMap[$sStatus] ?? 'bg-gray-800 text-gray-400';
                            ?>">
                                <?php
                                $sLabelMap = [
                                    'discovering' => 'Descobrindo',
                                    'discovery' => 'Descobrindo',
                                    'enriching' => 'Enriquecendo',
                                    'enriched' => 'Enriquecido',
                                    'scoring' => 'Pontuando',
                                    'scored' => 'Pontuado',
                                    'analyzing_leads' => 'Analisando',
                                    'market_analyzed' => 'Analisado',
                                    'analyzed' => 'Analisado',
                                    'completed' => 'Concluído',
                                ];
                                echo e($sLabelMap[$sStatus] ?? ucfirst($sStatus));
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= $sTs ? date('d/m/Y H:i', strtotime($sTs)) : '—' ?></td>
                        <td class="px-4 py-3">
                            <a href="/prospec/session/<?= e($sid) ?>" class="text-gray-500 hover:text-white transition">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function statusBadgeClass(status) {
    const map = {
        'discovering': 'bg-blue-900/50 text-blue-400',
        'discovery':   'bg-blue-900/50 text-blue-400',
        'enriching':   'bg-cyan-900/50 text-cyan-400',
        'enriched':    'bg-cyan-900/50 text-cyan-400',
        'scoring':     'bg-yellow-900/50 text-yellow-400',
        'scored':      'bg-yellow-900/50 text-yellow-400',
        'analyzing_leads': 'bg-orange-900/50 text-orange-400',
        'market_analyzed': 'bg-orange-900/50 text-orange-400',
        'analyzed':    'bg-green-900/50 text-green-400',
        'completed':   'bg-green-900/50 text-green-400',
    };
    return map[status] || 'bg-gray-800 text-gray-400';
}

function statusLabel(status) {
    const map = {
        'discovering': 'Descobrindo',
        'discovery':   'Descoberta',
        'enriching':   'Enriquecendo',
        'enriched':    'Enriquecido',
        'scoring':     'Pontuando',
        'scored':      'Pontuado',
        'analyzing_leads': 'Analisando IA',
        'market_analyzed': 'Mercado Analisado',
        'analyzed':    'Analisado',
        'completed':   'Concluído',
    };
    return map[status] || status;
}
</script>