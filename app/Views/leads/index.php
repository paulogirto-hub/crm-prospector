<!-- Leads Index -->
<div class="space-y-6" x-data="{ searchQuery: '<?= e($filters['search']) ?>' }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Leads</h1>
            <p class="text-sm text-gray-400 mt-1"><?= number_format($totalLeads, 0, ',', '.') ?> leads ativos</p>
        </div>
        <a href="/leads/create" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Novo Lead
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <form method="GET" action="/leads" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-400 mb-1 block">Buscar</label>
                <input type="text" name="search" value="<?= e($filters['search']) ?>" 
                       placeholder="Nome da empresa ou email..."
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
            </div>
            <div class="min-w-[150px]">
                <label class="text-xs text-gray-400 mb-1 block">Estágio</label>
                <select name="stage" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    <option value="">Todos</option>
                    <?php foreach ($stages as $stage): ?>
                    <option value="<?= e($stage['id']) ?>" <?= $filters['stage'] == $stage['id'] ? 'selected' : '' ?>><?= e($stage['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="text-xs text-gray-400 mb-1 block">Status</label>
                <select name="status" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Ativos</option>
                    <option value="won" <?= $filters['status'] === 'won' ? 'selected' : '' ?>>Ganhos</option>
                    <option value="lost" <?= $filters['status'] === 'lost' ? 'selected' : '' ?>>Perdidos</option>
                    <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
            </button>
            <?php if ($filters['search'] || $filters['stage'] || $filters['status']): ?>
            <a href="/leads" class="px-3 py-2 text-gray-400 hover:text-white text-sm transition">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <?php if (empty($leads['data'])): ?>
        <div class="text-center py-16 text-gray-500">
            <i data-lucide="users" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <p class="text-lg font-medium">Nenhum lead encontrado</p>
            <p class="text-sm mt-1">Crie um novo lead ou ajuste os filtros</p>
            <a href="/leads/create" class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                + Novo Lead
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Empresa</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Estágio</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Nicho</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Cidade</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Score</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Valor</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Criado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3">
                            <a href="/leads/<?= e($lead['id']) ?>" class="text-sm font-medium text-white hover:text-purple-400 transition">
                                <?= e($lead['company_name']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                  style="background-color: <?= e($lead['stage_color']) ?>20; color: <?= e($lead['stage_color']) ?>">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= e($lead['stage_color']) ?>"></span>
                                <?= e($lead['stage_name']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?= e($lead['company_niche'] ?: '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400">
                            <?php if ($lead['company_city']): ?>
                                <?= e($lead['company_city']) ?><?= $lead['company_state'] ? '/' . e($lead['company_state']) : '' ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($lead['score'] > 0): ?>
                            <span class="text-sm font-medium <?= $lead['score'] >= 70 ? 'text-green-400' : ($lead['score'] >= 40 ? 'text-yellow-400' : 'text-red-400') ?>">
                                <?= e($lead['score']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-sm text-gray-600">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-400">
                            <?= $lead['estimated_value'] > 0 ? 'R$ ' . number_format($lead['estimated_value'], 2, ',', '.') : '—' ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?= date('d/m/Y', strtotime($lead['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="/leads/<?= e($lead['id']) ?>" class="text-gray-500 hover:text-white transition" title="Ver">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="/leads/<?= e($lead['id']) ?>/edit" class="text-gray-500 hover:text-white transition" title="Editar">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($leads['last_page'] > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-800">
            <p class="text-sm text-gray-400">
                Mostrando <?= count($leads['data']) ?> de <?= $leads['total'] ?> resultado(s)
            </p>
            <div class="flex gap-1">
                <?php if ($leads['page'] > 1): ?>
                <a href="?page=<?= $leads['page'] - 1 ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&stage=<?= urlencode($filters['stage']) ?>"
                   class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded text-sm transition">←</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $leads['page'] - 2); $i <= min($leads['last_page'], $leads['page'] + 2); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&stage=<?= urlencode($filters['stage']) ?>"
                   class="px-3 py-1.5 rounded text-sm transition <?= $i === $leads['page'] ? 'bg-purple-600 text-white' : 'bg-gray-800 hover:bg-gray-700 text-gray-300' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($leads['page'] < $leads['last_page']): ?>
                <a href="?page=<?= $leads['page'] + 1 ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>&stage=<?= urlencode($filters['stage']) ?>"
                   class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded text-sm transition">→</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>