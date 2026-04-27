<!-- Lead Edit -->
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="/leads/<?= e($lead['id']) ?>" class="text-gray-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-white">Editar Lead</h1>
    </div>

    <form method="POST" action="/leads/<?= e($lead['id']) ?>" class="space-y-6">
        <?= $csrfField ?>
        <input type="hidden" name="_method" value="PUT">

        <!-- Dados do Lead -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-purple-400"></i> Dados do Lead
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Empresa</label>
                    <select name="company_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= $c['id'] == $lead['company_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Estágio do Pipeline</label>
                    <select name="pipeline_stage_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <?php foreach ($stages as $stage): ?>
                        <option value="<?= e($stage['id']) ?>" <?= $stage['id'] == $lead['pipeline_stage_id'] ? 'selected' : '' ?>><?= e($stage['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Status</label>
                    <select name="status" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <option value="active" <?= $lead['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="won" <?= $lead['status'] === 'won' ? 'selected' : '' ?>>Ganho</option>
                        <option value="lost" <?= $lead['status'] === 'lost' ? 'selected' : '' ?>>Perdido</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Fonte</label>
                    <select name="source" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <?php
                        $sources = ['prospecção' => 'Prospecção', 'indicação' => 'Indicação', 'site' => 'Site', 'evento' => 'Evento', 'outro' => 'Outro'];
                        foreach ($sources as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $lead['source'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Score (0-100)</label>
                    <input type="number" name="score" value="<?= e($lead['score']) ?>" min="0" max="100"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Valor Estimado (R$)</label>
                    <input type="number" name="estimated_value" value="<?= e($lead['estimated_value']) ?>" step="0.01" min="0"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Análise IA (editável) -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-5 h-5 text-yellow-400"></i> Análise IA
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Análise do Lead</label>
                    <textarea name="ia_analise" rows="4" placeholder="Análise gerada por IA..."
                              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"><?= e($lead['ia_analise'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Análise de Mercado</label>
                    <textarea name="ia_market_analysis" rows="4" placeholder="Análise de mercado..."
                              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"><?= e($lead['ia_market_analysis'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <a href="/leads/<?= e($lead['id']) ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Salvar
            </button>
        </div>
    </form>
</div>