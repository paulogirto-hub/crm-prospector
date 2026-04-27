<!-- Prospec Session Detail — Integrado com Prospector API -->
<?php
// Prepare clean data for JS (strip heavy fields)
$__sessionLeads = json_encode(
    array_map(function($l) {
        return [
            'id' => $l['id'] ?? null,
            'company_name' => $l['company_name'] ?? '',
            'company_niche' => $l['company_niche'] ?? '',
            'company_city' => $l['company_city'] ?? '',
            'company_site' => $l['site_url'] ?? ($l['company_site'] ?? ''),
            'company_phone' => $l['maps_phone'] ?? ($l['company_phone'] ?? ''),
            'company_email' => $l['company_email'] ?? '',
            'score' => (int)($l['score'] ?? 0),
            'lead_status' => $l['lead_status'] ?? 'discovered',
            'pipeline_stage_id' => $l['pipeline_stage_id'] ?? 1,
            'is_final' => (bool)($l['is_final'] ?? false),
        ];
    }, $leads ?? []),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
);
$__sessionSummary = json_encode($summary ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<script>window.__prospecLeads = <?= $__sessionLeads ?>; window.__prospecSummary = <?= $__sessionSummary ?>; window.__prospecCanUseAI = <?= $canUseAI ? 'true' : 'false' ?>;</script>
<div x-data="prospecSession('<?= e($searchId) ?>', '<?= e($status) ?>', <?= e($currentStep) ?>)" class="space-y-6" x-init="init()" x-cloak>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/prospec" class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Busca #<?= e($searchId) ?></h1>
            <span class="text-xs px-2 py-1 rounded-full" :class="statusBadgeClass(status)" x-text="statusLabel(status)"></span>
            <template x-if="isProcessing">
                <span class="text-xs text-yellow-400 flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Processando...
                </span>
            </template>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500" x-show="pollCount > 0" x-text="'Atualizado ' + pollCount + 'x'"></span>
            <button @click="refreshStatus()" :disabled="refreshing"
                    class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" :class="refreshing && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Atualizar
            </button>
        </div>
    </div>

    <!-- Pipeline Visual -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-400">Pipeline de Processamento</h3>
        </div>
        <div class="flex items-center gap-0">
            <?php
            $steps = [
                ['key' => 'discovery',   'label' => 'Descoberta',   'icon' => 'search',       'step' => 1],
                ['key' => 'enriching',   'label' => 'Enriquecer',   'icon' => 'database',     'step' => 2],
                ['key' => 'scoring',     'label' => 'Pontuar',      'icon' => 'bar-chart-2',  'step' => 3],
                ['key' => 'analyzing',   'label' => 'Análise IA',   'icon' => 'sparkles',     'step' => 4],
                ['key' => 'completed',  'label' => 'Concluído',    'icon' => 'check-circle', 'step' => 5],
            ];
            foreach ($steps as $i => $step): ?>
            <div class="flex-1 flex items-center">
                <div class="flex-1">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition"
                             :class="currentStep >= <?= $step['step'] ?> ? 'bg-purple-600 border-purple-400' : 'bg-gray-800 border-gray-700'">
                            <i data-lucide="<?= $step['icon'] ?>" class="w-5 h-5" :class="currentStep >= <?= $step['step'] ?> ? 'text-white' : 'text-gray-500'"></i>
                        </div>
                        <span class="text-xs mt-1.5" :class="currentStep >= <?= $step['step'] ?> ? 'text-white font-medium' : 'text-gray-500'">
                            <?= $step['label'] ?>
                        </span>
                    </div>
                </div>
                <?php if ($i < count($steps) - 1): ?>
                <div class="w-full h-0.5 mt-[-16px]" :class="currentStep > <?= $step['step'] ?> ? 'bg-purple-500' : 'bg-gray-700'"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Session Info -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Nicho</p>
                <p class="text-sm text-white mt-1" x-text="summary?.niche ? capitalize(summary.niche) : '—'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Cidade/UF</p>
                <p class="text-sm text-white mt-1" x-text="(summary?.city ? capitalize(summary.city) : '—') + (summary?.state ? '/' + summary.state : '')"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Resultados</p>
                <p class="text-sm text-white mt-1 font-bold" x-text="summary?.total_results ?? counts.total ?? 0"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Sem Instagram</p>
                <p class="text-sm text-white mt-1" x-text="(summary?.pct_sem_instagram ?? 0) + '%'"></p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-6 pt-6 border-t border-gray-800">
            <div class="text-center">
                <p class="text-lg font-bold text-white" x-text="summary?.total_results ?? counts.total ?? 0">0</p>
                <p class="text-xs text-gray-500">Total</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-cyan-400" x-text="summary?.com_site ?? 0">0</p>
                <p class="text-xs text-gray-500">Com Site</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-purple-400" x-text="summary?.com_instagram ?? 0">0</p>
                <p class="text-xs text-gray-500">Com Instagram</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-green-400" x-text="summary?.com_maps ?? 0">0</p>
                <p class="text-xs text-gray-500">Com Maps</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-yellow-400" x-text="summary?.com_ads ?? 0">0</p>
                <p class="text-xs text-gray-500">Com Ads</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons (batch) -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Ações em Lote:</span>

            <button @click="runAction('enrich')" :disabled="actionLoading"
                    class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="actionLoading && actionType==='enrich' && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Enriquecer Todos
            </button>

            <button @click="runAction('score')" :disabled="actionLoading"
                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                Pontuar Todos
            </button>

            <button @click="analyzeMarket()" :disabled="actionLoading || !canUseAI"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="marketLoading && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="marketLoading ? 'Analisando mercado (até 2min)...' : 'Analisar Mercado'" class="whitespace-nowrap"></span>
            </button>

            <button @click="analyzeIndividual()" :disabled="actionLoading || !canUseAI"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="analyzingLead && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="analyzingLead ? 'Analisando (até 2min)...' : 'Analisar com IA'"></span>
            </button>

            <button @click="importToCrm()" :disabled="importing"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="importing && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="importing ? 'Importando...' : 'Importar para CRM'"></span>
            </button>

            <a :href="'/prospec/export/' + searchId" class="ml-auto px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-xs transition flex items-center gap-1.5">
                <i data-lucide="download" class="w-3 h-3"></i> Exportar
            </a>
        </div>
    </div>

    <!-- Action result message -->
    <template x-if="actionMsg">
        <div class="p-3 rounded-lg text-sm flex items-center justify-between"
             :class="actionSuccess ? 'bg-green-900/30 border border-green-800 text-green-400' : 'bg-red-900/30 border border-red-800 text-red-400'">
            <span x-text="actionMsg"></span>
            <button @click="actionMsg=''" class="text-current opacity-50 hover:opacity-100">&times;</button>
        </div>
    </template>

    <!-- IA Market Analysis -->
    <template x-if="summary?.ia_market_analysis">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-5 h-5 text-yellow-400"></i> Análise de Mercado
            </h3>
            <template x-if="typeof summary.ia_market_analysis === 'object'">
                <div class="space-y-4">
                    <p class="text-sm text-gray-300" x-text="summary.ia_market_analysis.resumo"></p>
                    <template x-if="summary.ia_market_analysis.oportunidades?.length">
                        <div>
                            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Oportunidades</h4>
                            <div class="grid gap-2">
                                <template x-for="opp in summary.ia_market_analysis.oportunidades" :key="opp.titulo">
                                    <div class="flex items-start gap-2 bg-gray-800/50 rounded-lg p-3">
                                        <span class="text-xs px-1.5 py-0.5 rounded mt-0.5"
                                              :class="opp.potencial === 'alto' ? 'bg-green-900/50 text-green-400' : 'bg-yellow-900/50 text-yellow-400'"
                                              x-text="opp.potencial"></span>
                                        <div>
                                            <p class="text-sm text-white font-medium" x-text="opp.titulo"></p>
                                            <p class="text-xs text-gray-400 mt-0.5" x-text="opp.descricao"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="summary.ia_market_analysis.pontos_fracos?.length">
                        <div>
                            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Pontos Fracos do Mercado</h4>
                            <ul class="space-y-1">
                                <template x-for="ponto in summary.ia_market_analysis.pontos_fracos" :key="ponto">
                                    <li class="text-sm text-gray-400 flex items-start gap-2">
                                        <span class="text-red-400 mt-1">•</span>
                                        <span x-text="ponto"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <div class="flex flex-wrap gap-4 pt-3 border-t border-gray-800">
                        <div x-show="summary.ia_market_analysis.concorrencia">
                            <span class="text-xs text-gray-500">Concorrência:</span>
                            <span class="text-sm text-white ml-1" x-text="summary.ia_market_analysis.concorrencia"></span>
                        </div>
                        <div x-show="summary.ia_market_analysis.ticket_medio_estimado">
                            <span class="text-xs text-gray-500">Ticket Médio:</span>
                            <span class="text-sm text-white ml-1" x-text="summary.ia_market_analysis.ticket_medio_estimado"></span>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="typeof summary.ia_market_analysis === 'string' && summary.ia_market_analysis.trim()">
                <div class="text-sm text-gray-300 whitespace-pre-line" x-text="summary.ia_market_analysis"></div>
            </template>
        </div>
    </template>

    <!-- Filters -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Filtros:</span>

            <!-- Score filter -->
            <div class="flex items-center gap-1">
                <button @click="filterScore = 'all'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterScore === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    Todos <span class="ml-1 opacity-70" x-text="'(' + leads.length + ')'"></span>
                </button>
                <button @click="filterScore = 'high'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterScore === 'high' ? 'bg-green-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    🟢 Alto <span class="ml-1 opacity-70" x-text="'(' + counts.score_high + ')'"></span>
                </button>
                <button @click="filterScore = 'medium'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterScore === 'medium' ? 'bg-yellow-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    🟡 Médio <span class="ml-1 opacity-70" x-text="'(' + counts.score_medium + ')'"></span>
                </button>
                <button @click="filterScore = 'low'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterScore === 'low' ? 'bg-gray-500 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    ⚪ Baixo <span class="ml-1 opacity-70" x-text="'(' + counts.score_low + ')'"></span>
                </button>
            </div>

            <span class="text-gray-700">|</span>

            <!-- Status filter -->
            <div class="flex items-center gap-1">
                <button @click="filterStatus = 'all'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterStatus === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    Todos
                </button>
                <button @click="filterStatus = 'discovered'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterStatus === 'discovered' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    🔵 Descoberto
                </button>
                <button @click="filterStatus = 'enriched'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterStatus === 'enriched' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    🔷 Enriquecido
                </button>
                <button @click="filterStatus = 'scored'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterStatus === 'scored' ? 'bg-yellow-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    ⭐ Pontuado
                </button>
                <button @click="filterStatus = 'analyzed'"
                        class="px-2.5 py-1 rounded text-xs transition"
                        :class="filterStatus === 'analyzed' ? 'bg-green-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-gray-300'">
                    ✅ Analisado
                </button>
            </div>

            <span class="text-gray-700">|</span>

            <!-- Sort -->
            <select x-model="sortBy" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-gray-300 focus:outline-none focus:border-purple-500">
                <option value="position">Posição</option>
                <option value="score">Score ↓</option>
                <option value="name">Nome A-Z</option>
            </select>

            <!-- Count -->
            <span class="text-xs text-gray-500 ml-auto" x-text="filteredLeads.length + ' de ' + leads.length + ' leads'"></span>
        </div>
    </div>

    <!-- Companies Table -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" x-show="filteredLeads.length > 0">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase w-10">#</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Empresa</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">CNPJ/Contato</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Telefone</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Email/Site</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-400 uppercase">Score</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Rating</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Info</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(lead, idx) in filteredLeads" :key="lead.id || idx">
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/50 transition">
                            <!-- # -->
                            <td class="px-4 py-3 text-xs text-gray-500" x-text="lead.position || idx + 1"></td>

                            <!-- Empresa -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-white font-medium" x-text="lead.title || lead.maps_title || '—'"></div>
                                <div class="text-xs text-gray-500 mt-0.5" x-show="lead.maps_address" x-text="lead.maps_address"></div>
                                <div class="text-xs text-gray-600 mt-0.5" x-show="lead.snippet" x-text="lead.snippet?.substring(0, 80) + (lead.snippet?.length > 80 ? '...' : '')"></div>
                            </td>

                            <!-- CNPJ/Contato -->
                            <td class="px-4 py-3">
                                <template x-if="lead.cnpj">
                                    <span class="text-sm text-gray-300" x-text="lead.cnpj"></span>
                                </template>
                                <template x-if="!lead.cnpj">
                                    <span class="text-xs text-gray-600">—</span>
                                </template>
                                <template x-if="lead.capital_social">
                                    <div class="text-xs text-gray-500 mt-0.5" x-text="'R$ ' + Number(lead.capital_social).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></div>
                                </template>
                                <div class="text-xs text-gray-500 mt-0.5" x-show="lead.maps_category" x-text="lead.maps_category"></div>
                            </td>

                            <!-- Telefone -->
                            <td class="px-4 py-3">
                                <template x-if="lead.maps_phone">
                                    <a :href="'https://wa.me/55' + lead.maps_phone.replace(/\D/g, '')" target="_blank"
                                       class="text-sm text-green-400 hover:text-green-300 transition flex items-center gap-1">
                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                        <span x-text="lead.maps_phone"></span>
                                    </a>
                                </template>
                                <template x-if="!lead.maps_phone">
                                    <span class="text-xs text-gray-600">—</span>
                                </template>
                            </td>

                            <!-- Email/Site -->
                            <td class="px-4 py-3">
                                <template x-if="lead.email">
                                    <div class="text-sm text-gray-300" x-text="lead.email"></div>
                                </template>
                                <template x-if="lead.site_url">
                                    <a :href="lead.site_url" target="_blank"
                                       class="text-xs text-cyan-400 hover:text-cyan-300 transition flex items-center gap-1">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        <span x-text="lead.site_url.replace(/^https?:\/\//, '').substring(0, 30) + (lead.site_url.replace(/^https?:\/\//, '').length > 30 ? '...' : '')"></span>
                                    </a>
                                </template>
                                <template x-if="!lead.site_url && !lead.email">
                                    <span class="text-xs text-gray-600">—</span>
                                </template>
                            </td>

                            <!-- Score -->
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm font-bold"
                                      :class="lead.score >= 70 ? 'text-green-400' : (lead.score >= 40 ? 'text-yellow-400' : (lead.score > 0 ? 'text-gray-400' : 'text-gray-600'))"
                                      x-text="lead.score || '—'"></span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs px-2 py-1 rounded-full"
                                      :class="leadStatusBadgeClass(lead.lead_status)"
                                      x-text="leadStatusLabel(lead.lead_status)"></span>
                            </td>

                            <!-- Rating -->
                            <td class="px-4 py-3 text-center">
                                <template x-if="lead.maps_rating">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-yellow-400 text-xs">⭐</span>
                                        <span class="text-sm text-white font-medium" x-text="lead.maps_rating"></span>
                                        <template x-if="lead.maps_reviews">
                                            <span class="text-xs text-gray-500" x-text="'(' + lead.maps_reviews + ')'"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!lead.maps_rating">
                                    <span class="text-xs text-gray-600">—</span>
                                </template>
                            </td>

                            <!-- Info Badges (MEI, Instagram, Ads, etc) -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <template x-if="lead.opcao_pelo_mei">
                                        <span class="text-xs bg-yellow-900/50 px-1.5 py-0.5 rounded text-yellow-400" title="MEI">MEI</span>
                                    </template>
                                    <template x-if="lead.tem_site">
                                        <span class="text-xs bg-gray-800 px-1.5 py-0.5 rounded text-cyan-400" title="Site">🌐</span>
                                    </template>
                                    <template x-if="lead.tem_instagram || lead.instagram_url || lead.site_instagram">
                                        <span class="text-xs bg-gray-800 px-1.5 py-0.5 rounded text-purple-400" title="Instagram">📷</span>
                                    </template>
                                    <template x-if="lead.tem_maps">
                                        <span class="text-xs bg-gray-800 px-1.5 py-0.5 rounded text-green-400" title="Maps">📍</span>
                                    </template>
                                    <template x-if="lead.tem_ads">
                                        <span class="text-xs bg-gray-800 px-1.5 py-0.5 rounded text-yellow-400" title="Ads">📢</span>
                                    </template>
                                    <template x-if="!lead.tem_site && !lead.tem_instagram && !lead.tem_maps && !lead.tem_ads && !lead.opcao_pelo_mei && !lead.instagram_url && !lead.site_instagram">
                                        <span class="text-xs text-gray-600">—</span>
                                    </template>
                                </div>
                            </td>

                            <!-- Ações Individuais -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="diagnoseLead(lead.id)"
                                            :disabled="lead._diagnosing || !canUseAI"
                                            class="p-1 rounded hover:bg-gray-700 text-orange-400 hover:text-orange-300 transition disabled:opacity-50" title="Diagnosticar">
                                        <i data-lucide="stethoscope" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button @click="importSingleLead(lead.id)"
                                            :disabled="lead._importing"
                                            class="p-1 rounded hover:bg-gray-700 text-blue-400 hover:text-blue-300 transition disabled:opacity-50" 
                                            :title="lead._imported ? 'Já importado' : 'Importar para CRM'">
                                        <svg class="w-3.5 h-3.5" :class="lead._importing && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    </button>
                                    <template x-if="lead.ia_analise">
                                        <button @click="showIaAnalysis(idx)"
                                                class="p-1 rounded hover:bg-gray-700 text-purple-400 hover:text-purple-300 transition" title="Ver Análise IA">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </template>
                                    <template x-if="!lead.ia_analise && canUseAI">
                                        <button @click="analyzeLeadInline(idx)"
                                                :disabled="lead._analyzing"
                                                class="p-1 rounded hover:bg-gray-700 text-green-400 hover:text-green-300 transition disabled:opacity-50" title="Analisar com IA">
                                            <svg class="w-3.5 h-3.5" :class="lead._analyzing && 'animate-spin'" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </button>
                                    </template>
                                    <template x-if="lead.site_url">
                                        <a :href="lead.site_url" target="_blank"
                                           class="p-1 rounded hover:bg-gray-700 text-gray-400 hover:text-white transition" title="Abrir site">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </template>
                                    <template x-if="lead.instagram_url">
                                        <a :href="lead.instagram_url" target="_blank"
                                           class="p-1 rounded hover:bg-gray-700 text-pink-400 hover:text-pink-300 transition" title="Instagram">
                                            <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </template>
                                    <template x-if="lead.maps_phone">
                                        <a :href="'https://wa.me/55' + lead.maps_phone.replace(/\D/g, '')" target="_blank"
                                           class="p-1 rounded hover:bg-gray-700 text-green-400 hover:text-green-300 transition" title="WhatsApp">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty state -->
            <div class="text-center py-12 text-gray-500" x-show="filteredLeads.length === 0">
                <i data-lucide="search" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
                <p class="text-sm" x-text="leads.length === 0 ? 'Nenhuma empresa encontrada nesta busca' : 'Nenhum lead corresponde aos filtros'"></p>
                <p class="text-xs mt-1" x-show="leads.length === 0">A busca ainda pode estar em andamento. Aguarde a atualização automática.</p>
            </div>
        </div>
    </div>

    <!-- IA Analysis Modal -->
    <template x-if="showModal && modalType === 'analysis'">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showModal=false; modalType=''">
            <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl max-h-[80vh] overflow-y-auto p-6 m-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5 text-purple-400"></i>
                        <span x-text="modalTitle"></span>
                    </h3>
                    <button @click="showModal=false; modalType=''" class="text-gray-400 hover:text-white text-xl">&times;</button>
                </div>
                <div class="text-sm text-gray-300 whitespace-pre-line" x-text="modalContent"></div>
            </div>
        </div>
    </template>

    <!-- Diagnóstico Modal -->
    <template x-if="showModal && modalType === 'diagnosis'">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showModal=false; modalType=''">
            <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-3xl max-h-[85vh] overflow-y-auto p-6 m-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i data-lucide="stethoscope" class="w-5 h-5 text-orange-400"></i>
                        Diagnóstico: <span x-text="diagnosis?.empresa || modalTitle"></span>
                    </h3>
                    <button @click="showModal=false; modalType=''" class="text-gray-400 hover:text-white text-xl">&times;</button>
                </div>

                <!-- Loading state -->
                <template x-if="diagnosingLead">
                    <div class="text-center py-8">
                        <svg class="w-8 h-8 animate-spin mx-auto text-orange-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <p class="text-sm text-gray-400 mt-3">Diagnosticando... Isso pode levar até 2 minutos.</p>
                    </div>
                </template>

                <!-- Diagnosis content -->
                <template x-if="!diagnosingLead && diagnosis">
                    <div class="space-y-5">
                        <!-- Urgência Badge -->
                        <template x-if="diagnosis.urgencia">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Urgência:</span>
                                <span class="text-xs px-2.5 py-1 rounded-full font-bold"
                                    :class="diagnosis.urgencia?.toLowerCase() === 'alta' ? 'bg-red-900/60 text-red-400' : (diagnosis.urgencia?.toLowerCase() === 'média' || diagnosis.urgencia?.toLowerCase() === 'media' ? 'bg-yellow-900/60 text-yellow-400' : 'bg-green-900/60 text-green-400')"
                                    x-text="diagnosis.urgencia"></span>
                                <template x-if="diagnosis.urgencia_motivo">
                                    <span class="text-xs text-gray-500" x-text="diagnosis.urgencia_motivo"></span>
                                </template>
                            </div>
                        </template>

                        <!-- Pontos Fracos -->
                        <template x-if="diagnosis.pontos_fracos?.length">
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-red-400 mb-3 flex items-center gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Pontos Fracos
                                </h4>
                                <div class="space-y-3">
                                    <template x-for="(ponto, pi) in diagnosis.pontos_fracos" :key="pi">
                                        <div class="border-l-2 border-red-800/60 pl-3">
                                            <p class="text-sm text-white font-medium" x-text="ponto.ponto || ponto"></p>
                                            <template x-if="ponto.impacto">
                                                <p class="text-xs text-red-400/70 mt-0.5">Impacto: <span x-text="ponto.impacto"></span></p>
                                            </template>
                                            <template x-if="ponto.solucao">
                                                <p class="text-xs text-green-400/70 mt-0.5">Solução: <span x-text="ponto.solucao"></span></p>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Pontos Fortes -->
                        <template x-if="diagnosis.pontos_fortes?.length">
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-green-400 mb-3 flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i> Pontos Fortes
                                </h4>
                                <div class="space-y-3">
                                    <template x-for="(ponto, pi) in diagnosis.pontos_fortes" :key="pi">
                                        <div class="border-l-2 border-green-800/60 pl-3">
                                            <p class="text-sm text-white font-medium" x-text="ponto.ponto || ponto"></p>
                                            <template x-if="ponto.como_aproveitar">
                                                <p class="text-xs text-cyan-400/70 mt-0.5">Como aproveitar: <span x-text="ponto.como_aproveitar"></span></p>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Oportunidade Principal -->
                        <template x-if="diagnosis.oportunidade_principal">
                            <div class="bg-gradient-to-r from-purple-900/30 to-blue-900/30 border border-purple-800/40 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-purple-300 mb-3 flex items-center gap-2">
                                    <i data-lucide="target" class="w-4 h-4"></i> Oportunidade Principal
                                </h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <template x-if="diagnosis.oportunidade_principal.servico">
                                        <div>
                                            <p class="text-xs text-gray-500">Serviço</p>
                                            <p class="text-sm text-white font-medium" x-text="diagnosis.oportunidade_principal.servico"></p>
                                        </div>
                                    </template>
                                    <template x-if="diagnosis.oportunidade_principal.investimento">
                                        <div>
                                            <p class="text-xs text-gray-500">Investimento</p>
                                            <p class="text-sm text-white font-medium" x-text="diagnosis.oportunidade_principal.investimento"></p>
                                        </div>
                                    </template>
                                    <template x-if="diagnosis.oportunidade_principal.retorno">
                                        <div>
                                            <p class="text-xs text-gray-500">Retorno</p>
                                            <p class="text-sm text-green-400 font-medium" x-text="diagnosis.oportunidade_principal.retorno"></p>
                                        </div>
                                    </template>
                                    <template x-if="diagnosis.oportunidade_principal.prazo">
                                        <div>
                                            <p class="text-xs text-gray-500">Prazo</p>
                                            <p class="text-sm text-white font-medium" x-text="diagnosis.oportunidade_principal.prazo"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Abordagem WhatsApp -->
                        <template x-if="diagnosis.abordagem_whatsapp">
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-green-400 mb-2 flex items-center gap-2">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i> Abordagem WhatsApp
                                </h4>
                                <div class="bg-gray-900 rounded-lg p-3 text-sm text-gray-300 whitespace-pre-line" x-text="diagnosis.abordagem_whatsapp"></div>
                                <div class="flex items-center gap-2 mt-3">
                                    <button @click="copyToClipboard(diagnosis.abordagem_whatsapp)"
                                            class="px-3 py-1.5 bg-green-700 hover:bg-green-600 text-white rounded-lg text-xs font-medium transition flex items-center gap-1.5">
                                        <i data-lucide="copy" class="w-3 h-3"></i> Copiar
                                    </button>
                                    <template x-if="diagnosis.telefone">
                                        <a :href="'https://wa.me/55' + diagnosis.telefone.replace(/\D/g, '')" target="_blank"
                                           class="px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white rounded-lg text-xs font-medium transition flex items-center gap-1.5">
                                            <i data-lucide="message-circle" class="w-3 h-3"></i> Enviar no WhatsApp
                                        </a>
                                    </template>
                                    <template x-if="copySuccess">
                                        <span class="text-xs text-green-400">✓ Copiado!</span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Estimativa de Receita -->
                        <template x-if="diagnosis.estimativa_receita || diagnosis.receita_estimada">
                            <div class="flex items-center gap-3 bg-yellow-900/20 border border-yellow-800/40 rounded-lg p-3">
                                <i data-lucide="dollar-sign" class="w-5 h-5 text-yellow-400"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Estimativa de Receita</p>
                                    <p class="text-sm text-yellow-400 font-bold" x-text="diagnosis.estimativa_receita || diagnosis.receita_estimada"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Re-diagnosticar -->
                        <div class="pt-3 border-t border-gray-800 flex justify-end">
                            <button @click="rediagnoseCurrentLead()" :disabled="diagnosingLead"
                                    class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-xs transition flex items-center gap-1.5 disabled:opacity-50">
                                <i data-lucide="refresh-cw" class="w-3 h-3"></i> Re-diagnosticar
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Fallback: diagnosis is a string -->
                <template x-if="!diagnosingLead && diagnosis && typeof diagnosis === 'string'">
                    <div class="text-sm text-gray-300 whitespace-pre-line" x-text="diagnosis"></div>
                </template>
            </div>
        </div>
    </template>
</div>

<script>
function prospecSession(searchId, initialStatus, initialStep) {
    return {
        searchId: searchId,
        status: initialStatus,
        currentStep: initialStep,
        leads: window.__prospecLeads || [],
        summary: window.__prospecSummary || {},
        canUseAI: window.__prospecCanUseAI || false,

        refreshing: false,
        actionLoading: false,
        actionType: '',
        importing: false,
        analyzingLead: false,
        marketLoading: false,
        actionMsg: '',
        actionSuccess: true,

        // Diagnosis
        diagnosingLead: false,
        diagnosis: null,
        diagnosisLeadId: null,
        copySuccess: false,

        // Filters
        filterScore: 'all',
        filterStatus: 'all',
        sortBy: 'position',

        // Polling
        pollCount: 0,
        pollInterval: null,

        // Modal
        showModal: false,
        modalType: '', // 'analysis' or 'diagnosis'
        modalTitle: '',
        modalContent: '',

        // Counts (computed from leads)
        counts: {
            total: 0,
            score_high: 0,
            score_medium: 0,
            score_low: 0,
            enriched: 0,
            scored: 0,
            analyzed: 0,
        },

        get isProcessing() {
            return ['discovering', 'enriching', 'scoring', 'analyzing_leads'].includes(this.status);
        },

        get filteredLeads() {
            let filtered = [...this.leads];

            // Score filter
            if (this.filterScore === 'high') {
                filtered = filtered.filter(l => l.score >= 70);
            } else if (this.filterScore === 'medium') {
                filtered = filtered.filter(l => l.score >= 40 && l.score < 70);
            } else if (this.filterScore === 'low') {
                filtered = filtered.filter(l => l.score > 0 && l.score < 40);
            }

            // Status filter
            if (this.filterStatus !== 'all') {
                filtered = filtered.filter(l => l.lead_status === this.filterStatus);
            }

            // Sort
            if (this.sortBy === 'score') {
                filtered.sort((a, b) => (b.score || 0) - (a.score || 0));
            } else if (this.sortBy === 'name') {
                filtered.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
            } else {
                filtered.sort((a, b) => (a.position || 0) - (b.position || 0));
            }

            return filtered;
        },

        init() {
            this.recalculateCounts();
            if (this.isProcessing) {
                this.startPolling();
            }
        },

        recalculateCounts() {
            this.counts.total = this.leads.length;
            this.counts.score_high = this.leads.filter(l => l.score >= 70).length;
            this.counts.score_medium = this.leads.filter(l => l.score >= 40 && l.score < 70).length;
            this.counts.score_low = this.leads.filter(l => l.score > 0 && l.score < 40).length;
            this.counts.enriched = this.leads.filter(l => ['enriched', 'scored', 'analyzed'].includes(l.lead_status)).length;
            this.counts.scored = this.leads.filter(l => ['scored', 'analyzed'].includes(l.lead_status)).length;
            this.counts.analyzed = this.leads.filter(l => l.lead_status === 'analyzed').length;
        },

        startPolling() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(() => {
                if (this.isProcessing) {
                    this.refreshStatus();
                } else {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
            }, 3000);
        },

        async refreshStatus() {
            this.refreshing = true;
            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/status');
                const data = await res.json();
                if (data.success) {
                    this.status = data.status;
                    this.currentStep = data.currentStep;
                    this.leads = data.leads || [];
                    this.counts = data.counts || {};
                    this.summary = data.summary || this.summary;

                    // Restart Lucide icons after DOM update
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                    this.pollCount++;
                }
            } catch (e) {
                console.error('Poll error:', e);
            }
            this.refreshing = false;
        },

        async analyzeMarket() {
            this.marketLoading = true;
            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/analyze-market', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.actionSuccess = true;
                    this.actionMsg = data.message || 'Análise de mercado concluída!';
                    this.refreshStatus();
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro na análise de mercado';
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão. A análise pode demorar até 2 minutos.';
            }
            this.marketLoading = false;
        },

        async analyzeIndividual() {
            this.analyzingLead = true;
            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/analyze-lead', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.actionSuccess = true;
                    this.actionMsg = data.message || 'Análise IA iniciada com sucesso!';
                    this.startPolling();
                    setTimeout(() => this.refreshStatus(), 2000);
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro na análise IA';
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão. A análise pode demorar até 2 minutos.';
            }
            this.analyzingLead = false;
        },

        async analyzeLeadInline(idx) {
            const lead = this.filteredLeads[idx];
            if (!lead) return;

            // Mark as analyzing in main leads array too
            const mainIdx = this.leads.findIndex(l => l.id === lead.id);
            if (mainIdx !== -1) {
                this.leads[mainIdx]._analyzing = true;
                this.leads = [...this.leads];
            }

            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/analyze-lead', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.actionSuccess = true;
                    const name = lead.title || lead.maps_title || 'Lead';
                    this.actionMsg = `Análise IA de "${name}" iniciada! Aguardando resultado...`;
                    this.startPolling();
                    setTimeout(() => this.refreshStatus(), 2000);
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro na análise IA';
                    if (mainIdx !== -1) {
                        this.leads[mainIdx]._analyzing = false;
                        this.leads = [...this.leads];
                    }
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão. A análise pode demorar até 2 minutos.';
                if (mainIdx !== -1) {
                    this.leads[mainIdx]._analyzing = false;
                    this.leads = [...this.leads];
                }
            }
        },

        async diagnoseLead(leadId) {
            if (!leadId) return;
            this.diagnosisLeadId = leadId;
            this.diagnosingLead = true;
            this.diagnosis = null;
            this.copySuccess = false;
            this.showModal = true;
            this.modalType = 'diagnosis';
            this.modalTitle = '';
            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Mark lead as diagnosing
            const idx = this.leads.findIndex(l => l.id === leadId);
            if (idx !== -1) {
                this.leads[idx]._diagnosing = true;
                this.leads = [...this.leads];
            }

            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/diagnose/' + leadId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.diagnosis = data.diagnosis || data.data || data;
                    this.modalTitle = data.empresa || data.diagnosis?.empresa || '';
                    this.actionSuccess = true;
                    this.actionMsg = '';
                    this.refreshStatus();
                } else {
                    this.diagnosis = null;
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro ao gerar diagnóstico';
                    // Close modal on error if no content to show
                    this.showModal = false;
                    this.modalType = '';
                }
            } catch (e) {
                this.diagnosis = null;
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão. O diagnóstico pode demorar até 2 minutos.';
                this.showModal = false;
                this.modalType = '';
            }

            this.diagnosingLead = false;
            if (idx !== -1) {
                this.leads[idx]._diagnosing = false;
                this.leads = [...this.leads];
            }

            // Re-render lucide icons
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async rediagnoseCurrentLead() {
            if (!this.diagnosisLeadId) return;
            this.diagnosingLead = true;
            this.diagnosis = null;
            this.copySuccess = false;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/session/' + this.searchId + '/diagnose/' + this.diagnosisLeadId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.diagnosis = data.diagnosis || data.data || data;
                    this.modalTitle = data.empresa || data.diagnosis?.empresa || '';
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro ao re-diagnosticar';
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão ao re-diagnosticar.';
            }

            this.diagnosingLead = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.copySuccess = true;
                setTimeout(() => { this.copySuccess = false; }, 2000);
            }).catch(() => {
                // Fallback
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                this.copySuccess = true;
                setTimeout(() => { this.copySuccess = false; }, 2000);
            });
        },

        async runAction(action) {
            this.actionLoading = true;
            this.actionType = action;
            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/' + action + '/' + this.searchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success !== false) {
                    this.actionSuccess = true;
                    this.actionMsg = data.message || 'Ação executada com sucesso!';
                    // Start polling to track progress
                    this.startPolling();
                    // Also do immediate refresh
                    setTimeout(() => this.refreshStatus(), 1500);
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro ao executar ação';
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão';
            }
            this.actionLoading = false;
            this.actionType = '';
        },

        async importToCrm() {
            this.importing = true;
            this.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch('/prospec/import/' + this.searchId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await res.json();

                if (data.success) {
                    this.actionSuccess = true;
                    this.actionMsg = `Importação concluída! ${data.imported} importados, ${data.skipped} já existiam, ${data.errors} erros.`;
                } else {
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro na importação';
                }
            } catch (e) {
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão';
            }
            this.importing = false;
        },

        async importSingleLead(leadId) {
            const idx = this.leads.findIndex(l => l.id === leadId);
            if (idx === -1) return;
            this.leads[idx]._importing = true;
            this.leads[idx]._imported = false;
            this.leads = [...this.leads];
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            try {
                const res = await fetch('/prospec/import-lead/' + this.searchId + '/' + leadId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json();
                if (data.success) {
                    this.leads[idx]._imported = true;
                    this.leads[idx]._importing = false;
                    this.actionSuccess = true;
                    const name = this.leads[idx].title || this.leads[idx].maps_title || 'Lead';
                    this.actionMsg = data.action === 'updated' ? `Lead "${name}" atualizado!` : `Lead "${name}" importado!`;
                    setTimeout(() => { this.actionMsg = ''; }, 4000);
                } else {
                    this.leads[idx]._importing = false;
                    this.actionSuccess = false;
                    this.actionMsg = data.error || 'Erro ao importar';
                    setTimeout(() => { this.actionMsg = ''; }, 4000);
                }
            } catch (e) {
                this.leads[idx]._importing = false;
                this.actionSuccess = false;
                this.actionMsg = 'Erro de conexão';
                setTimeout(() => { this.actionMsg = ''; }, 4000);
            }
            this.leads = [...this.leads];
        },

        showIaAnalysis(idx) {
            const lead = this.filteredLeads[idx];
            if (lead) {
                this.modalTitle = lead.title || lead.maps_title || 'Lead';
                this.modalContent = lead.ia_analise || 'Análise não disponível';
                this.modalType = 'analysis';
                this.showModal = true;
            }
        },

        capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        leadStatusBadgeClass(leadStatus) {
            const map = {
                'discovered': 'bg-blue-900/50 text-blue-400',
                'enriched':   'bg-cyan-900/50 text-cyan-400',
                'scored':     'bg-yellow-900/50 text-yellow-400',
                'analyzed':   'bg-green-900/50 text-green-400',
            };
            return map[leadStatus] || 'bg-gray-800 text-gray-400';
        },

        leadStatusLabel(leadStatus) {
            const map = {
                'discovered': 'Descoberto',
                'enriched':   'Enriquecido',
                'scored':     'Pontuado',
                'analyzed':   'Analisado',
            };
            return map[leadStatus] || leadStatus || '—';
        },

        statusBadgeClass(status) {
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
        },

        statusLabel(status) {
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
        },
    }
}
</script>