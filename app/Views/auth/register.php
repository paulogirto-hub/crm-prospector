<!-- Register Form — Suporta admin-create e self-signup (BUG-010) -->
<?php
$isSelfRegister = $isSelfRegister ?? false;
$formAction = $isSelfRegister ? '/signup' : '/register';
$formTitle = $isSelfRegister ? 'Criar Conta' : 'Criar Usuário';
$formSubtitle = $isSelfRegister ? 'Crie sua conta gratuita no Prospec CRM' : 'Preencha os dados do novo usuário';
$submitLabel = $isSelfRegister ? 'Criar Conta' : 'Criar Usuário';
?>

<div class="bg-gray-900 border border-gray-800 rounded-xl p-8">
    <h2 class="text-2xl font-bold text-white mb-2 text-center"><?= e($formTitle) ?></h2>
    <p class="text-gray-500 text-sm text-center mb-6"><?= e($formSubtitle) ?></p>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-900/30 border border-red-700 rounded-lg p-4 mb-6">
        <ul class="text-sm text-red-300 space-y-1">
            <?php foreach ($errors as $field => $error): ?>
            <li>• <?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="<?= e($formAction) ?>" class="space-y-5">
        <?= $csrfField ?>
        
        <div>
            <label for="name" class="block text-sm font-medium text-gray-400 mb-1.5">Nome completo</label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   value="<?= e($old['name'] ?? '') ?>"
                   required
                   placeholder="João da Silva"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="<?= e($old['email'] ?? '') ?>"
                   required
                   placeholder="usuario@email.com"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-400 mb-1.5">Senha</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   required
                   minlength="8"
                   placeholder="Mínimo 8 caracteres"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1.5">Confirmar Senha</label>
            <input type="password" 
                   id="password_confirmation" 
                   name="password_confirmation" 
                   required
                   minlength="8"
                   placeholder="Repita a senha"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
        </div>
        
        <?php if (!$isSelfRegister): ?>
        <!-- Role selection — only for admin creating users -->
        <div>
            <label for="role" class="block text-sm font-medium text-gray-400 mb-1.5">Papel</label>
            <select id="role" 
                    name="role"
                    required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                <option value="seller" <?= ($old['role'] ?? '') === 'seller' ? 'selected' : '' ?>>Vendedor</option>
                <option value="manager" <?= ($old['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Gerente</option>
                <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>
        <?php else: ?>
        <!-- Self-register always creates seller -->
        <input type="hidden" name="role" value="seller">
        <?php endif; ?>
        
        <button type="submit" 
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-lg transition focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900">
            <?= e($submitLabel) ?>
        </button>
    </form>
    
    <div class="mt-6 pt-6 border-t border-gray-800 text-center">
        <?php if ($isSelfRegister): ?>
        <p class="text-sm text-gray-400">Já tem uma conta? <a href="/login" class="text-purple-400 hover:text-purple-300 transition">Faça login</a></p>
        <?php else: ?>
        <a href="/login" class="text-sm text-gray-400 hover:text-purple-400 transition">← Voltar para login</a>
        <?php endif; ?>
    </div>
</div>