<?php /** @var string $title */ ?>

<div class="min-h-screen bg-gray-900 text-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i data-lucide="trash-2" class="w-7 h-7 text-red-400"></i>
                Excluir Conta
            </h1>

            <div class="bg-red-900/30 border border-red-700/50 rounded-lg p-4 mb-6">
                <p class="text-red-300 text-sm flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                    <span><strong>Atenção:</strong> Esta ação é irreversível. Todos os seus dados serão permanentemente excluídos após 30 dias, incluindo leads, empresas, tarefas e histórico.</span>
                </p>
            </div>

            <div class="space-y-4 text-gray-300 text-sm mb-6">
                <p>Ao excluir sua conta:</p>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Seus leads serão desatribuídos</li>
                    <li>Suas tarefas serão removidas</li>
                    <li>Seus templates serão excluídos</li>
                    <li>Seus dados de prospecção serão removidos</li>
                    <li>Logs de auditoria serão mantidos por 1 ano (exigência legal)</li>
                </ul>
            </div>

            <?php if (isset($confirmation_required) && $confirmation_required): ?>
                <div class="bg-red-900/20 border border-red-800 rounded-lg p-3 mb-4 text-sm text-red-300">
                    Por favor, digite <strong>EXCLUIR</strong> para confirmar.
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-900/30 border border-red-700/50 rounded-lg p-3 mb-4 text-sm text-red-300">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/account/delete" class="space-y-4">
                <?= $csrfField ?? '' ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Digite EXCLUIR para confirmar</label>
                    <input type="text" name="confirm" required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-white placeholder-gray-400"
                        placeholder="EXCLUIR">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Sua senha</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-white placeholder-gray-400"
                        placeholder="Confirme sua senha">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Excluir Minha Conta
                    </button>
                    <a href="/dashboard"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg font-medium transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-gray-700">
                <p class="text-xs text-gray-500">Precisa apenas exportar seus dados? <a href="/account/export" class="text-blue-400 hover:underline">Exportar dados →</a></p>
            </div>
        </div>
    </div>
</div>