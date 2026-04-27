<!-- Login Form -->
<div class="bg-gray-900 border border-gray-800 rounded-xl p-8">
    <h2 class="text-2xl font-bold text-white mb-6 text-center">Entrar</h2>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-900/30 border border-red-700 rounded-lg p-4 mb-6">
        <ul class="text-sm text-red-300 space-y-1">
            <?php foreach ($errors as $field => $error): ?>
            <li>• <?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="/login" class="space-y-5">
        <?= $csrfField ?>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="<?= e($old['email'] ?? '') ?>"
                   required 
                   autocomplete="email"
                   placeholder="seu@email.com"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-400 mb-1.5">Senha</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   required 
                   autocomplete="current-password"
                   placeholder="••••••••"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <div class="flex items-center gap-2">
            <input type="checkbox" 
                   id="remember" 
                   name="remember" 
                   value="1"
                   class="w-4 h-4 rounded border-gray-700 bg-gray-800 text-purple-600 focus:ring-purple-500 focus:ring-offset-gray-900">
            <label for="remember" class="text-sm text-gray-400">Lembrar de mim</label>
        </div>
        
        <button type="submit" 
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-lg transition focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900">
            Entrar
        </button>
    </form>
    
    <?php if (\App\Core\Auth::check() && \App\Core\Auth::isAdmin()): ?>
    <div class="mt-6 pt-6 border-t border-gray-800 text-center">
        <a href="/register" class="text-sm text-purple-400 hover:text-purple-300 transition">Criar novo usuário →</a>
    </div>
    <?php endif; ?>
</div>