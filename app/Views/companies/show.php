<!-- Company Show -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/companies" class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white"><?= e($company['name']) ?></h1>
                <?php if ($company['niche']): ?>
                <p class="text-sm text-gray-400 mt-0.5"><?= e($company['niche']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <a href="/companies/<?= e($company['id']) ?>/edit" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm transition flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Editar
        </a>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Dados Principais -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i> Dados da Empresa
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">CNPJ</p>
                        <p class="text-sm text-white mt-1"><?= e($company['cnpj'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Razão Social</p>
                        <p class="text-sm text-white mt-1"><?= e($company['razao_social'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Situação CNPJ</p>
                        <p class="text-sm mt-1">
                            <?php $situacao = $company['situacao_cnpj'] ?? $company['situacao'] ?? ''; ?>
                            <?php if ($situacao): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                <?= strtoupper($situacao) === 'ATIVA' ? 'bg-green-900/50 text-green-400' : (strtoupper($situacao) === 'BAIXADA' ? 'bg-red-900/50 text-red-400' : 'bg-gray-800 text-gray-300') ?>">
                                <?= e($situacao) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-600">—</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Capital Social</p>
                        <p class="text-sm text-white mt-1"><?= $company['capital_social'] ? 'R$ ' . number_format((float)$company['capital_social'], 2, ',', '.') : '—' ?></p>
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
                    <div>
                        <p class="text-xs text-gray-500">Porte</p>
                        <p class="text-sm text-white mt-1"><?= e($company['porte'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">CNAE</p>
                        <p class="text-sm text-white mt-1 truncate" title="<?= e($company['cnae_descricao'] ?? '') ?>"><?= e($company['cnae_descricao'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Natureza Jurídica</p>
                        <p class="text-sm text-white mt-1 truncate" title="<?= e($company['natureza_juridica'] ?? '') ?>"><?= e($company['natureza_juridica'] ?: '—') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Início Atividade</p>
                        <p class="text-sm text-white mt-1"><?= e($company['data_inicio'] ?: '—') ?></p>
                    </div>
                    <div class="flex gap-2 items-end">
                        <div>
                            <p class="text-xs text-gray-500">MEI</p>
                            <?php if ($company['opcao_pelo_mei']): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-900/50 text-yellow-400">MEI</span>
                            <?php else: ?>
                            <span class="text-sm text-gray-600">—</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Simples</p>
                            <?php if ($company['opcao_pelo_simples']): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-900/50 text-blue-400">Simples</span>
                            <?php else: ?>
                            <span class="text-sm text-gray-600">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados Receita / Sócios -->
            <?php if ($company['email_receita'] || $company['telefone_receita'] || !empty($company['socios'])): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-blue-400"></i> Dados da Receita
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <?php if ($company['email_receita']): ?>
                    <div>
                        <p class="text-xs text-gray-500">Email (Receita)</p>
                        <p class="text-sm text-white mt-1"><?= e($company['email_receita']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($company['telefone_receita']): ?>
                    <div>
                        <p class="text-xs text-gray-500">Telefone (Receita)</p>
                        <p class="text-sm text-white mt-1"><?= e($company['telefone_receita']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                $socios = $company['socios'];
                if (is_string($socios)) { $socios = json_decode($socios, true); }
                if (!is_array($socios)) { $socios = []; }
                ?>
                <?php if (!empty($socios)): ?>
                <div>
                    <p class="text-xs text-gray-500 mb-2">Sócios</p>
                    <div class="space-y-1">
                        <?php foreach ($socios as $socio): ?>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="user" class="w-3 h-3 text-gray-500"></i>
                            <?= e(is_string($socio) ? $socio : ($socio['nome_socio'] ?? $socio['nome'] ?? json_encode($socio))) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Presença Digital -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="globe" class="w-5 h-5 text-green-400"></i> Presença Digital
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <?php
                    $digital = [
                        ['Site', $company['site_url'], $company['tem_site'], 'globe', 'cyan'],
                        ['Instagram', $company['instagram'] ?: $company['site_instagram'], $company['tem_instagram'], 'instagram', 'purple'],
                        ['Facebook', $company['facebook'] ?: $company['site_facebook'], $company['tem_facebook'], 'facebook', 'blue'],
                        ['YouTube', $company['youtube'] ?: $company['site_youtube'], !empty($company['youtube'] ?: $company['site_youtube']), 'youtube', 'red'],
                        ['TikTok', $company['tiktok'] ?: $company['site_tiktok'], !empty($company['tiktok'] ?: $company['site_tiktok']), 'music', 'pink'],
                        ['Google Maps', $company['maps_address'], $company['tem_maps'], 'map-pin', 'green'],
                    ];
                    foreach ($digital as $d): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center <?= $d[2] ? "bg-{$d[4]}-900/50" : 'bg-gray-700' ?>">
                            <i data-lucide="<?= $d[3] ?>" class="w-4 h-4 <?= $d[2] ? "text-{$d[4]}-400" : 'text-gray-500' ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500"><?= $d[0] ?></p>
                            <?php if ($d[1]): ?>
                            <a href="<?= e($d[1]) ?>" target="_blank" class="text-xs text-white hover:text-purple-400 truncate block"><?= e($d[1]) ?></a>
                            <?php else: ?>
                            <p class="text-xs text-gray-600">Não encontrado</p>
                            <?php endif; ?>
                        </div>
                        <?php if ($d[2]): ?>
                        <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Site Emails/Phones -->
                <?php
                $siteEmails = $company['site_emails'];
                if (is_string($siteEmails)) { $siteEmails = json_decode($siteEmails, true); }
                if (!is_array($siteEmails)) { $siteEmails = []; }
                $sitePhones = $company['site_phones'];
                if (is_string($sitePhones)) { $sitePhones = json_decode($sitePhones, true); }
                if (!is_array($sitePhones)) { $sitePhones = []; }
                ?>
                <?php if (!empty($siteEmails) || !empty($sitePhones) || $company['tem_ads']): ?>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <?php if (!empty($siteEmails)): ?>
                    <div class="p-3 bg-gray-800 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Emails do Site</p>
                        <div class="space-y-0.5">
                            <?php foreach (array_slice($siteEmails, 0, 5) as $em): ?>
                            <p class="text-xs text-cyan-400"><?= e(is_string($em) ? $em : ($em['email'] ?? json_encode($em))) ?></p>
                            <?php endforeach; ?>
                            <?php if (count($siteEmails) > 5): ?>
                            <p class="text-xs text-gray-500">+<?= count($siteEmails) - 5 ?> mais</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($sitePhones)): ?>
                    <div class="p-3 bg-gray-800 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Telefones do Site</p>
                        <div class="space-y-0.5">
                            <?php foreach (array_slice($sitePhones, 0, 5) as $ph): ?>
                            <p class="text-xs text-green-400"><?= e(is_string($ph) ? $ph : ($ph['phone'] ?? json_encode($ph))) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($company['tem_ads']): ?>
                    <div class="p-3 bg-yellow-900/20 rounded-lg flex items-center gap-2">
                        <span class="text-yellow-400 text-lg">📢</span>
                        <span class="text-xs text-yellow-400 font-medium">Possui Google Ads</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Google Maps -->
                <?php if ($company['maps_rating'] || $company['maps_category'] || $company['maps_address']): ?>
                <div class="mt-4 p-3 bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <?php if ($company['maps_rating']): ?>
                        <span class="text-yellow-400">⭐</span>
                        <span class="text-sm text-white font-medium"><?= e($company['maps_rating']) ?></span>
                        <span class="text-xs text-gray-500">(<?= e($company['maps_reviews'] ?? 0) ?> avaliações)</span>
                        <?php endif; ?>
                        <?php if ($company['maps_category']): ?>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-900/30 text-green-400 ml-auto"><?= e($company['maps_category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($company['maps_address']): ?>
                    <p class="text-xs text-gray-400 mt-1.5">📍 <?= e($company['maps_address']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Snippet -->
                <?php if ($company['snippet']): ?>
                <div class="mt-4 p-3 bg-gray-800/50 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Trecho da busca</p>
                    <p class="text-xs text-gray-400"><?= e($company['snippet']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Notas -->
            <?php if ($company['notes']): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-yellow-400"></i> Notas
                </h3>
                <p class="text-sm text-gray-300 whitespace-pre-line"><?= e($company['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Score -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center">
                <p class="text-xs text-gray-400 mb-2">Score de Qualificação</p>
                <p class="text-5xl font-bold <?= ($company['score'] ?? 0) >= 70 ? 'text-green-400' : (($company['score'] ?? 0) >= 40 ? 'text-yellow-400' : 'text-gray-400') ?>">
                    <?= e($company['score'] ?? 0) ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">de 100</p>
            </div>

            <!-- Leads -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Leads Vinculados</h3>
                <?php if (empty($leads)): ?>
                <p class="text-xs text-gray-500 text-center py-4">Nenhum lead</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($leads as $lead): ?>
                    <a href="/leads/<?= e($lead['id']) ?>" class="block p-2 bg-gray-800 rounded-lg hover:bg-gray-750 transition">
                        <p class="text-sm text-white font-medium">Lead #<?= e($lead['id']) ?></p>
                        <p class="text-xs text-gray-500">
                            <?= e($lead['status']) ?> · R$ <?= number_format($lead['estimated_value'], 2, ',', '.') ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Enrichment Status -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <p class="text-xs text-gray-400 mb-2">Status de Enriquecimento</p>
                <span class="text-xs px-2 py-1 rounded-full
                    <?= $company['enrichment_status'] === 'completed' ? 'bg-green-900/50 text-green-400' : ($company['enrichment_status'] === 'processing' ? 'bg-yellow-900/50 text-yellow-400' : 'bg-gray-800 text-gray-400') ?>">
                    <?= e($company['enrichment_status'] ?: 'pending') ?>
                </span>
            </div>

            <!-- Ações -->
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Ações</h3>
                <div class="space-y-2">
                    <form method="POST" action="/companies/<?= e($company['id']) ?>/archive" x-data x-on:submit.prevent="$store.confirmModal.open('Arquivar Empresa', 'Arquivar esta empresa?', () => $el.submit())">
                        <?= $csrfField ?>
                        <button type="submit" class="w-full px-3 py-2 bg-yellow-900/30 hover:bg-yellow-900/50 text-yellow-400 rounded-lg text-sm transition flex items-center gap-2 justify-center">
                            <i data-lucide="archive" class="w-4 h-4"></i> Arquivar Empresa
                        </button>
                    </form>
                    <form method="POST" action="/companies/<?= e($company['id']) ?>/delete" x-data x-on:submit.prevent="$store.confirmModal.open('Deletar Empresa', 'Tem certeza que deseja deletar esta empresa? Esta ação não pode ser desfeita.', () => $el.submit())">
                        <?= $csrfField ?>
                        <button type="submit" class="w-full px-3 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg text-sm transition flex items-center gap-2 justify-center">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Deletar Empresa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>