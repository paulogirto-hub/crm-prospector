<!-- Prompt Modal -->
<div x-show="$store.promptModal.show" x-transition.opacity
     class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center"
     @keydown.escape.window="$store.promptModal.show = false; $store.promptModal.onCancel?.()"
     x-cloak>
    <div x-show="$store.promptModal.show" x-transition
         class="bg-gray-900 border border-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl"
         @click.stop>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-purple-900/50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="message-square" class="w-5 h-5 text-purple-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-white" x-text="$store.promptModal.title"></h3>
        </div>
        <p class="text-sm text-gray-400 mb-3" x-text="$store.promptModal.message"></p>
        <input type="text" x-model="$store.promptModal.value"
               @keydown.enter="$store.promptModal.value.trim() && ($store.promptModal.show = false, $store.promptModal.onConfirm?.($store.promptModal.value.trim()))"
               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none mb-6"
               :placeholder="$store.promptModal.placeholder || 'Digite aqui...'"
               x-ref="promptInput"
               x-effect="$store.promptModal.show && setTimeout(() => $refs.promptInput?.focus(), 50)">
        <div class="flex gap-3 justify-end">
            <button @click="$store.promptModal.show = false; $store.promptModal.onCancel?.()"
                    class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg text-sm hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-gray-600">
                Cancelar
            </button>
            <button @click="$store.promptModal.value.trim() && ($store.promptModal.show = false, $store.promptModal.onConfirm?.($store.promptModal.value.trim()))"
                    :disabled="!$store.promptModal.value.trim()"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-purple-500">
                <span x-text="$store.promptModal.confirmText || 'Confirmar'"></span>
            </button>
        </div>
    </div>
</div>