<!-- Deletar Conta (LGPD) -->
<div class="min-h-screen bg-gray-950 flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-gray-900 border border-gray-800 rounded-xl p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-8 h-8 text-red-400"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Deletar Conta</h1>
            <p class="text-sm text-gray-400">Esta ação é permanente e não pode ser desfeita. Todos os seus dados serão removidos conforme a LGPD.</p>
        </div>

        <!-- Dados que serão afetados -->
        <div class="bg-gray-800/50 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-semibold text-white mb-3">Dados que serão afetados:</h3>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-gray-900 rounded-lg p-3">
                    <p class="text-2xl font-bold text-white"><?= e($leadCount) ?></p>
                    <p class="text-xs text-gray-500">Leads</p>
                </div>
                <div class="bg-gray-900 rounded-lg p-3">
                    <p class="text-2xl font-bold text-white"><?= e($taskCount) ?></p>
                    <p class="text-xs text-gray-500">Tarefas</p>
                </div>
                <div class="bg-gray-900 rounded-lg p-3">
                    <p class="text-2xl font-bold text-white"><?= e($companyCount) ?></p>
                    <p class="text-xs text-gray-500">Empresas</p>
                </div>
            </div>
            <?php if ($leadCount > 0): ?>
            <p class="text-xs text-yellow-400 mt-3">⚠️ Seus leads serão reatribuídos ao administrador do sistema.</p>
            <?php endif; ?>
        </div>

        <!-- Confirmação com senha -->
        <form method="POST" action="/account/delete" class="space-y-4">
            <?= $csrfField ?>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Digite sua senha para confirmar</label>
                <input type="password" name="password" required 
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-white placeholder-gray-500 focus:border-red-500 focus:outline-none"
                       placeholder="Sua senha atual">
            </div>
            <div class="flex gap-3">
                <a href="/profile" class="flex-1 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition text-center">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2 justify-center">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Deletar Minha Conta
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-800">
            <a href="/privacy" class="text-xs text-purple-400 hover:text-purple-300 transition">Política de Privacidade</a>
            <span class="text-xs text-gray-600 mx-2">·</span>
            <a href="/terms" class="text-xs text-purple-400 hover:text-purple-300 transition">Termos de Uso</a>
        </div>
    </div>
</div>