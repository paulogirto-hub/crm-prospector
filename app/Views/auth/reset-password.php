<?php /** @var string $title, string $token, string $email */ ?>

<div class="min-h-screen bg-gray-900 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            <div class="text-center mb-6">
                <i data-lucide="lock" class="w-12 h-12 text-green-400 mx-auto mb-3"></i>
                <h1 class="text-2xl font-bold text-white">Redefinir Senha</h1>
                <p class="text-gray-400 text-sm mt-2">Crie uma nova senha para sua conta.</p>
            </div>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="bg-red-900/30 border border-red-700/50 rounded-lg p-3 mb-4 text-sm text-red-300">
                    <?= e($_SESSION['flash_error']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/reset-password" class="space-y-4">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
                <input type="hidden" name="email" value="<?= e($email ?? '') ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nova Senha</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-white placeholder-gray-400"
                        placeholder="Mínimo 8 caracteres">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-white placeholder-gray-400"
                        placeholder="Repita a nova senha">
                </div>

                <button type="submit"
                    class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Redefinir Senha
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/login" class="text-blue-400 hover:underline text-sm flex items-center justify-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Voltar ao Login
                </a>
            </div>
        </div>
    </div>
</div>