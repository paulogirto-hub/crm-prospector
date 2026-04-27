<!-- Header -->
<header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center gap-4">
        <!-- Mobile menu toggle -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-400 hover:text-white">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        
        <h2 class="text-lg font-semibold text-gray-200"><?= e($title ?? 'Prospec CRM') ?></h2>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Notifications placeholder -->
        <button class="relative text-gray-400 hover:text-white transition">
            <i data-lucide="bell" class="w-5 h-5"></i>
        </button>
        
        <!-- User menu -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 text-gray-300 hover:text-white transition">
                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm font-bold">
                    <?= mb_substr(e($authUser['name'] ?? 'U'), 0, 1) ?>
                </div>
                <span class="hidden md:inline text-sm"><?= e($authUser['name'] ?? '') ?></span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            
            <div x-show="open" @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-1 z-50">
                <a href="/profile" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    <i data-lucide="user" class="w-4 h-4"></i> Meu Perfil
                </a>
                <a href="/settings" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    <i data-lucide="settings" class="w-4 h-4"></i> Configurações
                </a>
                <hr class="border-gray-700 my-1">
                <form method="POST" action="/logout">
                    <?= $csrfField ?>
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 text-sm text-red-400 hover:bg-gray-700 hover:text-red-300 transition w-full">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>