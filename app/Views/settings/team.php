<!-- Settings Team -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/settings" class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Equipe</h1>
        </div>
        <a href="/register" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Novo Usuário
        </a>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <?php if (empty($users)): ?>
        <div class="text-center py-16 text-gray-500">
            <i data-lucide="users" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <p class="text-lg font-medium">Nenhum usuário</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Usuário</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Email</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Cargo</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-400 uppercase">Criado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                    <?= mb_substr(e($user['name']), 0, 1) ?>
                                </div>
                                <span class="text-sm font-medium text-white"><?= e($user['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?= e($user['email']) ?></td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full 
                                <?= $user['role'] === 'admin' ? 'bg-red-900/50 text-red-300' : ($user['role'] === 'manager' ? 'bg-yellow-900/50 text-yellow-300' : 'bg-blue-900/50 text-blue-300') ?>">
                                <?= e($roles[$user['role']] ?? $user['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($user['active']): ?>
                            <span class="text-xs text-green-400">Ativo</span>
                            <?php else: ?>
                            <span class="text-xs text-red-400">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>