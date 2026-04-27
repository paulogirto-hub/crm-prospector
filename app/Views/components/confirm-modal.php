<!-- Confirm Modal -->
<div x-show="$store.confirmModal.show" x-transition.opacity
     class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center"
     @keydown.escape.window="$store.confirmModal.show = false; $store.confirmModal.onCancel?.()"
     x-cloak>
    <div x-show="$store.confirmModal.show" x-transition
         class="bg-gray-900 border border-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl"
         @click.stop>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-900/50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-red-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-white" x-text="$store.confirmModal.title"></h3>
        </div>
        <p class="text-sm text-gray-400 mb-6" x-text="$store.confirmModal.message"></p>
        <div class="flex gap-3 justify-end">
            <button @click="$store.confirmModal.show = false; $store.confirmModal.onCancel?.()"
                    class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg text-sm hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-gray-600">
                Cancelar
            </button>
            <button @click="$store.confirmModal.show = false; $store.confirmModal.onConfirm?.()"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500">
                Confirmar
            </button>
        </div>
    </div>
</div>