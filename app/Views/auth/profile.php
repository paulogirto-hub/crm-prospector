<!-- Profile Edit -->
<div class="max-w-2xl mx-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-8">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 rounded-full bg-purple-600 flex items-center justify-center text-white text-2xl font-bold">
                <?= mb_substr(e($user['name'] ?? 'U'), 0, 1) ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white"><?= e($user['name']) ?></h2>
                <p class="text-gray-400 text-sm"><?= e($user['email']) ?> · <?= ucfirst(e($user['role'])) ?></p>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-900/30 border border-red-700 rounded-lg p-4 mb-6">
            <ul class="text-sm text-red-300 space-y-1">
                <?php foreach ($errors as $field => $error): ?>
                <li>• <?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Dados Pessoais -->
        <h3 class="text-lg font-semibold text-white mb-4">Dados Pessoais</h3>
        <form method="POST" action="/profile" class="space-y-5">
            <?= $csrfField ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-400 mb-1.5">Nome completo</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?= e($user['name']) ?>"
                       required
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?= e($user['email']) ?>"
                       required
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
            </div>
            
            <button type="submit" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-2.5 rounded-lg transition">
                Salvar Alterações
            </button>
        </form>
        
        <!-- Trocar Senha -->
        <hr class="border-gray-800 my-8">
        <h3 class="text-lg font-semibold text-white mb-4">Alterar Senha</h3>
        <form method="POST" action="/profile" class="space-y-5">
            <?= $csrfField ?>
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-400 mb-1.5">Senha atual</label>
                <input type="password" 
                       id="current_password" 
                       name="current_password"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1.5">Nova senha</label>
                <input type="password" 
                       id="password" 
                       name="password"
                       minlength="8"
                       placeholder="Mínimo 8 caracteres"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
            </div>
            
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1.5">Confirmar nova senha</label>
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
            </div>
            
            <button type="submit" 
                    class="bg-gray-700 hover:bg-gray-600 text-white font-semibold px-6 py-2.5 rounded-lg transition">
                Alterar Senha
            </button>
        </form>
    </div>
</div>