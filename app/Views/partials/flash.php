<!-- Flash Messages / Toast Notifications -->
<?php if (!empty($flashes)): ?>
<div class="fixed top-4 right-4 z-50 space-y-2" x-data="{ messages: [] }" x-init="
    messages = <?= json_encode($flashes) ?>;
    setTimeout(() => { messages = []; }, 5000);
">
    <template x-for="(msg, index) in messages" :key="index">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="{
                'bg-green-900/90 border-green-500 text-green-200': msg.type === 'success',
                'bg-red-900/90 border-red-500 text-red-200': msg.type === 'error',
                'bg-yellow-900/90 border-yellow-500 text-yellow-200': msg.type === 'warning',
                'bg-blue-900/90 border-blue-500 text-blue-200': msg.type === 'info'
             }"
             class="flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg backdrop-blur-sm min-w-[300px]">
            <span x-text="msg.type === 'success' ? '✓' : msg.type === 'error' ? '✕' : msg.type === 'warning' ? '⚠' : 'ℹ'" class="text-lg font-bold"></span>
            <span x-text="msg.message" class="text-sm flex-1"></span>
            <button @click="messages.splice(index, 1)" class="text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </template>
</div>
<?php endif; ?>