<!-- Company Create -->
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="/companies" class="text-gray-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-white">Nova Empresa</h1>
    </div>

    <form method="POST" action="/companies" class="space-y-6">
        <?= $csrfField ?>

        <!-- Dados Básicos -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-cyan-400"></i> Dados Básicos
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-xs text-gray-400 mb-1 block">Nome *</label>
                    <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" required
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">CNPJ</label>
                    <input type="text" name="cnpj" value="<?= e($old['cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Nicho</label>
                    <input type="text" name="niche" value="<?= e($old['niche'] ?? '') ?>" placeholder="Ex: Restaurante, Salão..."
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Cidade</label>
                    <input type="text" name="city" value="<?= e($old['city'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Estado</label>
                    <input type="text" name="state" value="<?= e($old['state'] ?? '') ?>" maxlength="2" placeholder="UF"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Telefone</label>
                    <input type="text" name="phone" value="<?= e($old['phone'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Email</label>
                    <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Presença Digital -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i data-lucide="globe" class="w-5 h-5 text-green-400"></i> Presença Digital
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-xs text-gray-400 mb-1 block">Site URL</label>
                    <input type="url" name="site_url" value="<?= e($old['site_url'] ?? '') ?>" placeholder="https://..."
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Instagram</label>
                    <input type="text" name="instagram" value="<?= e($old['instagram'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Facebook</label>
                    <input type="text" name="facebook" value="<?= e($old['facebook'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">YouTube</label>
                    <input type="text" name="youtube" value="<?= e($old['youtube'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">TikTok</label>
                    <input type="text" name="tiktok" value="<?= e($old['tiktok'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Notas</h3>
            <textarea name="notes" rows="4" placeholder="Observações sobre a empresa..."
                      class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none"><?= e($old['notes'] ?? '') ?></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/companies" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Criar Empresa
            </button>
        </div>
    </form>
</div>