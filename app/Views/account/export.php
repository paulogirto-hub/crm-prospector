<?php /** @var string $title */ ?>

<div class="min-h-screen bg-gray-900 text-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i data-lucide="download" class="w-7 h-7 text-blue-400"></i>
                Exportar Meus Dados
            </h1>

            <div class="bg-blue-900/30 border border-blue-700/50 rounded-lg p-4 mb-6">
                <p class="text-blue-300 text-sm flex items-start gap-2">
                    <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5"></i>
                    <span>Direito de portabilidade conforme a <strong>LGPD</strong> (Art. 18, V). Você pode solicitar e receber seus dados pessoais em formato estruturado.</span>
                </p>
            </div>

            <div class="space-y-4 text-gray-300 text-sm mb-6">
                <p>O arquivo CSV incluirá:</p>
                <ul class="space-y-2 ml-4">
                    <li class="flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-purple-400"></i>
                        <span><strong>Perfil</strong> — nome, email e dados cadastrais</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="target" class="w-4 h-4 text-purple-400"></i>
                        <span><strong>Leads</strong> — todos os leads atribuídos a você</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-purple-400"></i>
                        <span><strong>Atividades</strong> — histórico de interações registradas</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-square" class="w-4 h-4 text-purple-400"></i>
                        <span><strong>Tarefas</strong> — tarefas criadas e concluídas</span>
                    </li>
                </ul>
            </div>

            <?php if (isset($leadCount) || isset($taskCount) || isset($companyCount)): ?>
            <div class="bg-gray-700/50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-white mb-3">Resumo dos seus dados:</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <?php if (isset($leadCount)): ?>
                    <div class="bg-gray-900 rounded-lg p-3">
                        <p class="text-2xl font-bold text-white"><?= e($leadCount) ?></p>
                        <p class="text-xs text-gray-500">Leads</p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($taskCount)): ?>
                    <div class="bg-gray-900 rounded-lg p-3">
                        <p class="text-2xl font-bold text-white"><?= e($taskCount) ?></p>
                        <p class="text-xs text-gray-500">Tarefas</p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($companyCount)): ?>
                    <div class="bg-gray-900 rounded-lg p-3">
                        <p class="text-2xl font-bold text-white"><?= e($companyCount) ?></p>
                        <p class="text-xs text-gray-500">Empresas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="/account/export" class="space-y-4">
                <?= $csrfField ?? '' ?>
                <div class="flex gap-3">
                    <a href="/dashboard"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg font-medium transition-colors flex items-center gap-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Voltar
                    </a>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Exportar meus dados (CSV)
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-gray-700">
                <p class="text-xs text-gray-500">Deseja excluir sua conta? <a href="/account/delete" class="text-red-400 hover:underline">Excluir conta →</a></p>
            </div>
        </div>
    </div>
</div>