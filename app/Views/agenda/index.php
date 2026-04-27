<!-- Agenda Index -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Agenda</h1>
            <p class="text-sm text-gray-400 mt-1"><?= $pendingCount ?> pendente<?= $pendingCount !== 1 ? 's' : '' ?> · <?= $completedCount ?> concluída<?= $completedCount !== 1 ? 's' : '' ?></p>
        </div>
        <a href="/agenda/create" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Nova Tarefa
        </a>
    </div>

    <!-- Filtros -->
    <div class="flex gap-2">
        <a href="/agenda?filter=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'pending' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
            Pendentes <?= $pendingCount > 0 ? "({$pendingCount})" : '' ?>
        </a>
        <a href="/agenda?filter=completed" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'completed' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
            Concluídas <?= $completedCount > 0 ? "({$completedCount})" : '' ?>
        </a>
        <a href="/agenda?filter=all" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
            Todas
        </a>
    </div>

    <!-- Tasks -->
    <div class="space-y-3">
        <?php if (empty($tasks)): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-xl text-center py-16 text-gray-500">
            <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <p class="text-lg font-medium">
                <?php if ($filter === 'completed'): ?>Nenhuma tarefa concluída
                <?php elseif ($filter === 'pending'): ?>Nenhuma tarefa pendente 🎉
                <?php else: ?>Nenhuma tarefa criada
                <?php endif; ?>
            </p>
            <a href="/agenda/create" class="inline-block mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                + Nova Tarefa
            </a>
        </div>
        <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4 <?= $task['completed_at'] ? 'opacity-60' : '' ?>">
            <!-- Status -->
            <?php if ($task['completed_at']): ?>
            <div class="w-8 h-8 rounded-full bg-green-900/50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="check" class="w-4 h-4 text-green-400"></i>
            </div>
            <?php else: ?>
            <div class="w-8 h-8 rounded-full bg-yellow-900/50 flex items-center justify-center flex-shrink-0">
                <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white <?= $task['completed_at'] ? 'line-through' : '' ?>"><?= e($task['title']) ?></p>
                <?php if ($task['description']): ?>
                <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e($task['description']) ?></p>
                <?php endif; ?>
                <div class="flex items-center gap-3 mt-1">
                    <?php if ($task['company_name']): ?>
                    <span class="text-xs text-gray-500 flex items-center gap-1">
                        <i data-lucide="building-2" class="w-3 h-3"></i> <?= e($task['company_name']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($task['due_date']): ?>
                    <span class="text-xs flex items-center gap-1 <?php
                        if (!$task['completed_at'] && strtotime($task['due_date']) < time()) echo 'text-red-400';
                        elseif (!$task['completed_at'] && strtotime($task['due_date']) < strtotime('+1 day')) echo 'text-yellow-400';
                        else echo 'text-gray-500';
                    ?>">
                        <i data-lucide="calendar" class="w-3 h-3"></i> <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <?php if (!$task['completed_at']): ?>
                <form method="POST" action="/agenda/<?= e($task['id']) ?>/complete">
                    <?= $csrfField ?>
                    <button type="submit" class="px-3 py-1.5 bg-green-900/30 hover:bg-green-900/50 text-green-400 rounded-lg text-xs font-medium transition">
                        Concluir
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="/agenda/<?= e($task['id']) ?>/delete" x-data x-on:submit.prevent="$store.confirmModal.open('Remover Tarefa', 'Remover esta tarefa?', () => $el.submit())">
                    <?= $csrfField ?>
                    <button type="submit" class="p-1.5 text-gray-600 hover:text-red-400 transition">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>