<!-- Lead Create -->
<div class="space-y-6" x-data="{ 
    companyMode: 'existing',
    searchQuery: '',
    companies: <?= json_encode(array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name'], 'niche' => $c['niche'] ?? '', 'city' => $c['city'] ?? ''], $companies)) ?>,
    get filteredCompanies() {
        if (!this.searchQuery) return this.companies.slice(0, 20);
        const q = this.searchQuery.toLowerCase();
        return this.companies.filter(c => c.name.toLowerCase().includes(q) || (c.niche||'').toLowerCase().includes(q));
    }
}">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="/leads" class="text-gray-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-white">Novo Lead</h1>
    </div>

    <form method="POST" action="/leads" class="space-y-6">
        <?= $csrfField ?>

        <!-- Empresa -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i> Empresa
            </h3>

            <!-- Toggle -->
            <div class="flex gap-2 mb-4">
                <button type="button" @click="companyMode = 'existing'"
                        :class="companyMode === 'existing' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Empresa Existente
                </button>
                <button type="button" @click="companyMode = 'new'"
                        :class="companyMode === 'new' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Nova Empresa
                </button>
            </div>

            <!-- Empresa existente -->
            <div x-show="companyMode === 'existing'" x-cloak>
                <input type="text" x-model="searchQuery" placeholder="Buscar empresa por nome ou nicho..."
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none mb-2">
                <select name="company_id" size="6" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none" required>
                    <template x-for="c in filteredCompanies" :key="c.id">
                        <option :value="c.id" x-text="c.name + (c.niche ? ' (' + c.niche + ')' : '') + (c.city ? ' - ' + c.city : '')"></option>
                    </template>
                </select>
            </div>

            <!-- Nova empresa -->
            <div x-show="companyMode === 'new'" x-cloak class="grid grid-cols-2 gap-4">
                <input type="hidden" name="company_id" value="new" x-show="companyMode === 'new'">
                <div class="col-span-2">
                    <label class="text-xs text-gray-400 mb-1 block">Nome da Empresa *</label>
                    <input type="text" name="new_company_name" placeholder="Nome da empresa"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Nicho</label>
                    <input type="text" name="new_company_niche" placeholder="Ex: Restaurante, Salão..."
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Cidade</label>
                    <input type="text" name="new_company_city" placeholder="Cidade"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Estado</label>
                    <input type="text" name="new_company_state" placeholder="UF" maxlength="2"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Telefone</label>
                    <input type="text" name="new_company_phone" placeholder="(11) 99999-9999"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Dados do Lead -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-purple-400"></i> Dados do Lead
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Estágio do Pipeline</label>
                    <select name="pipeline_stage_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <?php foreach ($stages as $stage): ?>
                        <option value="<?= e($stage['id']) ?>" <?= $stage['is_default'] ? 'selected' : '' ?>><?= e($stage['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Fonte</label>
                    <select name="source" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <option value="prospecção">Prospecção</option>
                        <option value="indicação">Indicação</option>
                        <option value="site">Site</option>
                        <option value="evento">Evento</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Score (0-100)</label>
                    <input type="number" name="score" value="0" min="0" max="100"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Valor Estimado (R$)</label>
                    <input type="number" name="estimated_value" value="0" step="0.01" min="0"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <a href="/leads" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Criar Lead
            </button>
        </div>
    </form>
</div>