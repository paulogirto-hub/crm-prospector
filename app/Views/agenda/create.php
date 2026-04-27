<!-- Agenda Create -->
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="/agenda" class="text-gray-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-white">Nova Tarefa</h1>
    </div>

    <form method="POST" action="/agenda" class="space-y-6">
        <?= $csrfField ?>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Dados da Tarefa</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Título *</label>
                    <input type="text" name="title" value="<?= e($old['title'] ?? '') ?>" required
                           placeholder="Ex: Ligar para o cliente..."
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Descrição</label>
                    <textarea name="description" rows="3" placeholder="Detalhes da tarefa..."
                              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"><?= e($old['description'] ?? '') ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Data Limite</label>
                        <input type="date" name="due_date" value="<?= e($old['due_date'] ?? '') ?>"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Lead Associado</label>
                        <select name="lead_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                            <option value="">Nenhum</option>
                            <?php foreach ($leads as $lead): ?>
                            <option value="<?= e($lead['id']) ?>"><?= e($lead['company_name']) ?> (Lead #<?= e($lead['id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/agenda" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Criar Tarefa
            </button>
        </div>
    </form>
</div>