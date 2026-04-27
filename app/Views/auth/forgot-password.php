<?php /** @var string $title */ ?>

<div class="min-h-screen bg-gray-900 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            <div class="text-center mb-6">
                <i data-lucide="key" class="w-12 h-12 text-blue-400 mx-auto mb-3"></i>
                <h1 class="text-2xl font-bold text-white">Recuperar Senha</h1>
                <p class="text-gray-400 text-sm mt-2">Informe seu e-mail para receber instruções de redefinição.</p>
            </div>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="bg-red-900/30 border border-red-700/50 rounded-lg p-3 mb-4 text-sm text-red-300">
                    <?= e($_SESSION['flash_error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="bg-green-900/30 border border-green-700/50 rounded-lg p-3 mb-4 text-sm text-green-300">
                    <?= e($_SESSION['flash_success']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/forgot-password" class="space-y-4">
                <?= $csrfField ?? '' ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
                    <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>"
                        class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white placeholder-gray-400"
                        placeholder="seu@email.com">
                </div>

                <button type="submit"
                    class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    Enviar Link de Recuperação
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