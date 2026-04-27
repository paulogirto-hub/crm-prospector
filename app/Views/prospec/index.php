<!-- Prospec Index — Dashboard integrado com Prospector API -->
<div x-data="prospecDashboard()" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Prospecção</h1>
            <p class="text-sm text-gray-400 mt-1">Busque e analise empresas por nicho e localização</p>
        </div>
        <a href="/prospec/history" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="history" class="w-4 h-4"></i> Histórico
        </a>
    </div>

    <!-- Search Form -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="search" class="w-5 h-5 text-purple-400"></i> Nova Busca
        </h3>
        <form @submit.prevent="startSearch" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= $csrfField ?>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Nicho / Ramo</label>
                <input type="text" x-model="form.niche" placeholder="Ex: Restaurante, Salão, Padaria..."
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"
                       :disabled="loading">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Cidade</label>
                <input type="text" x-model="form.city" placeholder="Ex: São Paulo, Campinas..."
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"
                       :disabled="loading">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Estado</label>
                <select x-model="form.state" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none" :disabled="loading">
                    <option value="">Todos</option>
                    <option value="AC">Acre</option><option value="AL">Alagoas</option><option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option><option value="BA">Bahia</option><option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option><option value="ES">Espírito Santo</option><option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option><option value="MT">Mato Grosso</option><option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option><option value="PA">Pará</option><option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option><option value="PE">Pernambuco</option><option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option><option value="RN">Rio Grande do Norte</option><option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option><option value="RR">Roraima</option><option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option><option value="SE">Sergipe</option><option value="TO">Tocantins</option>
                </select>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" :disabled="loading || !form.niche || !form.city"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <template x-if="loading">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </template>
                    <template x-if="!loading">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </template>
                    <span x-text="loading ? 'Buscando...' : 'Buscar Empresas'"></span>
                </button>
            </div>
        </form>

        <!-- Error message -->
        <template x-if="error">
            <div class="mt-3 p-3 bg-red-900/30 border border-red-800 rounded-lg text-sm text-red-400">
                <span x-text="error"></span>
            </div>
        </template>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Buscas Realizadas</span>
                <div class="w-10 h-10 rounded-lg bg-purple-900/50 flex items-center justify-center">
                    <i data-lucide="search" class="w-5 h-5 text-purple-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format(count($recentSearches), 0, ',', '.') ?></p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Empresas no CRM</span>
                <div class="w-10 h-10 rounded-lg bg-cyan-900/50 flex items-center justify-center">
                    <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format($totalCompanies, 0, ',', '.') ?></p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Leads Ativos</span>
                <div class="w-10 h-10 rounded-lg bg-green-900/50 flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-green-400"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white"><?= number_format($activeLeads, 0, ',', '.') ?></p>
        </div>
    </div>

    <!-- Buscas Recentes do Prospector -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="clock" class="w-5 h-5 text-cyan-400"></i> Buscas Recentes
        </h3>

        <?php if (empty($recentSearches)): ?>
        <div class="text-center py-8 text-gray-500">
            <p class="text-sm">Nenhuma busca realizada ainda</p>
            <p class="text-xs mt-1">Use o formulário acima para buscar empresas</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recentSearches as $search): ?>
            <?php
                $sid    = $search['search_id'] ?? '';
                $sNiche = $search['niche'] ?? '';
                $sCity  = $search['city'] ?? '';
                $sState = $search['state'] ?? '';
                $sTotal = $search['total_results'] ?? 0;
                $sStatus= $search['status'] ?? 'unknown';
                $sTs    = $search['timestamp'] ?? '';
            ?>
            <a href="/prospec/session/<?= e($sid) ?>" class="block p-4 bg-gray-800 rounded-lg hover:bg-gray-750 transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white group-hover:text-purple-400 transition">
                            <?= e(ucfirst($sNiche)) ?> em <?= e(ucfirst($sCity)) ?><?= $sState ? '/' . e($sState) : '' ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= e($sTotal) ?> resultado(s)
                            <?= $sTs ? ' · ' . date('d/m/Y H:i', strtotime($sTs)) : '' ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
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
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-600 group-hover:text-gray-400 transition"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 text-center">
            <a href="/prospec/history" class="text-sm text-purple-400 hover:text-purple-300 transition">Ver todo histórico →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function prospecDashboard() {
    return {
        form: { niche: '', city: '', state: 'PR' },
        loading: false,
        error: '',

        async startSearch() {
            this.loading = true;
            this.error = '';

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_csrf"]')?.value || '';

            try {
                const res = await fetch('/prospec/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (data.success && data.search_id) {
                    window.location.href = '/prospec/session/' + data.search_id;
                } else {
                    this.error = data.error || 'Erro desconhecido ao iniciar busca';
                    this.loading = false;
                }
            } catch (e) {
                this.error = 'Erro de conexão com o servidor';
                this.loading = false;
            }
        }
    }
}

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