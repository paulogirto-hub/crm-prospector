<!-- Companies Index -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Empresas</h1>
            <p class="text-sm text-gray-400 mt-1"><?= number_format($companies['total'] ?? 0, 0, ',', '.') ?> empresas cadastradas</p>
        </div>
        <a href="/companies/create" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Nova Empresa
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <form method="GET" action="/companies" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-400 mb-1 block">Buscar</label>
                <input type="text" name="search" value="<?= e($filters['search']) ?>" placeholder="Nome da empresa..."
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
            </div>
            <?php if (!empty($niches)): ?>
            <div class="min-w-[150px]">
                <label class="text-xs text-gray-400 mb-1 block">Nicho</label>
                <select name="niche" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    <option value="">Todos</option>
                    <?php foreach ($niches as $n): ?>
                    <option value="<?= e($n) ?>" <?= $filters['niche'] === $n ? 'selected' : '' ?>><?= e($n) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($cities)): ?>
            <div class="min-w-[150px]">
                <label class="text-xs text-gray-400 mb-1 block">Cidade</label>
                <select name="city" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    <option value="">Todas</option>
                    <?php foreach ($cities as $c): ?>
                    <option value="<?= e($c) ?>" <?= $filters['city'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <?php if (empty($companies['data'])): ?>
        <div class="text-center py-16 text-gray-500">
            <i data-lucide="building-2" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <p class="text-lg font-medium">Nenhuma empresa encontrada</p>
            <a href="/companies/create" class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">+ Nova Empresa</a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Empresa</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Nicho</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Cidade</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Score</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Recursos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies['data'] as $company): ?>
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3">
                            <a href="/companies/<?= e($company['id']) ?>" class="text-sm font-medium text-white hover:text-purple-400 transition"><?= e($company['name']) ?></a>
                            <?php if ($company['cnpj']): ?>
                            <p class="text-xs text-gray-600"><?= e($company['cnpj']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?= e($company['niche'] ?: '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400">
                            <?= e(($company['city'] ?? '') . ($company['state'] ? '/' . $company['state'] : '') ?: '—') ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php $score = $company['score'] ?? 0; ?>
                            <span class="text-sm font-medium <?= $score >= 70 ? 'text-green-400' : ($score >= 40 ? 'text-yellow-400' : 'text-gray-500') ?>"><?= $score ?: '—' ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <?php if (!empty($company['tem_site'])): ?><span class="w-2 h-2 rounded-full bg-cyan-400" title="Site"></span><?php endif; ?>
                                <?php if (!empty($company['tem_instagram'])): ?><span class="w-2 h-2 rounded-full bg-purple-400" title="Instagram"></span><?php endif; ?>
                                <?php if (!empty($company['tem_maps'])): ?><span class="w-2 h-2 rounded-full bg-green-400" title="Maps"></span><?php endif; ?>
                                <?php if (!empty($company['tem_ads'])): ?><span class="w-2 h-2 rounded-full bg-yellow-400" title="Ads"></span><?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="/companies/<?= e($company['id']) ?>" class="text-gray-500 hover:text-white transition"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                <a href="/companies/<?= e($company['id']) ?>/edit" class="text-gray-500 hover:text-white transition"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if (($companies['last_page'] ?? 1) > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-800">
            <p class="text-sm text-gray-400">Mostrando <?= count($companies['data']) ?> de <?= $companies['total'] ?></p>
            <div class="flex gap-1">
                <?php if ($companies['page'] > 1): ?>
                <a href="?page=<?= $companies['page'] - 1 ?>&search=<?= urlencode($filters['search']) ?>&niche=<?= urlencode($filters['niche']) ?>&city=<?= urlencode($filters['city']) ?>"
                   class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded text-sm transition">←</a>
                <?php endif; ?>
                <?php for ($i = max(1, $companies['page'] - 2); $i <= min($companies['last_page'], $companies['page'] + 2); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&niche=<?= urlencode($filters['niche']) ?>&city=<?= urlencode($filters['city']) ?>"
                   class="px-3 py-1.5 rounded text-sm transition <?= $i === $companies['page'] ? 'bg-purple-600 text-white' : 'bg-gray-800 hover:bg-gray-700 text-gray-300' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($companies['page'] < $companies['last_page']): ?>
                <a href="?page=<?= $companies['page'] + 1 ?>&search=<?= urlencode($filters['search']) ?>&niche=<?= urlencode($filters['niche']) ?>&city=<?= urlencode($filters['city']) ?>"
                   class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded text-sm transition">→</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>