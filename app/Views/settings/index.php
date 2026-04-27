<!-- Settings Index -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Configurações</h1>
            <p class="text-sm text-gray-400 mt-1">Gerencie o sistema e a equipe</p>
        </div>
    </div>

    <!-- Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($isAdmin): ?>
        <a href="/settings/team" class="bg-gray-900 border border-gray-800 rounded-xl p-6 hover:border-purple-500/30 transition group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-purple-900/50 flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-purple-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-white group-hover:text-purple-400 transition">Equipe</h3>
            </div>
            <p class="text-sm text-gray-400">Gerenciar usuários e permissões</p>
        </a>
        <?php endif; ?>

        <a href="/profile" class="bg-gray-900 border border-gray-800 rounded-xl p-6 hover:border-purple-500/30 transition group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-cyan-900/50 flex items-center justify-center">
                    <i data-lucide="user" class="w-5 h-5 text-cyan-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-white group-hover:text-purple-400 transition">Meu Perfil</h3>
            </div>
            <p class="text-sm text-gray-400">Alterar nome, email e senha</p>
        </a>
    </div>

    <!-- Pipeline Stages (Admin) -->
    <?php if ($isAdmin): ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i data-lucide="kanban" class="w-5 h-5 text-purple-400"></i> Estágios do Pipeline
            </h3>
            <!-- Add new stage form -->
            <div x-data="{ show: false }" class="relative">
                <button @click="show = !show" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Novo Estágio
                </button>
                <div x-show="show" x-cloak @click.away="show = false"
                     class="absolute right-0 top-10 bg-gray-800 border border-gray-700 rounded-lg p-4 w-72 z-10 shadow-xl">
                    <form method="POST" action="/settings/stage/create">
                        <?= $csrfField ?>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-gray-400 mb-1 block">Nome *</label>
                                <input type="text" name="name" required placeholder="Nome do estágio"
                                       class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:border-purple-500 focus:outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-400 mb-1 block">Cor</label>
                                    <input type="color" name="color" value="#6c5ce7"
                                           class="w-full h-8 bg-gray-900 border border-gray-700 rounded cursor-pointer">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400 mb-1 block">Posição</label>
                                    <input type="number" name="position" value="<?= count($stages) + 1 ?>" min="1"
                                           class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:border-purple-500 focus:outline-none">
                                </div>
                            </div>
                            <button type="submit" class="w-full px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-medium transition">Criar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <?php foreach ($stages as $stage): ?>
            <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg group">
                <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: <?= e($stage['color']) ?>"></span>
                <span class="text-sm text-white flex-1"><?= e($stage['name']) ?></span>
                <span class="text-xs text-gray-500">#<?= e($stage['position']) ?></span>
                <?php if ($stage['is_default']): ?>
                <span class="text-xs bg-purple-900/50 text-purple-300 px-2 py-0.5 rounded">padrão</span>
                <?php endif; ?>

                <!-- Edit inline -->
                <form method="POST" action="/settings/stage/<?= e($stage['id']) ?>" class="hidden group-hover:flex items-center gap-1">
                    <?= $csrfField ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="name" value="<?= e($stage['name']) ?>">
                    <input type="hidden" name="color" value="<?= e($stage['color']) ?>">
                    <input type="hidden" name="position" value="<?= e($stage['position']) ?>">
                    <button type="submit" class="p-1 text-gray-500 hover:text-white transition" title="Salvar posição">
                        <i data-lucide="pencil" class="w-3 h-3"></i>
                    </button>
                </form>

                <?php if (!$stage['is_default']): ?>
                <form method="POST" action="/settings/stage/<?= e($stage['id']) ?>/delete" x-data x-on:submit.prevent="$store.confirmModal.open('Deletar estágio', 'Deletar estágio \u201c<?= e($stage['name']) ?>\u201d? Leads neste estágio devem ser movidos antes.', () => $el.submit())">
                    <?= $csrfField ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="p-1 text-gray-500 hover:text-red-400 transition hidden group-hover:block" title="Deletar">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>