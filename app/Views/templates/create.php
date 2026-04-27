<!-- Template Create -->
<div class="space-y-6" x-data="{ 
    channel: 'email',
    vars: [],
    newVar: '',
    addVar() {
        if (this.newVar && !this.vars.includes(this.newVar)) {
            this.vars.push(this.newVar);
            this.newVar = '';
        }
    },
    removeVar(i) { this.vars.splice(i, 1); }
}">
    <div class="flex items-center gap-3">
        <a href="/templates" class="text-gray-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-white">Novo Template</h1>
    </div>

    <form method="POST" action="/templates" class="space-y-6">
        <?= $csrfField ?>

        <!-- Básico -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Informações Básicas</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-xs text-gray-400 mb-1 block">Nome do Template *</label>
                    <input type="text" name="name" placeholder="Ex: Primeiro contato - Restaurante"
                           value="<?= e($old['name'] ?? '') ?>" required
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Canal *</label>
                    <select name="channel" x-model="channel" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        <option value="email">📧 Email</option>
                        <option value="whatsapp">💬 WhatsApp</option>
                        <option value="instagram">📷 Instagram</option>
                        <option value="linkedin">💼 LinkedIn</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Nicho</label>
                    <input type="text" name="niche" placeholder="Ex: Restaurante, Salão..."
                           value="<?= e($old['niche'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Conteúdo</h3>
            
            <!-- Assunto (email) -->
            <div x-show="channel === 'email'" class="mb-4">
                <label class="text-xs text-gray-400 mb-1 block">Assunto</label>
                <input type="text" name="subject" placeholder="Assunto do email..."
                       value="<?= e($old['subject'] ?? '') ?>"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
            </div>

            <!-- Variáveis -->
            <div class="mb-4">
                <label class="text-xs text-gray-400 mb-1 block">Variáveis</label>
                <div class="flex gap-2 mb-2">
                    <input type="text" x-model="newVar" @keydown.enter.prevent="addVar()" placeholder="nome_empresa, nome_contato..."
                           class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none">
                    <button type="button" @click="addVar()" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition">+</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(v, i) in vars" :key="i">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-900/50 text-purple-300 rounded text-xs">
                            {{<span x-text="v"></span>}}
                            <button type="button" @click="removeVar(i)" class="text-purple-400 hover:text-white">✕</button>
                        </span>
                    </template>
                </div>
                <input type="hidden" name="variables" :value="JSON.stringify(vars)">
            </div>

            <!-- Body -->
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Corpo da Mensagem *</label>
                <textarea name="body" rows="10" placeholder="Escreva sua mensagem aqui... Use {{variavel}} para campos dinâmicos." required
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none font-mono"><?= e($old['body'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <a href="/templates" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Criar Template
            </button>
        </div>
    </form>
</div>