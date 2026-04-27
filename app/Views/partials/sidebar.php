<!-- Sidebar -->
<aside x-show="sidebarOpen" 
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       class="fixed left-0 top-0 h-full w-64 bg-gray-900 border-r border-gray-800 flex flex-col z-40">
    
    <!-- Logo -->
    <div class="p-5 border-b border-gray-800">
        <a href="/dashboard" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-cyan-500 flex items-center justify-center text-white font-bold text-sm">P</div>
            <span class="text-lg font-bold bg-gradient-to-r from-purple-400 to-cyan-400 bg-clip-text text-transparent">Prospec CRM</span>
        </a>
    </div>
    
    <!-- Menu -->
    <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        <a href="/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= (($_SERVER['REQUEST_URI'] ?? '/') === '/dashboard') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>
        
        <a href="/prospec" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/prospec') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="search" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Prospecção</span>
        </a>
        
        <a href="/leads" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/leads') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Leads</span>
        </a>
        
        <a href="/pipeline" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/pipeline') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="kanban" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Pipeline</span>
        </a>
        
        <a href="/templates" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/templates') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="mail" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Templates</span>
        </a>
        
        <a href="/agenda" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/agenda') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="calendar" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Agenda</span>
        </a>
        
        <a href="/companies" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/companies') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="building-2" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Empresas</span>
        </a>

        <a href="/reports" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Relatórios</span>
        </a>

        <?php if (\App\Core\Auth::isAdmin()): ?>
        <a href="/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/settings') ? 'bg-gray-800 text-white' : '' ?>">
            <i data-lucide="settings" class="w-5 h-5"></i>
            <span class="text-sm font-medium">Configurações</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <!-- User section -->
    <div class="border-t border-gray-800 p-3">
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
            <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm font-bold">
                <?= mb_substr(e($authUser['name'] ?? 'U'), 0, 1) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-200 truncate"><?= e($authUser['name'] ?? 'Usuário') ?></p>
                <p class="text-xs text-gray-500 truncate"><?= e($authUser['role'] ?? '') ?></p>
            </div>
        </div>
        
        <a href="/profile" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-gray-200 transition text-sm">
            <i data-lucide="user" class="w-4 h-4"></i>
            Meu Perfil
        </a>
        
        <form method="POST" action="/logout" class="mt-1">
            <?= $csrfField ?>
            <button type="submit" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-400 hover:bg-red-900/30 hover:text-red-400 transition text-sm w-full">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                Sair
            </button>
        </form>
    </div>
</aside>

<!-- Mobile overlay -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 md:hidden" x-cloak></div>