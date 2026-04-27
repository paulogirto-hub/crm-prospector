<!-- Lead Show -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/leads" class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white"><?= e($company['name'] ?? 'Lead') ?></h1>
                <p class="text-sm text-gray-400 mt-0.5">
                    Lead #<?= e($lead['id']) ?> · Criado em <?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/leads/<?= e($lead['id']) ?>/edit" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm transition flex items-center gap-2">
                <i data-lucide="pencil" class="w-4 h-4"></i> Editar
            </a>
        </div>
    </div>

    <!-- Stage Badge + Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Dados do Lead -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="w-5 h-5 text-purple-400"></i> Dados do Lead
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Estágio</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-medium mt-1"
                              style="background-color: <?= e($stage['color']) ?>20; color: <?= e($stage['color']) ?>">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= e($stage['color']) ?>"></span>
                            <?= e($stage['name']) ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Status</p>
                        <p class="text-sm text-white mt-1">
                            <?php
                            $statusLabels = ['active' => 'Ativo', 'won' => 'Ganho', 'lost' => 'Perdido', 'deleted' => 'Removido'];
                            echo e($statusLabels[$lead['status']] ?? $lead['status']);
                            ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Fonte</p>
                        <p class="text-sm text-white mt-1"><?= e($lead['source'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Score</p>
                        <p class="text-sm font-medium mt-1 <?= $lead['score'] >= 70 ? 'text-green-400' : ($lead['score'] >= 40 ? 'text-yellow-400' : 'text-gray-400') ?>">
                            <?= e($lead['score']) ?><?= $lead['score'] >= 70 ? ' 🟢' : ($lead['score'] >= 40 ? ' 🟡' : '') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Valor Estimado</p>
                        <p class="text-sm text-white mt-1"><?= $lead['estimated_value'] > 0 ? 'R$ ' . number_format($lead['estimated_value'], 2, ',', '.') : '—' ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Último Contato</p>
                        <p class="text-sm text-white mt-1"><?= $lead['last_contact_at'] ? date('d/m/Y', strtotime($lead['last_contact_at'])) : 'Nunca' ?></p>
                    </div>
                </div>
            </div>

            <!-- Dados da Empresa -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i> Empresa
                </h3>
                <?php if ($company): ?>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Nome</p>
                        <a href="/companies/<?= e($company['id']) ?>" class="text-sm text-cyan-400 hover:text-cyan-300 mt-1 block"><?= e($company['name']) ?></a>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">CNPJ</p>
                        <p class="text-sm text-white mt-1"><?= e($company['cnpj'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Nicho</p>
                        <p class="text-sm text-white mt-1"><?= e($company['niche'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Cidade/UF</p>
                        <p class="text-sm text-white mt-1"><?= e(($company['city'] ?? '') . ($company['state'] ? '/' . $company['state'] : '') ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Telefone</p>
                        <p class="text-sm text-white mt-1"><?= e($company['phone'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="text-sm text-white mt-1"><?= e($company['email'] ?: '—') ?></p>
                    </div>
                </div>
                <?php if ($company['site_url']): ?>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php if ($company['site_url']): ?>
                    <a href="<?= e($company['site_url']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-800 rounded text-xs text-gray-300 hover:text-white transition">
                        <i data-lucide="globe" class="w-3 h-3"></i> Site
                    </a>
                    <?php endif; ?>
                    <?php if ($company['instagram']): ?>
                    <a href="<?= e($company['instagram']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-800 rounded text-xs text-gray-300 hover:text-white transition">
                        <i data-lucide="instagram" class="w-3 h-3"></i> Instagram
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-sm text-gray-500">Dados da empresa não disponíveis</p>
                <?php endif; ?>
            </div>

            <!-- Análise IA -->
            <?php if ($lead['ia_analise'] || $lead['ia_market_analysis']): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-yellow-400"></i> Análise IA
                </h3>
                <?php if ($lead['ia_analise']): ?>
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-1">Análise do Lead</p>
                    <p class="text-sm text-gray-300 whitespace-pre-line"><?= e($lead['ia_analise']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($lead['ia_market_analysis']): ?>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Análise de Mercado</p>
                    <p class="text-sm text-gray-300 whitespace-pre-line"><?= e($lead['ia_market_analysis']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Timeline de Atividades -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5 text-green-400"></i> Atividades
                </h3>

                <!-- Form nova atividade -->
                <form method="POST" action="/leads/<?= e($lead['id']) ?>/activity" class="mb-6">
                    <?= $csrfField ?>
                    <div class="flex gap-2">
                        <select name="type" class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                            <option value="note">📝 Nota</option>
                            <option value="call">📞 Ligação</option>
                            <option value="email">📧 Email</option>
                            <option value="whatsapp">💬 WhatsApp</option>
                            <option value="meeting">🤝 Reunião</option>
                        </select>
                        <input type="text" name="description" placeholder="Descreva a atividade..." required
                               class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>

                <?php if (empty($activities)): ?>
                <p class="text-sm text-gray-500 text-center py-6">Nenhuma atividade registrada</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($activities as $activity): ?>
                    <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-lg">
                        <div class="flex-shrink-0 mt-0.5">
                            <?php
                            $icons = ['note' => '📝', 'call' => '📞', 'email' => '📧', 'whatsapp' => '💬', 'meeting' => '🤝', 'stage_change' => '🔄', 'created' => '✨'];
                            echo $icons[$activity['type']] ?? '📌';
                            ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-200"><?= e($activity['description']) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Mover Stage -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Mover no Pipeline</h3>
                <form method="POST" action="/leads/<?= e($lead['id']) ?>/stage">
                    <?= $csrfField ?>
                    <select name="pipeline_stage_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white mb-3 focus:border-purple-500 focus:outline-none">
                        <?php foreach ($stages as $s): ?>
                        <option value="<?= e($s['id']) ?>" <?= $s['id'] == $lead['pipeline_stage_id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                        Mover
                    </button>
                </form>
            </div>

            <!-- Tarefas do Lead -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white">Tarefas</h3>
                    <a href="/agenda/create" class="text-xs text-purple-400 hover:text-purple-300">+ Nova</a>
                </div>
                <?php if (empty($tasks)): ?>
                <p class="text-xs text-gray-500 text-center py-4">Nenhuma tarefa</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($tasks as $task): ?>
                    <div class="flex items-start gap-2 p-2 bg-gray-800 rounded-lg">
                        <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 <?= $task['completed_at'] ? 'bg-green-400' : 'bg-yellow-400' ?>"></div>
                        <div>
                            <p class="text-sm text-gray-200 <?= $task['completed_at'] ? 'line-through opacity-50' : '' ?>"><?= e($task['title']) ?></p>
                            <?php if ($task['due_date']): ?>
                            <p class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($task['due_date'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ações Rápidas -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Ações</h3>
                <div class="space-y-2">
                    <form method="POST" action="/leads/<?= e($lead['id']) ?>/delete" x-data x-on:submit.prevent="$store.confirmModal.open('Remover Lead', 'Tem certeza que deseja remover este lead?', () => $el.submit())">
                        <?= $csrfField ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="w-full px-3 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg text-sm transition flex items-center gap-2 justify-center">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Remover Lead
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>