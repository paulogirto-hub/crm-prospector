<!-- Templates Index -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Templates</h1>
            <p class="text-sm text-gray-400 mt-1">Modelos de mensagens para prospecção</p>
        </div>
        <a href="/templates/create" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Novo Template
        </a>
    </div>

    <?php if (empty($templates)): ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl text-center py-16 text-gray-500">
        <i data-lucide="mail" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
        <p class="text-lg font-medium">Nenhum template criado</p>
        <p class="text-sm mt-1">Crie templates para agilizar suas mensagens de prospecção</p>
        <a href="/templates/create" class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
            + Novo Template
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($templates as $tpl): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-purple-500/30 transition group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-white group-hover:text-purple-400 transition truncate"><?= e($tpl['name']) ?></h3>
                <span class="text-xs px-2 py-0.5 rounded-full 
                    <?= $tpl['channel'] === 'email' ? 'bg-cyan-900/50 text-cyan-400' : ($tpl['channel'] === 'whatsapp' ? 'bg-green-900/50 text-green-400' : ($tpl['channel'] === 'instagram' ? 'bg-purple-900/50 text-purple-400' : 'bg-blue-900/50 text-blue-400')) ?>">
                    <?= e($tpl['channel']) ?>
                </span>
            </div>
            <?php if ($tpl['subject']): ?>
            <p class="text-xs text-gray-500 mb-2 truncate">Assunto: <?= e($tpl['subject']) ?></p>
            <?php endif; ?>
            <?php if ($tpl['niche']): ?>
            <p class="text-xs text-gray-600 mb-2">Nicho: <?= e($tpl['niche']) ?></p>
            <?php endif; ?>
            <p class="text-sm text-gray-400 line-clamp-3 mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                <?= e($tpl['body']) ?>
            </p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600"><?= date('d/m/Y', strtotime($tpl['created_at'])) ?></span>
                <div class="flex items-center gap-2">
                    <a href="/templates/<?= e($tpl['id']) ?>/edit" class="text-gray-500 hover:text-white transition">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </a>
                    <form method="POST" action="/templates/<?= e($tpl['id']) ?>/delete" x-data x-on:submit.prevent="$store.confirmModal.open('Remover Template', 'Remover este template?', () => $el.submit())">
                        <?= $csrfField ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-gray-500 hover:text-red-400 transition">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>